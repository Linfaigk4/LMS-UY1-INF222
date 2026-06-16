<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * GOL (Gugle Online Learning) - Gestion des leçons (Enseignant)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$id_cours = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : 0;

// Récupérer le cours
$stmt = $pdo->prepare("SELECT * FROM cours WHERE id_cours = ?");
$stmt->execute([$id_cours]);
$cours = $stmt->fetch();

if (!$cours) {
    // Rediriger vers la liste des cours
    header('Location: gestion_cours.php');
    exit;
}

$message = '';
$error = '';
$show_form = isset($_GET['form']) ? true : false;

// Fonction d'upload
function uploadFichier($fichier, $type) {
    $upload_dir = __DIR__ . '/uploads/' . $type . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    $allowed = $type === 'pdf' ? ['pdf'] : ['mp4', 'webm', 'ogg'];
    
    if (!in_array($extension, $allowed)) {
        return ['success' => false, 'message' => 'Extension non autorisée. Formats acceptés: ' . implode(', ', $allowed)];
    }
    
    $nom_fichier = uniqid() . '.' . $extension;
    $chemin = $upload_dir . $nom_fichier;
    
    if (move_uploaded_file($fichier['tmp_name'], $chemin)) {
        return ['success' => true, 'fichier' => 'uploads/' . $type . '/' . $nom_fichier];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier.'];
}

// Ajouter une leçon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu_texte = trim($_POST['contenu_texte'] ?? '');
    $type_contenu = $_POST['type_contenu'] ?? 'texte';
    $duree = (int)($_POST['duree'] ?? 0);
    $fichier_pdf = null;
    $url_video = null;
    
    // Gestion du PDF
    if ($type_contenu === 'pdf' && isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFichier($_FILES['fichier_pdf'], 'pdf');
        if ($upload['success']) {
            $fichier_pdf = $upload['fichier'];
        } else {
            $error = $upload['message'];
        }
    }
    
    // Gestion de la vidéo
    if ($type_contenu === 'video') {
        if (isset($_FILES['fichier_video']) && $_FILES['fichier_video']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFichier($_FILES['fichier_video'], 'videos');
            if ($upload['success']) {
                $url_video = $upload['fichier'];
            } else {
                $error = $upload['message'];
            }
        } elseif (!empty($_POST['url_video'])) {
            $url_video = $_POST['url_video'];
        }
    }
    
    if (empty($titre)) {
        $error = 'Veuillez entrer un titre.';
    } elseif ($type_contenu === 'pdf' && !$fichier_pdf && !$error) {
        $error = 'Veuillez sélectionner un fichier PDF.';
    } elseif ($type_contenu === 'video' && !$url_video && !$error) {
        $error = 'Veuillez fournir une URL vidéo ou uploader un fichier.';
    } else {
        // Insérer la leçon
        $stmt = $pdo->prepare("
            INSERT INTO lecons (titre_lecon, contenu_texte, type_contenu, fichier_pdf, url_video, duree, id_cours)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$titre, $contenu_texte, $type_contenu, $fichier_pdf, $url_video, $duree, $id_cours])) {
            $message = 'Leçon ajoutée avec succès !';
            $show_form = false;
        } else {
            $error = 'Erreur lors de l\'ajout de la leçon.';
        }
    }
}

// Supprimer une leçon
if (isset($_GET['delete'])) {
    $id_lecon = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM lecons WHERE id_lecon = ?");
    if ($stmt->execute([$id_lecon])) {
        $message = 'Leçon supprimée avec succès !';
    } else {
        $error = 'Erreur lors de la suppression.';
    }
}

// Récupérer les leçons
$stmt = $pdo->prepare("SELECT * FROM lecons WHERE id_cours = ? ORDER BY id_lecon ASC");
$stmt->execute([$id_cours]);
$lecons = $stmt->fetchAll();

$page_title = 'Gestion des leçons - ' . $cours['titre_cours'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }
        .card { background: white; border-radius: 16px; padding: 24px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.875rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #2563eb; }
        .form-textarea { resize: vertical; min-height: 100px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .lecon-item { background: white; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid #e2e8f0; }
        .lecon-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
        .badge { padding: 4px 12px; border-radius: 999px; font-size: 12px; background: #e2e8f0; }
        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
        h1 { font-size: 1.75rem; margin-bottom: 8px; }
        .back-link { color: #2563eb; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { text-decoration: underline; }
        .help-text { font-size: 0.7rem; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <a href="gestion_cours.php" class="back-link">← Retour aux cours</a>

    <h1>📖 <?= htmlspecialchars($cours['titre_cours']) ?></h1>
    <p style="color: #64748b; margin-bottom: 24px;">Gestion des leçons (Texte, PDF, Vidéo)</p>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$show_form): ?>
        <a href="?cours_id=<?= $id_cours ?>&form=1" class="btn btn-primary">+ Nouvelle leçon</a>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <?php if ($show_form): ?>
    <div class="card" style="margin-top: 20px;">
        <h3 style="margin-bottom: 20px;">Créer une nouvelle leçon</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Titre de la leçon *</label>
                <input type="text" name="titre" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Type de contenu</label>
                <select name="type_contenu" id="type_contenu" class="form-select">
                    <option value="texte">📝 Texte</option>
                    <option value="pdf">📄 PDF (upload)</option>
                    <option value="video">🎥 Vidéo (upload ou lien)</option>
                </select>
            </div>
            
            <div id="champ_texte" class="form-group">
                <label class="form-label">Contenu texte</label>
                <textarea name="contenu_texte" class="form-textarea" placeholder="Écrivez votre contenu..."></textarea>
            </div>
            
            <div id="champ_pdf" class="form-group" style="display: none;">
                <label class="form-label">Fichier PDF *</label>
                <input type="file" name="fichier_pdf" class="form-input" accept=".pdf">
                <div class="help-text">Sélectionnez un fichier PDF à uploader</div>
            </div>
            
            <div id="champ_video" class="form-group" style="display: none;">
                <label class="form-label">URL Vidéo (YouTube)</label>
                <input type="text" name="url_video" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
                <div class="help-text">OU uploader un fichier vidéo ci-dessous</div>
                <input type="file" name="fichier_video" class="form-input" style="margin-top: 8px;" accept=".mp4,.webm,.ogg">
                <div class="help-text">Formats acceptés: MP4, WebM, OGG</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Durée (minutes)</label>
                <input type="number" name="duree" class="form-input" value="15">
            </div>
            
            <div class="form-actions">
                <a href="?cours_id=<?= $id_cours ?>" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Créer la leçon</button>
            </div>
        </form>
    </div>
    
    <script>
    const typeSelect = document.getElementById('type_contenu');
    const champTexte = document.getElementById('champ_texte');
    const champPdf = document.getElementById('champ_pdf');
    const champVideo = document.getElementById('champ_video');
    
    function toggleFields() {
        const type = typeSelect.value;
        champTexte.style.display = type === 'texte' ? 'block' : 'none';
        champPdf.style.display = type === 'pdf' ? 'block' : 'none';
        champVideo.style.display = type === 'video' ? 'block' : 'none';
    }
    
    typeSelect.addEventListener('change', toggleFields);
    toggleFields();
    </script>
    <?php endif; ?>

    <!-- Liste des leçons -->
    <div style="margin-top: 30px;">
        <h3>Leçons existantes (<?= count($lecons) ?>)</h3>
        <?php if (empty($lecons) && !$show_form): ?>
            <div class="empty-state">
                <p>Aucune leçon pour ce cours.</p>
                <a href="?cours_id=<?= $id_cours ?>&form=1" class="btn btn-primary" style="margin-top: 16px;">+ Ajouter une leçon</a>
            </div>
        <?php else: ?>
            <?php foreach ($lecons as $index => $lecon): ?>
                <div class="lecon-item">
                    <div class="lecon-header">
                        <div>
                            <strong><?= ($index+1) . '. ' . htmlspecialchars($lecon['titre_lecon']) ?></strong>
                            <span class="badge"><?= $lecon['type_contenu'] ?></span>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Supprimer cette leçon ?')) window.location.href='?cours_id=<?= $id_cours ?>&delete=<?= $lecon['id_lecon'] ?>'">🗑️ Supprimer</button>
                    </div>
                    <?php if ($lecon['type_contenu'] === 'texte' && $lecon['contenu_texte']): ?>
                        <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-top: 8px; font-size: 0.875rem; color: #475569;">
                            <?= htmlspecialchars(substr($lecon['contenu_texte'], 0, 150)) ?>...
                        </div>
                    <?php elseif ($lecon['type_contenu'] === 'pdf' && $lecon['fichier_pdf']): ?>
                        <div style="margin-top: 8px; font-size: 0.875rem;">📄 PDF: <?= basename($lecon['fichier_pdf']) ?></div>
                        <div style="margin-top: 4px;">
                            <a href="/GOL/<?= $lecon['fichier_pdf'] ?>" target="_blank" class="btn btn-primary btn-sm">Voir le PDF</a>
                        </div>
                    <?php elseif ($lecon['type_contenu'] === 'video' && $lecon['url_video']): ?>
                        <div style="margin-top: 8px; font-size: 0.875rem;">🎥 Vidéo: <?= htmlspecialchars($lecon['url_video']) ?></div>
                    <?php endif; ?>
                    <div style="margin-top: 8px; font-size: 0.75rem; color: #64748b;">⏱️ <?= $lecon['duree'] ?? 0 ?> minutes</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Créer les dossiers d'upload si nécessaire
if (!is_dir(__DIR__ . '/uploads/pdf')) mkdir(__DIR__ . '/uploads/pdf', 0777, true);
if (!is_dir(__DIR__ . '/uploads/videos')) mkdir(__DIR__ . '/uploads/videos', 0777, true);
?>
</body>
</html>