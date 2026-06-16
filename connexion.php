<?php
/**
 * GOL (Gugle Online Learning) - Connexion style DAY 70/LUNORK
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Redirection si déjà connecté
if (estConnecte()) {
    header('Location: tableau_bord.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $resultat = connecterUtilisateur($email, $password);
        
        if ($resultat['success']) {
            header('Location: tableau_bord.php');
            exit;
        } else {
            $error = $resultat['message'];
        }
    }
}

$page_title = 'Connexion - GOL';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           STYLE CONNEXION - INSPIRATION DAY 70 / LUNORK
           ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: radial-gradient(circle at 20% 30%, #1a1a2e 0%, #0f0f1a 100%);
            overflow: hidden;
        }

        /* Effet vitrail animé */
        .stained-glass {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
        }

        .glass-pane {
            position: absolute;
            background: linear-gradient(45deg, 
                rgba(37, 99, 235, 0.3), 
                rgba(6, 182, 212, 0.2),
                rgba(139, 92, 246, 0.3),
                rgba(236, 72, 153, 0.2)
            );
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            filter: blur(60px);
            animation: floatGlass 20s infinite ease-in-out;
        }

        .glass-pane:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .glass-pane:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -150px;
            right: -150px;
            animation-delay: 5s;
            background: linear-gradient(45deg, 
                rgba(236, 72, 153, 0.3), 
                rgba(245, 158, 11, 0.2),
                rgba(37, 99, 235, 0.3)
            );
        }

        .glass-pane:nth-child(3) {
            width: 250px;
            height: 250px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
            background: linear-gradient(45deg, 
                rgba(6, 182, 212, 0.2), 
                rgba(139, 92, 246, 0.3),
                rgba(37, 99, 235, 0.2)
            );
        }

        .glass-pane:nth-child(4) {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 20%;
            animation-delay: 15s;
            background: linear-gradient(45deg, 
                rgba(245, 158, 11, 0.3), 
                rgba(236, 72, 153, 0.2)
            );
        }

        @keyframes floatGlass {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            }
            33% {
                transform: translate(30px, -30px) rotate(120deg);
                border-radius: 70% 30% 30% 70% / 60% 40% 60% 40%;
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
                border-radius: 40% 60% 60% 40% / 50% 45% 55% 50%;
            }
        }

        /* Carte de connexion premium */
        .login-card-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            margin: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .card-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #06b6d4, #8b5cf6, #2563eb);
            background-size: 200% 100%;
            animation: glowMove 3s linear infinite;
        }

        @keyframes glowMove {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }

        /* En-tête style DAY 70 */
        .login-header {
            padding: 40px 40px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .day-badge {
            display: inline-block;
            font-family: monospace;
            font-size: 0.75rem;
            letter-spacing: 2px;
            padding: 5px 12px;
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(37, 99, 235, 0.5);
            border-radius: 9999px;
            color: #3b82f6;
            margin-bottom: 20px;
        }

        .login-header h2 {
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }

        /* Formulaire */
        .login-form {
            padding: 40px;
        }

        .input-group {
            margin-bottom: 30px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .input-icon input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-icon input:focus {
            outline: none;
            border-color: #2563eb;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
        }

        .input-icon input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Options */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .forgot-link:hover {
            color: #06b6d4;
        }

        /* Bouton de connexion - Style Neon */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(37, 99, 235, 0.6);
        }

        /* Séparateur */
        .login-divider {
            position: relative;
            text-align: center;
            margin: 30px 0;
        }

        .login-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .login-divider span {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Lien d'inscription */
        .register-link {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }

        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .register-link a:hover {
            color: #06b6d4;
        }

        /* Notification d'erreur */
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 30px;
            color: #f87171;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-header {
                padding: 30px 30px 15px;
            }
            
            .login-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="stained-glass">
        <div class="glass-pane"></div>
        <div class="glass-pane"></div>
        <div class="glass-pane"></div>
        <div class="glass-pane"></div>
    </div>

    <div class="login-card-container">
        <div class="login-card">
            <div class="card-glow"></div>
            
            <div class="login-header">
                <span class="day-badge">DAY 70 // LUNORK</span>
                <h2>GOL</h2>
                <p class="login-subtitle">Gugle Online Learning</p>
            </div>

            <form method="POST" action="" class="login-form">
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

                <div class="input-group">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" name="email" placeholder="email@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" name="password" placeholder="Mot de passe" required>
                    </div>
                </div>

                <div class="login-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember"> Se souvenir de moi
                    </label>
                    <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-login">
                    SE CONNECTER
                </button>

                <div class="login-divider">
                    <span>OU</span>
                </div>

                <div class="register-link">
                    Nouveau sur GOL ? <a href="choix_inscription.php">Créer un compte</a>
                </div>

                <!-- Dans le formulaire, avant la fermeture -->
                <div class="login-divider">
                    <span>OU</span>
                </div>

                <div class="register-link">
                    Nouveau sur GOL ? <a href="choix_inscription.php">Créer un compte</a>
                </div>

                <!-- AJOUTEZ CE LIEN ICI -->
                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php" style="color: rgba(255,255,255,0.6); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.875rem; transition: color 0.3s;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        ← Retour à l'accueil
                    </a>
                </div>
            </form>
            
        </div>
    </div>

    <script>
        // Animation des étoiles filantes
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.stained-glass');
            
            function createShootingStar() {
                const star = document.createElement('div');
                star.style.cssText = `
                    position: absolute;
                    width: 2px;
                    height: 2px;
                    background: white;
                    border-radius: 50%;
                    top: ${Math.random() * 100}%;
                    left: ${Math.random() * 100}%;
                    animation: shootingStar ${Math.random() * 3 + 2}s linear infinite;
                    opacity: 0;
                `;
                container.appendChild(star);
                setTimeout(() => star.remove(), 5000);
            }
            
            setInterval(createShootingStar, 2000);
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shootingStar {
                    0% {
                        transform: translateX(0) translateY(0);
                        opacity: 0;
                    }
                    10% {
                        opacity: 1;
                    }
                    90% {
                        opacity: 1;
                    }
                    100% {
                        transform: translateX(100px) translateY(100px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>