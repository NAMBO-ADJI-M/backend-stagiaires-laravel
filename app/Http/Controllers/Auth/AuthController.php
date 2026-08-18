<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Stagiaire;
use App\Models\Entreprise;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * 1️⃣ DEMANDE DE CODE - CRÉATION AUTO SI COMPTE N'EXISTE PAS
     * Plus de mot de passe : email + rôle suffisent, un code est toujours envoyé,
     * que le compte soit nouveau ou déjà existant.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'required|in:stagiaire,entreprise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $role = $request->role;

        $user = User::where('email', $email)->first();
        $isNewAccount = false;

        // ✅ SI LE COMPTE N'EXISTE PAS → LE CRÉER
        if (!$user) {
            $isNewAccount = true;

            $user = User::create([
                'email' => $email,
                // Le mot de passe n'est plus utilisé pour l'authentification.
                // La colonne reste NOT NULL en base : on y met une valeur
                // aléatoire technique, jamais communiquée ni vérifiée.
                'password' => Hash::make(Str::random(40)),
                'role' => $role,
            ]);

            // Créer le profil selon le rôle
            if ($role === 'stagiaire') {
                Stagiaire::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => 'Utilisateur',
                    'prenom' => 'StageLink',
                    'profil_complet' => false,
                    'carnet_creer' => false,
                ]);
            } else {
                Entreprise::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'raison_sociale' => 'Mon Entreprise',
                    'profil_complet' => false,
                ]);
            }
        }

        // ✅ DANS TOUS LES CAS (nouveau compte ou compte existant) → ENVOYER UN CODE
        $code = $this->generateCode($user->email);
        $this->sendVerificationEmail($user->email, $code);

        return response()->json([
            'message' => $isNewAccount
                ? '✅ Compte créé avec succès ! Un code de vérification a été envoyé à votre email.'
                : '📧 Un code de vérification a été envoyé à votre email.',
            'data' => [
                'email' => $user->email,
                'role' => $user->role,
                'requires_verification' => true,
                'code_sent' => true,
                'code_expires_in' => 900,
                'is_new_account' => $isNewAccount,
            ]
        ], $isNewAccount ? 201 : 200);
    }

    /**
     * 2️⃣ VÉRIFICATION EMAIL
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier le code
        $verification = VerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification) {
            return response()->json([
                'message' => '❌ Code invalide ou expiré',
                'errors' => ['code' => ['Le code saisi est incorrect ou a expiré.']]
            ], 422);
        }

        // Marquer comme utilisé
        $verification->update(['used' => true]);

        // Activer le compte
        $user = User::where('email', $request->email)->first();
        $user->update([
            'email_verified_at' => Carbon::now(),
            'last_login_at' => Carbon::now(),
        ]);

        // Générer le token (reste valide jusqu'à déconnexion manuelle)
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => '✅ Email vérifié avec succès !',
            'data' => [
                'token' => $token,
                'user' => $user,
                'redirect' => $user->role === 'stagiaire'
                    ? '/stagiaire/dashboard'
                    : '/entreprise/dashboard',
            ]
        ]);
    }

    /**
     * 3️⃣ DÉCONNEXION
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '✅ Déconnexion réussie'
        ]);
    }

    /**
     * 4️⃣ SUPPRESSION DE COMPTE
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // Nettoyage des profils associés si nécessaire (ou laisser SoftDeletes gérer)
        if ($user->role === 'stagiaire') {
            $user->stagiaire()->delete();
        } else {
            $user->entreprise()->delete();
        }

        $user->tokens()->delete();
        $user->delete(); // Soft delete grâce au trait SoftDeletes dans le modèle User

        return response()->json([
            'message' => '✅ Compte supprimé avec succès.'
        ]);
    }

    /**
     * 5️⃣ RENVOYER LE CODE
     */
    public function resendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => '✅ Ce compte est déjà vérifié',
                'verified' => true,
            ], 422);
        }

        $code = $this->generateCode($user->email);
        $this->sendVerificationEmail($user->email, $code);

        return response()->json([
            'message' => '📧 Un nouveau code de vérification a été envoyé à votre email',
            'data' => [
                'email' => $user->email,
                'expires_in' => '15 minutes',
            ]
        ]);
    }

    /**
     * 5️⃣ PROFIL UTILISATEUR
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $profile = $this->getProfileStatus($user);

        return response()->json([
            'user' => $user,
            'profile_status' => $profile,
            'profile_data' => $user->role === 'stagiaire'
                ? $user->stagiaire
                : $user->entreprise,
            'notifications_non_lues' => $user->unreadNotifications->count(),
        ]);
    }

    /**
     * 6️⃣ COMPLÉTER LE PROFIL STAGIAIRE
     */
    public function completeStagiaireProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'stagiaire') {
            return response()->json([
                'message' => 'Accès réservé au profil : stagiaire'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'date_naissance' => 'nullable|date',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:255',
            'ecole' => 'nullable|string|max:100',
            'filiere' => 'nullable|string|max:100',
            'niveau' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $stagiaire = Stagiaire::where('user_id', $user->id)->first();

        if (!$stagiaire) {
            return response()->json([
                'message' => 'Profil stagiaire non trouvé'
            ], 404);
        }

        $stagiaire->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'date_naissance' => $request->date_naissance,
            'telephone' => $request->telephone,
            'domicile_adresse' => $request->adresse,
            'ecole' => $request->ecole,
            'filiere' => $request->filiere,
            'niveau' => $request->niveau,
            'profil_complet' => true,
        ]);

        return response()->json([
            'message' => '✅ Profil complété avec succès !',
            'data' => $stagiaire
        ]);
    }

    /**
     * 7️⃣ COMPLÉTER LE PROFIL ENTREPRISE
     */
    public function completeEntrepriseProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'entreprise') {
            return response()->json([
                'message' => 'Accès réservé au profil : entreprise'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'raison_sociale' => 'required|string|max:150',
            'secteur' => 'nullable|string|max:100',
            'adresse_libelle' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'site_web' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $entreprise = Entreprise::where('user_id', $user->id)->first();

        if (!$entreprise) {
            return response()->json([
                'message' => 'Profil entreprise non trouvé'
            ], 404);
        }

        $entreprise->update([
            'raison_sociale' => $request->raison_sociale,
            'secteur' => $request->secteur,
            'adresse_libelle' => $request->adresse_libelle,
            'telephone' => $request->telephone,
            'site_web' => $request->site_web,
            'profil_complet' => true,
        ]);

        return response()->json([
            'message' => '✅ Profil entreprise complété avec succès !',
            'data' => $entreprise
        ]);
    }

    /**
     * 8️⃣ METTRE À JOUR LA PHOTO DE PROFIL (stagiaire ou entreprise)
     */
    public function updatePhotoProfil(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5 Mo max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $profil = $user->role === 'stagiaire'
            ? Stagiaire::where('user_id', $user->id)->first()
            : Entreprise::where('user_id', $user->id)->first();

        if (!$profil) {
            return response()->json([
                'message' => 'Profil non trouvé'
            ], 404);
        }

        // Supprimer l'ancienne photo si elle existe, pour ne pas accumuler des fichiers orphelins
        if ($profil->photo_profil) {
            Storage::disk('public')->delete($profil->photo_profil);
        }

        // Stocker la nouvelle photo
        $chemin = $request->file('photo')->store('photos_profil', 'public');

        $profil->update([
            'photo_profil' => $chemin,
        ]);

        return response()->json([
            'message' => '✅ Photo de profil mise à jour avec succès !',
            'data' => [
                'photo_profil' => $chemin,
                'photo_profil_url' => Storage::disk('public')->url($chemin),
            ]
        ]);
    }

    /**
     * 9️⃣ SUPPRIMER LA PHOTO DE PROFIL (stagiaire ou entreprise)
     */
    public function supprimerPhotoProfil(Request $request)
    {
        $user = $request->user();

        $profil = $user->role === 'stagiaire'
            ? Stagiaire::where('user_id', $user->id)->first()
            : Entreprise::where('user_id', $user->id)->first();

        if (!$profil) {
            return response()->json([
                'message' => 'Profil non trouvé'
            ], 404);
        }

        if (!$profil->photo_profil) {
            return response()->json([
                'message' => 'Aucune photo de profil à supprimer'
            ], 422);
        }

        Storage::disk('public')->delete($profil->photo_profil);

        $profil->update([
            'photo_profil' => null,
        ]);

        return response()->json([
            'message' => '✅ Photo de profil supprimée avec succès !'
        ]);
    }

    // ================================================
    // 🔧 MÉTHODES PRIVÉES
    // ================================================

    /**
     * Générer un code de vérification à 6 chiffres
     */
    private function generateCode(string $email): string
    {
        // Supprimer les anciens codes non utilisés
        VerificationCode::where('email', $email)
            ->where('used', false)
            ->delete();

        // Générer un code à 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Sauvegarder le code
        VerificationCode::create([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'code' => $code,
            'type' => 'registration',
            'used' => false,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        return $code;
    }

    /**
     * Envoyer l'email de vérification
     */
    private function sendVerificationEmail(string $email, string $code): void
    {
        try {
            Mail::send('emails.verification', [
                'code' => $code,
                'email' => $email
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('🔐 Vérifiez votre compte StageLink')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            Log::info("Code OTP envoyé avec succès à : $email");
        } catch (\Exception $e) {
            // Log l'erreur détaillée pour le diagnostic
            Log::error('❌ ÉCHEC ENVOI EMAIL OTP : ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString(),
                'smtp_host' => config('mail.mailers.smtp.host')
            ]);
        }
    }

    /**
     * Obtenir le statut du profil utilisateur
     */
    private function getProfileStatus(User $user): array
    {
        if ($user->role === 'stagiaire') {
            $stagiaire = Stagiaire::where('user_id', $user->id)->first();
            $profilComplet = $stagiaire->profil_complet ?? false;
            $carnetCree = $stagiaire->carnet_creer ?? false;

            return [
                'profile_complete' => $profilComplet,
                'carnet_created' => $carnetCree,
                'next_step' => $profilComplet ? 'carnet' : 'profile',
                'message' => $profilComplet
                    ? 'Créez votre carnet de stage'
                    : 'Complétez votre profil'
            ];
        } else {
            $entreprise = Entreprise::where('user_id', $user->id)->first();
            $profilComplet = $entreprise->profil_complet ?? false;

            return [
                'profile_complete' => $profilComplet,
                'next_step' => $profilComplet ? 'dashboard' : 'profile',
                'message' => $profilComplet
                    ? 'Gérez vos stagiaires'
                    : 'Complétez votre profil entreprise'
            ];
        }
    }
}
