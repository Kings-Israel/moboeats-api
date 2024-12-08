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
        Schema::create('orphanage_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orphanage_order_id')->references('id')->on('orphanage_orders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('menu_id')->references('id')->on('menus')->onDelete('cascade')->onUpdate('cascade');
            $table->double('quantity');
            $table->double('subtotal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orphanage_order_items');
    }
};
