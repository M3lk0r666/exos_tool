<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reglas de análisis'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Umbrales del Anexo B, editables. Los cambios aplican en los <b>próximos</b> análisis; las capturas ya
            procesadas conservan sus hallazgos. Pasa el cursor sobre el código para ver la referencia del umbral.
        </p>

        <div class="space-y-2">
            @foreach ($rules as $rule)
                <details class="border border-gray-200 dark:border-gray-700 rounded-lg {{ $rule->enabled ? '' : 'opacity-60' }}">
                    <summary class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg text-sm">
                        <span class="font-mono font-bold text-gray-900 dark:text-white shrink-0"
                            title="{{ $rule->params['reference'] ?? '' }}">{{ $rule->code }}</span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $rule->description }}</span>
                        <span class="ms-auto text-xs text-gray-400 shrink-0">{{ $rule->analyzer }}</span>
                        @unless ($rule->enabled)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300 shrink-0">Deshabilitada</span>
                        @endunless
                    </summary>
                    <form method="POST" action="{{ route('admin.rules.update', $rule) }}"
                        class="px-4 pb-4 grid gap-3 sm:grid-cols-5 items-end text-sm">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Umbral advertencia</label>
                            <input type="number" step="any" name="threshold_warning" value="{{ $rule->threshold_warning !== null ? 0 + $rule->threshold_warning : '' }}"
                                class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Severidad advertencia</label>
                            <select name="level_warning" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach (App\Enums\FindingSeverity::cases() as $sev)
                                    <option value="{{ $sev->value }}" @selected($rule->level_warning === $sev->value)>{{ $sev->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Umbral crítico</label>
                            <input type="number" step="any" name="threshold_critical" value="{{ $rule->threshold_critical !== null ? 0 + $rule->threshold_critical : '' }}"
                                class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Severidad crítica</label>
                            <select name="level_critical" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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
                                    class="rounded border-gray-300 text-blue-600">
                                Habilitada
                            </label>
                            <button type="submit"
                                class="py-1.5 px-3 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                Guardar
                            </button>
                        </div>
                    </form>
                </details>
            @endforeach
        </div>
    </div>
</x-admin-layout>
