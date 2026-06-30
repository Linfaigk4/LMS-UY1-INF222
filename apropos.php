<?php
/**
 * GOL (Gugle Online Learning) - À propos
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$page_title = 'À propos - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles spécifiques pour la page à propos */
.about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.about-hero {
    text-align: center;
    margin-bottom: 60px;
    padding: 60px 20px;
    background: linear-gradient(135deg, var(--primaire-sombre, #1e3a8a), var(--secondaire, #0f172a));
    border-radius: 30px;
    color: white;
}

.about-hero h1 {
    color: white;
    font-size: 2.5rem;
    margin-bottom: 20px;
}

.about-hero p {
    color: rgba(255,255,255,0.8);
    max-width: 600px;
    margin: 0 auto;
}

.about-section {
    background: var(--carte, white);
    border-radius: 24px;
    padding: 40px;
    margin-bottom: 40px;
    border: 1px solid var(--bordure, #e2e8f0);
}

.about-section h2 {
    font-size: 1.75rem;
    margin-bottom: 20px;
    color: var(--texte, #0f172a);
}

.about-section p {
    color: var(--texte-secondaire, #475569);
    line-height: 1.8;
    margin-bottom: 20px;
}

.mission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.mission-card {
    text-align: center;
    padding: 30px;
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 20px;
}

.mission-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primaire, #2563eb), var(--accent, #06b6d4));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.mission-icon svg {
    width: 32px;
    height: 32px;
    color: white;
}

.mission-card h3 {
    font-size: 1.25rem;
    margin-bottom: 10px;
    color: var(--texte, #0f172a);
}

.mission-card p {
    font-size: 0.875rem;
    color: var(--texte-secondaire, #475569);
}

.values-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.value-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--fond-secondaire, #f1f5f9);
    border-radius: 16px;
}

.value-icon {
    width: 48px;
    height: 48px;
    background: rgba(37, 99, 235, 0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.value-icon svg {
    width: 24px;
    height: 24px;
    color: var(--primaire, #2563eb);
}

.value-text h4 {
    font-size: 1rem;
    margin-bottom: 4px;
    color: var(--texte, #0f172a);
}

.value-text p {
    font-size: 0.75rem;
    color: var(--texte-secondaire, #475569);
    margin: 0;
}

.developer-card {
    background: linear-gradient(135deg, var(--primaire, #2563eb), var(--accent, #06b6d4));
    border-radius: 30px;
    padding: 50px;
    text-align: center;
    color: white;
    margin-top: 40px;
}

.developer-card h2 {
    color: white;
}

.developer-card p {
    color: rgba(255,255,255,0.9);
}

.developer-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 20px 0 10px;
}

.developer-meta {
    font-size: 0.875rem;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .about-section {
        padding: 24px;
    }
    
    .mission-grid,
    .values-list {
        grid-template-columns: 1fr;
    }
    
    .developer-card {
        padding: 30px;
    }
}

/* Mode sombre */
[data-theme="dark"] .about-section {
    background: var(--carte);
    border-color: var(--carte-border);
}

[data-theme="dark"] .mission-card,
[data-theme="dark"] .value-item {
    background: var(--carte-hover);
}

[data-theme="dark"] .mission-card h3,
[data-theme="dark"] .value-text h4 {
    color: var(--texte);
}

[data-theme="dark"] .mission-card p,
[data-theme="dark"] .value-text p {
    color: var(--texte-secondaire);
}
</style>

<div class="about-container">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1>À propos de GOL</h1>
        <p>Gugle Online Learning - La plateforme d'apprentissage nouvelle génération</p>
    </div>

    <!-- Notre histoire -->
    <div class="about-section">
        <h2>Notre histoire</h2>
        <p>Fondée en 2026, GOL (Gugle Online Learning) est née de la volonté de rendre l'éducation accessible à tous, partout et à tout moment. Notre plateforme innovante combine technologie de pointe et pédagogie moderne pour offrir une expérience d'apprentissage unique.</p>
        <p>Depuis notre lancement, nous avons formé plus de 1000 apprenants et collaboré avec des dizaines d'enseignants passionnés pour créer des contenus de qualité.</p>
    </div>

    <!-- Notre mission -->
    <div class="about-section">
        <h2>Notre mission</h2>
        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3>Éducation accessible</h3>
                <p>Rendre l'apprentissage accessible à tous, sans barrières géographiques ou financières.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <h3>Qualité pédagogique</h3>
                <p>Proposer des formations de haute qualité, conçues par des experts.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                </div>
                <h3>Innovation continue</h3>
                <p>Innover constamment pour offrir la meilleure expérience d'apprentissage.</p>
            </div>
        </div>
    </div>

    <!-- Nos valeurs -->
    <div class="about-section">
        <h2>Nos valeurs</h2>
        <div class="values-list">
            <div class="value-item">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                </div>
                <div class="value-text">
                    <h4>Excellence</h4>
                    <p>Viser l'excellence dans tout ce que nous faisons</p>
                </div>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="value-text">
                    <h4>Innovation</h4>
                    <p>Repousser les limites de la technologie éducative</p>
                </div>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <div class="value-text">
                    <h4>Communauté</h4>
                    <p>Créer une communauté d'apprenants et d'enseignants</p>
                </div>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="value-text">
                    <h4>Intégrité</h4>
                    <p>Agir avec transparence et honnêteté</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Développeur -->
    <div class="developer-card">
        <h2>Le développeur</h2>
        <div class="developer-name">ESSENGUE BILOA VICTORIEN MICHEL</div>
        <div class="developer-meta">Matricule: 23U2628 | INF-L2 | Université de Yaoundé 1</div>
        <p style="margin-top: 20px;">Étudiant passionné en développement web, j'ai conçu cette plateforme dans le cadre du projet universitaire INF222 - Développement Web. Mon objectif est de créer des solutions innovantes qui facilitent l'apprentissage et le partage des connaissances.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>