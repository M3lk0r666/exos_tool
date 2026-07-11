<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Configuración'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                @foreach ($settings as $setting)
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $setting->description ?? $setting->key }}
                            <span class="text-xs text-gray-400 font-mono">({{ $setting->key }} · {{ $setting->type }})</span>
                        </label>
                        @if ($setting->type === 'bool')
                            <select name="settings[{{ $setting->key }}]"
                                class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="true" @selected(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>Sí</option>
                                <option value="false" @selected(! filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>No</option>
                            </select>
                        @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white {{ $setting->key === 'branding.company_logo' ? 'font-mono' : '' }}">
                        @endif
                    </div>
                @endforeach

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                        Logo de la empresa (para portada de reportes)
                    </label>
                    @php($logoPath = App\Models\Setting::get('branding.company_logo'))
                    @if ($logoPath)
                        <img src="{{ Storage::url($logoPath) }}" alt="Logo actual" class="h-12 mb-2 bg-white rounded border p-1">
                    @endif
                    <input type="file" name="company_logo" accept=".png,.jpg,.jpeg,.svg,.webp"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600">
                </div>
            </div>

            <button type="submit"
                class="mt-6 text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                Guardar configuración
            </button>
        </form>
    </div>
</x-admin-layout>
