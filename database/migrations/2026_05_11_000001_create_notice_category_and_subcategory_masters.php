<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_category_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('name', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamps();
        });

        Schema::create('notice_subcategory_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('notice_category_master_pk');
            $table->string('name', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamps();

            $table->foreign('notice_category_master_pk', 'notice_subcat_cat_fk')
                ->references('pk')
                ->on('notice_category_master')
                ->onDelete('cascade');
        });

        if (Schema::hasTable('notices_notification')) {
            Schema::table('notices_notification', function (Blueprint $table) {
                if (!Schema::hasColumn('notices_notification', 'notice_category_master_pk')) {
                    $table->unsignedBigInteger('notice_category_master_pk')->nullable()->after('notice_type');
                }
                if (!Schema::hasColumn('notices_notification', 'notice_subcategory_master_pk')) {
                    $table->unsignedBigInteger('notice_subcategory_master_pk')->nullable()->after('notice_category_master_pk');
                }
            });
        }

        $defaults = [
            ['name' => 'Course notice', 'sort_order' => 10],
            ['name' => 'Office order', 'sort_order' => 20],
            ['name' => 'Personal', 'sort_order' => 30],
            ['name' => 'Office notice', 'sort_order' => 40],
            ['name' => 'Service related', 'sort_order' => 50],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('notice_category_master')->where('name', $row['name'])->exists();
            if (!$exists) {
                DB::table('notice_category_master')->insert([
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'active_inactive' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('notices_notification') && Schema::hasColumn('notices_notification', 'notice_category_master_pk')) {
            foreach ($defaults as $row) {
                $pk = DB::table('notice_category_master')->where('name', $row['name'])->value('pk');
                if ($pk) {
                    DB::table('notices_notification')
                        ->whereNull('notice_category_master_pk')
                        ->where('notice_type', $row['name'])
                        ->update(['notice_category_master_pk' => $pk]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notices_notification')) {
            Schema::table('notices_notification', function (Blueprint $table) {
                if (Schema::hasColumn('notices_notification', 'notice_subcategory_master_pk')) {
                    $table->dropColumn('notice_subcategory_master_pk');
                }
                if (Schema::hasColumn('notices_notification', 'notice_category_master_pk')) {
                    $table->dropColumn('notice_category_master_pk');
                }
            });
        }

        Schema::dropIfExists('notice_subcategory_master');
        Schema::dropIfExists('notice_category_master');
    }
};
