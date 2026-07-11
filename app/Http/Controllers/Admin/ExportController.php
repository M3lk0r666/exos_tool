<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Capture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ExportController extends Controller
{
    /** Exportación JSON: resumen + hallazgos + métricas (sección 5.10). */
    public function json(Capture $capture): JsonResponse
    {
        $this->authorize('view', $capture);

        $capture->load(['client:id,name', 'device']);

        $payload = [
            'capture' => [
                'id' => $capture->id,
                'client' => $capture->client?->name,
                'device' => [
                    'sysname' => $capture->device?->sysname,
                    'alias' => $capture->device?->alias,
                    'model' => $capture->device?->model,
                    'system_mac' => $capture->device?->system_mac,
                    'serial_number' => $capture->device?->serial_number,
                ],
                'captured_at' => $capture->captured_at?->toIso8601String(),
                'exos_version' => $capture->exos_version,
                'file_hash' => $capture->file_hash,
                'original_filename' => $capture->original_filename,
            ],
            'summary' => $capture->raw_summary,
            'findings' => $capture->findings()->get()->map(fn ($f) => [
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
            ]),
            'metrics' => $capture->metrics()
                ->get(['category', 'entity', 'metric', 'value', 'extra'])
                ->groupBy('category')
                ->map(fn ($group) => $group->map(fn ($m) => [
                    'entity' => $m->entity,
                    'metric' => $m->metric,
                    'value' => (float) $m->value,
                    'extra' => $m->extra,
                ])->values()),
        ];

        $filename = "analisis_captura_{$capture->id}.json";

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** Exportación Excel (requiere maatwebsite/excel). */
    public function excel(Capture $capture): mixed
    {
        $this->authorize('view', $capture);

        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return back()->with('success',
                'Exportación Excel no disponible: instala el paquete con "composer require maatwebsite/excel".');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CaptureExport($capture),
            "analisis_captura_{$capture->id}.xlsx"
        );
    }
}
