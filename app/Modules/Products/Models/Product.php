<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'description', 'price', 'image_path',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
