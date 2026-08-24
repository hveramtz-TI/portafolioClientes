<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('rut')->unique();
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            // Sin foreign key: la relación con companies se agrega en Fase 2.
            $table->uuid('company_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('activo'); // 'activo' | 'desactivado'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
