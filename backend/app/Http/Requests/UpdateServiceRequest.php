<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateServiceRequest extends StoreServiceRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * On update, categoria_id is optional; when absent the service keeps its
     * current category. Title uniqueness is scoped to the destination category.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoriaId = $this->input('categoria_id', $this->route('service')->categoria_id);

        return [
            // 'sometimes' (not 'nullable'): categoria_id is NOT NULL. An
            // explicit null must 422, not pass validated() into a NOT NULL
            // column (500). Omitting it keeps the current category (no move).
            'categoria_id' => ['sometimes', 'uuid', Rule::exists('categorias', 'id')],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'title')
                    ->where(fn ($query) => $query->where('categoria_id', $categoriaId))
                    ->ignore($this->route('service')),
            ],
            'description' => ['nullable', 'string'],
            'value' => ['required', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(self::ALLOWED_TAGS)],
        ];
    }
}
