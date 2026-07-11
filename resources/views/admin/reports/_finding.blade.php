{{-- Hallazgo del reporte: visible siempre, editable en borrador --}}
<details class="border border-gray-200 dark:border-gray-700 rounded-lg" id="finding-{{ $finding->id }}">
    <summary class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full shrink-0 {{ $finding->level->badgeClasses() }}">
            {{ $finding->level->label() }}
        </span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $finding->title }}</span>
        @if ($finding->is_manual)
            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 shrink-0">Manual</span>
        @endif
        @if ($finding->status !== App\Enums\FindingStatus::Open)
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 shrink-0">
                {{ $finding->status->label() }}
            </span>
        @endif
        <span class="ms-auto text-xs text-gray-400 font-mono shrink-0">{{ $finding->rule_code }}</span>
    </summary>

    <div class="px-4 pb-4 text-sm space-y-3">
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.findings.update', $finding) }}" class="space-y-3">
                @csrf
                @method('PUT')

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Severidad</label>
                        <select name="level" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach (App\Enums\FindingSeverity::cases() as $sev)
                                <option value="{{ $sev->value }}" @selected($finding->level === $sev)>{{ $sev->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Estado</label>
                        <select name="status" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach (App\Enums\FindingStatus::cases() as $st)
                                <option value="{{ $st->value }}" @selected($finding->status === $st)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Título</label>
                        <input type="text" name="title" value="{{ $finding->title }}" required
                            class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Descripción</label>
                    <textarea name="description" rows="3" required
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $finding->description }}</textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Impacto</label>
                        <textarea name="impact" rows="2"
                            class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $finding->impact }}</textarea>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Recomendación</label>
                        <textarea name="recommendation" rows="2"
                            class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $finding->recommendation }}</textarea>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Notas de estado</label>
                    <input type="text" name="status_notes" value="{{ $finding->status_notes }}"
                        class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="py-1.5 px-3 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Guardar cambios
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.findings.destroy', $finding) }}"
                onsubmit="return confirm('¿Eliminar este hallazgo del reporte?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-500 hover:underline">
                    Eliminar hallazgo
                </button>
            </form>
        @else
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $finding->description }}</p>
            @if ($finding->impact)
                <p><span class="font-semibold text-gray-900 dark:text-white">Impacto:</span> {{ $finding->impact }}</p>
            @endif
            @if ($finding->recommendation)
                <p><span class="font-semibold text-gray-900 dark:text-white">Recomendación:</span> {{ $finding->recommendation }}</p>
            @endif
        @endif

        @if ($finding->evidence)
            <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto font-mono text-gray-800 dark:text-gray-200">{{ $finding->evidence }}</pre>
        @endif
        @if ($finding->file_location)
            <p class="text-xs text-gray-400">Ubicación: {{ $finding->file_location }}</p>
        @endif

        {{-- Evidencias adjuntas --}}
        <div>
            @if ($finding->attachments->isNotEmpty())
                <div class="flex flex-wrap gap-3 mb-2">
                    @foreach ($finding->attachments as $attachment)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 text-xs">
                            @if ($attachment->type === 'image')
                                <a href="{{ Storage::url($attachment->path) }}" target="_blank">
                                    <img src="{{ Storage::url($attachment->path) }}" alt="{{ $attachment->caption }}"
                                        class="h-24 rounded object-cover">
                                </a>
                            @else
                                <a href="{{ Storage::url($attachment->path) }}" target="_blank"
                                    class="text-blue-600 hover:underline">{{ $attachment->original_filename }}</a>
                            @endif
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-gray-400">{{ $attachment->caption }}</span>
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('admin.findings.attachments.destroy', $attachment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Quitar</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($canEdit)
                <form method="POST" action="{{ route('admin.findings.attachments.store', $finding) }}"
                    enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="attachment" required
                        class="text-xs text-gray-500 file:me-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-gray-100 file:text-gray-700 dark:file:bg-gray-700 dark:file:text-gray-300">
                    <input type="text" name="caption" placeholder="Pie de evidencia (opcional)"
                        class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <button type="submit" class="py-1 px-2 text-xs font-medium text-white bg-gray-600 rounded hover:bg-gray-700">
                        Adjuntar evidencia
                    </button>
                </form>
            @endif
        </div>
    </div>
</details>
