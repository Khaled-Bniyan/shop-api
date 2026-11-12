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
        $clothing = Category::firstOrCreate(['name' => 'ملابس']);
        $books = Category::firstOrCreate(['name' => 'كتب']);
        $home = Category::firstOrCreate(['name' => 'المنزل']);
        $sports = Category::firstOrCreate(['name' => 'رياضة']);

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
        $this->seedProduct(101, [
            'category_id' => $home->id,
            'name' => 'مصباح مكتبي ذكي',
            'description' => 'مصباح LED قابل للتعديل بثلاث درجات إضاءة وتصميم عصري.',
            'price' => 49.90,
            'image_path' => 'assets/products/smart-desk-lamp.png',
        ]);
        $this->seedProduct(102, [
            'category_id' => $electronics->id,
            'name' => 'لوحة مفاتيح ميكانيكية',
            'description' => 'لوحة مفاتيح مريحة بإضاءة خلفية ومفاتيح هادئة.',
            'price' => 74.50,
        ]);
        $this->seedProduct(103, [
            'category_id' => $clothing->id,
            'name' => 'سترة قطنية',
            'description' => 'سترة يومية مريحة مصنوعة من القطن بقصة بسيطة.',
            'price' => 35.00,
        ]);
        $this->seedProduct(104, [
            'category_id' => $books->id,
            'name' => 'كتاب تطبيقات الويب الحديثة',
            'description' => 'مدخل عملي لبناء واجهات وخدمات ويب آمنة وقابلة للتوسع.',
            'price' => 22.00,
        ]);
        $this->seedProduct(105, [
            'category_id' => $sports->id,
            'name' => 'قارورة مياه رياضية',
            'description' => 'قارورة متينة من الستانلس ستيل تحافظ على برودة المشروب.',
            'price' => 18.75,
        ]);
        $this->seedProduct(106, [
            'category_id' => $clothing->id,
            'name' => 'حقيبة ظهر يومية',
            'description' => 'حقيبة خفيفة بجيوب منظمة ومكان مخصص للحاسوب المحمول.',
            'price' => 42.00,
        ]);
        $this->seedProduct(107, [
            'category_id' => $home->id,
            'name' => 'آلة تحضير القهوة',
            'description' => 'آلة قهوة صغيرة وسهلة الاستخدام مناسبة للمكتب والمنزل.',
            'price' => 95.00,
        ]);
        $this->seedProduct(108, [
            'category_id' => $electronics->id,
            'name' => 'ساعة ذكية',
            'description' => 'ساعة لمتابعة النشاط والتنبيهات مع بطارية تدوم عدة أيام.',
            'price' => 129.00,
        ]);
    }

    /** @param array{name:string, email:string, password:string, role:string} $attributes */
    private function seedUser(array $attributes): void
    {
        User::firstOrNew(['email' => $attributes['email']])
            ->forceFill($attributes)
            ->save();
    }

    /** @param array{category_id:int, name:string, description:string, price:float, image_path?:string|null} $attributes */
    private function seedProduct(int $id, array $attributes): void
    {
        $product = Product::find($id);

        if ($product === null) {
            Product::forceCreate(['id' => $id, ...$attributes]);

            return;
        }

        $product->forceFill($attributes)->save();
    }
}
