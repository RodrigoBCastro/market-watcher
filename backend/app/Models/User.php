<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(UserApiToken::class);
    }

    public function callReviews(): HasMany
    {
        return $this->hasMany(CallReview::class, 'reviewer_id');
    }

    /**
     * @return array{token: string, model: UserApiToken}
     */
    public function issueApiToken(string $name = 'api-token', ?\DateTimeInterface $expiresAt = null): array
    {
        $plainToken = Str::random(80);

        /** @var UserApiToken $tokenModel */
        $tokenModel = $this->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $plainToken,
            'model' => $tokenModel,
        ];
    }
}
