-- ============================================================
-- Schéma de base de données — Application mobile d'orientation vers le stage
-- Moteur : MySQL 8+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==================== STAGIAIRES ====================
CREATE TABLE stagiaires (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    photo_profil VARCHAR(500) NULL,
    domicile_adresse VARCHAR(255) NULL,
    domicile_lat DECIMAL(10,7) NULL,
    domicile_lng DECIMAL(10,7) NULL,
    autorisation_entraide BOOLEAN NOT NULL DEFAULT FALSE,
    date_premiere_connexion TIMESTAMP NULL,
    derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== ENTREPRISES ====================
CREATE TABLE entreprises (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) NOT NULL UNIQUE,
    raison_sociale VARCHAR(150) NULL,
    secteur VARCHAR(100) NULL,
    adresse_libelle VARCHAR(255) NULL,
    adresse_lat DECIMAL(10,7) NULL,
    adresse_lng DECIMAL(10,7) NULL,
    rayon_detection_metres INT NOT NULL DEFAULT 100,
    heure_debut_journee TIME NULL,
    heure_fin_journee TIME NULL,
    pause_heure_debut TIME NULL,
    pause_heure_fin TIME NULL,
    date_creation_compte TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== CODES DE CONFIRMATION ====================
CREATE TABLE code_confirmation_stagiaire (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    stagiaire_id CHAR(36) NULL,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(10) NOT NULL,
    utilise BOOLEAN NOT NULL DEFAULT FALSE,
    date_generation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration TIMESTAMP NOT NULL,
    FOREIGN KEY (stagiaire_id) REFERENCES stagiaires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE code_confirmation_entreprise (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    entreprise_id CHAR(36) NOT NULL,
    code VARCHAR(10) NOT NULL,
    utilise BOOLEAN NOT NULL DEFAULT FALSE,
    date_generation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration TIMESTAMP NOT NULL,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== REFERENTIEL ====================
CREATE TABLE domaines_formation (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE metiers (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    domaine_formation_id CHAR(36) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_metier_domaine_nom (domaine_formation_id, nom),
    FOREIGN KEY (domaine_formation_id) REFERENCES domaines_formation(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE niveaux_formation (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    nom VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE competences (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    metier_id CHAR(36) NOT NULL,
    entreprise_id CHAR(36) NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    seuil_decouverte DECIMAL(6,2) NOT NULL,
    seuil_maitrise DECIMAL(6,2) NOT NULL,
    mots_cles_detection JSON NULL,
    FOREIGN KEY (metier_id) REFERENCES metiers(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE criteres_savoir_etre (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    entreprise_id CHAR(36) NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_critere_entreprise_nom (entreprise_id, nom),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== CARNET DE STAGE ====================
CREATE TABLE carnets_de_stage (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    stagiaire_id CHAR(36) NOT NULL,
    entreprise_id CHAR(36) NULL,
    domaine_formation_id CHAR(36) NOT NULL,
    metier_id CHAR(36) NOT NULL,
    niveau_formation_id CHAR(36) NOT NULL,
    statut ENUM('EN_COURS','ARCHIVE') NOT NULL DEFAULT 'EN_COURS',
    code_rattachement_utilise VARCHAR(20) NULL,
    date_rattachement TIMESTAMP NULL,
    autorisation_suivi BOOLEAN NOT NULL DEFAULT FALSE,
    date_debut DATE NULL,
    date_fin DATE NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stagiaire_id) REFERENCES stagiaires(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (domaine_formation_id) REFERENCES domaines_formation(id),
    FOREIGN KEY (metier_id) REFERENCES metiers(id),
    FOREIGN KEY (niveau_formation_id) REFERENCES niveaux_formation(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE entrees_carnet (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    type ENUM('PRESENCE','MISSION','DIFFICULTE','NOTE_LIBRE') NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    position_lat DECIMAL(10,7) NULL,
    position_lng DECIMAL(10,7) NULL,
    source_validation ENUM('AUTOMATIQUE','MANUELLE') NOT NULL,
    commentaire_stagiaire TEXT NULL,
    commentaire_tuteur TEXT NULL,
    session_id CHAR(36) NULL,
    statut_cloture ENUM('EN_ATTENTE','PAUSE_CONFIRMEE','DEPART_CONFIRME') NOT NULL DEFAULT 'EN_ATTENTE',
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE progression_competences (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    competence_id CHAR(36) NOT NULL,
    heures_cumulees DECIMAL(8,2) NOT NULL DEFAULT 0,
    niveau_auto ENUM('NON_ABORDEE','DECOUVERTE','EN_COURS','MAITRISEE') NOT NULL DEFAULT 'NON_ABORDEE',
    niveau_stagiaire ENUM('NON_ABORDEE','DECOUVERTE','EN_COURS','MAITRISEE') NULL,
    niveau_tuteur ENUM('NON_ABORDEE','DECOUVERTE','EN_COURS','MAITRISEE') NULL,
    appreciation_tuteur TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progression_carnet_competence (carnet_id, competence_id),
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (competence_id) REFERENCES competences(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE indicateurs_assiduite (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL UNIQUE,
    jours_presents INT NOT NULL DEFAULT 0,
    jours_attendus INT NOT NULL DEFAULT 0,
    heures_totales_realisees DECIMAL(8,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE evaluations_savoir_etre (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    critere_id CHAR(36) NOT NULL,
    niveau ENUM('A_AMELIORER','SATISFAISANT','REMARQUABLE') NOT NULL,
    commentaire TEXT NULL,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_savoir_etre_carnet_critere (carnet_id, critere_id),
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (critere_id) REFERENCES criteres_savoir_etre(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bilans_reflexifs (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    periode VARCHAR(50) NOT NULL,
    contenu TEXT NOT NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== INVITATION ====================
CREATE TABLE fiches_stagiaire_invite (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    entreprise_id CHAR(36) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    code_invitation VARCHAR(20) NOT NULL UNIQUE,
    utilise BOOLEAN NOT NULL DEFAULT FALSE,
    carnet_id CHAR(36) NULL,
    date_generation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration TIMESTAMP NULL,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== EVALUATION / ATTESTATION / CARTE D'APPUI ====================
CREATE TABLE evaluations_competence (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    entreprise_id CHAR(36) NOT NULL,
    indicateur_assiduite_id CHAR(36) NULL,
    jugee_utile BOOLEAN NOT NULL DEFAULT FALSE,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (indicateur_assiduite_id) REFERENCES indicateurs_assiduite(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attestations (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    evaluation_id CHAR(36) NOT NULL,
    carnet_id CHAR(36) NOT NULL,
    stagiaire_id CHAR(36) NOT NULL,
    document_genere VARCHAR(500) NULL,
    date_generation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations_competence(id),
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (stagiaire_id) REFERENCES stagiaires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cartes_appui_stage (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    evaluation_id CHAR(36) NOT NULL,
    carnet_id CHAR(36) NOT NULL,
    entreprise_emettrice_id CHAR(36) NOT NULL,
    entreprise_destinataire_nom VARCHAR(150) NOT NULL,
    entreprise_destinataire_email VARCHAR(255) NOT NULL,
    recommandation TEXT NULL,
    document_genere VARCHAR(500) NULL,
    date_generation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations_competence(id),
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (entreprise_emettrice_id) REFERENCES entreprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== NOTIFICATIONS D'ENCOURAGEMENT ====================
CREATE TABLE notifications_encouragement (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    carnet_id CHAR(36) NOT NULL,
    entreprise_id CHAR(36) NOT NULL,
    type ENUM('ENCOURAGEMENT','FELICITATION') NOT NULL,
    origine ENUM('MANUELLE','AUTOMATIQUE') NOT NULL DEFAULT 'MANUELLE',
    contenu TEXT NOT NULL,
    lu BOOLEAN NOT NULL DEFAULT FALSE,
    date_envoi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carnet_id) REFERENCES carnets_de_stage(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== COVOITURAGE ====================
CREATE TABLE trajets (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    conducteur_id CHAR(36) NOT NULL,
    depart_lat DECIMAL(10,7) NOT NULL,
    depart_lng DECIMAL(10,7) NOT NULL,
    arrivee_lat DECIMAL(10,7) NOT NULL,
    arrivee_lng DECIMAL(10,7) NOT NULL,
    heure_depart TIME NOT NULL,
    jours_recurrence JSON NOT NULL,
    places_disponibles SMALLINT NOT NULL,
    statut ENUM('ACTIF','SUSPENDU','TERMINE') NOT NULL DEFAULT 'ACTIF',
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conducteur_id) REFERENCES stagiaires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservations (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    trajet_id CHAR(36) NOT NULL,
    passager_id CHAR(36) NOT NULL,
    statut ENUM('EN_ATTENTE','CONFIRMEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reservation_trajet_passager (trajet_id, passager_id),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id),
    FOREIGN KEY (passager_id) REFERENCES stagiaires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    trajet_id CHAR(36) NOT NULL,
    auteur_id CHAR(36) NOT NULL,
    contenu TEXT NOT NULL,
    lu BOOLEAN NOT NULL DEFAULT FALSE,
    date_envoi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trajet_id) REFERENCES trajets(id),
    FOREIGN KEY (auteur_id) REFERENCES stagiaires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE signalements (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    auteur_id CHAR(36) NOT NULL,
    trajet_id CHAR(36) NOT NULL,
    motif VARCHAR(100) NOT NULL,
    description TEXT NULL,
    statut ENUM('OUVERT','EN_COURS','TRAITE','REJETE') NOT NULL DEFAULT 'OUVERT',
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES stagiaires(id),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
