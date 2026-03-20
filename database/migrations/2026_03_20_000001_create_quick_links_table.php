<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_links', function (Blueprint $table) {
            $table->id();
            $table->string('label', 255);
            $table->string('url', 2048);
            $table->boolean('target_blank')->default(true);
            $table->integer('position')->default(0);
            $table->boolean('active_inactive')->default(1);
            $table->timestamps();

            $table->index(['active_inactive', 'position']);
        });

        // Pre-populate with your current static items (so functionality remains).
        $now = now();
        DB::table('quick_links')->insert([
            [
                'label' => 'E-Office',
                'url' => 'https://eoffice.lbsnaa.gov.in/',
                'target_blank' => true,
                'position' => 1,
                'active_inactive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Medical Center',
                'url' => 'http://cghs.lbsnaa.gov.in/',
                'target_blank' => true,
                'position' => 2,
                'active_inactive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Library',
                'url' => 'https://idpbridge.myloft.xyz/simplesaml/module.php/core/loginuserpass?AuthState=_13df360546d97777e748e8ded7bf639c5c8c45d3d7%3Ahttps%3A%2F%2Fidpbridge.myloft.xyz%2Fsimplesaml%2Fmodule.php%2Fsaml%2Fidp%2FsingleSignOnService%3Fspentityid%3Dhttps%253A%252F%252Felibrarylbsnaa.myloft.xyz%26cookieTime%3D1688360911',
                'target_blank' => true,
                'position' => 3,
                'active_inactive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Photo Gallery',
                'url' => 'https://rcentre.lbsnaa.gov.in/web/',
                'target_blank' => true,
                'position' => 4,
                'active_inactive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_links');
    }
};

