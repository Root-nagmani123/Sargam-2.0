<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master table holding the downloadable "sample / blank" form for each joining
 * document (keyed by the document field_name, e.g. doc_family_details).
 *
 * Purely additive: candidate upload/insert logic is untouched. The dynamic form
 * and admin preview read this table to show a "Sample Document" link, and the
 * "Sample Document Master" admin page does CRUD on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_joining_sample_documents')) {
            return;
        }

        Schema::create('fc_joining_sample_documents', function (Blueprint $table) {
            $table->id();
            $table->string('field_name', 100)->unique();          // doc field code, e.g. doc_family_details
            $table->string('document_title', 300)->nullable();    // display title (master page)
            $table->string('section', 200)->nullable();           // informational grouping label
            $table->string('sample_file_path', 500)->nullable();  // web path usable by asset()
            $table->string('sample_original_name', 300)->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_joining_sample_documents');
    }
};
