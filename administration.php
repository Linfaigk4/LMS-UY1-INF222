<?php
/**
 * GOL (Gugle Online Learning) - Administration
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est admin
if (!estConnecte() || (!estSuperAdmin() && !estPromoteur())) {
    header('Location: connexion.php');
    exit;
}

$section = $_GET['section'] ?? 'dashboard';
$utilisateur = obtenirUtilisateur();
$statistiques = obtenirStatistiquesPromoteur();

// Gestion des actions
$message = '';
$error = '';

// Ajouter un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifierTokenCSRF();
    if ($_POST['action'] === 'add_module' && estSuperAdmin()) {
        $resultat = ajouterModule(
            $_POST['nom_module'],
            $_POST['description'],
            $_POST['objectifs'],
            $_SESSION['id_utilisateur'],
            $_POST['niveau']
        );
        if ($resultat) {
            $message = 'Module ajouté avec succès !';
        } else {
            $error = 'Erreur lors de l\'ajout du module.';
        }
    }
    
    if ($_POST['action'] === 'add_user' && estSuperAdmin()) {
        $resultat = inscrireUtilisateur(
            $_POST['email'],
            $_POST['password'],
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['role']
        );
        if ($resultat['success']) {
            $message = 'Utilisateur créé avec succès !';
        } else {
            $error = $resultat['message'];
        }
    }
}

// Récupération des données
$modules = obtenirModules(false);
$demandes_certificats = estSuperAdmin() ? obtenirDemandesCertificatsEnAttente() : [];
$utilisateurs = obtenirTousUtilisateurs();
$demandes = obtenirDemandesEnAttente();

$page_title = 'Administration - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles de l'administration */
.admin-container {
    display: flex;
    min-height: calc(100vh - 200px);
}

/* Sidebar admin */
.admin-sidebar {
    width: 280px;
    background: var(--carte);
    border-right: 1px solid var(--bordure);
    padding: var(--spacing-6) 0;
    position: sticky;
    top: 0;
    height: calc(100vh - 80px);
    overflow-y: auto;
}

.admin-sidebar-header {
    padding: 0 var(--spacing-6) var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    margin-bottom: var(--spacing-6);
}

.admin-avatar {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-4);
}

.admin-avatar span {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
}

.admin-name {
    font-weight: 700;
    margin-bottom: var(--spacing-1);
}

.admin-role {
    font-size: 0.75rem;
    color: var(--primaire);
}

.admin-nav {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.admin-nav-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-6);
    color: var(--texte-secondaire);
    text-decoration: none;
    transition: all var(--transition-base);
    border-left: 3px solid transparent;
}

.admin-nav-item:hover {
    background: var(--fond-secondaire);
    color: var(--texte);
}

.admin-nav-item.active {
    background: var(--fond-secondaire);
    color: var(--primaire);
    border-left-color: var(--primaire);
}

.admin-nav-item svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Contenu principal */
.admin-content {
    flex: 1;
    padding: var(--spacing-8);
    overflow-x: auto;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-8);
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.admin-title {
    font-size: 1.75rem;
    font-weight: 700;
}

.admin-actions {
    display: flex;
    gap: var(--spacing-3);
}

.btn-admin {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-4);
    background: var(--primaire);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}

.btn-admin-outline {
    background: transparent;
    border: 1px solid var(--bordure);
    color: var(--texte);
}

.btn-admin-outline:hover {
    background: var(--fond-secondaire);
}

/* Cartes de statistiques */
.stats-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.stat-admin-card {
    background: var(--carte);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    border: 1px solid var(--bordure);
    transition: all var(--transition-base);
}

.stat-admin-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ombre-lg);
}

.stat-admin-icon {
    width: 48px;
    height: 48px;
    background: var(--fond-secondaire);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-4);
}

.stat-admin-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primaire);
    margin-bottom: var(--spacing-1);
}

.stat-admin-label {
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

/* Tableaux */
.admin-table {
    width: 100%;
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
}

.admin-table thead {
    background: var(--fond-secondaire);
}

.admin-table th {
    text-align: left;
    padding: var(--spacing-4);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--texte-secondaire);
    border-bottom: 1px solid var(--bordure);
}

.admin-table td {
    padding: var(--spacing-4);
    border-bottom: 1px solid var(--bordure);
    font-size: 0.875rem;
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.admin-table tr:hover {
    background: var(--fond-secondaire);
}

/* Badges de statut */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-1);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.active {
    background: rgba(34, 197, 94, 0.1);
    color: var(--succes);
}

.status-badge.suspendu {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.status-badge.brouillon {
    background: rgba(245, 158, 11, 0.1);
    color: var(--avertissement);
}

.status-badge.publie {
    background: rgba(34, 197, 94, 0.1);
    color: var(--succes);
}

/* Actions buttons */
.action-buttons {
    display: flex;
    gap: var(--spacing-2);
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: var(--spacing-1);
    border-radius: var(--radius-md);
    transition: all var(--transition-base);
}

.btn-icon:hover {
    background: var(--fond-secondaire);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
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
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.25rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--texte-secondaire);
}

.modal-body {
    padding: var(--spacing-6);
}

.modal-footer {
    padding: var(--spacing-6);
    border-top: 1px solid var(--bordure);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-3);
}

/* Formulaires */
.form-group {
    margin-bottom: var(--spacing-4);
}

.form-label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 500;
    font-size: 0.875rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: var(--spacing-3);
    background: var(--carte);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-lg);
    color: var(--texte);
    font-size: 0.875rem;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primaire);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

/* Messages */
.alert {
    padding: var(--spacing-3) var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid var(--succes);
    color: var(--succes);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid var(--danger);
    color: var(--danger);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
        position: relative;
        height: auto;
    }
    
    .admin-content {
        padding: var(--spacing-4);
    }
    
    .admin-table {
        font-size: 0.75rem;
    }
    
    .admin-table th,
    .admin-table td {
        padding: var(--spacing-2);
    }
}
</style>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-avatar">
                <span><?= strtoupper(substr($utilisateur['prenom'], 0, 1) . substr($utilisateur['nom'], 0, 1)) ?></span>
            </div>
            <div class="admin-name"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></div>
            <div class="admin-role">
                <?php if (estSuperAdmin()): ?>
                    Super Administrateur
                <?php else: ?>
                    Promoteur
                <?php endif; ?>
            </div>
        </div>
        
        <nav class="admin-nav">
            <a href="?section=dashboard" class="admin-nav-item <?= $section === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Tableau de bord
            </a>
            <a href="?section=modules" class="admin-nav-item <?= $section === 'modules' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                Modules
            </a>
            <a href="?section=utilisateurs" class="admin-nav-item <?= $section === 'utilisateurs' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Utilisateurs
            </a>
            <a href="?section=demandes" class="admin-nav-item <?= $section === 'demandes' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Demandes
                <?php if (count($demandes) > 0): ?>
                    <span class="notification-badge"><?= count($demandes) ?></span>
                <?php endif; ?>
            </a>
            <?php if (estSuperAdmin()): ?>
                <a href="?section=certificats" class="admin-nav-item <?= $section === 'certificats' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    Certificats
                </a>
                <a href="?section=statistiques" class="admin-nav-item <?= $section === 'statistiques' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 20V10M12 20V4M6 20v-6"/>
                    </svg>
                    Statistiques
                </a>
                <a href="?section=config" class="admin-nav-item <?= $section === 'config' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Configuration
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="admin-content">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Section Dashboard -->
        <?php if ($section === 'dashboard'): ?>
            <div class="admin-header">
                <h1 class="admin-title">Tableau de bord</h1>
                <div class="admin-actions">
                    <button class="btn-admin" onclick="openModal('addModuleModal')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nouveau module
                    </button>
                    <?php if (estSuperAdmin()): ?>
                        <button class="btn-admin btn-admin-outline" onclick="openModal('addUserModal')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                                <line x1="17" y1="3" x2="21" y2="7"/>
                                <line x1="21" y1="3" x2="17" y2="7"/>
                            </svg>
                            Ajouter un utilisateur
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-admin-grid">
                <div class="stat-admin-card">
                    <div class="stat-admin-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="stat-admin-value"><?= $statistiques['nb_etudiants'] ?? 0 ?></div>
                    <div class="stat-admin-label">Étudiants</div>
                </div>
                <div class="stat-admin-card">
                    <div class="stat-admin-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div class="stat-admin-value"><?= $statistiques['nb_enseignants'] ?? 0 ?></div>
                    <div class="stat-admin-label">Enseignants</div>
                </div>
                <div class="stat-admin-card">
                    <div class="stat-admin-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                    <div class="stat-admin-value"><?= $statistiques['nb_modules'] ?? 0 ?></div>
                    <div class="stat-admin-label">Modules</div>
                </div>
                <div class="stat-admin-card">
                    <div class="stat-admin-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="stat-admin-value"><?= $statistiques['nb_certificats'] ?? 0 ?></div>
                    <div class="stat-admin-label">Certificats</div>
                </div>
            </div>

            <!-- Modules récents -->
            <div class="admin-header" style="margin-top: var(--spacing-8);">
                <h2 class="admin-title" style="font-size: 1.25rem;">Modules récents</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>Nom</th><th>Niveau</th><th>Date</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($modules, 0, 5) as $module): ?>
                        <tr>
                            <td><?= htmlspecialchars($module['nom_module']) ?></td>
                            <td><?= $module['niveau'] ?></td>
                            <td><?= date('d/m/Y', strtotime($module['date_creation'])) ?></td>
                            <td><span class="status-badge <?= $module['actif'] ? 'active' : 'suspendu' ?>"><?= $module['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                            <td class="action-buttons">
                                <button class="btn-icon" onclick="editModule(<?= $module['id_module'] ?>)" title="Modifier">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                        <path d="M4 20h16"/>
                                    </svg>
                                </button>
                                <button class="btn-icon" onclick="deleteModule(<?= $module['id_module'] ?>)" title="Supprimer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Section Modules -->
        <?php elseif ($section === 'modules'): ?>
            <div class="admin-header">
                <h1 class="admin-title">Gestion des modules</h1>
                <button class="btn-admin" onclick="openModal('addModuleModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nouveau module
                </button>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>ID</th><th>Nom</th><th>Description</th><th>Niveau</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td><?= $module['id_module'] ?></td>
                            <td><?= htmlspecialchars($module['nom_module']) ?></td>
                            <td><?= htmlspecialchars(substr($module['description'] ?? '', 0, 50)) ?>...</td>
                            <td><?= $module['niveau'] ?></td>
                            <td><span class="status-badge <?= $module['actif'] ? 'active' : 'suspendu' ?>"><?= $module['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                            <td class="action-buttons">
                                <button class="btn-icon" onclick="editModule(<?= $module['id_module'] ?>)" title="Modifier">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                        <path d="M4 20h16"/>
                                    </svg>
                                </button>
                                <button class="btn-icon" onclick="deleteModule(<?= $module['id_module'] ?>)" title="Supprimer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Section Utilisateurs -->
        <?php elseif ($section === 'utilisateurs'): ?>
            <div class="admin-header">
                <h1 class="admin-title">Gestion des utilisateurs</h1>
                <?php if (estSuperAdmin()): ?>
                    <button class="btn-admin" onclick="openModal('addUserModal')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Ajouter
                    </button>
                <?php endif; ?>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td><?= $user['id_utilisateur'] ?></td>
                            <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['role'] ?></td>
                            <td><span class="status-badge <?= $user['statut'] === 'actif' ? 'active' : 'suspendu' ?>"><?= $user['statut'] ?></span></td>
                            <td class="action-buttons">
                                <button class="btn-icon" onclick="editUser(<?= $user['id_utilisateur'] ?>)" title="Modifier">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                        <path d="M4 20h16"/>
                                    </svg>
                                </button>
                                <button class="btn-icon" onclick="toggleUserStatus(<?= $user['id_utilisateur'] ?>)" title="Changer statut">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Section Demandes -->
        <?php elseif ($section === 'demandes'): ?>
            <div class="admin-header">
                <h1 class="admin-title">Demandes de modification</h1>
            </div>
            <?php if (empty($demandes)): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Aucune demande en attente.
                </div>
            <?php else: ?>
                <?php foreach ($demandes as $demande): ?>
                    <div class="stat-admin-card" style="margin-bottom: var(--spacing-4);">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></strong><br>
                                <small><?= htmlspecialchars($demande['email']) ?></small>
                            </div>
                            <span class="status-badge active">En attente</span>
                        </div>
                        <div style="margin-top: var(--spacing-3);">
                            <strong>Type :</strong> <?= $demande['type_demande'] ?><br>
                            <strong>Nouvelle valeur :</strong> <?= htmlspecialchars($demande['nouvelle_valeur']) ?>
                        </div>
                        <div class="action-buttons" style="margin-top: var(--spacing-4);">
                            <button class="btn-admin" onclick="approuverDemande(<?= $demande['id_demande'] ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                                Approuver
                            </button>
                            <button class="btn-admin btn-admin-outline" onclick="refuserDemande(<?= $demande['id_demande'] ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                Refuser
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php elseif ($section === 'certificats' && estSuperAdmin()): ?>
            <div class="admin-header">
                <h1 class="admin-title">Demandes de certificats exceptionnels</h1>
            </div>
            <?php if (empty($demandes_certificats)): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Aucune demande de certificat en attente.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Module</th>
                            <th>Date</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes_certificats as $dc): ?>
                        <tr>
                            <td><?= htmlspecialchars($dc['prenom'] . ' ' . $dc['nom']) ?><br><small><?= htmlspecialchars($dc['email']) ?></small></td>
                            <td><?= htmlspecialchars($dc['nom_module']) ?></td>
                            <td><?= date('d/m/Y', strtotime($dc['date_demande'])) ?></td>
                            <td style="max-width:200px;font-size:0.8rem"><?= htmlspecialchars(substr($dc['motif'], 0, 100)) ?>...</td>
                            <td><span class="status-badge active">En attente</span></td>
                            <td class="action-buttons">
                                <button class="btn-admin" onclick="approuverDemandeCertificat(<?= $dc['id_demande'] ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                    Approuver
                                </button>
                                <button class="btn-admin btn-admin-outline" onclick="refuserDemandeCertificat(<?= $dc['id_demande'] ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Refuser
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<!-- Modal Ajout Module -->
<div id="addModuleModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="">
            <div class="modal-header">
                <h3>Ajouter un module</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModuleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                <input type="hidden" name="action" value="add_module">
                <div class="form-group">
                    <label class="form-label">Nom du module *</label>
                    <input type="text" name="nom_module" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Objectifs</label>
                    <textarea name="objectifs" class="form-textarea"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Niveau</label>
                    <select name="niveau" class="form-select">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('addModuleModal')">Annuler</button>
                <button type="submit" class="btn-admin">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajout Utilisateur -->
<?php if (estSuperAdmin()): ?>
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="">
            <div class="modal-header">
                <h3>Ajouter un utilisateur</h3>
                <button type="button" class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                <input type="hidden" name="action" value="add_user">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-4);">
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rôle *</label>
                    <select name="role" class="form-select">
                        <option value="etudiant">Étudiant</option>
                        <option value="enseignant">Enseignant</option>
                        <option value="promoteur">Promoteur</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('addUserModal')">Annuler</button>
                <button type="submit" class="btn-admin">Créer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// openModal et closeModal sont définis dans assets/js/app.js

function editModule(id) {
    afficherNotification('Fonctionnalité à venir', 'info');
}

function deleteModule(id) {
    if (confirm('Voulez-vous vraiment supprimer ce module ?')) {
        envoyerRequeteAjax('supprimer_module', 'POST', { id_module: id })
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    afficherNotification(result.message, 'danger');
                }
            });
    }
}

function editUser(id) {
    afficherNotification('Fonctionnalité à venir', 'info');
}

function toggleUserStatus(id) {
    if (confirm('Changer le statut de cet utilisateur ?')) {
        envoyerRequeteAjax('toggle_user_status', 'POST', { id_utilisateur: id })
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    afficherNotification(result.message, 'danger');
                }
            });
    }
}

function approuverDemande(id) {
    if (confirm('Confirmez-vous l\'approbation de cette demande ?')) {
        envoyerRequeteAjax('approuver_demande', 'POST', { id_demande: id })
            .then(result => {
                if (result.success) {
                    afficherNotification('Demande approuvée', 'succes');
                    location.reload();
                } else {
                    afficherNotification(result.message, 'danger');
                }
            });
    }
}

function refuserDemande(id) {
    const commentaire = prompt('Motif du refus :');
    if (commentaire) {
        envoyerRequeteAjax('refuser_demande', 'POST', { id_demande: id, commentaire: commentaire })
            .then(result => {
                if (result.success) {
                    afficherNotification('Demande refusée', 'info');
                    location.reload();
                } else {
                    afficherNotification(result.message, 'danger');
                }
            });
    }
}

function approuverDemandeCertificat(id) {
    if (!confirm('Approuver cette demande et générer un certificat exceptionnel ?')) return;
    envoyerRequeteAjax('approuver_demande_certificat', 'POST', { id_demande: id })
        .then(result => {
            if (result.success) {
                afficherNotification('Certificat exceptionnel accordé', 'succes');
                location.reload();
            } else {
                afficherNotification(result.message || 'Erreur', 'danger');
            }
        });
}

function refuserDemandeCertificat(id) {
    const commentaire = prompt('Motif du refus (obligatoire) :');
    if (!commentaire) return;
    envoyerRequeteAjax('refuser_demande_certificat', 'POST', { id_demande: id, commentaire: commentaire })
        .then(result => {
            if (result.success) {
                afficherNotification('Demande refusée', 'info');
                location.reload();
            } else {
                afficherNotification(result.message || 'Erreur', 'danger');
            }
        });
}

// Fermer modal en cliquant en dehors
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>