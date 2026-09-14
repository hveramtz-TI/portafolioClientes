<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesUserCatalogItem;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store a personal user catalog item (R2, R4/S4.3). Store NEVER creates a
 * fork: `base_id` must be absent/null (base items are forked only through the
 * dedicated cascade endpoint) and a route/body `item_type` mismatch is a 422.
 * `#[FailOnUnknownFields]` rejects `status` and arbitrary keys with a 422
 * instead of silently dropping them.
 */
#[FailOnUnknownFields]
class StoreUserCatalogItemRequest extends FormRequest
{
    use ValidatesUserCatalogItem;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Store only requires an authenticated user; ownership is a per-row
     * property enforced by the policy (R1).
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

        $rules = [
            'item_type' => ['sometimes', 'string', Rule::in(self::ITEM_TYPES), $this->routeTypeCoherenceRule()],
            'base_id' => ['nullable', 'uuid', $this->baseIdForbiddenRule()],
            'parent_fork_id' => ['nullable', 'uuid'],
        ];

        if (in_array($type, self::ITEM_TYPES, true)) {
            $this->addParentRules($rules, $type, $userId, false);
            $this->addDisplayRules($rules, $type, $userId, false, null);
        }

        return $rules;
    }

    /**
     * The personal overrides built from the validated display fields: a
     * personal item resolves its values from `overrides` (R5).
     *
     * @return array<string, mixed>
     */
    public function personalOverrides(): array
    {
        $validated = $this->validated();
        $overrides = [];

        foreach ($this->displayFields($this->itemTypeFromInput()) as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $overrides[$field] = $validated[$field];
            }
        }

        return $overrides;
    }
}
