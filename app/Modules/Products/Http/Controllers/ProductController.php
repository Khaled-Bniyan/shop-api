<?php

namespace App\Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Http\Requests\StoreProductRequest;
use App\Modules\Products\Http\Resources\ProductResource;
use App\Modules\Products\Models\Product;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\ProductCreatedNotification;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->query('category_id'),
                fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->safe()->except('image');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('products', 'public');
        }

        
        $product = Product::create($data);

        
        $users = User::where('role', 'user')->get();
        Notification::send($users, new ProductCreatedNotification($product));

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }
}