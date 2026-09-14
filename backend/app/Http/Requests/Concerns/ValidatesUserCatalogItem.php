<?php

namespace App\Http\Requests\Concerns;

use App\Models\UserCatalogItem;
use App\Support\UserCatalogSubtree;
use App\Support\UserCatalogType;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
     * Resolve the item type: the plural route segment is authoritative (D-1),
     * then the payload `item_type`, then the bound item.
     */
    protected function itemTypeFromInput(?UserCatalogItem $item = null): string
    {
        $routeType = $this->routeItemType();

        if ($routeType !== null) {
            return $routeType;
        }

        $type = $this->input('item_type');

        return is_string($type) && $type !== '' ? $type : ($item?->item_type ?? '');
    }

    /**
     * The singular item type carried by the {type} route segment, if any.
     */
    protected function routeItemType(): ?string
    {
        return UserCatalogType::fromRoute($this->route('type'));
    }

    /**
     * The route {type} and the payload `item_type` must agree (D-1).
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function routeTypeCoherenceRule(): Closure
    {
        return function ($attribute, $value, $fail): void {
            $routeType = $this->routeItemType();

            if ($routeType !== null && $value !== $routeType) {
                $fail('The item type must match the route type.');
            }
        };
    }

    /**
     * Store never creates forks: a non-null `base_id` is rejected with a
     * message pointing at the dedicated cascade endpoint (R4/S4.3, D12).
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function baseIdForbiddenRule(): Closure
    {
        return function ($attribute, $value, $fail): void {
            if ($value !== null && $value !== '') {
                $fail('The base_id field is not writable; fork base items through the fork endpoint (POST /api/user-catalog/{type}/{baseId}/fork).');
            }
        };
    }

    /**
     * Defensively read the route-bound UserCatalogItem. The HTTP routes bind
     * `{fork}` (implicit binding); the slice-3 request tests bind
     * `{userCatalogItem}` explicitly. Both are resolved so authorize() and
     * rules() stay testable.
     */
    protected function routeItem(): ?UserCatalogItem
    {
        $bound = $this->route('userCatalogItem') ?? $this->route('fork');

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
            'parent_fork_id.required' => 'A personal categoria/service item must belong to a fork tree (parent_fork_id).',
            'status' => 'The status field is not writable; status changes go through dedicated endpoints.',
            'prohibited' => 'The :attribute field is not allowed.',
        ];
    }

    /**
     * R3 move/attach + D-9 detach contract for `parent_fork_id` on update.
     * The key is validated only when present (`sometimes`): a rubro explicit
     * null is legal (J4), while a categoria/service explicit null is a detach
     * and is rejected. A non-null parent must be a type-coherent, live fork
     * owned by the caller and never the item itself (cycle guard).
     *
     * @return Closure(string, mixed, Closure): void
     */
    protected function parentForkUpdateRule(UserCatalogItem $item, string $type, ?string $userId): Closure
    {
        return function ($attribute, $value, $fail) use ($item, $type, $userId): void {
            if ($value === null) {
                if ($type !== 'rubro') {
                    $fail('A categoria/service fork cannot be detached from its parent.');
                }

                return;
            }

            if (! is_string($value) || ! Str::isUuid($value)) {
                $fail('The parent fork must be a valid UUID.');

                return;
            }

            if ($type === 'rubro') {
                $fail('A rubro item cannot have a parent fork.');

                return;
            }

            if ($userId === null) {
                return;
            }

            $expected = $type === 'categoria' ? 'rubro' : 'categoria';

            $parentExists = UserCatalogItem::query()
                ->whereKey($value)
                ->whereKeyNot($item->id)
                ->where('user_id', $userId)
                ->where('item_type', $expected)
                ->whereNull('deleted_at')
                ->exists();

            if (! $parentExists) {
                $fail("The parent fork must be a {$expected} item owned by the same user.");

                return;
            }

            // R3 cycle guard: an item must never be reparented into itself or
            // one of its descendants. Type coherence makes a real cycle
            // unreachable through valid rows, but the guard keeps malformed
            // data from forming one (the resolver assumes an acyclic chain).
            if ($this->isDescendantOf($value, $item, $userId)) {
                $fail('A fork cannot be moved under itself or one of its descendants.');

                return;
            }

            // D8/S5.4: visible-name uniqueness is re-checked at the destination.
            $this->assertNoDestinationNameClash($item, $value, $userId, $fail);
        };
    }

    /**
     * Whether `$candidateId` is `$item` itself or anywhere below it in the
     * caller's live fork tree (R3 cycle guard), via the shared owner-scoped
     * subtree traversal.
     */
    protected function isDescendantOf(string $candidateId, UserCatalogItem $item, string $userId): bool
    {
        return UserCatalogSubtree::contains($item, $candidateId, $userId);
    }

    /**
     * Reject the move when another non-deleted sibling under the destination
     * parent already resolves to the same visible display name (R3/D8/S5.4).
     * The visible name is the override when present, else the live base value.
     *
     * @param  Closure(string, mixed, Closure): void  $fail
     */
    protected function assertNoDestinationNameClash(UserCatalogItem $item, string $destinationId, string $userId, Closure $fail): void
    {
        $display = $this->displayNameKey($item->item_type);
        $movingName = $this->visibleDisplayName($item, $display);

        if ($movingName === null) {
            return;
        }

        $siblings = UserCatalogItem::query()
            ->where('user_id', $userId)
            ->where('item_type', $item->item_type)
            ->where('parent_fork_id', $destinationId)
            ->whereNull('deleted_at')
            ->whereKeyNot($item->id)
            ->with('base')
            ->get();

        foreach ($siblings as $sibling) {
            if ($this->visibleDisplayName($sibling, $display) === $movingName) {
                $fail('Another item with the same visible name already exists under the destination parent.');

                return;
            }
        }
    }

    /**
     * The visible display value of an item: its override when present, else
     * the live base column. Returns null when neither is a non-empty string.
     */
    protected function visibleDisplayName(UserCatalogItem $item, string $display): ?string
    {
        $override = ($item->overrides ?? [])[$display] ?? null;

        if (is_string($override) && $override !== '') {
            return $override;
        }

        $baseValue = $item->base?->getAttribute($display);

        return is_string($baseValue) && $baseValue !== '' ? $baseValue : null;
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
