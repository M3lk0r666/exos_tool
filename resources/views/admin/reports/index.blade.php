<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reportes'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">

        <div class="flex items-center justify-between mb-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-2">
                <select name="client" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos los clientes</option>
                    @foreach ($clients as $id => $name)
                        <option value="{{ $id }}" @selected(request('client') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos los estados</option>
                    <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                    <option value="issued" @selected(request('status') === 'issued')>Emitido</option>
                </select>
            </form>
            <p class="text-sm text-gray-400">Los reportes se crean desde la vista de cada captura.</p>
        </div>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Cliente</th>
                        <th scope="col" class="px-4 py-3">Equipo</th>
                        <th scope="col" class="px-4 py-3">Versión</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3">Emitido</th>
                        <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-3">{{ $report->capture?->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $report->capture?->device?->displayName() ?? 'Captura #'.$report->capture_id }}
                            </td>
                            <td class="px-4 py-3">v{{ $report->version }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full
                                    {{ $report->status === App\Enums\ReportStatus::Draft
                                        ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                        : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                                    {{ $report->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($report->issued_at)
                                    {{ $report->issued_at->format('d/m/Y H:i') }}
                                    <div class="text-xs text-gray-400">{{ $report->issuer?->name }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.reports.show', $report) }}"
                                        class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Abrir</a>
                                    @if ($report->pdf_path)
                                        <a href="{{ route('admin.reports.pdf', $report) }}"
                                            class="font-medium text-gray-600 dark:text-gray-400 hover:underline">PDF</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                Sin reportes. Abre una captura completada y usa "Generar reporte".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</x-admin-layout>
