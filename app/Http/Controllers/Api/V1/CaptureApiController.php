<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CaptureStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API REST v1 (sección 5.10): subir archivos y consultar análisis,
 * hallazgos y métricas. Protegida con Sanctum.
 */
class CaptureApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Capture::class);

        $captures = Capture::with(['client:id,name', 'device:id,sysname,alias,serial_number'])
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('device_id'), fn ($q) => $q->where('device_id', $request->integer('device_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(min($request->integer('per_page') ?: 25, 100));

        return response()->json($captures->through(fn (Capture $c) => $this->captureSummary($c)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Capture::class);

        $maxMb = (int) Setting::get('upload.max_size_mb', 50);
        $extensions = Setting::get('upload.allowed_extensions', ['txt', 'log']);

        $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'file' => ['required', 'file', 'extensions:'.implode(',', $extensions), 'max:'.($maxMb * 1024)],
        ]);

        $client = Client::findOrFail($request->integer('client_id'));
        $file = $request->file('file');

        $hash = hash_file('sha256', $file->getRealPath());

        if ($existing = Capture::where('file_hash', $hash)->first()) {
            return response()->json([
                'message' => 'Archivo duplicado.',
                'capture_id' => $existing->id,
            ], 409);
        }

        $path = $file->storeAs(
            "captures/{$client->id}",
            $hash.'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $capture = Capture::create([
            'client_id' => $client->id,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_hash' => $hash,
            'file_size' => $file->getSize(),
            'status' => CaptureStatus::Pending,
        ]);

        AuditLogger::log('uploaded', $capture, ['via' => 'api', 'filename' => $capture->original_filename]);

        ProcessCaptureJob::dispatch($capture);

        return response()->json([
            'message' => 'Archivo en cola de procesamiento.',
            'capture_id' => $capture->id,
            'status' => $capture->status->value,
        ], 201);
    }

    public function show(Capture $capture): JsonResponse
    {
        $this->authorize('view', $capture);

        $capture->load(['client:id,name', 'device']);

        return response()->json($this->captureSummary($capture) + [
            'summary' => $capture->raw_summary,
            'parser_warnings' => $capture->parser_warnings,
            'error_message' => $capture->error_message,
        ]);
    }

    public function findings(Capture $capture): JsonResponse
    {
        $this->authorize('view', $capture);

        return response()->json([
            'capture_id' => $capture->id,
            'findings' => $capture->findings()->get()->map(fn ($f) => [
                'id' => $f->id,
                'rule_code' => $f->rule_code,
                'level' => $f->level->value,
                'area' => $f->area,
                'entity' => $f->entity,
                'title' => $f->title,
                'description' => $f->description,
                'impact' => $f->impact,
                'recommendation' => $f->recommendation,
                'evidence' => $f->evidence,
                'file_location' => $f->file_location,
                'status' => $f->status->value,
                'is_manual' => $f->is_manual,
                'first_seen_capture_id' => $f->first_seen_capture_id,
            ]),
        ]);
    }

    public function metrics(Request $request, Capture $capture): JsonResponse
    {
        $this->authorize('view', $capture);

        $metrics = $capture->metrics()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->get(['category', 'entity', 'metric', 'value', 'extra']);

        return response()->json([
            'capture_id' => $capture->id,
            'metrics' => $metrics->map(fn ($m) => [
                'category' => $m->category,
                'entity' => $m->entity,
                'metric' => $m->metric,
                'value' => (float) $m->value,
                'extra' => $m->extra,
            ]),
        ]);
    }

    private function captureSummary(Capture $c): array
    {
        return [
            'id' => $c->id,
            'client' => $c->client?->only(['id', 'name']),
            'device' => $c->device?->only(['id', 'sysname', 'alias', 'serial_number']),
            'status' => $c->status->value,
            'captured_at' => $c->captured_at?->toIso8601String(),
            'uploaded_at' => $c->uploaded_at?->toIso8601String(),
            'exos_version' => $c->exos_version,
            'uptime_seconds' => $c->uptime_seconds,
            'boot_count' => $c->boot_count,
            'original_filename' => $c->original_filename,
            'file_hash' => $c->file_hash,
        ];
    }
}
