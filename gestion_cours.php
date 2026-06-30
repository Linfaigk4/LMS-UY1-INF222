<?php
/**
 * GOL (Gugle Online Learning) - Gestion des cours (Enseignant)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/header.php';

// Contrôle d'accès strict — enseignant ou super admin uniquement
if (!estConnecte() || (!estEnseignant() && !estSuperAdmin())) {
    header('Location: connexion.php');
    exit;
}

$page_title = 'Gestion des cours - GOL';
$message = '';
$error = '';
$id_cours = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : 0;

// Mode gestion des leçons si cours_id fourni
if ($id_cours > 0) {
    // Récupérer le cours
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id_cours = ?");
    $stmt->execute([$id_cours]);
    $cours = $stmt->fetch();
    
    if (!$cours) {
        // Cours non trouvé, rediriger vers la liste
        header('Location: gestion_cours.php');
        exit;
    }
    
    $page_title = 'Gestion des leçons - ' . $cours['titre_cours'];
    $show_form = isset($_GET['form']) ? true : false;
    
    // Ajouter une leçon
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        verifierTokenCSRF();
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
        
        if ($type_contenu === 'video') {
            if (isset($_FILES['fichier_video']) && $_FILES['fichier_video']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadFichierLecon($_FILES['fichier_video'], 'video');
                if ($upload['success']) {
                    $url_video = $upload['chemin'];
                } else {
                    $error = $upload['message'];
                }
            } elseif (!empty($_POST['url_video'])) {
                $url_video = trim($_POST['url_video']);
            }
        }
        
        if (empty($titre)) {
            $error = 'Veuillez entrer un titre.';
        } elseif ($type_contenu === 'pdf' && !$fichier_pdf && !$error) {
            $error = 'Veuillez sélectionner un fichier PDF.';
        } elseif ($type_contenu === 'video' && !$url_video && !$error) {
            $error = 'Veuillez fournir une URL vidéo ou uploader un fichier.';
        } elseif (empty($error)) {
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
    
    // Supprimer une leçon — anti-IDOR
    if (isset($_GET['delete'])) {
        $id_lecon = (int)$_GET['delete'];
        $id_ens   = estSuperAdmin() ? null : $_SESSION['id_utilisateur'];
        $ok = supprimerLecon($id_lecon, $id_ens);
        if ($ok) {
            $message = 'Leçon supprimée avec succès !';
        } else {
            $error = 'Suppression refusée (accès non autorisé ou erreur).';
        }
    }
    
    // Récupérer les leçons
    $stmt = $pdo->prepare("SELECT * FROM lecons WHERE id_cours = ? ORDER BY id_lecon ASC");
    $stmt->execute([$id_cours]);
    $lecons = $stmt->fetchAll();
    
} else {
    // Mode liste des cours
    $page_title = 'Gestion des cours - GOL';
    
    // Récupérer les cours de l'enseignant (ou tous les cours si super admin)
    if (estSuperAdmin()) {
        $stmt = $pdo->query("SELECT * FROM cours ORDER BY date_creation DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cours WHERE id_enseignant = ? ORDER BY date_creation DESC");
        $stmt->execute([$_SESSION['id_utilisateur']]);
    }
    $cours_list = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.875rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
        h1 { font-size: 1.75rem; margin-bottom: 8px; }
        h2 { font-size: 1.25rem; margin-bottom: 16px; margin-top: 20px; }
        h3 { font-size: 1rem; margin-bottom: 16px; }
        .back-link { color: #2563eb; text-decoration: none; display: inline-block; margin-bottom: 20px; transition: all 0.3s; }
        .back-link:hover { text-decoration: underline; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .course-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; transition: all 0.3s; }
        .course-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .course-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 8px; color: #0f172a; }
        .course-desc { font-size: 0.875rem; color: #475569; margin-bottom: 12px; line-height: 1.5; }
        .course-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; background: #e2e8f0; margin-bottom: 12px; }
        .course-actions { display: flex; gap: 8px; margin-top: 16px; }
        .course-actions a { flex: 1; text-align: center; padding: 8px 12px; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px dashed #cbd5e1; }
        .empty-state p { color: #64748b; margin-bottom: 20px; }
        .lecon-item { background: white; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid #e2e8f0; }
        .lecon-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; background: #e2e8f0; }
        .help-text { font-size: 0.75rem; color: #64748b; margin-top: 4px; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
<?php if ($id_cours > 0): ?>
    <!-- Mode gestion des leçons -->
    <a href="gestion_cours.php" class="back-link">← Retour aux cours</a>
    
    <h1><?= htmlspecialchars($cours['titre_cours']) ?></h1>
    <p style="color: #64748b; margin-bottom: 24px;">Gestion des leçons (Texte, PDF, Vidéo)</p>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$show_form): ?>
        <a href="?cours_id=<?= $id_cours ?>&form=1" class="btn btn-primary">+ Nouvelle leçon</a>
    <?php endif; ?>
    
    <!-- Formulaire d'ajout -->
    <?php if ($show_form): ?>
    <div class="card" style="margin-top: 20px;">
        <h3>Créer une nouvelle leçon</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Titre de la leçon *</label>
                <input type="text" name="titre" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Type de contenu</label>
                <select name="type_contenu" id="type_contenu" class="form-select">
                    <option value="texte">Texte</option>
                    <option value="pdf">PDF (upload)</option>
                    <option value="video">Vidéo (upload ou lien)</option>
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
        <h2>Leçons existantes (<?= count($lecons) ?>)</h2>
        <?php if (empty($lecons) && !$show_form): ?>
            <div class="empty-state">
                <p>Aucune leçon pour ce cours.</p>
                <a href="?cours_id=<?= $id_cours ?>&form=1" class="btn btn-primary">+ Ajouter une leçon</a>
            </div>
        <?php else: ?>
            <?php foreach ($lecons as $index => $lecon): ?>
                <div class="lecon-item">
                    <div class="lecon-header">
                        <div>
                            <strong><?= ($index+1) . '. ' . htmlspecialchars($lecon['titre_lecon']) ?></strong>
                            <span class="badge"><?= htmlspecialchars($lecon['type_contenu']) ?></span>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Supprimer cette leçon ?')) window.location.href='?cours_id=<?= $id_cours ?>&delete=<?= $lecon['id_lecon'] ?>'">Supprimer</button>
                    </div>
                    <?php if ($lecon['type_contenu'] === 'texte' && $lecon['contenu_texte']): ?>
                        <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-top: 8px; font-size: 0.875rem; color: #475569;">
                            <?= htmlspecialchars(substr($lecon['contenu_texte'], 0, 150)) ?>...
                        </div>
                    <?php elseif ($lecon['type_contenu'] === 'pdf' && $lecon['fichier_pdf']): ?>
                        <div style="margin-top: 8px; font-size: 0.875rem;">PDF: <?= basename($lecon['fichier_pdf']) ?></div>
                        <div style="margin-top: 4px;">
                            <a href="/GOL/<?= $lecon['fichier_pdf'] ?>" target="_blank" class="btn btn-primary btn-sm">Voir le PDF</a>
                        </div>
                    <?php elseif ($lecon['type_contenu'] === 'video' && $lecon['url_video']): ?>
                        <div style="margin-top: 8px; font-size: 0.875rem;">Vidéo: <?= htmlspecialchars($lecon['url_video']) ?></div>
                    <?php endif; ?>
                    <div style="margin-top: 8px; font-size: 0.75rem; color: #64748b;">⏱ <?= $lecon['duree'] ?? 0 ?> minutes</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Mode liste des cours -->
    <div class="flex-between">
        <div>
            <h1>Gestion des cours</h1>
            <p style="color: #64748b;">Créez, modifiez et gérez vos cours</p>
        </div>
        <a href="creer_cours.php" class="btn btn-primary">+ Créer un cours</a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cours_list)): ?>
        <div class="empty-state">
            <p>Vous n'avez pas encore créé de cours.</p>
            <a href="creer_cours.php" class="btn btn-primary">+ Créer votre premier cours</a>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($cours_list as $course): ?>
                <div class="course-card">
                    <div class="course-badge"><?= htmlspecialchars($course['difficulte'] ?? 'debutant') ?></div>
                    <h3 class="course-title"><?= htmlspecialchars($course['titre_cours']) ?></h3>
                    <p class="course-desc"><?= htmlspecialchars(substr($course['description'] ?? '', 0, 100)) ?>...</p>
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 12px;">
                        Statut: <strong><?= htmlspecialchars($course['statut'] ?? 'brouillon') ?></strong>
                    </div>
                    <div class="course-actions">
                        <a href="?cours_id=<?= $course['id_cours'] ?>" class="btn btn-secondary btn-sm">Leçons</a>
                        <a href="creer_cours.php?edit=<?= $course['id_cours'] ?>" class="btn btn-primary btn-sm">Modifier</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<?php
// Créer les dossiers d'upload si nécessaire
if (!is_dir(__DIR__ . '/uploads/pdf')) @mkdir(__DIR__ . '/uploads/pdf', 0777, true);
if (!is_dir(__DIR__ . '/uploads/videos')) @mkdir(__DIR__ . '/uploads/videos', 0777, true);
?>
</body>
</html>
