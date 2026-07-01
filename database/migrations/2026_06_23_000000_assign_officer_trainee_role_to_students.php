<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill: every user_credentials row with user_category = 'S' (Officer Trainee /
 * student) must always carry the "Officer Trainee" role. This migration assigns
 * that role to all existing 'S' users that don't already have it. New 'S' users
 * are handled at creation time (User model "created" event + StudentImport) and as
 * a safety net at login.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'Officer Trainee')->value('id');
        if (! $roleId) {
            // Role not present in this environment; nothing to backfill.
            return;
        }

        $modelType = 'App\\Models\\User';

        // Insert (role_id, model_type, model_id) for every 'S' user missing it.
        DB::table('user_credentials as uc')
            ->where('uc.user_category', 'S')
            ->whereNotExists(function ($q) use ($roleId, $modelType) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles as mhr')
                    ->whereColumn('mhr.model_id', 'uc.pk')
                    ->where('mhr.role_id', $roleId)
                    ->where('mhr.model_type', $modelType);
            })
            ->select('uc.pk')
            ->chunkById(500, function ($rows) use ($roleId, $modelType) {
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'role_id'    => $roleId,
                        'model_type' => $modelType,
                        'model_id'   => $row->pk,
                    ];
                }
                if ($insert) {
                    DB::table('model_has_roles')->insertOrIgnore($insert);
                }
            }, 'pk', 'uc');
    }

    public function down(): void
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'Officer Trainee')->value('id');
        if (! $roleId) {
            return;
        }

        $modelType = 'App\\Models\\User';

        // Remove the Officer Trainee role only from 'S' users (leave others intact).
        $studentPks = DB::table('user_credentials')
            ->where('user_category', 'S')
            ->pluck('pk');

        DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', $modelType)
            ->whereIn('model_id', $studentPks)
            ->delete();
    }
};
