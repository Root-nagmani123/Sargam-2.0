<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_registration_master')) {
            Schema::table('fc_registration_master', function (Blueprint $table) {
                if (! $this->indexExists('fc_registration_master', 'frm_course_registered_pk_idx')) {
                    $table->index(['course_master_pk', 'is_registered', 'pk'], 'frm_course_registered_pk_idx');
                }
                if (! $this->indexExists('fc_registration_master', 'frm_user_id_idx')) {
                    $table->index('user_id', 'frm_user_id_idx');
                }
                if (! $this->indexExists('fc_registration_master', 'frm_contact_no_idx')) {
                    $table->index('contact_no', 'frm_contact_no_idx');
                }
            });
        }

        if (Schema::hasTable('user_credentials')) {
            Schema::table('user_credentials', function (Blueprint $table) {
                if (! $this->indexExists('user_credentials', 'uc_mobile_no_idx')) {
                    $table->index('mobile_no', 'uc_mobile_no_idx');
                }
                if (! $this->indexExists('user_credentials', 'uc_email_id_idx')) {
                    $table->index('email_id', 'uc_email_id_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fc_registration_master')) {
            Schema::table('fc_registration_master', function (Blueprint $table) {
                foreach (['frm_course_registered_pk_idx', 'frm_user_id_idx', 'frm_contact_no_idx'] as $index) {
                    if ($this->indexExists('fc_registration_master', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }

        if (Schema::hasTable('user_credentials')) {
            Schema::table('user_credentials', function (Blueprint $table) {
                foreach (['uc_mobile_no_idx', 'uc_email_id_idx'] as $index) {
                    if ($this->indexExists('user_credentials', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $result !== [];
    }
};
