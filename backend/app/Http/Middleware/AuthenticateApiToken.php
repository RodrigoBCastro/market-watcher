<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null || trim($plainToken) === '') {
            return new JsonResponse(['message' => 'Token de autenticação ausente.'], 401);
        }

        $tokenHash = hash('sha256', $plainToken);

        $token = UserApiToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->first();

        if ($token === null || $token->user === null) {
            return new JsonResponse(['message' => 'Token inválido.'], 401);
        }

        if ($token->isExpired()) {
            return new JsonResponse(['message' => 'Token expirado.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->attributes->set('current_api_token', $token);

        return $next($request);
    }
}
