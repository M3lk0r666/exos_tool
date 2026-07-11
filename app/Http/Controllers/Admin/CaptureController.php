<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CaptureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaptureRequest;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CaptureController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Capture::class);

        $captures = Capture::query()
            ->with(['client:id,name', 'device:id,sysname,alias,model'])
            ->when($request->filled('client'), fn ($q) => $q->where('client_id', $request->integer('client')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::orderBy('name')->pluck('name', 'id');

        return view('admin.captures.index', compact('captures', 'clients'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Capture::class);

        $clients = Client::orderBy('name')->pluck('name', 'id');
        $selectedClient = $request->integer('client') ?: null;

        return view('admin.captures.create', compact('clients', 'selectedClient'));
    }

    public function store(StoreCaptureRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->integer('client_id'));

        $created = 0;
        $skipped = [];

        foreach ($request->file('files', []) as $file) {
            $result = $this->storeOne($client, $file);
            if ($result === true) {
                $created++;
            } else {
                $skipped[] = $result;
            }
        }

        $message = $created === 1
            ? '1 archivo en cola de procesamiento.'
            : "{$created} archivos en cola de procesamiento.";

        if ($skipped !== []) {
            $message .= ' Omitidos: '.implode(' ', $skipped);
        }

        return redirect()
            ->route('admin.captures.index', ['client' => $client->id])
            ->with('success', $message);
    }

    /** @return true|string true si se creó; mensaje si se omitió */
    private function storeOne(Client $client, UploadedFile $file): true|string
    {
        $name = $file->getClientOriginalName();

        // Validación de codificación: texto plano UTF-8 (o ASCII/latin1 convertible).
        $sample = (string) file_get_contents($file->getRealPath(), length: 65536);
        if (str_contains($sample, "\x00")) {
            return "«{$name}» no es un archivo de texto.";
        }

        $hash = hash_file('sha256', $file->getRealPath());

        if ($existing = Capture::where('file_hash', $hash)->first()) {
            return "«{$name}» es duplicado de la captura #{$existing->id}.";
        }

        $path = $file->storeAs(
            "captures/{$client->id}",
            $hash.'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $capture = Capture::create([
            'client_id' => $client->id,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'original_filename' => $name,
            'file_path' => $path,
            'file_hash' => $hash,
            'file_size' => $file->getSize(),
            'status' => CaptureStatus::Pending,
        ]);

        AuditLogger::log('uploaded', $capture, ['filename' => $name, 'client' => $client->name]);

        ProcessCaptureJob::dispatch($capture);

        return true;
    }

    public function show(Capture $capture): View
    {
        $this->authorize('view', $capture);

        $capture->load(['client:id,name', 'device', 'uploader:id,name']);

        $metricsSummary = $capture->metrics()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('total', 'category');

        $findings = $capture->findings()
            ->orderByRaw(
                "CASE level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ".
                "WHEN 'low' THEN 4 ELSE 5 END"
            )
            ->get();

        return view('admin.captures.show', compact('capture', 'metricsSummary', 'findings'));
    }

    public function destroy(Capture $capture): RedirectResponse
    {
        $this->authorize('delete', $capture);

        Storage::disk('local')->delete($capture->file_path);
        AuditLogger::log('deleted', $capture, ['filename' => $capture->original_filename]);
        $capture->delete();

        return redirect()
            ->route('admin.captures.index')
            ->with('success', 'Captura eliminada.');
    }

    /** Estado de capturas para polling desde la UI. */
    public function status(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Capture::class);

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));

        $captures = Capture::whereIn('id', $ids)
            ->get(['id', 'status', 'error_message', 'device_id', 'captured_at'])
            ->map(fn (Capture $c) => [
                'id' => $c->id,
                'status' => $c->status->value,
                'label' => $c->status->label(),
                'error' => $c->error_message,
            ]);

        return response()->json($captures);
    }
}
