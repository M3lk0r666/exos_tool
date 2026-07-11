<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Metodología'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Dictamen metodológico del análisis
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Documento entregable al cliente: explica qué se analiza, contra qué se contrasta
                    y de dónde provienen los umbrales de cada hallazgo.
                </p>
            </div>
            <a href="{{ route('admin.methodology.pdf') }}"
                class="sm:ms-auto inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                Descargar PDF
            </a>
        </div>

        <div class="methodology-content max-w-none text-sm text-gray-700 dark:text-gray-300
            [&_h2]:text-base [&_h2]:font-bold [&_h2]:text-blue-700 dark:[&_h2]:text-blue-400 [&_h2]:mt-6 [&_h2]:mb-2
            [&_p]:mb-3 [&_p]:leading-relaxed
            [&_table]:w-full [&_table]:text-xs [&_table]:my-3 [&_table]:border [&_table]:border-gray-200 dark:[&_table]:border-gray-700
            [&_th]:bg-gray-50 dark:[&_th]:bg-gray-700 [&_th]:p-2 [&_th]:text-left [&_th]:border [&_th]:border-gray-200 dark:[&_th]:border-gray-700
            [&_td]:p-2 [&_td]:border [&_td]:border-gray-200 dark:[&_td]:border-gray-700 [&_td]:align-top">
            @include('admin.methodology._content')
        </div>
    </div>
</x-admin-layout>
