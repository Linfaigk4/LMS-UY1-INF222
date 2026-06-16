<?php
/**
 * GOL (Gugle Online Learning) - Détail d'un cours
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$id_cours = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cours = obtenirCours($id_cours);

if (!$cours) {
    header('Location: index.php');
    exit;
}

$lecons = obtenirLecons($id_cours);
$progression = 0;
$lecons_terminees = [];

if (estConnecte() && estEtudiant()) {
    $progression_data = obtenirProgressionCours($_SESSION['id_utilisateur'], $id_cours);
    if ($progression_data) {
        $progression = $progression_data['pourcentage'];
        $lecons_terminees = json_decode($progression_data['lecons_terminees'], true) ?: [];
    }
}

$page_title = $cours['titre_cours'] . ' - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
.cours-header-section {
    background: linear-gradient(135deg, var(--primaire-sombre), var(--secondaire));
    padding: var(--spacing-8) 0;
    color: white;
}

.cours-navigation {
    margin-bottom: var(--spacing-6);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color var(--transition-base);
}

.back-link:hover {
    color: white;
}

.cours-title {
    font-size: 2rem;
    margin-bottom: var(--spacing-4);
}

.cours-infos {
    display: flex;
    gap: var(--spacing-6);
    flex-wrap: wrap;
    margin-top: var(--spacing-4);
}

.cours-info-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: 0.875rem;
    opacity: 0.8;
}

.progression-header {
    margin-top: var(--spacing-6);
    background: rgba(255,255,255,0.1);
    border-radius: var(--radius-full);
    padding: var(--spacing-1);
}

/* Layout leçons */
.lecons-container {
    display: flex;
    gap: var(--spacing-8);
    padding: var(--spacing-8) 0;
    flex-wrap: wrap;
}

.lecons-sidebar {
    flex: 1;
    min-width: 300px;
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
    position: sticky;
    top: 100px;
    height: fit-content;
}

.lecons-header {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    background: var(--fond-secondaire);
}

.lecons-header h3 {
    font-size: 1.125rem;
    margin-bottom: var(--spacing-2);
}

.lecons-count {
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.lecons-list {
    display: flex;
    flex-direction: column;
}

.lecon-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-4) var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    text-decoration: none;
    color: var(--texte);
    transition: all var(--transition-base);
    cursor: pointer;
}

.lecon-item:hover {
    background: var(--fond-secondaire);
}

.lecon-item.active {
    background: var(--fond-secondaire);
    border-left: 3px solid var(--primaire);
}

.lecon-icon {
    width: 32px;
    height: 32px;
    background: var(--fond-secondaire);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primaire);
}

.lecon-info {
    flex: 1;
}

.lecon-titre {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: var(--spacing-1);
}

.lecon-duree {
    font-size: 0.7rem;
    color: var(--texte-tertiaire);
}

.lecon-status {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-done {
    color: var(--succes);
}

.lecon-content {
    flex: 2;
    min-width: 300px;
}

.content-card {
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
}

.content-header {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    background: var(--fond-secondaire);
}

.content-title {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-2);
}

.content-body {
    padding: var(--spacing-6);
}

.content-texte {
    line-height: 1.8;
    color: var(--texte-secondaire);
}

.content-texte p {
    margin-bottom: var(--spacing-4);
}

.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.video-container video,
.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: var(--radius-lg);
}

.pdf-viewer {
    border: 1px solid var(--bordure);
    border-radius: var(--radius-lg);
    overflow: hidden;
    margin-bottom: var(--spacing-6);
}

.pdf-viewer iframe {
    width: 100%;
    height: 500px;
    border: none;
}

.btn-marquer-termine {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-6);
    background: var(--succes);
    color: white;
    border: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-base);
    margin-top: var(--spacing-6);
}

.btn-marquer-termine:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(34, 197, 94, 0.3);
}

.btn-marquer-termine.termine {
    background: var(--carte);
    border: 1px solid var(--succes);
    color: var(--succes);
}

.empty-lecons {
    text-align: center;
    padding: var(--spacing-12);
    color: var(--texte-tertiaire);
}

@media (max-width: 768px) {
    .lecons-container {
        flex-direction: column;
    }
    
    .lecons-sidebar {
        position: relative;
        top: 0;
    }
    
    .cours-title {
        font-size: 1.5rem;
    }
}
</style>

<div class="cours-header-section">
    <div class="container">
        <div class="cours-navigation">
            <a href="module.php?id=<?= $cours['id_module'] ?>" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Retour au module
            </a>
        </div>
        
        <h1 class="cours-title"><?= htmlspecialchars($cours['titre_cours']) ?></h1>
        <p><?= htmlspecialchars($cours['description'] ?? '') ?></p>
        
        <div class="cours-infos">
            <div class="cours-info-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?= $cours['duree'] ?? 0 ?> minutes
            </div>
            <div class="cours-info-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                Enseigné par <?= htmlspecialchars($cours['enseignant_prenom'] . ' ' . $cours['enseignant_nom']) ?>
            </div>
        </div>
        
        <?php if (estConnecte() && estEtudiant() && $progression > 0): ?>
            <div class="progression-header">
                <div class="progression-bar" style="width: <?= $progression ?>%; height: 8px; background: white; border-radius: var(--radius-full);"></div>
                <div style="font-size: 0.75rem; margin-top: var(--spacing-2);">Progression : <?= round($progression) ?>%</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="lecons-container">
        <!-- Sidebar des leçons -->
        <aside class="lecons-sidebar">
            <div class="lecons-header">
                <h3>Contenu du cours</h3>
                <div class="lecons-count"><?= count($lecons) ?> leçon<?= count($lecons) > 1 ? 's' : '' ?></div>
            </div>
            <div class="lecons-list">
                <?php if (empty($lecons)): ?>
                    <div class="empty-lecons">
                        <p>Aucune leçon disponible pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($lecons as $index => $lecon): ?>
                        <a href="?id=<?= $id_cours ?>&lecon=<?= $lecon['id_lecon'] ?>&module=<?= $cours['id_module'] ?>" 
                           class="lecon-item <?= (isset($_GET['lecon']) && $_GET['lecon'] == $lecon['id_lecon']) || (!isset($_GET['lecon']) && $index === 0) ? 'active' : '' ?>">
                            <div class="lecon-icon">
                                <?php if ($lecon['type_contenu'] === 'video'): ?>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                                        <polygon points="10 9 16 12 10 15"/>
                                    </svg>
                                <?php elseif ($lecon['type_contenu'] === 'pdf'): ?>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                <?php else: ?>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="lecon-info">
                                <div class="lecon-titre"><?= htmlspecialchars($lecon['titre_lecon']) ?></div>
                                <div class="lecon-duree"><?= $lecon['duree'] ?? 5 ?> min</div>
                            </div>
                            <div class="lecon-status">
                                <?php if (in_array($lecon['id_lecon'], $lecons_terminees)): ?>
                                    <svg class="status-done" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Contenu de la leçon -->
        <div class="lecon-content">
            <?php
            $lecon_id = isset($_GET['lecon']) ? (int)$_GET['lecon'] : ($lecons[0]['id_lecon'] ?? 0);
            $lecon_actuelle = obtenirLecon($lecon_id);
            
            if ($lecon_actuelle):
                $est_terminee = in_array($lecon_actuelle['id_lecon'], $lecons_terminees);
            ?>
                <div class="content-card">
                    <div class="content-header">
                        <h2 class="content-title"><?= htmlspecialchars($lecon_actuelle['titre_lecon']) ?></h2>
                    </div>
                    <div class="content-body">
                        <?php if ($lecon_actuelle['type_contenu'] === 'video' && $lecon_actuelle['url_video']): ?>
                            <div class="video-container">
                                <?php 
                                $video_url = $lecon_actuelle['url_video'];
                                if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false):
                                    // Extraire l'ID YouTube
                                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $video_url, $matches);
                                    $youtube_id = $matches[1] ?? '';
                                    if ($youtube_id):
                                ?>
                                    <iframe src="https://www.youtube.com/embed/<?= $youtube_id ?>" frameborder="0" allowfullscreen></iframe>
                                <?php 
                                    endif;
                                else:
                                ?>
                                    <video controls>
                                        <source src="<?= htmlspecialchars($video_url) ?>" type="video/mp4">
                                        Votre navigateur ne supporte pas la lecture vidéo.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($lecon_actuelle['type_contenu'] === 'pdf' && $lecon_actuelle['fichier_pdf']): ?>
                            <div class="pdf-viewer">
                                <iframe src="<?= htmlspecialchars($lecon_actuelle['fichier_pdf']) ?>" frameborder="0"></iframe>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($lecon_actuelle['contenu_texte']): ?>
                            <div class="content-texte">
                                <?= nl2br(htmlspecialchars($lecon_actuelle['contenu_texte'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (estConnecte() && estEtudiant()): ?>
                            <?php if (!$est_terminee): ?>
                                <button class="btn-marquer-termine" onclick="marquerTermine(<?= $lecon_actuelle['id_lecon'] ?>, <?= $id_cours ?>)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Marquer comme terminée
                                </button>
                            <?php else: ?>
                                <button class="btn-marquer-termine termine" disabled>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Leçon complétée ✓
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Évaluation associée -->
                        <?php
                        $evaluations = obtenirEvaluationsParLecon($lecon_actuelle['id_lecon']);
                        if (!empty($evaluations) && $est_terminee):
                        ?>
                            <div style="margin-top: var(--spacing-8); padding-top: var(--spacing-6); border-top: 1px solid var(--bordure);">
                            <h3>
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
                                    stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    style="vertical-align:middle;margin-right:8px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                Évaluation
                            </h3>
                                <p>Testez vos connaissances avec cette évaluation.</p>
                                <a href="evaluation.php?id=<?= $evaluations[0]['id_evaluation'] ?>&lecon=<?= $lecon_actuelle['id_lecon'] ?>&cours=<?= $id_cours ?>" class="btn-inscription" style="background: var(--primaire); color: white;">
                                    Commencer l'évaluation
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="content-card">
                    <div class="content-body" style="text-align: center; padding: var(--spacing-12);">
                        <p>Sélectionnez une leçon pour commencer.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function marquerTermine(idLecon, idCours) {
    envoyerRequeteAjax('maj_progression', 'POST', {
        lecon_id: idLecon,
        terminee: true
    }).then(result => {
        if (result.success) {
            afficherNotification('Leçon terminée !', 'succes');
            location.reload();
        } else {
            afficherNotification(result.message || 'Erreur', 'danger');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>