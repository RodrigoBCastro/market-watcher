<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
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
            'ticker' => ['required', 'string', 'max:12', 'unique:monitored_assets,ticker'],
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:120'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ticker' => strtoupper((string) $this->input('ticker')),
        ]);
    }
}
