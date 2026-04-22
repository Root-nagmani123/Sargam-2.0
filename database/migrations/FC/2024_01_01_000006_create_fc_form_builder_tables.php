<?php

use App\Support\MigrationSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Steps definition (Step 1, Step 2, Step 3, Bank, Documents)
        MigrationSchema::createIfMissing('fc_form_steps', function (Blueprint $table) {
            $table->id();
            $table->string('step_name', 100);
            $table->string('step_slug', 50)->unique();
            $table->integer('step_number');
            $table->string('target_table', 100);
            $table->string('completion_column', 100)->nullable();
            $table->string('tracker_column', 100)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });

        // Fields within each step (flat, non-repeatable)
        MigrationSchema::createIfMissing('fc_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_id')->constrained('fc_form_steps')->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->string('label', 200);
            $table->string('field_type', 50)->default('text');
            $table->string('target_table', 100);
            $table->string('target_column', 100);
            $table->string('validation_rules', 500)->nullable();
            $table->tinyInteger('is_required')->default(0);
            $table->integer('display_order')->default(0);
            $table->string('placeholder', 200)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->string('default_value', 200)->nullable();
            $table->text('options_json')->nullable();
            $table->string('lookup_table', 100)->nullable();
            $table->string('lookup_value_column', 100)->nullable();
            $table->string('lookup_label_column', 100)->nullable();
            $table->string('lookup_order_column', 100)->nullable();
            $table->string('section_heading', 200)->nullable();
            $table->string('css_class', 100)->default('col-md-6');
            $table->integer('file_max_kb')->nullable();
            $table->string('file_extensions', 200)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Repeatable field groups (for Step 3 sub-tabs)
        MigrationSchema::createIfMissing('fc_form_field_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_id')->constrained('fc_form_steps')->cascadeOnDelete();
            $table->string('group_name', 100);
            $table->string('group_label', 200);
            $table->string('target_table', 100);
            $table->string('save_mode', 20)->default('replace_all');
            $table->integer('min_rows')->default(0);
            $table->integer('max_rows')->default(20);
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Fields within repeatable groups
        MigrationSchema::createIfMissing('fc_form_group_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('fc_form_field_groups')->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->string('label', 200);
            $table->string('field_type', 50)->default('text');
            $table->string('target_column', 100);
            $table->string('validation_rules', 500)->nullable();
            $table->tinyInteger('is_required')->default(0);
            $table->integer('display_order')->default(0);
            $table->string('placeholder', 200)->nullable();
            $table->text('options_json')->nullable();
            $table->string('lookup_table', 100)->nullable();
            $table->string('lookup_value_column', 100)->nullable();
            $table->string('lookup_label_column', 100)->nullable();
            $table->string('css_class', 100)->default('col-md-6');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_form_group_fields');
        Schema::dropIfExists('fc_form_field_groups');
        Schema::dropIfExists('fc_form_fields');
        Schema::dropIfExists('fc_form_steps');
    }
};
