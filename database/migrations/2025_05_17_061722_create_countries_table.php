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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('iso2', 2);
            $table->char('iso3', 3);
            $table->char('numeric_code', 3)->nullable();
            $table->string('phone_code', 15)->nullable();
            $table->string('region')->nullable();
            $table->string('subregion')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->bigInteger('population')->nullable();
            $table->bigInteger('area_km2')->nullable();
            $table->enum('status', ['active', 'inactive'])->nullable()->default('inactive');
            $table->timestamps();

            // Indexes
            $table->unique('iso2');
            $table->unique('iso3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
