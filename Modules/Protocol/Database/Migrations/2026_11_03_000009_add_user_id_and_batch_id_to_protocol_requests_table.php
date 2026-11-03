<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ProtocolRequest writes user_id (Auth::id()) and batch_id (protocol_request_batches.id)
 * and scopeForUser() filters on user_id, but the create migration never added either.
 * Guarded per column: staging already has them, created by hand.
 * Nullable so the change is safe on a table that already holds rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocol_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('protocol_requests', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('protocol_requests', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('requestable_type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('protocol_requests', function (Blueprint $table) {
            foreach (['batch_id', 'user_id'] as $column) {
                if (Schema::hasColumn('protocol_requests', $column)) {
                    $table->dropIndex([$column]);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
