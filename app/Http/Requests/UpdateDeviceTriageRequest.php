<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceTriageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'inspector';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'priority' => ['required', 'integer', Rule::in([0, 1, 2])],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'inspector')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'priority.required' => 'Kies een prioriteit.',
            'priority.integer' => 'Kies een geldige prioriteit.',
            'priority.in' => 'Kies laag, normaal of hoog.',
            'assigned_to_user_id.integer' => 'Kies een geldige keurmeester.',
            'assigned_to_user_id.exists' => 'Deze keurmeester is niet beschikbaar.',
        ];
    }
}
