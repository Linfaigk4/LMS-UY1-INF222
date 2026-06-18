<?php
/**
 * GOL (Gugle Online Learning) - API AJAX Centralisée
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 * 
 * Tous les appels AJAX du site passent par ce fichier
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Forcer l'en-tête JSON
header('Content-Type: application/json');

// Vérifier si la requête est AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer les données JSON pour les requêtes POST
$input = json_decode(file_get_contents('php://input'), true);
if ($method === 'POST' && empty($_POST) && $input) {
    $_POST = array_merge($_POST, $input);
}

$response = ['success' => false, 'message' => 'Action inconnue'];

switch ($action) {
    // ============================================
    // GESTION DES DEMANDES DE MODIFICATION
    // ============================================
    
    case 'approuver_demande':
        if (estSuperAdmin() || estPromoteur()) {
            $id_demande = $_POST['id_demande'] ?? 0;
            if ($id_demande) {
                $resultat = approuverDemandeModification($id_demande, $_SESSION['id_utilisateur']);
                $response = $resultat;
            } else {
                $response = ['success' => false, 'message' => 'ID de demande manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
        
    case 'refuser_demande':
        if (estSuperAdmin() || estPromoteur()) {
            $id_demande = $_POST['id_demande'] ?? 0;
            $commentaire = $_POST['commentaire'] ?? '';
            if ($id_demande) {
                $resultat = refuserDemandeModification($id_demande, $_SESSION['id_utilisateur'], $commentaire);
                $response = ['success' => $resultat, 'message' => $resultat ? 'Demande refusée' : 'Erreur lors du refus'];
            } else {
                $response = ['success' => false, 'message' => 'ID de demande manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    // ============================================
    // GESTION DES MODULES (Super Admin)
    // ============================================
    
    case 'supprimer_module':
        if (estSuperAdmin()) {
            $id_module = $_POST['id_module'] ?? 0;
            if ($id_module) {
                $resultat = supprimerModule($id_module);
                $response = ['success' => $resultat, 'message' => $resultat ? 'Module supprimé' : 'Erreur lors de la suppression'];
            } else {
                $response = ['success' => false, 'message' => 'ID de module manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    case 'toggle_module_statut':
        if (estSuperAdmin() || estPromoteur()) {
            $id_module = $_POST['id_module'] ?? 0;
            if ($id_module) {
                global $pdo;
                $stmt = $pdo->prepare("UPDATE modules SET actif = NOT actif WHERE id_module = ?");
                $resultat = $stmt->execute([$id_module]);
                $response = ['success' => $resultat, 'message' => $resultat ? 'Statut modifié' : 'Erreur'];
            } else {
                $response = ['success' => false, 'message' => 'ID de module manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    // ============================================
    // GESTION DES UTILISATEURS (Super Admin)
    // ============================================
    
    case 'toggle_user_status':
        if (estSuperAdmin()) {
            $id_utilisateur = $_POST['id_utilisateur'] ?? 0;
            if ($id_utilisateur && $id_utilisateur != $_SESSION['id_utilisateur']) {
                global $pdo;
                $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = IF(statut = 'actif', 'suspendu', 'actif') WHERE id_utilisateur = ?");
                $resultat = $stmt->execute([$id_utilisateur]);
                $response = ['success' => $resultat, 'message' => $resultat ? 'Statut utilisateur modifié' : 'Erreur'];
            } else {
                $response = ['success' => false, 'message' => 'Action non autorisée sur cet utilisateur'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    // ============================================
    // SOUMISSION D'ÉVALUATION
    // ============================================
    
    case 'soumettre_evaluation':
        if (estEtudiant()) {
            $id_evaluation  = (int)($_POST['evaluation_id'] ?? 0);
            $reponses       = $_POST['reponses'] ?? [];
            $temps_consacre = $_POST['temps_consacre'] ?? null;

            if ($id_evaluation && !empty($reponses)) {
                $score = calculerScore($reponses, $id_evaluation);
                // Récupérer la note requise et la leçon associée
                $stmtE = $pdo->prepare("SELECT note_requise, id_lecon FROM evaluations WHERE id_evaluation = ?");
                $stmtE->execute([$id_evaluation]);
                $eval_data = $stmtE->fetch();
                $reussi = $eval_data && $score >= (float)$eval_data['note_requise'];

                // Enregistrer le résultat
                enregistrerResultat($_SESSION['id_utilisateur'], $id_evaluation, $reponses, $temps_consacre);

                // Si quiz réussi → statut leçon = 100
                if ($reussi && $eval_data) {
                    validerLeconApresQuiz($_SESSION['id_utilisateur'], $eval_data['id_lecon']);
                }

                $response = [
                    'success' => true,
                    'score'   => $score,
                    'reussi'  => $reussi,
                    'message' => $reussi ? 'Évaluation réussie !' : 'Score insuffisant'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Données manquantes'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    // ============================================
    // MISE À JOUR DE LA PROGRESSION
    // ============================================

    case 'maj_progression':
        if (estEtudiant()) {
            $id_lecon = (int)($_POST['lecon_id'] ?? 0);
            $action   = $_POST['action_progression'] ?? 'ouvrir'; // 'ouvrir' ou 'valider'

            if ($id_lecon) {
                if ($action === 'valider') {
                    $ok = validerLeconApresQuiz($_SESSION['id_utilisateur'], $id_lecon);
                } else {
                    $ok = ouvrirLecon($_SESSION['id_utilisateur'], $id_lecon);
                }
                // Récupérer pourcentages mis à jour
                $stmtC = $pdo->prepare("SELECT id_cours FROM lecons WHERE id_lecon = ?");
                $stmtC->execute([$id_lecon]);
                $rowC = $stmtC->fetch();
                $pct_cours = 0;
                $pct_module = 0;
                if ($rowC) {
                    $stmtPC = $pdo->prepare("SELECT pourcentage FROM progression_cours WHERE id_utilisateur = ? AND id_cours = ?");
                    $stmtPC->execute([$_SESSION['id_utilisateur'], $rowC['id_cours']]);
                    $rowPC = $stmtPC->fetch();
                    $pct_cours = $rowPC ? round($rowPC['pourcentage'], 0) : 0;
                    $stmtM = $pdo->prepare("SELECT id_module FROM cours WHERE id_cours = ?");
                    $stmtM->execute([$rowC['id_cours']]);
                    $rowM = $stmtM->fetch();
                    if ($rowM) {
                        $stmtPM = $pdo->prepare("SELECT progression_globale FROM inscriptions_modules WHERE id_utilisateur = ? AND id_module = ?");
                        $stmtPM->execute([$_SESSION['id_utilisateur'], $rowM['id_module']]);
                        $rowPM = $stmtPM->fetch();
                        $pct_module = $rowPM ? round($rowPM['progression_globale'], 0) : 0;
                    }
                }
                $response = [
                    'success'         => $ok,
                    'pourcentage'     => $pct_cours,
                    'pct_module'      => $pct_module,
                    'message'         => $ok ? 'Progression mise à jour' : 'Erreur'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Données manquantes'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    // ============================================
    // INSCRIPTION MODULE (appelé depuis module.php)
    // ============================================

    case 'inscrire_module':
        if (estEtudiant()) {
            $id_module = (int)($_POST['id_module'] ?? 0);
            if ($id_module) {
                $resultat = inscrireEtudiantModule($_SESSION['id_utilisateur'], $id_module);
                $response = $resultat;
            } else {
                $response = ['success' => false, 'message' => 'ID module manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;

    // ============================================
    // RECHERCHE EN TEMPS RÉEL
    // ============================================
    
    case 'recherche':
        $terme = $_GET['q'] ?? $_POST['q'] ?? '';
        if (strlen($terme) >= 2) {
            $resultats = rechercherGlobal($terme);
            $response = ['success' => true, 'data' => $resultats];
        } else {
            $response = ['success' => true, 'data' => ['modules' => [], 'cours' => [], 'lecons' => []]];
        }
        break;
    
    // ============================================
    // NOTIFICATIONS
    // ============================================
    
    case 'marquer_notification_lue':
        $id_notification = $_POST['id_notification'] ?? 0;
        if ($id_notification) {
            $resultat = marquerNotificationLue($id_notification);
            $response = ['success' => $resultat];
        } else {
            $response = ['success' => false, 'message' => 'ID manquant'];
        }
        break;
    
    case 'obtenir_notifications':
        $notifications = obtenirNotificationsNonLues($_SESSION['id_utilisateur']);
        $response = ['success' => true, 'data' => $notifications, 'count' => count($notifications)];
        break;
    
    // ============================================
    // STATISTIQUES (Dashboard)
    // ============================================
    
    case 'statistiques_globales':
        if (estSuperAdmin() || estPromoteur()) {
            $stats = obtenirStatistiquesPromoteur();
            $response = ['success' => true, 'data' => $stats];
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;
    
    case 'statistiques_utilisateur':
        if (estEtudiant()) {
            $stats = obtenirStatistiquesEtudiant($_SESSION['id_utilisateur']);
            $response = ['success' => true, 'data' => $stats];
        } elseif (estEnseignant()) {
            $stats = obtenirStatistiquesEnseignant($_SESSION['id_utilisateur']);
            $response = ['success' => true, 'data' => $stats];
        } else {
            $response = ['success' => false, 'message' => 'Action non disponible'];
        }
        break;
    
    // ============================================
    // CERTIFICATS
    // ============================================

    case 'generer_certificat':
        if (estEtudiant()) {
            $id_module = (int)($_POST['id_module'] ?? 0);
            if ($id_module) {
                // Vérifier progression + note avant de générer
                $ok = verifierEtGenererCertificat($_SESSION['id_utilisateur'], $id_module);
                if ($ok) {
                    $stmtC = $pdo->prepare("SELECT c.*, u.nom, u.prenom, m.nom_module FROM certificats c JOIN utilisateurs u ON c.id_utilisateur=u.id_utilisateur JOIN modules m ON c.id_module=m.id_module WHERE c.id_utilisateur=? AND c.id_module=?");
                    $stmtC->execute([$_SESSION['id_utilisateur'], $id_module]);
                    $cert = $stmtC->fetch();
                    $response = ['success' => true, 'data' => $cert];
                } else {
                    $response = ['success' => false, 'message' => 'Conditions non remplies (progression ou note insuffisante)'];
                }
            } else {
                $response = ['success' => false, 'message' => 'ID module manquant'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;

    case 'obtenir_certificat':
        $id_certificat = (int)($_GET['id'] ?? 0);
        if ($id_certificat) {
            // Étudiant ne voit que le sien ; admin voit tous
            if (estSuperAdmin() || estPromoteur()) {
                $stmtC = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.email, m.nom_module FROM certificats c JOIN utilisateurs u ON c.id_utilisateur=u.id_utilisateur JOIN modules m ON c.id_module=m.id_module WHERE c.id_certificat=?");
                $stmtC->execute([$id_certificat]);
            } else {
                $stmtC = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.email, m.nom_module FROM certificats c JOIN utilisateurs u ON c.id_utilisateur=u.id_utilisateur JOIN modules m ON c.id_module=m.id_module WHERE c.id_certificat=? AND c.id_utilisateur=?");
                $stmtC->execute([$id_certificat, $_SESSION['id_utilisateur']]);
            }
            $cert = $stmtC->fetch();
            $response = $cert ? ['success' => true, 'data' => $cert] : ['success' => false, 'message' => 'Certificat non trouvé'];
        } else {
            $response = ['success' => false, 'message' => 'ID manquant'];
        }
        break;

    case 'demander_certificat':
        if (estEtudiant()) {
            $id_module = (int)($_POST['id_module'] ?? 0);
            $motif     = trim($_POST['motif'] ?? '');
            if (!$id_module || !$motif) {
                $response = ['success' => false, 'message' => 'Données manquantes'];
                break;
            }
            $response = creerDemandeCertificat($_SESSION['id_utilisateur'], $id_module, $motif);
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;

    case 'approuver_demande_certificat':
        if (estSuperAdmin()) {
            $id_demande = (int)($_POST['id_demande'] ?? 0);
            if (!$id_demande) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
            $response = approuverDemandeCertificat($id_demande, $_SESSION['id_utilisateur']);
        } else {
            $response = ['success' => false, 'message' => 'Réservé au Super Admin'];
        }
        break;

    case 'refuser_demande_certificat':
        if (estSuperAdmin()) {
            $id_demande  = (int)($_POST['id_demande'] ?? 0);
            $commentaire = trim($_POST['commentaire'] ?? '');
            if (!$id_demande) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
            $response = refuserDemandeCertificat($id_demande, $_SESSION['id_utilisateur'], $commentaire);
        } else {
            $response = ['success' => false, 'message' => 'Réservé au Super Admin'];
        }
        break;
    
    // ============================================
    // PROFIL - CHARGEMENT AVATAR
    // ============================================
    
    case 'upload_avatar':
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $resultat = uploadFichier($_FILES['avatar'], UPLOAD_AVATARS, ['jpg', 'jpeg', 'png', 'gif', 'svg']);
            if ($resultat['success']) {
                global $pdo;
                $stmt = $pdo->prepare("UPDATE utilisateurs SET avatar = ? WHERE id_utilisateur = ?");
                $stmt->execute([$resultat['fichier'], $_SESSION['id_utilisateur']]);
                $response = ['success' => true, 'avatar' => $resultat['fichier'], 'message' => 'Avatar mis à jour'];
            } else {
                $response = $resultat;
            }
        } else {
            $response = ['success' => false, 'message' => 'Aucun fichier reçu'];
        }
        break;

    // ============================================
    // CERTIFICATS EXCEPTIONNELS (ancien bloc — supprimé, fusionné ci-dessus)
    // ============================================

    case 'creer_demande_certificat':
        // Alias pour compatibilité — redirige vers demander_certificat
        if (estEtudiant()) {
            $id_module = (int)($_POST['id_module'] ?? 0);
            $motif     = trim($_POST['motif'] ?? '');
            if (!$id_module || !$motif) { $response = ['success' => false, 'message' => 'Données manquantes']; break; }
            $response = creerDemandeCertificat($_SESSION['id_utilisateur'], $id_module, $motif);
        } else {
            $response = ['success' => false, 'message' => 'Permission refusée'];
        }
        break;

    // ============================================
    // GESTION QUIZ — CRUD ENSEIGNANT
    // ============================================

    case 'ajouter_evaluation':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_lecon    = (int)($_POST['id_lecon'] ?? 0);
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note        = (float)($_POST['note_requise'] ?? 60);
        $duree       = $_POST['duree'] ? (int)$_POST['duree'] : null;
        $tentatives  = (int)($_POST['tentative_max'] ?? 3);
        if (!$id_lecon || !$titre) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        // Vérification que la leçon appartient à un cours de l'enseignant
        $stmtV = $pdo->prepare("SELECT c.id_enseignant FROM lecons l JOIN cours c ON l.id_cours = c.id_cours WHERE l.id_lecon = ?");
        $stmtV->execute([$id_lecon]);
        $propriete = $stmtV->fetch();
        if (!estSuperAdmin() && (!$propriete || $propriete['id_enseignant'] != $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $ok = ajouterEvaluation($titre, $description, $note, $duree, $id_lecon, $tentatives);
        $response = $ok
            ? ['success' => true, 'message' => 'Évaluation créée', 'id' => $pdo->lastInsertId()]
            : ['success' => false, 'message' => 'Erreur lors de la création'];
        break;

    case 'modifier_evaluation':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_eval     = (int)($_POST['id_evaluation'] ?? 0);
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note        = (float)($_POST['note_requise'] ?? 60);
        $duree       = $_POST['duree'] ? (int)$_POST['duree'] : null;
        $tentatives  = (int)($_POST['tentative_max'] ?? 3);
        if (!$id_eval || !$titre) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        if (!estSuperAdmin() && !evaluationAppartientEnseignant($id_eval, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $ok = modifierEvaluation($id_eval, $titre, $description, $note, $duree, $tentatives);
        $response = ['success' => $ok, 'message' => $ok ? 'Évaluation modifiée' : 'Erreur'];
        break;

    case 'supprimer_evaluation':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_eval = (int)($_POST['id_evaluation'] ?? 0);
        if (!$id_eval) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
        if (!estSuperAdmin() && !evaluationAppartientEnseignant($id_eval, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $ok = supprimerEvaluation($id_eval);
        $response = ['success' => $ok, 'message' => $ok ? 'Évaluation supprimée' : 'Erreur'];
        break;

    case 'ajouter_question':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_eval      = (int)($_POST['id_evaluation'] ?? 0);
        $texte        = trim($_POST['texte_question'] ?? '');
        $points       = (int)($_POST['points'] ?? 1);
        $temps_limite = $_POST['temps_limite'] ? (int)$_POST['temps_limite'] : null;
        if (!$id_eval || !$texte) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        if (!estSuperAdmin() && !evaluationAppartientEnseignant($id_eval, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $id_new = ajouterQuestion($texte, $points, $temps_limite, $id_eval);
        $response = $id_new
            ? ['success' => true, 'message' => 'Question ajoutée', 'id' => $id_new]
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout'];
        break;

    case 'modifier_question':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_question  = (int)($_POST['id_question'] ?? 0);
        $texte        = trim($_POST['texte_question'] ?? '');
        $points       = (int)($_POST['points'] ?? 1);
        $temps_limite = $_POST['temps_limite'] ? (int)$_POST['temps_limite'] : null;
        if (!$id_question || !$texte) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        if (!estSuperAdmin() && !questionAppartientEnseignant($id_question, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $ok = modifierQuestion($id_question, $texte, $points, $temps_limite);
        $response = ['success' => $ok, 'message' => $ok ? 'Question modifiée' : 'Erreur'];
        break;

    case 'supprimer_question':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_question = (int)($_POST['id_question'] ?? 0);
        if (!$id_question) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
        if (!estSuperAdmin() && !questionAppartientEnseignant($id_question, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $ok = supprimerQuestion($id_question);
        $response = ['success' => $ok, 'message' => $ok ? 'Question supprimée' : 'Erreur'];
        break;

    case 'ajouter_option':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_question  = (int)($_POST['id_question'] ?? 0);
        $texte        = trim($_POST['texte_option'] ?? '');
        $est_correcte = !empty($_POST['est_correcte']) && $_POST['est_correcte'] !== '0';
        if (!$id_question || !$texte) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        if (!estSuperAdmin() && !questionAppartientEnseignant($id_question, $_SESSION['id_utilisateur'])) {
            $response = ['success' => false, 'message' => 'Accès refusé'];
            break;
        }
        $id_new = ajouterOption($texte, $est_correcte, $id_question);
        $response = $id_new
            ? ['success' => true, 'message' => 'Option ajoutée', 'id' => $id_new]
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout'];
        break;

    case 'modifier_option':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_option    = (int)($_POST['id_option'] ?? 0);
        $texte        = trim($_POST['texte_option'] ?? '');
        $est_correcte = !empty($_POST['est_correcte']) && $_POST['est_correcte'] !== '0';
        if (!$id_option || !$texte) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }
        $ok = modifierOption($id_option, $texte, $est_correcte);
        $response = ['success' => $ok, 'message' => $ok ? 'Option modifiée' : 'Erreur'];
        break;

    case 'supprimer_option':
        if (!estEnseignant() && !estSuperAdmin()) {
            $response = ['success' => false, 'message' => 'Permission refusée'];
            break;
        }
        $id_option = (int)($_POST['id_option'] ?? 0);
        if (!$id_option) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
        $ok = supprimerOption($id_option);
        $response = ['success' => $ok, 'message' => $ok ? 'Option supprimée' : 'Erreur'];
        break;

    case 'obtenir_evaluation_complete':
        if (!estConnecte()) { $response = ['success' => false, 'message' => 'Non connecté']; break; }
        $id_lecon = (int)($_GET['id_lecon'] ?? 0);
        if (!$id_lecon) { $response = ['success' => false, 'message' => 'ID manquant']; break; }
        $eval = obtenirEvaluationComplete($id_lecon);
        $response = ['success' => true, 'data' => $eval];
        break;

    // ============================================
    // ACTION PAR DÉFAUT
    // ============================================

    default:
        $response = ['success' => false, 'message' => 'Action non reconnue'];
        break;
}

echo json_encode($response);
exit;
?>