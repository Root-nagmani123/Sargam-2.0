<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * menus.is_container — "this menu only holds sub-menus".
 *
 * Makes the intent explicit instead of inferred. A menu is now required to point
 * at either a Url or an Attachment, and this flag is the one legitimate way to
 * say "neither, on purpose" — which is what a parent menu needs. Inferring it
 * from "has children" cannot work: a menu has no children at the moment it is
 * created.
 *
 * Guarded on both sides — the live schema of this app has drifted from its
 * migration history before, so a bare add/drop can fail on an environment where
 * the column already exists (or never did).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        if (! Schema::hasColumn('menus', 'is_container')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->boolean('is_container')->default(0)->after('attachment');
            });
        }

        // Back-fill the menus that are containers TODAY: they hold sub-menus and
        // have no destination of their own. Without this they would fail the new
        // validation the next time someone edited them.
        //
        // Idempotent: re-running only ever re-sets the same rows to 1.
        $parentIds = DB::table('menus')
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id')
            ->filter()
            ->all();

        if (! empty($parentIds)) {
            DB::table('menus')
                ->whereIn('id', $parentIds)
                ->where(fn ($q) => $q->whereNull('route')->orWhere('route', ''))
                ->whereNull('attachment')
                ->update(['is_container' => 1]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasColumn('menus', 'is_container')) {
            return;
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('is_container');
        });
    }
};
