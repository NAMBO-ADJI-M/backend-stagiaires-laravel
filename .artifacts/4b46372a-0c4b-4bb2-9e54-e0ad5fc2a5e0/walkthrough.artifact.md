## Corrections urgentes — Migration + 7 points fonctionnels

### 0. PRIORITÉ ABSOLUE — Débloquer le déploiement
- **Diagnostic** : La migration `fix_indicateurs_assiduite_indexes` échouait car elle tentait de supprimer un index sans vérifier son existence.
- **Correctif** : Migration rendue idempotente via un check `SHOW INDEX` avant le `dropUnique`.
- **Impact** : Le déploiement (`php artisan migrate`) est désormais débloqué.

### 1. Persistance du Toggle "Confirmez votre présence"
- **Diagnostic** : Le toggle ne restait pas actif car le statut `CONVENTION_SIGNEE` (renvoyé par le backend après signature) n'était pas traité comme un état "actif" dans le widget `_LiaisonPanel`.
- **Correctif** : Mise à jour de la logique Flutter pour inclure `CONVENTION_SIGNEE` dans l'état actif.

### 2. Résolution de l'erreur "string" (Type Mismatch)
- **Diagnostic** : Les coordonnées GPS (`lieu_execution_lat/lng`) étaient renvoyées comme `String` par l'API (défaut MySQL Decimal), provoquant un échec du cast `as num` dans Flutter lors du démarrage du géofencing.
- **Correctif** : Ajout de casts `float` explicites dans les modèles `AutorisationPointage` et `FicheStagiaireInvite`.

### 3. PDF Convention : Nom + Prénom et Mise en page
- **Diagnostic** : Seul le prénom s'affichait et l'espace de la date de naissance laissait un trou visuel.
- **Correctif** :
    - Concaténation `{{ strtoupper($stagiaire->nom) }} {{ $stagiaire->prenom }}`.
    - Suppression complète du bloc HTML de la date de naissance dans `convention.blade.php`.

### 4. Carnet accessible dès création
- **Diagnostic** : Les nouveaux carnets étaient créés avec le statut `EN_ATTENTE`, mais les filtres Flutter cherchaient `EN_COURS`.
- **Correctif** : Statut par défaut passé à `EN_COURS` dans `CarnetController@store`.

### 5. Erreur création Trajet + Persistance Géoloc
- **Diagnostic** :
    - Erreur 500 possible si le profil stagiaire de secours échouait (unique constraint).
    - État du bouton géoloc (tracking) perdu au redémarrage/navigation.
- **Correctif** :
    - Utilisation de `updateOrCreate` pour le profil stagiaire dans `TrajetController`.
    - Persistance de l'état du tracking et de l'ID du trajet actif via `SharedPreferences` dans `LiveTrackingService` et `CovoiturageHomeScreen`.

### 6. Dashboard Tuteur : Nettoyage des cartes "Demander l'accès"
- **Diagnostic** : Les demandes de rattachement ne passaient pas en `traitee` lors d'une invitation directe par email.
- **Correctif** : Mise à jour de `FicheStagiaireInviteController@store` pour marquer les demandes existantes comme traitées.

### 7. Signature -> Activation automatique du Géofencing
- **Diagnostic** : Le géofencing ne démarrait pas automatiquement après signature ou au rechargement de l'app.
- **Correctif** :
    - Déclenchement automatique de `GeofencingService().start()` dans `_loadDashboardData` si le statut est `CONVENTION_SIGNEE`.
    - Suppression de l'exigence d'un `carnetId` non-null pour démarrer (le backend gère le pointage sans carnet).

---
**Vérification** : Toutes les migrations passent, le dashboard tuteur est propre, et le cycle de signature/pointage est entièrement automatisé.
