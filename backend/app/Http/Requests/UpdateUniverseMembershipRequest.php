<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UniverseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniverseMembershipRequest extends FormRequest
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
            'universe_type' => ['required', 'string', Rule::in(array_map(static fn (UniverseType $type): string => $type->value, UniverseType::cases()))],
            'is_active' => ['required', 'boolean'],
            'manual_reason' => ['nullable', 'string', 'max:800'],
        ];
    }
}

