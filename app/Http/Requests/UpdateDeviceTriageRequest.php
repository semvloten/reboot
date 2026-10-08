<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Onderdeel: Controleert prioriteit en toewijzing aan een bestaande keurmeester.
 * Eisen: FE-06, FE-13, RV-02, RV-07, TE-04.
 * Bouw: T-13 (keurmeesteroverzicht), T-25 (roltoegang), T-29 (invoercontrole).
 * Geplande controle: T-14, T-26, T-30.
 * Toelichting: Prioriteren/toewijzen komt uit projectbriefing hoofdstuk 4; geen afzonderlijk eis- of taaknummer in de planning.
 */
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
