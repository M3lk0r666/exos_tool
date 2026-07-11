@php($client = $client ?? null)

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Nombre <span class="text-red-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $client?->name) }}" required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('name') border-red-500 @enderror">
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Nombre de contacto
        </label>
        <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $client?->contact_name) }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('contact_name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Correo de contacto
        </label>
        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $client?->contact_email) }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('contact_email')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Teléfono de contacto
        </label>
        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $client?->contact_phone) }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('contact_phone')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="logo" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Logo (para reportes)
        </label>
        @if ($client?->logo_path)
            <img src="{{ Storage::url($client->logo_path) }}" alt="Logo actual"
                class="h-12 mb-2 object-contain bg-white rounded border border-gray-200 p-1">
        @endif
        <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, SVG o WEBP (máx. 2 MB).</p>
        @error('logo')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notas</label>
        <textarea id="notes" name="notes" rows="4"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $client?->notes) }}</textarea>
        @error('notes')
            <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
        {{ $client ? 'Guardar cambios' : 'Crear cliente' }}
    </button>
    <a href="{{ route('admin.clients.index') }}"
        class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
        Cancelar
    </a>
</div>
