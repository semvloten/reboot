<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'customer';
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        if (is_string($this->input('serial_number'))) {
            $values['serial_number'] = strtoupper(trim($this->input('serial_number')));
        }
        if (is_string($this->input('asking_price'))) {
            $values['asking_price'] = str_replace(',', '.', $this->input('asking_price'));
        }
        $this->merge($values);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['console', 'telefoon', 'laptops'])],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:devices,serial_number'],
            'condition' => ['required', Rule::in(['gebruikt', 'goed', 'nieuw'])],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'asking_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Vul :attribute in.',
            'string' => 'Vul een geldige waarde in voor :attribute.',
            'in' => 'Selecteer een geldige waarde voor :attribute.',
            'max.string' => ':attribute mag maximaal :max tekens bevatten.',
            'serial_number.unique' => 'Dit serienummer is al geregistreerd. Controleer het nummer of neem contact met ons op.',
            'asking_price.numeric' => 'Vul een geldige vraagprijs in.',
            'asking_price.min' => 'De vraagprijs moet minimaal € 0,01 zijn.',
            'asking_price.max' => 'De vraagprijs mag maximaal € 99.999.999,99 zijn.',
            'asking_price.decimal' => 'Gebruik maximaal twee decimalen voor de vraagprijs.',
            'photos.array' => 'Selecteer geldige foto’s.',
            'photos.max' => 'Je kunt maximaal vijf foto’s toevoegen.',
            'photos.*.required' => 'Selecteer een foto.',
            'photos.*.image' => 'Het bestand moet een afbeelding zijn.',
            'photos.*.mimes' => 'Gebruik een JPG-, PNG- of WebP-foto.',
            'photos.*.max' => 'Elke foto mag maximaal 2 MB groot zijn.',
            'photos.*.uploaded' => 'De foto kon niet worden geüpload. Gebruik een foto van maximaal 2 MB.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['type' => 'type apparaat', 'brand' => 'merk', 'model' => 'model', 'serial_number' => 'serienummer', 'condition' => 'conditie', 'accessories' => 'accessoires', 'asking_price' => 'vraagprijs'];
    }
}
