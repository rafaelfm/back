<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json(['message' => 'Token não informado'], 401);
        }

        try {
            $claims = (array) JWT::decode($token, new Key($this->resolveAppKey(), 'HS256'));
        } catch (ExpiredException $e) {
            return response()->json(['message' => 'Sessão expirada. Faça login novamente.'], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $userId = $claims['sub'] ?? null;

        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        Auth::setUser($user);

        return $next($request);
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

    private function resolveAppKey(): string
    {
        $appKey = config('app.key');

        if (! is_string($appKey) || $appKey === '') {
            abort(500, 'Chave de aplicação não configurada.');
        }

        if (Str::startsWith($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7)) ?: null;

            if ($decoded === null) {
                abort(500, 'Chave de aplicação inválida.');
            }

            return $decoded;
        }

        return $appKey;
    }
}
