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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('value');
            $table->json('tags')->nullable();
            $table->string('status')->default('activo'); // 'activo' | 'desactivado'
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['categoria_id', 'title'], 'services_categoria_id_title_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE services ADD CONSTRAINT services_value_check CHECK (value >= 0)");
            DB::statement("ALTER TABLE services ADD CONSTRAINT services_status_check CHECK (status IN ('activo', 'desactivado'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
