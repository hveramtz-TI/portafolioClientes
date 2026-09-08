<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rubro_id' => ['required', 'uuid', Rule::exists('rubros', 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'name')->where(
                    fn ($query) => $query->where('rubro_id', $this->input('rubro_id'))
                ),
            ],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
