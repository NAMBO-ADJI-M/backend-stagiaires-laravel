<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeStagiaires extends Command
{
    protected $signature = 'stagiaires:purge {--dry-run}';
    protected $description = 'Supprime tous les comptes stagiaires et toutes leurs données liées (suppression physique)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $userIds       = User::where('role', 'stagiaire')->pluck('id');
        $stagiaireIds  = DB::table('stagiaires')->whereIn('user_id', $userIds)->pluck('id');
        $carnetIds     = DB::table('carnets_de_stage')->whereIn('stagiaire_id', $stagiaireIds)->pluck('id');
        $trajetIds     = DB::table('trajets')->whereIn('conducteur_id', $stagiaireIds)->pluck('id');

        $counts = [
            'users (role=stagiaire)'      => $userIds->count(),
            'stagiaires'                  => $stagiaireIds->count(),
            'carnets_de_stage'            => $carnetIds->count(),
            'trajets'                     => $trajetIds->count(),
            'attestations'                => DB::table('attestations')->whereIn('carnet_id', $carnetIds)->count(),
            'cartes_appui_stage'          => DB::table('cartes_appui_stage')->whereIn('carnet_id', $carnetIds)->count(),
            'reservations'                => DB::table('reservations')->whereIn('trajet_id', $trajetIds)->count(),
            'messages'                    => DB::table('messages')->whereIn('trajet_id', $trajetIds)->count(),
            'signalements'                => DB::table('signalements')->whereIn('trajet_id', $trajetIds)->count(),
            'entrees_carnet'              => DB::table('entrees_carnet')->whereIn('carnet_id', $carnetIds)->count(),
            'progression_competences'     => DB::table('progression_competences')->whereIn('carnet_id', $carnetIds)->count(),
            'evaluations_savoir_etre'     => DB::table('evaluations_savoir_etre')->whereIn('carnet_id', $carnetIds)->count(),
            'bilans_reflexifs'            => DB::table('bilans_reflexifs')->whereIn('carnet_id', $carnetIds)->count(),
            'notifications_encouragement' => DB::table('notifications_encouragement')->whereIn('carnet_id', $carnetIds)->count(),
            'evaluations_competence'      => DB::table('evaluations_competence')->whereIn('carnet_id', $carnetIds)->count(),
            'demandes_rattachement'       => DB::table('demandes_rattachement')->whereIn('stagiaire_id', $stagiaireIds)->count(),
            'conventions'                 => DB::table('conventions')->whereIn('carnet_id', $carnetIds)->orWhereIn('stagiaire_id', $stagiaireIds)->count(),
            'indicateurs_assiduite'       => DB::table('indicateurs_assiduite')->whereIn('carnet_id', $carnetIds)->count(),
            'autorisations_pointage'      => DB::table('autorisations_pointage')->whereIn('stagiaire_id', $stagiaireIds)->count(),
            'verification_codes'         => DB::table('verification_codes')->whereIn('email', User::whereIn('id', $userIds)->pluck('email'))->count(),
        ];

        $this->table(['Table', 'Lignes concernées'], collect($counts)->map(fn ($v, $k) => [$k, $v])->values());

        if ($userIds->isEmpty()) {
            $this->info('Aucun compte stagiaire trouvé. Rien à faire.');
            return 0;
        }

        if ($dryRun) {
            $this->warn('--dry-run : aucune suppression effectuée.');
            return 0;
        }

        $confirm = $this->ask("Tape SUPPRIMER en toutes lettres pour confirmer la suppression définitive de {$userIds->count()} compte(s) stagiaire et toutes leurs données");
        if ($confirm !== 'SUPPRIMER') {
            $this->error('Confirmation invalide. Annulation.');
            return 1;
        }

        DB::transaction(function () use ($userIds, $stagiaireIds, $carnetIds, $trajetIds) {

            // --- Niveau 5 : tables feuilles (dépendent de carnet_id / trajet_id) ---
            DB::table('attestations')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('cartes_appui_stage')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('reservations')->whereIn('trajet_id', $trajetIds)->delete();
            DB::table('messages')->whereIn('trajet_id', $trajetIds)->delete();
            DB::table('signalements')->whereIn('trajet_id', $trajetIds)->delete();
            DB::table('entrees_carnet')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('progression_competences')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('evaluations_savoir_etre')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('bilans_reflexifs')->whereIn('carnet_id', $carnetIds)->delete();
            DB::table('notifications_encouragement')->whereIn('carnet_id', $carnetIds)->delete();

            // --- Trajets (vidés de leurs enfants ci-dessus) ---
            DB::table('trajets')->whereIn('id', $trajetIds)->delete();

            // --- evaluations_competence (après attestations/cartes_appui_stage qui la référencent) ---
            DB::table('evaluations_competence')->whereIn('carnet_id', $carnetIds)->delete();

            // --- Rattachements et conventions ---
            DB::table('demandes_rattachement')->whereIn('stagiaire_id', $stagiaireIds)->delete();
            DB::table('conventions')->whereIn('carnet_id', $carnetIds)->orWhereIn('stagiaire_id', $stagiaireIds)->delete();

            // --- indicateurs_assiduite (après conventions qui peut la référencer via autorisation_pointage_id) ---
            DB::table('indicateurs_assiduite')->whereIn('carnet_id', $carnetIds)->delete();

            // --- autorisations_pointage (après conventions/indicateurs_assiduite qui la référencent) ---
            DB::table('autorisations_pointage')->whereIn('stagiaire_id', $stagiaireIds)->delete();

            // --- fiches_stagiaire_invite : reset (pas de suppression), comme lors de la précédente opération ---
            if (Schema::hasTable('fiches_stagiaire_invite') && Schema::hasColumn('fiches_stagiaire_invite', 'carnet_id')) {
                DB::table('fiches_stagiaire_invite')->whereIn('carnet_id', $carnetIds)->update(['carnet_id' => null]);
            }

            // --- carnets_de_stage ---
            DB::table('carnets_de_stage')->whereIn('stagiaire_id', $stagiaireIds)->delete();

            // --- Tokens Sanctum ---
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            // --- Sessions (uniquement user_sessions, en UUID compatible avec users.id) ---
            // NB : la table Laravel "sessions" (auth web classique) utilise un user_id en BIGINT,
            // incompatible avec les UUID de users.id — on ne la touche pas ici.
            if (Schema::hasTable('user_sessions') && Schema::hasColumn('user_sessions', 'user_id')) {
                DB::table('user_sessions')->whereIn('user_id', $userIds)->delete();
            }

            // --- verification_codes (par email, pas de FK) ---
            $emails = User::whereIn('id', $userIds)->pluck('email');
            DB::table('verification_codes')->whereIn('email', $emails)->delete();

            // --- Profils stagiaires puis comptes users ---
            DB::table('stagiaires')->whereIn('user_id', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        });

        $this->info('Suppression terminée avec succès.');
        return 0;
    }
}
