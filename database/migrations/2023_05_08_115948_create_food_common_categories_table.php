<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_common_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('description');
            $table->integer("status")->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('food_common_categories')->insert(
            [
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Menu Sections',
                    'description' => 'such as appetizers, entrees, sides, desserts, and beverages.',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Meal Type',
                    'description' => 'such as breakfast, lunch, dinner, or brunch.',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Cuisine',
                    'description' => 'such as Italian, Mexican, or Chinese, may categorize their menu items by cuisine.',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Dietary Restrictions',
                    'description' => 'such as vegetarian, vegan, gluten-free, or dairy-free',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Pricing',
                    'description' => 'such as value menu items, mid-range items, and high-end or premium items.',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => 'Seasonal or Promotional',
                    'description' => 'such as holiday specials or limited-time menu items',
                    'status' => 2,
                    'created_by' => config('app.company.COMPANY_EMAIL'),
                    'updated_by' => config('app.company.COMPANY_EMAIL'),
                ], 
            ]
        );
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_common_categories');
    }
};
