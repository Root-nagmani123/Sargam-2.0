<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the structured data entered through a fillable joining-document form
 * (e.g. "Details of Family" — Form No. 3). One row per candidate per document.
 *
 * The entered data is kept as JSON so the candidate can re-open and edit it.
 * The generated PDF path is mirrored into the document field's target_column
 * (fc_joining_documents_user_uploads) so the existing checklist View/Status
 * logic works without any change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_joining_document_forms')) {
            return;
        }

        Schema::create('fc_joining_document_forms', function (Blueprint $table) {
            $table->id();
            // FC user identifiers can be negative — use signed integers.
            $table->bigInteger('user_id')->index();
            $table->bigInteger('form_id')->nullable()->index();
            $table->bigInteger('step_id')->nullable();
            $table->string('field_name', 100);          // the doc_* field this form fills
            $table->string('template_key', 100);         // e.g. family_details
            $table->longText('form_data')->nullable();   // JSON of entered values
            $table->string('pdf_path', 255)->nullable(); // generated PDF (also written to target_column)
            $table->timestamps();

            $table->unique(['user_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_joining_document_forms');
    }
};
