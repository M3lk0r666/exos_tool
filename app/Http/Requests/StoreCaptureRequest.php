<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Capture::class);
    }

    public function rules(): array
    {
        $maxMb = (int) Setting::get('upload.max_size_mb', 50);
        $extensions = Setting::get('upload.allowed_extensions', ['txt', 'log']);

        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'analysis_type' => ['required', 'in:tech_support,log'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'extensions:'.implode(',', $extensions),
                'max:'.($maxMb * 1024), // KB
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'files' => 'archivos',
            'files.*' => 'archivo',
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.extensions' => 'Solo se permiten archivos de texto (:values).',
            'files.*.max' => 'El archivo excede el tamaño máximo configurado.',
        ];
    }
}
