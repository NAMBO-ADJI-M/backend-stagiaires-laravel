<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // 0. DONNÉES DE RÉFÉRENCE (domaines / niveaux de formation)
        // ============================================
        $this->call([
            DomainesNiveauxFormationSeeder::class,
            MetierSeeder::class,
        ]);

        // ============================================
        // 1. CRÉER UN STAGIAIRE
        // ============================================
        
        // 1.1 Créer l'utilisateur d'abord
        $userStagiaireId = Str::uuid();
        
        DB::table('users')->insert([
            'id' => $userStagiaireId,
            'email' => 'stagiaire@test.com',
            'password' => Hash::make('password123'),
            'role' => 'stagiaire',
            'email_verified_at' => now(),
            'last_login_at' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1.2 Puis créer le stagiaire avec l'UUID de l'utilisateur
        DB::table('stagiaires')->insert([
            'id' => Str::uuid(),
            'user_id' => $userStagiaireId,  // ✅ Utiliser l'UUID
            'email' => 'stagiaire@test.com',
            'nom' => 'Test',
            'prenom' => 'Stagiaire',
            'photo_profil' => null,
            'domicile_adresse' => 'Dakar, Sénégal',
            'domicile_lat' => 14.7167,
            'domicile_lng' => -17.4677,
            'autorisation_entraide' => true,
            'profil_complet' => true,
            'carnet_creer' => true,
            'date_naissance' => '2000-01-01',
            'telephone' => '77 123 45 67',
            'ecole' => 'ESP',
            'filiere' => 'Informatique',
            'niveau' => 'Master 1',
            'date_premiere_connexion' => now(),
            'derniere_connexion' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ============================================
        // 2. CRÉER UNE ENTREPRISE
        // ============================================
        
        // 2.1 Créer l'utilisateur d'abord
        $userEntrepriseId = Str::uuid();
        
        DB::table('users')->insert([
            'id' => $userEntrepriseId,
            'email' => 'entreprise@test.com',
            'password' => Hash::make('password123'),
            'role' => 'entreprise',
            'email_verified_at' => now(),
            'last_login_at' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2.2 Puis créer l'entreprise avec l'UUID de l'utilisateur
        DB::table('entreprises')->insert([
            'id' => Str::uuid(),
            'user_id' => $userEntrepriseId,  // ✅ Utiliser l'UUID
            'email' => 'entreprise@test.com',
            'raison_sociale' => 'TechCorp SARL',
            'secteur' => 'Informatique',
            'adresse_libelle' => 'Dakar, Sénégal',
            'adresse_lat' => 14.7167,
            'adresse_lng' => -17.4677,
            'rayon_detection_metres' => 100,
            'heure_debut_journee' => '08:00:00',
            'heure_fin_journee' => '17:00:00',
            'pause_heure_debut' => '12:00:00',
            'pause_heure_fin' => '14:00:00',
            'profil_complet' => true,
            'telephone' => '33 123 45 67',
            'site_web' => 'https://techcorp.sn',
            'derniere_connexion' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ============================================
        // 3. MESSAGE DE SUCCÈS
        // ============================================
        
        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('✅ SEEDING TERMINÉ AVEC SUCCÈS !');
        $this->command->info('========================================');
        $this->command->newLine();
        $this->command->info('🔑 COMPTES DE TEST :');
        $this->command->info('   📧 Stagiaire  : stagiaire@test.com');
        $this->command->info('   🔑 Mot de passe : password123');
        $this->command->newLine();
        $this->command->info('   📧 Entreprise : entreprise@test.com');
        $this->command->info('   🔑 Mot de passe : password123');
        $this->command->newLine();
        $this->command->info('🚀 TESTEZ L\'API :');
        $this->command->info('   curl -X POST http://localhost:8000/api/auth/login \\');
        $this->command->info('   -H "Content-Type: application/json" \\');
        $this->command->info('   -d \'{"email":"stagiaire@test.com","password":"password123"}\'');
        $this->command->newLine();
        $this->command->info('========================================');
    }
}
