<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('finding'));
    }

    public function rules(): array
    {
        return [
            'level' => ['required', Rule::in(['critical', 'high', 'medium', 'low', 'informational'])],
            'status' => ['required', Rule::in(['open', 'acknowledged', 'in_progress', 'resolved', 'false_positive'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65000'],
            'impact' => ['nullable', 'string', 'max:65000'],
            'recommendation' => ['nullable', 'string', 'max:65000'],
            'status_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return (new StoreFindingRequest)->attributes() + [
            'status' => 'estado',
            'status_notes' => 'notas de estado',
        ];
    }
}
