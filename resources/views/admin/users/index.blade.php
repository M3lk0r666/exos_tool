<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Usuarios'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Usuarios del sistema</h3>
            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                + Nuevo usuario
            </a>
        </div>

        @if ($errors->any())
            <div class="p-3 mb-3 text-sm text-red-800 bg-red-50 rounded-lg dark:bg-gray-700 dark:text-red-400">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Alta</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="text-xs text-gray-400">(tú)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @forelse ($user->roles as $role)
                                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full
                                        {{ $role->name === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
                                            : ($role->name === 'engineer' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}"
                                        title="{{ $roles[$role->name]['description'] ?? '' }}">
                                        {{ $roles[$role->name]['label'] ?? $role->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-red-500" title="Usuario sin permisos: asígnale un rol">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                        class="font-medium text-yellow-500 hover:underline">Editar</a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('¿Eliminar al usuario «{{ $user->name }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    {{-- Explicación de roles --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Roles y permisos</h3>
        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($roles as $key => $role)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="font-semibold text-gray-900 dark:text-white mb-1">{{ $role['label'] }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $role['description'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-gray-400">
            Los usuarios que se registran por su cuenta entran automáticamente como <b>Ingeniero</b>;
            aquí puedes cambiarles el rol.
        </p>
    </div>
</x-admin-layout>
