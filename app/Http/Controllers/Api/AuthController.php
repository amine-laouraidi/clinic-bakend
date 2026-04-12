<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class AuthController extends Controller
{
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
            'message' => 'Registration successful. Please verify your email.',
        ], 201);
    }
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check hash is valid
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Lien de vérification invalide.'], 400);
        }

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 200);
        }

        // Mark as verified
        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email vérifié avec succès! Vous pouvez maintenant vous connecter.'], 200);
    }
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 400);
        }

        SendVerificationEmailJob::dispatch($request->user());

        return response()->json(['message' => 'Email de vérification renvoyé.'], 200);
    }
    public function login() {}
    public function refresh() {}
    public function logout() {}
    public function logoutAll() {}
    public function me() {}
}
