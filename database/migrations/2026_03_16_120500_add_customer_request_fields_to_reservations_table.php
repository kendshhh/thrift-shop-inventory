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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('customer_request_type', 30)->nullable()->after('notes');
            $table->string('customer_request_status', 20)->nullable()->after('customer_request_type');
            $table->text('customer_request_reason')->nullable()->after('customer_request_status');
            $table->date('customer_requested_pickup_date')->nullable()->after('customer_request_reason');
            $table->string('customer_requested_pickup_slot')->nullable()->after('customer_requested_pickup_date');
            $table->timestamp('customer_requested_at')->nullable()->after('customer_requested_pickup_slot');
            $table->timestamp('customer_request_handled_at')->nullable()->after('customer_requested_at');
            $table->text('customer_request_admin_note')->nullable()->after('customer_request_handled_at');

            $table->index(['customer_request_status', 'customer_request_type'], 'reservations_customer_request_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_customer_request_idx');

            $table->dropColumn([
                'customer_request_type',
                'customer_request_status',
                'customer_request_reason',
                'customer_requested_pickup_date',
                'customer_requested_pickup_slot',
                'customer_requested_at',
                'customer_request_handled_at',
                'customer_request_admin_note',
            ]);
        });
    }
};
