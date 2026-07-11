<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Clientes', 'href' => route('admin.clients.index')],
    ['name' => $client->name],
]">
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Datos del cliente --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center gap-4 mb-4">
                @if ($client->logo_path)
                    <img src="{{ Storage::url($client->logo_path) }}" alt="{{ $client->name }}"
                        class="w-16 h-16 rounded object-contain bg-white border border-gray-200 p-1">
                @else
                    <span
                        class="inline-flex items-center justify-center w-16 h-16 rounded bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300 text-xl font-bold">
                        {{ strtoupper(substr($client->name, 0, 2)) }}
                    </span>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $client->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Cliente desde {{ $client->created_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <dl class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500 dark:text-gray-400">Contacto</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $client->contact_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500 dark:text-gray-400">Correo</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $client->contact_email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500 dark:text-gray-400">Teléfono</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $client->contact_phone ?? '—' }}</dd>
                </div>
            </dl>

            @if ($client->notes)
                <div class="mt-4 p-3 text-sm text-gray-700 bg-gray-50 rounded-lg dark:bg-gray-700 dark:text-gray-300">
                    {{ $client->notes }}
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2 mt-4">
                <a href="{{ route('admin.devices.index', ['client' => $client->id]) }}"
                    class="py-2 text-center text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Equipos
                </a>
                <a href="{{ route('admin.captures.index', ['client' => $client->id]) }}"
                    class="py-2 text-center text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Capturas
                </a>
                <a href="{{ route('admin.reports.index', ['client' => $client->id]) }}"
                    class="py-2 text-center text-xs font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700">
                    Reportes
                </a>
            </div>

            @can('update', $client)
                <a href="{{ route('admin.clients.edit', $client) }}"
                    class="inline-flex items-center mt-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Editar cliente
                </a>
            @endcan
        </div>

        {{-- Equipos del cliente --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Equipos ({{ $client->devices->count() }})
                </h3>
                <div class="flex gap-1">
                    @foreach ($severityCounts as $level => $count)
                        @php($sev = App\Enums\FindingSeverity::from($level))
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $sev->badgeClasses() }}">
                            {{ $sev->label() }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            </div>

            @if ($client->devices->isEmpty())
                <p class="text-sm text-gray-400">
                    Sin equipos registrados. Los equipos se crean automáticamente al procesar un archivo
                    tech-support del cliente (Fase 2).
                </p>
            @else
                <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Equipo</th>
                                <th scope="col" class="px-4 py-3">Modelo</th>
                                <th scope="col" class="px-4 py-3">Serie</th>
                                <th scope="col" class="px-4 py-3">MAC</th>
                                <th scope="col" class="px-4 py-3">Sitio</th>
                                <th scope="col" class="px-4 py-3">Criticidad</th>
                                <th scope="col" class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($client->devices as $device)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        <a href="{{ route('admin.devices.show', $device) }}" class="hover:text-blue-600">{{ $device->displayName() }}</a>
                                        @if ($device->is_stack)
                                            <span
                                                class="ms-1 bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Stack</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $device->model ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $device->serial_number ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $device->system_mac }}</td>
                                    <td class="px-4 py-3">{{ $device->site ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $device->criticality->label() }}</td>
                                    <td class="px-4 py-3">
                                        @php($worst = $device->worstSeverity())
                                        @if ($device->latestCapture === null)
                                            <span class="text-xs text-gray-400">Sin análisis</span>
                                        @elseif ($worst === null)
                                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">OK</span>
                                        @else
                                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $worst->badgeClasses() }}">
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
