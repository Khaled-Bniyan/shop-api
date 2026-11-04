<?php

namespace App\Shared\Notifications\Contracts;

interface CustomerRecipientProvider
{
    /** @return iterable<object> */
    public function customers(): iterable;
}
