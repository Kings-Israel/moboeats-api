<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Mail\NewAccount;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->first();

        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@java.com',
        ]);

        $user->addRole($admin);
    }
}
