<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyRankingWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'technical_weight' => ['required', 'numeric', 'gt:0'],
            'expectancy_weight' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
