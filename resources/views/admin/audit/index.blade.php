<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Auditoría'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">

        <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-wrap gap-2 mb-4">
            <select name="user" onchange="this.form.submit()"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los usuarios</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected(request('user') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="action" onchange="this.form.submit()"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todas las acciones</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </form>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Acción</th>
                        <th class="px-4 py-3">Recurso</th>
                        <th class="px-4 py-3">Detalle</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $log->user?->name ?? 'sistema' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-gray-400 break-all">
                                    {{ Str::limit(json_encode($log->changes, JSON_UNESCAPED_UNICODE), 120) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Sin registros de auditoría.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
