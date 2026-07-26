<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Capturas', 'href' => route('admin.captures.index')],
    ['name' => 'Captura #'.$capture->id],
]">
    @php($summary = $capture->raw_summary ?? [])
    @php($serials = $summary['serial_numbers'] ?? [])

    {{-- ===== Encabezado: identificación del equipo ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-4">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">Captura #{{ $capture->id }}</h1>
                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $capture->status->badgeClasses() }}"
                    @if ($capture->error_message) title="{{ $capture->error_message }}" @endif>
                    {{ $capture->status->label() }}
                </span>
                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md
                    {{ $capture->isLogAnalysis()
                        ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
                        : 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                    {{ $capture->analysisTypeLabel() }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $capture->client?->name }}</span>
            </div>

            @if ($capture->status === App\Enums\CaptureStatus::Completed)
                <div class="lg:ms-auto flex flex-wrap gap-2">
                    <a href="{{ route('admin.captures.export.json', $capture) }}"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <i class="ri-file-code-line"></i> JSON
                    </a>
                    <a href="{{ route('admin.captures.export.excel', $capture) }}"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <i class="ri-file-excel-2-line"></i> Excel
                    </a>
                    @can('viewAny', App\Models\Report::class)
                        <a href="{{ route('admin.reports.for-capture', $capture) }}"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800">
                            <i class="ri-file-chart-line"></i> Generar / abrir reporte
                        </a>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Datos destacados: equipo, serie(s), versión --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-3">
            <div class="p-3 rounded-md border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                <div class="text-xs uppercase font-semibold text-blue-700 dark:text-blue-300">Equipo</div>
                <div class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
                    {{ $capture->device?->displayName() ?? $summary['sysname'] ?? '—' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $summary['system_type'] ?? '' }}
                    @if (! empty($summary['is_stack']))
                        <span class="ms-1 bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Stack</span>
                    @endif
                </div>
            </div>

            <div class="p-3 rounded-md border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                <div class="text-xs uppercase font-semibold text-blue-700 dark:text-blue-300">Número(s) de serie</div>
                @if ($serials === [])
                    <div class="text-lg font-bold text-gray-400">—</div>
                @elseif (isset($serials['Switch']))
                    <div class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $serials['Switch'] }}</div>
                @else
                    <div class="font-mono text-sm font-semibold text-gray-900 dark:text-white leading-snug">
                        @foreach ($serials as $unit => $serial)
                            <span class="text-gray-400">{{ $unit }}:</span> {{ $serial }}<br>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="p-3 rounded-md border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                <div class="text-xs uppercase font-semibold text-blue-700 dark:text-blue-300">Versión EXOS</div>
                <div class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $capture->exos_version ?? '—' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">BootROM {{ $summary['bootrom'] ?? '—' }}</div>
            </div>

            <div class="p-3 rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600">
                <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Fecha de captura</div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $capture->captured_at?->format('d/m/Y H:i') ?? '—' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Uptime: {{ $summary['uptime_text'] ?? '—' }}</div>
            </div>
        </div>

        {{-- Metadatos secundarios --}}
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            <span><span class="font-semibold">Archivo:</span> {{ $capture->original_filename }} ({{ number_format($capture->file_size / 1024) }} KB)</span>
            <span><span class="font-semibold">Subido:</span> {{ $capture->uploaded_at?->format('d/m/Y H:i') }}@if ($capture->uploader) por {{ $capture->uploader->name }}@endif</span>
            <span><span class="font-semibold">Boot count:</span> {{ $capture->boot_count ?? '—' }}</span>
            <span class="font-mono" title="SHA-256"><span class="font-sans font-semibold">SHA-256:</span> {{ substr($capture->file_hash, 0, 20) }}…</span>
        </div>

        @if ($capture->error_message)
            <div class="mt-3 p-3 text-sm text-red-800 bg-red-50 rounded-lg dark:bg-gray-700 dark:text-red-400">
                {{ $capture->error_message }}
            </div>
        @endif
    </div>

    {{-- ===== Resumen del equipo ===== --}}
    @if (! empty($summary))
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resumen del equipo</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 text-sm">
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Firmware</div>
                    <div class="font-medium text-gray-900 dark:text-white font-mono">{{ $summary['exos_version'] ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $summary['firmware_build_date'] ?? '' }}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Ventiladores / PSU</div>
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ $summary['fans']['ok'] ?? 0 }} OK
                        @if (! empty($summary['fans']['failed']))
                            · <span class="text-red-500">{{ count($summary['fans']['failed']) }} en falla</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-400">
                        PSU: {{ $summary['power']['on'] ?? 0 }} encendidas, {{ $summary['power']['failed'] ?? 0 }} en falla
                    </div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">CPU 1h / Memoria</div>
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ isset($summary['cpu_1h']) && $summary['cpu_1h'] !== null ? $summary['cpu_1h'].' %' : 'n/d' }}
                    </div>
                    <div class="text-xs text-gray-400">
                        @foreach ($summary['memory'] ?? [] as $slot => $mem)
                            {{ $slot }}: {{ $mem['total_kb'] > 0 ? round($mem['free_kb'] / $mem['total_kb'] * 100) : '?' }}% libre<br>
                        @endforeach
                    </div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Temperaturas</div>
                    @forelse ($summary['temperatures'] ?? [] as $t)
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $t['unit'] }}: {{ $t['temp'] }} °C
                            <span class="{{ $t['status'] === 'Normal' ? 'text-green-500' : 'text-red-500' }}">({{ $t['status'] }})</span>
                        </div>
                    @empty
                        <div class="text-gray-400">n/d</div>
                    @endforelse
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Logs</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $summary['logs']['total'] ?? 0 }} eventos</div>
                    <div class="text-xs text-gray-400">
                        Errores: {{ $summary['logs']['errors'] ?? 0 }} ·
                        Warnings: {{ $summary['logs']['warnings'] ?? 0 }} ·
                        Reinicios inesperados: {{ count($summary['logs']['unexpected_reboots'] ?? []) }} ·
                        Logins fallidos: {{ $summary['logs']['auth_failures'] ?? 0 }}
                        @foreach ($summary['logs']['cpu_warnings'] ?? [] as $proc => $cw)
                            <br><span class="text-red-500 font-medium">CPU: {{ $proc }} hasta {{ $cw['max_pct'] }}% (x{{ $cw['count'] }})</span>
                        @endforeach
                    </div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Puertos</div>
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ $summary['ports']['active'] ?? 0 }} activos
                    </div>
                    <div class="text-xs text-gray-400">
                        Con errores rx: {{ $summary['ports']['with_rx_errors'] ?? 0 }} ·
                        A 10 Mbps: {{ count($summary['ports']['at_10mbps'] ?? []) }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Advertencias del parser ===== --}}
    @if (! empty($capture->parser_warnings))
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                Advertencias del parser ({{ count($capture->parser_warnings) }})
            </h3>
            <ul class="space-y-1 text-sm text-yellow-700 dark:text-yellow-400 list-disc ps-4">
                @foreach ($capture->parser_warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== Hallazgos ===== --}}
    @if ($findings->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Hallazgos del análisis ({{ $findings->count() }})
                </h3>
                <div class="flex gap-1">
                    @foreach ($findings->groupBy(fn ($f) => $f->level->value) as $level => $group)
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $group->first()->level->badgeClasses() }}">
                            {{ $group->first()->level->label() }}: {{ $group->count() }}
                        </span>
                    @endforeach
                </div>
            </div>

            @php([$logFindings, $currentFindings] = $findings->partition(fn ($f) => $f->isLogBased()))

            @foreach ([
                ['Estado actual del equipo (tech-support)', $currentFindings, 'Condiciones presentes al momento de la captura.',
                    'border-blue-600 bg-blue-50 text-blue-900 dark:bg-blue-900/30 dark:text-blue-200'],
                ['Histórico del equipo (show log / NVRAM)', $logFindings, 'Eventos ocurridos en el periodo del log: aunque el estado actual sea normal, aquí queda lo que pasó y sobre lo que se puede tomar acción.',
                    'border-teal-600 bg-teal-50 text-teal-900 dark:bg-teal-900/30 dark:text-teal-200'],
            ] as [$sectionTitle, $sectionFindings, $sectionNote, $sectionClasses])
            @continue($sectionFindings->isEmpty())
            <div class="mb-5">
            <div class="border-l-4 rounded-lg p-3 mb-3 {{ $sectionClasses }}">
                <div class="text-lg font-bold">{{ $sectionTitle }} ({{ $sectionFindings->count() }})</div>
                <p class="text-xs opacity-80 mt-0.5">{{ $sectionNote }}</p>
            </div>
            <div class="space-y-3">
                @foreach ($sectionFindings as $finding)
                    <details class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <summary class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full shrink-0 {{ $finding->level->badgeClasses() }}">
                                {{ $finding->level->label() }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $finding->title }}</span>
                            <span class="ms-auto text-xs text-gray-400 font-mono shrink-0">{{ $finding->rule_code }}</span>
                        </summary>
                        <div class="px-4 pb-4 text-sm space-y-2">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $finding->description }}</p>
                            @if ($finding->impact)
                                <p><span class="font-semibold text-gray-900 dark:text-white">Impacto:</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $finding->impact }}</span></p>
                            @endif
                            @if ($finding->recommendation)
                                <p><span class="font-semibold text-gray-900 dark:text-white">Recomendación:</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $finding->recommendation }}</span></p>
                            @endif
                            @if ($finding->evidence)
                                <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto font-mono text-gray-800 dark:text-gray-200">{{ $finding->evidence }}</pre>
                            @endif
                            @if ($finding->file_location)
                                <p class="text-xs text-gray-400">Ubicación: {{ $finding->file_location }}</p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- ===== Métricas ===== --}}
    @if ($metricsSummary->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Métricas normalizadas</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($metricsSummary as $category => $total)
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded-full dark:bg-blue-900 dark:text-blue-300">
                        {{ $category }}: {{ $total }}
                    </span>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-400">
                Estas métricas alimentan los comparativos e histórico por equipo (Fase 5).
            </p>
        </div>
    @endif
</x-admin-layout>
