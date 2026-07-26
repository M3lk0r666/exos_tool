<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Usuarios', 'href' => route('admin.users.index')],
    ['name' => $user ? 'Editar: '.$user->name : 'Nuevo usuario'],
]">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4" style="font-family:'Hanken Grotesk',sans-serif;">
        {{ $user ? 'Editar usuario' : 'Nuevo usuario' }}
    </h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 max-w-2xl">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($user) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2 mb-5">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
                        class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Correo *</label>
                    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
                        class="w-full text-sm font-mono rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                        Contraseña {{ $user ? '(dejar vacío para no cambiar)' : '*' }}
                    </label>
                    <input type="password" name="password" {{ $user ? '' : 'required' }}
                        class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" {{ $user ? '' : 'required' }}
                        class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Rol *</label>
            @error('role')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="space-y-2 mb-6">
                @php($currentRole = old('role', $user?->roles->first()?->name ?? 'engineer'))
                @foreach ($roles as $key => $role)
                    <label class="flex items-start gap-3 p-3 border rounded-md cursor-pointer transition-colors
                        {{ $currentRole === $key ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <input type="radio" name="role" value="{{ $key }}" @checked($currentRole === $key)
                            class="mt-1 text-blue-600 border-gray-300 focus:ring-blue-600">
                        <span>
                            <span class="inline-block text-[11px] font-bold uppercase px-2 py-0.5 rounded-md mb-1
                                {{ $key === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'
                                    : ($key === 'engineer' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ $role['label'] }}
                            </span>
                            <span class="block text-sm text-gray-600 dark:text-gray-300">{{ $role['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center gap-2 text-white bg-blue-700 hover:bg-blue-800 font-semibold rounded-md text-sm px-5 py-2.5">
                    <i class="ri-save-line"></i> {{ $user ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
