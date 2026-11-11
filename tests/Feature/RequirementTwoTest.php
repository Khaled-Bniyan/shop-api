<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Products\Contracts\ProductCatalog;
use App\Modules\Products\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use App\Shared\Inventory\Contracts\InventoryGateway;
use App\Shared\Inventory\Exceptions\InventoryReservationRejectedException;
use App\Shared\Inventory\Exceptions\InventoryServiceUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequirementTwoTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'customer'): User
    {
        return User::forceCreate([
            'name' => ucfirst($role),
            'email' => $role.'-requirement-two@shop.test',
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'Inventory']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Stocked Product',
            'price' => 25,
        ]);
    }

    public function test_product_list_and_details_include_available_quantity(): void
    {
        $customer = $this->makeUser();
        $product = $this->makeProduct();
        $inventory = new RequirementTwoInventoryGateway;
        $inventory->quantities[$product->id] = 7;
        $this->app->instance(InventoryGateway::class, $inventory);

        $this->actingAs($customer, 'api')
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.available_quantity', 7);

        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.available_quantity', 7);
    }

    public function test_insufficient_stock_rejects_order_with_clear_422_response(): void
    {
        $customer = $this->makeUser();
        $product = $this->makeProduct();
        $inventory = new RequirementTwoInventoryGateway;
        $inventory->reserveFailure = new InventoryReservationRejectedException(
            'Insufficient stock for product '.$product->id,
        );
        $this->app->instance(InventoryGateway::class, $inventory);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 2]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.inventory.0', 'Insufficient stock for product '.$product->id);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unavailable_inventory_returns_clear_503_response(): void
    {
        $customer = $this->makeUser();
        $product = $this->makeProduct();
        $inventory = new RequirementTwoInventoryGateway;
        $inventory->reserveFailure = new InventoryServiceUnavailableException('Timed out');
        $this->app->instance(InventoryGateway::class, $inventory);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertServiceUnavailable()
            ->assertJsonStructure(['message', 'errors']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_reserved_stock_is_released_when_saving_order_fails(): void
    {
        $customer = $this->makeUser();
        $inventory = new RequirementTwoInventoryGateway;
        $this->app->instance(InventoryGateway::class, $inventory);
        $this->app->instance(ProductCatalog::class, new BrokenRequirementTwoCatalog);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'items' => [['product_id' => 123, 'quantity' => 1]],
            ])
            ->assertInternalServerError();

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(1, $inventory->releases);
    }

    public function test_cancelling_order_releases_its_reservation(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser('admin');
        $inventory = new RequirementTwoInventoryGateway;
        $this->app->instance(InventoryGateway::class, $inventory);
        $order = Order::create([
            'user_id' => $customer->id,
            'status' => Order::PENDING,
            'inventory_reservation_id' => '00000000-0000-4000-8000-000000000001',
            'total' => 10,
        ]);

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => Order::CANCELLED])
            ->assertOk();

        $this->assertSame([$order->inventory_reservation_id], $inventory->releases);
    }
}

class RequirementTwoInventoryGateway implements InventoryGateway
{
    /** @var array<int, int> */
    public array $quantities = [];

    /** @var string[] */
    public array $releases = [];

    public ?\Throwable $reserveFailure = null;

    public function availableQuantities(array $productIds): array
    {
        return array_intersect_key($this->quantities, array_flip($productIds));
    }

    public function reserve(string $reservationId, array $items): void
    {
        if ($this->reserveFailure !== null) {
            throw $this->reserveFailure;
        }
    }

    public function release(string $reservationId): void
    {
        $this->releases[] = $reservationId;
    }
}

class BrokenRequirementTwoCatalog implements ProductCatalog
{
    public function findMany(array $ids): array
    {
        $id = (int) $ids[0];

        return [
            $id => [
                'id' => $id,
                'name' => 'Broken Product',
                'price' => 'not-a-number',
            ],
        ];
    }
}
