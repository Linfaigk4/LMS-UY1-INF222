<?php
/**
 * GOL (Gugle Online Learning) - Inscription Enseignant
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Redirection si déjà connecté
if (estConnecte()) {
    header('Location: tableau_bord.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $biographie = trim($_POST['biographie'] ?? '');
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $resultat = inscrireUtilisateur($email, $password, $nom, $prenom, 'enseignant');
        
        if ($resultat['success']) {
            $pdo = connexionBDD();
            
            // Mettre à jour les informations supplémentaires
            $updates = [];
            $params = [];
            
            if (!empty($telephone)) {
                $updates[] = "telephone = ?";
                $params[] = $telephone;
            }
            if (!empty($specialite)) {
                $updates[] = "specialite = ?";
                $params[] = $specialite;
            }
            if (!empty($biographie)) {
                $updates[] = "bio = ?";
                $params[] = $biographie;
            }
            
            if (!empty($updates)) {
                $params[] = $resultat['id'];
                $stmt = $pdo->prepare("UPDATE utilisateurs SET " . implode(", ", $updates) . " WHERE id_utilisateur = ?");
                $stmt->execute($params);
            }
            
            // Ajouter à la table enseignants
            $stmt = $pdo->prepare("INSERT INTO enseignants (id_utilisateur, specialite, biographie, statut) VALUES (?, ?, ?, 'en_attente')");
            $stmt->execute([$resultat['id'], $specialite, $biographie]);
            
            $success = 'Inscription réussie ! Votre compte enseignant sera validé sous 48h. Redirection...';
            header('refresh:3;url=connexion.php');
        } else {
            $error = $resultat['message'];
        }
    }
}

$page_title = 'Inscription Enseignant - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles spécifiques à l'inscription enseignant */
.inscription-page {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-8) var(--spacing-4);
    background: linear-gradient(135deg, var(--fond) 0%, var(--fond-secondaire) 100%);
}

.inscription-card {
    max-width: 600px;
    width: 100%;
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    box-shadow: var(--ombre-xl);
    overflow: hidden;
}

.inscription-header {
    padding: var(--spacing-8) var(--spacing-8) var(--spacing-4);
    text-align: center;
    background: linear-gradient(135deg, var(--accent) 0%, var(--primaire) 100%);
    color: white;
}

.inscription-header h2 {
    color: white;
    margin-bottom: var(--spacing-2);
}

.inscription-header p {
    opacity: 0.9;
    font-size: 0.875rem;
}

.inscription-form {
    padding: var(--spacing-8);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
}

.input-group {
    margin-bottom: var(--spacing-5);
}

.input-label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

.input-label .required {
    color: var(--danger);
    margin-left: var(--spacing-1);
}

.input-label .optional {
    color: var(--texte-tertiaire);
    font-weight: normal;
    font-size: 0.75rem;
}

.input-field,
.input-textarea {
    width: 100%;
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--carte);
    border: 2px solid var(--bordure);
    border-radius: var(--radius-lg);
    color: var(--texte);
    font-size: 1rem;
    transition: all var(--transition-base);
}

.input-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.input-field:focus,
.input-textarea:focus {
    outline: none;
    border-color: var(--primaire);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.info-badge {
    background: var(--fond-secondaire);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    display: flex;
    gap: var(--spacing-3);
    align-items: flex-start;
    border-left: 3px solid var(--primaire);
}

.info-badge svg {
    flex-shrink: 0;
    color: var(--primaire);
}

.info-badge p {
    font-size: 0.75rem;
    color: var(--texte-secondaire);
    margin: 0;
}

.btn-inscription {
    width: 100%;
    padding: var(--spacing-4);
    background: linear-gradient(135deg, var(--accent), var(--primaire));
    border: none;
    border-radius: var(--radius-lg);
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all var(--transition-base);
    margin-top: var(--spacing-4);
}

.btn-inscription:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
}

.login-link {
    text-align: center;
    margin-top: var(--spacing-6);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--bordure);
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

.login-link a {
    color: var(--primaire);
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

.success-message {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid var(--succes);
    border-radius: var(--radius-lg);
    padding: var(--spacing-3) var(--spacing-4);
    margin-bottom: var(--spacing-6);
    color: var(--succes);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.error-message {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid var(--danger);
    border-radius: var(--radius-lg);
    padding: var(--spacing-3) var(--spacing-4);
    margin-bottom: var(--spacing-6);
    color: var(--danger);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.password-strength {
    margin-top: var(--spacing-2);
    height: 4px;
    background: var(--bordure);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.strength-bar {
    width: 0%;
    height: 100%;
    transition: width 0.3s ease, background 0.3s ease;
}

.strength-text {
    font-size: 0.7rem;
    margin-top: var(--spacing-1);
    color: var(--texte-tertiaire);
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .inscription-header {
        padding: var(--spacing-6) var(--spacing-6) var(--spacing-3);
    }
    
    .inscription-form {
        padding: var(--spacing-6);
    }
}
</style>

<div class="inscription-page">
    <div class="inscription-card animate-scaleIn">
        <div class="inscription-header">
            <h2>🎓 Devenir enseignant sur GOL</h2>
            <p>Partagez votre expertise et formez la prochaine génération</p>
        </div>

        <div class="inscription-form">
            <?php if ($success): ?>
                <div class="success-message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="info-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>Les comptes enseignants sont soumis à validation par notre équipe. Vous serez notifié par email une fois votre compte activé.</p>
            </div>

            <form method="POST" action="" id="inscriptionForm">
                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="input-field" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="input-field" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">Téléphone <span class="optional">(optionnel)</span></label>
                        <input type="tel" name="telephone" class="input-field" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Spécialité <span class="optional">(optionnel)</span></label>
                        <input type="text" name="specialite" class="input-field" value="<?= htmlspecialchars($_POST['specialite'] ?? '') ?>" placeholder="ex: Développement Web, Data Science...">
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Biographie <span class="optional">(optionnel)</span></label>
                    <textarea name="biographie" class="input-textarea" placeholder="Présentez-vous et partagez votre expérience..."><?= htmlspecialchars($_POST['biographie'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">Mot de passe <span class="required">*</span></label>
                        <input type="password" name="password" id="password" class="input-field" required>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Confirmer <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="input-field" required>
                    </div>
                </div>

                <button type="submit" class="btn-inscription">
                    Devenir enseignant GOL
                </button>

                <div class="login-link">
                    Vous avez déjà un compte ? <a href="connexion.php">Se connecter</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Force du mot de passe
const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

passwordInput?.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/\d/)) strength++;
    if (password.match(/[^a-zA-Z\d]/)) strength++;
    
    const percentage = (strength / 4) * 100;
    strengthBar.style.width = percentage + '%';
    
    let color, text;
    if (percentage < 25) {
        color = '#ef4444';
        text = 'Très faible';
    } else if (percentage < 50) {
        color = '#f59e0b';
        text = 'Faible';
    } else if (percentage < 75) {
        color = '#eab308';
        text = 'Moyen';
    } else {
        color = '#22c55e';
        text = 'Fort';
    }
    
    strengthBar.style.background = color;
    strengthText.textContent = text;
    strengthText.style.color = color;
});
</script>

<?php include 'includes/footer.php'; ?>