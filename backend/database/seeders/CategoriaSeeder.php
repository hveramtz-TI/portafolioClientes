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

    private const RUBRO_IDS = [
        'informatica' => 'a2ccdb43-9e02-4afa-8a3d-cb98bf5970c2',
        'diseno' => 'a2ccdb43-9f1c-4913-99a3-07dd4b4075b8',
        'consultoria' => 'a2ccdb43-9f20-4a7a-a13e-27784307697c',
    ];

    public function run(): void
    {
        Rubro::query()->whereKey(self::RUBRO_IDS['informatica'])->firstOrFail();
        Rubro::query()->whereKey(self::RUBRO_IDS['diseno'])->firstOrFail();
        Rubro::query()->whereKey(self::RUBRO_IDS['consultoria'])->firstOrFail();

        $rows = [
            [
                'id' => 'a2ccdb43-9f22-4400-adbc-ec65343b5723',
                'rubro_id' => self::RUBRO_IDS['informatica'],
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
                'rubro_id' => self::RUBRO_IDS['informatica'],
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
                'rubro_id' => self::RUBRO_IDS['informatica'],
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
                'rubro_id' => self::RUBRO_IDS['diseno'],
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
                'rubro_id' => self::RUBRO_IDS['diseno'],
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
                'rubro_id' => self::RUBRO_IDS['consultoria'],
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
                'rubro_id' => self::RUBRO_IDS['consultoria'],
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
                ['id'],
                ['rubro_id', 'name', 'description', 'order', 'status', 'updated_at', 'deleted_at']
            );
        });
    }
}
