<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('rut') && is_string($this->input('rut'))) {
            $this->merge([
                'rut' => Rut::normalize($this->input('rut')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20', new Rut(), Rule::unique('companies', 'rut')],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'rut.required' => 'El RUT es obligatorio.',
            'rut.unique' => 'Ya existe una empresa con este RUT.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email no es válido.',
            'phone.required' => 'El teléfono es obligatorio.',
            'website.url' => 'El sitio web no es válido.',
        ];
    }
}
