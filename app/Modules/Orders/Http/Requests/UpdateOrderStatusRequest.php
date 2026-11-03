<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Order::STATUSES)],
        ];
    }
}
