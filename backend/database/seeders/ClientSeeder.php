<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $clients = [
            ['name' => 'María', 'surname' => 'González', 'rut' => '11111111-1', 'email' => 'maria.gonzalez@example.com', 'phone' => '+56 9 1234 5678', 'status' => 'activo'],
            ['name' => 'Juan', 'surname' => 'Pérez', 'rut' => '22222222-2', 'email' => 'juan.perez@example.com', 'phone' => '+56 9 2345 6789', 'status' => 'activo'],
            ['name' => 'Ana', 'surname' => 'Rodríguez', 'rut' => '33333333-3', 'email' => 'ana.rodriguez@example.com', 'phone' => '+56 9 3456 7890', 'status' => 'activo'],
            ['name' => 'Pedro', 'surname' => 'Soto', 'rut' => '44444444-4', 'email' => 'pedro.soto@example.com', 'phone' => '+56 9 4567 8901', 'status' => 'activo'],
            ['name' => 'Carolina', 'surname' => 'Muñoz', 'rut' => '55555555-5', 'email' => 'carolina.munoz@example.com', 'phone' => '+56 9 5678 9012', 'status' => 'desactivado'],
            ['name' => 'José', 'surname' => 'Contreras', 'rut' => '66666666-6', 'email' => 'jose.contreras@example.com', 'phone' => '+56 9 6789 0123', 'status' => 'activo'],
            ['name' => 'Valentina', 'surname' => 'Rojas', 'rut' => '77777777-7', 'email' => 'valentina.rojas@example.com', 'phone' => '+56 9 7890 1234', 'status' => 'desactivado'],
            ['name' => 'Camila', 'surname' => 'Flores', 'rut' => '88888888-8', 'email' => 'camila.flores@example.com', 'phone' => '+56 9 8901 2345', 'status' => 'activo'],
            ['name' => 'Andrés', 'surname' => 'Araya', 'rut' => '99999999-9', 'email' => 'andres.araya@example.com', 'phone' => '+56 9 9012 3456', 'status' => 'activo'],
            ['name' => 'Francisca', 'surname' => 'Díaz', 'rut' => '12345678-5', 'email' => 'francisca.diaz@example.com', 'phone' => '+56 9 1234 0000', 'status' => 'activo'],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
