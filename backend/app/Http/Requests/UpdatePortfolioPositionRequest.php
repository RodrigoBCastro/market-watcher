<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioPositionRequest extends FormRequest
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
            'stop_price' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'target_price' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'current_price' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'status' => ['sometimes', 'string', 'in:open,canceled'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
