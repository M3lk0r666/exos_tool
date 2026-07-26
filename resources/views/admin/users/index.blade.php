<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Usuarios'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Usuarios
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Gestión de accesos y roles del sistema.
            </p>
        </div>
        <a href="{{ route('admin.users.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800 shadow-sm">
            <i class="ri-user-add-line"></i> Nuevo usuario
        </a>
    </div>

    @if ($errors->any())
        <div class="p-3 mb-4 text-sm text-red-800 bg-red-50 rounded-md border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">Usuario</th>
                        <th class="px-4 py-2.5 font-bold">Correo</th>
                        <th class="px-4 py-2.5 font-bold">Rol</th>
                        <th class="px-4 py-2.5 font-bold">Alta</th>
                        <th class="px-4 py-2.5 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                        @if ($user->id === auth()->id())
                                            <span class="text-xs text-gray-400">(tú)</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $user->email }}</td>
                            <td class="px-4 py-2.5">
                                @forelse ($user->roles as $role)
                                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md
                                        {{ $role->name === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'
                                            : ($role->name === 'engineer' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}"
                                        title="{{ $roles[$role->name]['description'] ?? '' }}">
                                        {{ $roles[$role->name]['label'] ?? $role->name }}
                                    </span>
                                @empty
                                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300" title="Usuario sin permisos: asígnale un rol">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-2.5">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <i class="ri-edit-line"></i> Editar
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('¿Eliminar al usuario «{{ $user->name }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $users->links() }}</div>
    </div>

    {{-- Explicación de roles --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Roles y permisos</h3>
        </div>
        <div class="p-5 grid gap-3 md:grid-cols-3">
            @foreach ($roles as $key => $role)
                <div class="border rounded-md p-4
                    {{ $key === 'admin' ? 'border-purple-200 dark:border-purple-800'
                        : ($key === 'engineer' ? 'border-blue-200 dark:border-blue-800'
                        : 'border-gray-200 dark:border-gray-700') }}">
                    <span class="inline-block text-[11px] font-bold uppercase px-2 py-0.5 rounded-md mb-2
                        {{ $key === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'
                            : ($key === 'engineer' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
                        {{ $role['label'] }}
                    </span>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $role['description'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="px-5 pb-4 text-xs text-gray-400">
            Los usuarios que se registran por su cuenta entran automáticamente como <b>Ingeniero</b>;
            aquí puedes cambiarles el rol.
        </p>
    </div>
</x-admin-layout>
