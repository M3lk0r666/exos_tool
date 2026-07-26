<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Configuración'],
]">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
            Configuración
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Parámetros del sistema y marca de la empresa para los reportes.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-4">
        @csrf
        @method('PUT')

        {{-- Parámetros --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Parámetros del sistema</h3>
            </div>
            <div class="p-5 space-y-4">
                @foreach ($settings as $setting)
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $setting->description ?? $setting->key }}
                            <span class="text-[11px] text-gray-400 font-mono">({{ $setting->key }} · {{ $setting->type }})</span>
                        </label>
                        @if ($setting->type === 'bool')
                            <select name="settings[{{ $setting->key }}]"
                                class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="true" @selected(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>Sí</option>
                                <option value="false" @selected(! filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>No</option>
                            </select>
                        @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white {{ in_array($setting->type, ['int', 'json']) || $setting->key === 'branding.company_logo' ? 'font-mono' : '' }}">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Marca --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Marca de la empresa</h3>
            </div>
            <div class="p-5">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    Logo (navbar, portada de reportes y dictamen)
                </label>
                @php($logoPath = App\Models\Setting::get('branding.company_logo'))
                @if ($logoPath)
                    <div class="flex items-center gap-3 mb-3">
                        <img src="{{ Storage::url($logoPath) }}" alt="Logo actual" class="h-12 bg-white rounded-md border border-gray-200 dark:border-gray-700 p-1">
                        <span class="text-xs text-gray-400">Logo actual</span>
                    </div>
                @endif
                <input type="file" name="company_logo" accept=".png,.jpg,.jpeg,.svg,.webp"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-gray-50 file:me-3 file:py-2 file:px-3 file:border-0 file:bg-gray-100 file:text-gray-700 file:font-medium dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:file:bg-gray-600 dark:file:text-gray-300">
                <p class="mt-1 text-xs text-gray-400">PNG, JPG, SVG o WEBP.</p>
            </div>
        </div>

        <button type="submit"
            class="inline-flex items-center gap-2 text-white bg-blue-700 hover:bg-blue-800 font-semibold rounded-md text-sm px-5 py-2.5">
            <i class="ri-save-line"></i> Guardar configuración
        </button>
    </form>
</x-admin-layout>
