<x-admin-layout :breadcrumbs="[['name' => 'Dashboard']]">

    {{-- Título de página --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Panorama de la red
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Estado del análisis de infraestructura Extreme EXOS por cliente y equipo.
            </p>
        </div>
        <a href="{{ route('admin.captures.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800 shadow-sm">
            <i class="ri-upload-cloud-2-line"></i> Subir archivo
        </a>
    </div>

    {{-- Tarjetas de métricas --}}
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
        @foreach ([
            ['Clientes', $counts['clients'], route('admin.clients.index'), 'ri-building-line', 'text-blue-700 bg-blue-50 dark:bg-blue-900/40'],
            ['Equipos', $counts['devices'], route('admin.devices.index'), 'ri-router-line', 'text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40'],
            ['Análisis realizados', $counts['captures'], route('admin.captures.index'), 'ri-file-list-3-line', 'text-emerald-700 bg-emerald-50 dark:bg-emerald-900/40'],
            ['Hallazgos abiertos', $counts['open_findings'], route('admin.captures.index'), 'ri-error-warning-line', 'text-amber-700 bg-amber-50 dark:bg-amber-900/40'],
            ['Críticos / altos', $counts['critical_findings'], route('admin.captures.index'), 'ri-alarm-warning-line', 'text-red-700 bg-red-50 dark:bg-red-900/40'],
        ] as [$label, $value, $href, $icon, $iconClasses])
            <a href="{{ $href }}"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-blue-400 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $label }}</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1" style="font-family:'Hanken Grotesk',sans-serif;">
                            {{ number_format($value) }}
                        </div>
                    </div>
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-md text-lg {{ $iconClasses }}">
                        <i class="{{ $icon }}"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Fila principal: donut de severidad + equipos que requieren atención --}}
    <div class="grid gap-4 lg:grid-cols-3 mb-6">
        {{-- Donut severidades --}}
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Hallazgos por severidad</h3>
                <span class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Total</span>
            </div>
            <div class="p-4">
                <div data-chart="donut"
                    data-values='@json($severityChart->pluck('value'))'
                    data-labels='@json($severityChart->pluck('label'))'></div>
            </div>
        </div>

        {{-- Equipos que requieren atención --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Equipos que requieren atención</h3>
                <a href="{{ route('admin.devices.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Ver equipos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-2 font-bold">Equipo</th>
                            <th class="px-4 py-2 font-bold">Cliente</th>
                            <th class="px-4 py-2 font-bold text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($worstDevices as $row)
                            <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('admin.devices.show', $row['device']) }}"
                                        class="font-medium text-gray-900 dark:text-white hover:text-blue-700">
                                        {{ $row['device']->displayName() }}
                                    </a>
                                </td>
                                <td class="px-4 py-2.5">{{ $row['device']->client?->name }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $row['worst']->badgeClasses() }}">
                                        {{ $row['worst']->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Sin equipos con hallazgos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Segunda fila: clientes con incidencias + análisis por mes --}}
    <div class="grid gap-4 lg:grid-cols-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Clientes con más hallazgos abiertos</h3>
            </div>
            <ul class="p-2">
                @forelse ($topClients as $client)
                    <li>
                        <a href="{{ route('admin.clients.show', $client) }}"
                            class="flex items-center justify-between px-2 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ $client->open_findings_count }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-2 py-8 text-center text-sm text-gray-400">Sin hallazgos abiertos.</li>
                @endforelse
            </ul>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Análisis por mes</h3>
            </div>
            <div class="p-4">
                <div data-chart="bar"
                    data-values='@json($capturesPerMonth->pluck('value'))'
                    data-labels='@json($capturesPerMonth->pluck('label'))'></div>
            </div>
        </div>
    </div>

    {{-- Tercera fila: últimos análisis y reportes --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Últimos análisis</h3>
                <a href="{{ route('admin.captures.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Ver todos</a>
            </div>
            <ul class="divide-y divide-gray-50 dark:divide-gray-700/50 text-sm">
                @forelse ($latestCaptures as $capture)
                    <li class="flex items-center gap-2 px-4 py-2.5">
                        <a href="{{ route('admin.captures.show', $capture) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-700 truncate">
                            #{{ $capture->id }} {{ $capture->device?->displayName() ?? $capture->original_filename }}
                        </a>
                        <span class="text-xs text-gray-400 truncate">{{ $capture->client?->name }}</span>
                        <span class="ms-auto text-[11px] font-bold uppercase px-2 py-0.5 rounded-md shrink-0 {{ $capture->status->badgeClasses() }}">
                            {{ $capture->status->label() }}
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-gray-400">Sin capturas.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Últimos reportes</h3>
                <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Ver todos</a>
            </div>
            <ul class="divide-y divide-gray-50 dark:divide-gray-700/50 text-sm">
                @forelse ($latestReports as $report)
                    <li class="flex items-center gap-2 px-4 py-2.5">
                        <a href="{{ route('admin.reports.show', $report) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-blue-700 truncate">
                            {{ $report->capture?->device?->displayName() ?? 'Captura #'.$report->capture_id }} — v{{ $report->version }}
                        </a>
                        <span class="text-xs text-gray-400 truncate">{{ $report->capture?->client?->name }}</span>
                        <span class="ms-auto text-[11px] font-bold uppercase px-2 py-0.5 rounded-md shrink-0
                            {{ $report->status === App\Enums\ReportStatus::Draft
                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                            {{ $report->status->label() }}
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-gray-400">Sin reportes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
