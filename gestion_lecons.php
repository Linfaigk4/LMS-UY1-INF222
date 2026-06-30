<?php
/**
 * GOL (Gugle Online Learning) - Gestion des leçons (Enseignant)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté et est enseignant ou promoteur ou super admin
if (!estConnecte() || (!estEnseignant() && !estPromoteur() && !estSuperAdmin())) {
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
    verifierTokenCSRF();
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
                <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
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
 class="form-textarea" placeholder="Écrivez votre contenu ici..." rows="6"></textarea>
                </div>
                <div id="add_pdf_field" class="form-group" style="display:none;">
                    <label class="form-label">Fichier PDF *</label>
                    <input type="file" name="fichier_pdf" class="form-input" accept=".pdf">
                </div>
                <div id="add_video_field" class="form-group" style="display:none;">
                    <label class="form-label">URL Vidéo (YouTube / Vimeo)</label>
                    <input type="text" name="url_video" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
                    <p style="font-size:0.75rem;color:var(--texte-tertiaire);margin-top:4px;">OU uploader un fichier vidéo :</p>
                    <input type="file" name="fichier_video" class="form-input" accept=".mp4,.webm,.ogg" style="margin-top:4px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Durée (minutes)</label>
                    <input type="number" name="duree" class="form-input" value="15" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('addLeconModal')">Annuler</button>
                <button type="submit" class="btn-primary">Créer la leçon</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifier Leçon -->
<div id="editLeconModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="modal-header">
                <h3>Modifier la leçon</h3>
                <button type="button" class="modal-close" onclick="closeModal('editLeconModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_lecon" id="edit_id_lecon" value="">
                <input type="hidden" name="fichier_pdf_existant" id="edit_fichier_pdf_existant" value="">
                <input type="hidden" name="url_video_existante" id="edit_url_video_existante" value="">
                <div class="form-group">
                    <label class="form-label">Titre de la leçon *</label>
                    <input type="text" name="titre" id="edit_titre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type de contenu</label>
                    <select name="type_contenu" id="type_contenu_edit" class="form-select" onchange="toggleContentFields('edit')">
                        <option value="texte">Texte</option>
                        <option value="pdf">PDF</option>
                        <option value="video">Vidéo</option>
                    </select>
                </div>
                <div id="edit_texte_field" class="form-group">
                    <label class="form-label">Contenu texte</label>
                    <textarea name="contenu_texte" id="edit_contenu_texte" class="form-textarea" rows="6"></textarea>
                </div>
                <div id="edit_pdf_field" class="form-group" style="display:none;">
                    <label class="form-label">Nouveau fichier PDF (laisser vide pour conserver l'actuel)</label>
                    <input type="file" name="fichier_pdf" class="form-input" accept=".pdf">
                    <p id="edit_pdf_actuel" style="font-size:0.75rem;color:var(--texte-tertiaire);margin-top:4px;"></p>
                </div>
                <div id="edit_video_field" class="form-group" style="display:none;">
                    <label class="form-label">URL Vidéo</label>
                    <input type="text" name="url_video" id="edit_url_video_input" class="form-input" placeholder="https://...">
                    <p style="font-size:0.75rem;color:var(--texte-tertiaire);margin-top:4px;">OU uploader un nouveau fichier :</p>
                    <input type="file" name="fichier_video" class="form-input" accept=".mp4,.webm,.ogg" style="margin-top:4px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Durée (minutes)</label>
                    <input type="number" name="duree" id="edit_duree" class="form-input" value="15" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('editLeconModal')">Annuler</button>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37,99,235,0.3);
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--fond-secondaire);
    color: var(--texte);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-lg);
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--transition-base);
}
.btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}
.btn-sm-edit {
    background: rgba(37,99,235,0.1);
    color: #2563eb;
}
.btn-sm-edit:hover {
    background: #2563eb;
    color: white;
}
.btn-sm-delete {
    background: rgba(239,68,68,0.1);
    color: #ef4444;
}
.btn-sm-delete:hover {
    background: #ef4444;
    color: white;
}
.btn-sm-publish {
    background: rgba(34,197,94,0.1);
    color: #22c55e;
}
.btn-sm-publish:hover {
    background: #22c55e;
    color: white;
}
.cours-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.alert {
    padding: 12px 16px;
    border-radius: var(--radius-lg);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-success {
    background: rgba(34,197,94,0.1);
    border: 1px solid #22c55e;
    color: #22c55e;
}
.alert-error {
    background: rgba(239,68,68,0.1);
    border: 1px solid #ef4444;
    color: #ef4444;
}
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}
.admin-title {
    font-size: 1.75rem;
    font-weight: 700;
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal.active {
    display: flex;
}
.modal-content {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    max-width: 560px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid var(--bordure);
}
.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--bordure);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--texte-secondaire);
    line-height: 1;
}
.modal-close:hover {
    color: var(--danger);
}
.modal-body {
    padding: 24px;
}
.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--bordure);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>

<script>
// Ouvrir / fermer les modales
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
}

// Fermer en cliquant hors de la modal
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Afficher / masquer les champs selon le type de contenu
function toggleContentFields(prefix) {
    const type = document.getElementById('type_contenu_' + prefix).value;
    const texte = document.getElementById(prefix + '_texte_field');
    const pdf   = document.getElementById(prefix + '_pdf_field');
    const video = document.getElementById(prefix + '_video_field');
    if (texte) texte.style.display = type === 'texte' ? 'block' : 'none';
    if (pdf)   pdf.style.display   = type === 'pdf'   ? 'block' : 'none';
    if (video) video.style.display = type === 'video' ? 'block' : 'none';
}

// Préremplir la modal d'édition
function editLecon(lecon) {
    document.getElementById('edit_id_lecon').value            = lecon.id_lecon      ?? '';
    document.getElementById('edit_titre').value               = lecon.titre_lecon   ?? '';
    document.getElementById('edit_contenu_texte').value       = lecon.contenu_texte ?? '';
    document.getElementById('edit_duree').value               = lecon.duree         ?? 15;
    document.getElementById('edit_fichier_pdf_existant').value = lecon.fichier_pdf  ?? '';
    document.getElementById('edit_url_video_existante').value  = lecon.url_video    ?? '';
    document.getElementById('edit_url_video_input').value     = lecon.url_video     ?? '';

    // Sélectionner le bon type
    const sel = document.getElementById('type_contenu_edit');
    if (sel) sel.value = lecon.type_contenu ?? 'texte';
    toggleContentFields('edit');

    // Afficher le nom du PDF actuel si présent
    const pdfInfo = document.getElementById('edit_pdf_actuel');
    if (pdfInfo) {
        pdfInfo.textContent = lecon.fichier_pdf
            ? 'Fichier actuel : ' + lecon.fichier_pdf.split('/').pop()
            : '';
    }

    openModal('editLeconModal');
}

// Initialiser les champs à l'ouverture du modal ajout
document.getElementById('addLeconModal')?.addEventListener('click', function() {});
// Afficher le bon champ par défaut au reset
document.getElementById('addLeconModal')?.addEventListener('transitionend', function() {
    toggleContentFields('add');
});
// Appliquer au chargement
toggleContentFields('add');
</script>

<?php include 'includes/footer.php'; ?>
