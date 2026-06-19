<?php
/**
 * GOL (Gugle Online Learning) - Certificats de l'utilisateur
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté
if (!estConnecte() || !estEtudiant()) {
    header('Location: connexion.php');
    exit;
}

$utilisateur = obtenirUtilisateur();

// Récupérer les certificats de l'utilisateur
$stmt = connexionBDD()->prepare("
    SELECT c.*, m.nom_module, m.id_module
    FROM certificats c
    JOIN modules m ON c.id_module = m.id_module
    WHERE c.id_utilisateur = ? AND c.statut = 'valide'
    ORDER BY c.date_emission DESC
");
$stmt->execute([$_SESSION['id_utilisateur']]);
$certificats = $stmt->fetchAll();

// Récupérer les modules complétés sans certificat
$stmt = connexionBDD()->prepare("
    SELECT m.id_module, m.nom_module, im.progression_globale
    FROM inscriptions_modules im
    JOIN modules m ON im.id_module = m.id_module
    WHERE im.id_utilisateur = ? 
    AND im.progression_globale >= 100
    AND NOT EXISTS (
        SELECT 1 FROM certificats c WHERE c.id_utilisateur = im.id_utilisateur AND c.id_module = im.id_module
    )
");
$stmt->execute([$_SESSION['id_utilisateur']]);
$modules_sans_certificat = $stmt->fetchAll();

$page_title = 'Mes certificats - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
.certificats-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-8) var(--spacing-6);
}

.certificats-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
}

.certificats-header h1 {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-4);
}

.certificats-header p {
    color: var(--texte-secondaire);
    max-width: 600px;
    margin: 0 auto;
}

/* Grille des certificats */
.certificats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-8);
    margin-bottom: var(--spacing-12);
}

/* Carte certificat */
.certificat-card {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
    transition: all var(--transition-base);
    cursor: pointer;
    position: relative;
}

.certificat-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--ombre-xl);
}

.certificat-preview {
    background: linear-gradient(135deg, #fff8e7, #fff);
    padding: var(--spacing-6);
    text-align: center;
    border-bottom: 1px solid var(--bordure);
}

[data-theme="dark"] .certificat-preview {
    background: linear-gradient(135deg, #1e1e2e, #2a2a3e);
}

.certificat-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-4);
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.certificat-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primaire-sombre);
    margin-bottom: var(--spacing-2);
}

.certificat-subtitle {
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.certificat-info {
    padding: var(--spacing-6);
}

.certificat-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: var(--spacing-2);
}

.certificat-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-4);
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.certificat-code {
    font-family: monospace;
    background: var(--fond-secondaire);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-md);
    font-size: 0.7rem;
}

.certificat-actions {
    display: flex;
    gap: var(--spacing-2);
    margin-top: var(--spacing-4);
}

.btn-certificat {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-4);
    background: var(--fond-secondaire);
    border: none;
    border-radius: var(--radius-lg);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
    color: var(--texte);
}

.btn-certificat:hover {
    background: var(--primaire);
    color: white;
}

.btn-certificat-primary {
    background: var(--primaire);
    color: white;
}

.btn-certificat-primary:hover {
    background: var(--primaire-sombre);
    transform: translateY(-2px);
}

/* Section modules sans certificat */
.modules-pending {
    background: var(--fond-secondaire);
    border-radius: var(--radius-2xl);
    padding: var(--spacing-8);
    margin-top: var(--spacing-8);
}

.modules-pending h2 {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-4);
}

.pending-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.pending-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4);
    background: var(--carte);
    border-radius: var(--radius-lg);
    flex-wrap: wrap;
    gap: var(--spacing-3);
}

.pending-item h3 {
    font-size: 1rem;
    margin-bottom: var(--spacing-1);
}

.pending-item p {
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
}

.btn-generer {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-6);
    background: var(--primaire);
    color: white;
    border: none;
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-generer:hover {
    transform: translateY(-2px);
    box-shadow: var(--ombre-glow);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-12);
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
}

.empty-state svg {
    margin-bottom: var(--spacing-4);
    opacity: 0.5;
}

/* Modal certificat */
.certificat-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(8px);
    z-index: 1100;
    align-items: center;
    justify-content: center;
}

.certificat-modal.active {
    display: flex;
}

.certificat-modal-content {
    background: white;
    border-radius: var(--radius-2xl);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-close-cert {
    position: absolute;
    top: var(--spacing-4);
    right: var(--spacing-4);
    background: rgba(0,0,0,0.5);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-full);
    color: white;
    cursor: pointer;
    z-index: 10;
    font-size: 1.2rem;
}

.modal-close-cert:hover {
    background: rgba(0,0,0,0.7);
}

@media print {
    body * {
        visibility: hidden;
    }
    .certificat-print, .certificat-print * {
        visibility: visible;
    }
    .certificat-print {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
    }
    .no-print {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .certificats-grid {
        grid-template-columns: 1fr;
    }
    .pending-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="certificats-container">
    <div class="certificats-header">
        <h1><?= icone('certificat', 22) ?> Mes certificats</h1>
        <p>Retrouvez ici tous les certificats que vous avez obtenus sur GOL. Chaque certificat est unique et vérifiable.</p>
    </div>

    <?php if (!empty($modules_sans_certificat)): ?>
    <div class="modules-pending">
        <h2><?= icone('cours', 18) ?> Certificats disponibles à générer</h2>
        <p style="margin-bottom: var(--spacing-4);">Félicitations ! Vous avez complété ces modules. Générez votre certificat dès maintenant.</p>
        <div class="pending-list">
            <?php foreach ($modules_sans_certificat as $module): ?>
                <div class="pending-item">
                    <div>
                        <h3><?= htmlspecialchars($module['nom_module']) ?></h3>
                        <p>Module complété à 100%</p>
                    </div>
                    <button class="btn-generer" onclick="genererCertificat(<?= $module['id_module'] ?>)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        Générer mon certificat
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($certificats) && empty($modules_sans_certificat)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h3>Aucun certificat pour le moment</h3>
            <p>Complétez des modules à 100% pour obtenir vos premiers certificats.</p>
            <a href="index.php#modules" class="btn-generer" style="margin-top: var(--spacing-4); display: inline-flex;">Découvrir les modules</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($certificats)): ?>
    <div class="certificats-grid">
        <?php foreach ($certificats as $certificat): ?>
            <div class="certificat-card" onclick="voirCertificat(<?= $certificat['id_certificat'] ?>)">
                <div class="certificat-preview">
                    <div class="certificat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                    <div class="certificat-title">CERTIFICAT DE RÉUSSITE</div>
                    <div class="certificat-subtitle">GOL - Gugle Online Learning</div>
                </div>
                <div class="certificat-info">
                    <div class="certificat-name"><?= htmlspecialchars($certificat['nom_module']) ?></div>
                    <div class="certificat-meta">
                        <span>Délivré le <?= date('d/m/Y', strtotime($certificat['date_emission'])) ?></span>
                        <span class="certificat-code">#<?= $certificat['code_unique'] ?></span>
                    </div>
                    <div class="certificat-actions">
                        <button class="btn-certificat" onclick="event.stopPropagation(); imprimerCertificatDirect(<?= $certificat['id_certificat'] ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <path d="M6 14h12v8H6z"/>
                            </svg>
                            Imprimer
                        </button>
                        <button class="btn-certificat btn-certificat-primary" onclick="event.stopPropagation(); voirCertificat(<?= $certificat['id_certificat'] ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                            Voir détails
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal certificat -->
<div id="certificatModal" class="certificat-modal">
    <div class="certificat-modal-content">
        <button class="modal-close-cert" onclick="fermerModalCertificat()">✕</button>
        <div id="certificatPrintContent"></div>
        <div style="padding: var(--spacing-4); text-align: center;" class="no-print">
            <button onclick="imprimerCertificatCourant()" class="btn-generer" style="margin-right: var(--spacing-4);">
                <?= icone('imprimer', 16) ?> Imprimer
            </button>
            <button onclick="fermerModalCertificat()" class="btn-generer" style="background: var(--carte); color: var(--texte); border: 1px solid var(--bordure);">
                Fermer
            </button>
        </div>
    </div>
</div>

<script>
let currentCertificatData = null;

function genererCertificat(idModule) {
    if (!confirm('Voulez-vous générer votre certificat pour ce module ?')) return;
    
    fetch('ajax.php?action=generer_certificat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id_module: idModule })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            alert('Certificat généré avec succès !');
            location.reload();
        } else {
            alert(result.message || 'Erreur lors de la génération');
        }
    })
    .catch(error => {
        alert('Erreur de communication');
    });
}

function voirCertificat(idCertificat) {
    fetch('ajax.php?action=obtenir_certificat&id=' + idCertificat, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            currentCertificatData = result.data;
            afficherModalCertificat(result.data);
        } else {
            alert('Erreur lors du chargement du certificat');
        }
    });
}

function afficherModalCertificat(certificat) {
    const modal = document.getElementById('certificatModal');
    const content = document.getElementById('certificatPrintContent');
    
    content.innerHTML = `
        <div class="certificat-print" style="padding: 40px; background: white; font-family: 'Times New Roman', Times, serif;">
            <div style="border: 8px double #2563eb; padding: 30px; text-align: center;">
                <div style="margin-bottom: 20px;">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                
                <h1 style="font-size: 2rem; margin-bottom: 5px; color: #2563eb; letter-spacing: 2px;">GOL</h1>
                <p style="font-size: 0.8rem; margin-bottom: 25px; color: #666;">Gugle Online Learning</p>
                
                <div style="border-top: 1px solid #ddd; width: 100px; margin: 0 auto 20px;"></div>
                
                <h2 style="font-size: 1.3rem; margin-bottom: 15px; color: #333;">CERTIFICAT DE RÉUSSITE</h2>
                
                <p style="font-size: 1rem; margin-bottom: 10px; color: #555;">Ce certificat est décerné à</p>
                <h3 style="font-size: 1.8rem; margin-bottom: 15px; color: #2563eb;">${escapeHtml(certificat.prenom)} ${escapeHtml(certificat.nom)}</h3>
                
                <p style="font-size: 1rem; margin-bottom: 10px; color: #555;">Pour avoir complété avec succès le module</p>
                <h3 style="font-size: 1.4rem; margin-bottom: 25px; color: #1e40af;">« ${escapeHtml(certificat.nom_module)} »</h3>
                
                <div style="margin: 25px 0;">
                    <div style="border-top: 1px solid #ddd; width: 150px; margin: 0 auto;"></div>
                    <p style="margin-top: 10px; font-size: 0.8rem; color: #666;">Date d'émission: ${new Date(certificat.date_emission).toLocaleDateString('fr-FR')}</p>
                    <p style="font-family: monospace; font-size: 0.7rem; background: #f0f0f0; padding: 5px 10px; border-radius: 5px; display: inline-block;">Code: ${certificat.code_unique}</p>
                </div>
                
                <div style="margin-top: 40px; display: flex; justify-content: space-between; text-align: center;">
                    <div style="flex: 1;">
                        <div style="border-top: 1px solid #333; width: 180px; margin: 0 auto 8px;"></div>
                        <p style="font-size: 0.7rem; color: #666;">Signature de l'apprenant</p>
                    </div>
                    <div style="flex: 1;">
                        <div style="border-top: 1px solid #333; width: 180px; margin: 0 auto 8px;"></div>
                        <p style="font-size: 0.7rem; color: #666;">Le Directeur Pédagogique</p>
                    </div>
                </div>
                
                <div style="margin-top: 30px; font-size: 0.65rem; color: #999;">
                    <p>Certificat vérifiable en ligne sur https://gol.com/verify/${certificat.code_unique}</p>
                    <p>© GOL - Gugle Online Learning - ${new Date().getFullYear()}</p>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
}

function fermerModalCertificat() {
    document.getElementById('certificatModal').classList.remove('active');
    currentCertificatData = null;
}

function imprimerCertificatDirect(idCertificat) {
    fetch('ajax.php?action=obtenir_certificat&id=' + idCertificat, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            imprimerCertificatData(result.data);
        } else {
            alert('Erreur lors du chargement du certificat');
        }
    });
}

function imprimerCertificatCourant() {
    if (currentCertificatData) {
        imprimerCertificatData(currentCertificatData);
    }
}

function imprimerCertificatData(certificat) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Certificat GOL - ${escapeHtml(certificat.nom_module)}</title>
            <meta charset="UTF-8">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    margin: 0;
                    padding: 40px;
                    font-family: 'Times New Roman', Times, serif;
                    background: white;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .certificat-container {
                    max-width: 900px;
                    margin: 0 auto;
                }
                .certificat {
                    background: white;
                    border: 8px double #2563eb;
                    padding: 40px;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                }
                h1 {
                    font-size: 2rem;
                    margin-bottom: 5px;
                    color: #2563eb;
                    letter-spacing: 2px;
                }
                .subtitle {
                    font-size: 0.8rem;
                    color: #666;
                    margin-bottom: 25px;
                }
                hr {
                    width: 100px;
                    margin: 0 auto 20px;
                    border: none;
                    border-top: 1px solid #ddd;
                }
                h2 {
                    font-size: 1.3rem;
                    margin-bottom: 15px;
                    color: #333;
                }
                .recipient {
                    font-size: 1.8rem;
                    font-weight: bold;
                    margin-bottom: 15px;
                    color: #2563eb;
                }
                .module-name {
                    font-size: 1.4rem;
                    font-weight: 600;
                    margin-bottom: 25px;
                    color: #1e40af;
                }
                .date-text {
                    margin-top: 10px;
                    font-size: 0.8rem;
                    color: #666;
                }
                .code {
                    font-family: monospace;
                    font-size: 0.7rem;
                    background: #f0f0f0;
                    padding: 5px 10px;
                    border-radius: 5px;
                    display: inline-block;
                    margin-top: 10px;
                }
                .signatures {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                    text-align: center;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    width: 180px;
                    margin: 0 auto 8px;
                }
                .signature-label {
                    font-size: 0.7rem;
                    color: #666;
                }
                .footer {
                    margin-top: 30px;
                    font-size: 0.65rem;
                    color: #999;
                }
                @media print {
                    body {
                        background: white;
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="certificat-container">
                <div class="certificat">
                    <h1>GOL</h1>
                    <div class="subtitle">Gugle Online Learning</div>
                    <hr>
                    <h2>CERTIFICAT DE RÉUSSITE</h2>
                    <p>Ce certificat est décerné à</p>
                    <div class="recipient">${escapeHtml(certificat.prenom)} ${escapeHtml(certificat.nom)}</div>
                    <p>Pour avoir complété avec succès le module</p>
                    <div class="module-name">« ${escapeHtml(certificat.nom_module)} »</div>
                    <div class="date-text">Date d'émission: ${new Date(certificat.date_emission).toLocaleDateString('fr-FR')}</div>
                    <div class="code">Code: ${certificat.code_unique}</div>
                    <div class="signatures">
                        <div>
                            <div class="signature-line"></div>
                            <div class="signature-label">Signature de l'apprenant</div>
                        </div>
                        <div>
                            <div class="signature-line"></div>
                            <div class="signature-label">Le Directeur Pédagogique</div>
                        </div>
                    </div>
                    <div class="footer">
                        <p>© GOL - Gugle Online Learning - ${new Date().getFullYear()}</p>
                    </div>
                </div>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// escapeHtml est défini dans assets/js/app.js

// Fermer la modal en cliquant en dehors
document.getElementById('certificatModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fermerModalCertificat();
    }
});
</script>

<?php include 'includes/footer.php'; ?>