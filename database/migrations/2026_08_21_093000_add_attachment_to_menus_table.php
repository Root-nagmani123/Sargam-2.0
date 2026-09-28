<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * menus.attachment — a file a menu carries, mirroring useful_links.file_path.
 *
 * Guarded on both sides: the live schema of this app has drifted from its
 * migration history more than once, so a bare add/drop here can fail on an
 * environment where the column already exists (or never did). Both directions
 * check first and no-op rather than throwing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus') || Schema::hasColumn('menus', 'attachment')) {
            return;
        }

        Schema::table('menus', function (Blueprint $table) {
            // Path relative to the `public` disk, e.g. "menu-attachments/xyz.pdf".
            // Nullable: almost every menu is a route, not a document.
            $table->string('attachment', 255)->nullable()->after('route');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasColumn('menus', 'attachment')) {
            return;
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
};
