<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioPositionRequest extends FormRequest
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
            'monitored_asset_id' => ['required', 'integer', 'exists:monitored_assets,id'],
            'trade_call_id' => ['nullable', 'integer', 'exists:trade_calls,id'],
            'entry_date' => ['nullable', 'date'],
            'entry_price' => ['nullable', 'numeric', 'gt:0'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'invested_amount' => ['nullable', 'numeric', 'gt:0'],
            'stop_price' => ['nullable', 'numeric', 'gt:0'],
            'target_price' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
