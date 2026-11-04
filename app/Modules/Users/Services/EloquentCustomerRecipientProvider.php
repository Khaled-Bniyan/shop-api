<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Models\User;
use App\Shared\Notifications\Contracts\CustomerRecipientProvider;

class EloquentCustomerRecipientProvider implements CustomerRecipientProvider
{
    public function customers(): iterable
    {
        return User::query()
            ->where('role', 'customer')
            ->get();
    }
}
