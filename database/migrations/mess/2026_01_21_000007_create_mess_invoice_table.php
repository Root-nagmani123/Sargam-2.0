<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->date('invoice_date');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->timestamps();
            $table->foreign('vendor_id')->references('id')->on('mess_vendors')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('mess_invoices');
    }
};
