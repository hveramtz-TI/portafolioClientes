<?php

namespace App\Support;

/**
 * Maps the plural user-catalog route segment to the singular `item_type`
 * stored on `user_catalog_items` (D-1). The URL is the authoritative type
 * source for store/show/update/status/fork routes.
 */
final class UserCatalogType
{
    /**
     * @var array<string, string>
     */
    public const ROUTE_MAP = [
        'rubros' => 'rubro',
        'categorias' => 'categoria',
        'services' => 'service',
    ];

    /**
     * Resolve the singular item type from a plural route segment.
     */
    public static function fromRoute(mixed $plural): ?string
    {
        return is_string($plural) ? (self::ROUTE_MAP[$plural] ?? null) : null;
    }
}
