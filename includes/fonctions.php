<?php
/**
 * GOL (Gugle Online Learning) - Fonctions réutilisables
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once __DIR__ . '/config.php';

// ============================================
// FONCTIONS D'AUTHENTIFICATION
// ============================================

function connexionBDD() {
    global $pdo;
    return $pdo;
}

function estConnecte() {
    return isset($_SESSION['id_utilisateur']);
}

function estSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

function estPromoteur() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'promoteur';
}

function estEnseignant() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'enseignant';
}

function estEtudiant() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant';
}

function obtenirUtilisateur($id = null) {
    global $pdo;
    $id = $id ?? $_SESSION['id_utilisateur'] ?? null;
    if (!$id) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function connecterUtilisateur($email, $mot_de_passe) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM utilisateurs 
            WHERE email = ? AND statut = 'actif'
        ");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();
        
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['role'] = $utilisateur['role'];
            
            $stmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id_utilisateur = ?");
            $stmt->execute([$utilisateur['id_utilisateur']]);
            
            return ['success' => true, 'role' => $utilisateur['role']];
        }
        
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur de connexion'];
    }
}

function inscrireUtilisateur($email, $mot_de_passe, $nom, $prenom, $role) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, statut)
        VALUES (?, ?, ?, ?, ?, 'actif')
    ");
    
    if ($stmt->execute([$email, $hash, $nom, $prenom, $role])) {
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
}

function deconnecterUtilisateur() {
    session_destroy();
    return true;
}

// ============================================
// FONCTIONS NOTIFICATIONS
// ============================================

function obtenirNotificationsNonLues($id_utilisateur) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE id_utilisateur = ? AND est_lue = 0
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$id_utilisateur]);
    return $stmt->fetchAll();
}

function marquerNotificationLue($id_notification) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET est_lue = 1 WHERE id_notification = ?");
    return $stmt->execute([$id_notification]);
}

function ajouterNotification($id_utilisateur, $titre, $message, $type = 'info', $lien = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (id_utilisateur, titre, message, type, lien_action)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$id_utilisateur, $titre, $message, $type, $lien]);
}

// ============================================
// FONCTIONS MODULES
// ============================================

function obtenirModules($actif = true) {
    global $pdo;
    
    $sql = "SELECT m.*, COUNT(c.id_cours) as nb_cours 
            FROM modules m
            LEFT JOIN cours c ON m.id_module = c.id_module";
    
    if ($actif) {
        $sql .= " WHERE m.actif = 1";
    }
    
    $sql .= " GROUP BY m.id_module ORDER BY m.ordre_affichage";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function obtenirModule($id_module) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id_module = ?");
    $stmt->execute([$id_module]);
    return $stmt->fetch();
}

function ajouterModule($nom_module, $description, $objectifs, $id_promoteur, $niveau = 'debutant', $image = null) {
    global $pdo;
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom_module)));
    
    $stmt = $pdo->prepare("
        INSERT INTO modules (nom_module, slug, description, objectifs, image_principale, id_promoteur, niveau)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$nom_module, $slug, $description, $objectifs, $image, $id_promoteur, $niveau]);
}

function modifierModule($id_module, $nom_module, $description, $objectifs, $niveau) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE modules 
        SET nom_module = ?, description = ?, objectifs = ?, niveau = ?
        WHERE id_module = ?
    ");
    
    return $stmt->execute([$nom_module, $description, $objectifs, $niveau, $id_module]);
}

function supprimerModule($id_module) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id_module = ?");
    return $stmt->execute([$id_module]);
}

// ============================================
// FONCTIONS COURS
// ============================================

function obtenirCours($id_cours = null) {
    global $pdo;
    
    if ($id_cours) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                   m.nom_module, m.id_module
            FROM cours c
            JOIN utilisateurs u ON c.id_enseignant = u.id_utilisateur
            JOIN modules m ON c.id_module = m.id_module
            WHERE c.id_cours = ?
        ");
        $stmt->execute([$id_cours]);
        return $stmt->fetch();
    }
    
    $stmt = $pdo->query("
        SELECT c.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
               m.nom_module, COUNT(l.id_lecon) as nb_lecons
        FROM cours c
        JOIN utilisateurs u ON c.id_enseignant = u.id_utilisateur
        JOIN modules m ON c.id_module = m.id_module
        LEFT JOIN lecons l ON c.id_cours = l.id_cours
        WHERE c.statut = 'publie'
        GROUP BY c.id_cours
        ORDER BY c.id_cours DESC
    ");
    return $stmt->fetchAll();
}

function obtenirCoursParModule($id_module) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
               COUNT(l.id_lecon) as nb_lecons
        FROM cours c
        JOIN utilisateurs u ON c.id_enseignant = u.id_utilisateur
        LEFT JOIN lecons l ON c.id_cours = l.id_cours
        WHERE c.id_module = ? AND c.statut = 'publie'
        GROUP BY c.id_cours
        ORDER BY c.ordre_affichage
    ");
    $stmt->execute([$id_module]);
    return $stmt->fetchAll();
}

function obtenirCoursParEnseignant($id_enseignant) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT pc.id_utilisateur) as nb_etudiants,
               COUNT(DISTINCT l.id_lecon) as nb_lecons
        FROM cours c
        LEFT JOIN progression_cours pc ON c.id_cours = pc.id_cours
        LEFT JOIN lecons l ON c.id_cours = l.id_cours
        WHERE c.id_enseignant = ?
        GROUP BY c.id_cours
        ORDER BY c.id_cours DESC
    ");
    $stmt->execute([$id_enseignant]);
    return $stmt->fetchAll();
}

/**
 * Génère un slug unique pour un cours.
 * Si "cours-test" existe, retourne "cours-test-2", "cours-test-3", etc.
 */
function genererSlugUnique(string $titre, int $id_cours_exclu = 0): string {
    global $pdo;
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
    $base = trim($base, '-');
    $slug = $base;
    $compteur = 1;
    do {
        $stmt = $pdo->prepare("SELECT id_cours FROM cours WHERE slug = ? AND id_cours != ?");
        $stmt->execute([$slug, $id_cours_exclu]);
        if ($stmt->fetch()) {
            $compteur++;
            $slug = $base . '-' . $compteur;
        } else {
            break;
        }
    } while (true);
    return $slug;
}

function ajouterCours($titre, $description, $objectifs, $difficulte, $duree, $id_module, $id_enseignant, $prerequis = null) {
    global $pdo;
    $slug = genererSlugUnique($titre);
    $stmt = $pdo->prepare("
        INSERT INTO cours (titre_cours, slug, description, objectifs, difficulte, duree, id_module, id_enseignant, prerequis, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'brouillon')
    ");
    return $stmt->execute([$titre, $slug, $description, $objectifs, $difficulte, $duree, $id_module, $id_enseignant, $prerequis]);
}

function modifierCours($id_cours, $titre, $description, $objectifs, $difficulte, $duree, $prerequis) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE cours 
        SET titre_cours = ?, description = ?, objectifs = ?, difficulte = ?, duree = ?, prerequis = ?
        WHERE id_cours = ?
    ");
    
    return $stmt->execute([$titre, $description, $objectifs, $difficulte, $duree, $prerequis, $id_cours]);
}

function supprimerCours($id_cours) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM cours WHERE id_cours = ?");
    return $stmt->execute([$id_cours]);
}

function publierCours($id_cours) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE cours SET statut = 'publie' WHERE id_cours = ?");
    return $stmt->execute([$id_cours]);
}

// ============================================
// FONCTIONS LECONS
// ============================================

function obtenirLecons($id_cours) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.*, 
               (SELECT COUNT(*) FROM evaluations WHERE id_lecon = l.id_lecon) as a_evaluation
        FROM lecons l
        WHERE l.id_cours = ?
        ORDER BY l.ordre_affichage
    ");
    $stmt->execute([$id_cours]);
    return $stmt->fetchAll();
}

function obtenirLecon($id_lecon) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.*, c.titre_cours, c.id_cours
        FROM lecons l
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE l.id_lecon = ?
    ");
    $stmt->execute([$id_lecon]);
    return $stmt->fetch();
}

function ajouterLecon($titre, $contenu_texte, $type_contenu, $id_cours, $duree = null, $fichier_pdf = null, $url_video = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT MAX(ordre_affichage) as max_ordre FROM lecons WHERE id_cours = ?");
    $stmt->execute([$id_cours]);
    $ordre = ($stmt->fetch()['max_ordre'] ?? 0) + 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO lecons (titre_lecon, contenu_texte, type_contenu, fichier_pdf, url_video, duree, id_cours, ordre_affichage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$titre, $contenu_texte, $type_contenu, $fichier_pdf, $url_video, $duree, $id_cours, $ordre]);
}

function modifierLecon($id_lecon, $titre, $contenu_texte, $type_contenu, $duree = null, $fichier_pdf = null, $url_video = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE lecons 
        SET titre_lecon = ?, contenu_texte = ?, type_contenu = ?, fichier_pdf = ?, url_video = ?, duree = ?
        WHERE id_lecon = ?
    ");
    
    return $stmt->execute([$titre, $contenu_texte, $type_contenu, $fichier_pdf, $url_video, $duree, $id_lecon]);
}

/**
 * Supprime une leçon — vérifie que la leçon appartient à l'enseignant connecté.
 * Super Admin bypass la vérification.
 * Retourne false si IDOR détecté.
 */
function supprimerLecon($id_lecon, $id_enseignant = null) {
    global $pdo;
    if ($id_enseignant !== null) {
        // Vérifier propriété : leçon → cours → enseignant
        $stmt = $pdo->prepare("
            SELECT l.id_lecon FROM lecons l
            JOIN cours c ON l.id_cours = c.id_cours
            WHERE l.id_lecon = ? AND c.id_enseignant = ?
        ");
        $stmt->execute([$id_lecon, $id_enseignant]);
        if (!$stmt->fetch()) return false; // IDOR bloqué
    }
    $stmt = $pdo->prepare("DELETE FROM lecons WHERE id_lecon = ?");
    return $stmt->execute([$id_lecon]);
}

function marquerLeconTerminee($id_utilisateur, $id_lecon) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id_cours FROM lecons WHERE id_lecon = ?");
    $stmt->execute([$id_lecon]);
    $lecon = $stmt->fetch();
    
    if (!$lecon) return false;
    
    $id_cours = $lecon['id_cours'];
    
    $stmt = $pdo->prepare("SELECT * FROM progression_cours WHERE id_utilisateur = ? AND id_cours = ?");
    $stmt->execute([$id_utilisateur, $id_cours]);
    $progression = $stmt->fetch();
    
    if (!$progression) {
        $stmt = $pdo->prepare("
            INSERT INTO progression_cours (id_utilisateur, id_cours, lecons_terminees, pourcentage, statut)
            VALUES (?, ?, ?, 0, 'en_cours')
        ");
        $stmt->execute([$id_utilisateur, $id_cours, json_encode([])]);
        
        $stmt = $pdo->prepare("SELECT * FROM progression_cours WHERE id_utilisateur = ? AND id_cours = ?");
        $stmt->execute([$id_utilisateur, $id_cours]);
        $progression = $stmt->fetch();
    }
    
    $lecons_terminees = json_decode($progression['lecons_terminees'], true) ?: [];
    if (!in_array($id_lecon, $lecons_terminees)) {
        $lecons_terminees[] = $id_lecon;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lecons WHERE id_cours = ?");
    $stmt->execute([$id_cours]);
    $total_lecons = $stmt->fetch()['total'];
    
    $pourcentage = ($total_lecons > 0) ? (count($lecons_terminees) / $total_lecons) * 100 : 0;
    $statut = ($pourcentage >= 100) ? 'termine' : 'en_cours';
    
    $stmt = $pdo->prepare("
        UPDATE progression_cours 
        SET lecons_terminees = ?, pourcentage = ?, statut = ?, dernier_acces = NOW()
        WHERE id_progression = ?
    ");
    
    return $stmt->execute([json_encode($lecons_terminees), $pourcentage, $statut, $progression['id_progression']]);
}

// ============================================
// FONCTIONS PROGRESSION (nouvelle logique 0/50/100)
// ============================================

/**
 * Enregistre l'ouverture d'une leçon par un étudiant (statut 50).
 * Utilise INSERT ... ON DUPLICATE KEY pour ne pas rétrograder un statut 100.
 */
function ouvrirLecon($id_utilisateur, $id_lecon) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO progression_lecons (id_utilisateur, id_lecon, statut)
        VALUES (?, ?, 50)
        ON DUPLICATE KEY UPDATE statut = IF(statut < 50, 50, statut)
    ");
    if ($stmt->execute([$id_utilisateur, $id_lecon])) {
        // Récupérer le cours pour synchroniser
        $stmtL = $pdo->prepare("SELECT id_cours FROM lecons WHERE id_lecon = ?");
        $stmtL->execute([$id_lecon]);
        $row = $stmtL->fetch();
        if ($row) {
            synchroniserProgressionCours($id_utilisateur, $row['id_cours']);
        }
        return true;
    }
    return false;
}

/**
 * Marque le quiz d'une leçon comme réussi (statut 100).
 * Appelé après un quiz réussi.
 */
function validerLeconApresQuiz($id_utilisateur, $id_lecon) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO progression_lecons (id_utilisateur, id_lecon, statut, date_completion)
        VALUES (?, ?, 100, NOW())
        ON DUPLICATE KEY UPDATE statut = 100, date_completion = NOW()
    ");
    if ($stmt->execute([$id_utilisateur, $id_lecon])) {
        $stmtL = $pdo->prepare("SELECT id_cours FROM lecons WHERE id_lecon = ?");
        $stmtL->execute([$id_lecon]);
        $row = $stmtL->fetch();
        if ($row) {
            synchroniserProgressionCours($id_utilisateur, $row['id_cours']);
        }
        return true;
    }
    return false;
}

/**
 * Calcule et synchronise la progression d'un cours.
 * Progression cours = moyenne des statuts de toutes les leçons du cours.
 * Met à jour progression_cours.pourcentage et inscriptions_modules.progression_globale.
 */
function synchroniserProgressionCours($id_utilisateur, $id_cours) {
    global $pdo;
    // Nombre total de leçons du cours
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM lecons WHERE id_cours = ?");
    $stmtTotal->execute([$id_cours]);
    $total = (int)$stmtTotal->fetch()['total'];
    if ($total === 0) return 0;

    // Somme des statuts enregistrés
    $stmtSomme = $pdo->prepare("
        SELECT COALESCE(SUM(pl.statut), 0) as somme
        FROM lecons l
        LEFT JOIN progression_lecons pl ON pl.id_lecon = l.id_lecon AND pl.id_utilisateur = ?
        WHERE l.id_cours = ?
    ");
    $stmtSomme->execute([$id_utilisateur, $id_cours]);
    $somme = (float)$stmtSomme->fetch()['somme'];

    $pourcentage = round($somme / $total, 2);
    $statut = ($pourcentage >= 100) ? 'termine' : 'en_cours';

    // Mettre à jour progression_cours
    $stmtUpsert = $pdo->prepare("
        INSERT INTO progression_cours (id_utilisateur, id_cours, pourcentage, statut, dernier_acces)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE pourcentage = ?, statut = ?, dernier_acces = NOW()
    ");
    $stmtUpsert->execute([$id_utilisateur, $id_cours, $pourcentage, $statut, $pourcentage, $statut]);

    // Synchroniser le module
    $stmtModule = $pdo->prepare("SELECT id_module FROM cours WHERE id_cours = ?");
    $stmtModule->execute([$id_cours]);
    $rowM = $stmtModule->fetch();
    if ($rowM) {
        synchroniserProgressionModule($id_utilisateur, $rowM['id_module']);
    }
    return $pourcentage;
}

/**
 * Calcule et synchronise la progression d'un module.
 * Progression module = moyenne des progressions des cours publiés.
 * Met à jour inscriptions_modules.progression_globale.
 */
function synchroniserProgressionModule($id_utilisateur, $id_module) {
    global $pdo;
    $stmtCours = $pdo->prepare("SELECT id_cours FROM cours WHERE id_module = ? AND statut = 'publie'");
    $stmtCours->execute([$id_module]);
    $cours_list = $stmtCours->fetchAll();
    $total = count($cours_list);
    if ($total === 0) return 0;

    $somme = 0;
    foreach ($cours_list as $c) {
        $stmtP = $pdo->prepare("SELECT pourcentage FROM progression_cours WHERE id_utilisateur = ? AND id_cours = ?");
        $stmtP->execute([$id_utilisateur, $c['id_cours']]);
        $row = $stmtP->fetch();
        $somme += $row ? (float)$row['pourcentage'] : 0;
    }
    $progression_globale = round($somme / $total, 2);
    $statut = ($progression_globale >= 100) ? 'termine' : 'en_cours';

    $stmtUpd = $pdo->prepare("
        UPDATE inscriptions_modules
        SET progression_globale = ?, statut = ?, date_completion = IF(? >= 100, NOW(), NULL)
        WHERE id_utilisateur = ? AND id_module = ?
    ");
    $stmtUpd->execute([$progression_globale, $statut, $progression_globale, $id_utilisateur, $id_module]);

    // Générer le certificat automatiquement si 100%
    if ($progression_globale >= 100) {
        verifierEtGenererCertificat($id_utilisateur, $id_module);
    }

    return $progression_globale;
}

// ============================================
// FONCTIONS ÉVALUATIONS
// ============================================

function obtenirEvaluation($id_evaluation) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT e.*, l.titre_lecon, l.id_cours
        FROM evaluations e
        JOIN lecons l ON e.id_lecon = l.id_lecon
        WHERE e.id_evaluation = ?
    ");
    $stmt->execute([$id_evaluation]);
    return $stmt->fetch();
}

function obtenirEvaluationsParLecon($id_lecon) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id_lecon = ? AND actif = 1");
    $stmt->execute([$id_lecon]);
    return $stmt->fetchAll();
}

function obtenirQuestions($id_evaluation) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT q.* FROM questions q
        WHERE q.id_evaluation = ?
        ORDER BY q.ordre_affichage
    ");
    $stmt->execute([$id_evaluation]);
    return $stmt->fetchAll();
}

function calculerScore($reponses_utilisateur, $id_evaluation) {
    global $pdo;
    
    $questions = obtenirQuestions($id_evaluation);
    $score_total = 0;
    $points_maximum = 0;
    
    foreach ($questions as $question) {
        $points_maximum += $question['points'];
        
        $stmt = $pdo->prepare("
            SELECT o.id_option FROM options o
            WHERE o.id_question = ? AND o.est_correcte = 1
        ");
        $stmt->execute([$question['id_question']]);
        $bonne_option = $stmt->fetch();
        
        $reponse = $reponses_utilisateur[$question['id_question']] ?? null;
        
        if ($bonne_option && $reponse == $bonne_option['id_option']) {
            $score_total += $question['points'];
        }
    }
    
    $score_pourcentage = $points_maximum > 0 ? ($score_total / $points_maximum) * 100 : 0;
    
    return round($score_pourcentage, 2);
}

function enregistrerResultat($id_utilisateur, $id_evaluation, $reponses, $temps_consacre = null) {
    global $pdo;
    
    $score = calculerScore($reponses, $id_evaluation);
    
    $stmt = $pdo->prepare("
        INSERT INTO resultats_evaluations (score, reponses_json, id_utilisateur, id_evaluation, temps_consacre)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$score, json_encode($reponses), $id_utilisateur, $id_evaluation, $temps_consacre]);
}

// ============================================
// FONCTIONS GESTION QUIZ (CRUD enseignant)
// ============================================

/**
 * Crée une évaluation pour une leçon.
 * Une leçon ne peut avoir qu'une évaluation active.
 */
function ajouterEvaluation($titre, $description, $note_requise, $duree, $id_lecon, $tentative_max = 3) {
    global $pdo;
    // Désactiver l'ancienne évaluation si elle existe
    $stmt = $pdo->prepare("UPDATE evaluations SET actif = 0 WHERE id_lecon = ?");
    $stmt->execute([$id_lecon]);
    // Insérer la nouvelle
    $stmt = $pdo->prepare("
        INSERT INTO evaluations (titre_evaluation, description, note_requise, duree, id_lecon, tentative_max, actif)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    return $stmt->execute([$titre, $description ?: null, $note_requise, $duree ?: null, $id_lecon, $tentative_max]);
}

/**
 * Modifie une évaluation existante.
 */
function modifierEvaluation($id_evaluation, $titre, $description, $note_requise, $duree, $tentative_max) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE evaluations
        SET titre_evaluation = ?, description = ?, note_requise = ?, duree = ?, tentative_max = ?
        WHERE id_evaluation = ?
    ");
    return $stmt->execute([$titre, $description ?: null, $note_requise, $duree ?: null, $tentative_max, $id_evaluation]);
}

/**
 * Supprime une évaluation et ses questions/options en cascade.
 */
function supprimerEvaluation($id_evaluation) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM evaluations WHERE id_evaluation = ?");
    return $stmt->execute([$id_evaluation]);
}

/**
 * Récupère l'évaluation active d'une leçon avec ses questions et options.
 */
function obtenirEvaluationComplete($id_lecon) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id_lecon = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$id_lecon]);
    $evaluation = $stmt->fetch();
    if (!$evaluation) return null;
    // Charger les questions avec leurs options
    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE id_evaluation = ? ORDER BY ordre_affichage");
    $stmtQ->execute([$evaluation['id_evaluation']]);
    $questions = $stmtQ->fetchAll();
    foreach ($questions as &$q) {
        $stmtO = $pdo->prepare("SELECT * FROM options WHERE id_question = ?");
        $stmtO->execute([$q['id_question']]);
        $q['options'] = $stmtO->fetchAll();
    }
    $evaluation['questions'] = $questions;
    return $evaluation;
}

/**
 * Ajoute une question à une évaluation.
 */
function ajouterQuestion($texte, $points, $temps_limite, $id_evaluation) {
    global $pdo;
    // Calculer le prochain ordre
    $stmt = $pdo->prepare("SELECT MAX(ordre_affichage) as max_ordre FROM questions WHERE id_evaluation = ?");
    $stmt->execute([$id_evaluation]);
    $ordre = ($stmt->fetch()['max_ordre'] ?? 0) + 1;
    $stmt = $pdo->prepare("
        INSERT INTO questions (texte_question, points, temps_limite, id_evaluation, ordre_affichage)
        VALUES (?, ?, ?, ?, ?)
    ");
    if ($stmt->execute([$texte, $points ?: 1, $temps_limite ?: null, $id_evaluation, $ordre])) {
        return $pdo->lastInsertId();
    }
    return false;
}

/**
 * Modifie une question existante.
 */
function modifierQuestion($id_question, $texte, $points, $temps_limite) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE questions SET texte_question = ?, points = ?, temps_limite = ? WHERE id_question = ?
    ");
    return $stmt->execute([$texte, $points ?: 1, $temps_limite ?: null, $id_question]);
}

/**
 * Supprime une question et ses options en cascade.
 */
function supprimerQuestion($id_question) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id_question = ?");
    return $stmt->execute([$id_question]);
}

/**
 * Ajoute une option à une question.
 */
function ajouterOption($texte, $est_correcte, $id_question) {
    global $pdo;
    // Si cette option est correcte, désactiver les autres réponses correctes
    if ($est_correcte) {
        $stmt = $pdo->prepare("UPDATE options SET est_correcte = 0 WHERE id_question = ?");
        $stmt->execute([$id_question]);
    }
    $stmt = $pdo->prepare("INSERT INTO options (texte_option, est_correcte, id_question) VALUES (?, ?, ?)");
    if ($stmt->execute([$texte, $est_correcte ? 1 : 0, $id_question])) {
        return $pdo->lastInsertId();
    }
    return false;
}

/**
 * Modifie une option existante.
 */
function modifierOption($id_option, $texte, $est_correcte) {
    global $pdo;
    if ($est_correcte) {
        // Récupérer la question parente pour réinitialiser les autres
        $stmt = $pdo->prepare("SELECT id_question FROM options WHERE id_option = ?");
        $stmt->execute([$id_option]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt2 = $pdo->prepare("UPDATE options SET est_correcte = 0 WHERE id_question = ?");
            $stmt2->execute([$row['id_question']]);
        }
    }
    $stmt = $pdo->prepare("UPDATE options SET texte_option = ?, est_correcte = ? WHERE id_option = ?");
    return $stmt->execute([$texte, $est_correcte ? 1 : 0, $id_option]);
}

/**
 * Supprime une option.
 */
function supprimerOption($id_option) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM options WHERE id_option = ?");
    return $stmt->execute([$id_option]);
}

/**
 * Vérifie que l'évaluation appartient à un cours de l'enseignant connecté.
 * Sécurité anti-IDOR.
 */
function evaluationAppartientEnseignant($id_evaluation, $id_enseignant) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT e.id_evaluation FROM evaluations e
        JOIN lecons l ON e.id_lecon = l.id_lecon
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE e.id_evaluation = ? AND c.id_enseignant = ?
    ");
    $stmt->execute([$id_evaluation, $id_enseignant]);
    return $stmt->fetch() !== false;
}

/**
 * Vérifie que la question appartient à un cours de l'enseignant connecté.
 */
function questionAppartientEnseignant($id_question, $id_enseignant) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT q.id_question FROM questions q
        JOIN evaluations e ON q.id_evaluation = e.id_evaluation
        JOIN lecons l ON e.id_lecon = l.id_lecon
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE q.id_question = ? AND c.id_enseignant = ?
    ");
    $stmt->execute([$id_question, $id_enseignant]);
    return $stmt->fetch() !== false;
}

// ============================================
// FONCTIONS STATISTIQUES
// ============================================

function obtenirStatistiquesGlobales() {
    global $pdo;
    
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND statut = 'actif'");
    $stats['nb_etudiants'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modules WHERE actif = 1");
    $stats['nb_modules'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM certificats WHERE statut = 'valide'");
    $stats['nb_certificats'] = $stmt->fetch()['total'] ?? 0;
    
    return $stats;
}

function obtenirStatistiquesEtudiant($id_etudiant) {
    global $pdo;
    
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscriptions_modules WHERE id_utilisateur = ? AND statut != 'abandonne'");
    $stmt->execute([$id_etudiant]);
    $stats['nb_modules'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT AVG(pourcentage) as moyenne FROM progression_cours WHERE id_utilisateur = ?");
    $stmt->execute([$id_etudiant]);
    $stats['progression_moyenne'] = round($stmt->fetch()['moyenne'] ?? 0, 2);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM certificats WHERE id_utilisateur = ? AND statut = 'valide'");
    $stmt->execute([$id_etudiant]);
    $stats['nb_certificats'] = $stmt->fetch()['total'] ?? 0;
    
    $stats['temps_total'] = 0;
    
    return $stats;
}

function obtenirStatistiquesEnseignant($id_enseignant) {
    global $pdo;
    
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cours WHERE id_enseignant = ?");
    $stmt->execute([$id_enseignant]);
    $stats['nb_cours'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(l.id_lecon) as total
        FROM lecons l
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE c.id_enseignant = ?
    ");
    $stmt->execute([$id_enseignant]);
    $stats['nb_lecons'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT pc.id_utilisateur) as total
        FROM progression_cours pc
        JOIN cours c ON pc.id_cours = c.id_cours
        WHERE c.id_enseignant = ?
    ");
    $stmt->execute([$id_enseignant]);
    $stats['nb_etudiants'] = $stmt->fetch()['total'] ?? 0;
    
    $stats['note_moyenne'] = 0;
    
    return $stats;
}

function obtenirStatistiquesPromoteur() {
    global $pdo;
    
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND statut = 'actif'");
    $stats['nb_etudiants'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'enseignant' AND statut = 'actif'");
    $stats['nb_enseignants'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modules WHERE actif = 1");
    $stats['nb_modules'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM certificats WHERE statut = 'valide'");
    $stats['nb_certificats'] = $stmt->fetch()['total'] ?? 0;
    
    return $stats;
}

// ============================================
// FONCTIONS INSCRIPTIONS
// ============================================

function obtenirModulesInscrits($id_utilisateur) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.*, im.progression_globale,
               (SELECT COUNT(*) FROM cours WHERE id_module = m.id_module) as nb_cours_total
        FROM inscriptions_modules im
        JOIN modules m ON im.id_module = m.id_module
        WHERE im.id_utilisateur = ? AND im.statut != 'abandonne'
        ORDER BY im.date_inscription DESC
    ");
    $stmt->execute([$id_utilisateur]);
    return $stmt->fetchAll();
}

function estInscritModule($id_utilisateur, $id_module) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id_inscription FROM inscriptions_modules
        WHERE id_utilisateur = ? AND id_module = ?
    ");
    $stmt->execute([$id_utilisateur, $id_module]);
    return $stmt->fetch() !== false;
}

function inscrireEtudiantModule($id_utilisateur, $id_module) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM inscriptions_modules WHERE id_utilisateur = ? AND id_module = ?");
    $stmt->execute([$id_utilisateur, $id_module]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Déjà inscrit à ce module'];
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO inscriptions_modules (id_utilisateur, id_module, statut)
        VALUES (?, ?, 'inscrit')
    ");
    
    if ($stmt->execute([$id_utilisateur, $id_module])) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
}

// ============================================
// FONCTIONS CERTIFICATS
// ============================================

function genererCertificat($id_utilisateur, $id_module, $note_obtenue = null, $exceptionnel = false) {
    global $pdo;

    // Si déjà existant, retourner l'existant
    $stmt = $pdo->prepare("SELECT * FROM certificats WHERE id_utilisateur = ? AND id_module = ?");
    $stmt->execute([$id_utilisateur, $id_module]);
    $existant = $stmt->fetch();
    if ($existant) {
        return $existant;
    }

    // Calculer la note finale du module si non fournie
    if ($note_obtenue === null && !$exceptionnel) {
        $note_obtenue = calculerNoteFinalModule($id_utilisateur, $id_module);
    }

    $code_unique = 'GOL-' . strtoupper(uniqid()) . '-' . date('Ymd');

    $stmt = $pdo->prepare("
        INSERT INTO certificats (code_unique, id_utilisateur, id_module, note_obtenue, statut, validation_exceptionnelle)
        VALUES (?, ?, ?, ?, 'valide', ?)
    ");
    $stmt->execute([$code_unique, $id_utilisateur, $id_module, $note_obtenue, $exceptionnel ? 1 : 0]);

    $id_new = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.email, m.nom_module
        FROM certificats c
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
        JOIN modules m ON c.id_module = m.id_module
        WHERE c.id_certificat = ?
    ");
    $stmt->execute([$id_new]);
    return $stmt->fetch();
}

/**
 * Calcule la note finale d'un étudiant sur l'ensemble du module.
 * Formule : (somme points gagnés / somme points totaux) * 100
 * N'utilise que le meilleur score par évaluation.
 */
function calculerNoteFinalModule($id_utilisateur, $id_module) {
    global $pdo;
    // Récupérer toutes les évaluations du module
    $stmt = $pdo->prepare("
        SELECT e.id_evaluation, e.note_requise,
               MAX(r.score) as meilleur_score
        FROM evaluations e
        JOIN lecons l ON e.id_lecon = l.id_lecon
        JOIN cours c ON l.id_cours = c.id_cours
        LEFT JOIN resultats_evaluations r ON r.id_evaluation = e.id_evaluation AND r.id_utilisateur = ?
        WHERE c.id_module = ? AND c.statut = 'publie' AND e.actif = 1
        GROUP BY e.id_evaluation
    ");
    $stmt->execute([$id_utilisateur, $id_module]);
    $evaluations = $stmt->fetchAll();

    $score_total = 0;
    $points_max_total = 0;

    foreach ($evaluations as $ev) {
        // Points max de cette évaluation
        $stmtPts = $pdo->prepare("SELECT COALESCE(SUM(points),0) as total FROM questions WHERE id_evaluation = ?");
        $stmtPts->execute([$ev['id_evaluation']]);
        $pts_max = (float)$stmtPts->fetch()['total'];
        $points_max_total += $pts_max;
        // Score obtenu = meilleur score en % × pts_max
        $score_pct = (float)($ev['meilleur_score'] ?? 0);
        $score_total += ($score_pct / 100) * $pts_max;
    }

    if ($points_max_total === 0) return 0;
    return round(($score_total / $points_max_total) * 100, 2);
}

// ============================================
// FONCTIONS DEMANDES DE MODIFICATION
// ============================================

function obtenirDemandesEnAttente() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT d.*, u.nom, u.prenom, u.email, u.role
        FROM demandes_modification d
        JOIN utilisateurs u ON d.id_utilisateur = u.id_utilisateur
        WHERE d.statut = 'en_attente'
        ORDER BY d.date_demande DESC
    ");
    return $stmt->fetchAll();
}

function obtenirDemandesUtilisateur($id_utilisateur) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM demandes_modification
        WHERE id_utilisateur = ?
        ORDER BY date_demande DESC
    ");
    $stmt->execute([$id_utilisateur]);
    return $stmt->fetchAll();
}

function creerDemandeModification($id_utilisateur, $type_demande, $nouvelle_valeur, $justification = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO demandes_modification (id_utilisateur, type_demande, nouvelle_valeur, justification, statut)
        VALUES (?, ?, ?, ?, 'en_attente')
    ");
    
    return $stmt->execute([$id_utilisateur, $type_demande, $nouvelle_valeur, $justification]);
}

function approuverDemandeModification($id_demande, $id_admin) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM demandes_modification WHERE id_demande = ?");
        $stmt->execute([$id_demande]);
        $demande = $stmt->fetch();
        
        if (!$demande) {
            throw new Exception("Demande non trouvée");
        }
        
        switch ($demande['type_demande']) {
            case 'mot_de_passe':
                $hash = password_hash($demande['nouvelle_valeur'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
                $stmt->execute([$hash, $demande['id_utilisateur']]);
                break;
            case 'email':
                $stmt = $pdo->prepare("UPDATE utilisateurs SET email = ? WHERE id_utilisateur = ?");
                $stmt->execute([$demande['nouvelle_valeur'], $demande['id_utilisateur']]);
                break;
            case 'telephone':
                $stmt = $pdo->prepare("UPDATE utilisateurs SET telephone = ? WHERE id_utilisateur = ?");
                $stmt->execute([$demande['nouvelle_valeur'], $demande['id_utilisateur']]);
                break;
            case 'nom_complet':
                $noms = explode(' ', $demande['nouvelle_valeur'], 2);
                $nouveau_nom = $noms[0];
                $nouveau_prenom = $noms[1] ?? '';
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nouveau_nom, $nouveau_prenom, $demande['id_utilisateur']]);
                break;
        }
        
        $stmt = $pdo->prepare("
            UPDATE demandes_modification 
            SET statut = 'approuvee', date_traitement = NOW(), id_admin_traitant = ?
            WHERE id_demande = ?
        ");
        $stmt->execute([$id_admin, $id_demande]);
        
        ajouterNotification($demande['id_utilisateur'], 'Demande approuvée', 'Votre demande de modification a été approuvée.', 'succes');
        
        $pdo->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function refuserDemandeModification($id_demande, $id_admin, $commentaire) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE demandes_modification 
        SET statut = 'refusee', date_traitement = NOW(), id_admin_traitant = ?, commentaire_admin = ?
        WHERE id_demande = ?
    ");
    
    return $stmt->execute([$id_admin, $commentaire, $id_demande]);
}

// ============================================
// FONCTIONS LOGS ET ACTIVITÉS
// ============================================

function enregistrerLog($id_utilisateur, $action, $details = []) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO logs_activite (id_utilisateur, action, details, ip_adresse, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $id_utilisateur,
        $action,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function obtenirActivitesRecentes($id_utilisateur, $limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM logs_activite
        WHERE id_utilisateur = ?
        ORDER BY date_action DESC
        LIMIT ?
    ");
    $stmt->execute([$id_utilisateur, $limit]);
    return $stmt->fetchAll();
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

function time_elapsed_string($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return 'il y a ' . $diff->m . ' mois';
    if ($diff->d > 0) return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    return 'à l\'instant';
}

/**
 * Upload sécurisé pour avatars et images (conservé pour l'upload d'avatars).
 * Pour PDF/vidéo des leçons, utiliser uploadFichierLecon().
 */
function uploadFichier($fichier, $dossier, $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp']) {
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload (code ' . $fichier['error'] . ')'];
    }
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions_autorisees)) {
        return ['success' => false, 'message' => 'Extension non autorisée : ' . $extension];
    }
    // Vérification MIME réel — uniquement images bitmap
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_reel = $finfo->file($fichier['tmp_name']);
    $mimes_autorises = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime_reel, $mimes_autorises)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé (MIME: ' . $mime_reel . ')'];
    }
    if ($fichier['size'] > MAX_AVATAR_SIZE) {
        return ['success' => false, 'message' => 'Fichier trop lourd (max 2 Mo)'];
    }
    if (!is_dir($dossier)) mkdir($dossier, 0755, true);
    $nom_fichier = 'avatar_' . uniqid() . '.' . $extension;
    $chemin = $dossier . $nom_fichier;
    if (move_uploaded_file($fichier['tmp_name'], $chemin)) {
        return ['success' => true, 'fichier' => $nom_fichier, 'chemin' => $chemin];
    }
    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
}

/**
 * Upload sécurisé centralisé pour les ressources de leçons (PDF et vidéo).
 *
 * @param array  $fichier  Entrée $_FILES['...']
 * @param string $type     'pdf' ou 'video'
 * @return array           ['success'=>bool, 'chemin'=>string, 'message'=>string]
 *
 * Chemin retourné (relatif à la racine du projet) :
 *   uploads/pdf/pdf_XXXX.pdf
 *   uploads/videos/video_XXXX.mp4
 */
function uploadFichierLecon(array $fichier, string $type): array {
    // Vérifier l'erreur PHP
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreurs = [
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop lourd (limite serveur)',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop lourd (limite formulaire)',
            UPLOAD_ERR_PARTIAL    => 'Upload incomplet',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
        ];
        return ['success' => false, 'message' => $erreurs[$fichier['error']] ?? 'Erreur upload code ' . $fichier['error']];
    }

    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

    if ($type === 'pdf') {
        $extensions_ok = ['pdf'];
        $mimes_ok      = ['application/pdf'];
        $taille_max    = 20 * 1024 * 1024; // 20 Mo
        $dossier       = UPLOAD_PDF;
        $prefixe       = 'pdf_';
    } elseif ($type === 'video') {
        $extensions_ok = ['mp4', 'webm', 'ogg'];
        $mimes_ok      = ['video/mp4', 'video/webm', 'video/ogg', 'video/x-msvideo'];
        $taille_max    = 50 * 1024 * 1024; // 50 Mo
        $dossier       = UPLOAD_VIDEOS;
        $prefixe       = 'video_';
    } else {
        return ['success' => false, 'message' => 'Type d\'upload inconnu : ' . $type];
    }

    // Vérifier l'extension
    if (!in_array($extension, $extensions_ok)) {
        return ['success' => false, 'message' => 'Extension non autorisée. Formats acceptés : ' . implode(', ', $extensions_ok)];
    }

    // Vérifier la taille
    if ($fichier['size'] > $taille_max) {
        return ['success' => false, 'message' => 'Fichier trop lourd. Maximum : ' . ($taille_max / 1024 / 1024) . ' Mo'];
    }

    // Vérifier le MIME réel via finfo
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $mime_reel = $finfo->file($fichier['tmp_name']);
    if (!in_array($mime_reel, $mimes_ok)) {
        return ['success' => false, 'message' => 'Type MIME invalide détecté : ' . $mime_reel];
    }

    // Créer le dossier si nécessaire
    if (!is_dir($dossier)) mkdir($dossier, 0755, true);

    // Nom de fichier sécurisé
    $nom_fichier = $prefixe . uniqid() . '.' . $extension;
    $chemin_absolu = $dossier . $nom_fichier;

    if (!move_uploaded_file($fichier['tmp_name'], $chemin_absolu)) {
        return ['success' => false, 'message' => 'Erreur lors de la copie du fichier sur le serveur'];
    }

    // Retourner le chemin relatif (stocké en base)
    $chemin_relatif = ($type === 'pdf') ? 'uploads/pdf/' . $nom_fichier : 'uploads/videos/' . $nom_fichier;

    return ['success' => true, 'chemin' => $chemin_relatif, 'fichier' => $nom_fichier];
}

function obtenirTousUtilisateurs() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT id_utilisateur, email, nom, prenom, role, statut, date_inscription
        FROM utilisateurs
        ORDER BY date_inscription DESC
    ");
    return $stmt->fetchAll();
}

function calculerProgressionModule($id_utilisateur, $id_module) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.id_cours
        FROM cours c
        WHERE c.id_module = ? AND c.statut = 'publie'
    ");
    $stmt->execute([$id_module]);
    $cours_list = $stmt->fetchAll();
    
    $total_progression = 0;
    $nb_cours = count($cours_list);
    
    foreach ($cours_list as $cour) {
        $stmt = $pdo->prepare("SELECT pourcentage FROM progression_cours WHERE id_utilisateur = ? AND id_cours = ?");
        $stmt->execute([$id_utilisateur, $cour['id_cours']]);
        $prog = $stmt->fetch();
        $total_progression += $prog['pourcentage'] ?? 0;
    }
    
    return $nb_cours > 0 ? $total_progression / $nb_cours : 0;
}

function obtenirProgressionCours($id_utilisateur, $id_cours) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM progression_cours
        WHERE id_utilisateur = ? AND id_cours = ?
    ");
    $stmt->execute([$id_utilisateur, $id_cours]);
    return $stmt->fetch();
}

// ============================================
// FONCTION RECHERCHE GLOBALE
// ============================================

/**
 * Recherche dans les modules et cours publiés.
 * Utilisée par l'endpoint AJAX 'recherche'.
 */
function rechercherGlobal($terme) {
    global $pdo;
    $like = '%' . $terme . '%';
    $resultats = ['modules' => [], 'cours' => []];

    $stmtM = $pdo->prepare("
        SELECT id_module, nom_module, description, niveau
        FROM modules WHERE actif = 1 AND (nom_module LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmtM->execute([$like, $like]);
    $resultats['modules'] = $stmtM->fetchAll();

    $stmtC = $pdo->prepare("
        SELECT c.id_cours, c.titre_cours, c.description, m.nom_module
        FROM cours c JOIN modules m ON c.id_module = m.id_module
        WHERE c.statut = 'publie' AND (c.titre_cours LIKE ? OR c.description LIKE ?)
        LIMIT 5
    ");
    $stmtC->execute([$like, $like]);
    $resultats['cours'] = $stmtC->fetchAll();

    return $resultats;
}

// ============================================
// FONCTIONS CERTIFICATS EXCEPTIONNELS
// ============================================

/**
 * Vérifie si toutes les leçons d'un module sont à statut 100 pour un étudiant.
 */
function moduleValideParEtudiant($id_utilisateur, $id_module) {
    global $pdo;
    // Compter les leçons du module
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM lecons l
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE c.id_module = ? AND c.statut = 'publie'
    ");
    $stmt->execute([$id_module]);
    $total = (int)$stmt->fetch()['total'];
    if ($total === 0) return false;

    // Compter les leçons à 100
    $stmt2 = $pdo->prepare("
        SELECT COUNT(*) as valides FROM progression_lecons pl
        JOIN lecons l ON pl.id_lecon = l.id_lecon
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE c.id_module = ? AND c.statut = 'publie'
          AND pl.id_utilisateur = ? AND pl.statut = 100
    ");
    $stmt2->execute([$id_module, $id_utilisateur]);
    $valides = (int)$stmt2->fetch()['valides'];
    return $valides >= $total;
}

/**
 * Génère automatiquement le certificat si le module est complété à 100% ET note suffisante.
 */
function verifierEtGenererCertificat($id_utilisateur, $id_module) {
    global $pdo;
    // Vérifier progression_globale = 100
    $stmt = $pdo->prepare("SELECT progression_globale FROM inscriptions_modules WHERE id_utilisateur = ? AND id_module = ?");
    $stmt->execute([$id_utilisateur, $id_module]);
    $row = $stmt->fetch();
    if (!$row || (float)$row['progression_globale'] < 100) return false;

    // Calculer la note finale du module
    $note = calculerNoteFinalModule($id_utilisateur, $id_module);

    // Vérifier si la note est suffisante (note_requise moyenne des évaluations du module)
    $stmtNR = $pdo->prepare("
        SELECT AVG(e.note_requise) as note_requise_moy
        FROM evaluations e
        JOIN lecons l ON e.id_lecon = l.id_lecon
        JOIN cours c ON l.id_cours = c.id_cours
        WHERE c.id_module = ? AND c.statut = 'publie' AND e.actif = 1
    ");
    $stmtNR->execute([$id_module]);
    $rowNR = $stmtNR->fetch();
    $note_requise = (float)($rowNR['note_requise_moy'] ?? 60);

    if ($note < $note_requise) return false; // CAS 2 : note insuffisante

    $cert = genererCertificat($id_utilisateur, $id_module, $note, false);
    if ($cert) {
        ajouterNotification($id_utilisateur, 'Certificat obtenu !',
            'Félicitations ! Votre certificat de module a été généré automatiquement.', 'succes');
    }
    return (bool)$cert;
}

/**
 * Crée une demande de certificat exceptionnel.
 */
function creerDemandeCertificat($id_etudiant, $id_module, $motif) {
    global $pdo;
    // Vérifier qu'une demande en attente n'existe pas déjà
    $stmt = $pdo->prepare("
        SELECT id_demande FROM demandes_certificats
        WHERE id_etudiant = ? AND id_module = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$id_etudiant, $id_module]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Une demande est déjà en attente'];
    }
    $stmt2 = $pdo->prepare("
        INSERT INTO demandes_certificats (id_etudiant, id_module, motif)
        VALUES (?, ?, ?)
    ");
    if ($stmt2->execute([$id_etudiant, $id_module, $motif])) {
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    }
    return ['success' => false, 'message' => 'Erreur lors de la création'];
}

/**
 * Approuve une demande — Super Admin uniquement.
 * Génère le certificat même sans progression = 100%.
 */
function approuverDemandeCertificat($id_demande, $id_admin) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM demandes_certificats WHERE id_demande = ?");
    $stmt->execute([$id_demande]);
    $demande = $stmt->fetch();
    if (!$demande) return ['success' => false, 'message' => 'Demande introuvable'];

    // Générer le certificat exceptionnel
    genererCertificat($demande['id_etudiant'], $demande['id_module'], null, true);

    // Mettre à jour le statut
    $stmt2 = $pdo->prepare("
        UPDATE demandes_certificats
        SET statut='approuve', date_traitement=NOW(), id_admin_traitant=?
        WHERE id_demande=?
    ");
    $stmt2->execute([$id_admin, $id_demande]);

    ajouterNotification($demande['id_etudiant'], 'Certificat exceptionnel accordé',
        'Votre demande de certificat exceptionnel a été approuvée.', 'succes');

    return ['success' => true];
}

/**
 * Refuse une demande — Super Admin uniquement.
 */
function refuserDemandeCertificat($id_demande, $id_admin, $commentaire) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id_etudiant FROM demandes_certificats WHERE id_demande = ?");
    $stmt->execute([$id_demande]);
    $demande = $stmt->fetch();
    if (!$demande) return ['success' => false, 'message' => 'Demande introuvable'];

    $stmt2 = $pdo->prepare("
        UPDATE demandes_certificats
        SET statut='refuse', date_traitement=NOW(), id_admin_traitant=?, commentaire_admin=?
        WHERE id_demande=?
    ");
    $stmt2->execute([$id_admin, $commentaire, $id_demande]);

    ajouterNotification($demande['id_etudiant'], 'Demande de certificat refusée',
        'Votre demande de certificat exceptionnel a été refusée.', 'avertissement');

    return ['success' => true];
}

/**
 * Retourne les demandes de certificats en attente (Super Admin).
 */
function obtenirDemandesCertificatsEnAttente() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT dc.*, u.nom, u.prenom, u.email, m.nom_module
        FROM demandes_certificats dc
        JOIN utilisateurs u ON dc.id_etudiant = u.id_utilisateur
        JOIN modules m ON dc.id_module = m.id_module
        WHERE dc.statut = 'en_attente'
        ORDER BY dc.date_demande DESC
    ");
    return $stmt->fetchAll();
}
