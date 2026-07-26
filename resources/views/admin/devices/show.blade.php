<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Equipos', 'href' => route('admin.devices.index')],
    ['name' => $device->displayName()],
]">
    {{-- Encabezado del equipo --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">{{ $device->displayName() }}</h1>
                    @if ($device->is_stack)
                        <span class="bg-purple-100 text-purple-800 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-md dark:bg-purple-900 dark:text-purple-300">Stack</span>
                    @endif
                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Criticidad: {{ $device->criticality->label() }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $device->client?->name }} · {{ $device->model ?? 'modelo n/d' }}
                    · Serie: <span class="font-mono">{{ $device->serial_number ?? 'n/d' }}</span>
                    · MAC: <span class="font-mono">{{ $device->system_mac }}</span>
                    @if ($device->site) · Sitio: {{ $device->site }} @endif
                </p>
            </div>

            @can('update', $device)
                <a href="{{ route('admin.devices.edit', $device) }}"
                    class="lg:ms-auto inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    <i class="ri-edit-line"></i> Editar equipo
                </a>
            @endcan
        </div>
        @if ($device->notes)
            <p class="mt-3 p-3 text-sm text-gray-700 bg-gray-50 rounded-md dark:bg-gray-700 dark:text-gray-300">{{ $device->notes }}</p>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3 mb-4">
        {{-- Línea de tiempo de capturas + comparador --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Capturas ({{ $captures->count() }})</h3>
            </div>
            <div class="p-5">
                @if ($captures->count() >= 2)
                    <form method="GET" action="{{ route('admin.devices.compare', $device) }}"
                        class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md space-y-2">
                        <div class="text-[11px] font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Comparar capturas</div>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="old" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach ($captures as $c)
                                    <option value="{{ $c->id }}" @selected($loop->index === 1)>
                                        #{{ $c->id }} — {{ $c->captured_at?->format('d/m/Y H:i') }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="new" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach ($captures as $c)
                                    <option value="{{ $c->id }}" @selected($loop->first)>
                                        #{{ $c->id }} — {{ $c->captured_at?->format('d/m/Y H:i') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full py-1.5 text-xs font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800">
                            Comparar
                        </button>
                    </form>
                @endif

                <ol class="relative border-s border-gray-200 dark:border-gray-700 ms-2">
                    @forelse ($captures as $capture)
                        <li class="mb-5 ms-4">
                            <div class="absolute w-3 h-3 rounded-full mt-1.5 -start-1.5 border border-white dark:border-gray-800
                                {{ $capture->status === App\Enums\CaptureStatus::Completed ? 'bg-emerald-500' : 'bg-gray-300' }}"></div>
                            <time class="text-xs text-gray-400">{{ $capture->captured_at?->format('d/m/Y H:i') ?? 'sin fecha' }}</time>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('admin.captures.show', $capture) }}" class="hover:text-blue-700">Captura #{{ $capture->id }}</a>
                                <span class="text-xs text-gray-400">· EXOS {{ $capture->exos_version ?? 'n/d' }}</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $capture->findings_count }} hallazgo(s)
                                · uptime {{ $capture->uptime_seconds !== null ? round($capture->uptime_seconds / 86400, 1).' días' : 'n/d' }}
                            </div>
                        </li>
                    @empty
                        <p class="text-sm text-gray-400">Sin capturas procesadas.</p>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Gráficos de tendencia --}}
        <div class="lg:col-span-2 space-y-4">
            @if (count($trends['labels']) < 2)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-sm text-gray-400">
                    Los gráficos de tendencia se muestran a partir de la segunda captura del equipo.
                    Sube capturas periódicas para ver la evolución de temperatura, memoria, CPU, errores
                    y hallazgos a lo largo del tiempo.
                </div>
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ([
                        ['Temperatura por slot (°C)', 'line', $trends['temperature']],
                        ['Memoria libre (%)', 'line', $trends['memory']],
                        ['CPU sistema 1h (%)', 'line', $trends['cpu']],
                        ['Errores CRC totales', 'line', $trends['crc']],
                    ] as [$chartTitle, $chartType, $chartSeries])
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $chartTitle }}</h4>
                            </div>
                            <div class="p-4">
                                @if ($chartSeries === [])
                                    <p class="text-xs text-gray-400">Sin datos (normal en EXOS 12.x).</p>
                                @else
                                    <div data-chart="{{ $chartType }}" data-series='@json($chartSeries)'></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Hallazgos por severidad</h4>
                    </div>
                    <div class="p-4">
                        <div data-chart="severity" data-series='@json($trends['severity'])' data-labels='@json($trends['labels'])'></div>
                    </div>
                </div>
            @endif

            @if (! empty($logPerDay))
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Eventos de log por día (última captura)</h4>
                        <p class="text-xs text-gray-400">Un pico de eventos suele señalar el día de un incidente.</p>
                    </div>
                    <div class="p-4">
                        <div data-chart="bar"
                            data-values='@json(array_values($logPerDay))'
                            data-labels='@json(array_keys($logPerDay))'></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
