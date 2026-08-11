<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('issue_reports')) {
            return;
        }

        Schema::create('issue_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reported_by')->comment('user_credentials.user_id of the reporter');
            $table->unsignedBigInteger('menu_group_id')->nullable()->comment('menu_groups.id — the module picked');
            $table->string('module_name', 255)->comment('Denormalized menu_groups.name, survives menu edits');
            $table->string('sub_module', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('attachment', 500)->nullable()->comment('Path on the public disk');
            $table->string('page_url', 500)->nullable()->comment('Where the reporter was when they hit Report');
            $table->tinyInteger('status')->default(0)->comment('0=Open, 1=In Progress, 2=Resolved, 3=Closed');
            $table->timestamps();

            $table->index('reported_by');
            $table->index('menu_group_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('issue_reports');
    }
};
