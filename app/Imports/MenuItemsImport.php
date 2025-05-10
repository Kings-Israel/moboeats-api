<?php

namespace App\Imports;

use App\Models\FoodCommonCategory;
use App\Models\Menu;
use App\Models\MenuImage;
use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\WithEvents;

class MenuItemsImport implements ToCollection, WithHeadingRow, WithMapping, WithEvents
{
    public $data = 0;
    public $total_rows = 0;

    public function map($row): array
    {
        if(!array_key_exists('titlename', $row) ||
            !array_key_exists('price', $row) ||
            !array_key_exists('description', $row) ||
            !array_key_exists('categoriescomma_separated', $row) ||
            !array_key_exists('activeinactive', $row) ||
            !array_key_exists('brancheswhere_this_item_will_be_served_separated_by_commas', $row) ||
            !array_key_exists('image_link', $row))
        {
            throw ValidationException::withMessages(['Invalid headers or missing column. Download and use the sample template.']);
        }

        return [
            'title' => $row['titlename'],
            'price' => $row['price'],
            'description' => $row['description'],
            'categories' => $row['categoriescomma_separated'],
            'active' => $row['activeinactive'],
            'branches' => $row['brancheswhere_this_item_will_be_served_separated_by_commas'],
            'image_link' => $row['image_link'],
        ];
    }

    public function registerEvents(): array
    {
        return [
        BeforeImport::class => function (BeforeImport $event) {
            $this->total_rows = $event->getReader()->getTotalRows();
        }
        ];
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $new_item) {
            // Get Branches
            $branches = explode(',', $new_item['branches']);

            // If Branch is not available, return error for the row
            $user_branches = Restaurant::where('user_id', auth()->id())->pluck('name');

            $branch_exists = collect($user_branches)->diff(collect($branches))->isEmpty();

            if ($branch_exists) {
                $existing_branches = Restaurant::where('user_id', auth()->id())->pluck('id');

                // Get Categories
                $categories = explode(',', $new_item['categories']);

                // If category is not available, create
                foreach ($categories as $new_category) {
                    $existing_category = FoodCommonCategory::where('title', 'LIKE', '%' . $new_category . '%')
                        ->where(function ($q) use ($existing_branches){
                            $q->whereIn('restaurant_id', $existing_branches)->orWhere('restaurant_id', NULL);
                        })
                        ->first();

                    if (!$existing_category) {
                        foreach ($existing_branches as $existing_branch) {
                            FoodCommonCategory::create([
                                'title' => $new_category,
                                'description' => $new_category,
                                'status' => 1,
                                'created_by' => auth()->user()->email,
                                'updated_by' => auth()->user()->email,
                                'restaurant_id' => $existing_branch
                            ]);
                        }
                    }
                }

                foreach ($branches as $branch) {
                    $branch_details = Restaurant::where('name', $branch)->select('id')->first();

                    $menu_item = Menu::create([
                        'title' => $new_item['title'],
                        'description' => $new_item['description'],
                        'restaurant_id' => $branch_details->id,
                        'status' => strtolower($new_item['active']) == 'active' ? 2 : 1,
                        'created_by' => auth()->user()->email,
                        'updated_by' => auth()->user()->email,
                    ]);

                    // Create categories
                    foreach ($categories as $menu_category) {
                        $saved_category = FoodCommonCategory::where('title', $menu_category)->first();
                        if ($saved_category) {
                            $menu_item->categories()->attach($saved_category->id, [
                                'uuid' => Str::uuid(),
                                'created_by' => auth()->user()->email,
                            ]);
                        }
                    }

                    // Save Images Links
                    MenuImage::create([
                        'menu_id' => $menu_item->id,
                        'image_url' => $new_item['image_link'],
                        'sequence' => 1,
                        'status' => 2,
                        'created_by' => auth()->user()->email,
                    ]);
                }

                $this->data++;
            } else {
                // Create error log table
            }
        }
    }
}
