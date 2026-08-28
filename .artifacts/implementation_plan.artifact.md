# Correction de la gestion des SoftDeletes pour l'authentification

Ce plan vise à résoudre l'erreur `SQLSTATE[23000]: Duplicate entry` lors de la reconnexion d'un utilisateur précédemment supprimé (soft delete). Le problème provient du fait que les profils associés (`Stagiaire` et `Entreprise`) ne sont pas restaurés ou sont ignorés lors des vérifications d'existence, provoquant des tentatives de création de doublons.

## Changements proposés

### Modèles [Component]

#### [MODIFY] [Stagiaire.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Models/Stagiaire.php)
- Ajout du trait `SoftDeletes`.

#### [MODIFY] [Entreprise.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Models/Entreprise.php)
- Ajout du trait `SoftDeletes`.

---

### Base de données [Component]

#### [NEW] [2026_08_28_153500_add_soft_deletes_to_profiles_tables.php](file:///C:/laragon/www/backend-stagiaires-laravel/database/migrations/2026_08_28_153500_add_soft_deletes_to_profiles_tables.php)
- Ajout de la colonne `deleted_at` aux tables `stagiaires` et `entreprises`.

---

### Contrôleur d'authentification [Component]

#### [MODIFY] [AuthController.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Http/Controllers/Auth/AuthController.php)
- **Méthode `login()`** : 
    - Restaurer non seulement le `User`, mais aussi son profil associé (`Stagiaire` ou `Entreprise`) s'ils sont supprimés.
    - S'assurer que les codes de vérification obsolètes sont nettoyés.
    - Retourner `is_new_user` et `was_restored` dans la réponse JSON.
- **Méthode `profile()`** : 
    - Utiliser `withTrashed()` lors de la vérification de l'existence du profil pour éviter de recréer un profil si un profil supprimé existe déjà (ce qui causait l'erreur de duplication d'email).
- **Méthode `deleteAccount()`** : 
    - S'assurer que la suppression des profils est bien effectuée (elle sera désormais en "soft delete").

## Plan de vérification

### Tests Manuels
- Supprimer un compte stagiaire via l'API.
- Tenter de se reconnecter avec le même email.
- Vérifier que le compte est restauré, que le profil est récupéré (nom/prénom conservés) et que la réponse contient `was_restored: true`.
- Vérifier qu'aucun doublon n'est créé en base de données.
