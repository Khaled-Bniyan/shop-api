<?php

namespace Tests\Feature;

use App\Modules\Products\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use App\Notifications\ProductCreatedNotification;
use App\Shared\Inventory\Contracts\InventoryGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class RequirementOneTest extends TestCase
{
    use RefreshDatabase;

    private int $userNumber = 0;

    private function makeUser(string $role = 'customer'): User
    {
        $this->userNumber++;

        return User::forceCreate([
            'name' => "Requirement User {$this->userNumber}",
            'email' => "requirement{$this->userNumber}@shop.test",
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function actingAsUser(User $user): static
    {
        $this->resetAuthentication();

        return $this->actingAs($user, 'api');
    }

    private function resetAuthentication(): void
    {
        $this->app['auth']->forgetGuards();
        JWTAuth::unsetToken();
    }

    public function test_customer_can_register_view_profile_and_logout(): void
    {
        $register = $this->postJson('/api/v1/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@shop.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

        $token = $register->json('access_token');

        $this->resetAuthentication();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'new-customer@shop.test')
            ->assertJsonPath('data.role', 'customer');

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->resetAuthentication();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_admin_can_create_and_filter_an_image_product_and_customers_are_notified(): void
    {
        Storage::fake('public');
        $inventory = new FakeInventoryGateway;
        $this->app->instance(InventoryGateway::class, $inventory);

        $admin = $this->makeUser('admin');
        $customer = $this->makeUser();
        $category = Category::create(['name' => 'Electronics']);

        $created = $this->actingAsUser($admin)
            ->post('/api/v1/products', [
                'name' => 'Camera',
                'description' => 'Compact camera',
                'price' => 125.50,
                'category_id' => $category->id,
                'image' => UploadedFile::fake()->image('camera.png'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Camera')
            ->assertJsonPath('data.category', 'Electronics');

        $product = Product::findOrFail($created->json('data.id'));
        $inventory->quantities[$product->id] = 8;
        Storage::disk('public')->assertExists($product->image_path);

        $this->actingAsUser($customer)
            ->getJson("/api/v1/products?category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.available_quantity', 8)
            ->assertJsonStructure(['links', 'meta']);

        $notification = $customer->notifications()->firstOrFail();

        $this->assertSame(ProductCreatedNotification::class, $notification->type);
        $this->assertSame($product->id, $notification->data['product_id']);

        $this->actingAsUser($customer)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.0.id', $notification->id)
            ->assertJsonPath('notifications.0.data.product_id', $product->id)
            ->assertJsonPath('notifications.0.read_at', null);

        $this->actingAsUser($customer)
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_order_total_is_calculated_server_side_and_duplicate_lines_are_combined(): void
    {
        $inventory = new FakeInventoryGateway;
        $this->app->instance(InventoryGateway::class, $inventory);

        $customer = $this->makeUser();
        $category = Category::create(['name' => 'Books']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Laravel Book',
            'price' => 12.50,
        ]);

        $response = $this->actingAsUser($customer)
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertSame(37.5, (float) $response->json('data.total'));

        $reservationId = $response->json('data.inventory_reservation_id');

        $this->assertSame([
            ['product_id' => $product->id, 'quantity' => 3],
        ], $inventory->reservations[$reservationId]);
    }

    public function test_security_headers_are_present_on_successful_and_error_responses(): void
    {
        $customer = $this->makeUser();

        $this->actingAsUser($customer)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $this->actingAsUser($customer)
            ->getJson('/api/v1/orders/999999')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }
}

class FakeInventoryGateway implements InventoryGateway
{
    /** @var array<int, int> */
    public array $quantities = [];

    /** @var array<string, array<int, array{product_id: int, quantity: int}>> */
    public array $reservations = [];

    /** @var string[] */
    public array $releases = [];

    public function availableQuantities(array $productIds): array
    {
        return array_intersect_key($this->quantities, array_flip($productIds));
    }

    public function reserve(string $reservationId, array $items): void
    {
        $this->reservations[$reservationId] = $items;
    }

    public function release(string $reservationId): void
    {
        $this->releases[] = $reservationId;
    }
}
