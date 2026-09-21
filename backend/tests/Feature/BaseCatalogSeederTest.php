<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RubroSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BaseCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private const CANONICAL_TIMESTAMP = '2026-08-29 00:00:00';

    private const TAG_WHITELIST = ['frontend', 'backend', 'fullstack', 'devops', 'mobile'];

    /**
     * Run the catalog trio in canonical dependency order.
     */
    private function seedCatalog(): void
    {
        $this->seed([
            RubroSeeder::class,
            CategoriaSeeder::class,
            ServiceSeeder::class,
        ]);
    }

    public function test_catalog_has_canonical_counts_status_and_null_deletes(): void
    {
        $this->seedCatalog();

        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);

        $this->assertDatabaseHas('rubros', ['status' => 'activo', 'deleted_at' => null]);
        $this->assertDatabaseHas('categorias', ['status' => 'activo', 'deleted_at' => null]);
        $this->assertDatabaseHas('services', ['status' => 'activo', 'deleted_at' => null]);

        $this->assertEquals(
            [3, 7, 12],
            [
                Rubro::query()->whereNull('deleted_at')->count(),
                Categoria::query()->whereNull('deleted_at')->count(),
                Service::query()->whereNull('deleted_at')->count(),
            ]
        );

        $this->assertSame(0, Service::query()->whereHas('categoria', function ($query) {
            $query->where('name', 'X');
        })->count());
    }

    public function test_categories_have_canonical_parentage_and_order(): void
    {
        $this->seedCatalog();

        $informatica = Rubro::query()->where('name', 'Informática')->firstOrFail();
        $diseno = Rubro::query()->where('name', 'Diseño')->firstOrFail();
        $consultoria = Rubro::query()->where('name', 'Consultoría')->firstOrFail();

        $expected = [
            ['rubro_id' => $informatica->id, 'name' => 'Sitios web y presencia digital', 'order' => 1],
            ['rubro_id' => $informatica->id, 'name' => 'Aplicaciones a medida', 'order' => 2],
            ['rubro_id' => $informatica->id, 'name' => 'Mantenimiento y soporte', 'order' => 3],
            ['rubro_id' => $diseno->id, 'name' => 'Identidad visual', 'order' => 1],
            ['rubro_id' => $diseno->id, 'name' => 'UX/UI', 'order' => 2],
            ['rubro_id' => $consultoria->id, 'name' => 'Arquitectura y estrategia', 'order' => 1],
            ['rubro_id' => $consultoria->id, 'name' => 'X', 'order' => 2],
        ];

        foreach ($expected as $row) {
            $this->assertDatabaseHas('categorias', $row);
        }
    }

    public function test_services_have_exact_values_tags_and_descriptions(): void
    {
        $this->seedCatalog();

        $categoria = Categoria::query()->where('name', 'Sitios web y presencia digital')->firstOrFail();

        $expected = [
            ['categoria_id' => $categoria->id, 'title' => 'Actualizar portafolio web', 'value' => 300000, 'tags' => ['frontend', 'fullstack']],
            ['categoria_id' => $categoria->id, 'title' => 'Landing page nueva', 'value' => 450000, 'tags' => ['frontend']],
            ['categoria_id' => $categoria->id, 'title' => 'E-commerce básico', 'value' => 800000, 'tags' => ['frontend', 'backend', 'fullstack']],
        ];

        foreach ($expected as $row) {
            $this->assertDatabaseHas('services', [
                'categoria_id' => $row['categoria_id'],
                'title' => $row['title'],
                'value' => $row['value'],
                'status' => 'activo',
            ]);

            $service = Service::query()
                ->where('categoria_id', $row['categoria_id'])
                ->where('title', $row['title'])
                ->firstOrFail();

            $this->assertSame($row['tags'], $service->tags);
            $this->assertSame(
                "Servicio profesional: {$row['title']}. Valor referencial, precio final sujeto a conversación según requerimientos.",
                $service->description
            );

            foreach ($service->tags as $tag) {
                $this->assertContains($tag, self::TAG_WHITELIST);
            }
        }
    }

    public function test_service_lookup_uses_rubro_and_category_pair(): void
    {
        $this->seedCatalog();

        $informatica = Rubro::query()->where('name', 'Informática')->firstOrFail();

        Categoria::create([
            'rubro_id' => $informatica->id,
            'name' => 'Identidad visual',
            'order' => 99,
            'status' => 'activo',
        ]);

        $this->seed(ServiceSeeder::class);

        $disenoIdentidad = Categoria::query()
            ->whereHas('rubro', function ($query) {
                $query->where('name', 'Diseño');
            })
            ->where('name', 'Identidad visual')
            ->firstOrFail();

        $service = Service::query()
            ->where('title', 'Logo + brand guide')
            ->firstOrFail();

        $this->assertSame($disenoIdentidad->id, $service->categoria_id);
    }

    public function test_dependent_seeder_fails_before_writing_without_parent(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->seed(CategoriaSeeder::class);

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_service_seeder_fails_without_categories(): void
    {
        $this->seed(RubroSeeder::class);

        $this->expectException(ModelNotFoundException::class);
        $this->seed(ServiceSeeder::class);

        $this->assertDatabaseCount('services', 0);
    }

    public function test_second_seed_is_byte_equal_to_first_snapshot(): void
    {
        $this->seedCatalog();

        $first = $this->snapshotCatalog();

        $this->seedCatalog();

        $this->assertSame($first, $this->snapshotCatalog());

        $this->seedCatalog();

        $this->assertSame($first, $this->snapshotCatalog());
    }

    public function test_trashed_rows_are_revived_and_deactivation_converges(): void
    {
        $this->seedCatalog();

        Rubro::query()->where('name', 'Diseño')->firstOrFail()->delete();
        Categoria::query()->where('name', 'UX/UI')->firstOrFail()->delete();

        $service = Service::query()->where('title', 'E-commerce básico')->firstOrFail();
        $service->update(['status' => 'desactivado']);
        $service->delete();

        $this->seedCatalog();

        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);

        $this->assertDatabaseHas('rubros', ['name' => 'Diseño', 'deleted_at' => null, 'status' => 'activo']);
        $this->assertDatabaseHas('categorias', ['name' => 'UX/UI', 'deleted_at' => null, 'status' => 'activo']);
        $this->assertDatabaseHas('services', ['title' => 'E-commerce básico', 'deleted_at' => null, 'status' => 'activo']);

        $this->assertSame(
            1,
            Rubro::query()->where('name', 'Diseño')->whereNull('deleted_at')->count()
        );
        $this->assertSame(
            1,
            Categoria::query()->where('name', 'UX/UI')->whereNull('deleted_at')->count()
        );
        $this->assertSame(
            1,
            Service::query()->where('title', 'E-commerce básico')->whereNull('deleted_at')->count()
        );
    }

    public function test_canonical_renames_converge_on_fixed_ids_after_admin_edits(): void
    {
        $this->seedCatalog();

        Rubro::query()->where('name', 'Informática')->firstOrFail()->update(['name' => 'Informática editada']);
        Categoria::query()->where('name', 'Sitios web y presencia digital')->firstOrFail()->update(['name' => 'Sitios editados']);
        Service::query()->where('title', 'Actualizar portafolio web')->firstOrFail()->update(['title' => 'Portafolio editado']);

        $this->seedCatalog();

        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);
        $this->assertDatabaseHas('rubros', ['name' => 'Informática']);
        $this->assertDatabaseHas('categorias', ['name' => 'Sitios web y presencia digital']);
        $this->assertDatabaseHas('services', ['title' => 'Actualizar portafolio web']);
    }

    public function test_canonical_service_move_converges_on_fixed_id_after_admin_edit(): void
    {
        $this->seedCatalog();

        $service = Service::query()->where('title', 'Actualizar portafolio web')->firstOrFail();
        $targetCategory = Categoria::query()->where('name', 'Aplicaciones a medida')->firstOrFail();
        $service->update(['categoria_id' => $targetCategory->id]);

        $this->seedCatalog();

        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'categoria_id' => Categoria::query()->where('name', 'Sitios web y presencia digital')->firstOrFail()->id,
            'title' => 'Actualizar portafolio web',
        ]);
    }

    public function test_standalone_commands_match_explicit_seed_path(): void
    {
        $this->seedCatalog();
        $reference = $this->snapshotCatalog();

        DB::table('services')->delete();
        DB::table('categorias')->delete();
        DB::table('rubros')->delete();

        $this->artisan('db:seed', ['--class' => RubroSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => CategoriaSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => ServiceSeeder::class])->assertSuccessful();

        $this->assertSame($reference, $this->snapshotCatalog());

        $this->artisan('db:seed', ['--class' => RubroSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => CategoriaSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => ServiceSeeder::class])->assertSuccessful();

        $this->assertSame($reference, $this->snapshotCatalog());
    }

    public function test_catalog_seeders_emit_no_model_events(): void
    {
        $rubroEvents = 0;
        $categoriaEvents = 0;
        $serviceEvents = 0;

        Event::listen('eloquent.*: App\Models\Rubro', function () use (&$rubroEvents) {
            $rubroEvents++;
        });
        Event::listen('eloquent.*: App\Models\Categoria', function () use (&$categoriaEvents) {
            $categoriaEvents++;
        });
        Event::listen('eloquent.*: App\Models\Service', function () use (&$serviceEvents) {
            $serviceEvents++;
        });

        $this->seed(RubroSeeder::class);
        $this->seed(CategoriaSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->assertSame(0, $rubroEvents);
        $this->assertSame(0, $categoriaEvents);
        $this->assertSame(0, $serviceEvents);

        $this->seedCatalog();

        $this->assertSame(0, $rubroEvents);
        $this->assertSame(0, $categoriaEvents);
        $this->assertSame(0, $serviceEvents);
    }

    public function test_database_seeder_wires_catalog_before_company_and_client(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Users seeded inline before catalog.
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'user@example.com', 'role' => 'user']);

        // Catalog trio populated in dependency order before company/client.
        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);

        // Company/Client seeders ran after catalog wiring.
        $this->assertDatabaseCount('companies', 5);
        $this->assertDatabaseCount('clients', 10);

        $this->assertCanonicalColumnsPersisted();
    }

    public function test_seeded_rows_do_not_leak_between_tests(): void
    {
        $this->assertDatabaseCount('rubros', 0);
        $this->assertDatabaseCount('categorias', 0);
        $this->assertDatabaseCount('services', 0);

        $this->seedCatalog();

        $this->assertDatabaseCount('rubros', 3);
        $this->assertDatabaseCount('categorias', 7);
        $this->assertDatabaseCount('services', 12);
    }

    /**
     * Capture a stable, ordered snapshot of the three catalog tables.
     */
    private function snapshotCatalog(): array
    {
        return [
            'rubros' => Rubro::query()->orderBy('name')->get()->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'description' => $row->description,
                'status' => $row->status,
                'created_at' => $row->created_at->toDateTimeString(),
                'updated_at' => $row->updated_at->toDateTimeString(),
                'deleted_at' => $row->deleted_at,
            ])->toArray(),
            'categorias' => Categoria::query()->with('rubro')->orderBy('rubro_id')->orderBy('order')->get()->map(fn ($row) => [
                'id' => $row->id,
                'rubro_id' => $row->rubro_id,
                'rubro_name' => $row->rubro->name,
                'name' => $row->name,
                'description' => $row->description,
                'order' => $row->order,
                'status' => $row->status,
                'created_at' => $row->created_at->toDateTimeString(),
                'updated_at' => $row->updated_at->toDateTimeString(),
                'deleted_at' => $row->deleted_at,
            ])->toArray(),
            'services' => Service::query()->with('categoria')->orderBy('categoria_id')->orderBy('title')->get()->map(fn ($row) => [
                'id' => $row->id,
                'categoria_id' => $row->categoria_id,
                'categoria_name' => $row->categoria->name,
                'title' => $row->title,
                'description' => $row->description,
                'value' => $row->value,
                'tags' => $row->tags,
                'status' => $row->status,
                'created_at' => $row->created_at->toDateTimeString(),
                'updated_at' => $row->updated_at->toDateTimeString(),
                'deleted_at' => $row->deleted_at,
            ])->toArray(),
        ];
    }

    /**
     * Assert canonical columns are present after a full DatabaseSeeder run.
     */
    private function assertCanonicalColumnsPersisted(): void
    {
        $this->assertDatabaseHas('rubros', [
            'name' => 'Informática',
            'status' => 'activo',
        ]);

        $this->assertDatabaseHas('categorias', [
            'name' => 'Sitios web y presencia digital',
            'order' => 1,
            'status' => 'activo',
        ]);

        $this->assertDatabaseHas('services', [
            'title' => 'E-commerce básico',
            'value' => 800000,
            'status' => 'activo',
        ]);

        $service = Service::query()->where('title', 'E-commerce básico')->firstOrFail();
        $this->assertSame(['frontend', 'backend', 'fullstack'], $service->tags);
        $this->assertSame(
            'Servicio profesional: E-commerce básico. Valor referencial, precio final sujeto a conversación según requerimientos.',
            $service->description
        );
    }
}
