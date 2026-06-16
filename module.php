<?php
/**
 * GOL (Gugle Online Learning) - Détail d'un module
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$id_module = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$module = obtenirModule($id_module);

if (!$module) {
    header('Location: index.php');
    exit;
}

$cours = obtenirCoursParModule($id_module);
$est_inscrit = false;
$progression_globale = 0;

if (estConnecte() && estEtudiant()) {
    $est_inscrit = estInscritModule($_SESSION['id_utilisateur'], $id_module);
    if ($est_inscrit) {
        $progression_globale = calculerProgressionModule($_SESSION['id_utilisateur'], $id_module);
    }
}

$page_title = $module['nom_module'] . ' - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
.module-hero {
    background: linear-gradient(135deg, var(--primaire-sombre), var(--secondaire));
    padding: var(--spacing-12) 0;
    color: white;
    position: relative;
    overflow: hidden;
}

.module-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"/></svg>') no-repeat bottom;
    background-size: cover;
    opacity: 0.1;
}

.module-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
}

.module-badge {
    display: inline-block;
    padding: var(--spacing-1) var(--spacing-3);
    background: rgba(255,255,255,0.2);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    margin-bottom: var(--spacing-4);
}

.module-title {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-4);
}

.module-description {
    opacity: 0.9;
    margin-bottom: var(--spacing-6);
    line-height: 1.6;
}

.module-meta {
    display: flex;
    gap: var(--spacing-6);
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: 0.875rem;
    opacity: 0.8;
}

.module-actions {
    margin-top: var(--spacing-8);
    display: flex;
    gap: var(--spacing-4);
    flex-wrap: wrap;
}

.btn-inscription {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-8);
    background: white;
    color: var(--primaire);
    border-radius: var(--radius-full);
    font-weight: 600;
    text-decoration: none;
    transition: all var(--transition-base);
}

.btn-inscription:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.btn-continuer {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-8);
    background: transparent;
    border: 2px solid white;
    color: white;
    border-radius: var(--radius-full);
    font-weight: 600;
    text-decoration: none;
    transition: all var(--transition-base);
}

.btn-continuer:hover {
    background: white;
    color: var(--primaire);
}

.progression-globale {
    margin-top: var(--spacing-6);
    background: rgba(255,255,255,0.1);
    border-radius: var(--radius-full);
    padding: var(--spacing-1);
}

.progression-bar {
    height: 8px;
    background: linear-gradient(90deg, var(--succes), var(--primaire-clair));
    border-radius: var(--radius-full);
    width: 0%;
    transition: width 0.5s ease;
}

.progression-texte {
    font-size: 0.75rem;
    margin-top: var(--spacing-2);
    opacity: 0.8;
}

/* Section objectifs */
.objectifs-section {
    background: var(--fond-secondaire);
    padding: var(--spacing-8) 0;
}

.objectifs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-6);
    margin-top: var(--spacing-6);
}

.objectif-card {
    background: var(--carte);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    border: 1px solid var(--bordure);
    transition: all var(--transition-base);
}

.objectif-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ombre-lg);
}

.objectif-icon {
    width: 48px;
    height: 48px;
    background: var(--fond-secondaire);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-4);
    color: var(--primaire);
}

.objectif-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: var(--spacing-2);
}

.objectif-text {
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

/* Liste des cours */
.cours-list {
    padding: var(--spacing-12) 0;
}

.cours-item {
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
    margin-bottom: var(--spacing-6);
    overflow: hidden;
    transition: all var(--transition-base);
}

.cours-item:hover {
    transform: translateX(8px);
    box-shadow: var(--ombre-lg);
}

.cours-header {
    padding: var(--spacing-6);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
    border-bottom: 1px solid var(--bordure);
    background: var(--fond-secondaire);
}

.cours-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.cours-level {
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.level-debutant { background: var(--succes); color: white; }
.level-intermediaire { background: var(--avertissement); color: white; }
.level-avance { background: var(--danger); color: white; }
.level-expert { background: var(--primaire-sombre); color: white; }

.cours-body {
    padding: var(--spacing-6);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.cours-description {
    flex: 1;
    color: var(--texte-secondaire);
    font-size: 0.875rem;
}

.cours-info {
    display: flex;
    gap: var(--spacing-4);
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.btn-voir-cours {
    padding: var(--spacing-2) var(--spacing-6);
    background: var(--primaire);
    color: white;
    border-radius: var(--radius-full);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all var(--transition-base);
}

.btn-voir-cours:hover {
    background: var(--primaire-sombre);
    transform: translateY(-2px);
}

.empty-cours {
    text-align: center;
    padding: var(--spacing-12);
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
}

@media (max-width: 768px) {
    .module-title {
        font-size: 1.75rem;
    }
    
    .cours-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .cours-body {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="module-hero">
    <div class="container">
        <div class="module-hero-content">
            <span class="module-badge">Module <?= $module['niveau'] === 'debutant' ? 'Débutant' : ($module['niveau'] === 'intermediaire' ? 'Intermédiaire' : 'Avancé') ?></span>
            <h1 class="module-title"><?= htmlspecialchars($module['nom_module']) ?></h1>
            <p class="module-description"><?= nl2br(htmlspecialchars($module['description'] ?? '')) ?></p>
            
            <div class="module-meta">
                <div class="meta-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?= count($cours) ?> cours
                </div>
                <div class="meta-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <?= $module['duree_totale'] ?? 0 ?> minutes
                </div>
            </div>
            
            <?php if (estConnecte() && estEtudiant()): ?>
                <div class="module-actions">
                    <?php if (!$est_inscrit): ?>
                        <a href="?action=inscrire&id=<?= $id_module ?>" class="btn-inscription" onclick="return inscrireModule(event, <?= $id_module ?>)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            S'inscrire au module
                        </a>
                    <?php else: ?>
                        <a href="?id=<?= $id_module ?>" class="btn-continuer" onclick="return continuerModule(<?= $id_module ?>)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                            Continuer ma progression
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($est_inscrit && $progression_globale > 0): ?>
                    <div class="progression-globale">
                        <div class="progression-bar" style="width: <?= $progression_globale ?>%"></div>
                        <div class="progression-texte">Progression globale : <?= round($progression_globale) ?>%</div>
                    </div>
                <?php endif; ?>
                
            <?php elseif (!estConnecte()): ?>
                <div class="module-actions">
                    <a href="connexion.php" class="btn-inscription">
                        Connectez-vous pour vous inscrire
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($module['objectifs'])): ?>
<div class="objectifs-section">
    <div class="container">
        <h2 style="text-align: center;">Objectifs pédagogiques</h2>
        <div class="objectifs-grid">
            <?php 
            $objectifs = explode("\n", $module['objectifs']);
            foreach ($objectifs as $index => $objectif):
                if (trim($objectif)):
            ?>
            <div class="objectif-card">
                <div class="objectif-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                </div>
                <div class="objectif-title">Objectif <?= $index + 1 ?></div>
                <div class="objectif-text"><?= htmlspecialchars(trim($objectif)) ?></div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="cours-list">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: var(--spacing-8);">Programme du module</h2>
        
        <?php if (empty($cours)): ?>
            <div class="empty-cours">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: var(--spacing-4); opacity: 0.5;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>Aucun cours n'est encore disponible dans ce module.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cours as $index => $cour): ?>
                <div class="cours-item">
                    <div class="cours-header">
                        <h3 class="cours-title"><?= ($index + 1) . '. ' . htmlspecialchars($cour['titre_cours']) ?></h3>
                        <span class="cours-level level-<?= $cour['difficulte'] ?>">
                            <?= ucfirst($cour['difficulte']) ?>
                        </span>
                    </div>
                    <div class="cours-body">
                        <div class="cours-description">
                            <?= htmlspecialchars(substr($cour['description'] ?? '', 0, 150)) ?>...
                        </div>
                        <a href="cours.php?id=<?= $cour['id_cours'] ?>&module=<?= $id_module ?>" class="btn-voir-cours">
                            Voir le cours
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function inscrireModule(event, idModule) {
    event.preventDefault();
    
    if (confirm('Voulez-vous vous inscrire à ce module ?')) {
        envoyerRequeteAjax('inscrire_module', 'POST', { id_module: idModule })
            .then(result => {
                if (result.success) {
                    afficherNotification('Inscription réussie !', 'succes');
                    location.reload();
                } else {
                    afficherNotification(result.message || 'Erreur lors de l\'inscription', 'danger');
                }
            });
    }
}

function continuerModule(idModule) {
    // Rediriger vers le premier cours disponible
    window.location.href = 'cours.php?module=' + idModule;
    return false;
}
</script>

<?php include 'includes/footer.php'; ?>