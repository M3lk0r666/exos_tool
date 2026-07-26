<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'API'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xl shrink-0">
                <i class="ri-code-s-slash-line"></i>
            </span>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                    API REST v1
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sube archivos y consulta análisis, hallazgos y métricas mediante la API.
                </p>
            </div>
        </div>
        <a href="{{ asset('openapi.yaml') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white rounded-md border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 shrink-0">
            <i class="ri-download-line"></i> openapi.yaml
        </a>
    </div>

    {{-- Cómo autenticar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
            <i class="ri-key-2-line text-blue-700 dark:text-blue-300 mt-0.5"></i>
            <p>
                Genera tu token en
                <a href="{{ route('api-tokens.index') }}" class="font-medium text-blue-700 hover:underline">Perfil → API Tokens</a>
                y envíalo en el encabezado:
                <code class="ms-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-900 text-blue-700 dark:text-blue-300 font-mono text-xs">Authorization: Bearer &lt;token&gt;</code>
            </p>
        </div>
    </div>

    {{-- Visor Swagger --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div id="swagger-ui" class="bg-white"></div>
    </div>

    @push('js')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                if (window.SwaggerUIBundle) {
                    SwaggerUIBundle({ url: '{{ asset('openapi.yaml') }}', dom_id: '#swagger-ui' });
                } else {
                    document.getElementById('swagger-ui').innerHTML =
                        '<p class="p-4 text-sm text-gray-500">Sin conexión a internet: consulta la especificación <a class="text-blue-700 underline" href="{{ asset('openapi.yaml') }}">openapi.yaml</a> directamente.</p>';
                }
            });
        </script>
    @endpush
</x-admin-layout>
