<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Capturas'],
]">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
                Capturas
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Archivos tech-support y logs analizados por cliente y equipo.
            </p>
        </div>
        @can('create', App\Models\Capture::class)
            <a href="{{ route('admin.captures.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800 shadow-sm">
                <i class="ri-upload-cloud-2-line"></i> Subir archivos
            </a>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.captures.index') }}" class="flex flex-wrap gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
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
                @foreach (App\Enums\CaptureStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-[11px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">#</th>
                        <th class="px-4 py-2.5 font-bold">Archivo</th>
                        <th class="px-4 py-2.5 font-bold">Tipo</th>
                        <th class="px-4 py-2.5 font-bold">Cliente</th>
                        <th class="px-4 py-2.5 font-bold">Equipo</th>
                        <th class="px-4 py-2.5 font-bold">Fecha de captura</th>
                        <th class="px-4 py-2.5 font-bold">Estado</th>
                        <th class="px-4 py-2.5 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($captures as $capture)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            data-capture-row="{{ $capture->id }}">
                            <td class="px-4 py-2.5 text-gray-400 font-mono text-xs">{{ $capture->id }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('admin.captures.show', $capture) }}" class="hover:text-blue-700">
                                    {{ Str::limit($capture->original_filename, 40) }}
                                </a>
                                <div class="text-xs text-gray-400">{{ number_format($capture->file_size / 1024) }} KB</div>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md
                                    {{ $capture->isLogAnalysis()
                                        ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
                                        : 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                    {{ $capture->analysisTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">{{ $capture->client?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ $capture->device?->displayName() ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ $capture->captured_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <span data-capture-status="{{ $capture->id }}"
                                    data-status-value="{{ $capture->status->value }}"
                                    class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md {{ $capture->status->badgeClasses() }}"
                                    @if ($capture->error_message) title="{{ $capture->error_message }}" @endif>
                                    {{ $capture->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.captures.show', $capture) }}"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300">
                                        <i class="ri-eye-line"></i> Ver
                                    </a>
                                    @can('delete', $capture)
                                        <form method="POST" action="{{ route('admin.captures.destroy', $capture) }}"
                                            onsubmit="return confirm('¿Eliminar esta captura y sus métricas?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold uppercase px-2.5 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                No hay capturas registradas. Sube un archivo tech-support para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $captures->links() }}
        </div>
    </div>

    @push('js')
        <script>
            // Polling de estados mientras haya capturas pendientes/procesando.
            (function () {
                const pending = Array.from(document.querySelectorAll('[data-capture-status]'))
                    .filter(el => ['pending', 'processing'].includes(el.dataset.statusValue))
                    .map(el => el.dataset.captureStatus);

                if (pending.length === 0) return;

                const timer = setInterval(async () => {
                    try {
                        const res = await fetch('{{ route('admin.captures.status') }}?ids=' + pending.join(','), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const done = data.every(c => !['pending', 'processing'].includes(c.status));
                        if (done) {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    } catch (e) { /* reintentar en el siguiente ciclo */ }
                }, 4000);
            })();
        </script>
    @endpush
</x-admin-layout>
