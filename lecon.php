<?php
/**
 * GOL (Gugle Online Learning) - Lecture d'une leçon (vue dédiée)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 * 
 * Ce fichier permet la lecture immersive d'une leçon avec
 * un focus sur le contenu (plein écran, mode lecture)
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$id_lecon = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lecon = obtenirLecon($id_lecon);

if (!$lecon) {
    header('Location: index.php');
    exit;
}

// Vérifier l'accès à la leçon (cours publié)
$cours = obtenirCours($lecon['id_cours']);
if (!$cours || $cours['statut'] !== 'publie') {
    header('Location: index.php');
    exit;
}

$progression = 0;
$lecon_terminee = false;

if (estConnecte() && estEtudiant()) {
    $progression_data = obtenirProgressionCours($_SESSION['id_utilisateur'], $lecon['id_cours']);
    if ($progression_data) {
        $lecons_terminees = json_decode($progression_data['lecons_terminees'], true) ?: [];
        $lecon_terminee = in_array($id_lecon, $lecons_terminees);
        $progression = $progression_data['pourcentage'];
    }
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'normal';
$page_title = $lecon['titre_lecon'] . ' - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles spécifiques pour la lecture de leçon */
.lecon-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: var(--spacing-6);
}

/* Navigation */
.lecon-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.nav-back {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--texte-secondaire);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color var(--transition-base);
}

.nav-back:hover {
    color: var(--primaire);
}

.lecon-progress {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    background: var(--fond-secondaire);
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
}

.progress-ring {
    width: 32px;
    height: 32px;
}

.view-mode-toggle {
    display: flex;
    gap: var(--spacing-1);
    background: var(--fond-secondaire);
    border-radius: var(--radius-full);
    padding: var(--spacing-1);
}

.mode-btn {
    padding: var(--spacing-2) var(--spacing-3);
    background: transparent;
    border: none;
    border-radius: var(--radius-full);
    cursor: pointer;
    transition: all var(--transition-base);
}

.mode-btn.active {
    background: var(--carte);
    box-shadow: var(--ombre-sm);
}

/* Mode plein écran */
.lecon-container.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--carte);
    z-index: 1000;
    overflow-y: auto;
    padding: var(--spacing-8);
}

/* Carte de contenu */
.lecon-card {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
    box-shadow: var(--ombre-lg);
}

.lecon-header {
    padding: var(--spacing-8);
    border-bottom: 1px solid var(--bordure);
    background: linear-gradient(135deg, var(--fond-secondaire), var(--carte));
}

.lecon-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-1) var(--spacing-3);
    background: var(--fond-secondaire);
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    margin-bottom: var(--spacing-4);
}

.lecon-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: var(--spacing-4);
    line-height: 1.2;
}

.lecon-meta {
    display: flex;
    gap: var(--spacing-4);
    flex-wrap: wrap;
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.lecon-body {
    padding: var(--spacing-8);
}

/* Contenu texte */
.lecon-texte {
    line-height: 1.8;
    color: var(--texte-secondaire);
    font-size: 1.05rem;
}

.lecon-texte h1, 
.lecon-texte h2, 
.lecon-texte h3 {
    color: var(--texte);
    margin-top: var(--spacing-6);
    margin-bottom: var(--spacing-4);
}

.lecon-texte p {
    margin-bottom: var(--spacing-4);
}

.lecon-texte ul, 
.lecon-texte ol {
    margin-bottom: var(--spacing-4);
    padding-left: var(--spacing-6);
}

.lecon-texte li {
    margin-bottom: var(--spacing-1);
}

.lecon-texte code {
    background: var(--fond-secondaire);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-md);
    font-family: monospace;
    font-size: 0.9em;
}

.lecon-texte pre {
    background: var(--fond-secondaire);
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    overflow-x: auto;
    margin-bottom: var(--spacing-4);
}

.lecon-texte blockquote {
    border-left: 4px solid var(--primaire);
    padding-left: var(--spacing-4);
    margin: var(--spacing-4) 0;
    font-style: italic;
    color: var(--texte-tertiaire);
}

/* Contenu vidéo */
.video-wrapper {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-6);
    background: var(--secondaire);
}

.video-wrapper video,
.video-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* Contenu PDF */
.pdf-wrapper {
    border-radius: var(--radius-xl);
    overflow: hidden;
    margin-bottom: var(--spacing-6);
    background: var(--fond-secondaire);
}

.pdf-wrapper iframe {
    width: 100%;
    height: 600px;
    border: none;
}

/* Actions de fin de leçon */
.lecon-actions {
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--bordure);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.btn-terminer {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-8);
    background: var(--succes);
    color: white;
    border: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-terminer:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(34, 197, 94, 0.3);
}

.btn-terminer.termine {
    background: var(--carte);
    border: 2px solid var(--succes);
    color: var(--succes);
}

.btn-terminer.termine:hover {
    background: var(--succes);
    color: white;
}

.navigation-lecons {
    display: flex;
    gap: var(--spacing-4);
}

.btn-nav-lecon {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-6);
    background: transparent;
    border: 1px solid var(--bordure);
    border-radius: var(--radius-full);
    color: var(--texte);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all var(--transition-base);
}

.btn-nav-lecon:hover {
    background: var(--fond-secondaire);
    transform: translateX(var(--translate, 0));
}

.btn-nav-lecon.prev:hover {
    --translate: -4px;
}

.btn-nav-lecon.next:hover {
    --translate: 4px;
}

/* Évaluation associée */
.evaluation-section {
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--bordure);
}

.evaluation-card {
    background: linear-gradient(135deg, var(--fond-secondaire), var(--carte));
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.evaluation-info h4 {
    font-size: 1.125rem;
    margin-bottom: var(--spacing-2);
}

.evaluation-info p {
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

.btn-evaluation {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-6);
    background: var(--primaire);
    color: white;
    border-radius: var(--radius-full);
    text-decoration: none;
    font-weight: 500;
    transition: all var(--transition-base);
}

.btn-evaluation:hover {
    transform: translateY(-2px);
    box-shadow: var(--ombre-glow);
}

/* Skeleton loading */
.skeleton {
    background: linear-gradient(90deg, var(--bordure) 25%, var(--carte-hover) 50%, var(--bordure) 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: var(--radius-md);
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Responsive */
@media (max-width: 768px) {
    .lecon-title {
        font-size: 1.5rem;
    }
    
    .lecon-header {
        padding: var(--spacing-6);
    }
    
    .lecon-body {
        padding: var(--spacing-6);
    }
    
    .lecon-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .navigation-lecons {
        justify-content: center;
    }
    
    .lecon-container.fullscreen {
        padding: var(--spacing-4);
    }
}

/* Mode focus (lecture uniquement) */
.lecon-container.focus-mode .lecon-navigation,
.lecon-container.focus-mode .lecon-actions,
.lecon-container.focus-mode .lecon-meta,
.lecon-container.focus-mode .lecon-badge,
.lecon-container.focus-mode .evaluation-section {
    display: none;
}

.lecon-container.focus-mode .lecon-header {
    padding-bottom: var(--spacing-4);
}

.lecon-container.focus-mode .lecon-body {
    padding-top: var(--spacing-4);
}
</style>

<div class="lecon-container" id="leconContainer">
    <!-- Navigation -->
    <div class="lecon-navigation">
        <a href="cours.php?id=<?= $lecon['id_cours'] ?>" class="nav-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Retour au cours
        </a>
        
        <div class="lecon-progress">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="12" x2="12" y2="6"/>
                <polyline points="12 22 12 12 18 12"/>
            </svg>
            <span>Progression globale : <?= round($progression) ?>%</span>
        </div>
        
        <div class="view-mode-toggle">
            <button class="mode-btn" data-mode="normal" title="Mode normal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </button>
            <button class="mode-btn" data-mode="focus" title="Mode focus (lecture)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                </svg>
            </button>
            <button class="mode-btn" data-mode="fullscreen" title="Plein écran">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="lecon-card animate-scaleIn">
        <div class="lecon-header">
            <div class="lecon-badge">
                <?php if ($lecon['type_contenu'] === 'video'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <polygon points="10 9 16 12 10 15"/>
                    </svg>
                    Leçon vidéo
                <?php elseif ($lecon['type_contenu'] === 'pdf'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Document PDF
                <?php else: ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    </svg>
                    Leçon texte
                <?php endif; ?>
                <?= ucfirst($lecon['type_contenu']) ?>
            </div>
            <h1 class="lecon-title"><?= htmlspecialchars($lecon['titre_lecon']) ?></h1>
            <div class="lecon-meta">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle;margin-right:6px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                Cours : <?= htmlspecialchars($cours['titre_cours']) ?>
            </span>

            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle;margin-right:6px;">
                    <circle cx="12" cy="12" r="9"/>
                    <polyline points="12 7 12 12 15 15"/>
                </svg>
                Durée estimée : <?= $lecon['duree'] ?? 5 ?> minutes
            </span>

            <?php if ($lecon_terminee): ?>
                <span style="color: var(--succes);">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                        stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        style="vertical-align:middle;margin-right:6px;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="8 12 11 15 16 9"/>
                    </svg>
                    Leçon complétée
                </span>
            <?php endif; ?>
            </div>
        </div>
        
        <div class="lecon-body">
            <!-- Vidéo -->
            <?php if ($lecon['type_contenu'] === 'video' && $lecon['url_video']): ?>
                <div class="video-wrapper">
                    <?php 
                    $video_url = $lecon['url_video'];
                    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false):
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $video_url, $matches);
                        $youtube_id = $matches[1] ?? '';
                        if ($youtube_id):
                    ?>
                        <iframe src="https://www.youtube.com/embed/<?= $youtube_id ?>?rel=0&modestbranding=1" frameborder="0" allowfullscreen></iframe>
                    <?php
                        endif;
                    elseif (strpos($video_url, 'vimeo.com') !== false):
                        preg_match('/vimeo\.com\/(\d+)/', $video_url, $mv);
                        $vimeo_id = $mv[1] ?? '';
                    ?>
                        <iframe src="https://player.vimeo.com/video/<?= $vimeo_id ?>" frameborder="0" allowfullscreen></iframe>
                    <?php else:
                        $video_src = SITE_URL . ltrim($video_url, '/');
                        $ext_v = strtolower(pathinfo($video_url, PATHINFO_EXTENSION));
                        $mime_v = $ext_v === 'webm' ? 'video/webm' : ($ext_v === 'ogg' ? 'video/ogg' : 'video/mp4');
                    ?>
                        <video controls preload="metadata" style="width:100%;border-radius:var(--radius-lg)">
                            <source src="<?= htmlspecialchars($video_src) ?>" type="<?= $mime_v ?>">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- PDF -->
            <?php if ($lecon['type_contenu'] === 'pdf' && $lecon['fichier_pdf']): ?>
                <div class="pdf-wrapper">
                    <?php $pdf_src = SITE_URL . ltrim($lecon['fichier_pdf'], '/'); ?>
                    <iframe src="<?= htmlspecialchars($pdf_src) ?>#toolbar=1" frameborder="0"></iframe>
                    <div style="padding:var(--spacing-3);text-align:right">
                        <a href="<?= htmlspecialchars($pdf_src) ?>" target="_blank" rel="noopener"
                           style="font-size:0.8rem;color:var(--primaire)">
                            Ouvrir le PDF dans un nouvel onglet
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Texte -->
            <?php if ($lecon['contenu_texte']): ?>
                <div class="lecon-texte">
                    <?= nl2br(htmlspecialchars($lecon['contenu_texte'])) ?>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="lecon-actions">
                <?php if (!estConnecte()): ?>
                    <div style="flex: 1;">
                        <a href="connexion.php" class="btn-terminer" style="background: var(--primaire);">
                            Connectez-vous pour suivre votre progression
                        </a>
                    </div>
                <?php elseif (estEtudiant()): ?>
                    <?php if (!$lecon_terminee): ?>
                        <button class="btn-terminer" onclick="marquerTerminee(<?= $lecon['id_lecon'] ?>, <?= $lecon['id_cours'] ?>)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Marquer comme terminée
                        </button>
                    <?php else: ?>
                        <button class="btn-terminer termine" disabled>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Leçon complétée ✓
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Navigation entre leçons -->
                <?php
                $lecons_cours = obtenirLecons($lecon['id_cours']);
                $current_index = array_search($id_lecon, array_column($lecons_cours, 'id_lecon'));
                $prev_lecon = $current_index > 0 ? $lecons_cours[$current_index - 1] : null;
                $next_lecon = $current_index < count($lecons_cours) - 1 ? $lecons_cours[$current_index + 1] : null;
                ?>
                
                <div class="navigation-lecons">
                    <?php if ($prev_lecon): ?>
                        <a href="lecon.php?id=<?= $prev_lecon['id_lecon'] ?>" class="btn-nav-lecon prev">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                            Leçon précédente
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($next_lecon): ?>
                        <a href="lecon.php?id=<?= $next_lecon['id_lecon'] ?>" class="btn-nav-lecon next">
                            Leçon suivante
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Évaluation associée -->
            <?php
            $evaluations = obtenirEvaluationsParLecon($lecon['id_lecon']);
            if (!empty($evaluations)):
            ?>
                <div class="evaluation-section">
                    <div class="evaluation-card">
                        <div class="evaluation-info">
                            <h4><svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                    stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    style="vertical-align:middle;margin-right:6px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                Évaluation : <?= htmlspecialchars($evaluations[0]['titre_evaluation']) ?>
                            </h4>
                            <p>Testez vos connaissances avec cette évaluation. Score minimum requis : <?= $evaluations[0]['note_requise'] ?>%</p>
                        </div>
                        <a href="evaluation.php?id=<?= $evaluations[0]['id_evaluation'] ?>&lecon=<?= $lecon['id_lecon'] ?>&cours=<?= $lecon['id_cours'] ?>" class="btn-evaluation">
                            Commencer l'évaluation
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Mode d'affichage
const container = document.getElementById('leconContainer');
const modeBtns = document.querySelectorAll('.mode-btn');

function setMode(mode) {
    // Mettre à jour les classes
    container.classList.remove('focus-mode', 'fullscreen');
    
    if (mode === 'focus') {
        container.classList.add('focus-mode');
    } else if (mode === 'fullscreen') {
        container.classList.add('fullscreen');
        
        // Demander le plein écran
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
    } else {
        // Quitter le plein écran si actif
        if (document.fullscreenElement) {
            document.exitFullscreen();
        }
    }
    
    // Mettre à jour l'état des boutons
    modeBtns.forEach(btn => {
        const btnMode = btn.dataset.mode;
        if ((mode === 'normal' && btnMode === 'normal') ||
            (mode === 'focus' && btnMode === 'focus') ||
            (mode === 'fullscreen' && btnMode === 'fullscreen')) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Sauvegarder la préférence
    localStorage.setItem('lecon_mode', mode);
}

modeBtns.forEach(btn => {
    btn.addEventListener('click', () => setMode(btn.dataset.mode));
});

// Charger la préférence sauvegardée
const savedMode = localStorage.getItem('lecon_mode');
if (savedMode && savedMode !== 'normal') {
    setMode(savedMode);
}

// Quitter le plein écran à la sortie
document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement && container.classList.contains('fullscreen')) {
        container.classList.remove('fullscreen');
        setMode('normal');
    }
});

function marquerTerminee(idLecon, idCours) {
    envoyerRequeteAjax('maj_progression', 'POST', {
        lecon_id: idLecon,
        terminee: true
    }).then(result => {
        if (result.success) {
            afficherNotification('Leçon terminée ! Votre progression a été mise à jour.', 'succes');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            afficherNotification(result.message || 'Erreur lors de la mise à jour', 'danger');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>