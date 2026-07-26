<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reglas de análisis'],
]">
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Hanken Grotesk',sans-serif;">
            Reglas de análisis
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-3xl">
            Umbrales del Anexo B, editables. Los cambios aplican en los <b>próximos</b> análisis; las capturas ya
            procesadas conservan sus hallazgos. Pasa el cursor sobre el código para ver la referencia del umbral.
        </p>
    </div>

    <div class="space-y-2">
        @foreach ($rules as $rule)
            <details class="bg-white dark:bg-gray-800 border rounded-lg group
                {{ $rule->enabled ? 'border-gray-200 dark:border-gray-700' : 'border-gray-200 dark:border-gray-700 opacity-70' }}">
                <summary class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg text-sm">
                    <span class="font-mono font-bold text-[11px] uppercase px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 shrink-0"
                        title="{{ $rule->params['reference'] ?? '' }}">{{ $rule->code }}</span>
                    <span class="text-gray-700 dark:text-gray-300">{{ $rule->description }}</span>
                    <span class="ms-auto text-[11px] uppercase tracking-wide text-gray-400 font-mono shrink-0">{{ $rule->analyzer }}</span>
                    @if ($rule->enabled)
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 shrink-0">Activa</span>
                    @else
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 shrink-0">Inactiva</span>
                    @endif
                    <svg class="w-3 h-3 text-gray-400 transition-transform group-open:rotate-180 shrink-0" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </summary>

                @php($ref = $rule->params['reference'] ?? null)
                <form method="POST" action="{{ route('admin.rules.update', $rule) }}" class="px-4 pb-4">
                    @csrf
                    @method('PUT')

                    @if ($ref)
                        <div class="mb-3 p-2.5 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                            <span class="font-bold uppercase text-[10px] tracking-wide text-gray-400">Referencia del umbral</span><br>
                            {{ $ref }}
                        </div>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-5 items-end text-sm">
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">Umbral advertencia</label>
                            <input type="number" step="any" name="threshold_warning" value="{{ $rule->threshold_warning !== null ? 0 + $rule->threshold_warning : '' }}"
                                class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">Severidad advertencia</label>
                            <select name="level_warning" class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach (App\Enums\FindingSeverity::cases() as $sev)
                                    <option value="{{ $sev->value }}" @selected($rule->level_warning === $sev->value)>{{ $sev->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">Umbral crítico</label>
                            <input type="number" step="any" name="threshold_critical" value="{{ $rule->threshold_critical !== null ? 0 + $rule->threshold_critical : '' }}"
                                class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">Severidad crítica</label>
                            <select name="level_critical" class="w-full text-sm rounded-md border-gray-300 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">—</option>
                                @foreach (App\Enums\FindingSeverity::cases() as $sev)
                                    <option value="{{ $sev->value }}" @selected($rule->level_critical === $sev->value)>{{ $sev->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" @checked($rule->enabled)
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                Habilitada
                            </label>
                            <button type="submit"
                                class="inline-flex items-center gap-1 py-1.5 px-3 text-xs font-semibold text-white bg-blue-700 rounded-md hover:bg-blue-800">
                                <i class="ri-save-line"></i> Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </details>
        @endforeach
    </div>
</x-admin-layout>
