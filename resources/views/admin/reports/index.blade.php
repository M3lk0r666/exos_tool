<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reportes'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Reportes
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Los reportes se generan desde la vista de cada captura completada.
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
            <select name="client" onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-md py-2 ps-3 pe-9 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los clientes</option>
                @foreach ($clients as $id => $name)
                    <option value="{{ $id }}" @selected(request('client') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-md py-2 ps-3 pe-9 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos los estados</option>
                <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                <option value="issued" @selected(request('status') === 'issued')>Emitido</option>
            </select>
        </form>

        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">Cliente</th>
                        <th class="px-4 py-2.5 font-bold">Equipo</th>
                        <th class="px-4 py-2.5 font-bold">Versión</th>
                        <th class="px-4 py-2.5 font-bold">Estado</th>
                        <th class="px-4 py-2.5 font-bold">Emitido</th>
                        <th class="px-4 py-2.5 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2.5">{{ $report->capture?->client?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                {{ $report->capture?->device?->displayName() ?? 'Captura #'.$report->capture_id }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs">v{{ $report->version }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md
                                    {{ $report->status === App\Enums\ReportStatus::Draft
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                        : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                    {{ $report->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($report->issued_at)
                                    {{ $report->issued_at->format('d/m/Y H:i') }}
                                    <div class="text-xs text-gray-400">{{ $report->issuer?->name }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.reports.show', $report) }}"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                                        <i class="ri-external-link-line"></i> Abrir
                                    </a>
                                    @if ($report->pdf_path)
                                        <a href="{{ route('admin.reports.pdf', $report) }}"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                                            <i class="ri-file-pdf-2-line"></i> PDF
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                Sin reportes. Abre una captura completada y usa "Generar reporte".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $reports->links() }}
        </div>
    </div>
</x-admin-layout>
