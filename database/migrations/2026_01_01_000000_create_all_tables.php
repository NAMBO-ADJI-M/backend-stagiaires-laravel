<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { // TABLE USERS UNIFIÉE
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['stagiaire', 'entreprise']);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
        });

        // ==================== STAGIAIRES ====================
        Schema::create('stagiaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('photo_profil')->nullable();
            $table->string('domicile_adresse')->nullable();
            $table->decimal('domicile_lat', 10, 7)->nullable();
            $table->decimal('domicile_lng', 10, 7)->nullable();
            $table->boolean('autorisation_entraide')->default(false);
            $table->boolean('profil_complet')->default(false);
            $table->boolean('carnet_creer')->default(false);
            $table->date('date_naissance')->nullable();
            $table->string('telephone')->nullable();
            $table->string('ecole')->nullable();
            $table->string('filiere')->nullable();
            $table->string('niveau')->nullable();
            $table->timestamp('date_premiere_connexion')->nullable();
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('email');
        });

        // ==================== ENTREPRISES ====================
        Schema::create('entreprises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('raison_sociale')->nullable();
            $table->string('secteur')->nullable();
            $table->string('adresse_libelle')->nullable();
            $table->decimal('adresse_lat', 10, 7)->nullable();
            $table->decimal('adresse_lng', 10, 7)->nullable();
            $table->integer('rayon_detection_metres')->default(100);
            $table->time('heure_debut_journee')->nullable();
            $table->time('heure_fin_journee')->nullable();
            $table->time('pause_heure_debut')->nullable();
            $table->time('pause_heure_fin')->nullable();
            $table->boolean('profil_complet')->default(false);
            $table->string('telephone')->nullable();
            $table->string('site_web')->nullable();
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('email');
        });

        // ==================== CODES DE VÉRIFICATION UNIFIÉS ====================
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('code', 6);
            $table->enum('type', ['registration', 'password_reset', 'email_change'])->default('registration');
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['email', 'code']);
            $table->index('expires_at');
        });

        // ==================== SESSIONS ====================
        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token', 500);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('expires_at');
        });

        // ==================== REFERENTIEL ====================
        Schema::create('domaines_formation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom')->unique();
        });

        Schema::create('metiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('domaine_formation_id')->constrained('domaines_formation');
            $table->string('nom');
            $table->unique(['domaine_formation_id', 'nom']);
        });

        Schema::create('niveaux_formation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom')->unique();
        });

        Schema::create('competences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('metier_id')->constrained('metiers');
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('seuil_decouverte', 6, 2);
            $table->decimal('seuil_maitrise', 6, 2);
            $table->json('mots_cles_detection')->nullable();
        });

        Schema::create('criteres_savoir_etre', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->unique(['entreprise_id', 'nom']);
        });

        // ==================== CARNET DE STAGE ====================
        Schema::create('carnets_de_stage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stagiaire_id')->constrained('stagiaires');
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises');
            $table->foreignUuid('domaine_formation_id')->constrained('domaines_formation');
            $table->foreignUuid('metier_id')->constrained('metiers');
            $table->foreignUuid('niveau_formation_id')->constrained('niveaux_formation');
            $table->enum('statut', ['EN_COURS', 'ARCHIVE'])->default('EN_COURS');
            $table->string('code_rattachement_utilise')->nullable();
            $table->timestamp('date_rattachement')->nullable();
            $table->boolean('autorisation_suivi')->default(false);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamp('date_creation')->useCurrent();
            
            $table->index('stagiaire_id');
            $table->index('entreprise_id');
        });

        Schema::create('entrees_carnet', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->enum('type', ['PRESENCE', 'MISSION', 'DIFFICULTE', 'NOTE_LIBRE']);
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->decimal('position_lat', 10, 7)->nullable();
            $table->decimal('position_lng', 10, 7)->nullable();
            $table->enum('source_validation', ['AUTOMATIQUE', 'MANUELLE']);
            $table->text('commentaire_stagiaire')->nullable();
            $table->text('commentaire_tuteur')->nullable();
            $table->uuid('session_id')->nullable();
            $table->enum('statut_cloture', ['EN_ATTENTE', 'PAUSE_CONFIRMEE', 'DEPART_CONFIRME'])->default('EN_ATTENTE');
        });

        Schema::create('progression_competences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->foreignUuid('competence_id')->constrained('competences');
            $table->decimal('heures_cumulees', 8, 2)->default(0);
            $table->enum('niveau_auto', ['NON_ABORDEE', 'DECOUVERTE', 'EN_COURS', 'MAITRISEE'])->default('NON_ABORDEE');
            $table->enum('niveau_stagiaire', ['NON_ABORDEE', 'DECOUVERTE', 'EN_COURS', 'MAITRISEE'])->nullable();
            $table->enum('niveau_tuteur', ['NON_ABORDEE', 'DECOUVERTE', 'EN_COURS', 'MAITRISEE'])->nullable();
            $table->text('appreciation_tuteur')->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
            $table->unique(['carnet_id', 'competence_id']);
        });

        Schema::create('indicateurs_assiduite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->unique()->constrained('carnets_de_stage');
            $table->integer('jours_presents')->default(0);
            $table->integer('jours_attendus')->default(0);
            $table->decimal('heures_totales_realisees', 8, 2)->default(0);
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });

        Schema::create('evaluations_savoir_etre', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->foreignUuid('critere_id')->constrained('criteres_savoir_etre');
            $table->enum('niveau', ['A_AMELIORER', 'SATISFAISANT', 'REMARQUABLE']);
            $table->text('commentaire')->nullable();
            $table->timestamp('date_evaluation')->useCurrent();
            $table->unique(['carnet_id', 'critere_id']);
        });

        Schema::create('bilans_reflexifs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->string('periode', 50);
            $table->text('contenu');
            $table->timestamp('date_creation')->useCurrent();
        });

        // ==================== INVITATION ====================
        Schema::create('fiches_stagiaire_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entreprise_id')->constrained('entreprises');
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('code_invitation')->unique();
            $table->boolean('utilise')->default(false);
            $table->foreignUuid('carnet_id')->nullable()->constrained('carnets_de_stage');
            $table->timestamp('date_generation')->useCurrent();
            $table->timestamp('date_expiration')->nullable();
        });

        // ==================== EVALUATION / ATTESTATION / CARTE D'APPUI ====================
        Schema::create('evaluations_competence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->foreignUuid('entreprise_id')->constrained('entreprises');
            $table->foreignUuid('indicateur_assiduite_id')->nullable()->constrained('indicateurs_assiduite');
            $table->boolean('jugee_utile')->default(false);
            $table->timestamp('date_evaluation')->useCurrent();
        });

        Schema::create('attestations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluation_id')->constrained('evaluations_competence');
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->foreignUuid('stagiaire_id')->constrained('stagiaires');
            $table->string('document_genere')->nullable();
            $table->timestamp('date_generation')->useCurrent();
        });

        Schema::create('cartes_appui_stage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluation_id')->nullable()->constrained('evaluations_competence');
            $table->foreignUuid('carnet_id')->nullable()->constrained('carnets_de_stage');
            $table->foreignUuid('entreprise_emettrice_id')->constrained('entreprises');
            $table->string('entreprise_destinataire_nom');
            $table->string('entreprise_destinataire_email');
            $table->text('recommandation')->nullable();
            $table->string('document_genere')->nullable();
            $table->timestamp('date_generation')->useCurrent();
            $table->timestamps();
            
            $table->index('evaluation_id');
            $table->index('carnet_id');
        });

        // ==================== NOTIFICATIONS D'ENCOURAGEMENT ====================
        Schema::create('notifications_encouragement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carnet_id')->constrained('carnets_de_stage');
            $table->foreignUuid('entreprise_id')->constrained('entreprises');
            $table->enum('type', ['ENCOURAGEMENT', 'FELICITATION']);
            $table->enum('origine', ['MANUELLE', 'AUTOMATIQUE'])->default('MANUELLE');
            $table->text('contenu');
            $table->boolean('lu')->default(false);
            $table->timestamp('date_envoi')->useCurrent();
        });

        // ==================== COVOITURAGE ====================
        Schema::create('trajets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conducteur_id')->constrained('stagiaires');
            $table->decimal('depart_lat', 10, 7);
            $table->decimal('depart_lng', 10, 7);
            $table->decimal('arrivee_lat', 10, 7);
            $table->decimal('arrivee_lng', 10, 7);
            $table->time('heure_depart');
            $table->json('jours_recurrence');
            $table->smallInteger('places_disponibles');
            $table->enum('statut', ['ACTIF', 'SUSPENDU', 'TERMINE'])->default('ACTIF');
            $table->timestamp('date_creation')->useCurrent();
            
            $table->index('conducteur_id');
            $table->index('statut');
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trajet_id')->constrained('trajets');
            $table->foreignUuid('passager_id')->constrained('stagiaires');
            $table->enum('statut', ['EN_ATTENTE', 'CONFIRMEE', 'ANNULEE'])->default('EN_ATTENTE');
            $table->timestamp('date_creation')->useCurrent();
            $table->unique(['trajet_id', 'passager_id']);
            
            $table->index('trajet_id');
            $table->index('passager_id');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trajet_id')->constrained('trajets');
            $table->foreignUuid('auteur_id')->constrained('stagiaires');
            $table->text('contenu');
            $table->boolean('lu')->default(false);
            $table->timestamp('date_envoi')->useCurrent();
            
            $table->index('trajet_id');
        });

        Schema::create('signalements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auteur_id')->constrained('stagiaires');
            $table->foreignUuid('trajet_id')->constrained('trajets');
            $table->string('motif');
            $table->text('description')->nullable();
            $table->enum('statut', ['OUVERT', 'EN_COURS', 'TRAITE', 'REJETE'])->default('OUVERT');
            $table->timestamp('date_creation')->useCurrent();
            
            $table->index('trajet_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        // Ordre INVERSE pour respecter les contraintes
        Schema::dropIfExists('signalements');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('trajets');
        Schema::dropIfExists('notifications_encouragement');
        Schema::dropIfExists('cartes_appui_stage');
        Schema::dropIfExists('attestations');
        Schema::dropIfExists('evaluations_competence');
        Schema::dropIfExists('fiches_stagiaire_invite');
        Schema::dropIfExists('bilans_reflexifs');
        Schema::dropIfExists('evaluations_savoir_etre');
        Schema::dropIfExists('indicateurs_assiduite');
        Schema::dropIfExists('progression_competences');
        Schema::dropIfExists('entrees_carnet');
        Schema::dropIfExists('carnets_de_stage');
        Schema::dropIfExists('criteres_savoir_etre');
        Schema::dropIfExists('competences');
        Schema::dropIfExists('niveaux_formation');
        Schema::dropIfExists('metiers');
        Schema::dropIfExists('domaines_formation');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('verification_codes');
        Schema::dropIfExists('entreprises');
        Schema::dropIfExists('stagiaires');
        Schema::dropIfExists('users');
    }
};