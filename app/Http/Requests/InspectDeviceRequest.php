<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'inspector';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = $this->route('device')->type;
        $checkRules = ['required', 'boolean'];
        if ($this->input('status') === 'goedgekeurd') {
            $checkRules[] = 'accepted';
        }

        return [
            'status' => ['required', Rule::in(['goedgekeurd', 'afgekeurd', 'onderhoud nodig'])],
            'notes' => ['required', 'string', 'max:5000'],
            'works' => $checkRules,
            'accessories_work' => $checkRules,
            'presentable' => $checkRules,
            'plugs_present' => $checkRules,
            'ports_work' => $checkRules,
            'reset_done' => $checkRules,
            'screen_work' => [Rule::excludeIf($type === 'console'), ...$checkRules],
            'battery_work' => [Rule::excludeIf($type === 'console'), ...$checkRules],
            'battery_percentage' => [Rule::excludeIf($type === 'console'), 'required', 'integer', 'between:0,100'],
            'video_port' => [Rule::excludeIf($type !== 'console'), 'required', 'string', 'max:100'],
            'port_types' => [Rule::excludeIf($type !== 'laptops'), 'required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Vul :attribute in.',
            'status.required' => 'Vul een status in.',
            'status.in' => 'Kies een geldige keuringsstatus.',
            'notes.required' => 'Vul een opmerking in.',
            'accepted' => 'Een apparaat kan alleen worden goedgekeurd als alle nodige checkboxes zijn geselecteerd.',
            'boolean' => 'Selecteer een geldige controle.',
            'integer' => 'Vul een heel percentage in.',
            'between' => 'Vul een batterijpercentage tussen 0 en 100 in.',
            'string' => 'Vul een geldige tekst in voor :attribute.',
            'max' => ':attribute mag maximaal :max tekens bevatten.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'notes' => 'opmerkingen', 'battery_percentage' => 'batterijpercentage',
            'video_port' => 'type videopoort', 'port_types' => 'typen aansluitingen',
            'works' => 'werking', 'accessories_work' => 'werking accessoires',
            'presentable' => 'vertoonbare staat', 'plugs_present' => 'aanwezige stekkers',
            'ports_work' => 'werking aansluitingen', 'reset_done' => 'fabrieksreset',
            'screen_work' => 'werking scherm', 'battery_work' => 'werking batterij',
        ];
    }
}
