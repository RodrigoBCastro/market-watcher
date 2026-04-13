<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskSettingsRequest extends FormRequest
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
            'total_capital' => ['required', 'numeric', 'gt:0'],
            'risk_per_trade_percent' => ['required', 'numeric', 'gt:0', 'lte:10'],
            'max_portfolio_risk_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'max_open_positions' => ['required', 'integer', 'min:1', 'max:50'],
            'max_position_size_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'max_sector_exposure_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'max_correlated_positions' => ['required', 'integer', 'min:1', 'max:20'],
            'allow_pyramiding' => ['required', 'boolean'],
        ];
    }
}
