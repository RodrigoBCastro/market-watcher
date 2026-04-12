<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UserApiToken;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->where('email', $payload['email'])->first();

        if ($user === null || ! Hash::check((string) $payload['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 422);
        }

        $tokenName = (string) ($payload['token_name'] ?? 'api-session');
        $ttlDays = (int) config('market.auth.token_ttl_days', 30);

        $issued = $user->issueApiToken(
            name: $tokenName,
            expiresAt: CarbonImmutable::now()->addDays($ttlDays),
        );

        return response()->json([
            'token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['model']->expires_at?->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var UserApiToken|null $currentToken */
        $currentToken = $request->attributes->get('current_api_token');

        if ($currentToken !== null) {
            $currentToken->delete();
        }

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }
}
