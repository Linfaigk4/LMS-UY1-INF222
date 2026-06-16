<?php
/**
 * GOL (Gugle Online Learning) - Page d'accueil
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$page_title = 'Accueil - Plateforme d\'apprentissage nouvelle génération';

// Récupérer les modules pour la section
$modules = obtenirModules(true);
$statistiques = obtenirStatistiquesGlobales();
?>

<?php include 'includes/header.php'; ?>

<!-- ============================================
     SECTION HERO - DESIGN PREMIUM
     ============================================ -->
<section class="hero-section">
    <div class="hero-bg-gradient"></div>
    <div class="hero-particles" id="heroParticles"></div>
    
    <div class="container hero-container">
        <div class="hero-content animate-slideUp">
            <div class="hero-badge">
            <span class="badge-glow">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle;margin-right:6px;">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 2v3"></path>
                    <path d="M12 19v3"></path>
                    <path d="M2 12h3"></path>
                    <path d="M19 12h3"></path>
                    <path d="M4.9 4.9l2.1 2.1"></path>
                    <path d="M17 17l2.1 2.1"></path>
                    <path d="M4.9 19.1l2.1-2.1"></path>
                    <path d="M17 7l2.1-2.1"></path>
                </svg>
                Plateforme Premium 2026
            </span>
            </div>
            
            <h1 class="hero-title">
                Apprenez les compétences
                <span class="gradient-text">du futur</span>
            </h1>
            
            <p class="hero-description">
                GOL (Gugle Online Learning) est la plateforme d'apprentissage nouvelle génération 
                qui combine technologie de pointe et pédagogie innovante pour former les talents de demain.
            </p>
            
            <div class="hero-actions">
                <a href="choix_inscription.php" class="btn-hero-primary">
                    Commencer gratuitement
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
                <a href="#modules" class="btn-hero-secondary">
                    Explorer les modules
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </a>
            </div>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number" id="statEtudiants"><?= number_format($statistiques['nb_etudiants'] ?? 1247) ?>+</span>
                    <span class="stat-label">Étudiants actifs</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-number" id="statModules"><?= number_format($statistiques['nb_modules'] ?? 5) ?></span>
                    <span class="stat-label">Modules disponibles</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-number" id="statCertificats"><?= number_format($statistiques['nb_certificats'] ?? 892) ?>+</span>
                    <span class="stat-label">Certificats délivrés</span>
                </div>
            </div>
        
        <div class="hero-visual animate-scaleIn">
            <div class="hero-card-3d">
                <div class="card-content">
                    <div class="card-glow"></div>
                    <div class="card-preview">
                        <div class="preview-header">
                            <div class="preview-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span>Dashboard GOL</span>
                        </div>
                        <div class="preview-chart">
                            <div class="chart-bar" style="height: 60px;"></div>
                            <div class="chart-bar" style="height: 85px;"></div>
                            <div class="chart-bar" style="height: 45px;"></div>
                            <div class="chart-bar" style="height: 92px;"></div>
                            <div class="chart-bar" style="height: 70px;"></div>
                        </div>
                        <div class="preview-progress">
                            <div class="progress-label">Progression moyenne</div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 78%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="hero-wave">
        <svg viewBox="0 0 1440 120" fill="none">
            <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z" fill="url(#waveGrad)"/>
            <defs>
                <linearGradient id="waveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="var(--primaire)" stop-opacity="0.1"/>
                    <stop offset="100%" stop-color="var(--accent)" stop-opacity="0.1"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
</section>

<!-- ============================================
     SECTION POUR QUI ? (CHOIX RÔLE)
     ============================================ -->
<section class="role-section" id="role">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">Pour qui ?</span>
            <h2>Une plateforme adaptée à <span class="gradient-text">vos besoins</span></h2>
            <p>Que vous soyez étudiant, enseignant ou professionnel, GOL vous offre l'expérience d'apprentissage parfaite.</p>
        </div>
        
        <div class="role-cards">
            <div class="role-card animate-on-scroll" data-role="etudiant">
                <div class="role-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3>Étudiant</h3>
                <p>Accédez à des modules de formation complets, suivez votre progression et obtenez des certificats reconnus.</p>
                <ul class="role-features">
                    <li> Cours illimités</li>
                    <li> Progression en temps réel</li>
                    <li> Certificats premium</li>
                </ul>
                <a href="inscription_etudiant.php" class="btn-role">Je suis étudiant →</a>
            </div>
            
            <div class="role-card animate-on-scroll" data-role="enseignant">
                <div class="role-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <h3>Enseignant</h3>
                <p>Créez des cours interactifs, suivez vos étudiants et développez votre communauté d'apprenants.</p>
                <ul class="role-features">
                    <li> Création de cours</li>
                    <li> Analytics avancés</li>
                    <li> Gestion des étudiants</li>
                </ul>
                <a href="inscription_enseignant.php" class="btn-role">Je suis enseignant →</a>
            </div>
            
            <div class="role-card animate-on-scroll" data-role="promoteur">
                <div class="role-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                </div>
                <h3>Institution / Promoteur</h3>
                <p>Déployez GOL au sein de votre organisation et bénéficiez d'outils de gestion complets.</p>
                <ul class="role-features">
                    <li> Gestion multi-utilisateurs</li>
                    <li> Tableaux de bord globaux</li>
                    <li> Sécurité renforcée</li>
                </ul>
                <a href="connexion.php" class="btn-role">Espace institution →</a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION MODULES POPULAIRES
     ============================================ -->
<section class="modules-section" id="modules">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">Découvrir</span>
            <h2>Modules les plus <span class="gradient-text">populaires</span></h2>
            <p>Des formations conçues par des experts pour vous aider à atteindre vos objectifs.</p>
        </div>
        
        <div class="modules-grid">
            <?php if (!empty($modules)): ?>
                <?php foreach (array_slice($modules, 0, 6) as $module): ?>
                    <div class="module-card animate-on-scroll">
                        <div class="module-card-inner">
                            <div class="module-image">
                                <?php if (!empty($module['image_principale'])): ?>
                                    <img src="uploads/modules_images/<?= htmlspecialchars($module['image_principale']) ?>" alt="<?= htmlspecialchars($module['nom_module']) ?>">
                                <?php else: ?>
                                    <div class="module-image-placeholder">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="module-level">
                                    <span class="level-badge <?= $module['niveau'] ?>">
                                        <?= $module['niveau'] === 'debutant' ? 'Débutant' : ($module['niveau'] === 'intermediaire' ? 'Intermédiaire' : 'Avancé') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?= htmlspecialchars($module['nom_module']) ?></h3>
                                <p><?= htmlspecialchars(substr($module['description'] ?? '', 0, 100)) ?>...</p>
                                <div class="module-meta">
                                    <span class="meta-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <?= $module['nb_cours'] ?? 0 ?> cours
                                    </span>
                                    <span class="meta-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                        </svg>
                                        <?= $module['duree_totale'] ?? 120 ?> min
                                    </span>
                                </div>
                                <a href="module.php?id=<?= $module['id_module'] ?>" class="btn-module">
                                    Découvrir
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                        <polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucun module disponible pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="modules-cta">
            <a href="modules.php" class="btn-voir-tous">
                Voir tous les modules
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION FONCTIONNALITÉS PREMIUM
     ============================================ -->
<section class="features-section">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">Fonctionnalités</span>
            <h2>Une expérience d'apprentissage <span class="gradient-text">unique</span></h2>
            <p>Découvrez tous les outils qui font de GOL une plateforme d'exception.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3>Apprentissage adaptatif</h3>
                <p>Un parcours personnalisé qui s'adapte à votre rythme et à votre niveau.</p>
            </div>
            
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h3>Contenus multimédia</h3>
                <p>Vidéos, PDF interactifs, QCM et exercices pratiques pour une immersion totale.</p>
            </div>
            
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M22 12h-4l-3 9-4-18-3 9H2"/>
                    </svg>
                </div>
                <h3>Certification officielle</h3>
                <p>Obtenez des certificats vérifiables et partagez vos compétences sur LinkedIn.</p>
            </div>
            
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <h3>Notifications intelligentes</h3>
                <p>Restez informé de votre progression et des nouvelles opportunités.</p>
            </div>
            
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <h3>Suivi en temps réel</h3>
                <p>Visualisez votre progression et identifiez vos points d'amélioration.</p>
            </div>
            
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M4 4h16v16H4z"/>
                        <path d="M9 9h6v6H9z"/>
                        <line x1="9" y1="15" x2="15" y2="9"/>
                    </svg>
                </div>
                <h3>Communauté active</h3>
                <p>Échangez avec d'autres apprenants et enseignants sur notre forum intégré.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION TÉMOIGNAGES
     ============================================ -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">Témoignages</span>
            <h2>Ce que nos <span class="gradient-text">apprenants</span> disent</h2>
            <p>Des milliers d'étudiants nous font confiance pour leur formation.</p>
        </div>
        
        <div class="testimonials-slider">
            <div class="testimonial-card animate-on-scroll">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                    GOL a complètement transformé ma façon d'apprendre. Les modules sont bien structurés 
                    et le suivi de progression est très motivant !
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">MK</div>
                    <div class="author-info">
                        <strong>Marie Kamga</strong>
                        <span>Étudiante en Développement Web</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card animate-on-scroll">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                    En tant qu'enseignant, la plateforme me permet de créer des cours interactifs 
                    et de suivre facilement la progression de mes étudiants.
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">PN</div>
                    <div class="author-info">
                        <strong>Prof. Paul Ngana</strong>
                        <span>Enseignant en Data Science</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card animate-on-scroll">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                    Le design est magnifique et l'expérience utilisateur est incroyable. 
                    Je recommande vivement GOL à toute personne souhaitant se former.
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">BE</div>
                    <div class="author-info">
                        <strong>Brigitte Essomba</strong>
                        <span>Certifiée en Marketing Digital</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION CALL TO ACTION
     ============================================ -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content animate-on-scroll">
            <h2>Prêt à commencer votre <span class="gradient-text">aventure</span> ?</h2>
            <p>Rejoignez des milliers d'apprenants et développez les compétences qui feront la différence.</p>
            <div class="cta-buttons">
                <a href="choix_inscription.php" class="btn-cta-primary">
                    Créer mon compte gratuit
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
                <a href="connexion.php" class="btn-cta-secondary">
                    Déjà inscrit ? Se connecter
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* Styles additionnels spécifiques à la page d'accueil */

/* Hero Section */
.hero-section {
    position: relative;
    min-height: 90vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: linear-gradient(135deg, var(--fond) 0%, var(--fond-secondaire) 100%);
}

.hero-bg-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 50%, var(--primaire-clair) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--accent) 0%, transparent 50%);
    opacity: 0.1;
}

.hero-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-12);
    align-items: center;
    position: relative;
    z-index: 2;
}

.hero-badge {
    margin-bottom: var(--spacing-4);
}

.badge-glow {
    display: inline-block;
    padding: var(--spacing-2) var(--spacing-4);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--primaire);
    border: 1px solid var(--glass-border);
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    line-height: 1.1;
    margin-bottom: var(--spacing-6);
}

.gradient-text {
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-description {
    font-size: 1.125rem;
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-8);
    max-width: 90%;
}

.hero-actions {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-10);
}

.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4) var(--spacing-8);
    background: var(--primaire-gradient);
    color: white;
    border-radius: var(--radius-full);
    font-weight: 600;
    text-decoration: none;
    transition: all var(--transition-base);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--ombre-glow);
}

.btn-hero-secondary {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4) var(--spacing-8);
    background: var(--carte);
    color: var(--texte);
    border-radius: var(--radius-full);
    font-weight: 600;
    text-decoration: none;
    border: 1px solid var(--bordure);
    transition: all var(--transition-base);
}

.btn-hero-secondary:hover {
    background: var(--carte-hover);
    transform: translateY(-2px);
}

.hero-stats {
    display: flex;
    gap: var(--spacing-8);
    align-items: center;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--primaire);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: var(--bordure);
}

/* Hero Visual Card */
.hero-visual {
    position: relative;
}

.hero-card-3d {
    perspective: 1000px;
}

.card-content {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-2xl);
    padding: var(--spacing-6);
    border: 1px solid var(--glass-border);
    transform: rotateY(10deg) rotateX(5deg);
    transition: transform var(--transition-base);
}

.card-content:hover {
    transform: rotateY(5deg) rotateX(2deg);
}

.card-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 50% 50%, var(--primaire-clair) 0%, transparent 70%);
    opacity: 0.1;
    border-radius: var(--radius-2xl);
}

.preview-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-6);
}

.preview-dots {
    display: flex;
    gap: 6px;
}

.preview-dots span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--danger);
}

.preview-dots span:nth-child(2) {
    background: var(--avertissement);
}

.preview-dots span:nth-child(3) {
    background: var(--succes);
}

.preview-chart {
    display: flex;
    align-items: flex-end;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
    height: 120px;
}

.chart-bar {
    flex: 1;
    background: linear-gradient(180deg, var(--primaire), var(--accent));
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    transition: height var(--transition-base);
}

.preview-progress .progress-label {
    font-size: 0.75rem;
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-2);
}

.preview-progress .progress-bar-bg {
    background: var(--bordure);
    border-radius: var(--radius-full);
    height: 8px;
    overflow: hidden;
}

.preview-progress .progress-bar-fill {
    background: linear-gradient(90deg, var(--primaire), var(--accent));
    height: 100%;
    border-radius: var(--radius-full);
    width: 0%;
}

/* Hero Wave */
.hero-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1;
}

/* Section Header */
.section-header {
    text-align: center;
    max-width: 800px;
    margin: 0 auto var(--spacing-12);
}

.section-badge {
    display: inline-block;
    padding: var(--spacing-2) var(--spacing-4);
    background: var(--fond-secondaire);
    color: var(--primaire);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: var(--spacing-4);
}

.section-header h2 {
    margin-bottom: var(--spacing-4);
}

.section-header p {
    color: var(--texte-secondaire);
}

/* Role Cards */
.role-section {
    padding: var(--spacing-20) 0;
}

.role-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-8);
}

.role-card {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    padding: var(--spacing-8);
    text-align: center;
    border: 1px solid var(--bordure);
    transition: all var(--transition-base);
}

.role-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--ombre-xl);
    border-color: var(--primaire);
}

.role-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-6);
    background: linear-gradient(135deg, var(--primaire-clair) 0%, var(--accent) 100%);
    border-radius: var(--radius-2xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.role-card h3 {
    margin-bottom: var(--spacing-4);
}

.role-card p {
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-6);
}

.role-features {
    list-style: none;
    text-align: left;
    margin-bottom: var(--spacing-6);
}

.role-features li {
    padding: var(--spacing-2) 0;
    color: var(--texte-secondaire);
}

.btn-role {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    width: 100%;
    padding: var(--spacing-3) var(--spacing-6);
    background: var(--fond-secondaire);
    color: var(--primaire);
    border-radius: var(--radius-full);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-base);
}

.btn-role:hover {
    background: var(--primaire);
    color: white;
}

/* Modules Grid */
.modules-section {
    padding: var(--spacing-20) 0;
    background: var(--fond-secondaire);
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-8);
    margin-bottom: var(--spacing-10);
}

.module-card {
    background: var(--carte);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all var(--transition-base);
    border: 1px solid var(--bordure);
}

.module-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--ombre-xl);
}

.module-image {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primaire-sombre), var(--secondaire));
}

.module-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.module-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: white;
    opacity: 0.5;
}

.module-level {
    position: absolute;
    top: var(--spacing-4);
    right: var(--spacing-4);
}

.level-badge {
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.level-badge.debutant {
    background: var(--succes);
    color: white;
}

.level-badge.intermediaire {
    background: var(--avertissement);
    color: white;
}

.level-badge.avance {
    background: var(--danger);
    color: white;
}

.module-content {
    padding: var(--spacing-6);
}

.module-content h3 {
    margin-bottom: var(--spacing-3);
}

.module-content p {
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-4);
    font-size: 0.875rem;
}

.module-meta {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-1);
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.btn-module {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--primaire);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-base);
}

.btn-module:hover {
    gap: var(--spacing-3);
}

.modules-cta {
    text-align: center;
}

.btn-voir-tous {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-8);
    border: 2px solid var(--primaire);
    border-radius: var(--radius-full);
    color: var(--primaire);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-base);
}

.btn-voir-tous:hover {
    background: var(--primaire);
    color: white;
    transform: translateY(-2px);
}

/* Features Section */
.features-section {
    padding: var(--spacing-20) 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-8);
}

.feature-card {
    text-align: center;
    padding: var(--spacing-8);
    background: var(--carte);
    border-radius: var(--radius-xl);
    border: 1px solid var(--bordure);
    transition: all var(--transition-base);
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ombre-lg);
}

.feature-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto var(--spacing-4);
    background: var(--fond-secondaire);
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primaire);
}

.feature-card h3 {
    margin-bottom: var(--spacing-3);
}

.feature-card p {
    color: var(--texte-secondaire);
}

/* Testimonials Section */
.testimonials-section {
    padding: var(--spacing-20) 0;
    background: var(--fond-secondaire);
}

.testimonials-slider {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-8);
}

.testimonial-card {
    background: var(--carte);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    position: relative;
    border: 1px solid var(--bordure);
}

.testimonial-quote {
    position: absolute;
    top: var(--spacing-6);
    right: var(--spacing-6);
    font-size: 4rem;
    color: var(--primaire);
    opacity: 0.2;
    font-family: serif;
}

.testimonial-text {
    margin-bottom: var(--spacing-6);
    font-style: italic;
    color: var(--texte-secondaire);
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
}

.author-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.author-info strong {
    display: block;
    margin-bottom: var(--spacing-1);
}

.author-info span {
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

/* CTA Section */
.cta-section {
    padding: var(--spacing-20) 0;
    background: linear-gradient(135deg, var(--primaire-sombre), var(--secondaire));
}

.cta-content {
    text-align: center;
    color: white;
}

.cta-content h2 {
    color: white;
    margin-bottom: var(--spacing-4);
}

.cta-content p {
    margin-bottom: var(--spacing-8);
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: var(--spacing-4);
    justify-content: center;
    flex-wrap: wrap;
}

.btn-cta-primary {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4) var(--spacing-8);
    background: white;
    color: var(--primaire);
    border-radius: var(--radius-full);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-base);
}

.btn-cta-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255,255,255,0.2);
}

.btn-cta-secondary {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4) var(--spacing-8);
    background: transparent;
    border: 2px solid white;
    color: white;
    border-radius: var(--radius-full);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-base);
}

.btn-cta-secondary:hover {
    background: white;
    color: var(--primaire);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-container {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .hero-description {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .hero-visual {
        display: none;
    }
    
    .role-cards,
    .modules-grid,
    .features-grid,
    .testimonials-slider {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}

/* Animations */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.animate-on-scroll.animated {
    opacity: 1;
    transform: translateY(0);
}

/* ============================================
   ANIMATIONS AVANCÉES POUR LANDING PAGE
   ============================================ */

/* Effet de particules flottantes */
.hero-particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.particle {
    position: absolute;
    background: radial-gradient(circle, rgba(59,130,246,0.4) 0%, rgba(6,182,212,0.1) 100%);
    border-radius: 50%;
    animation: floatParticle 15s infinite ease-in-out;
}

@keyframes floatParticle {
    0%, 100% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    20% {
        opacity: 0.5;
    }
    80% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(-100px) translateX(50px);
        opacity: 0;
    }
}

/* Animation de dégradé animé */
.animated-gradient {
    background: linear-gradient(270deg, var(--primaire), var(--accent), var(--info), var(--primaire));
    background-size: 400% 400%;
    animation: gradientShift 8s ease infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Effet de survol amélioré pour les cartes */
.module-card {
    transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}

.module-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.module-card:hover .module-image img {
    transform: scale(1.1);
}

.module-image {
    overflow: hidden;
}

.module-image img {
    transition: transform 0.5s ease;
}

/* Effet de texte défilant */
.scrolling-text {
    overflow: hidden;
    white-space: nowrap;
    animation: scrollText 20s linear infinite;
}

@keyframes scrollText {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

/* Animation des statistiques */
.stat-number {
    display: inline-block;
    animation: countUp 1s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes countUp {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Effet de glow au survol des boutons */
.btn-hero-primary {
    position: relative;
    overflow: hidden;
}

.btn-hero-primary::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -60%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s;
}

.btn-hero-primary:hover::after {
    opacity: 1;
}

/* Effet de ripple au clic */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.4);
    transform: scale(0);
    animation: ripple 0.6s linear;
    pointer-events: none;
}

@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Animation d'entrée des cartes */
.module-card, .feature-card, .role-card {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.module-card:nth-child(1) { animation-delay: 0.1s; }
.module-card:nth-child(2) { animation-delay: 0.2s; }
.module-card:nth-child(3) { animation-delay: 0.3s; }
.module-card:nth-child(4) { animation-delay: 0.4s; }
.module-card:nth-child(5) { animation-delay: 0.5s; }
.module-card:nth-child(6) { animation-delay: 0.6s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Section CTA améliorée */
.cta-section {
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, var(--primaire) 0%, transparent 70%);
    opacity: 0.1;
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Thème sombre - ajustements spécifiques */
[data-theme="dark"] .hero-section {
    background: linear-gradient(135deg, #0a0a0f 0%, #111827 100%);
}

[data-theme="dark"] .btn-hero-secondary {
    border-color: var(--carte-border);
    background: rgba(30, 41, 59, 0.8);
    color: white;
}

[data-theme="dark"] .btn-hero-secondary:hover {
    background: var(--carte-hover);
}
</style>

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
    
    // Particules pour le hero
    createParticles();
});

function createParticles() {
    const container = document.getElementById('heroParticles');
    if (!container) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            position: absolute;
            width: ${Math.random() * 4 + 2}px;
            height: ${Math.random() * 4 + 2}px;
            background: var(--primaire);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            opacity: ${Math.random() * 0.5};
            animation: float ${Math.random() * 10 + 5}s infinite ease-in-out;
            animation-delay: ${Math.random() * 5}s;
        `;
        container.appendChild(particle);
    }
}

// Ajout des styles pour les particules
const styleParticles = document.createElement('style');
styleParticles.textContent = `
    .hero-particles {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        overflow: hidden;
        pointer-events: none;
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0) translateX(0);
        }
        50% {
            transform: translateY(-20px) translateX(10px);
        }
    }
`;
document.head.appendChild(styleParticles);

// Création des particules flottantes
function createParticles() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;
    
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'hero-particles';
    heroSection.insertBefore(particlesContainer, heroSection.firstChild);
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        const size = Math.random() * 6 + 2;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.bottom = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        particlesContainer.appendChild(particle);
    }
}

// Effet de ripple sur les boutons
function addRippleEffect() {
    const buttons = document.querySelectorAll('.btn-hero-primary, .btn-hero-secondary, .btn-choice');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
}

// Animation des compteurs
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    counters.forEach(counter => {
        const target = parseInt(counter.innerText);
        let current = 0;
        const increment = target / 50;
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.innerText = Math.floor(current) + (counter.innerText.includes('+') ? '+' : '');
                requestAnimationFrame(updateCounter);
            } else {
                counter.innerText = target + (counter.innerText.includes('+') ? '+' : '');
            }
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(counter);
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    createParticles();
    addRippleEffect();
    animateCounters();
});
</script>

<?php include 'includes/footer.php'; ?>