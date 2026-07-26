<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Equipos'],
]">
    {{-- Encabezado + métricas --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Inventario de equipos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $totalDevices }} equipo(s) identificados automáticamente a partir de las capturas.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:w-80">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Total equipos</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">{{ number_format($totalDevices) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">En stack</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">{{ number_format($stackCount) }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.devices.index') }}" class="flex flex-wrap gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
            <select name="client" onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-md py-2 ps-3 pe-9 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los clientes</option>
                @foreach ($clients as $id => $name)
                    <option value="{{ $id }}" @selected(request('client') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <div class="relative flex-1 min-w-[16rem]">
                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400"><i class="ri-search-line"></i></span>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full ps-10 p-2 text-sm text-gray-900 border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Buscar por nombre, alias, serie o MAC...">
            </div>
        </form>

        {{-- Tabla --}}
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">Equipo</th>
                        <th class="px-4 py-2.5 font-bold">Cliente / sitio</th>
                        <th class="px-4 py-2.5 font-bold">Modelo</th>
                        <th class="px-4 py-2.5 font-bold">Serie</th>
                        <th class="px-4 py-2.5 font-bold">Estado</th>
                        <th class="px-4 py-2.5 font-bold">Criticidad</th>
                        <th class="px-4 py-2.5 font-bold text-center">Capturas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        @php($worst = $device->worstSeverity())
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2.5">
                                <a href="{{ route('admin.devices.show', $device) }}" class="font-medium text-gray-900 dark:text-white hover:text-blue-700">
                                    {{ $device->displayName() }}
                                </a>
                                @if ($device->is_stack)
                                    <span class="ms-1 bg-purple-100 text-purple-800 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-md dark:bg-purple-900 dark:text-purple-300">Stack</span>
                                @endif
                                <div class="text-xs text-gray-400 font-mono">{{ $device->system_mac }}</div>
                            </td>
                            <td class="px-4 py-2.5">
                                {{ $device->client?->name ?? '—' }}
                                @if ($device->site)<div class="text-xs text-gray-400">{{ $device->site }}</div>@endif
                            </td>
                            <td class="px-4 py-2.5">{{ $device->model ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $device->serial_number ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if ($device->latestCapture === null)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Sin análisis
                                    </span>
                                @elseif ($worst === null)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Óptimo
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $worst->badgeClasses() }}">
                                        {{ $worst->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md
                                    {{ $device->criticality->value === 'high' ? 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                        : ($device->criticality->value === 'medium' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
                                    {{ $device->criticality->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="bg-blue-50 text-blue-700 text-[11px] font-bold px-2 py-0.5 rounded-md dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ $device->captures_count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                Sin equipos. Se crean automáticamente al procesar archivos tech-support.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $devices->links() }}
        </div>
    </div>
</x-admin-layout>
