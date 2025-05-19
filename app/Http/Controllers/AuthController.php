<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function syncAuth0User(Request $request)
    {
        // 1. Vérification du token Auth0
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Token Auth0 manquant'], 401);
        }
    
        $token = substr($authHeader, 7);
    
        try {
            // 2. Décodage et validation du token JWT
            $auth0Domain = 'dev-7aofz21k0iyppwar.us.auth0.com';
            $jwksUrl = "https://{$auth0Domain}/.well-known/jwks.json";
            $jwks = Http::get($jwksUrl)->json();
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));
    
            // 3. Extraction des claims importants
            $auth0Id = $decoded->sub;
            $email = $decoded->email ?? null;
            $name = $decoded->name ?? $decoded->nickname ?? 'Auth0 User';
    
            if (!$email) {
                return response()->json(['message' => 'Email manquant dans le token Auth0'], 400);
            }
    
            // 4. Synchronisation de l'utilisateur
            $user = User::updateOrCreate(
                ['auth0_id' => $auth0Id], // Recherche par auth0_id d'abord
                [
                    'email' => $email,
                    'name' => $name,
                    'password' => Hash::make(Str::random(32)) // Mot de passe aléatoire sécurisé
                ]
            );
    
            // 5. Authentification de l'utilisateur
            auth()->login($user);
    
            // 6. Création d'un token Sanctum pour les requêtes suivantes
            $sanctumToken = $user->createToken('auth0-sanctum-token')->plainTextToken;
    
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'auth0_id' => $user->auth0_id
                ],
                'token' => $sanctumToken,
                'auth_method' => 'auth0'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de la synchronisation Auth0',
                'error' => $e->getMessage()
            ], 401);
        }
    }
    // Inscription
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('YourAppName')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // Connexion
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            return response()->json(['token' => $user->createToken('YourAppName')->plainTextToken]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Déconnexion
    public function logout(Request $request)
    {
        $request->user()->tokens->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
