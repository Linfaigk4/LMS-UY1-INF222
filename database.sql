-- =====================================================
-- GOL (Gugle Online Learning) - Base de données complète
-- Version: 1.0
-- =====================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gol_lms;
USE gol_lms;

-- =====================================================
-- TABLE: utilisateurs (système d'authentification principal)
-- =====================================================
CREATE TABLE utilisateurs (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'promoteur', 'enseignant', 'etudiant') NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'suspendu', 'en_attente') DEFAULT 'actif',
    avatar VARCHAR(255) DEFAULT 'default-avatar.svg',
    derniere_connexion DATETIME,
    telephone VARCHAR(20),
    bio TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: demandes_modification (validation admin)
-- =====================================================
CREATE TABLE demandes_modification (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    type_demande ENUM('mot_de_passe', 'email', 'telephone', 'nom_complet') NOT NULL,
    ancienne_valeur TEXT,
    nouvelle_valeur TEXT NOT NULL,
    justification TEXT,
    statut ENUM('en_attente', 'approuvee', 'refusee') DEFAULT 'en_attente',
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME,
    id_admin_traitant INT,
    commentaire_admin TEXT,
    INDEX idx_utilisateur (id_utilisateur),
    INDEX idx_statut (statut),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_admin_traitant) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: modules (structure pédagogique)
-- =====================================================
CREATE TABLE modules (
    id_module INT PRIMARY KEY AUTO_INCREMENT,
    nom_module VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    objectifs TEXT,
    image_principale VARCHAR(255),
    id_promoteur INT NOT NULL,
    id_super_admin INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    ordre_affichage INT DEFAULT 0,
    duree_totale INT COMMENT 'Durée en minutes',
    niveau ENUM('debutant', 'intermediaire', 'avance', 'expert') DEFAULT 'debutant',
    INDEX idx_promoteur (id_promoteur),
    INDEX idx_slug (slug),
    FOREIGN KEY (id_promoteur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_super_admin) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: inscriptions_modules (étudiants inscrits aux modules)
-- =====================================================
CREATE TABLE inscriptions_modules (
    id_inscription INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_module INT NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    progression_globale DECIMAL(5,2) DEFAULT 0,
    statut ENUM('inscrit', 'en_cours', 'termine', 'abandonne') DEFAULT 'inscrit',
    date_completion DATETIME,
    INDEX idx_utilisateur_module (id_utilisateur, id_module),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    UNIQUE KEY unique_inscription (id_utilisateur, id_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: cours
-- =====================================================
CREATE TABLE cours (
    id_cours INT PRIMARY KEY AUTO_INCREMENT,
    titre_cours VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    objectifs TEXT,
    difficulte ENUM('debutant', 'intermediaire', 'avance', 'expert') DEFAULT 'debutant',
    duree INT COMMENT 'Durée en minutes',
    id_module INT NOT NULL,
    id_enseignant INT NOT NULL,
    statut ENUM('brouillon', 'publie', 'archive') DEFAULT 'brouillon',
    ordre_affichage INT DEFAULT 0,
    image_principale VARCHAR(255),
    prerequis TEXT,
    INDEX idx_module (id_module),
    INDEX idx_enseignant (id_enseignant),
    INDEX idx_slug (slug),
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    FOREIGN KEY (id_enseignant) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: lecons
-- =====================================================
CREATE TABLE lecons (
    id_lecon INT PRIMARY KEY AUTO_INCREMENT,
    titre_lecon VARCHAR(255) NOT NULL,
    contenu_texte LONGTEXT,
    fichier_pdf VARCHAR(255),
    url_video VARCHAR(500),
    duree INT COMMENT 'Durée en minutes',
    id_cours INT NOT NULL,
    ordre_affichage INT DEFAULT 0,
    type_contenu ENUM('texte', 'pdf', 'video', 'mixte') DEFAULT 'texte',
    est_gratuite BOOLEAN DEFAULT FALSE,
    INDEX idx_cours (id_cours),
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: evaluations
-- =====================================================
CREATE TABLE evaluations (
    id_evaluation INT PRIMARY KEY AUTO_INCREMENT,
    titre_evaluation VARCHAR(255) NOT NULL,
    description TEXT,
    note_requise DECIMAL(5,2) DEFAULT 60,
    duree INT COMMENT 'Durée en minutes',
    id_lecon INT NOT NULL,
    tentative_max INT DEFAULT 3,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    INDEX idx_lecon (id_lecon),
    FOREIGN KEY (id_lecon) REFERENCES lecons(id_lecon) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: questions
-- =====================================================
CREATE TABLE questions (
    id_question INT PRIMARY KEY AUTO_INCREMENT,
    texte_question TEXT NOT NULL,
    type_question ENUM('qcm', 'qcu', 'texte', 'numerique') DEFAULT 'qcm',
    points INT DEFAULT 1,
    id_evaluation INT NOT NULL,
    explication TEXT,
    ordre_affichage INT DEFAULT 0,
    INDEX idx_evaluation (id_evaluation),
    FOREIGN KEY (id_evaluation) REFERENCES evaluations(id_evaluation) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: options
-- =====================================================
CREATE TABLE options (
    id_option INT PRIMARY KEY AUTO_INCREMENT,
    texte_option TEXT NOT NULL,
    est_correcte BOOLEAN DEFAULT FALSE,
    id_question INT NOT NULL,
    points_option DECIMAL(5,2) DEFAULT 0,
    INDEX idx_question (id_question),
    FOREIGN KEY (id_question) REFERENCES questions(id_question) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: resultats_evaluations
-- =====================================================
CREATE TABLE resultats_evaluations (
    id_resultat INT PRIMARY KEY AUTO_INCREMENT,
    score DECIMAL(5,2) NOT NULL,
    note DECIMAL(5,2),
    reponses_json JSON,
    date_completion DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NOT NULL,
    id_evaluation INT NOT NULL,
    tentative_numero INT DEFAULT 1,
    temps_consacre INT COMMENT 'Temps en secondes',
    INDEX idx_utilisateur (id_utilisateur),
    INDEX idx_evaluation (id_evaluation),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_evaluation) REFERENCES evaluations(id_evaluation) ON DELETE CASCADE,
    UNIQUE KEY unique_tentative (id_utilisateur, id_evaluation, tentative_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: progression_cours
-- =====================================================
CREATE TABLE progression_cours (
    id_progression INT PRIMARY KEY AUTO_INCREMENT,
    pourcentage DECIMAL(5,2) DEFAULT 0,
    dernier_acces DATETIME DEFAULT CURRENT_TIMESTAMP,
    lecons_terminees JSON,
    notes_moyennes DECIMAL(5,2),
    id_utilisateur INT NOT NULL,
    id_cours INT NOT NULL,
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('non_commence', 'en_cours', 'termine', 'abandonne') DEFAULT 'non_commence',
    INDEX idx_utilisateur_cours (id_utilisateur, id_cours),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE,
    UNIQUE KEY unique_progression (id_utilisateur, id_cours)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: certificats
-- =====================================================
CREATE TABLE certificats (
    id_certificat INT PRIMARY KEY AUTO_INCREMENT,
    code_unique VARCHAR(100) UNIQUE NOT NULL,
    date_emission DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME,
    statut ENUM('valide', 'expire', 'revoke') DEFAULT 'valide',
    id_utilisateur INT NOT NULL,
    id_module INT NOT NULL,
    note_obtenue DECIMAL(5,2),
    fichier_pdf VARCHAR(255),
    INDEX idx_utilisateur (id_utilisateur),
    INDEX idx_module (id_module),
    INDEX idx_code (code_unique),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    UNIQUE KEY unique_certificat (id_utilisateur, id_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: notifications
-- =====================================================
CREATE TABLE notifications (
    id_notification INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'succes', 'avertissement', 'danger') DEFAULT 'info',
    est_lue BOOLEAN DEFAULT FALSE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    lien_action VARCHAR(500),
    INDEX idx_utilisateur_lu (id_utilisateur, est_lue),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: logs_activite
-- =====================================================
CREATE TABLE logs_activite (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT,
    action VARCHAR(255) NOT NULL,
    details JSON,
    ip_adresse VARCHAR(45),
    user_agent TEXT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur_date (id_utilisateur, date_action),
    INDEX idx_action (action),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTION SUPER ADMIN
-- Mot de passe: loi770Messi2026 (hashé)
-- =====================================================
INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, statut, bio) 
VALUES (
    'superadmin@gol.com', 
    '$2y$12$YGxQrgqSuWwa1qSwulSx8uu1r7t2E2vd68CLfhOzOYeTzqFNoafzq',
    'SYSTEM', 
    'GOL', 
    'super_admin', 
    'actif',
    'Administrateur principal de la plateforme GOL (Gugle Online Learning) - En charge de la validation des modifications et de la gestion globale.'
);

-- =====================================================
-- INDEX POUR PERFORMANCES
-- =====================================================
CREATE INDEX idx_cours_statut ON cours(statut);
CREATE INDEX idx_module_actif ON modules(actif);
CREATE INDEX idx_lecon_ordre ON lecons(ordre_affichage);
CREATE INDEX idx_evaluation_actif ON evaluations(actif);
CREATE INDEX idx_resultats_date ON resultats_evaluations(date_completion);
CREATE INDEX idx_progression_statut ON progression_cours(statut);
CREATE INDEX idx_notifications_lues ON notifications(est_lue, date_creation);

-- =====================================================
-- DONNÉES DE DÉMONSTRATION (optionnelles)
-- =====================================================

-- Insertion d'un module de démonstration
INSERT INTO modules (nom_module, slug, description, objectifs, id_promoteur, actif, niveau) 
SELECT 
    'Développement Web Moderne',
    'dev-web-moderne',
    'Apprenez à créer des sites web modernes avec les dernières technologies',
    'Maîtriser HTML5, CSS3, JavaScript moderne et les bonnes pratiques',
    1,
    TRUE,
    'debutant'
WHERE EXISTS (SELECT 1 FROM utilisateurs WHERE id_utilisateur = 1);

-- Insertion d'un cours de démonstration
INSERT INTO cours (titre_cours, slug, description, id_module, id_enseignant, statut, difficulte, duree)
SELECT 
    'Introduction au HTML5 et CSS3',
    'intro-html-css',
    'Les fondamentaux du web moderne',
    (SELECT id_module FROM modules WHERE slug = 'dev-web-moderne' LIMIT 1),
    1,
    'publie',
    'debutant',
    120
WHERE EXISTS (SELECT 1 FROM utilisateurs WHERE id_utilisateur = 1);
