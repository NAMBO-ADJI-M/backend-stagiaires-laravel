# Découplage complet de la Convention et du Carnet

Ce plan vise à corriger le flux de signature pour permettre la création d'une convention de stage même si le stagiaire n'a pas encore créé de carnet de stage.

## User Review Required

> [!IMPORTANT]
> La création d'une convention sans carnet signifie que certains champs qui étaient auparavant extraits du carnet (comme le métier ou le domaine de formation s'ils y étaient stockés) doivent maintenant provenir de l'autorisation de pointage ou des profils utilisateurs.

## Proposed Changes

### [Backend] Gestion des Conventions et Liaisons

#### [MODIFY] [ConventionController.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Http/Controllers/ConventionController.php)
- Rendre `carnet_id` optionnel dans la validation.
- Ajouter `autorisation_pointage_id` comme identifiant alternatif.
- Mettre à jour la logique de synchronisation des profils pour fonctionner via l'autorisation si le carnet est absent.
- Ajouter la méthode de contrôle d'accès `autoriserAccesAutorisation`.

#### [MODIFY] [AutorisationPointageController.php](file:///C:/laragon/www/backend-stagiaires-laravel/app/Http/Controllers/AutorisationPointageController.php)
- Supprimer le bloc conditionnel `if ($request->carnet_id)` dans `validerLiaison`.
- Appeler systématiquement `app(\App\Services\RattachementService::class)->rattacherEtSigner(...)` en passant `null` pour le carnet si nécessaire.

## Verification Plan

### Automated Tests
- Lancement d'un test de signature via Tinker pour simuler une liaison sans carnet :
```php
$auto = AutorisationPointage::factory()->create(['carnet_id' => null]);
$entreprise = $auto->entreprise;
app(RattachementService::class)->rattacherEtSigner($auto, $entreprise, null);
// Vérifier que Convention::where('autorisation_pointage_id', $auto->id)->exists() est vrai.
```

### Manual Verification
1. Créer un stagiaire via l'API (sans carnet).
2. L'entreprise envoie une demande de suivi.
3. Le stagiaire valide le code (liaison).
4. Vérifier en base que la table `conventions` contient une nouvelle ligne liée à `autorisation_pointage_id` et que `carnet_id` est `NULL`.
5. Vérifier que le PDF de la convention peut être généré via l'endpoint dédié.
