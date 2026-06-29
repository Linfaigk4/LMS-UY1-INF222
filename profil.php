<?php
/**
 * GOL (Gugle Online Learning) - Profil utilisateur
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
$message = '';
$error = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pdo = connexionBDD();
        
        if ($_POST['action'] === 'update_profile') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            
            if (empty($nom) || empty($prenom)) {
                $error = 'Le nom et le prénom sont obligatoires.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET nom = ?, prenom = ?, telephone = ?, bio = ?
                    WHERE id_utilisateur = ?
                ");
                if ($stmt->execute([$nom, $prenom, $telephone, $bio, $_SESSION['id_utilisateur']])) {
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $message = 'Profil mis à jour avec succès !';
                    $utilisateur = obtenirUtilisateur();
                } else {
                    $error = 'Erreur lors de la mise à jour.';
                }
            }
        } elseif ($_POST['action'] === 'request_change') {
            $type_demande = $_POST['type_demande'] ?? '';
            $nouvelle_valeur = trim($_POST['nouvelle_valeur'] ?? '');
            $justification = trim($_POST['justification'] ?? '');
            
            if (empty($type_demande) || empty($nouvelle_valeur)) {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $resultat = creerDemandeModification($_SESSION['id_utilisateur'], $type_demande, $nouvelle_valeur, $justification);
                if ($resultat) {
                    $message = 'Votre demande a été envoyée à l\'administrateur.';
                } else {
                    $error = 'Erreur lors de l\'envoi de la demande.';
                }
            }
        }
    }
}

// Récupérer les données spécifiques selon le rôle
$statistiques = [];
$modules_inscrits = [];
$demandes = [];

// Appel des fonctions avec vérification d'existence
if ($role === 'etudiant') {
    if (function_exists('obtenirStatistiquesEtudiant')) {
        $statistiques = obtenirStatistiquesEtudiant($_SESSION['id_utilisateur']);
    }
    if (function_exists('obtenirModulesInscrits')) {
        $modules_inscrits = obtenirModulesInscrits($_SESSION['id_utilisateur']);
    }
} elseif ($role === 'enseignant') {
    if (function_exists('obtenirStatistiquesEnseignant')) {
        $statistiques = obtenirStatistiquesEnseignant($_SESSION['id_utilisateur']);
    }
}

if (function_exists('obtenirDemandesUtilisateur')) {
    $demandes = obtenirDemandesUtilisateur($_SESSION['id_utilisateur']);
}

$page_title = 'Mon profil - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px;
}

.profile-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 32px;
}

/* Carte de profil */
.profile-card {
    background: var(--carte, white);
    border-radius: 24px;
    border: 1px solid var(--bordure, #e2e8f0);
    overflow: hidden;
    position: sticky;
    top: 100px;
}

.profile-cover {
    height: 100px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
}

.profile-avatar-section {
    text-align: center;
    margin-top: -50px;
    padding: 0 24px 24px;
}

.profile-avatar-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    border-radius: 24px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--carte, white);
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
}

.profile-avatar-large span {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
}

.profile-name-large {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.profile-role {
    display: inline-block;
    padding: 4px 12px;
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 999px;
    font-size: 0.75rem;
    color: #2563eb;
    margin-bottom: 16px;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    padding: 16px 0;
    border-top: 1px solid var(--bordure, #e2e8f0);
    border-bottom: 1px solid var(--bordure, #e2e8f0);
    margin-bottom: 16px;
}

.profile-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2563eb;
}

.profile-stat-label {
    font-size: 0.7rem;
    color: var(--texte-tertiaire, #64748b);
}

.profile-info {
    padding: 0 24px 24px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    font-size: 0.875rem;
}

.info-label {
    width: 80px;
    color: var(--texte-tertiaire, #64748b);
}

.info-value {
    flex: 1;
    color: var(--texte, #0f172a);
    word-break: break-all;
}

/* Sections principales */
.profile-section {
    background: var(--carte, white);
    border-radius: 24px;
    border: 1px solid var(--bordure, #e2e8f0);
    margin-bottom: 32px;
    overflow: hidden;
}

.section-title {
    padding: 24px;
    border-bottom: 1px solid var(--bordure, #e2e8f0);
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-content {
    padding: 24px;
}

/* Modules suivis */
.module-item {
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--bordure, #e2e8f0);
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}

.module-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #2563eb;
}

.module-progress {
    background: var(--carte, white);
    padding: 4px 16px;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #2563eb;
}

.cours-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.cours-progress-item {
    background: var(--carte, white);
    border-radius: 16px;
    padding: 16px;
}

.cours-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.cours-name {
    font-weight: 600;
    font-size: 0.875rem;
}

.cours-percent {
    font-size: 0.75rem;
    color: #2563eb;
    font-weight: 600;
}

.progress-bar-container {
    width: 100%;
    height: 6px;
    background: var(--bordure, #e2e8f0);
    border-radius: 999px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2563eb, #06b6d4);
    border-radius: 999px;
    width: 0%;
    transition: width 0.5s ease;
}

.cours-status {
    font-size: 0.7rem;
    margin-top: 8px;
    color: var(--texte-tertiaire, #64748b);
}

.status-termine {
    color: #22c55e;
}

.status-en_cours {
    color: #f59e0b;
}

/* Formulaires */
.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--texte-secondaire, #475569);
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 12px;
    background: var(--carte, white);
    border: 2px solid var(--bordure, #e2e8f0);
    border-radius: 12px;
    color: var(--texte, #0f172a);
    font-size: 0.875rem;
    transition: all 0.3s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(37,99,235,0.3);
}

.btn-outline {
    background: transparent;
    border: 2px solid #2563eb;
    color: #2563eb;
}

.btn-outline:hover {
    background: #2563eb;
    color: white;
}

/* Demandes */
.demande-item {
    padding: 16px;
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 16px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.demande-status {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-en_attente {
    background: rgba(245,158,11,0.1);
    color: #f59e0b;
}

.status-approuvee {
    background: rgba(34,197,94,0.1);
    color: #22c55e;
}

.status-refusee {
    background: rgba(239,68,68,0.1);
    color: #ef4444;
}

/* Messages */
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 24px;
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

.empty-state {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .profile-card {
        position: relative;
        top: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .profile-container {
        padding: 16px;
    }
    
    .module-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="profile-container">
    <div class="profile-grid">
        <!-- Colonne gauche - Carte de profil -->
        <aside class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-avatar-section">
                <div class="profile-avatar-large">
                    <span><?= strtoupper(substr($utilisateur['prenom'], 0, 1) . substr($utilisateur['nom'], 0, 1)) ?></span>
                </div>
                <h2 class="profile-name-large"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h2>
                <span class="profile-role">
                    <?php if (estSuperAdmin()): ?>
                        Super Administrateur
                    <?php elseif (estPromoteur()): ?>
                        Promoteur
                    <?php elseif (estEnseignant()): ?>
                        Enseignant
                    <?php else: ?>
                        Étudiant
                    <?php endif; ?>
                </span>
                
                <div class="profile-stats">
                    <div class="profile-stat-item">
                        <div class="profile-stat-value"><?= $statistiques['nb_modules'] ?? 0 ?></div>
                        <div class="profile-stat-label">Modules</div>
                    </div>
                    <div class="profile-stat-item">
                        <div class="profile-stat-value"><?= $statistiques['progression_moyenne'] ?? 0 ?>%</div>
                        <div class="profile-stat-label">Progression</div>
                    </div>
                    <div class="profile-stat-item">
                        <div class="profile-stat-value"><?= $statistiques['nb_certificats'] ?? 0 ?></div>
                        <div class="profile-stat-label">Certificats</div>
                    </div>
                </div>
                
                <div class="profile-info">
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($utilisateur['email']) ?></div>
                    </div>
                    <?php if ($utilisateur['telephone']): ?>
                    <div class="info-row">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?= htmlspecialchars($utilisateur['telephone']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Membre depuis</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($utilisateur['date_inscription'])) ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Colonne droite - Contenu -->
        <div class="profile-content">
            
            <!-- Section Modules suivis (pour étudiants) -->
            <?php if ($role === 'etudiant' && !empty($modules_inscrits)): ?>
            <div class="profile-section">
                <div class="section-title">
                    <?= icone('cours', 18) ?> Mes modules et cours suivis
                </div>
                <div class="section-content">
                    <?php foreach ($modules_inscrits as $module): ?>
                        <div class="module-item">
                            <div class="module-header">
                                <h3 class="module-title"><?= htmlspecialchars($module['nom_module']) ?></h3>
                                <span class="module-progress"><?= round($module['progression_globale'] ?? 0) ?>%</span>
                            </div>
                            <div class="cours-list">
                                <?php 
                                // Récupérer les cours du module
                                $stmt = $pdo->prepare("
                                    SELECT c.id_cours, c.titre_cours, pc.pourcentage, pc.statut
                                    FROM cours c
                                    LEFT JOIN progression_cours pc ON c.id_cours = pc.id_cours AND pc.id_utilisateur = ?
                                    WHERE c.id_module = ? AND c.statut = 'publie'
                                ");
                                $stmt->execute([$_SESSION['id_utilisateur'], $module['id_module']]);
                                $cours_module = $stmt->fetchAll();
                                ?>
                                
                                <?php if (!empty($cours_module)): ?>
                                    <?php foreach ($cours_module as $cours): ?>
                                        <div class="cours-progress-item">
                                            <div class="cours-info">
                                                <span class="cours-name"><?= htmlspecialchars($cours['titre_cours']) ?></span>
                                                <span class="cours-percent"><?= round($cours['pourcentage'] ?? 0) ?>%</span>
                                            </div>
                                            <div class="progress-bar-container">
                                                <div class="progress-bar-fill" style="width: <?= round($cours['pourcentage'] ?? 0) ?>%"></div>
                                            </div>
                                            <div class="cours-status <?= ($cours['statut'] ?? 'non_commence') === 'termine' ? 'status-termine' : 'status-en_cours' ?>">
                                                <?php if (($cours['statut'] ?? 'non_commence') === 'termine'): ?>
                                                    <?= icone('succes', 16) ?> Terminé
                                                <?php elseif (($cours['pourcentage'] ?? 0) > 0): ?>
                                                    <?= icone('cours', 18) ?> En cours
                                                <?php else: ?>
                                                    <?= icone('lecon', 18) ?> Non commencé
                                                <?php endif; ?>
                                                <a href="cours.php?id=<?= $cours['id_cours'] ?>" style="float: right; color: #2563eb; text-decoration: none;">Continuer →</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-state">Aucun cours disponible dans ce module pour le moment.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Modification du profil -->
            <div class="profile-section">
                <div class="section-title">
                    <?= icone('modifier', 14) ?> Modifier mon profil
                </div>
                <div class="section-content">
                    <?php if ($message): ?>
                        <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= icone('alerte', 16) ?> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" class="form-input" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="prenom" class="form-input" value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-input" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Biographie</label>
                            <textarea name="bio" class="form-textarea" placeholder="Parlez-nous de vous..."><?= htmlspecialchars($utilisateur['bio'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                    </form>
                </div>
            </div>

            <!-- Section Demande de modification -->
            <div class="profile-section">
                <div class="section-title">
                    🔐 Demander une modification
                </div>
                <div class="section-content">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="request_change">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Type de modification *</label>
                                <select name="type_demande" class="form-select" required>
                                    <option value="">Sélectionnez...</option>
                                    <option value="mot_de_passe">Changer mon mot de passe</option>
                                    <option value="email">Changer mon email</option>
                                    <option value="telephone">Changer mon téléphone</option>
                                    <option value="nom_complet">Changer mon nom complet</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nouvelle valeur *</label>
                                <input type="text" name="nouvelle_valeur" class="form-input" placeholder="Nouvelle valeur..." required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Justification</label>
                            <textarea name="justification" class="form-textarea" placeholder="Pourquoi cette modification ? (optionnel)"></textarea>
                        </div>
                        <button type="submit" class="btn-submit btn-outline">Envoyer la demande</button>
                    </form>
                </div>
            </div>

            <!-- Section Historique des demandes -->
            <?php if (!empty($demandes)): ?>
            <div class="profile-section">
                <div class="section-title">
                    <?= icone('cours', 18) ?> Historique des demandes
                </div>
                <div class="section-content">
                    <?php foreach ($demandes as $demande): ?>
                        <div class="demande-item">
                            <div>
                                <strong><?= $demande['type_demande'] ?></strong><br>
                                <small>Nouvelle valeur : <?= htmlspecialchars($demande['nouvelle_valeur']) ?></small><br>
                                <small><?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?></small>
                            </div>
                            <div class="demande-status status-<?= $demande['statut'] ?>">
                                <?php if ($demande['statut'] === 'en_attente'): ?>
                                    En attente
                                <?php elseif ($demande['statut'] === 'approuvee'): ?>
                                    Approuvée ✓
                                <?php else: ?>
                                    Refusée ✗
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>