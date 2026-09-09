<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesUserCatalogItem;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a user catalog item. Update is per-field override personalization
 * (D-3, D13): every writable field is optional, an explicit null removes the
 * override key (restoring inheritance, R5/S5.3) and an omitted field leaves
 * it untouched. Structural keys (item_type, base_id, parent_fork_id) and
 * `status` are rejected via #[FailOnUnknownFields] — they are not part of the
 * rules, so they fail with a 422 instead of being silently dropped.
 */
#[FailOnUnknownFields]
class UpdateUserCatalogItemRequest extends FormRequest
{
    use ValidatesUserCatalogItem;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the owner may update the item; the bound UserCatalogItem is read
     * defensively because the routes do not exist yet (D-3).
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $item = $this->routeItem();

        return $user !== null && $item !== null && $user->can('update', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $item = $this->routeItem();

        if (! $item instanceof UserCatalogItem) {
            return [];
        }

        $userId = $this->user()?->id;
        $type = $item->item_type;
        $rules = [];

        foreach ($this->displayFields($type) as $field) {
            $rules[$field] = match ($field) {
                'name', 'title' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'value' => ['nullable', 'integer', 'min:0'],
                'tags' => ['nullable', 'array'],
                default => [],
            };
        }

        if ($type === 'service') {
            $rules['tags.*'] = ['string', Rule::in(self::ALLOWED_TAGS)];
        }

        // Sibling visible-name uniqueness still applies to personal items on
        // rename; the item itself is excluded and its parent scopes the
        // search, since parent_fork_id is not writable (R3/S3.2).
        if ($item->base_id === null && $userId !== null) {
            $display = $this->displayNameKey($type);
            $rules[$display][] = $this->siblingNameRule($type, $userId, $item->parent_fork_id, $item->id);
        }

        return $rules;
    }

    /**
     * Merge the submitted per-field overrides into the item's current ones:
     * a present field sets the value, an explicit null removes that key
     * (restoring inheritance), an omitted field is left untouched (R5/S5.3).
     *
     * @return array<string, mixed>
     */
    public function mergedOverrides(UserCatalogItem $item): array
    {
        $merged = $item->overrides ?? [];
        $validated = $this->validated();

        foreach ($this->displayFields($item->item_type) as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $value = $validated[$field];

            if ($value === null) {
                unset($merged[$field]);
            } else {
                $merged[$field] = $value;
            }
        }

        return $merged;
    }
}
