<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const PENDING = 'pending';
    public const STATUSES = ['pending', 'processing', 'completed', 'cancelled'];

    protected $fillable = ['user_id', 'status', 'total'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
