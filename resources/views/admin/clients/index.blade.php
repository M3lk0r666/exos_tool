<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Clientes'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Directorio de clientes
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Infraestructura de red gestionada en {{ $clients->total() }} organización(es).
            </p>
        </div>
        @can('create', App\Models\Client::class)
            <a href="{{ route('admin.clients.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800 shadow-sm">
                <i class="ri-add-line"></i> Nuevo cliente
            </a>
        @endcan
    </div>

    {{-- Buscador --}}
    <form method="GET" action="{{ route('admin.clients.index') }}" class="w-full sm:w-96 mb-5">
        <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400">
                <i class="ri-search-line"></i>
            </span>
            <input type="text" name="search" value="{{ request('search') }}"
                class="block w-full ps-10 p-2 text-sm text-gray-900 border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                placeholder="Buscar cliente, contacto o correo...">
        </div>
    </form>

    {{-- Grid de clientes --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($clients as $client)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col hover:border-blue-400 transition-colors">
                {{-- Cabecera de la card --}}
                <div class="flex items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
                    @if ($client->logo_path)
                        <img src="{{ Storage::url($client->logo_path) }}" alt="{{ $client->name }}"
                            class="w-11 h-11 rounded-md object-contain bg-white border border-gray-100 dark:border-gray-700 p-1">
                    @else
                        <span class="inline-flex items-center justify-center w-11 h-11 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-base font-bold">
                            {{ strtoupper(substr($client->name, 0, 2)) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.clients.show', $client) }}"
                            class="block font-semibold text-gray-900 dark:text-white truncate hover:text-blue-700">
                            {{ $client->name }}
                        </a>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $client->contact_name ?? 'Sin contacto' }}
                            @if ($client->contact_email) · {{ $client->contact_email }} @endif
                        </div>
                    </div>
                    {{-- Indicador de salud --}}
                    @if ($client->critical_findings_count > 0)
                        <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300"
                            title="Hallazgos críticos/altos abiertos">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span> {{ $client->critical_findings_count }}
                        </span>
                    @elseif ($client->open_findings_count > 0)
                        <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                            title="Hallazgos abiertos">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> {{ $client->open_findings_count }}
                        </span>
                    @else
                        <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> OK
                        </span>
                    @endif
                </div>

                {{-- Contadores --}}
                <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-700 text-center">
                    @foreach ([
                        ['Equipos', $client->devices_count],
                        ['Capturas', $client->captures_count],
                        ['Reportes', $client->reports_count],
                    ] as [$cLabel, $cValue])
                        <div class="py-3">
                            <div class="text-xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">{{ $cValue }}</div>
                            <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $cLabel }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Accesos por cliente --}}
                <div class="p-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                    <div class="grid grid-cols-3 gap-2">
                        <a href="{{ route('admin.devices.index', ['client' => $client->id]) }}"
                            class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                            Equipos
                        </a>
                        <a href="{{ route('admin.captures.index', ['client' => $client->id]) }}"
                            class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                            Capturas
                        </a>
                        <a href="{{ route('admin.reports.index', ['client' => $client->id]) }}"
                            class="py-1.5 text-center text-xs font-semibold text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                            Reportes
                        </a>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-3">
                        <a href="{{ route('admin.clients.show', $client) }}"
                            class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                            <i class="ri-eye-line"></i> Ver
                        </a>
                        @can('update', $client)
                            <a href="{{ route('admin.clients.edit', $client) }}"
                                class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <i class="ri-edit-line"></i> Editar
                            </a>
                        @endcan
                        @can('delete', $client)
                            <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
                                onsubmit="return confirm('¿Eliminar el cliente «{{ $client->name }}»? Sus equipos y capturas se conservan en el histórico.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300">
                                    <i class="ri-delete-bin-line"></i> Eliminar
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-10 text-center text-gray-400">
                @if (request('search'))
                    Sin resultados para «{{ request('search') }}».
                @else
                    Aún no hay clientes registrados. Crea el primero con "Nuevo cliente".
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $clients->links() }}
    </div>
</x-admin-layout>
