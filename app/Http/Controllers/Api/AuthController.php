<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ─────────────────────────────────────────
    // Register
    // ─────────────────────────────────────────
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => $request->password,
        ]);

        $user->assignRole('patient');

        SendVerificationEmailJob::dispatch($user);

        return response()->json([
            'message' => 'Inscription réussie. Veuillez vérifier votre email.',
        ], 201);
    }

    // ─────────────────────────────────────────
    // Verify Email
    // ─────────────────────────────────────────
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Lien de vérification invalide.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email vérifié avec succès! Vous pouvez maintenant vous connecter.'], 200);
    }

    // ─────────────────────────────────────────
    // Resend Verification
    // ─────────────────────────────────────────
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 400);
        }

        SendVerificationEmailJob::dispatch($request->user());

        return response()->json(['message' => 'Email de vérification renvoyé.'], 200);
    }

    // ─────────────────────────────────────────
    // Login
    // ─────────────────────────────────────────
    public function login(LoginRequest $request)
    {
        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.',
            ], 401);
        }

        // Check if account is active
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // Check email verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Veuillez vérifier votre email avant de vous connecter.',
            ], 403);
        }

        // Delete token for this device only (if exists)
        $user->tokens()->where('name', $request->device_id)->delete();

        // Create new token for this device
        $token = $user->createToken($request->device_id)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'device_name'  => $request->device_name,
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->getRoleNames()->first(),
            ],
        ], 200);
    }

    // ─────────────────────────────────────────
    // Logout (current device)
    // ─────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ], 200);
    }

    // ─────────────────────────────────────────
    // Logout All Devices
    // ─────────────────────────────────────────
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnecté de tous les appareils.',
        ], 200);
    }

    // ─────────────────────────────────────────
    // Me (current user)
    // ─────────────────────────────────────────
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role'  => $user->getRoleNames()->first(),
        ], 200);
    }

    // ─────────────────────────────────────────
    // Active Sessions
    // ─────────────────────────────────────────
    public function sessions(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->get()
            ->map(fn($token) => [
                'id'          => $token->id,
                'device_id'   => $token->name,
                'last_active' => $token->last_used_at,
                'logged_in'   => $token->created_at,
            ]);

        return response()->json($tokens, 200);
    }

    // ─────────────────────────────────────────
    // Revoke Specific Device
    // ─────────────────────────────────────────
    public function revokeDevice(Request $request, $tokenId)
    {
        $deleted = $request->user()
            ->tokens()
            ->where('id', $tokenId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Appareil introuvable.'], 404);
        }

        return response()->json([
            'message' => 'Appareil déconnecté avec succès.',
        ], 200);
    }
}