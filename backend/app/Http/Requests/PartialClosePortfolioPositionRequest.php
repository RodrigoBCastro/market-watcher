<?php

declare(strict_types=1);

namespace App\Http\Requests;

class PartialClosePortfolioPositionRequest extends ClosePortfolioPositionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
