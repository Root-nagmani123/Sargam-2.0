<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtc_claim_attachments', function (Blueprint $table) {
            $table->string('attachment_type', 24)->default('supporting')->after('original_name');
        });
    }

    public function down(): void
    {
        Schema::table('gtc_claim_attachments', function (Blueprint $table) {
            $table->dropColumn('attachment_type');
        });
    }
};
