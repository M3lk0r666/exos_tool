<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyzerRule;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyzerRuleController extends Controller
{
    private const LEVELS = ['critical', 'high', 'medium', 'low', 'informational'];

    public function index(): View
    {
        $this->authorize('rules.manage');

        $rules = AnalyzerRule::orderBy('analyzer')->orderBy('code')->get();

        return view('admin.rules.index', compact('rules'));
    }

    public function update(Request $request, AnalyzerRule $rule): RedirectResponse
    {
        $this->authorize('rules.manage');

        $data = $request->validate([
            'threshold_warning' => ['nullable', 'numeric', 'min:0'],
            'threshold_critical' => ['nullable', 'numeric', 'min:0'],
            'level_warning' => ['required', Rule::in(self::LEVELS)],
            'level_critical' => ['nullable', Rule::in(self::LEVELS)],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $data['enabled'] = $request->boolean('enabled');

        $original = $rule->only(array_keys($data));
        $rule->update($data);

        AuditLogger::log('updated', $rule, [
            'code' => $rule->code,
            'before' => $original,
            'after' => $rule->only(array_keys($data)),
        ]);

        return back()->with('success', "Regla {$rule->code} actualizada. Aplica en los próximos análisis.");
    }
}
