<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\CodeConfirmationStagiaire;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthStagiaireController extends Controller
{
    // Étape 1 : demande de code
    public function demanderCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $code = (string) random_int(100000, 999999);

        CodeConfirmationStagiaire::create([
            'email' => $request->email,
            'code' => $code,
            'date_expiration' => now()->addMinutes(10),
        ]);

        // Envoi réel par e-mail via Brevo
        try {
            Mail::to($request->email)->send(new VerificationCodeMail($code, $request->email));
            Log::info("Code OTP envoyé avec succès à {$request->email} (stagiaire)");
        } catch (\Exception $e) {
            Log::error("❌ ÉCHEC ENVOI EMAIL OTP STAGIAIRE : " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Un code de confirmation a été envoyé à votre adresse e-mail.',
        ]);
    }

    // Étape 2 : vérification du code + création du token
    public function verifierCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $codeValide = CodeConfirmationStagiaire::where('email', $request->email)
            ->where('code', $request->code)
            ->where('utilise', false)
            ->where('date_expiration', '>', now())
            ->latest('date_generation')
            ->first();

        if (!$codeValide) {
            return response()->json(['message' => 'Code invalide ou expiré'], 401);
        }

        $codeValide->update(['utilise' => true]);

        // Création automatique du compte si première connexion
        $stagiaire = Stagiaire::firstOrCreate(
            ['email' => $request->email],
            ['nom' => '', 'prenom' => '', 'date_premiere_connexion' => now()]
        );
        $stagiaire->update(['derniere_connexion' => now()]);

        $token = $stagiaire->createToken('auth-stagiaire')->plainTextToken;

        return response()->json(['access_token' => $token]);
    }
}
