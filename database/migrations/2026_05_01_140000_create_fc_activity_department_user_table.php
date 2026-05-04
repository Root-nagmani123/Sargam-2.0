<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_activity_department_user')) {
            return;
        }

        Schema::create('fc_activity_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fc_activity_department_id')
                ->constrained('fc_activity_department')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedBigInteger('user_credentials_pk')->comment('user_credentials.pk');
            $table->timestamps();

            $table->unique(['fc_activity_department_id', 'user_credentials_pk'], 'fc_act_dept_user_dept_uc_unique');
            $table->index('user_credentials_pk', 'fc_act_dept_user_uc_pk_idx');
        });

        if (Schema::hasTable('user_credentials')) {
            Schema::table('fc_activity_department_user', function (Blueprint $table) {
                $table->foreign('user_credentials_pk', 'fc_act_dept_user_uc_fk')
                    ->references('pk')
                    ->on('user_credentials')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_activity_department_user');
    }
};
