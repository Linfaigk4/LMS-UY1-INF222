<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * GOL (Gugle Online Learning) - Tableau de bord multi-rôles
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    header('Location: connexion.php');
    exit;
}

$utilisateur = obtenirUtilisateur();
$role = $_SESSION['role'];

// REDIRECTION POUR SUPER ADMIN VERS ADMINISTRATION.PHP
if ($role === 'super_admin') {
    header('Location: administration.php');
    exit;
}

// Initialisation des variables
$statistiques = [];
$modules_inscrits = [];
$cours_enseignant = [];
$demandes_modification = [];
$activites_recentes = [];
$notifications = [];

// Récupérer les notifications
if (function_exists('obtenirNotificationsNonLues')) {
    $notifications = obtenirNotificationsNonLues($_SESSION['id_utilisateur']);
}

// Statistiques selon le rôle
if ($role === 'etudiant') {
    if (function_exists('obtenirStatistiquesEtudiant')) {
        $statistiques = obtenirStatistiquesEtudiant($_SESSION['id_utilisateur']);
    }
    if (function_exists('obtenirModulesInscrits')) {
        $modules_inscrits = obtenirModulesInscrits($_SESSION['id_utilisateur']);
    }
    if (function_exists('obtenirActivitesRecentes')) {
        $activites_recentes = obtenirActivitesRecentes($_SESSION['id_utilisateur'], 5);
    }
} elseif ($role === 'enseignant') {
    if (function_exists('obtenirStatistiquesEnseignant')) {
        $statistiques = obtenirStatistiquesEnseignant($_SESSION['id_utilisateur']);
    }
    if (function_exists('obtenirCoursParEnseignant')) {
        $cours_enseignant = obtenirCoursParEnseignant($_SESSION['id_utilisateur']);
    }
} elseif ($role === 'promoteur') {
    if (function_exists('obtenirStatistiquesPromoteur')) {
        $statistiques = obtenirStatistiquesPromoteur();
    }
    if (function_exists('obtenirDemandesEnAttente')) {
        $demandes_modification = obtenirDemandesEnAttente();
    }
}

$page_title = 'Tableau de bord - ' . SITE_NAME;
?>

<?php include 'includes/header.php'; ?>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 24px;
}
.welcome-section {
    margin-bottom: 32px;
}
.welcome-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.welcome-subtitle {
    color: var(--texte-secondaire, #475569);
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}
.stat-card {
    background: var(--carte, white);
    border-radius: 20px;
    padding: 24px;
    border: 1px solid var(--bordure, #e2e8f0);
    transition: all 0.3s;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
}
.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #2563eb;
    margin-bottom: 4px;
}
.stat-label {
    color: var(--texte-secondaire, #475569);
    font-size: 0.875rem;
}
.dashboard-main {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    margin-bottom: 32px;
}
.section-card {
    background: var(--carte, white);
    border-radius: 20px;
    border: 1px solid var(--bordure, #e2e8f0);
    overflow: hidden;
}
.section-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--bordure, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.section-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}
.section-link {
    color: #2563eb;
    text-decoration: none;
    font-size: 0.875rem;
}
.section-content {
    padding: 24px;
}
.item-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.item-info h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 4px;
}
.item-info p {
    font-size: 0.75rem;
    color: var(--texte-tertiaire, #64748b);
}
.statut-badge {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
}
.statut-badge.publie {
    background: rgba(34,197,94,0.1);
    color: #22c55e;
}
.statut-badge.brouillon {
    background: rgba(245,158,11,0.1);
    color: #f59e0b;
}
.statut-badge.en_attente {
    background: rgba(245,158,11,0.1);
    color: #f59e0b;
}
.notif-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 16px;
    background: var(--fond-secondaire, #f1f5f9);
    cursor: pointer;
}
.notif-item.unread {
    background: rgba(37,99,235,0.08);
    border-left: 3px solid #2563eb;
}
.notif-content {
    flex: 1;
}
.notif-title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 4px;
}
.notif-message {
    font-size: 0.75rem;
    color: var(--texte-secondaire, #475569);
    margin-bottom: 4px;
}
.notif-time {
    font-size: 0.7rem;
    color: var(--texte-tertiaire, #64748b);
}
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #64748b;
}
.empty-state svg {
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state p {
    margin-bottom: 16px;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}
.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(37,99,235,0.3);
}
.btn-admin-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 8px;
    transition: all 0.3s;
}
.btn-admin-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(37,99,235,0.3);
}
.progress-bar-container {
    width: 100%;
    height: 6px;
    background: var(--bordure, #e2e8f0);
    border-radius: 999px;
    margin-top: 8px;
    overflow: hidden;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2563eb, #06b6d4);
    border-radius: 999px;
    width: 0%;
}
.progress-percent {
    font-weight: 600;
    color: #2563eb;
    font-size: 0.875rem;
}
@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px;
    }
    .dashboard-main {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .stats-grid {
        gap: 16px;
    }
    .welcome-title {
        font-size: 1.5rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Section Bienvenue -->
    <div class="welcome-section">
        <h1 class="welcome-title">
            Bonjour, <?= htmlspecialchars($utilisateur['prenom']) ?> !
        </h1>
        <p class="welcome-subtitle">
            <?php if ($role === 'etudiant'): ?>
                Continuez votre apprentissage, votre prochain certificat vous attend !
            <?php elseif ($role === 'enseignant'): ?>
                Gérez vos cours et suivez la progression de vos étudiants.
            <?php elseif ($role === 'promoteur'): ?>
                Bienvenue dans l'espace d'administration GOL.
            <?php endif; ?>
        </p>
    </div>

    <!-- Grille de statistiques -->
    <div class="stats-grid">
        <?php if ($role === 'etudiant'): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_modules'] ?? 0 ?></div>
                <div class="stat-label">Modules suivis</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['progression_moyenne'] ?? 0 ?>%</div>
                <div class="stat-label">Progression moyenne</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_certificats'] ?? 0 ?></div>
                <div class="stat-label">Certificats obtenus</div>
            </div>
        <?php elseif ($role === 'enseignant'): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_cours'] ?? 0 ?></div>
                <div class="stat-label">Cours créés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_lecons'] ?? 0 ?></div>
                <div class="stat-label">Leçons</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_etudiants'] ?? 0 ?></div>
                <div class="stat-label">Étudiants inscrits</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['note_moyenne'] ?? 0 ?>%</div>
                <div class="stat-label">Note moyenne</div>
            </div>
        <?php elseif ($role === 'promoteur'): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_etudiants'] ?? 0 ?></div>
                <div class="stat-label">Étudiants</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_enseignants'] ?? 0 ?></div>
                <div class="stat-label">Enseignants</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_modules'] ?? 0 ?></div>
                <div class="stat-label">Modules</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistiques['nb_certificats'] ?? 0 ?></div>
                <div class="stat-label">Certificats délivrés</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenu principal -->
    <?php if ($role === 'etudiant'): ?>
        <div class="dashboard-main">
            <div class="section-card">
                <div class="section-header">
                    <h3>Mes modules en cours</h3>
                    <a href="index.php#modules" class="section-link">Explorer →</a>
                </div>
                <div class="section-content">
                    <?php if (!empty($modules_inscrits)): ?>
                        <div class="item-list">
                            <?php foreach ($modules_inscrits as $module): ?>
                                <div class="list-item">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($module['nom_module']) ?></h4>
                                        <p><?= round($module['progression_globale'] ?? 0) ?>% complété</p>
                                    </div>
                                    <div class="item-progress">
                                        <div class="progress-percent"><?= round($module['progression_globale'] ?? 0) ?>%</div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill" style="width: <?= round($module['progression_globale'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Aucun module inscrit pour le moment.</p>
                            <a href="index.php#modules" class="btn-action">Découvrir les modules</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Notifications</h3>
                    <span class="section-link"><?= count($notifications) ?> non lue(s)</span>
                </div>
                <div class="section-content">
                    <?php if (!empty($notifications)): ?>
                        <div class="notif-list">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notif-item unread">
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($notif['titre']) ?></div>
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time"><?= time_elapsed_string($notif['date_creation']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Aucune notification</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($role === 'enseignant'): ?>
        <div class="dashboard-main">
            <div class="section-card">
                <div class="section-header">
                    <h3>Mes cours</h3>
                    <a href="gestion_cours.php" class="section-link">Gérer →</a>
                </div>
                <div class="section-content">
                    <?php if (!empty($cours_enseignant)): ?>
                        <div class="item-list">
                            <?php foreach ($cours_enseignant as $cours): ?>
                                <div class="list-item">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($cours['titre_cours']) ?></h4>
                                        <p><?= $cours['nb_etudiants'] ?? 0 ?> étudiants • <?= $cours['nb_lecons'] ?? 0 ?> leçons</p>
                                    </div>
                                    <div class="item-progress">
                                        <span class="statut-badge <?= $cours['statut'] ?? 'brouillon' ?>">
                                            <?= ($cours['statut'] ?? 'brouillon') === 'publie' ? 'Publié' : 'Brouillon' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Vous n'avez pas encore créé de cours.</p>
                            <a href="gestion_cours.php" class="btn-action">+ Créer mon premier cours</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Notifications</h3>
                    <span class="section-link"><?= count($notifications) ?> non lue(s)</span>
                </div>
                <div class="section-content">
                    <?php if (!empty($notifications)): ?>
                        <div class="notif-list">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notif-item unread">
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($notif['titre']) ?></div>
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time"><?= time_elapsed_string($notif['date_creation']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Aucune notification</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($role === 'promoteur'): ?>
        <div class="section-card">
            <div class="section-header">
                <h3>Demandes de modification en attente</h3>
                <a href="administration.php?section=demandes" class="section-link">Gérer →</a>
            </div>
            <div class="section-content">
                <?php if (!empty($demandes_modification)): ?>
                    <div class="item-list">
                        <?php foreach ($demandes_modification as $demande): ?>
                            <div class="list-item">
                                <div class="item-info">
                                    <h4><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></h4>
                                    <p><?= $demande['type_demande'] ?> : <?= htmlspecialchars($demande['nouvelle_valeur']) ?></p>
                                    <small><?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?></small>
                                </div>
                                <div class="item-progress">
                                    <span class="statut-badge en_attente">En attente</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Aucune demande de modification en attente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 24px;">
            <a href="administration.php" class="btn-admin-link">
                Accéder au panneau d'administration complet
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>