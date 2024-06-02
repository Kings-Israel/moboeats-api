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
        Schema::create('supplement_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('supplement_id')->references('id')->on('supplements')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('status', ['pending', 'paid', 'on delivery', 'delivered', 'cancelled', 'refunded'])->nullable()->default('pending');
            $table->bigInteger('quantity');
            $table->date('expected_delivery_date')->nullable();
            $table->text('courier_contact_name')->nullable();
            $table->text('courier_contact_email')->nullable();
            $table->text('courier_contact_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplement_orders');
    }
};
