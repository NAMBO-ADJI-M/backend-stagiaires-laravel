## Corrections formulaire Convention + PDF + Autosave

### 1. Champ "cursus" -> liste de choix (dropdown)
Le champ "cursus" a été transformé d'un texte libre en une liste déroulante pour assurer la cohérence des données.
- **Liste proposée** : BTS, Licence, Master, Ingénieur, Doctorat, Autre.
- **Backend** : Mise à jour de `AutorisationPointageController` et `FicheStagiaireInviteController` avec une validation `in:BTS,Licence,Master,Ingénieur,Doctorat,Autre`.
- **Flutter** : Utilisation de `DropdownButtonFormField` dans `add_stagiaire_dialog.dart` et `liaison_stagiaire_dialog.dart`.

### 2. Retrait de champs (Formulaire + PDF)
Les champs "Date de naissance" et "Référent pédagogique" ont été retirés du parcours stagiaire.
- **Flutter** : Suppression des champs dans `home_screen.dart`.
- **Backend** : Mise à jour de la validation dans `AutorisationPointageController@validerLiaison` pour rendre ces champs optionnels.
- **Template PDF** : Suppression des lignes correspondantes dans `convention.blade.php` pour éviter d'afficher des libellés vides. La mise en page a été préservée.

### 3. Séparation des endpoints pour l'Autosave
Pour éviter de déclencher la signature de la convention à chaque frappe de clavier, les responsabilités ont été séparées.
- **Brouillon (Autosave)** : Un nouvel endpoint `POST /api/pointage/brouillon-liaison` a été créé. Il met à jour uniquement le profil du stagiaire et les coordonnées de l'autorisation, sans changer le statut ni générer de PDF.
- **Validation Finale (Signature)** : L'endpoint `valider-liaison` n'est plus appelé que lors du clic sur le bouton final de signature.
- **Flutter** : `InternshipService` intègre maintenant `sauvegarderBrouillonLiaison`, utilisé par le `debounceTimer` dans `home_screen.dart`.

### 4. Correction compilation Flutter
Correction d'une erreur de compilation dans `home_screen.dart` liée à l'utilisation de variables locales `lat` et `lng` après un changement de contexte.

---
**Note :** Le scénario "plusieurs autosaves puis une signature finale" a été sécurisé : seule l'action finale déclenche le statut `CONVENTION_SIGNEE`.
