<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Capture;
use App\Models\Device;
use App\Services\AuditLogger;
use App\Services\History\ComparisonService;
use App\Services\History\TrendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Device::class);

        $devices = Device::query()
            ->with(['client:id,name', 'latestCapture.findings'])
            ->withCount('captures')
            ->when($request->filled('client'), fn ($q) => $q->where('client_id', $request->integer('client')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq
                    ->where('sysname', 'like', "%{$search}%")
                    ->orWhere('alias', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('system_mac', 'like', "%{$search}%"));
            })
            ->orderBy('sysname')
            ->paginate(15)
            ->withQueryString();

        $clients = \App\Models\Client::orderBy('name')->pluck('name', 'id');

        $totalDevices = Device::count();
        $stackCount = Device::where('is_stack', true)->count();

        return view('admin.devices.index', compact('devices', 'clients', 'totalDevices', 'stackCount'));
    }

    public function show(Device $device, TrendService $trends): View
    {
        $this->authorize('view', $device);

        $device->load('client:id,name');

        $captures = $device->captures()
            ->orderByDesc('captured_at')
            ->withCount('findings')
            ->get();

        // Eventos de log por día de la última captura: ubica visualmente el
        // día de un incidente (picos de eventos).
        $latestSummary = $captures->firstWhere('status', \App\Enums\CaptureStatus::Completed)?->raw_summary ?? [];
        $logPerDay = $latestSummary['logs']['per_day'] ?? [];

        return view('admin.devices.show', [
            'device' => $device,
            'captures' => $captures,
            'trends' => $trends->forDevice($device),
            'logPerDay' => $logPerDay,
        ]);
    }

    public function edit(Device $device): View
    {
        $this->authorize('update', $device);

        return view('admin.devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        $data = $request->validate([
            'alias' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'criticality' => ['required', Rule::in(['low', 'medium', 'high'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $device->update($data);

        AuditLogger::log('updated', $device, $data);

        return redirect()
            ->route('admin.devices.show', $device)
            ->with('success', 'Equipo actualizado.');
    }

    /** Comparativo entre dos capturas del equipo (sección 5.8). */
    public function compare(Request $request, Device $device, ComparisonService $comparison): View|RedirectResponse
    {
        $this->authorize('view', $device);

        $validated = $request->validate([
            'old' => ['required', 'integer', 'different:new'],
            'new' => ['required', 'integer'],
        ], [], ['old' => 'captura anterior', 'new' => 'captura nueva']);

        $old = Capture::where('device_id', $device->id)->findOrFail($validated['old']);
        $new = Capture::where('device_id', $device->id)->findOrFail($validated['new']);

        return view('admin.devices.compare', [
            'device' => $device->load('client:id,name'),
            'comparison' => $comparison->compare($old, $new),
        ]);
    }
}
