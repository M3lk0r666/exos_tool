<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reportes', 'href' => route('admin.reports.index')],
    ['name' => 'Reporte v'.$report->version.' — '.($capture->device?->displayName() ?? 'Captura #'.$capture->id)],
]">
    @php($isDraft = $report->status === App\Enums\ReportStatus::Draft)
    @php($canEdit = $isDraft && auth()->user()->can('update', $report))

    {{-- Encabezado y acciones --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $capture->device?->displayName() ?? 'Equipo' }}
                        <span class="text-gray-400 font-normal">· {{ $capture->client?->name }}</span>
                    </h2>
                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full
                        {{ $isDraft ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                        v{{ $report->version }} · {{ $report->status->label() }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Captura del {{ $capture->captured_at?->format('d/m/Y H:i') }} ·
                    EXOS {{ $capture->exos_version }}
                    @if (! $isDraft && $report->issuer)
                        · Emitido por {{ $report->issuer->name }} el {{ $report->issued_at?->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>

            <div class="lg:ms-auto flex flex-wrap gap-2">
                <a href="{{ route('admin.captures.show', $capture) }}"
                    class="py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Ver captura
                </a>
                <a href="{{ route('admin.methodology.pdf') }}" title="Dictamen entregable: cómo se obtienen y validan los hallazgos"
                    class="py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    Dictamen metodológico
                </a>
                <a href="{{ route('admin.reports.pdf', $report) }}"
                    class="py-2 px-4 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500">
                    {{ $isDraft ? 'Vista previa PDF' : 'Descargar PDF' }}
                </a>
                @if ($isDraft)
                    @can('issue', $report)
                        <form method="POST" action="{{ route('admin.reports.issue', $report) }}"
                            onsubmit="return confirm('¿Emitir la versión v{{ $report->version }}? El reporte quedará congelado y se generará el PDF.');">
                            @csrf
                            <button type="submit"
                                class="py-2 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                                Emitir versión
                            </button>
                        </form>
                    @endcan
                @else
                    @can('create', App\Models\Finding::class)
                        <form method="POST" action="{{ route('admin.reports.new-version', $report) }}">
                            @csrf
                            <button type="submit"
                                class="py-2 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                                Crear nueva versión
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    {{-- Semáforo por área --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Estado por área</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
            @foreach ($areas as $area)
                <div class="border rounded-lg p-3 text-center {{ App\Services\Reporting\AreaStatusService::cardClasses($area['status']) }}">
                    <div class="text-sm font-semibold">{{ $area['label'] }}</div>
                    <div class="text-xs mt-1">
                        @if ($area['count'] === 0)
                            Sin hallazgos
                        @else
                            {{ $area['count'] }} hallazgo(s) · peor: {{ $area['worst']->label() }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-2 mt-3">
            @foreach ($severityCounts as $level => $count)
                @php($sev = App\Enums\FindingSeverity::from($level))
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $sev->badgeClasses() }}">
                    {{ $sev->label() }}: {{ $count }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Secciones editables (WYSIWYG) --}}
    <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
        @csrf
        @method('PUT')

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Contenido del reporte</h3>

        @foreach (['executive_summary' => 'Resumen ejecutivo', 'conclusions' => 'Conclusiones', 'recommendations' => 'Recomendaciones'] as $field => $label)
            <div class="mb-5">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</label>
                @if ($canEdit)
                    {{-- Quill local: el div es el editor; el textarea oculto lleva el HTML al servidor --}}
                    <textarea id="{{ $field }}" name="{{ $field }}" class="hidden">{{ old($field, $report->{$field}) }}</textarea>
                    <div class="quill-editor bg-white dark:bg-gray-700 rounded-b-lg" data-target="{{ $field }}"
                        style="min-height: 180px;"></div>
                @else
                    <div class="prose prose-sm dark:prose-invert max-w-none p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        {!! $report->{$field} ?: '<span class="text-gray-400">Sin contenido.</span>' !!}
                    </div>
                @endif
            </div>
        @endforeach

        @if ($canEdit)
            <button type="submit"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                Guardar contenido
            </button>
        @endif
    </form>

    {{-- Hallazgos --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Hallazgos ({{ $findings->count() }})
            </h3>
            @if ($canEdit)
                @can('create', App\Models\Finding::class)
                    <button type="button" onclick="document.getElementById('manual-finding').showModal()"
                        class="py-2 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                        + Hallazgo manual
                    </button>
                @endcan
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($findings as $finding)
                @include('admin.reports._finding', ['finding' => $finding, 'canEdit' => $canEdit])
            @empty
                <p class="text-sm text-gray-400">Sin hallazgos para esta captura.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal: hallazgo manual --}}
    @if ($canEdit)
        <dialog id="manual-finding" class="rounded-lg p-0 w-full max-w-2xl backdrop:bg-gray-900/50 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.findings.store', $capture) }}" class="p-6">
                @csrf
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nuevo hallazgo manual</h3>

                <div class="grid gap-4 sm:grid-cols-3 mb-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Severidad</label>
                        <select name="level" required class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach (App\Enums\FindingSeverity::cases() as $sev)
                                <option value="{{ $sev->value }}">{{ $sev->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Área</label>
                        <select name="area" required class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach (App\Services\Reporting\AreaStatusService::AREAS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Entidad (puerto/slot)</label>
                        <input type="text" name="entity" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Título *</label>
                    <input type="text" name="title" required class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Descripción *</label>
                    <textarea name="description" rows="3" required class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Impacto</label>
                    <textarea name="impact" rows="2" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Recomendación</label>
                    <textarea name="recommendation" rows="2" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Evidencia (texto)</label>
                    <textarea name="evidence" rows="2" class="w-full text-sm font-mono rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="py-2 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">Agregar</button>
                    <button type="button" onclick="this.closest('dialog').close()"
                        class="py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">Cancelar</button>
                </div>
            </form>
        </dialog>
    @endif

    {{-- El editor Quill se inicializa desde resources/js/app.js (bundle local, funciona sin internet) --}}
</x-admin-layout>
