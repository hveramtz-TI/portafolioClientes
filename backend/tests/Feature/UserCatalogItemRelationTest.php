<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCatalogItemRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_fork_resolves_base_to_service(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        $fork = UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => $service->id,
        ]);

        $this->assertInstanceOf(Service::class, $fork->base);
        $this->assertTrue($fork->base->is($service));
    }

    public function test_rubro_fork_resolves_base_to_rubro(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Diseño']);

        $fork = UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'rubro',
            'base_id' => $rubro->id,
        ]);

        $this->assertInstanceOf(Rubro::class, $fork->base);
        $this->assertTrue($fork->base->is($rubro));
    }

    public function test_categoria_fork_resolves_base_to_categoria(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Consultoría']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Estrategia']);

        $fork = UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'categoria',
            'base_id' => $categoria->id,
        ]);

        $this->assertInstanceOf(Categoria::class, $fork->base);
        $this->assertTrue($fork->base->is($categoria));
    }

    public function test_personal_item_with_null_base_id_resolves_to_null(): void
    {
        $user = User::factory()->create();

        $fork = UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => null,
        ]);

        $this->assertNull($fork->base);
    }
}
