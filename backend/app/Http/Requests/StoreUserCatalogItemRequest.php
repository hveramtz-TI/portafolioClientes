<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesUserCatalogItem;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store a user catalog item: a fork of a global base item or a personal item
 * (R3, R4). `#[FailOnUnknownFields]` rejects any top-level key that is not
 * part of the type-driven rules — including `status` (S4.2) and arbitrary
 * fields — with a 422 instead of silently dropping them.
 */
#[FailOnUnknownFields]
class StoreUserCatalogItemRequest extends FormRequest
{
    use ValidatesUserCatalogItem;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Store only requires an authenticated user; ownership is a per-row
     * property enforced by the policy on view/update/delete (R1).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->itemTypeFromInput();
        $userId = $this->user()?->id;
        $isFork = $this->hasForkBase();

        $rules = [
            'item_type' => ['required', 'string', Rule::in(self::ITEM_TYPES)],
            'base_id' => ['nullable', 'uuid'],
            'parent_fork_id' => ['nullable', 'uuid'],
        ];

        if ($isFork && in_array($type, self::ITEM_TYPES, true)) {
            // R2 referential integrity first: the base row must exist live in
            // the table mapped by the (validated) item_type — a wrong-type or
            // bogus UUID never reaches the identity check as a valid fork.
            $rules['base_id'][] = $this->baseExistsRule($type);

            if ($userId !== null) {
                $rules['base_id'][] = $this->forkIdentityUniqueRule($type, $userId);
            }
        }

        $this->addParentRules($rules, $type, $userId, $isFork);
        $this->addDisplayRules($rules, $type, $userId, $isFork, null);

        return $rules;
    }
}
