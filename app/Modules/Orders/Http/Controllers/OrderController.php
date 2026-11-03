<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::query()->with('items');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return OrderResource::collection($query->latest()->paginate(10));
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orders->place(
            $request->user()->id,
            $request->validated('items')
        );

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order); 

        return new OrderResource($order->load('items'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $order = $this->orders->changeStatus(
            $order,
            $request->validated('status')
        );

        return new OrderResource($order->load('items'));
    }
}
