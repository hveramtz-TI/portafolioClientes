<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $companies = [
            ['name' => 'Empresa Alpha SPA', 'rut' => '10101010-4', 'email' => 'contacto@empresaalpha.cl', 'phone' => '+56 2 2101 0101', 'website' => 'https://empresaalpha.cl'],
            ['name' => 'Beta Tecnología LTDA', 'rut' => '20202020-8', 'email' => 'contacto@betatecnologia.cl', 'phone' => '+56 2 2202 0202', 'website' => 'https://betatecnologia.cl'],
            ['name' => 'Gamma Consultores', 'rut' => '30303030-1', 'email' => 'contacto@gammaconsultores.cl', 'phone' => '+56 2 2303 0303', 'website' => 'https://gammaconsultores.cl'],
            ['name' => 'Delta Ingeniería SpA', 'rut' => '40404040-5', 'email' => 'contacto@deltaingenieria.cl', 'phone' => '+56 2 2404 0404', 'website' => 'https://deltaingenieria.cl'],
            ['name' => 'Épsilon Servicios', 'rut' => '50505050-2', 'email' => 'contacto@epsilonservicios.cl', 'phone' => '+56 2 2505 0505', 'website' => 'https://epsilonservicios.cl'],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
