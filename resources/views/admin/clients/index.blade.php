<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Clientes'],
]">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
        <form method="GET" action="{{ route('admin.clients.index') }}" class="w-full sm:w-80">
            <input type="text" name="search" value="{{ request('search') }}"
                class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                placeholder="Buscar cliente, contacto o correo...">
        </form>

        @can('create', App\Models\Client::class)
            <a href="{{ route('admin.clients.create') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 18 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                </svg>
                Nuevo cliente
            </a>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($clients as $client)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 flex flex-col">
                {{-- Identidad --}}
                <div class="flex items-center gap-3 mb-3">
                    @if ($client->logo_path)
                        <img src="{{ Storage::url($client->logo_path) }}" alt="{{ $client->name }}"
                            class="w-12 h-12 rounded-lg object-contain bg-white border border-gray-100 dark:border-gray-700 p-1">
                    @else
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 text-lg font-bold">
                            {{ strtoupper(substr($client->name, 0, 2)) }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <a href="{{ route('admin.clients.show', $client) }}"
                            class="block font-semibold text-gray-900 dark:text-white truncate hover:text-blue-600">
                            {{ $client->name }}
                        </a>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $client->contact_name ?? 'Sin contacto' }}
                            @if ($client->contact_email) · {{ $client->contact_email }} @endif
                        </div>
                    </div>
                    @if ($client->open_findings_count > 0)
                        <span class="ms-auto shrink-0 bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300"
                            title="Hallazgos abiertos">
                            {{ $client->open_findings_count }}
                        </span>
                    @endif
                </div>

                {{-- Contadores --}}
                <div class="grid grid-cols-3 gap-2 text-center text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg py-2">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $client->devices_count }}</div>
                        Equipos
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg py-2">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $client->captures_count }}</div>
                        Capturas
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg py-2">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $client->reports_count }}</div>
                        Reportes
                    </div>
                </div>

                {{-- Accesos por cliente --}}
                <div class="mt-auto grid grid-cols-3 gap-2">
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

                <div class="flex items-center justify-end gap-3 mt-3 text-xs">
                    <a href="{{ route('admin.clients.show', $client) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Ver detalle</a>
                    @can('update', $client)
                        <a href="{{ route('admin.clients.edit', $client) }}" class="font-medium text-yellow-500 hover:underline">Editar</a>
                    @endcan
                    @can('delete', $client)
                        <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
                            onsubmit="return confirm('¿Eliminar el cliente «{{ $client->name }}»? Sus equipos y capturas se conservan en el histórico.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Eliminar</button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-10 text-center text-gray-400">
                @if (request('search'))
                    Sin resultados para «{{ request('search') }}».
                @else
                    Aún no hay clientes registrados. Crea el primero con "Nuevo cliente".
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $clients->links() }}
    </div>
</x-admin-layout>
