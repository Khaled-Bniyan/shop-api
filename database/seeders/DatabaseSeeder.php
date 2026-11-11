<?php

namespace Database\Seeders;

use App\Modules\Products\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUser([
            'name' => 'Admin', 'email' => 'admin@shop.test',
            'password' => 'password123', 'role' => 'admin',
        ]);
        $this->seedUser([
            'name' => 'Customer A', 'email' => 'a@shop.test',
            'password' => 'password123', 'role' => 'customer',
        ]);
        $this->seedUser([
            'name' => 'Customer B', 'email' => 'b@shop.test',
            'password' => 'password123', 'role' => 'customer',
        ]);

        $electronics = Category::firstOrCreate(['name' => 'إلكترونيات']);
        Category::firstOrCreate(['name' => 'ملابس']);
        Category::firstOrCreate(['name' => 'كتب']);

        $this->seedProduct(1, [
            'category_id' => $electronics->id,
            'name' => 'حاسوب محمول',
            'description' => 'حاسوب عملي للدراسة والعمل اليومي.',
            'price' => 799.00,
        ]);
        $this->seedProduct(2, [
            'category_id' => $electronics->id,
            'name' => 'سماعات لاسلكية',
            'description' => 'سماعات خفيفة بصوت واضح واتصال لاسلكي.',
            'price' => 89.00,
        ]);
    }

    /** @param array{name:string, email:string, password:string, role:string} $attributes */
    private function seedUser(array $attributes): void
    {
        User::firstOrNew(['email' => $attributes['email']])
            ->forceFill($attributes)
            ->save();
    }

    /** @param array{category_id:int, name:string, description:string, price:float} $attributes */
    private function seedProduct(int $id, array $attributes): void
    {
        if (Product::find($id) === null) {
            Product::forceCreate(['id' => $id, ...$attributes]);
        }
    }
}
