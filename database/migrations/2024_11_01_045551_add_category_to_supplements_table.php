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
        Schema::table('supplements', function (Blueprint $table) {
            $table->foreignId('supplement_category_id')->nullable()->after('id')->references('id')->on('supplement_categories')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplements', function (Blueprint $table) {
            $table->dropColumn('supplement_category_id');
        });
    }
};
