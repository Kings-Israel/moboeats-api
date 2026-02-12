<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionGrouping;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                "name" => "assign riders to motorbikes",
                "display_name" => "Assign Riders to Motorbikes",
            ],
            [
                "name" => 'view motorbikes',
                "display_name" => "View Motorbikes",
            ],
            [
                "name" => 'add motorbikes',
                "display_name" => "Add Motorbikes",
            ],
            [
                "name" => 'update motorbikes',
                "display_name" => "Update Motorbikes",
            ],
        ];

        collect($permissions)->each(function ($permission) {
            Permission::firstOrCreate($permission);
        });

        $permissions = Permission::all();

        $new_role = Role::whereName('admin')->first();
        $new_role->syncPermissions($permissions);

        $new_role = Role::whereName('super')->first();
        $new_role->syncPermissions($permissions);

        $groups = [
            [
                'name' => 'motorbikes',
            ],
        ];

        collect($groups)->each(fn ($group) => PermissionGroup::firstOrCreate($group));

        $data = [
            'riders' => [
                'assign riders to motorbikes'
            ],
            'motorbikes' => [
                'view motorbikes',
                'add motorbikes',
                'update motorbikes',
            ]
        ];

        try {
            DB::beginTransaction();

            foreach ($data as $key => $value) {
                $permission_group = PermissionGroup::where('name', $key)->first()->id;
                foreach ($value as $permission) {
                    $permission = Permission::where('name', $permission)->first()->id;
                    PermissionGrouping::firstOrCreate([
                        'permission_group_id' => $permission_group,
                        'permission_id' => $permission
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }
}
