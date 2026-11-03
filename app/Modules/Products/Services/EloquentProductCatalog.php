<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Contracts\ProductCatalog;
use App\Modules\Products\Models\Product;

class EloquentProductCatalog implements ProductCatalog
{
    public function findMany(array $ids): array
    {
        return Product::whereIn('id', $ids)
            ->get(['id', 'name', 'price'])
            ->mapWithKeys(fn ($p) => [
                $p->id => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                ],
            ])
            ->all();
    }
}
