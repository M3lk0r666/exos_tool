<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('settings.manage');

        $settings = Setting::orderBy('key')->get();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $values = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ])['settings'];

        foreach ($values as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting === null) {
                continue;
            }

            // Validación mínima por tipo
            if ($setting->type === 'int' && ! is_numeric($value)) {
                continue;
            }
            if ($setting->type === 'json') {
                json_decode((string) $value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
            }

            $setting->update(['value' => $value]);
        }

        // Logo de la empresa para reportes
        if ($request->hasFile('company_logo')) {
            $request->validate(['company_logo' => ['image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048']]);
            $path = $request->file('company_logo')->store('branding', 'public');
            Setting::updateOrCreate(
                ['key' => 'branding.company_logo'],
                ['value' => $path, 'type' => 'string', 'description' => 'Ruta del logo de la empresa para reportes']
            );
        }

        AuditLogger::log('updated', null, ['settings' => array_keys($values)]);

        return back()->with('success', 'Configuración guardada.');
    }
}
