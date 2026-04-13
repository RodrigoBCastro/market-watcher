<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioSimulationRequest extends FormRequest
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
            'capital_total' => ['nullable', 'numeric', 'gt:0'],
            'call_ids' => ['nullable', 'array'],
            'call_ids.*' => ['integer', 'exists:trade_calls,id'],
            'candidates' => ['nullable', 'array'],
            'candidates.*.ticker' => ['nullable', 'string', 'max:12'],
            'candidates.*.entry_price' => ['required_with:candidates', 'numeric', 'gt:0'],
            'candidates.*.stop_price' => ['nullable', 'numeric', 'gt:0'],
            'candidates.*.target_price' => ['nullable', 'numeric', 'gt:0'],
            'candidates.*.stop_distance_percent' => ['nullable', 'numeric', 'gt:0'],
            'candidates.*.reward_percent' => ['nullable', 'numeric'],
            'candidates.*.expectancy' => ['nullable', 'numeric'],
            'candidates.*.score' => ['nullable', 'numeric'],
            'candidates.*.sector' => ['nullable', 'string', 'max:120'],
        ];
    }
}
