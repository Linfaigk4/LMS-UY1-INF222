<?php
/**
 * GOL (Gugle Online Learning) - En-tête global
 */
if (!function_exists('icone')) {
    require_once __DIR__ . '/../assets/svg/icons.php';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'GOL - Gugle Online Learning' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= defined('SITE_URL') ? SITE_URL : '/' ?>assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--fond, #f8fafc);
            color: var(--texte, #0f172a);
            line-height: 1.6;
        }
        
        :root {
            --primaire: #2563eb;
            --primaire-clair: #3b82f6;
            --primaire-sombre: #1d4ed8;
            --primaire-gradient: linear-gradient(135deg, #2563eb, #06b6d4);
            --secondaire: #0f172a;
            --accent: #06b6d4;
            --succes: #22c55e;
            --danger: #ef4444;
            --avertissement: #f59e0b;
            --info: #8b5cf6;
            --fond: #f8fafc;
            --fond-secondaire: #f1f5f9;
            --carte: #ffffff;
            --carte-hover: #f8fafc;
            --carte-border: #e2e8f0;
            --texte: #0f172a;
            --texte-secondaire: #475569;
            --texte-tertiaire: #64748b;
            --bordure: #e2e8f0;
            --ombre-sm:   0 1px 3px rgba(0,0,0,0.08);
            --ombre-md:   0 4px 6px -1px rgba(0,0,0,0.1);
            --ombre-lg:   0 10px 15px -3px rgba(0,0,0,0.1);
            --ombre-xl:   0 20px 25px -5px rgba(0,0,0,0.12);
            --ombre-glow: 0 0 20px rgba(37,99,235,0.35);
            --glass-bg:     rgba(255,255,255,0.06);
            --glass-border: rgba(255,255,255,0.12);
            --radius-sm:   0.25rem;
            --radius-md:   0.5rem;
            --radius-lg:   0.75rem;
            --radius-xl:   1rem;
            --radius-2xl:  1.5rem;
            --radius-full: 9999px;
            --spacing-1:  0.25rem;
            --spacing-2:  0.5rem;
            --spacing-3:  0.75rem;
            --spacing-4:  1rem;
            --spacing-5:  1.25rem;
            --spacing-6:  1.5rem;
            --spacing-8:  2rem;
            --spacing-10: 2.5rem;
            --spacing-12: 3rem;
            --transition-base: 0.3s ease;
        }

        [data-theme="dark"] {
            --fond:           #0a0a0f;
            --fond-secondaire: #111827;
            --carte:          #1e1e2e;
            --carte-hover:    #2a2a3e;
            --carte-border:   #2d3348;
            --texte:          #f1f5f9;
            --texte-secondaire: #cbd5e1;
            --texte-tertiaire:  #94a3b8;
            --bordure:        #334155;
            --ombre-sm:   0 1px 3px rgba(0,0,0,0.4);
            --ombre-md:   0 4px 6px -1px rgba(0,0,0,0.5);
            --ombre-lg:   0 10px 15px -3px rgba(0,0,0,0.5);
            --ombre-xl:   0 20px 25px -5px rgba(0,0,0,0.6);
            --ombre-glow: 0 0 24px rgba(37,99,235,0.5);
            --glass-bg:     rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.08);
        }
        
        /* Navbar */
        .navbar-premium {
            position: sticky;
            top: 0;
            background: var(--carte);
            border-bottom: 1px solid var(--bordure);
            padding: 0.75rem 2rem;
            z-index: 100;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1280px;
            margin: 0 auto;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primaire);
        }
        
        .nav-menu {
            display: flex;
            gap: 2rem;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--texte-secondaire);
            transition: color var(--transition-base);
        }
        
        .nav-link:hover {
            color: var(--primaire);
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .theme-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texte-secondaire);
            padding: 0.5rem;
            border-radius: var(--radius-full);
            transition: all var(--transition-base);
        }
        
        .theme-btn:hover {
            background: var(--fond-secondaire);
            color: var(--primaire);
        }
        
        .btn-logout {
            padding: 0.5rem 1rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--transition-base);
        }
        
        .btn-logout:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .main-content {
            min-height: calc(100vh - 200px);
            padding: var(--spacing-8) 0;
        }
        
        @media (max-width: 768px) {
            .navbar-premium {
                padding: 0.5rem 1rem;
            }
            
            .nav-menu {
                display: none;
            }
        }
    </style>
</head>
<body>

<script src="<?= defined('SITE_URL') ? SITE_URL : '/' ?>assets/js/app.js"></script>

<nav class="navbar-premium">
    <div class="nav-container">
        <a href="tableau_bord.php" class="nav-logo">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                <path d="M16 2L2 9L16 16L30 9L16 2Z" fill="url(#grad1)" stroke="currentColor" stroke-width="1.5"/>
                <path d="M2 16L16 23L30 16" stroke="currentColor" stroke-width="1.5" fill="none"/>
                <path d="M2 23L16 30L30 23" stroke="currentColor" stroke-width="1.5" fill="none"/>
                <defs>
                    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#2563eb"/>
                        <stop offset="100%" stop-color="#06b6d4"/>
                    </linearGradient>
                </defs>
            </svg>
            <span>GOL</span>
        </a>
        
        <div class="nav-menu">
            <a href="index.php" class="nav-link">Accueil</a>
            <a href="apropos.php" class="nav-link">À propos</a>
            <?php if (estConnecte()): ?>
                <a href="profil.php" class="nav-link">Profil</a>
                <a href="tableau_bord.php" class="nav-link">Tableau de bord</a>
                <?php if (estEnseignant() || estPromoteur()): ?>
                    <a href="gestion_cours.php" class="nav-link">Gestion des cours</a>
                <?php endif; ?>
                <?php if (estSuperAdmin()): ?>
                    <a href="administration.php" class="nav-link">Administration</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="nav-actions">
            <button class="theme-btn" id="themeToggle">
                <svg class="theme-icon-light" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                </svg>
                <svg class="theme-icon-dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
            <?php if (estConnecte()): ?>
                <a href="deconnexion.php" class="btn-logout">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php" class="btn-logout" style="background:var(--primaire)">Connexion</a>
                <a href="choix_inscription.php" class="btn-logout" style="background:var(--succes)">S'inscrire</a>
            <?php endif; ?>
            <!-- Bouton hamburger — visible uniquement mobile -->
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Menu" aria-expanded="false">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

<!-- Overlay mobile -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar mobile -->
<aside class="mobile-sidebar" id="mobileSidebar" role="navigation" aria-label="Menu mobile">
    <div class="mobile-sidebar-header">
        <a href="tableau_bord.php" class="nav-logo" style="font-size:1.1rem">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                <path d="M16 2L2 9L16 16L30 9L16 2Z" fill="#2563eb"/>
                <path d="M2 16L16 23L30 16" stroke="#2563eb" stroke-width="1.5" fill="none"/>
            </svg>
            GOL
        </a>
        <button class="mobile-close-btn" id="mobileCloseBtn" aria-label="Fermer le menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    <nav class="mobile-nav">
        <a href="index.php" class="mobile-nav-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Accueil
        </a>
        <a href="apropos.php" class="mobile-nav-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            À propos
        </a>
        <?php if (estConnecte()): ?>
            <a href="profil.php" class="mobile-nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profil
            </a>
            <a href="tableau_bord.php" class="mobile-nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Tableau de bord
            </a>
            <?php if (estEnseignant() || estPromoteur()): ?>
                <a href="gestion_cours.php" class="mobile-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5V4.5z"/></svg>
                    Gestion des cours
                </a>
            <?php endif; ?>
            <?php if (estSuperAdmin()): ?>
                <a href="administration.php" class="mobile-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Administration
                </a>
            <?php endif; ?>
            <div class="mobile-nav-divider"></div>
            <a href="deconnexion.php" class="mobile-nav-link mobile-nav-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Déconnexion
            </a>
        <?php else: ?>
            <div class="mobile-nav-divider"></div>
            <a href="connexion.php" class="mobile-nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Connexion
            </a>
            <a href="choix_inscription.php" class="mobile-nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                S'inscrire
            </a>
        <?php endif; ?>
    </nav>
</aside>

<main class="main-content">

