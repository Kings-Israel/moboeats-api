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
        Schema::create('diet_subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('tag_line')->nullable();
            $table->text('description')->nullable();
            $table->double('price');
            $table->string('currency');
            $table->enum('duration', ['daily', 'weekly', 'monthly', 'quarterly', 'half annually', 'annually'])->default('monthly');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diet_subscription_packages');
    }
};
