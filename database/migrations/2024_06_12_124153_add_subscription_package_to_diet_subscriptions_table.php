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
        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('diet_subscription_package_id')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->dropColumn('diet_subscription_package_id');
        });
    }
};
