<x-admin-layout :breadcrumbs="[['name' => 'Dashboard']]">

    {{-- Contadores --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 mb-4">
        @foreach ([
            ['Clientes', $counts['clients'], route('admin.clients.index'), 'text-blue-600'],
            ['Equipos', $counts['devices'], route('admin.devices.index'), 'text-indigo-600'],
            ['Análisis realizados', $counts['captures'], route('admin.captures.index'), 'text-green-600'],
            ['Reportes emitidos', $counts['reports_issued'], route('admin.reports.index'), 'text-gray-600'],
            ['Hallazgos abiertos', $counts['open_findings'], route('admin.captures.index'), 'text-red-600'],
        ] as [$label, $value, $href, $color])
            <a href="{{ $href }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow transition-shadow">
                <div class="text-3xl font-bold {{ $color }} dark:text-white">{{ number_format($value) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
            </a>
        @endforeach
    </div>

    {{-- Gráficos --}}
    <div class="grid gap-4 lg:grid-cols-2 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Hallazgos por severidad</h3>
            <div data-chart="donut"
                data-values='@json($severityChart->pluck('value'))'
                data-labels='@json($severityChart->pluck('label'))'></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Análisis por mes</h3>
            <div data-chart="bar"
                data-values='@json($capturesPerMonth->pluck('value'))'
                data-labels='@json($capturesPerMonth->pluck('label'))'></div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mb-4">
        {{-- Equipos en peor estado --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Equipos que requieren atención</h3>
            <ul class="space-y-2">
                @forelse ($worstDevices as $row)
                    <li class="flex items-center gap-2 text-sm">
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full shrink-0 {{ $row['worst']->badgeClasses() }}">
                            {{ $row['worst']->label() }}
                        </span>
                        <a href="{{ route('admin.devices.show', $row['device']) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-600">
                            {{ $row['device']->displayName() }}
                        </a>
                        <span class="text-xs text-gray-400">{{ $row['device']->client?->name }}</span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">Sin equipos con hallazgos.</li>
                @endforelse
            </ul>
        </div>

        {{-- Clientes con más incidencias --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Clientes con más hallazgos abiertos</h3>
            <ul class="space-y-2">
                @forelse ($topClients as $client)
                    <li class="flex items-center justify-between text-sm">
                        <a href="{{ route('admin.clients.show', $client) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-600">{{ $client->name }}</a>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">
                            {{ $client->open_findings_count }}
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">Sin hallazgos abiertos.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Últimas capturas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Últimos análisis</h3>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                @forelse ($latestCaptures as $capture)
                    <li class="py-2 flex items-center gap-2">
                        <a href="{{ route('admin.captures.show', $capture) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-600">
                            #{{ $capture->id }} {{ $capture->device?->displayName() ?? $capture->original_filename }}
                        </a>
                        <span class="text-xs text-gray-400">{{ $capture->client?->name }}</span>
                        <span class="ms-auto text-xs font-medium px-2 py-0.5 rounded-full {{ $capture->status->badgeClasses() }}">
                            {{ $capture->status->label() }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-gray-400">Sin capturas.</li>
                @endforelse
            </ul>
        </div>

        {{-- Últimos reportes --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Últimos reportes</h3>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                @forelse ($latestReports as $report)
                    <li class="py-2 flex items-center gap-2">
                        <a href="{{ route('admin.reports.show', $report) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-600">
                            {{ $report->capture?->device?->displayName() ?? 'Captura #'.$report->capture_id }} — v{{ $report->version }}
                        </a>
                        <span class="text-xs text-gray-400">{{ $report->capture?->client?->name }}</span>
                        <span class="ms-auto text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $report->status === App\Enums\ReportStatus::Draft
                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                            {{ $report->status->label() }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-gray-400">Sin reportes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
