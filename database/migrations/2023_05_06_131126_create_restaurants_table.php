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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->string('name_short');
            $table->string('email')->unique();
            $table->string('about', 3000);
            $table->string('about_short');
            $table->string('phone_no');
            $table->string('address');
            $table->string('city')->nullable();;
            $table->string('state')->nullable();;
            $table->string('postal_code')->nullable();
            $table->string('map_location');
            $table->string('url')->nullable();
            $table->string('logo')->nullable();
            $table->integer("status")->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
