<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Equipos', 'href' => route('admin.devices.index')],
    ['name' => $device->displayName(), 'href' => route('admin.devices.show', $device)],
    ['name' => 'Editar'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-2xl">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Los datos de identificación (MAC, serie, modelo, sysname) provienen del propio equipo y se
            actualizan al procesar capturas. Aquí se editan los datos administrativos.
        </p>

        <form method="POST" action="{{ route('admin.devices.update', $device) }}">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="alias" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alias</label>
                    <input type="text" id="alias" name="alias" value="{{ old('alias', $device->alias) }}"
                        placeholder="{{ $device->sysname }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('alias')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="site" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sitio</label>
                    <input type="text" id="site" name="site" value="{{ old('site', $device->site) }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('site')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="criticality" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Criticidad</label>
                    <select id="criticality" name="criticality"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach (App\Enums\DeviceCriticality::cases() as $crit)
                            <option value="{{ $crit->value }}" @selected(old('criticality', $device->criticality->value) === $crit->value)>
                                {{ $crit->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notas</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $device->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center gap-3 mt-6">
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.devices.show', $device) }}"
                    class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
