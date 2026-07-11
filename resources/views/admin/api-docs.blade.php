<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'API'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            Documentación OpenAPI de la API REST v1. Genera tu token en
            <a href="{{ route('api-tokens.index') }}" class="text-blue-600 hover:underline">Perfil → API Tokens</a>
            y úsalo como <code class="font-mono">Authorization: Bearer &lt;token&gt;</code>.
            Especificación: <a href="{{ asset('openapi.yaml') }}" class="text-blue-600 hover:underline font-mono">openapi.yaml</a>.
        </p>
        <div id="swagger-ui" class="bg-white rounded"></div>
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
                        '<p class="p-4 text-sm">Sin conexión a internet: consulta la especificación <a class="text-blue-600 underline" href="{{ asset('openapi.yaml') }}">openapi.yaml</a> directamente.</p>';
                }
            });
        </script>
    @endpush
</x-admin-layout>
