<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BootstrapDataUniverseRequest extends FormRequest
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
            'asset_types' => ['nullable', 'array'],
            'asset_types.*' => ['string', Rule::in(array_map(static fn (AssetType $type): string => $type->value, AssetType::cases()))],
            'sectors' => ['nullable', 'array'],
            'sectors.*' => ['string', 'max:120'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'market_cap_min' => ['nullable', 'numeric', 'min:0'],
            'volume_min' => ['nullable', 'numeric', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }
}

