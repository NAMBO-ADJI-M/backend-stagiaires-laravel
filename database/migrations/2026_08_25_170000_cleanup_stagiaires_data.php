<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. D'abord, on synchronise tous les emails depuis la table users (SECURITÉ)
        // Cela remplit les champs vides pour les anciens comptes.
        DB::table('stagiaires')
            ->join('users', 'stagiaires.user_id', '=', 'users.id')
            ->update([
                'stagiaires.email' => DB::raw('users.email')
            ]);

        // 2. Ensuite, on supprime les placeholders "StageLink Utilisateur"
        // On les remet à NULL pour que le front puisse afficher "Profil incomplet"
        DB::table('stagiaires')
            ->where('nom', 'Utilisateur')
            ->where('prenom', 'StageLink')
            ->update([
                'nom' => null,
                'prenom' => null
            ]);
    }

    public function down(): void
    {
        // Opération non réversible sans backup
    }
};
