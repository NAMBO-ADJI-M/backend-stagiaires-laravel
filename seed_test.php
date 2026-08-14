<?php

use App\Models\Stagiaire;
use App\Models\Entreprise;
use App\Models\CarnetDeStage;
use App\Models\Metier;
use App\Models\NiveauFormation;
use App\Models\FicheStagiaireInvite;
use App\Models\Trajet;

// ==================== ENTREPRISE ====================
$entreprise = Entreprise::firstOrCreate(
    ['email' => 'contact@techcorp.tg'],
    [
        'raison_sociale' => 'TechCorp SARL',
        'secteur' => 'Informatique',
        'heure_debut_journee' => '08:00:00',
        'heure_fin_journee' => '17:00:00',
        'pause_heure_debut' => '12:00:00',
        'pause_heure_fin' => '14:00:00',
    ]
);
echo "Entreprise créée : {$entreprise->id}\n";

// ==================== STAGIAIRE ====================
$marie = Stagiaire::firstOrCreate(
    ['email' => 'marie.dupont@email.com'],
    ['nom' => 'Dupont', 'prenom' => 'Marie', 'date_premiere_connexion' => now()]
);
echo "Stagiaire créé : {$marie->id}\n";

// ==================== CARNET DE STAGE ====================
$metier = Metier::where('nom', 'Developpeur web')->first();
$niveau = NiveauFormation::first();

$carnet = CarnetDeStage::firstOrCreate(
    ['stagiaire_id' => $marie->id, 'metier_id' => $metier->id],
    [
        'domaine_formation_id' => $metier->domaine_formation_id,
        'niveau_formation_id' => $niveau->id,
    ]
);
echo "Carnet créé : {$carnet->id}\n";

// ==================== RATTACHEMENT ====================
$fiche = FicheStagiaireInvite::firstOrCreate(
    ['entreprise_id' => $entreprise->id, 'email' => $marie->email],
    [
        'nom' => 'Dupont',
        'prenom' => 'Marie',
        'code_invitation' => 'TESTCODE1',
        'date_expiration' => now()->addDays(30),
    ]
);

if (!$carnet->entreprise_id) {
    $carnet->update([
        'entreprise_id' => $entreprise->id,
        'code_rattachement_utilise' => $fiche->code_invitation,
        'date_rattachement' => now(),
        'autorisation_suivi' => true,
    ]);
    $fiche->update(['utilise' => true, 'carnet_id' => $carnet->id]);
    echo "Carnet rattaché à TechCorp\n";
}

// ==================== DEUXIEME STAGIAIRE (pour tester la réservation) ====================
$paul = Stagiaire::firstOrCreate(
    ['email' => 'paul.k@email.com'],
    ['nom' => 'Kodjo', 'prenom' => 'Paul', 'date_premiere_connexion' => now()]
);
echo "Stagiaire Paul créé : {$paul->id}\n";

// ==================== TOKENS ====================
$tokenMarie = $marie->createToken('auth-stagiaire')->plainTextToken;
$tokenPaul = $paul->createToken('auth-stagiaire')->plainTextToken;
$tokenEntreprise = $entreprise->createToken('auth-entreprise')->plainTextToken;

echo "\n========== RECAPITULATIF ==========\n";
echo "Carnet ID       : {$carnet->id}\n";
echo "Token Marie     : {$tokenMarie}\n";
echo "Token Paul      : {$tokenPaul}\n";
echo "Token Entreprise: {$tokenEntreprise}\n";
echo "====================================\n";
