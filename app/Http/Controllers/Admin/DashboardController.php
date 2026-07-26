<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FindingSeverity;
use App\Http\Controllers\Controller;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Models\Report;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $counts = [
            'clients' => Client::count(),
            'devices' => Device::count(),
            'captures' => Capture::where('status', 'completed')->count(),
            'reports_issued' => Report::where('status', 'issued')->count(),
            'open_findings' => Finding::whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'critical_findings' => Finding::where('status', '!=', 'false_positive')
                ->whereIn('level', ['critical', 'high'])->count(),
        ];

        // Hallazgos por severidad (excluye falsos positivos)
        $severityCounts = Finding::where('status', '!=', 'false_positive')
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        $severityChart = collect(['critical', 'high', 'medium', 'low', 'informational'])
            ->map(fn ($level) => [
                'label' => FindingSeverity::from($level)->label(),
                'value' => (int) ($severityCounts[$level] ?? 0),
            ]);

        // Capturas completadas por mes (últimos 6 meses)
        $capturesPerMonth = collect(range(5, 0))
            ->map(function ($monthsAgo) {
                $month = now()->subMonths($monthsAgo);

                return [
                    'label' => $month->format('m/Y'),
                    'value' => Capture::where('status', 'completed')
                        ->whereYear('captured_at', $month->year)
                        ->whereMonth('captured_at', $month->month)
                        ->count(),
                ];
            });

        // Equipos en peor estado (semáforo por última captura)
        $worstDevices = Device::with(['client:id,name', 'latestCapture.findings'])
            ->get()
            ->map(fn (Device $d) => ['device' => $d, 'worst' => $d->worstSeverity()])
            ->filter(fn ($row) => $row['worst'] !== null)
            ->sortByDesc(fn ($row) => $row['worst']->weight())
            ->take(6)
            ->values();

        // Clientes con más hallazgos abiertos (filtrado en PHP: HAVING sobre
        // columna calculada no es portable entre MySQL y SQLite)
        $topClients = Client::withCount([
            'findings as open_findings_count' => fn ($q) => $q->whereIn('findings.status', ['open', 'acknowledged', 'in_progress']),
        ])
            ->orderByDesc('open_findings_count')
            ->take(10)
            ->get()
            ->filter(fn ($c) => $c->open_findings_count > 0)
            ->take(5)
            ->values();

        $latestCaptures = Capture::with(['client:id,name', 'device:id,sysname,alias'])
            ->latest('id')->take(5)->get();

        $latestReports = Report::with(['capture.client:id,name', 'capture.device:id,sysname,alias'])
            ->latest('id')->take(5)->get();

        return view('admin.dashboard', compact(
            'counts', 'severityChart', 'capturesPerMonth',
            'worstDevices', 'topClients', 'latestCaptures', 'latestReports'
        ));
    }
}
