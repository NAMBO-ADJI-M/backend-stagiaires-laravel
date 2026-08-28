<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Stagiaire;
use App\Models\Entreprise;
use App\Models\VerificationCode;
use App\Mail\VerificationCodeMail;
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
     * 1️⃣ LOGIN / REGISTER UNIQUE - DEMANDE DE CODE
     * Gère la création, la restauration (soft delete) et la connexion.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'nullable|string|in:stagiaire,entreprise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strtolower(trim($request->email));

        // 🔍 Rechercher l'utilisateur, y compris ceux supprimés (soft delete)
        $user = User::withTrashed()->where('email', $email)->first();

        $isNewUser = false;
        $wasRestored = false;

        if ($user) {
            // ♻️ Si l'utilisateur était supprimé, on le restaure
            if ($user->trashed()) {
                $user->restore();
                $wasRestored = true;

                // Restaurer également le profil associé s'il existe et est supprimé
                if ($user->role === 'stagiaire') {
                    $profil = Stagiaire::withTrashed()->where('user_id', $user->id)->first();
                    if ($profil && $profil->trashed()) {
                        $profil->restore();
                    } elseif (!$profil) {
                        // Cas rare : User existe (même trashed) mais pas de profil
                        Stagiaire::create([
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'nom' => null,
                            'prenom' => null,
                            'profil_complet' => false,
                            'carnet_creer' => false,
                        ]);
                    }
                } elseif ($user->role === 'entreprise') {
                    $profil = Entreprise::withTrashed()->where('user_id', $user->id)->first();
                    if ($profil && $profil->trashed()) {
                        $profil->restore();
                    } elseif (!$profil) {
                        Entreprise::create([
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'raison_sociale' => 'Mon Entreprise',
                            'profil_complet' => false,
                        ]);
                    }
                }

                Log::info("♻️ Utilisateur et profil restaurés : {$email}");
            }
        } else {
            // ✨ Nouvel utilisateur : création
            $user = User::create([
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => $request->role ?? 'stagiaire', // Utilisation du rôle demandé ou stagiaire par défaut
                'is_active' => true,
            ]);

            // Création immédiate du profil métier pour éviter les erreurs 404 ultérieures
            if ($user->role === 'stagiaire') {
                Stagiaire::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => null,
                    'prenom' => null,
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

            $isNewUser = true;
            Log::info("✨ Nouvel utilisateur créé : {$email} avec rôle {$user->role}");
        }

        // 🛑 S'assurer que le compte est actif (indépendant du soft delete)
        if (!$user->is_active) {
            $user->update(['is_active' => true]);
        }

        // 🧹 Invalider les anciens codes non utilisés
        VerificationCode::where('email', $email)->where('used', false)->delete();

        // 🔑 Générer un nouveau code
        $code = $this->generateCode($email);

        try {
            $this->sendVerificationEmail($email, $code);
        } catch (\Exception $e) {
            Log::error("❌ Erreur d'envoi OTP à {$email}: " . $e->getMessage());
            return response()->json([
                'message' => '❌ Impossible d\'envoyer le code de vérification.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => $isNewUser
                ? '✅ Compte créé ! Un code de vérification a été envoyé.'
                : ($wasRestored ? '♻️ Compte réactivé ! Un code de vérification a été envoyé.' : '📧 Un code de vérification a été envoyé.'),
            'data' => [
                'email' => $email,
                'is_new_user' => $isNewUser,
                'was_restored' => $wasRestored,
                'requires_verification' => true,
                'code_expires_in' => 600,
            ]
        ], $isNewUser ? 201 : 200);
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

        $email = strtolower(trim($request->email));
        $code = trim($request->code);

        // Vérifier le code
        $verification = VerificationCode::where('email', $email)
            ->where('code', $code)
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
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $user->update([
            'email_verified_at' => Carbon::now(),
            'last_login_at' => Carbon::now(),
        ]);

        // Générer le token (reste valide jusqu'à déconnexion manuelle)
        $token = $user->createToken('auth-token')->plainTextToken;
        $profileStatus = $this->getProfileStatus($user);

        return response()->json([
            'message' => '✅ Email vérifié avec succès !',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profil_complet' => $profileStatus['profile_complete'],
                    'profil_data' => $user->role === 'stagiaire' ? $user->stagiaire : $user->entreprise,
                ],
                'profile_status' => $profileStatus,
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

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => '❌ Ce compte est désactivé.',
                'errors' => ['email' => ['Compte inactif ou désactivé.']]
            ], 403);
        }

        $code = $this->generateCode($user->email);

        try {
            $this->sendVerificationEmail($user->email, $code);
        } catch (\Exception $e) {
            Log::error("❌ Erreur renvoi OTP Brevo à {$user->email}: " . $e->getMessage());
            return response()->json([
                'message' => '❌ Impossible d\'envoyer le code de vérification par email.',
                'error' => $e->getMessage()
            ], 500);
        }

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

        // Auto-restauration ou création défensive du profil si manquant
        if ($user->role === 'stagiaire') {
            $stagiaire = Stagiaire::withTrashed()->where('user_id', $user->id)->first();
            if ($stagiaire && $stagiaire->trashed()) {
                $stagiaire->restore();
            } elseif (!$stagiaire) {
                Stagiaire::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => null,
                    'prenom' => null,
                    'profil_complet' => false,
                    'carnet_creer' => false,
                ]);
            }
            $user->load('stagiaire');
        } elseif ($user->role === 'entreprise') {
            $entreprise = Entreprise::withTrashed()->where('user_id', $user->id)->first();
            if ($entreprise && $entreprise->trashed()) {
                $entreprise->restore();
            } elseif (!$entreprise) {
                Entreprise::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'raison_sociale' => 'Mon Entreprise',
                    'profil_complet' => false,
                ]);
            }
            $user->load('entreprise');
        }

        $profile = $this->getProfileStatus($user);
        $notifCount = rescue(fn() => $user->unreadNotifications()->count(), 0);

        $data = [
            'user' => $user,
            'profile_status' => $profile,
            'profile_data' => $user->role === 'stagiaire'
                ? $user->stagiaire
                : $user->entreprise,
            'notifications_non_lues' => $notifCount,
        ];

        // Pour le stagiaire, on ajoute l'état de l'autorisation pour l'entreprise active
        if ($user->role === 'stagiaire' && $user->stagiaire) {
            $carnet = \App\Models\CarnetDeStage::where('stagiaire_id', $user->stagiaire->id)
                ->where('statut', 'EN_COURS')
                ->first();

            if ($carnet && $carnet->entreprise_id) {
                $auto = \App\Models\AutorisationPointage::where('stagiaire_id', $user->stagiaire->id)
                    ->where('entreprise_id', $carnet->entreprise_id)
                    ->first();
                $data['autorisation_pointage'] = [
                    'entreprise_id' => $carnet->entreprise_id,
                    'entreprise_nom' => $carnet->entreprise_nom,
                    'statut' => $auto ? $auto->statut : 'INACTIVE',
                ];
            }
        }

        return response()->json($data);
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
            'adresse_lat' => 'nullable|numeric',
            'adresse_lng' => 'nullable|numeric',
            'rayon_detection_metres' => 'nullable|integer|min:50|max:1000',
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
            'adresse_lat' => $request->adresse_lat,
            'adresse_lng' => $request->adresse_lng,
            'rayon_detection_metres' => $request->rayon_detection_metres ?? 100,
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
        // Supprimer tous les anciens codes pour cet email (évite l'accumulation)
        VerificationCode::where('email', $email)->delete();

        // Générer un code à 6 chiffres entre 100000 et 999999
        $code = (string) random_int(100000, 999999);

        // Sauvegarder le code
        VerificationCode::create([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'code' => $code,
            'type' => 'registration',
            'used' => false,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return $code;
    }

    /**
     * Envoyer l'email de vérification
     */
    private function sendVerificationEmail(string $email, string $code): void
    {
        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $email));
            Log::info("Code OTP envoyé avec succès via Brevo à : $email");
        } catch (\Exception $e) {
            // Log l'erreur détaillée pour le diagnostic
            Log::error('❌ ÉCHEC ENVOI EMAIL OTP : ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtenir le statut du profil utilisateur
     */
    private function getProfileStatus(User $user): array
    {
        if ($user->role === 'stagiaire') {
            $stagiaire = Stagiaire::withTrashed()->where('user_id', $user->id)->first();

            // Auto-restauration ou création défensive si manquant
            if ($stagiaire && $stagiaire->trashed()) {
                $stagiaire->restore();
            } elseif (!$stagiaire) {
                $stagiaire = Stagiaire::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => null,
                    'prenom' => null,
                    'profil_complet' => false,
                    'carnet_creer' => false,
                ]);
            }

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
            $entreprise = Entreprise::withTrashed()->where('user_id', $user->id)->first();

            // Auto-restauration ou création défensive si manquant
            if ($entreprise && $entreprise->trashed()) {
                $entreprise->restore();
            } elseif (!$entreprise) {
                $entreprise = Entreprise::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'raison_sociale' => 'Mon Entreprise',
                    'profil_complet' => false,
                ]);
            }

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
