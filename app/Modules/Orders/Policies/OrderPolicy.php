<?php

namespace App\Modules\Orders\Policies;

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || (int) $order->user_id === (int) $user->id;
    }
}
