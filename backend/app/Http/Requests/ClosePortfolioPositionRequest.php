<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClosePortfolioPositionRequest extends FormRequest
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
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'exit_date' => ['nullable', 'date'],
            'exit_price' => ['nullable', 'numeric', 'gt:0'],
            'exit_reason' => ['nullable', 'string', 'in:target,stop,manual,timeout,rebalance'],
        ];
    }
}
