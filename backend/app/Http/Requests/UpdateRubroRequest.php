<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateRubroRequest extends StoreRubroRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('rubros', 'name')->ignore($this->route('rubro')),
        ];

        return $rules;
    }
}
