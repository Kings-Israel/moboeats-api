<?php

use App\Models\Order;
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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Order::class, 'order_id');
            $table->bigInteger('orderable_id');
            $table->string('orderable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->dropColumn('orderable_id');
            $table->dropColumn('orderable_type');
        });
    }
};
