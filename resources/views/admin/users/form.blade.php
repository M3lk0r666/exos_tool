<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Usuarios', 'href' => route('admin.users.index')],
    ['name' => $user ? 'Editar: '.$user->name : 'Nuevo usuario'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($user) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2 mb-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Correo *</label>
                    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                        Contraseña {{ $user ? '(dejar vacío para no cambiar)' : '*' }}
                    </label>
                    <input type="password" name="password" {{ $user ? '' : 'required' }}
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" {{ $user ? '' : 'required' }}
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Rol *</label>
            @error('role')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="space-y-2 mb-6">
                @php($currentRole = old('role', $user?->roles->first()?->name ?? 'engineer'))
                @foreach ($roles as $key => $role)
                    <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer
                        border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <input type="radio" name="role" value="{{ $key }}" @checked($currentRole === $key)
                            class="mt-1 text-blue-600 border-gray-300">
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-white">{{ $role['label'] }}</span>
                            <span class="block text-sm text-gray-600 dark:text-gray-300">{{ $role['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    {{ $user ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
