<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RubroSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CANONICAL_TIMESTAMP = '2026-08-29 00:00:00';

    public function run(): void
    {
        $rows = [
            [
                'id' => 'a2ccdb43-9e02-4afa-8a3d-cb98bf5970c2',
                'name' => 'Informática',
                'description' => 'Servicios de desarrollo, web, apps, mantenimiento',
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f1c-4913-99a3-07dd4b4075b8',
                'name' => 'Diseño',
                'description' => 'Identidad visual, UX/UI, branding',
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
            [
                'id' => 'a2ccdb43-9f20-4a7a-a13e-27784307697c',
                'name' => 'Consultoría',
                'description' => 'Arquitectura, revisiones, estrategia técnica',
                'status' => 'activo',
                'created_at' => self::CANONICAL_TIMESTAMP,
                'updated_at' => self::CANONICAL_TIMESTAMP,
                'deleted_at' => null,
            ],
        ];

        DB::transaction(function () use ($rows) {
            DB::table('rubros')->upsert(
                $rows,
                ['name'],
                ['description', 'status', 'updated_at', 'deleted_at']
            );
        });
    }
}
