<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Autentica o usuário e retorna um token JWT válido por 15 minutos.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $key = $this->resolveAppKey();
        if ($key === null) {
            return response()->json(['message' => 'Chave de aplicação não configurada'], 500);
        }

        $issuedAt = now();
        $expiration = now()->addMinutes(15);

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->getKey(),
            'iat' => $issuedAt->timestamp,
            'exp' => $expiration->timestamp,
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return response()->json([
            'token' => $token,
            'expires_in' => $expiration->diffInSeconds($issuedAt),
        ]);
    }

    /**
     * Retorna o usuário autenticado a partir do token informado.
     */
    public function show(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json(['message' => 'Token não informado'], 400);
        }

        $key = $this->resolveAppKey();
        if ($key === null) {
            return response()->json(['message' => 'Chave de aplicação não configurada'], 500);
        }

        try {
            $payload = (array) JWT::decode($token, new Key($key, 'HS256'));
        } catch (ExpiredException $e) {
            return response()->json(['message' => 'Token expirado'], 401);
        } catch (SignatureInvalidException|BeforeValidException $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Não foi possível validar o token'], 400);
        }

        $userId = $payload['sub'] ?? null;

        if (! $userId) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $roles = $user->roles()->pluck('name');

        return response()->json([
            'user' => array_merge(
                $user->only(['id', 'name', 'email']),
                [
                    'roles' => $roles->toArray(),
                    'role' => $roles->first(),
                ],
            ),
        ]);
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (is_string($authorization) && Str::startsWith($authorization, 'Bearer ')) {
            $token = substr($authorization, 7);
            if ($token !== false && $token !== '') {
                return $token;
            }
        }

        $queryToken = $request->query('token');

        return is_string($queryToken) && $queryToken !== '' ? $queryToken : null;
    }

    private function resolveAppKey(): ?string
    {
        $appKey = config('app.key');

        if (! is_string($appKey) || $appKey === '') {
            return null;
        }

        if (Str::startsWith($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7)) ?: null;

            return $decoded ?: null;
        }

        return $appKey;
    }
}
