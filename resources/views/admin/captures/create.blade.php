<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Capturas', 'href' => route('admin.captures.index')],
    ['name' => 'Subir archivos'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-3xl">

        @if ($errors->any())
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-700 dark:text-red-400" role="alert">
                <ul class="list-disc ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.captures.store') }}" enctype="multipart/form-data" id="upload-form">
            @csrf

            <div class="mb-5">
                <label for="client_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    Cliente <span class="text-red-500">*</span>
                </label>
                <select id="client_id" name="client_id" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Selecciona un cliente…</option>
                    @foreach ($clients as $id => $name)
                        <option value="{{ $id }}" @selected(old('client_id', $selectedClient) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Archivos tech-support (.txt / .log) <span class="text-red-500">*</span>
            </label>
            <div id="dropzone"
                class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" viewBox="0 0 20 16">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                </svg>
                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-semibold">Haz clic para seleccionar</span> o arrastra y suelta aquí
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Salida de <code class="font-mono">show tech-support all</code> · varios archivos a la vez
                </p>
                <input id="file-input" name="files[]" type="file" class="hidden" multiple accept=".txt,.log">
            </div>

            <ul id="file-list" class="mt-3 space-y-1 text-sm text-gray-700 dark:text-gray-300"></ul>

            <div class="flex items-center gap-3 mt-6">
                <button type="submit" id="submit-btn" disabled
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Subir y procesar
                </button>
                <a href="{{ route('admin.captures.index') }}"
                    class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            (function () {
                const dropzone = document.getElementById('dropzone');
                const input = document.getElementById('file-input');
                const list = document.getElementById('file-list');
                const submit = document.getElementById('submit-btn');

                function render() {
                    list.innerHTML = '';
                    Array.from(input.files).forEach(f => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center gap-2';
                        li.innerHTML = '<svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 16 20">' +
                            '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 18a.97.97 0 0 0 .933 1h12.134A.97.97 0 0 0 15 18V5.828a2 2 0 0 0-.586-1.414l-2.828-2.828A2 2 0 0 0 10.172 1H1.933A.97.97 0 0 0 1 2v16Z"/></svg>' +
                            '<span>' + f.name + '</span><span class="text-xs text-gray-400">(' + Math.round(f.size / 1024) + ' KB)</span>';
                        list.appendChild(li);
                    });
                    submit.disabled = input.files.length === 0;
                }

                dropzone.addEventListener('click', () => input.click());
                input.addEventListener('change', render);

                ['dragover', 'dragenter'].forEach(ev => dropzone.addEventListener(ev, e => {
                    e.preventDefault();
                    dropzone.classList.add('border-blue-500');
                }));
                ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
                    e.preventDefault();
                    dropzone.classList.remove('border-blue-500');
                }));
                dropzone.addEventListener('drop', e => {
                    input.files = e.dataTransfer.files;
                    render();
                });
            })();
        </script>
    @endpush
</x-admin-layout>
