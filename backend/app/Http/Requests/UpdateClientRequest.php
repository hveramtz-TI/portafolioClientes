<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

class UpdateClientRequest extends StoreClientRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['rut'] = [
            'required',
            'string',
            'max:20',
            new \App\Rules\Rut(),
            $this->rutUniquenessRule()->ignore($this->route('client')),
        ];

        return $rules;
    }
}
