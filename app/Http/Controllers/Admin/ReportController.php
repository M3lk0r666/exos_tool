<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Capture;
use App\Models\Report;
use App\Services\AuditLogger;
use App\Services\Reporting\AreaStatusService;
use App\Services\Reporting\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly AreaStatusService $areas,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::query()
            ->with(['capture.client:id,name', 'capture.device:id,sysname,alias', 'issuer:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('client'), fn ($q) => $q->whereHas(
                'capture', fn ($qq) => $qq->where('client_id', $request->integer('client'))
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $clients = \App\Models\Client::orderBy('name')->pluck('name', 'id');

        return view('admin.reports.index', compact('reports', 'clients'));
    }

    /** Reporte de una captura (crea el borrador v1 si aún no existe). */
    public function forCapture(Capture $capture): RedirectResponse
    {
        $this->authorize('viewAny', Report::class);

        $report = $this->reports->currentFor($capture);

        return redirect()->route('admin.reports.show', $report);
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load(['capture.client', 'capture.device', 'issuer:id,name']);
        $capture = $report->capture;

        $findings = $capture->findings()
            ->with('attachments')
            ->orderByRaw(
                "CASE level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ".
                "WHEN 'low' THEN 4 ELSE 5 END"
            )
            ->get();

        return view('admin.reports.show', [
            'report' => $report,
            'capture' => $capture,
            'findings' => $findings,
            'areas' => $this->areas->compute($findings),
            'severityCounts' => $findings->groupBy(fn ($f) => $f->level->value)->map->count(),
        ]);
    }

    /** Guarda las secciones de texto enriquecido del borrador. */
    public function update(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'executive_summary' => ['nullable', 'string', 'max:65000'],
            'conclusions' => ['nullable', 'string', 'max:65000'],
            'recommendations' => ['nullable', 'string', 'max:65000'],
        ]);

        $report->update($data);

        AuditLogger::log('updated', $report, ['version' => $report->version]);

        return back()->with('success', 'Reporte guardado.');
    }

    /** Emite la versión actual (la congela y genera el PDF). */
    public function issue(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('issue', $report);

        $report = $this->reports->issue($report, $request->user());

        $message = $report->pdf_path
            ? "Reporte v{$report->version} emitido y PDF generado."
            : "Reporte v{$report->version} emitido. El PDF no pudo generarse (verifica que dompdf esté instalado).";

        return redirect()->route('admin.reports.show', $report)->with('success', $message);
    }

    /** Crea una nueva versión borrador a partir de la última emitida. */
    public function newVersion(Report $report): RedirectResponse
    {
        $this->authorize('viewAny', Report::class);

        $draft = $this->reports->draftFor($report->capture);

        return redirect()
            ->route('admin.reports.show', $draft)
            ->with('success', "Borrador v{$draft->version} creado.");
    }

    /** Descarga el PDF (emitido) o genera una vista previa (borrador). */
    public function pdf(Report $report): StreamedResponse
    {
        $this->authorize('view', $report);

        if ($report->pdf_path === null || ! Storage::disk('local')->exists($report->pdf_path)) {
            $path = $this->reports->generatePdf($report);

            if ($report->status === \App\Enums\ReportStatus::Issued) {
                $report->update(['pdf_path' => $path]);
            }

            $report->pdf_path = $path;
        }

        $client = $report->capture->client?->name ?? 'cliente';
        $filename = sprintf(
            'Reporte_%s_%s_v%d.pdf',
            str_replace(' ', '-', $client),
            $report->capture->device?->displayName() ?? 'equipo',
            $report->version
        );

        return Storage::disk('local')->download($report->pdf_path, $filename);
    }
}
