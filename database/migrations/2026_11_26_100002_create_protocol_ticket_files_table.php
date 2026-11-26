<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('protocol_ticket_files')) {
            return;
        }

        if (! Schema::hasTable('protocol_requests')) {
            return;
        }

        Schema::create('protocol_ticket_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('protocol_request_id');
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->index(
                'protocol_request_id',
                'idx_protocol_ticket_files_request'
            );
            $table->foreign(
                'protocol_request_id',
                'fk_protocol_ticket_files_request'
            )->references('id')
                ->on('protocol_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_ticket_files');
    }
};
