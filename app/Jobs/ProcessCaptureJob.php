<?php

namespace App\Jobs;

use App\Enums\CaptureStatus;
use App\Models\Capture;
use App\Services\Analysis\AnalysisEngine;
use App\Services\CaptureMetricsRecorder;
use App\Services\DeviceResolver;
use App\Services\Parser\ExosParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCaptureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Archivos de stack grandes: dar margen suficiente. */
    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public Capture $capture) {}

    public function handle(
        ExosParser $parser,
        DeviceResolver $deviceResolver,
        CaptureMetricsRecorder $metricsRecorder,
        AnalysisEngine $analysisEngine,
    ): void {
        $capture = $this->capture->fresh();

        if ($capture === null || $capture->status === CaptureStatus::Completed) {
            return;
        }

        $capture->update(['status' => CaptureStatus::Processing]);

        try {
            $text = Storage::disk('local')->get($capture->file_path);

            $parsed = $parser->parse($text);

            DB::transaction(function () use ($capture, $parsed, $text, $deviceResolver, $metricsRecorder, $analysisEngine) {
                $device = $deviceResolver->resolve($capture->client, $parsed);

                $capture->update([
                    'device_id' => $device?->id,
                    // Fecha de referencia: Current Time del archivo (Anexo A);
                    // si no se pudo extraer, se usa la fecha de subida.
                    'captured_at' => $parsed->capturedAt ?? $capture->uploaded_at,
                    'exos_version' => $parsed->exosVersion,
                    'uptime_seconds' => $parsed->uptimeSeconds,
                    'boot_count' => $parsed->bootCount,
                    'parser_warnings' => $parsed->warnings ?: null,
                    'raw_summary' => $parsed->toRawSummary(),
                    'status' => CaptureStatus::Completed,
                    'error_message' => null,
                ]);

                $metricsRecorder->record($capture, $parsed);

                // Motor de análisis (Fase 3): genera hallazgos con evidencia.
                $analysisEngine->analyze($capture->fresh(), $parsed, $text);
            });

            $this->notifyIfCritical($capture->fresh());
        } catch (Throwable $e) {
            Log::error('Error procesando captura', [
                'capture_id' => $capture->id,
                'error' => $e->getMessage(),
            ]);

            $capture->update([
                'status' => CaptureStatus::Error,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }
    }

    /** Notifica a administradores e ingenieros si hay hallazgos Critical/High. */
    private function notifyIfCritical(?Capture $capture): void
    {
        if ($capture === null) {
            return;
        }

        $critical = $capture->findings()->where('level', 'critical')->count();
        $high = $capture->findings()->where('level', 'high')->count();

        if ($critical === 0 && $high === 0) {
            return;
        }

        try {
            $recipients = \App\Models\User::role(['admin', 'engineer'])->get();

            \Illuminate\Support\Facades\Notification::send(
                $recipients,
                new \App\Notifications\CriticalFindingsDetected($capture, $critical, $high)
            );
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar la notificación de hallazgos críticos', [
                'capture_id' => $capture->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        $this->capture->fresh()?->update([
            'status' => CaptureStatus::Error,
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
