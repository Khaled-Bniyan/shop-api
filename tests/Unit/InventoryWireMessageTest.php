<?php

namespace Tests\Unit;

use App\Shared\Inventory\Grpc\GetStockRequest;
use App\Shared\Inventory\Grpc\GetStockResponse;
use App\Shared\Inventory\Grpc\StockItem;
use PHPUnit\Framework\TestCase;

class InventoryWireMessageTest extends TestCase
{
    public function test_get_stock_request_encodes_repeated_product_ids(): void
    {
        $request = new GetStockRequest([1, 300]);

        $this->assertSame("\x08\x01\x08\xAC\x02", $request->serializeToString());
    }

    public function test_get_stock_response_decodes_all_stock_items(): void
    {
        $response = new GetStockResponse;
        $response->items = [
            new StockItem(1, 5),
            new StockItem(2, 0),
        ];

        $decoded = GetStockResponse::decode($response->serializeToString());

        $this->assertCount(2, $decoded->items);
        $this->assertSame(1, $decoded->items[0]->productId);
        $this->assertSame(5, $decoded->items[0]->availableQuantity);
        $this->assertSame(2, $decoded->items[1]->productId);
        $this->assertSame(0, $decoded->items[1]->availableQuantity);
    }
}
