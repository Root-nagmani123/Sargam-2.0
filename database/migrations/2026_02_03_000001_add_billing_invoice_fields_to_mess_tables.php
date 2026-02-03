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
        // Update mess_invoices table if needed
        if (!Schema::hasColumn('mess_invoices', 'invoice_no')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->string('invoice_no')->unique()->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'buyer_id')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->bigInteger('buyer_id')->nullable()->after('vendor_id');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'bill_no')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->text('bill_no')->nullable()->after('buyer_id');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'total_amount')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('amount');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'payment_type')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->string('payment_type')->default('cash')->after('total_amount');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'is_deleted')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->after('status');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'created_by')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->bigInteger('created_by')->nullable()->after('is_deleted');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'approved_by')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->bigInteger('approved_by')->nullable()->after('created_by');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'approved_at')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }

        if (!Schema::hasColumn('mess_invoices', 'remarks')) {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->text('remarks')->nullable()->after('approved_at');
            });
        }

        // Create mess_kitchen_issues table if it doesn't exist
        if (!Schema::hasTable('mess_kitchen_issues')) {
            Schema::create('mess_kitchen_issues', function (Blueprint $table) {
                $table->id();
                $table->string('bill_no')->unique();
                $table->bigInteger('buyer_id')->nullable();
                $table->string('buyer_name')->nullable();
                $table->string('guest_name')->nullable();
                $table->date('issue_date');
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('client_type')->default('employee'); // employee, guest, other
                $table->string('section')->nullable();
                $table->string('programme_name')->nullable();
                $table->string('payment_type')->default('cash');
                $table->string('status')->default('pending');
                $table->boolean('is_deleted')->default(false);
                $table->bigInteger('created_by')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('issue_date');
                $table->index('client_type');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns from mess_invoices
        Schema::table('mess_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('mess_invoices', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('mess_invoices', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('mess_invoices', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('mess_invoices', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('mess_invoices', 'is_deleted')) {
                $table->dropColumn('is_deleted');
            }
            if (Schema::hasColumn('mess_invoices', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
            if (Schema::hasColumn('mess_invoices', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('mess_invoices', 'bill_no')) {
                $table->dropColumn('bill_no');
            }
            if (Schema::hasColumn('mess_invoices', 'buyer_id')) {
                $table->dropColumn('buyer_id');
            }
            if (Schema::hasColumn('mess_invoices', 'invoice_no')) {
                $table->dropColumn('invoice_no');
            }
        });

        // Drop mess_kitchen_issues table
        Schema::dropIfExists('mess_kitchen_issues');
    }
};
