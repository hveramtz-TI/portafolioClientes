<?php

namespace App\Http\Requests\Concerns;

use App\Models\UserCatalogItem;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared validation contract for the user catalog item store/update pair
 * (D-3). One request pair, driven by `item_type`, enforces the R3 fork
 * identity + structural uniqueness rules and the R4 field validation rules
 * for both endpoints.
 */
trait ValidatesUserCatalogItem
{
    /**
     * Allowed item types (R3/S3.3 hierarchy).
     *
     * @var array<int, string>
     */
    public const ITEM_TYPES = ['rubro', 'categoria', 'service'];

    /**
     * Allowed technical tags for services (R4/S4.1).
     *
     * @var array<int, string>
     */
    public const ALLOWED_TAGS = ['frontend', 'backend', 'fullstack', 'devops', 'mobile'];

    /**
     * Override fields that can be personalized per item type.
     *
     * @return array<int, string>
     */
    protected function displayFields(string $type): array
    {
        return match ($type) {
            'service' => ['title', 'description', 'value', 'tags'],
            default => ['name', 'description'],
        };
    }

    /**
     * The visible-name key used by sibling uniqueness (R3/S3.2): rubro and
     * categoria use `name`, service uses `title`.
     */
    protected function displayNameKey(string $type): string
    {
        return $type === 'service' ? 'title' : 'name';
    }

    /**
     * Resolve the item type from the payload, falling back to the bound item.
     */
    protected function itemTypeFromInput(?UserCatalogItem $item = null): string
    {
        $type = $this->input('item_type');

        return is_string($type) && $type !== '' ? $type : ($item?->item_type ?? '');
    }

    /**
     * Whether the payload forks an existing base item (R3 fork identity).
     */
    protected function hasForkBase(): bool
    {
        $baseId = $this->input('base_id');

        return is_string($baseId) && $baseId !== '';
    }

    /**
     * Defensively read the route-bound UserCatalogItem. There are no routes
     * yet, so the bound value may already be the model (implicit binding) or
     * a raw id; both are resolved so authorize() and rules() stay testable.
     */
    protected function routeItem(): ?UserCatalogItem
    {
        $bound = $this->route('userCatalogItem');

        if ($bound instanceof UserCatalogItem) {
            return $bound;
        }

        return is_string($bound) ? UserCatalogItem::query()->find($bound) : null;
    }

    /**
     * Custom messages shared by the store/update pair.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_type.in' => 'The item type must be one of: rubro, categoria, service.',
            'base_id.unique' => 'You already have a fork of this item for the selected type.',
            'base_id.exists' => 'The selected base item does not exist in the catalog for the selected item type.',
            'parent_fork_id.required' => 'A personal categoria/service item must belong to a fork tree (parent_fork_id).',
            'status' => 'The status field is not writable; status changes go through dedicated endpoints.',
            'prohibited' => 'The :attribute field is not allowed.',
        ];
    }

    /**
     * R3/S3.1 fork identity: reject a second non-trashed fork of the same
     * base by the same user+item_type. Soft-deleted forks are ignored.
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function forkIdentityUniqueRule(string $type, string $userId): Unique
    {
        return Rule::unique('user_catalog_items', 'base_id')->where(
            fn ($query) => $query
                ->where('user_id', $userId)
                ->where('item_type', $type)
                ->whereNull('deleted_at')
        );
    }

    /**
     * R2 base reference: a non-null base_id MUST point at a live row of the
     * base table mapped by item_type (rubro→rubros, categoria→categorias,
     * service→services). Checking the type-mapped table enforces existence
     * AND type coherence in one rule, blocking R2-orphan rows at the door;
     * trashed bases are excluded, matching the fork engines' findOrFail/
     * default-scope behaviour. Only built for a validated item_type.
     */
    protected function baseExistsRule(string $type): Exists
    {
        $table = match ($type) {
            'rubro' => 'rubros',
            'categoria' => 'categorias',
            'service' => 'services',
        };

        return Rule::exists($table, 'id')->whereNull('deleted_at');
    }

    /**
     * R3/S3.3 parent coherence: rubro items never have a parent.
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function rubroParentForbiddenRule(): Closure
    {
        return function ($attribute, $value, $fail): void {
            $fail('A rubro item cannot have a parent fork.');
        };
    }

    /**
     * R3/S3.3 parent coherence: a categoria parent must be a rubro item and a
     * service parent must be a categoria item, owned by the same user and not
     * trashed. Wrong type / missing / foreign parent fails.
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function parentCoherenceRule(string $type, ?string $userId): Closure
    {
        return function ($attribute, $value, $fail) use ($type, $userId): void {
            if (! is_string($value) || $value === '' || $userId === null) {
                return;
            }

            $expected = $type === 'categoria' ? 'rubro' : 'categoria';

            $parentExists = UserCatalogItem::query()
                ->whereKey($value)
                ->where('user_id', $userId)
                ->where('item_type', $expected)
                ->whereNull('deleted_at')
                ->exists();

            if (! $parentExists) {
                $fail("The parent fork must be a {$expected} item owned by the same user.");
            }
        };
    }

    /**
     * R3/S3.2 sibling visible-name uniqueness: personal items (base_id null)
     * compete on their display name among non-trashed siblings sharing the
     * same parent_fork_id and item_type for the same user. The item itself is
     * excluded on update via $ignoreId. $parentId comes from the payload on
     * store and from the bound item on update (parent is not writable).
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function siblingNameRule(string $type, string $userId, ?string $parentId, ?string $ignoreId): Closure
    {
        $display = $this->displayNameKey($type);

        return function ($attribute, $value, $fail) use ($type, $userId, $display, $parentId, $ignoreId): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $siblings = UserCatalogItem::query()
                ->select('id', 'overrides')
                ->where('user_id', $userId)
                ->where('item_type', $type)
                ->whereNull('base_id')
                ->whereNull('deleted_at')
                ->when(
                    $parentId !== null,
                    fn ($query) => $query->where('parent_fork_id', $parentId),
                    fn ($query) => $query->whereNull('parent_fork_id')
                )
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->get();

            foreach ($siblings as $sibling) {
                if (($sibling->overrides[$display] ?? null) === $value) {
                    $fail('A personal :attribute with the same value already exists under this parent.');

                    return;
                }
            }
        };
    }

    /**
     * Add the parent rules for a store payload.
     *
     * @param  array<string, mixed>  $rules
     */
    protected function addParentRules(array &$rules, string $type, ?string $userId, bool $isFork): void
    {
        if ($type === 'rubro') {
            $rules['parent_fork_id'][] = $this->rubroParentForbiddenRule();

            return;
        }

        if (! $isFork) {
            $rules['parent_fork_id'][] = 'required';
        }

        $rules['parent_fork_id'][] = $this->parentCoherenceRule($type, $userId);
    }

    /**
     * Add the per-type display field rules and the sibling-name uniqueness
     * rule for personal items.
     *
     * @param  array<string, mixed>  $rules
     */
    protected function addDisplayRules(array &$rules, string $type, ?string $userId, bool $isFork, ?UserCatalogItem $item): void
    {
        foreach ($this->displayFields($type) as $field) {
            $fieldRules = match ($field) {
                'name', 'title' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'value' => ['nullable', 'integer', 'min:0'],
                'tags' => ['nullable', 'array'],
                default => [],
            };

            // Required-field contract per type (R3/S3.2 + R4/S4.2): personal
            // rubro/categoria need `name`, personal service needs `title` and
            // `value`; forks of a base inherit everything and require nothing.
            // 'required' stays implicit so an explicit null still fails for
            // personal items while 'nullable' lets forks/updates clear a field.
            if (! $isFork && in_array($field, ['name', 'title', 'value'], true)) {
                array_unshift($fieldRules, 'required');
            }

            $rules[$field] = $fieldRules;
        }

        if ($type === 'service') {
            $rules['tags.*'] = ['string', Rule::in(self::ALLOWED_TAGS)];
        }

        if (! $isFork && $userId !== null) {
            $display = $this->displayNameKey($type);
            $parentId = $this->input('parent_fork_id');
            $parentId = is_string($parentId) && $parentId !== '' ? $parentId : null;
            $rules[$display][] = $this->siblingNameRule($type, $userId, $parentId, $item?->id);
        }
    }
}
