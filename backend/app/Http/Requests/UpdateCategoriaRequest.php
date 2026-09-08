<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends StoreCategoriaRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The parent category (rubro_id) is immutable on edit (HU-018).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['rubro_id']);

        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('categorias', 'name')
                ->where(fn ($query) => $query->where('rubro_id', $this->route('categoria')->rubro_id))
                ->ignore($this->route('categoria')),
        ];

        return $rules;
    }
}
