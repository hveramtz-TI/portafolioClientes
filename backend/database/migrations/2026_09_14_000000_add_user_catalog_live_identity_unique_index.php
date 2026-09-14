<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX = 'user_catalog_items_live_identity_unique';

    /**
     * Amended D5: enforce fork identity (user_id + item_type + base_id) at the
     * database level with a partial unique index that only covers live forks
     * (base_id present, not soft-deleted). Personal items and soft-deleted
     * rows stay outside the index, so re-forking after a cascade delete stays
     * legal. Raw DDL because the predicate is not portable through the
     * Blueprint builder; both SQLite and PostgreSQL support partial indexes.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        DB::statement(
            'CREATE UNIQUE INDEX '.self::INDEX
            .' ON user_catalog_items (user_id, item_type, base_id)'
            .' WHERE base_id IS NOT NULL AND deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
    }
};
