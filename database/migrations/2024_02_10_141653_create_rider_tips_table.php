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
        Schema::create('rider_tips', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('rider_id')->references('id')->on('riders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('order_id')->nullable()->references('id')->on('orders')->onDelete('set null')->onUpdate('cascade');
            $table->string('transaction_id')->nullable();
            $table->integer('amount');
            $table->enum('status', ['pending', 'paid', 'withdrawn'])->default('paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_tips');
    }
};
