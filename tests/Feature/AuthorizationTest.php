<?php

namespace Tests\Feature;

use App\Modules\Products\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $n = 0;

    private function makeUser(string $role = 'customer'): User
    {
        $this->n++;

        return User::forceCreate([
            'name' => "User {$this->n}",
            'email' => "user{$this->n}@shop.test",
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function actingAsToken(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken(JWTAuth::fromUser($user));
    }

    private function makeOrder(User $owner): Order
    {
        return Order::create([
            'user_id' => $owner->id,
            'status' => Order::PENDING,
            'total' => 0,
        ]);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $order = $this->makeOrder($a);

        $this->actingAsToken($b)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(403)
            ->assertJson(['message' => 'غير مصرّح لك بتنفيذ هذه العملية']);
    }

    public function test_owner_can_view_own_order(): void
    {
        $a = $this->makeUser();
        $order = $this->makeOrder($a);

        $this->actingAsToken($a)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_admin_can_view_any_order(): void
    {
        $a = $this->makeUser();
        $admin = $this->makeUser('admin');
        $order = $this->makeOrder($a);

        $this->actingAsToken($admin)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk();
    }

    public function test_customer_orders_list_contains_only_own_orders(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $mine = $this->makeOrder($a);
        $this->makeOrder($b);

        $this->actingAsToken($a)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_customer_cannot_change_order_status(): void
    {
        $a = $this->makeUser();
        $order = $this->makeOrder($a);

        $this->actingAsToken($a)
            ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertStatus(403);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::PENDING,
        ]);
    }

    public function test_only_admin_can_change_status_and_customer_gets_notification(): void
    {
        $a = $this->makeUser();
        $admin = $this->makeUser('admin');
        $order = $this->makeOrder($a);

        $this->actingAsToken($admin)
            ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_customer_cannot_create_product(): void
    {
        $a = $this->makeUser();
        $category = Category::create(['name' => 'Test']);

        $this->actingAsToken($a)
            ->postJson('/api/v1/products', [
                'name' => 'Phone',
                'price' => 100,
                'category_id' => $category->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('products', 0);
    }
}