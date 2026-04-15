<?php

declare(strict_types=1);

namespace App\Http\Requests;

class UpdateAssetMasterBlacklistRequest extends AdminActionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_blacklisted' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
