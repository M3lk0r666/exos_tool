<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyzerRule;
use App\Models\Setting;
use Illuminate\View\View;

/**
 * Dictamen metodológico: explica qué se analiza, contra qué se contrasta
 * y de dónde provienen los umbrales — entregable al cliente para validar
 * la confiabilidad de los reportes.
 */
class MethodologyController extends Controller
{
    private function data(): array
    {
        return [
            'rules' => AnalyzerRule::orderBy('analyzer')->orderBy('code')->get(),
            'companyName' => Setting::get('branding.company_name', 'EXOS-Tool'),
            'companyLogo' => Setting::get('branding.company_logo', ''),
            'footerText' => Setting::get('branding.footer_text', ''),
            'generatedAt' => now(),
        ];
    }

    public function show(): View
    {
        return view('admin.methodology.show', $this->data());
    }

    public function pdf(): mixed
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('success', 'DomPDF no está instalado (composer require barryvdh/laravel-dompdf).');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.methodology.pdf', $this->data())
            ->setPaper('letter')
            ->setOption('isPhpEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return $pdf->download('Dictamen_metodologico_analisis_EXOS.pdf');
    }
}
