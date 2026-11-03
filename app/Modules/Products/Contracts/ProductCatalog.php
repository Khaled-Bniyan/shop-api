<?php

namespace App\Modules\Products\Contracts;

interface ProductCatalog
{
    /**
     * @param  int[]  $ids
     * @return array<int, array{id:int, name:string, price:string}>  مفهرسة برقم المنتج
     */
    public function findMany(array $ids): array;
}
