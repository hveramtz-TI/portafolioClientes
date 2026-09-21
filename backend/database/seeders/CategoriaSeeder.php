<?php

namespace Database\Seeders;

use App\Models\Rubro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CategoriaSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CANONICAL_TIMESTAMP = '2026-08-29 00:00:00';

    public function run(): void
    {
        $informatica = Rubro::query()->where('name', 'Informática')->firstOrFail();
        $diseno = Rubro::query()->where('name', 'Diseño')->firstOrFail();
        $consultoria = Rubro::query()->where('name', 'Consultoría')->firstOrFail();

        $rows = [
            [
                'id' => 'a2ccdb43-9f22-4400-adbc-ec65343b5723',
                'rubro_id' => $informatica->id,
                'name' => 'Sitios web y presencia digital',
                'description' => 'Webs, landings, e-commerce',
                'order' => 1,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f23-4a32-9573-a7b656b4c02d',
                'rubro_id' => $informatica->id,
                'name' => 'Aplicaciones a medida',
                'description' => 'Apps móviles, dashboards, sistemas',
                'order' => 2,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f25-4812-8bb7-46de38400c43',
                'rubro_id' => $informatica->id,
                'name' => 'Mantenimiento y soporte',
                'description' => 'Retenciones, soporte continuo',
                'order' => 3,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f27-4dc5-8bcc-cd461280e092',
                'rubro_id' => $diseno->id,
                'name' => 'Identidad visual',
                'description' => 'Logo, brand guide, rebranding',
                'order' => 1,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f28-4d48-9582-c0a6c06a78b5',
                'rubro_id' => $diseno->id,
                'name' => 'UX/UI',
                'description' => 'Auditoría, prototipos, investigación',
                'order' => 2,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f2a-4a4f-962c-e05d9370e036',
                'rubro_id' => $consultoria->id,
                'name' => 'Arquitectura y estrategia',
                'description' => 'Arquitectura técnica, revisiones código',
                'order' => 1,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f2c-4b14-b1c0-b882e3c7723f',
                'rubro_id' => $consultoria->id,
                'name' => 'X',
                'description' => 'Placeholder sin servicios, solo para completar el set base del MVP',
                'order' => 2,
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
        ];

        DB::transaction(function () use ($rows) {
            DB::table('categorias')->upsert(
                $rows,
                ['rubro_id', 'name'],
                ['description', 'order', 'status', 'updated_at', 'deleted_at']
            );
        });
    }
}
