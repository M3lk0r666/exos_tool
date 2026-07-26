<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->withCount(['devices', 'captures', 'reports'])
            ->withCount([
                'findings as open_findings_count' => fn ($q) => $q->whereIn('findings.status', ['open', 'acknowledged', 'in_progress']),
                'findings as critical_findings_count' => fn ($q) => $q
                    ->whereIn('findings.level', ['critical', 'high'])
                    ->where('findings.status', '!=', 'false_positive'),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('admin.clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('client-logos', 'public');
        }

        $client = Client::create($data);

        AuditLogger::log('created', $client, ['name' => $client->name]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', "Cliente «{$client->name}» creado correctamente.");
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load([
            'devices' => fn ($q) => $q->orderBy('sysname'),
            'devices.latestCapture.findings',
        ]);

        // Semáforo por equipo + resumen de hallazgos del cliente (sección 5.9)
        $severityCounts = $client->findings()
            ->where('findings.status', '!=', 'false_positive')
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        return view('admin.clients.show', compact('client', 'severityCounts'));
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('admin.clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('client-logos', 'public');
        }

        $original = $client->only(array_keys($data));
        $client->update($data);

        AuditLogger::log('updated', $client, [
            'before' => $original,
            'after' => $client->only(array_keys($data)),
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', "Cliente «{$client->name}» actualizado correctamente.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete(); // soft delete: conserva histórico de equipos y capturas

        AuditLogger::log('deleted', $client, ['name' => $client->name]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', "Cliente «{$client->name}» eliminado.");
    }
}
