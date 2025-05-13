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
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8' // Champ optionnel
        ]);

        $userData = [
            'name' => $validated['name'] ?? 'Auth0 User',
            'password' => isset($validated['password'])
                ? Hash::make($validated['password'])
                : Hash::make(Str::random(32)) // Mot de passe aléatoire si non fourni
        ];

        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            $userData
        );

        return response()->json([
            'user_id' => $user->id,
            'wasRecentlyCreated' => $user->wasRecentlyCreated,
            'auth_method' => isset($validated['password']) ? 'standard' : 'auth0'
        ]);
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
