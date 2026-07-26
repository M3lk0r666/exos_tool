<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Clientes', 'href' => route('admin.clients.index')],
    ['name' => $client->name],
]">
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Datos del cliente --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 p-5 border-b border-gray-100 dark:border-gray-700">
                @if ($client->logo_path)
                    <img src="{{ Storage::url($client->logo_path) }}" alt="{{ $client->name }}"
                        class="w-16 h-16 rounded-md object-contain bg-white border border-gray-200 dark:border-gray-700 p-1">
                @else
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xl font-bold">
                        {{ strtoupper(substr($client->name, 0, 2)) }}
                    </span>
                @endif
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">{{ $client->name }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cliente desde {{ $client->created_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <dl class="text-sm px-5 py-2">
                <div class="flex justify-between py-2 border-b border-gray-50 dark:border-gray-700/50">
                    <dt class="text-gray-500 dark:text-gray-400">Contacto</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $client->contact_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50 dark:border-gray-700/50">
                    <dt class="text-gray-500 dark:text-gray-400">Correo</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $client->contact_email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500 dark:text-gray-400">Teléfono</dt>
                    <dd class="text-gray-900 dark:text-white font-mono">{{ $client->contact_phone ?? '—' }}</dd>
                </div>
            </dl>

            @if ($client->notes)
                <div class="mx-5 mb-4 p-3 text-sm text-gray-700 bg-gray-50 rounded-md dark:bg-gray-700 dark:text-gray-300">
                    {{ $client->notes }}
                </div>
            @endif

            <div class="p-5 border-t border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-2">
                    <a href="{{ route('admin.devices.index', ['client' => $client->id]) }}"
                        class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">Equipos</a>
                    <a href="{{ route('admin.captures.index', ['client' => $client->id]) }}"
                        class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">Capturas</a>
                    <a href="{{ route('admin.reports.index', ['client' => $client->id]) }}"
                        class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">Reportes</a>
                </div>

                @can('update', $client)
                    <a href="{{ route('admin.clients.edit', $client) }}"
                        class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <i class="ri-edit-line"></i> Editar cliente
                    </a>
                @endcan
            </div>
        </div>

        {{-- Equipos del cliente --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    Equipos ({{ $client->devices->count() }})
                </h3>
                <div class="flex gap-1">
                    @foreach ($severityCounts as $level => $count)
                        @php($sev = App\Enums\FindingSeverity::from($level))
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $sev->badgeClasses() }}">
                            {{ $sev->label() }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            </div>

            @if ($client->devices->isEmpty())
                <p class="p-5 text-sm text-gray-400">
                    Sin equipos registrados. Los equipos se crean automáticamente al procesar un archivo
                    tech-support del cliente.
                </p>
            @else
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 font-bold">Equipo</th>
                                <th class="px-4 py-2.5 font-bold">Modelo</th>
                                <th class="px-4 py-2.5 font-bold">Serie</th>
                                <th class="px-4 py-2.5 font-bold">MAC</th>
                                <th class="px-4 py-2.5 font-bold">Sitio</th>
                                <th class="px-4 py-2.5 font-bold">Criticidad</th>
                                <th class="px-4 py-2.5 font-bold text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($client->devices as $device)
                                <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                        <a href="{{ route('admin.devices.show', $device) }}" class="hover:text-blue-700">{{ $device->displayName() }}</a>
                                        @if ($device->is_stack)
                                            <span class="ms-1 bg-purple-100 text-purple-800 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-md dark:bg-purple-900 dark:text-purple-300">Stack</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">{{ $device->model ?? '—' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $device->serial_number ?? '—' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $device->system_mac }}</td>
                                    <td class="px-4 py-2.5">{{ $device->site ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $device->criticality->label() }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        @php($worst = $device->worstSeverity())
                                        @if ($device->latestCapture === null)
                                            <span class="text-xs text-gray-400">Sin análisis</span>
                                        @elseif ($worst === null)
                                            <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">OK</span>
                                        @else
                                            <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $worst->badgeClasses() }}">
                                                {{ $worst->label() }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
