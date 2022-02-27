<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name'=> 'AdminF AdminM AdminL',
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'user_type' => 'admin',
        ]);

        \App\Models\User::create([
            'name'=> 'EnforcernF EnforcernM EnforcernL',
            'username' => 'user1',
            'password' => Hash::make('user1'),
            'user_type' => 'enforcer',
        ]);
    }
}
