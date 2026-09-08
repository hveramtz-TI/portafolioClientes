<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    /**
     * Allowed technical tags for services (D4).
     *
     * @var array<int, string>
     */
    public const ALLOWED_TAGS = ['frontend', 'backend', 'fullstack', 'devops', 'mobile'];

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
            'categoria_id' => ['required', 'uuid', Rule::exists('categorias', 'id')],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'title')->where(
                    fn ($query) => $query->where('categoria_id', $this->input('categoria_id'))
                ),
            ],
            'description' => ['nullable', 'string'],
            'value' => ['required', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(self::ALLOWED_TAGS)],
        ];
    }
}
