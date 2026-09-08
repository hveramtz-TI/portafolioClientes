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
        Schema::create('user_catalog_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_type'); // 'rubro' | 'categoria' | 'service'
            $table->uuid('base_id')->nullable();
            $table->uuid('parent_fork_id')->nullable();
            $table->jsonb('overrides')->default('{}');
            $table->string('status')->default('activo'); // 'activo' | 'desactivado'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'item_type', 'base_id'], 'user_catalog_items_user_type_base_index');
            $table->index(['user_id', 'parent_fork_id', 'item_type'], 'user_catalog_items_user_parent_type_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE user_catalog_items ADD CONSTRAINT user_catalog_items_parent_fork_id_foreign FOREIGN KEY (parent_fork_id) REFERENCES user_catalog_items (id) ON DELETE RESTRICT');
            DB::statement("ALTER TABLE user_catalog_items ADD CONSTRAINT user_catalog_items_item_type_check CHECK (item_type IN ('rubro', 'categoria', 'service'))");
            DB::statement("ALTER TABLE user_catalog_items ADD CONSTRAINT user_catalog_items_status_check CHECK (status IN ('activo', 'desactivado'))");
            DB::statement('CREATE INDEX user_catalog_items_base_id_index ON user_catalog_items (base_id) WHERE base_id IS NOT NULL');
            DB::statement('CREATE INDEX user_catalog_items_parent_fork_id_index ON user_catalog_items (parent_fork_id) WHERE parent_fork_id IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_catalog_items');
    }
};
