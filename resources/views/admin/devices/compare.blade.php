<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Equipos', 'href' => route('admin.devices.index')],
    ['name' => $device->displayName(), 'href' => route('admin.devices.show', $device)],
    ['name' => 'Comparativo'],
]">
    @php($old = $comparison['old'])
    @php($new = $comparison['new'])
    @php($changeClasses = [
        'better' => 'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-900/30',
        'worse' => 'text-red-700 bg-red-50 dark:text-red-300 dark:bg-red-900/30',
        'same' => 'text-gray-500',
        'info' => 'text-blue-700 bg-blue-50 dark:text-blue-300 dark:bg-blue-900/30',
    ])

    {{-- Encabezado --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
            Comparativo — {{ $device->displayName() }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <a class="text-blue-600 hover:underline" href="{{ route('admin.captures.show', $old) }}">Captura #{{ $old->id }}</a>
            ({{ $old->captured_at?->format('d/m/Y H:i') }})
            →
            <a class="text-blue-600 hover:underline" href="{{ route('admin.captures.show', $new) }}">Captura #{{ $new->id }}</a>
            ({{ $new->captured_at?->format('d/m/Y H:i') }})
        </p>

        @if ($comparison['reboot_detected'])
            <div class="mt-3 flex items-center p-3 text-sm text-yellow-800 bg-yellow-50 rounded-lg dark:bg-gray-700 dark:text-yellow-300">
                <svg class="shrink-0 w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/>
                </svg>
                <b>Reinicio detectado entre capturas:</b>&nbsp;el uptime de la captura nueva es menor que el de la
                anterior. Los contadores de puertos se reiniciaron, por lo que no se calculan deltas de contadores.
            </div>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mb-4">
        {{-- Generales --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Generales</h3>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Métrica</th>
                        <th class="px-3 py-2">#{{ $old->id }}</th>
                        <th class="px-3 py-2">#{{ $new->id }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['general'] as $label => $row)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $label }}</td>
                            <td class="px-3 py-2">{{ $row['old'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded {{ $changeClasses[$row['change']] }}">{{ $row['new'] ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ambiente --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Ambiente y recursos</h3>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Métrica</th>
                        <th class="px-3 py-2">#{{ $old->id }}</th>
                        <th class="px-3 py-2">#{{ $new->id }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comparison['environment'] as $label => $row)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $label }}</td>
                            <td class="px-3 py-2">{{ $row['old'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded {{ $changeClasses[$row['change']] }}">{{ $row['new'] ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-3 text-gray-400">Sin datos de ambiente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Puertos --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Contadores de puertos</h3>
        <p class="text-xs text-gray-400 mb-3">
            Contadores acumulados desde el último arranque. Solo se listan puertos con actividad.
            @if ($comparison['ports']['reset'])
                <b>Deltas suspendidos por reinicio detectado.</b>
            @endif
        </p>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Puerto</th>
                        <th class="px-3 py-2">Métrica</th>
                        <th class="px-3 py-2 text-right">Captura #{{ $old->id }}</th>
                        <th class="px-3 py-2 text-right">Captura #{{ $new->id }}</th>
                        <th class="px-3 py-2 text-right">Δ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comparison['ports']['rows'] as $row)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-3 py-2 font-mono font-medium text-gray-900 dark:text-white">{{ $row['port'] }}</td>
                            <td class="px-3 py-2">{{ $row['metric'] }}</td>
                            <td class="px-3 py-2 text-right font-mono">{{ $row['old'] !== null ? number_format($row['old']) : '—' }}</td>
                            <td class="px-3 py-2 text-right font-mono">{{ $row['new'] !== null ? number_format($row['new']) : '—' }}</td>
                            <td class="px-3 py-2 text-right font-mono">
                                @if ($row['delta'] === null)
                                    <span class="text-yellow-600 dark:text-yellow-400" title="Reinicio detectado">n/a</span>
                                @elseif ($row['delta'] > 0)
                                    <span class="px-2 py-0.5 rounded {{ $changeClasses['worse'] }}">+{{ number_format($row['delta']) }}</span>
                                @elseif ($row['delta'] < 0)
                                    <span class="text-gray-400" title="Contador menor (posible reinicio parcial)">{{ number_format($row['delta']) }}</span>
                                @else
                                    <span class="{{ $changeClasses['same'] }}">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400">Sin contadores de puertos con actividad en ninguna de las dos capturas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hallazgos --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ([
            'new' => ['Nuevos hallazgos', 'text-red-700 dark:text-red-400'],
            'resolved' => ['Ya no presentes', 'text-green-700 dark:text-green-400'],
            'persisting' => ['Persistentes', 'text-yellow-700 dark:text-yellow-400'],
        ] as $key => [$title, $color])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <h3 class="text-base font-semibold {{ $color }} mb-3">
                    {{ $title }} ({{ $comparison['findings'][$key]->count() }})
                </h3>
                <ul class="space-y-2">
                    @forelse ($comparison['findings'][$key] as $finding)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0 {{ $finding->level->badgeClasses() }}">
                                {{ $finding->level->label() }}
                            </span>
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $finding->title }}
                                <span class="text-xs text-gray-400 font-mono">{{ $finding->rule_code }}</span>
                                @if ($key === 'persisting' && $finding->isLogBased())
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-600 text-white"
                                        title="Este evento del log aparece en ambas capturas: no es un incidente aislado, es un problema crónico que requiere atención">
                                        Crónico
                                    </span>
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">Ninguno.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>
</x-admin-layout>
