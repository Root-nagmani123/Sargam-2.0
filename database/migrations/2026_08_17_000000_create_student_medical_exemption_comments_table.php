<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_medical_exemption_comments', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('student_medical_exemption_pk');
            $table->text('comment');
            $table->date('comment_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();

            $table->foreign('student_medical_exemption_pk', 'sme_comments_sme_pk_fk')
                ->references('pk')->on('student_medical_exemption')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_medical_exemption_comments');
    }
};
