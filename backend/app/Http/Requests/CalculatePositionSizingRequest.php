<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePositionSizingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entry_price' => ['required', 'numeric', 'gt:0'],
            'stop_price' => ['nullable', 'numeric', 'gt:0'],
            'stop_distance_percent' => ['nullable', 'numeric', 'gt:0'],
            'capital_total' => ['nullable', 'numeric', 'gt:0'],
            'risk_per_trade_percent' => ['nullable', 'numeric', 'gt:0'],
            'available_capital' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
