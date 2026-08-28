<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('rut');
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('activo'); // 'activo' | 'desactivado'
            $table->timestamps();
            $table->unique(['rut', 'company_id'], 'clients_rut_company_id_unique');
        });

        DB::statement('CREATE UNIQUE INDEX clients_rut_without_company_unique ON clients (rut) WHERE company_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
