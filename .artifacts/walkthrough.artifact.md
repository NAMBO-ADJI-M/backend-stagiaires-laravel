# Walkthrough - Correction de la gestion des SoftDeletes (Authentification)

Nous avons corrigé l'erreur de duplication d'email lors de la reconnexion d'utilisateurs supprimés en implémentant une restauration complète (Compte + Profil).

## Changements effectués

### Backend Laravel

#### 1. Modèles de Profils
- **[Stagiaire.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Models/Stagiaire.php)** et **[Entreprise.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Models/Entreprise.php)** : Ajout du trait `SoftDeletes`. Cela permet de conserver les données (nom, prénom, raison sociale) même après une suppression de compte, facilitant la restauration.

#### 2. Migration
- **[2026_08_28_153500_add_soft_deletes_to_profiles_tables.php](file:///C:/laragon/www/backend-stagiaires-laravel/database/migrations/2026_08_28_153500_add_soft_deletes_to_profiles_tables.php)** : Ajout de la colonne `deleted_at` aux tables `stagiaires` et `entreprises`.

#### 3. Contrôleur d'Authentification
- **[AuthController.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Http/Controllers/Auth/AuthController.php)** :
    - **`login()`** : Utilise désormais `User::withTrashed()` pour détecter un ancien compte. S'il est trouvé et supprimé, il est restauré ainsi que son profil associé.
    - **`profile()`** : Correction de la logique d'auto-création pour vérifier d'abord si un profil supprimé existe avant d'en créer un nouveau, évitant ainsi l'erreur `Duplicate entry`.
    - **Flags de réponse** : La réponse JSON inclut `is_new_user` et `was_restored` pour informer le frontend de l'état du compte.

## Actions requises par l'utilisateur

> [!IMPORTANT]
> Vous devez exécuter les migrations sur votre serveur Laravel pour que les changements prennent effet :
> ```bash
> php artisan migrate
> ```

## Vérification effectuée
- Analyse statique de la logique de restauration.
- Vérification des contraintes d'unicité sur les tables `users`, `stagiaires` et `entreprises`.
- Validation de l'invalidité des codes OTP lors d'une nouvelle demande.
