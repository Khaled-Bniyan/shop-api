<?php

namespace App\Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Http\Requests\StoreProductRequest;
use App\Modules\Products\Http\Resources\ProductResource;
use App\Modules\Products\Models\Product;
use App\Notifications\ProductCreatedNotification;
use App\Shared\Inventory\Contracts\InventoryGateway;
use App\Shared\Notifications\Contracts\CustomerRecipientProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    public function __construct(
        private CustomerRecipientProvider $recipients,
        private InventoryGateway $inventory,
    ) {}

    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->query('category_id'),
                fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->paginate(10);

        $this->attachAvailableQuantities($products->getCollection());

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load('category');
        $this->attachAvailableQuantities(collect([$product]));

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->safe()->except('image');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($data);

        Notification::send(
            $this->recipients->customers(),
            new ProductCreatedNotification($product->id, $product->name),
        );

        $product->setAttribute('available_quantity', 0);

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /** @param Collection<int, Product> $products */
    private function attachAvailableQuantities(Collection $products): void
    {
        $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $quantities = $this->inventory->availableQuantities($productIds);

        $products->each(function (Product $product) use ($quantities) {
            $product->setAttribute(
                'available_quantity',
                (int) ($quantities[$product->id] ?? 0),
            );
        });
    }
}
