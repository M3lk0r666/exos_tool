<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Capturas'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
            <form method="GET" action="{{ route('admin.captures.index') }}" class="flex flex-wrap gap-2">
                <select name="client" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos los clientes</option>
                    @foreach ($clients as $id => $name)
                        <option value="{{ $id }}" @selected(request('client') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos los estados</option>
                    @foreach (App\Enums\CaptureStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </form>

            @can('create', App\Models\Capture::class)
                <a href="{{ route('admin.captures.create') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 15v2a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-2M10 4v10m0-10 3 3m-3-3-3 3" />
                    </svg>
                    Subir archivos
                </a>
            @endcan
        </div>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">#</th>
                        <th scope="col" class="px-4 py-3">Archivo</th>
                        <th scope="col" class="px-4 py-3">Tipo</th>
                        <th scope="col" class="px-4 py-3">Cliente</th>
                        <th scope="col" class="px-4 py-3">Equipo</th>
                        <th scope="col" class="px-4 py-3">Fecha de captura</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($captures as $capture)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            data-capture-row="{{ $capture->id }}">
                            <td class="px-4 py-3 text-gray-400">{{ $capture->id }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('admin.captures.show', $capture) }}" class="hover:text-blue-600">
                                    {{ Str::limit($capture->original_filename, 40) }}
                                </a>
                                <div class="text-xs text-gray-400">{{ number_format($capture->file_size / 1024) }} KB</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                    {{ $capture->isLogAnalysis()
                                        ? 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                    {{ $capture->analysisTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $capture->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $capture->device?->displayName() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $capture->captured_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span data-capture-status="{{ $capture->id }}"
                                    data-status-value="{{ $capture->status->value }}"
                                    class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $capture->status->badgeClasses() }}"
                                    @if ($capture->error_message) title="{{ $capture->error_message }}" @endif>
                                    {{ $capture->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.captures.show', $capture) }}"
                                        class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Ver</a>
                                    @can('delete', $capture)
                                        <form method="POST" action="{{ route('admin.captures.destroy', $capture) }}"
                                            onsubmit="return confirm('¿Eliminar esta captura y sus métricas?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline">Eliminar</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                No hay capturas registradas. Sube un archivo tech-support para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
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
