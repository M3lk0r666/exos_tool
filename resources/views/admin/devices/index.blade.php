<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Equipos'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">

        <form method="GET" action="{{ route('admin.devices.index') }}" class="flex flex-wrap gap-2 mb-4">
            <select name="client" onchange="this.form.submit()"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los clientes</option>
                @foreach ($clients as $id => $name)
                    <option value="{{ $id }}" @selected(request('client') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}"
                class="w-full sm:w-80 p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                placeholder="Buscar por nombre, alias, serie o MAC...">
        </form>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Equipo</th>
                        <th scope="col" class="px-4 py-3">Cliente</th>
                        <th scope="col" class="px-4 py-3">Modelo</th>
                        <th scope="col" class="px-4 py-3">Serie</th>
                        <th scope="col" class="px-4 py-3">Sitio</th>
                        <th scope="col" class="px-4 py-3">Criticidad</th>
                        <th scope="col" class="px-4 py-3 text-center">Capturas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('admin.devices.show', $device) }}" class="hover:text-blue-600">
                                    {{ $device->displayName() }}
                                </a>
                                @if ($device->is_stack)
                                    <span class="ms-1 bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Stack</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $device->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $device->model ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $device->serial_number ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $device->site ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $device->criticality->label() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                    {{ $device->captures_count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                Sin equipos. Se crean automáticamente al procesar archivos tech-support.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $devices->links() }}
        </div>
    </div>
</x-admin-layout>
