<?php

namespace Database\Seeders;

use App\Modules\Products\Models\Category;
use App\Modules\Users\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::forceCreate([
            'name' => 'Admin', 'email' => 'admin@shop.test',
            'password' => 'password123', 'role' => 'admin',
        ]);
        User::forceCreate([
            'name' => 'Customer A', 'email' => 'a@shop.test',
            'password' => 'password123', 'role' => 'customer',
        ]);
        User::forceCreate([
            'name' => 'Customer B', 'email' => 'b@shop.test',
            'password' => 'password123', 'role' => 'customer',
        ]);

        foreach (['إلكترونيات', 'ملابس', 'كتب'] as $name) {
            Category::create(['name' => $name]);
        }
    }
}
