<?php
/**
 * GOL (Gugle Online Learning) - Choix du type d'inscription
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

$page_title = 'Rejoindre GOL - Choisissez votre statut';
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles spécifiques à la page de choix */
.choice-hero {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    padding: var(--spacing-12) 0;
    background: linear-gradient(135deg, var(--fond) 0%, var(--fond-secondaire) 100%);
    position: relative;
    overflow: hidden;
}

.choice-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 50%, var(--primaire-clair) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, var(--accent) 0%, transparent 40%);
    opacity: 0.08;
    pointer-events: none;
}

.choice-container {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-6);
}

.choice-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
}

.choice-badge {
    display: inline-block;
    padding: var(--spacing-2) var(--spacing-4);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--primaire);
    border: 1px solid var(--glass-border);
    margin-bottom: var(--spacing-4);
}

.choice-title {
    font-size: clamp(2rem, 4vw, 3rem);
    margin-bottom: var(--spacing-4);
}

.choice-title .gradient-text {
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.choice-description {
    color: var(--texte-secondaire);
    max-width: 600px;
    margin: 0 auto;
}

.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-8);
    margin-bottom: var(--spacing-12);
}

.choice-card {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    overflow: hidden;
    transition: all var(--transition-base);
    border: 1px solid var(--bordure);
    position: relative;
}

.choice-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--ombre-xl);
}

.choice-card.featured {
    border: 2px solid var(--primaire);
    transform: scale(1.02);
}

.choice-card.featured:hover {
    transform: scale(1.02) translateY(-8px);
}

.card-badge {
    position: absolute;
    top: var(--spacing-4);
    right: var(--spacing-4);
    background: var(--primaire);
    color: white;
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.card-header {
    padding: var(--spacing-8);
    text-align: center;
    background: linear-gradient(135deg, var(--fond-secondaire) 0%, var(--carte) 100%);
}

.card-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-4);
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-2xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.card-header h3 {
    font-size: 1.75rem;
    margin-bottom: var(--spacing-2);
}

.card-price {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primaire);
}

.card-price small {
    font-size: 0.875rem;
    font-weight: normal;
    color: var(--texte-secondaire);
}

.card-body {
    padding: var(--spacing-8);
}

.features-list {
    list-style: none;
    margin-bottom: var(--spacing-8);
}

.features-list li {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-2) 0;
    color: var(--texte-secondaire);
}

.features-list li::before {
    content: '✓';
    color: var(--succes);
    font-weight: bold;
}

.btn-choice {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    width: 100%;
    padding: var(--spacing-4);
    background: var(--primaire);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}

.btn-choice:hover {
    transform: translateY(-2px);
    box-shadow: var(--ombre-glow);
}

.btn-choice-outline {
    background: transparent;
    border: 2px solid var(--primaire);
    color: var(--primaire);
}

.btn-choice-outline:hover {
    background: var(--primaire);
    color: white;
}

.choice-footer {
    text-align: center;
    padding-top: var(--spacing-8);
    border-top: 1px solid var(--bordure);
}

.choice-footer p {
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-4);
}

.choice-footer a {
    color: var(--primaire);
    text-decoration: none;
    font-weight: 600;
}

.choice-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .cards-container {
        grid-template-columns: 1fr;
    }
    
    .choice-card.featured {
        transform: none;
    }
    
    .choice-card.featured:hover {
        transform: translateY(-8px);
    }
}
</style>

<div class="choice-hero">
    <div class="choice-container">
        <div class="choice-header animate-on-scroll">
            <span class="choice-badge">Rejoignez l'aventure GOL</span>
            <h1 class="choice-title">
                Choisissez votre <span class="gradient-text">statut</span>
            </h1>
            <p class="choice-description">
                Que vous soyez étudiant désireux d'apprendre ou enseignant passionné par le partage de connaissances, 
                GOL vous offre la plateforme idéale.
            </p>
        </div>

        <div class="cards-container">
            <!-- Carte Étudiant -->
            <div class="choice-card animate-on-scroll">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3>Étudiant</h3>
                    <div class="card-price">
                        Gratuit
                        <small>à vie</small>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="features-list">
                        <li>Accès à tous les modules gratuits</li>
                        <li>Suivi de progression en temps réel</li>
                        <li>Certificats officiels à la validation</li>
                        <li>Communauté d'apprenants active</li>
                        <li>Support prioritaire</li>
                        <li>Interface personnalisable</li>
                    </ul>
                    <a href="inscription_etudiant.php" class="btn-choice">
                        Je m'inscris comme étudiant
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Carte Enseignant (Mise en avant) -->
            <div class="choice-card featured animate-on-scroll">
                <div class="card-badge">POPULAIRE</div>
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <h3>Enseignant</h3>
                    <div class="card-price">
                        Gratuit
                        <small>pour les premiers inscrits</small>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="features-list">
                        <li>Création de cours illimitée</li>
                        <li>Statistiques détaillées</li>
                        <li>Gestion des étudiants</li>
                        <li>Export des résultats</li>
                        <li>Certificats personnalisés</li>
                        <li>Support dédié</li>
                    </ul>
                    <a href="inscription_enseignant.php" class="btn-choice btn-choice-outline">
                        Je m'inscris comme enseignant
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Carte Institution -->
            <div class="choice-card animate-on-scroll">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <h3>Institution / Promoteur</h3>
                    <div class="card-price">
                        Sur devis
                        <small>solution sur mesure</small>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="features-list">
                        <li>Gestion multi-utilisateurs</li>
                        <li>Tableaux de bord globaux</li>
                        <li>API personnalisable</li>
                        <li>Hébergement dédié</li>
                        <li>Formation des équipes</li>
                        <li>Support 24/7</li>
                    </ul>
                    <a href="connexion.php" class="btn-choice btn-choice-outline">
                        Contacter notre équipe
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="choice-footer animate-on-scroll">
            <p>Vous avez déjà un compte ? <a href="connexion.php">Connectez-vous ici</a></p>
            <p style="font-size: 0.875rem;">En vous inscrivant, vous acceptez nos <a href="#">conditions d'utilisation</a> et notre <a href="#">politique de confidentialité</a>.</p>
        </div>
    </div>
</div>

<script>
// Animation au scroll
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(el => observer.observe(el));
});

// Ajout des styles d'animation
const styleAnim = document.createElement('style');
styleAnim.textContent = `
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .animate-on-scroll.animated {
        opacity: 1;
        transform: translateY(0);
    }
`;
document.head.appendChild(styleAnim);
</script>

<?php include 'includes/footer.php'; ?>