<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Rubro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ServiceSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CANONICAL_TIMESTAMP = '2026-08-29 00:00:00';

    public function run(): void
    {
        $rubros = Rubro::query()->pluck('id', 'name');
        $categorias = Categoria::query()
            ->with('rubro')
            ->get()
            ->keyBy(fn (Categoria $categoria) => "{$categoria->rubro->name}|{$categoria->name}");

        $resolve = function (string $rubroName, string $categoriaName) use ($rubros, $categorias): Categoria {
            $key = "{$rubroName}|{$categoriaName}";

            return $categorias->get($key) ?? Categoria::query()
                ->where('rubro_id', $rubros->get($rubroName))
                ->where('name', $categoriaName)
                ->firstOrFail();
        };

        $rows = [
            [
                'id' => 'a2ccdb43-9f2d-4ec0-b215-180c41b70e93',
                'categoria_id' => $resolve('Informática', 'Sitios web y presencia digital')->id,
                'title' => 'Actualizar portafolio web',
                'description' => $this->generateDescription('Actualizar portafolio web'),
                'value' => 300000,
                'tags' => json_encode(['frontend', 'fullstack']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f2f-4b94-a917-18b1eb02ab53',
                'categoria_id' => $resolve('Informática', 'Sitios web y presencia digital')->id,
                'title' => 'Landing page nueva',
                'description' => $this->generateDescription('Landing page nueva'),
                'value' => 450000,
                'tags' => json_encode(['frontend']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f30-4e9d-9b5b-ccc22806332b',
                'categoria_id' => $resolve('Informática', 'Sitios web y presencia digital')->id,
                'title' => 'E-commerce básico',
                'description' => $this->generateDescription('E-commerce básico'),
                'value' => 800000,
                'tags' => json_encode(['frontend', 'backend', 'fullstack']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f32-4b30-aae6-7e6093b714ae',
                'categoria_id' => $resolve('Informática', 'Aplicaciones a medida')->id,
                'title' => 'App móvil React Native',
                'description' => $this->generateDescription('App móvil React Native'),
                'value' => 1200000,
                'tags' => json_encode(['mobile', 'frontend']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f33-4c96-8a3a-3046bdd9aa4e',
                'categoria_id' => $resolve('Informática', 'Aplicaciones a medida')->id,
                'title' => 'Dashboard administrativo',
                'description' => $this->generateDescription('Dashboard administrativo'),
                'value' => 900000,
                'tags' => json_encode(['frontend', 'backend', 'fullstack']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f35-446e-b9d9-b2ed74bd312f',
                'categoria_id' => $resolve('Informática', 'Mantenimiento y soporte')->id,
                'title' => 'Retención mensual mantenimiento',
                'description' => $this->generateDescription('Retención mensual mantenimiento'),
                'value' => 200000,
                'tags' => json_encode(['devops']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f36-4ded-af6e-726241f5f6a9',
                'categoria_id' => $resolve('Diseño', 'Identidad visual')->id,
                'title' => 'Logo + brand guide',
                'description' => $this->generateDescription('Logo + brand guide'),
                'value' => 400000,
                'tags' => json_encode(['frontend']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f37-470a-b349-6a59f699ae60',
                'categoria_id' => $resolve('Diseño', 'Identidad visual')->id,
                'title' => 'Rediseño marca',
                'description' => $this->generateDescription('Rediseño marca'),
                'value' => 600000,
                'tags' => json_encode(['frontend']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f39-4d9d-bd2f-f47baeaaee7c',
                'categoria_id' => $resolve('Diseño', 'UX/UI')->id,
                'title' => 'Auditoría usabilidad',
                'description' => $this->generateDescription('Auditoría usabilidad'),
                'value' => 350000,
                'tags' => json_encode(['frontend']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f3a-4156-9836-a81e8f895d40',
                'categoria_id' => $resolve('Diseño', 'UX/UI')->id,
                'title' => 'Prototipo navegable',
                'description' => $this->generateDescription('Prototipo navegable'),
                'value' => 500000,
                'tags' => json_encode(['frontend', 'fullstack']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f3c-4531-a5e9-e79c04ed063c',
                'categoria_id' => $resolve('Consultoría', 'Arquitectura y estrategia')->id,
                'title' => 'Definición arquitectura técnica',
                'description' => $this->generateDescription('Definición arquitectura técnica'),
                'value' => 500000,
                'tags' => json_encode(['backend', 'devops']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f3d-4298-b964-29f032454729',
                'categoria_id' => $resolve('Consultoría', 'Arquitectura y estrategia')->id,
                'title' => 'Revisión código y deuda técnica',
                'description' => $this->generateDescription('Revisión código y deuda técnica'),
                'value' => 400000,
                'tags' => json_encode(['backend', 'fullstack']),
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
        ];

        DB::transaction(function () use ($rows) {
            DB::table('services')->upsert(
                $rows,
                ['categoria_id', 'title'],
                ['description', 'value', 'tags', 'status', 'updated_at', 'deleted_at']
            );
        });
    }

    private function generateDescription(string $title): string
    {
        return "Servicio profesional: {$title}. Valor referencial, precio final sujeto a conversación según requerimientos.";
    }
}
