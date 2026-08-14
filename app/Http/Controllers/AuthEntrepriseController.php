<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\CodeConfirmationEntreprise;
use Illuminate\Http\Request;

class AuthEntrepriseController extends Controller
{
    // Étape 1 : demande de code (le compte entreprise doit déjà exister)
    public function demanderCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $entreprise = Entreprise::where('email', $request->email)->first();
        if (!$entreprise) {
            return response()->json(['message' => 'Aucun compte entreprise associé à cet e-mail'], 401);
        }

        $code = (string) random_int(100000, 999999);

        CodeConfirmationEntreprise::create([
            'entreprise_id' => $entreprise->id,
            'code' => $code,
            'date_expiration' => now()->addMinutes(10),
        ]);

        \Log::info("[DEV] Code généré pour {$request->email} (entreprise) : {$code}");

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

        $entreprise = Entreprise::where('email', $request->email)->first();
        if (!$entreprise) {
            return response()->json(['message' => 'Code invalide ou expiré'], 401);
        }

        $codeValide = CodeConfirmationEntreprise::where('entreprise_id', $entreprise->id)
            ->where('code', $request->code)
            ->where('utilise', false)
            ->where('date_expiration', '>', now())
            ->latest('date_generation')
            ->first();

        if (!$codeValide) {
            return response()->json(['message' => 'Code invalide ou expiré'], 401);
        }

        $codeValide->update(['utilise' => true]);
        $entreprise->update(['derniere_connexion' => now()]);

        $token = $entreprise->createToken('auth-entreprise')->plainTextToken;

        return response()->json(['access_token' => $token]);
    }
}
