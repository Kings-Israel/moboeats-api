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
            'email' => 'admin@moboeats.com',
            'password' => bcrypt('password'),
        ]);

        $user->addRole($admin);

        $user = User::where('email', 'k.king@moboeats.co.uk')->first();

        if ($user) {
            $password = Str::random(6);
            $user->update([
                'password' => bcrypt($password)
            ]);

            $user->roles()->detach([Role::where('name', 'orderer')->first()]);
            $user->roles()->detach([Role::where('name', 'restaurant')->first()]);
            $user->roles()->detach([Role::where('name', 'rider')->first()]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        } else {
            $password = Str::random(6);
            $user = User::create([
                'name' => 'Kennedy King',
                'email' => 'k.king@moboeats.co.uk',
                'password' => bcrypt($password),
            ]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        }

        $user->restaurants()->delete();
        $user->restaurant()->delete();
        $user->orderer()->delete();
        $user->rider()->delete();
        $user->bookmarks()->delete();
        $user->restaurantBookmarks()->delete();
        $user->cart()->delete();
        $user->orders()->delete();
        $user->stripePayments()->delete();
        $user->dietPlans()->delete();
        $user->dietsubscriptions()->delete();
        $user->deliveries()->delete();
        $user->reviews()->delete();
        $user->referralCode()->delete();

        // Mail::to($user->email)->send(new NewAccount($user, $password, 'admin'));

        $user = User::where('email', 'l.nnorom@moboeats.co.uk')->first();

        if ($user) {
            $password = Str::random(6);
            $user->update([
                'password' => $password
            ]);
            $user->roles()->detach([Role::where('name', 'orderer')->first()]);
            $user->roles()->detach([Role::where('name', 'restaurant')->first()]);
            $user->roles()->detach([Role::where('name', 'rider')->first()]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        } else {
            $password = Str::random(6);
            $user = User::create([
                'name' => 'Lord Nnorom',
                'email' => 'l.nnorom@moboeats.co.uk',
                'password' => bcrypt($password),
            ]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }
        }

        $user->restaurants()->delete();
        $user->restaurant()->delete();
        $user->orderer()->delete();
        $user->rider()->delete();
        $user->bookmarks()->delete();
        $user->restaurantBookmarks()->delete();
        $user->cart()->delete();
        $user->orders()->delete();
        $user->stripePayments()->delete();
        $user->dietPlans()->delete();
        $user->dietsubscriptions()->delete();
        $user->deliveries()->delete();
        $user->reviews()->delete();
        $user->referralCode()->delete();

        // Mail::to($user->email)->send(new NewAccount($user, $password, 'admin'));

        $user = User::where('email', 'k.milimo@moboeats.co.uk')->first();

        if ($user) {
            $user->roles()->detach([Role::where('name', 'orderer')->first()]);
            $user->roles()->detach([Role::where('name', 'restaurant')->first()]);
            $user->roles()->detach([Role::where('name', 'rider')->first()]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        } else {
            $user = User::create([
                'name' => 'Kings Israel',
                'email' => 'k.milimo@moboeats.co.uk',
                'password' => 'password',
            ]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        }

        // Mail::to($user->email)->send(new NewAccount($user, $password, 'admin'));
        // Mail::to('milimokings@gmail.com')->send(new NewAccount($user, $password, 'admin'));

        $user = User::where('email', 'j.mbugua@moboeats.co.uk')->first();

        if ($user) {
            $password = Str::random(6);
            $user->update([
                'password' => $password
            ]);
            $user->roles()->detach([Role::where('name', 'orderer')->first()]);
            $user->roles()->detach([Role::where('name', 'restaurant')->first()]);
            $user->roles()->detach([Role::where('name', 'rider')->first()]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        } else {
            $password = Str::random(6);
            $user = User::create([
                'name' => 'John Mbugua',
                'email' => 'j.mbugua@moboeats.co.uk',
                'password' => bcrypt($password),
            ]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        }

        $user->restaurants()->delete();
        $user->restaurant()->delete();
        $user->orderer()->delete();
        $user->rider()->delete();
        $user->bookmarks()->delete();
        $user->restaurantBookmarks()->delete();
        $user->cart()->delete();
        $user->orders()->delete();
        $user->stripePayments()->delete();
        $user->dietPlans()->delete();
        $user->dietsubscriptions()->delete();
        $user->deliveries()->delete();
        $user->reviews()->delete();
        $user->referralCode()->delete();

        // Mail::to($user->email)->send(new NewAccount($user, $password, 'admin'));

        $user = User::where('email', 'l.atieno@moboeats.co.uk')->first();

        if ($user) {
            $password = Str::random(6);
            $user->update([
                'password' => bcrypt($password)
            ]);
            $user->roles()->detach([Role::where('name', 'orderer')->first()]);
            $user->roles()->detach([Role::where('name', 'restaurant')->first()]);
            $user->roles()->detach([Role::where('name', 'rider')->first()]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        } else {
            $password = Str::random(6);
            $user = User::create([
                'name' => 'Linet Ruth Atieno',
                'email' => 'l.atieno@moboeats.co.uk',
                'password' => bcrypt($password),
            ]);

            if (!$user->hasRole('admin')) {
                $user->addRole($admin);
            }

        }

        $user->restaurants()->delete();
        $user->restaurant()->delete();
        $user->orderer()->delete();
        $user->rider()->delete();
        $user->bookmarks()->delete();
        $user->restaurantBookmarks()->delete();
        $user->cart()->delete();
        $user->orders()->delete();
        $user->stripePayments()->delete();
        $user->dietPlans()->delete();
        $user->dietsubscriptions()->delete();
        $user->deliveries()->delete();
        $user->reviews()->delete();
        $user->referralCode()->delete();

        // Mail::to($user->email)->send(new NewAccount($user, $password, 'admin'));
    }
}
