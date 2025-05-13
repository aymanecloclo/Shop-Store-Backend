<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class VerifyAuth0Token
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $auth0Domain = env('AUTH0_DOMAIN');
            $jwksUrl = "https://{$auth0Domain}/.well-known/jwks.json";
            $jwks = Http::get($jwksUrl)->json();
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            // Ici tu peux synchroniser l'utilisateur dans ta DB
            $email = $decoded->email ?? null;
            if (!$email) {
                return response()->json(['message' => 'Invalid token payload'], 401);
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $decoded->name ?? 'Auth0 User', 'password' => bcrypt(str()->random(32))]
            );

            // Auth::login() si tu veux authentifier dans Laravel
            auth()->setUser($user);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token', 'error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
