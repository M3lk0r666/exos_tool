<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Auditoría'],
]">
    @php($actionColors = [
        'created' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'updated' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'deleted' => 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'issued' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'uploaded' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    ])

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
            Auditoría
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Registro de todas las acciones realizadas en el sistema.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-wrap gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
            <select name="user" onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-md py-2 ps-3 pe-9 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los usuarios</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected(request('user') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="action" onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-md py-2 ps-3 pe-9 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todas las acciones</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </form>

        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">Fecha</th>
                        <th class="px-4 py-2.5 font-bold">Usuario</th>
                        <th class="px-4 py-2.5 font-bold">Acción</th>
                        <th class="px-4 py-2.5 font-bold">Recurso</th>
                        <th class="px-4 py-2.5 font-bold">Detalle</th>
                        <th class="px-4 py-2.5 font-bold">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2.5 whitespace-nowrap font-mono text-xs">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2.5">{{ $log->user?->name ?? 'sistema' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs">
                                {{ $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—' }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-xs text-gray-400 break-all">
                                    {{ Str::limit(json_encode($log->changes, JSON_UNESCAPED_UNICODE), 120) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Sin registros de auditoría.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
