<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Atomic cascade fork of base catalog rows into a user's private
 * user_catalog_items tree (R7, D-5).
 *
 * Each fork method owns exactly one DB transaction: either the whole tree is
 * persisted or nothing is (S7.2). Duplicate identity is checked INSIDE the
 * transaction (user + item_type + base_id, non-trashed — the SoftDeletes
 * scope excludes trashed rows, so re-forking after a soft delete is allowed).
 * Non-trashed descendants are copied REGARDLESS of their base status: a
 * deactivated base is forked with own status 'activo' and only resolves as
 * desactivado through R6 (S7.4, independently reactivable per D7). Exceptions
 * propagate — nothing is swallowed — so the transaction rolls back.
 *
 * Domain signals: a duplicate fork throws \DomainException (SPL, no new class)
 * and a missing or trashed base throws ModelNotFoundException via
 * Model::findOrFail, the idiomatic Eloquent not-found signal.
 *
 * sort_order: carried from the base ordering column where one exists
 * (Categoria::order); Rubro and Service have no ordering column, so those
 * forks take the positional index of the copied row (0 for the single rubro /
 * service fork, the collection position for each service under its categoria).
 */
class CascadeForkService
{
    /**
     * Fork a rubro base: its fork + one fork per non-trashed categoria + one
     * per non-trashed service under each, atomically (S7.1).
     *
     * @return array<string, mixed> tree summary (root id, counts + created ids)
     */
    public function forkRubro(string $baseId, User $user): array
    {
        return DB::transaction(function () use ($baseId, $user) {
            $rubro = Rubro::findOrFail($baseId);

            $this->assertNoExistingFork('rubro', $baseId, $user);

            $rubroFork = UserCatalogItem::create([
                'user_id' => $user->id,
                'item_type' => 'rubro',
                'base_id' => $rubro->id,
                'parent_fork_id' => null,
                'overrides' => [],
                'status' => 'activo',
                'sort_order' => 0,
            ]);

            $categoriaIds = [];
            $serviceIds = [];
            $categoriaCount = 0;
            $serviceCount = 0;

            foreach ($rubro->categorias()->orderBy('order')->get() as $categoria) {
                $categoriaFork = $this->createCategoriaFork($categoria, $user, $rubroFork);
                $categoriaIds[] = $categoriaFork->id;
                $categoriaCount++;

                foreach ($categoria->services()->get() as $position => $service) {
                    $serviceFork = $this->createServiceFork($service, $user, $categoriaFork, $position);
                    $serviceIds[] = $serviceFork->id;
                    $serviceCount++;
                }
            }

            return [
                'rubro' => $rubroFork->id,
                'categorias' => ['count' => $categoriaCount, 'ids' => $categoriaIds],
                'services' => ['count' => $serviceCount, 'ids' => $serviceIds],
            ];
        });
    }

    /**
     * Fork a categoria base: its fork + one fork per non-trashed service,
     * atomically (S7.3). The rubro context is NOT auto-forked: per the flujo,
     * a categoria may be forked standalone, so its fork has no rubro parent
     * (parent_fork_id stays null — linking happens via Update in Slice 4).
     *
     * @return array<string, mixed> tree summary (categoria id, service counts + ids)
     */
    public function forkCategoria(string $baseId, User $user): array
    {
        return DB::transaction(function () use ($baseId, $user) {
            $categoria = Categoria::findOrFail($baseId);

            $this->assertNoExistingFork('categoria', $baseId, $user);

            $categoriaFork = $this->createCategoriaFork($categoria, $user, null);

            $serviceIds = [];
            $serviceCount = 0;

            foreach ($categoria->services()->get() as $position => $service) {
                $serviceFork = $this->createServiceFork($service, $user, $categoriaFork, $position);
                $serviceIds[] = $serviceFork->id;
                $serviceCount++;
            }

            return [
                'categoria' => $categoriaFork->id,
                'services' => ['count' => $serviceCount, 'ids' => $serviceIds],
            ];
        });
    }

    /**
     * Fork a single service base. The fork is created with no parent: a
     * standalone service fork has nothing to attach to, and attachment
     * (parent_fork_id) happens via Update / parent change in Slice 4
     * (documented assumption).
     *
     * @return array<string, mixed> tree summary (service id)
     */
    public function forkService(string $baseId, User $user): array
    {
        return DB::transaction(function () use ($baseId, $user) {
            $service = Service::findOrFail($baseId);

            $this->assertNoExistingFork('service', $baseId, $user);

            $serviceFork = $this->createServiceFork($service, $user, null, 0);

            return ['service' => $serviceFork->id];
        });
    }

    /**
     * Create a categoria fork. sort_order is carried from the base `order`
     * column (the only ordering column among the three base models).
     */
    private function createCategoriaFork(Categoria $categoria, User $user, ?UserCatalogItem $rubroFork): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'categoria',
            'base_id' => $categoria->id,
            'parent_fork_id' => $rubroFork?->id,
            'overrides' => [],
            'status' => 'activo',
            'sort_order' => $categoria->order,
        ]);
    }

    /**
     * Create a service fork under its categoria fork. Services have no
     * ordering column, so sort_order is the positional index within the
     * categoria's copied services.
     */
    private function createServiceFork(Service $service, User $user, ?UserCatalogItem $categoriaFork, int $position): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => $service->id,
            'parent_fork_id' => $categoriaFork?->id,
            'overrides' => [],
            'status' => 'activo',
            'sort_order' => $position,
        ]);
    }

    /**
     * Reject a duplicate fork of the same base by the same user (R3, D-5).
     * The check runs INSIDE the transaction and only counts non-trashed rows
     * (the SoftDeletes scope excludes them), so a soft-deleted prior fork
     * does not block re-forking. Throws \DomainException — the simplest
     * idiomatic domain signal, no new exception class needed.
     */
    private function assertNoExistingFork(string $itemType, string $baseId, User $user): void
    {
        $exists = UserCatalogItem::query()
            ->where('user_id', $user->id)
            ->where('item_type', $itemType)
            ->where('base_id', $baseId)
            ->exists();

        if ($exists) {
            throw new DomainException("El usuario ya tiene un fork de este {$itemType}.");
        }
    }
}
