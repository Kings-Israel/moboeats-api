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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('delivery')->default(false)->after('total_amount');
            $table->decimal('delivery_fee')->nullable()->before('status');
            $table->string('delivery_address')->nullable()->before('status');
            $table->string('delivery_status')->default('pending')->before('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery');
            $table->dropColumn('delivery_fee');
            $table->dropColumn('delivery_address');
            $table->dropColumn('delivery_status');
        });
    }
};
