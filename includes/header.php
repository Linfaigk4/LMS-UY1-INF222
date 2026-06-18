<!DOCTYPE html>
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
            <a href="tableau_bord.php" class="nav-link">Tableau de bord</a>
            <a href="index.php#modules" class="nav-link">Modules</a>
            <a href="certificat.php" class="nav-link">Certificats</a>
            <a href="profil.php" class="nav-link">Mon profil</a>
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
            <a href="deconnexion.php" class="btn-logout">Déconnexion</a>
        </div>
    </div>
</nav>

<main class="main-content">