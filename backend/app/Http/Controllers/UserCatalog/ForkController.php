<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Models\UserCatalogItem;
use App\Services\CascadeForkService;
use App\Support\UserCatalogType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Fork a base catalog item into the caller's private tree (R1, D12). The only
 * fork-creation path: the cascade engine owns atomicity, the duplicate check
 * and the rollback, so the controller stays a thin normalizer.
 */
class ForkController extends Controller
{
    public function __construct(private readonly CascadeForkService $forks) {}

    public function fork(string $type, string $baseId): JsonResponse
    {
        $itemType = UserCatalogType::fromRoute($type);
        $user = request()->user();

        // JD4-1: forking is a create on the personal catalog, so it carries the
        // same ability gate as store (D-8): the admin role is denied by the
        // policy and must never reach the cascade engine.
        Gate::authorize('create', UserCatalogItem::class);

        $summary = match ($itemType) {
            'rubro' => $this->forks->forkRubro($baseId, $user),
            'categoria' => $this->forks->forkCategoria($baseId, $user),
            'service' => $this->forks->forkService($baseId, $user),
        };

        return response()->json($this->normalizeSummary($itemType, $summary), 201);
    }

    /**
     * Normalize the engine summary into the public HTTP shape: root `id`,
     * `item_type`, created-item `counts` per type and every created `ids` entry.
     *
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function normalizeSummary(string $itemType, array $summary): array
    {
        $counts = ['rubro' => 0, 'categoria' => 0, 'service' => 0];
        $ids = [];

        if ($itemType === 'rubro') {
            $root = $summary['rubro'];
            $counts['rubro'] = 1;
            $counts['categoria'] = $summary['categorias']['count'];
            $counts['service'] = $summary['services']['count'];
            $ids = array_merge([$root], $summary['categorias']['ids'], $summary['services']['ids']);
        } elseif ($itemType === 'categoria') {
            $root = $summary['categoria'];
            $counts['categoria'] = 1;
            $counts['service'] = $summary['services']['count'];
            $ids = array_merge([$root], $summary['services']['ids']);
        } else {
            $root = $summary['service'];
            $counts['service'] = 1;
            $ids = [$root];
        }

        return [
            'id' => $root,
            'item_type' => $itemType,
            'counts' => $counts,
            'ids' => $ids,
        ];
    }
}
