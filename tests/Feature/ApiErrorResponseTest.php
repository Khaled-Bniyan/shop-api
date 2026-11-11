<?php

namespace Tests\Feature;

use App\Modules\Products\Models\Category;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_error_uses_the_unified_format(): void
    {
        $this->getJson('/api/v1/products')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'يجب تسجيل الدخول',
                'errors' => null,
            ]);
    }

    public function test_validation_error_uses_the_unified_format(): void
    {
        $this->postJson('/api/v1/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'بيانات غير صالحة')
            ->assertJsonStructure([
                'message',
                'errors' => ['name', 'email', 'password'],
            ]);
    }

    public function test_authorization_and_not_found_errors_use_the_unified_format(): void
    {
        $customer = User::forceCreate([
            'name' => 'Customer',
            'email' => 'customer-errors@shop.test',
            'password' => 'password123',
            'role' => 'customer',
        ]);
        Category::create(['name' => 'Test']);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/products', [
                'name' => 'Phone',
                'price' => 100,
                'category_id' => 1,
            ])
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'غير مصرّح لك بتنفيذ هذه العملية',
                'errors' => null,
            ]);

        $this->actingAs($customer, 'api')
            ->getJson('/api/v1/orders/999999')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'المورد المطلوب غير موجود',
                'errors' => null,
            ]);
    }

    public function test_login_rate_limit_error_uses_the_unified_format(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'missing@shop.test',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/login', [
            'email' => 'missing@shop.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests()
            ->assertExactJson([
                'message' => 'محاولات كثيرة، حاول لاحقاً',
                'errors' => null,
            ]);
    }
}
