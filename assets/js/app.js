/**
 * GOL (Gugle Online Learning) - JavaScript Premium
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

// ============================================
// GESTION DU THÈME (Clair/Sombre)
// ============================================

function chargerTheme() {
    const theme = localStorage.getItem('gol_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.cookie = `gol_theme=${theme}; path=/; max-age=31536000`;
}

function changerTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('gol_theme', newTheme);
    document.cookie = `gol_theme=${newTheme}; path=/; max-age=31536000`;
    
    afficherNotification(`Thème ${newTheme === 'dark' ? 'sombre' : 'clair'} activé`, 'info');
}

// ============================================
// REQUÊTES AJAX CENTRALISÉES
// ============================================

async function envoyerRequeteAjax(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`ajax.php?action=${endpoint}`, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Une erreur est survenue');
        }
        
        return result;
    } catch (error) {
        console.error('Erreur AJAX:', error);
        afficherNotification(error.message, 'danger');
        throw error;
    }
}

// ============================================
// NOTIFICATIONS TOAST
// ============================================

function afficherNotification(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-message">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ============================================
// MODALES
// ============================================

function ouvrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function fermerModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// ============================================
// MENU MOBILE
// ============================================

function ouvrirMenuMobile() {
    const sidebar = document.getElementById('mobileSidebar');
    if (sidebar) {
        sidebar.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function fermerMenuMobile() {
    const sidebar = document.getElementById('mobileSidebar');
    if (sidebar) {
        sidebar.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// ============================================
// RECHERCHE EN TEMPS RÉEL
// ============================================

function rechercherCours() {
    const searchTerm = document.getElementById('searchCours')?.value.toLowerCase() || '';
    const coursItems = document.querySelectorAll('.cours-item');
    
    coursItems.forEach(item => {
        const titre = item.querySelector('.cours-titre')?.textContent.toLowerCase() || '';
        const description = item.querySelector('.cours-description')?.textContent.toLowerCase() || '';
        
        if (titre.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function rechercherModules() {
    const searchTerm = document.getElementById('searchModule')?.value.toLowerCase() || '';
    const modulesItems = document.querySelectorAll('.module-card');
    
    modulesItems.forEach(item => {
        const titre = item.querySelector('.module-titre')?.textContent.toLowerCase() || '';
        
        if (titre.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// ============================================
// SOUMISSION D'ÉVALUATION (QCM)
// ============================================

async function soumettreEvaluation(evaluationId) {
    const formData = new FormData();
    const reponses = {};
    
    document.querySelectorAll('.question-item').forEach((question, index) => {
        const selected = question.querySelector('input:checked');
        if (selected) {
            reponses[index] = selected.value;
        }
    });
    
    formData.append('evaluation_id', evaluationId);
    formData.append('reponses', JSON.stringify(reponses));
    
    try {
        const result = await envoyerRequeteAjax('soumettre_evaluation', 'POST', {
            evaluation_id: evaluationId,
            reponses: reponses
        });
        
        afficherResultatEvaluation(result);
    } catch (error) {
        afficherNotification('Erreur lors de la soumission', 'danger');
    }
}

function afficherResultatEvaluation(resultat) {
    const modalContent = document.getElementById('resultatModalContent');
    if (modalContent) {
        const score = resultat.score || 0;
        const reussi = score >= (resultat.note_requise || 60);
        
        modalContent.innerHTML = `
            <div class="resultat-container">
                <div class="resultat-score ${reussi ? 'succes' : 'echec'}">
                    <span class="score-valeur">${score}%</span>
                </div>
                <h3>${reussi ? '🎉 Félicitations !' : '📚 Continuez vos efforts'}</h3>
                <p>${reussi ? 'Vous avez réussi cette évaluation !' : 'Vous pouvez réessayer pour améliorer votre score.'}</p>
                ${resultat.feedback ? `<div class="resultat-feedback">${resultat.feedback}</div>` : ''}
            </div>
        `;
    }
    
    ouvrirModal('resultatModal');
}

// ============================================
// MISE À JOUR DE LA PROGRESSION
// ============================================

function mettreAJourProgression(leconId, terminee = true) {
    envoyerRequeteAjax('maj_progression', 'POST', {
        lecon_id: leconId,
        terminee: terminee
    }).then(result => {
        const barreProgression = document.querySelector('.progress-bar-fill');
        if (barreProgression) {
            barreProgression.style.width = `${result.pourcentage}%`;
            barreProgression.setAttribute('aria-valuenow', result.pourcentage);
        }
        
        const texteProgression = document.querySelector('.progression-texte');
        if (texteProgression) {
            texteProgression.textContent = `${result.pourcentage}% complété`;
        }
        
        afficherNotification('Progression mise à jour !', 'succes');
    }).catch(error => {
        console.error('Erreur mise à jour progression:', error);
    });
}

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar-premium');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
});

// ============================================
// INITIALISATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Charger le thème
    chargerTheme();
    
    // Bouton de changement de thème
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', changerTheme);
    }
    
    // Fermeture des modales au clic sur l'overlay
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                fermerModal(modal.id);
            }
        });
    });
    
    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slideUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
    
    console.log('GOL LMS - Initialisé avec succès');
    console.log('Développeur: ESSENGUE BILOA VICTORIEN MICHEL (23U2628)');
});

// ============================================
// ALIASES — compatibilité pages existantes
// ============================================

// Pages qui appellent openModal/closeModal au lieu de ouvrirModal/fermerModal
function openModal(id)  { ouvrirModal(id); }
function closeModal(id) { fermerModal(id); }

// escapeHtml global (evaluation.php, gestion_quiz.php, certificat.php)
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// ============================================
// STYLES TOAST — injectés si absents
// ============================================
(function() {
    if (document.getElementById('gol-toast-styles')) return;
    const s = document.createElement('style');
    s.id = 'gol-toast-styles';
    s.textContent = `
        .gol-toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none}
        .toast-notification{padding:.75rem 1.25rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#fff;box-shadow:0 4px 20px rgba(0,0,0,.15);animation:golSlideIn .3s ease;pointer-events:all;max-width:320px}
        .toast-succes,.toast-success{background:#22c55e}
        .toast-danger{background:#ef4444}
        .toast-info{background:#2563eb}
        .toast-avertissement{background:#f59e0b}
        @keyframes golSlideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
    `;
    document.head.appendChild(s);
})();

// ============================================
// THÈME — application immédiate anti-flash
// ============================================
(function() {
    const t = localStorage.getItem('gol_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
    document.cookie = 'gol_theme=' + t + '; path=/; max-age=31536000';
})();

// ============================================
// MENU HAMBURGER MOBILE
// ============================================

function ouvrirMenuMobile() {
    const sidebar  = document.getElementById('mobileSidebar');
    const overlay  = document.getElementById('mobileOverlay');
    const btn      = document.getElementById('hamburgerBtn');
    if (!sidebar) return;
    sidebar.classList.add('open');
    overlay?.classList.add('visible');
    btn?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
}

function fermerMenuMobile() {
    const sidebar  = document.getElementById('mobileSidebar');
    const overlay  = document.getElementById('mobileOverlay');
    const btn      = document.getElementById('hamburgerBtn');
    if (!sidebar) return;
    sidebar.classList.remove('open');
    overlay?.classList.remove('visible');
    btn?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('hamburgerBtn')?.addEventListener('click', ouvrirMenuMobile);
    document.getElementById('mobileCloseBtn')?.addEventListener('click', fermerMenuMobile);
    document.getElementById('mobileOverlay')?.addEventListener('click', fermerMenuMobile);

    // Fermer si lien mobile sélectionné
    document.querySelectorAll('.mobile-nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) fermerMenuMobile();
        });
    });

    // Fermer au resize si on passe en desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) fermerMenuMobile();
    });
});
