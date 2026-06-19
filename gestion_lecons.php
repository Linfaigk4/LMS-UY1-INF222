<?php
/**
 * GOL (Gugle Online Learning) - Gestion des leçons (Enseignant)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté et est enseignant
if (!estConnecte() || !estEnseignant()) {
    header('Location: connexion.php');
    exit;
}

$id_cours = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : 0;
$cours = obtenirCours($id_cours);

if (!$cours || $cours['id_enseignant'] != $_SESSION['id_utilisateur']) {
    header('Location: gestion_cours.php');
    exit;
}

$message = '';
$error = '';

// La fonction uploadFichierLecon() est définie dans includes/fonctions.php

// Ajouter une leçon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $titre         = trim($_POST['titre'] ?? '');
    $contenu_texte = trim($_POST['contenu_texte'] ?? '');
    $type_contenu  = $_POST['type_contenu'] ?? 'texte';
    $duree         = (int)($_POST['duree'] ?? 0);
    $fichier_pdf   = null;
    $url_video     = null;

    if ($type_contenu === 'pdf' && isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFichierLecon($_FILES['fichier_pdf'], 'pdf');
        if ($upload['success']) {
            $fichier_pdf = $upload['chemin'];
        } else {
            $error = $upload['message'];
        }
    }

    if ($type_contenu === 'video' && isset($_FILES['fichier_video']) && $_FILES['fichier_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFichierLecon($_FILES['fichier_video'], 'video');
        if ($upload['success']) {
            $url_video = $upload['chemin'];
        } else {
            $error = $upload['message'];
        }
    }

    if ($type_contenu === 'video' && empty($url_video) && !empty($_POST['url_video'])) {
        $url_video = trim($_POST['url_video']);
    }

    if (empty($titre)) {
        $error = 'Veuillez entrer un titre.';
    } elseif (empty($error)) {
        $resultat = ajouterLecon($titre, $contenu_texte, $type_contenu, $id_cours, $duree, $fichier_pdf, $url_video);
        if ($resultat) {
            $message = 'Leçon ajoutée avec succès !';
        } else {
            $error = 'Erreur lors de l\'ajout.';
        }
    }
}

// Modifier une leçon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_lecon      = (int)($_POST['id_lecon'] ?? 0);
    $titre         = trim($_POST['titre'] ?? '');
    $contenu_texte = trim($_POST['contenu_texte'] ?? '');
    $type_contenu  = $_POST['type_contenu'] ?? 'texte';
    $duree         = (int)($_POST['duree'] ?? 0);
    $fichier_pdf   = $_POST['fichier_pdf_existant'] ?? null;
    $url_video     = $_POST['url_video_existante'] ?? null;

    if ($type_contenu === 'pdf' && isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFichierLecon($_FILES['fichier_pdf'], 'pdf');
        if ($upload['success']) {
            $fichier_pdf = $upload['chemin'];
        } else {
            $error = $upload['message'];
        }
    }

    if ($type_contenu === 'video' && isset($_FILES['fichier_video']) && $_FILES['fichier_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFichierLecon($_FILES['fichier_video'], 'video');
        if ($upload['success']) {
            $url_video = $upload['chemin'];
        } else {
            $error = $upload['message'];
        }
    }

    if ($type_contenu === 'video' && empty($url_video) && !empty($_POST['url_video'])) {
        $url_video = trim($_POST['url_video']);
    }

    if (empty($error)) {
        $resultat = modifierLecon($id_lecon, $titre, $contenu_texte, $type_contenu, $duree, $fichier_pdf, $url_video);
        if ($resultat) {
            $message = 'Leçon modifiée avec succès !';
        } else {
            $error = 'Erreur lors de la modification.';
        }
    }
}

// Supprimer une leçon
if (isset($_GET['delete'])) {
    $id_lecon = (int)$_GET['delete'];
    // Anti-IDOR : vérifier que la leçon appartient à l'enseignant connecté
    $id_ens = estSuperAdmin() ? null : $_SESSION['id_utilisateur'];
    $resultat = supprimerLecon($id_lecon, $id_ens);
    if ($resultat) {
        $message = 'Leçon supprimée avec succès !';
    } else {
        $error = 'Suppression refusée ou erreur (accès non autorisé).';
    }
}

$lecons = obtenirLecons($id_cours);

$page_title = 'Gestion des leçons - ' . $cours['titre_cours'];
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles similaires à gestion_cours.php */
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 24px;
}

.lecon-card {
    background: var(--carte);
    border-radius: 16px;
    border: 1px solid var(--bordure);
    margin-bottom: 16px;
    overflow: hidden;
}

.lecon-header {
    padding: 16px 20px;
    background: var(--fond-secondaire);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.lecon-title {
    font-weight: 600;
    font-size: 1rem;
}

.lecon-badge {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    background: rgba(37,99,235,0.1);
    color: #2563eb;
}

.lecon-body {
    padding: 20px;
}

.lecon-preview {
    background: var(--fond-secondaire);
    padding: 16px;
    border-radius: 12px;
    margin-top: 12px;
    font-size: 0.875rem;
    color: var(--texte-secondaire);
    max-height: 150px;
    overflow-y: auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <div>
            <a href="gestion_cours.php" style="color: #2563eb; text-decoration: none;">← Retour aux cours</a>
            <h1 class="admin-title" style="margin-top: 16px;"><?= icone('lecon', 18) ?> <?= htmlspecialchars($cours['titre_cours']) ?></h1>
            <p style="color: var(--texte-secondaire);">Gestion des leçons et du contenu</p>
        </div>
        <button class="btn-primary" onclick="openModal('addLeconModal')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nouvelle leçon
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= icone('succes', 16) ?> <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= icone('erreur', 16) ?> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="lecons-list">
        <?php if (empty($lecons)): ?>
            <div style="text-align: center; padding: 60px; background: var(--carte); border-radius: 20px;">
                <p>Aucune leçon pour ce cours.</p>
                <button class="btn-primary" style="margin-top: 20px;" onclick="openModal('addLeconModal')">Ajouter ma première leçon</button>
            </div>
        <?php else: ?>
            <?php foreach ($lecons as $index => $lecon): ?>
                <div class="lecon-card">
                    <div class="lecon-header">
                        <div>
                            <span class="lecon-title"><?= ($index + 1) . '. ' . htmlspecialchars($lecon['titre_lecon']) ?></span>
                            <span class="lecon-badge"><?= ucfirst($lecon['type_contenu']) ?></span>
                        </div>
                        <div class="cours-actions">
                            <button class="btn-sm btn-sm-edit" onclick="editLecon(<?= htmlspecialchars(json_encode($lecon)) ?>)">
                                <?= icone('modifier', 14) ?> Modifier
                            </button>
                            <button class="btn-sm btn-sm-delete" onclick="if(confirm('Supprimer cette leçon ?')) window.location.href='?cours_id=<?= $id_cours ?>&delete=<?= $lecon['id_lecon'] ?>'">
                                <?= icone('supprimer', 14) ?> Supprimer
                            </button>
                            <a href="gestion_quiz.php?lecon_id=<?= $lecon['id_lecon'] ?>&cours_id=<?= $id_cours ?>" class="btn-sm btn-sm-publish">
                                <?= icone('quiz', 18) ?> Gérer le quiz
                            </a>
                        </div>
                    </div>
                    <div class="lecon-body">
                        <?php if ($lecon['type_contenu'] === 'texte' && $lecon['contenu_texte']): ?>
                            <div class="lecon-preview">
                                <?= htmlspecialchars(substr($lecon['contenu_texte'], 0, 200)) ?>...
                            </div>
                        <?php elseif ($lecon['type_contenu'] === 'pdf' && $lecon['fichier_pdf']): ?>
                            <div class="lecon-preview">
                                <?= icone('pdf', 16) ?> Fichier PDF : <?= basename($lecon['fichier_pdf']) ?>
                            </div>
                        <?php elseif ($lecon['type_contenu'] === 'video' && $lecon['url_video']): ?>
                            <div class="lecon-preview">
                                <?= icone('video', 16) ?> Vidéo : <?= htmlspecialchars($lecon['url_video']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="cours-meta" style="margin-top: 12px;">
                            <span><?= icone('timer', 14) ?> <?= $lecon['duree'] ?? 0 ?> minutes</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajout Leçon -->
<div id="addLeconModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="modal-header">
                <h3>Nouvelle leçon</h3>
                <button type="button" class="modal-close" onclick="closeModal('addLeconModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Titre de la leçon *</label>
                    <input type="text" name="titre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type de contenu</label>
                    <select name="type_contenu" id="type_contenu_add" class="form-select" onchange="toggleContentFields('add')">
                        <option value="texte">Texte</option>
                        <option value="pdf">PDF</option>
                        <option value="video">Vidéo</option>
                    </select>
                </div>
                <div id="add_texte_field" class="form-group">
                    <label class="form-label">Contenu texte</label>
                    <textarea name="contenu_texte"