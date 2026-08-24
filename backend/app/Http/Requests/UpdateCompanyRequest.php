<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends StoreCompanyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['rut'] = ['required', 'string', 'max:20', new Rut(), Rule::unique('companies', 'rut')->ignore($this->route('company'))];

        return $rules;
    }
}
