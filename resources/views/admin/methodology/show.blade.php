<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Metodología'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xl shrink-0">
                <i class="ri-shield-check-line"></i>
            </span>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                    Dictamen metodológico
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl">
                    Documento entregable al cliente: explica qué se analiza, contra qué se contrasta
                    y de dónde provienen los umbrales de cada hallazgo.
                </p>
            </div>
        </div>
        <a href="{{ route('admin.methodology.pdf') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800 shadow-sm shrink-0">
            <i class="ri-file-pdf-2-line"></i> Descargar PDF
        </a>
    </div>

    {{-- Documento --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 lg:p-8 max-w-4xl">
        <div class="methodology-content max-w-none text-sm text-gray-700 dark:text-gray-300
            [&_h2]:text-base [&_h2]:font-bold [&_h2]:text-blue-700 dark:[&_h2]:text-blue-400 [&_h2]:mt-7 [&_h2]:mb-3 [&_h2]:pb-1 [&_h2]:border-b [&_h2]:border-blue-100 dark:[&_h2]:border-blue-900/40
            [&_p]:mb-3 [&_p]:leading-relaxed
            [&_table]:w-full [&_table]:text-xs [&_table]:my-3 [&_table]:border [&_table]:border-gray-200 dark:[&_table]:border-gray-700 [&_table]:rounded-md
            [&_th]:bg-gray-50 dark:[&_th]:bg-gray-700/50 [&_th]:p-2 [&_th]:text-left [&_th]:text-[11px] [&_th]:uppercase [&_th]:tracking-wide [&_th]:text-gray-500 [&_th]:font-bold [&_th]:border [&_th]:border-gray-100 dark:[&_th]:border-gray-700
            [&_td]:p-2 [&_td]:border [&_td]:border-gray-50 dark:[&_td]:border-gray-700/50 [&_td]:align-top
            [&_code]:font-mono [&_code]:text-xs [&_code]:text-blue-700 dark:[&_code]:text-blue-300">
            @include('admin.methodology._content')
        </div>
    </div>
</x-admin-layout>
