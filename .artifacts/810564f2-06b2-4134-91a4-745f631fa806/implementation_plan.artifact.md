# Gestion des Utilisateurs Soft-Deleted lors de la Connexion

Ce plan vise à corriger l'erreur `Duplicate entry` sur la contrainte d'email unique lorsqu'un utilisateur précédemment supprimé (soft delete) tente de se reconnecter. La solution consiste à vérifier la présence d'un compte supprimé, le restaurer le cas échéant, ou créer un nouveau compte si l'email est inconnu.

## User Review Required

> [!IMPORTANT]
> **Décision sur la Restauration :** La solution actuelle restaure automatiquement le compte. Cependant, pour des raisons de conformité RGPD ou de clarté métier, il peut être préférable d'informer l'utilisateur que son compte va être restauré avant de procéder.
> 
> Voir la section **Options de Restauration** ci-dessous pour plus de détails.

## Proposed Changes

### [Backend Laravel]

#### [MODIFY] [AuthController.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Http/Controllers/Auth/AuthController.php)
Optimisation de la méthode `login()` pour :
1. Rechercher l'utilisateur avec `withTrashed()`.
2. Restaurer (`restore()`) si l'utilisateur est dans la corbeille.
3. Créer le compte uniquement si aucun utilisateur (même supprimé) n'existe.
4. Nettoyer les anciens codes de vérification.
5. Retourner les drapeaux `is_new_user` et `was_restored`.

---

## Options de Restauration

### Option 1 : Restauration Automatique (Transparent)
C'est l'approche implémentée dans la proposition actuelle.
- **Avantage :** Expérience utilisateur fluide, sans friction.
- **Inconvénient :** L'utilisateur peut ne pas réaliser que ses anciennes données sont conservées (point d'attention RGPD).
- **Conformité :** Nécessite une mention dans les CGU/Politique de confidentialité précisant que les données sont conservées pendant X jours avant suppression définitive.

### Option 2 : Restauration Explicite (Confirmation)
- **Fonctionnement :** Si l'utilisateur est trouvé en `trashed()`, le backend renvoie un code spécifique (ex: `needs_restoration`) et n'envoie pas d'OTP tout de suite. Le frontend demande confirmation.
- **Avantage :** Plus respectueux de la volonté initiale de suppression.
- **Inconvénient :** Ajoute une étape au flux de connexion.

---

## Verification Plan

### Automated Tests
- Tentative de connexion avec un nouvel email -> Vérifier création + 201.
- Suppression du compte (Soft Delete).
- Tentative de connexion avec le même email -> Vérifier restauration + 200 + `was_restored: true`.

### Manual Verification
- Utiliser Postman/Insomnia pour tester le endpoint `/api/auth/login` et vérifier les réponses JSON.
- Vérifier en base de données que la colonne `deleted_at` repasse à `null`.
