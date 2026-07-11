<?php

namespace App\Http\Requests;

use App\Services\Reporting\AreaStatusService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Finding::class);
    }

    public function rules(): array
    {
        return [
            'level' => ['required', Rule::in(['critical', 'high', 'medium', 'low', 'informational'])],
            'area' => ['required', Rule::in(array_keys(AreaStatusService::AREAS))],
            'entity' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65000'],
            'impact' => ['nullable', 'string', 'max:65000'],
            'recommendation' => ['nullable', 'string', 'max:65000'],
            'evidence' => ['nullable', 'string', 'max:65000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'level' => 'severidad',
            'area' => 'área',
            'entity' => 'entidad',
            'title' => 'título',
            'description' => 'descripción',
            'impact' => 'impacto',
            'recommendation' => 'recomendación',
            'evidence' => 'evidencia',
        ];
    }
}
