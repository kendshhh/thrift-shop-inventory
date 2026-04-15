<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('seller_name')->default('Unknown Seller')->after('description');
            $table->string('seller_contact_number', 50)->default('N/A')->after('seller_name');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn(['seller_name', 'seller_contact_number']);
        });
    }
};
