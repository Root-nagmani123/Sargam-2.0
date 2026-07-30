<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sec_visitor_names')) {
            return;
        }

        // sec_visitor_card_generated.pk is a signed INT on the live schema, so the
        // referencing column has to match it exactly. Declaring it as
        // unsignedBigInteger (as this migration originally did) makes MySQL reject
        // the constraint with errno 3780, "Referencing column ... and referenced
        // column ... are incompatible".
        $parentPk = Schema::getConnection()
            ->selectOne("SHOW COLUMNS FROM sec_visitor_card_generated WHERE Field = 'pk'");
        $parentType = strtolower($parentPk->Type ?? '');
        $parentIsUnsigned = str_contains($parentType, 'unsigned');
        $parentIsBig = str_starts_with($parentType, 'bigint');

        Schema::create('sec_visitor_names', function (Blueprint $table) use ($parentIsBig, $parentIsUnsigned) {
            $table->id('pk');

            if ($parentIsBig) {
                $parentIsUnsigned
                    ? $table->unsignedBigInteger('sec_visitor_card_generated_pk')
                    : $table->bigInteger('sec_visitor_card_generated_pk');
            } else {
                $parentIsUnsigned
                    ? $table->unsignedInteger('sec_visitor_card_generated_pk')
                    : $table->integer('sec_visitor_card_generated_pk');
            }

            $table->string('visitor_name', 255);
            $table->timestamp('created_date')->nullable();

            $table->index('sec_visitor_card_generated_pk', 'sec_visitor_names_card_idx');
            $table->foreign('sec_visitor_card_generated_pk')
                ->references('pk')->on('sec_visitor_card_generated')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_visitor_names');
    }
};
