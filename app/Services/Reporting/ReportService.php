<?php

namespace App\Services\Reporting;

use App\Enums\ReportStatus;
use App\Models\Capture;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Versionado del reporte (sección 5.6): borradores y versiones emitidas.
 * - Cada captura tiene N versiones de reporte.
 * - Editar siempre ocurre sobre un borrador; emitir lo congela y genera el PDF.
 * - Editar después de emitir crea una nueva versión borrador con el contenido anterior.
 */
class ReportService
{
    /** Devuelve el borrador activo de la captura, creándolo si no existe. */
    public function draftFor(Capture $capture): Report
    {
        $latest = $capture->reports()->orderByDesc('version')->first();

        if ($latest !== null && $latest->status === ReportStatus::Draft) {
            return $latest;
        }

        return Report::create([
            'capture_id' => $capture->id,
            'version' => $latest !== null ? $latest->version + 1 : 1,
            'executive_summary' => $latest?->executive_summary,
            'conclusions' => $latest?->conclusions,
            'recommendations' => $latest?->recommendations,
            'status' => ReportStatus::Draft,
        ]);
    }

    /** Reporte a mostrar: el borrador activo o la última versión emitida. */
    public function currentFor(Capture $capture): Report
    {
        return $capture->reports()->orderByDesc('version')->first()
            ?? $this->draftFor($capture);
    }

    /** Emite el borrador: lo congela y genera el PDF. */
    public function issue(Report $report, User $user): Report
    {
        $report->update([
            'status' => ReportStatus::Issued,
            'issued_by' => $user->id,
            'issued_at' => now(),
        ]);

        try {
            $report->update(['pdf_path' => $this->generatePdf($report)]);
        } catch (Throwable $e) {
            // La emisión no se bloquea si el PDF falla (p. ej. dompdf sin instalar);
            // el PDF puede regenerarse desde la vista del reporte.
            Log::warning('No se pudo generar el PDF al emitir', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
        }

        AuditLogger::log('issued', $report, ['version' => $report->version]);

        return $report->fresh();
    }

    /** Genera el PDF del reporte y devuelve la ruta en storage. */
    public function generatePdf(Report $report): string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('DomPDF no está instalado (composer require barryvdh/laravel-dompdf).');
        }

        $report->loadMissing('capture.device', 'capture.client', 'issuer');
        $capture = $report->capture;

        $findings = $capture->findings()
            ->with('attachments')
            ->where('status', '!=', 'false_positive')
            ->orderByRaw(
                "CASE level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ".
                "WHEN 'low' THEN 4 ELSE 5 END"
            )
            ->get();

        $areaService = new AreaStatusService;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', [
            'report' => $report,
            'capture' => $capture,
            'client' => $capture->client,
            'device' => $capture->device,
            'summary' => $capture->raw_summary ?? [],
            'findings' => $findings,
            'areas' => $areaService->compute($findings),
            'severityCounts' => $findings->groupBy(fn ($f) => $f->level->value)->map->count(),
            'companyName' => \App\Models\Setting::get('branding.company_name', 'EXOS-Tool'),
            'companyLogo' => \App\Models\Setting::get('branding.company_logo', ''),
            'footerText' => \App\Models\Setting::get('branding.footer_text', ''),
        ])->setPaper('letter')->setOption('isPhpEnabled', true);

        $path = sprintf(
            'reports/%d/reporte_capture%d_v%d.pdf',
            $capture->client_id,
            $capture->id,
            $report->version
        );

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
