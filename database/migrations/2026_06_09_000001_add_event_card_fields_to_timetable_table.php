<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional presentation fields used by the printable / downloadable Event Card.
     * All columns are nullable so existing timetable rows and the academic
     * scheduling workflow remain completely unaffected.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable', 'event_banner')) {
                $table->string('event_banner', 500)->nullable()->comment('Event card banner/image path or URL');
            }
            if (!Schema::hasColumn('timetable', 'event_category')) {
                $table->string('event_category', 191)->nullable()->comment('Event card category label');
            }
            if (!Schema::hasColumn('timetable', 'organizer')) {
                $table->string('organizer', 255)->nullable()->comment('Event card organizer / department');
            }
            if (!Schema::hasColumn('timetable', 'contact_info')) {
                $table->string('contact_info', 255)->nullable()->comment('Event card contact information');
            }
            if (!Schema::hasColumn('timetable', 'qr_code_data')) {
                $table->string('qr_code_data', 500)->nullable()->comment('Text/URL encoded into the event card QR code');
            }
            if (!Schema::hasColumn('timetable', 'event_description')) {
                $table->text('event_description')->nullable()->comment('Long-form event card description');
            }
            if (!Schema::hasColumn('timetable', 'custom_fields')) {
                $table->text('custom_fields')->nullable()->comment('JSON of custom label/value pairs for the event card');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('timetable', function (Blueprint $table) {
            foreach ([
                'event_banner',
                'event_category',
                'organizer',
                'contact_info',
                'qr_code_data',
                'event_description',
                'custom_fields',
            ] as $column) {
                if (Schema::hasColumn('timetable', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
