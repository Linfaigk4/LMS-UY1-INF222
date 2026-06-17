<?php
/**
 * GOL (Gugle Online Learning) - Gestion des quiz par leçon (Enseignant)
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Contrôle d'accès : enseignant ou super admin uniquement
if (!estConnecte() || (!estEnseignant() && !estSuperAdmin())) {
    header('Location: connexion.php');
    exit;
}

$id_lecon  = isset($_GET['lecon_id'])  ? (int)$_GET['lecon_id']  : 0;
$id_cours  = isset($_GET['cours_id'])  ? (int)$_GET['cours_id']  : 0;

// Récupérer la leçon et vérifier la propriété
$lecon = obtenirLecon($id_lecon);
if (!$lecon) {
    header('Location: tableau_bord.php');
    exit;
}

$cours = obtenirCours($lecon['id_cours']);
if (!$cours) {
    header('Location: tableau_bord.php');
    exit;
}

// Vérification propriété (super admin bypass)
if (!estSuperAdmin() && $cours['id_enseignant'] != $_SESSION['id_utilisateur']) {
    header('Location: gestion_lecons.php?cours_id=' . $lecon['id_cours']);
    exit;
}

// Charger l'évaluation existante de cette leçon
$evaluation = obtenirEvaluationComplete($id_lecon);

$page_title = 'Quiz — ' . htmlspecialchars($lecon['titre_lecon']) . ' — GOL';
?>
<?php include 'includes/header.php'; ?>

<style>
/* =====================================================
   GESTION QUIZ — Design system GOL
   ===================================================== */
.quiz-container {
    max-width: 960px;
    margin: 0 auto;
    padding: var(--spacing-8) var(--spacing-6);
}

/* En-tête de page */
.quiz-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-8);
}

.quiz-breadcrumb {
    font-size: 0.8rem;
    color: var(--texte-tertiaire);
    margin-bottom: var(--spacing-2);
}

.quiz-breadcrumb a {
    color: var(--primaire);
    text-decoration: none;
}

.quiz-breadcrumb a:hover { text-decoration: underline; }

.quiz-page-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: var(--spacing-1);
}

.quiz-page-subtitle {
    font-size: 0.875rem;
    color: var(--texte-secondaire);
}

/* Carte principale évaluation */
.card-eval {
    background: var(--carte);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-2xl);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-6);
}

.card-eval-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
}

.card-eval-title {
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}

.card-eval-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}
</style>

<style>
/* Formulaire évaluation */
.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
}

.form-group { margin-bottom: var(--spacing-4); }

.form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--texte-secondaire);
    margin-bottom: var(--spacing-2);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--fond-secondaire);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-lg);
    color: var(--texte);
    font-size: 0.875rem;
    font-family: 'Inter', sans-serif;
    transition: border-color var(--transition-base);
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primaire);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.form-textarea { resize: vertical; min-height: 80px; }

/* Boutons */
.btn-primaire {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-6);
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    color: white;
    border: none;
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-primaire:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37,99,235,0.3);
}

.btn-secondaire {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-6);
    background: var(--fond-secondaire);
    color: var(--texte);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-secondaire:hover { background: var(--carte-hover); }

.btn-danger {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-4);
    background: rgba(239,68,68,0.1);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: var(--radius-lg);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-danger:hover { background: var(--danger); color: white; }

.btn-edit {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-4);
    background: rgba(37,99,235,0.1);
    color: var(--primaire);
    border: 1px solid rgba(37,99,235,0.3);
    border-radius: var(--radius-lg);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-edit:hover { background: var(--primaire); color: white; }
</style>

<style>
/* Carte question */
.question-card {
    background: var(--fond-secondaire);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-4);
    overflow: hidden;
    transition: box-shadow var(--transition-base);
}

.question-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

.question-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-4) var(--spacing-6);
    background: var(--carte);
    border-bottom: 1px solid var(--bordure);
    gap: var(--spacing-3);
    flex-wrap: wrap;
}

.question-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    color: white;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.question-texte {
    flex: 1;
    font-size: 0.925rem;
    font-weight: 500;
    line-height: 1.5;
}

.question-meta {
    display: flex;
    gap: var(--spacing-2);
    flex-wrap: wrap;
}

.badge-meta {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    background: var(--fond-secondaire);
    border: 1px solid var(--bordure);
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--texte-secondaire);
    white-space: nowrap;
}

.badge-meta.timer { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); color: var(--avertissement); }
.badge-meta.points { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: var(--succes); }

/* Options */
.options-list {
    padding: var(--spacing-4) var(--spacing-6);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.option-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--radius-lg);
    background: var(--carte);
    border: 1px solid var(--bordure);
    font-size: 0.875rem;
    transition: all var(--transition-base);
}

.option-item.correcte {
    background: rgba(34,197,94,0.05);
    border-color: rgba(34,197,94,0.4);
    color: var(--succes);
    font-weight: 500;
}

.option-indicateur {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--bordure);
    flex-shrink: 0;
}

.option-item.correcte .option-indicateur {
    border-color: var(--succes);
    background: var(--succes);
}

.option-actions { margin-left: auto; display: flex; gap: var(--spacing-1); }

.btn-icon-sm {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    background: transparent;
    color: var(--texte-tertiaire);
    transition: all var(--transition-base);
}

.btn-icon-sm:hover { background: var(--fond-secondaire); color: var(--danger); }
</style>

<style>
/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active { display: flex; }

.modal-box {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    max-width: 560px;
    width: 94%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,0.2);
}

.modal-box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
}

.modal-box-header h3 { font-size: 1.125rem; font-weight: 600; }

.modal-close-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--fond-secondaire);
    border: none;
    border-radius: var(--radius-full);
    cursor: pointer;
    color: var(--texte-secondaire);
    transition: all var(--transition-base);
}

.modal-close-btn:hover { background: var(--danger); color: white; }

.modal-body { padding: var(--spacing-6); }

.modal-footer {
    padding: var(--spacing-4) var(--spacing-6);
    border-top: 1px solid var(--bordure);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-3);
}

/* Toast notification */
.toast-container {
    position: fixed;
    bottom: var(--spacing-6);
    right: var(--spacing-6);
    z-index: 3000;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.toast {
    padding: var(--spacing-3) var(--spacing-5);
    border-radius: var(--radius-lg);
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: slideInRight 0.3s ease;
    max-width: 320px;
}

.toast.succes { background: var(--succes); color: white; }
.toast.danger  { background: var(--danger);  color: white; }
.toast.info    { background: var(--primaire); color: white; }

@keyframes slideInRight {
    from { transform: translateX(120%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}

/* Vide état */
.empty-state {
    text-align: center;
    padding: var(--spacing-12) var(--spacing-6);
    color: var(--texte-tertiaire);
}

.empty-state svg { margin-bottom: var(--spacing-4); opacity: 0.4; }
.empty-state h4 { font-size: 1rem; margin-bottom: var(--spacing-2); color: var(--texte-secondaire); }

/* Séparateur ajout option */
.add-option-row {
    display: flex;
    gap: var(--spacing-2);
    margin-top: var(--spacing-3);
    align-items: center;
}

.add-option-row .form-input { flex: 1; }

/* Responsive */
@media (max-width: 640px) {
    .form-grid-2 { grid-template-columns: 1fr; }
    .quiz-page-header { flex-direction: column; }
}
</style>

<div class="quiz-container">

    <!-- En-tête -->
    <div class="quiz-page-header">
        <div>
            <div class="quiz-breadcrumb">
                <a href="gestion_lecons.php?cours_id=<?= $lecon['id_cours'] ?>">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    <?= htmlspecialchars($cours['titre_cours']) ?>
                </a>
                &rsaquo; Gestion du quiz
            </div>
            <h1 class="quiz-page-title"><?= htmlspecialchars($lecon['titre_lecon']) ?></h1>
            <p class="quiz-page-subtitle">Gérez l'évaluation et les questions associées à cette leçon.</p>
        </div>
        <button class="btn-primaire" id="btnCreerEval" <?= $evaluation ? 'style="display:none"' : '' ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Créer l'évaluation
        </button>
    </div>

    <!-- ================================================
         SECTION ÉVALUATION
    ================================================ -->
    <div class="card-eval" id="sectionEvaluation">
        <div class="card-eval-header">
            <div class="card-eval-title">
                <div class="card-eval-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                Paramètres de l'évaluation
            </div>
            <?php if ($evaluation): ?>
            <div style="display:flex;gap:var(--spacing-2)">
                <button class="btn-edit" onclick="ouvrirModalEvaluation(true)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                    </svg>
                    Modifier
                </button>
                <button class="btn-danger" onclick="supprimerEvaluation(<?= $evaluation['id_evaluation'] ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    </svg>
                    Supprimer
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($evaluation): ?>
        <!-- Affichage de l'évaluation existante -->
        <div id="evalInfo">
            <div class="form-grid-2" style="gap:var(--spacing-4);padding:0 0 var(--spacing-2)">
                <div>
                    <div class="form-label">Titre</div>
                    <div style="font-weight:600"><?= htmlspecialchars($evaluation['titre_evaluation']) ?></div>
                </div>
                <div>
                    <div class="form-label">Note requise</div>
                    <div style="font-weight:600;color:var(--primaire)"><?= $evaluation['note_requise'] ?>%</div>
                </div>
                <div>
                    <div class="form-label">Tentatives max</div>
                    <div><?= $evaluation['tentative_max'] ?></div>
                </div>
                <div>
                    <div class="form-label">Durée globale</div>
                    <div><?= $evaluation['duree'] ? $evaluation['duree'] . ' minutes' : 'Sans limite' ?></div>
                </div>
            </div>
            <?php if ($evaluation['description']): ?>
            <div style="margin-top:var(--spacing-3);padding-top:var(--spacing-3);border-top:1px solid var(--bordure)">
                <div class="form-label">Description</div>
                <div style="font-size:0.875rem;color:var(--texte-secondaire)"><?= htmlspecialchars($evaluation['description']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <h4>Aucune évaluation</h4>
            <p>Cliquez sur "Créer l'évaluation" pour commencer.</p>
        </div>
        <?php endif; ?>
    </div>


    <!-- ================================================
         SECTION QUESTIONS
    ================================================ -->
    <?php if ($evaluation): ?>
    <div id="sectionQuestions">
        <div class="card-eval-header" style="margin-bottom:var(--spacing-4)">
            <div class="card-eval-title" style="font-size:1rem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                Questions
                <span style="font-weight:400;color:var(--texte-tertiaire);font-size:0.8rem">
                    (<?= count($evaluation['questions']) ?>)
                </span>
            </div>
            <button class="btn-primaire" onclick="ouvrirModalQuestion()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Ajouter une question
            </button>
        </div>

        <div id="listeQuestions">
            <?php if (empty($evaluation['questions'])): ?>
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                <h4>Aucune question</h4>
                <p>Ajoutez votre première question pour ce quiz.</p>
            </div>
            <?php else: ?>
            <?php foreach ($evaluation['questions'] as $idx => $q): ?>
            <div class="question-card" id="question-<?= $q['id_question'] ?>">
                <div class="question-card-header">
                    <div class="question-num"><?= $idx + 1 ?></div>
                    <div class="question-texte"><?= htmlspecialchars($q['texte_question']) ?></div>
                    <div class="question-meta">
                        <span class="badge-meta points">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <?= $q['points'] ?> pt<?= $q['points'] > 1 ? 's' : '' ?>
                        </span>
                        <?php if ($q['temps_limite']): ?>
                        <span class="badge-meta timer">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?= $q['temps_limite'] ?>s
                        </span>
                        <?php endif; ?>
                        <button class="btn-edit" onclick='ouvrirModalQuestion(<?= htmlspecialchars(json_encode($q), ENT_QUOTES) ?>)'>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                            Modifier
                        </button>
                        <button class="btn-danger" onclick="supprimerQuestion(<?= $q['id_question'] ?>)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                            </svg>
                            Supprimer
                        </button>
                    </div>
                </div>
                <div class="options-list">
                    <?php foreach ($q['options'] as $opt): ?>
                    <div class="option-item <?= $opt['est_correcte'] ? 'correcte' : '' ?>" id="opt-<?= $opt['id_option'] ?>">
                        <div class="option-indicateur"></div>
                        <span style="flex:1"><?= htmlspecialchars($opt['texte_option']) ?></span>
                        <?php if ($opt['est_correcte']): ?>
                        <span style="font-size:0.7rem;color:var(--succes);font-weight:600">Bonne réponse</span>
                        <?php endif; ?>
                        <div class="option-actions">
                            <button class="btn-icon-sm" title="Supprimer" onclick="supprimerOption(<?= $opt['id_option'] ?>, <?= $q['id_question'] ?>)">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <!-- Ajout rapide d'option -->
                    <div class="add-option-row">
                        <input type="text" class="form-input" id="newOpt-<?= $q['id_question'] ?>" placeholder="Nouvelle option..." style="font-size:0.8rem">
                        <label style="display:flex;align-items:center;gap:4px;font-size:0.75rem;white-space:nowrap;cursor:pointer">
                            <input type="checkbox" id="newOptCorrecte-<?= $q['id_question'] ?>"> Bonne réponse
                        </label>
                        <button class="btn-primaire" style="padding:var(--spacing-2) var(--spacing-4);font-size:0.75rem"
                                onclick="ajouterOptionRapide(<?= $q['id_question'] ?>)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Ajouter
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- fin .quiz-container -->

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>


<!-- ================================================
     MODAL — ÉVALUATION
================================================ -->
<div class="modal-overlay" id="modalEvaluation">
    <div class="modal-box">
        <div class="modal-box-header">
            <h3 id="modalEvalTitre">Créer l'évaluation</h3>
            <button class="modal-close-btn" onclick="fermerModal('modalEvaluation')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="evalId" value="">
            <div class="form-group">
                <label class="form-label">Titre de l'évaluation *</label>
                <input type="text" class="form-input" id="evalTitre" placeholder="Ex : Quiz de fin de leçon">
            </div>
            <div class="form-group">
                <label class="form-label">Description (optionnel)</label>
                <textarea class="form-textarea" id="evalDescription" placeholder="Instructions pour les étudiants..."></textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Note requise (%)</label>
                    <input type="number" class="form-input" id="evalNoteRequise" value="60" min="0" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Tentatives max</label>
                    <input type="number" class="form-input" id="evalTentatives" value="3" min="1" max="10">
                </div>
                <div class="form-group">
                    <label class="form-label">Durée globale (min)</label>
                    <input type="number" class="form-input" id="evalDuree" placeholder="Vide = sans limite" min="1">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondaire" onclick="fermerModal('modalEvaluation')">Annuler</button>
            <button class="btn-primaire" onclick="sauvegarderEvaluation()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- ================================================
     MODAL — QUESTION
================================================ -->
<div class="modal-overlay" id="modalQuestion">
    <div class="modal-box">
        <div class="modal-box-header">
            <h3 id="modalQTitre">Ajouter une question</h3>
            <button class="modal-close-btn" onclick="fermerModal('modalQuestion')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="questionId" value="">
            <div class="form-group">
                <label class="form-label">Texte de la question *</label>
                <textarea class="form-textarea" id="questionTexte" placeholder="Formulez votre question..."></textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Points</label>
                    <input type="number" class="form-input" id="questionPoints" value="1" min="1" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Timer par question</label>
                    <select class="form-select" id="questionTimer">
                        <option value="">Sans timer</option>
                        <option value="30">30 secondes</option>
                        <option value="45">45 secondes</option>
                        <option value="60">60 secondes</option>
                        <option value="90">90 secondes</option>
                        <option value="120">120 secondes</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondaire" onclick="fermerModal('modalQuestion')">Annuler</button>
            <button class="btn-primaire" onclick="sauvegarderQuestion()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Enregistrer
            </button>
        </div>
    </div>
</div>


<script>
/* =====================================================
   GOL — gestion_quiz.php — JavaScript
   Auteur : ESSENGUE BILOA VICTORIEN MICHEL
===================================================== */

// Données PHP → JS
const ID_LECON = <?= (int)$id_lecon ?>;
const ID_EVAL  = <?= $evaluation ? (int)$evaluation['id_evaluation'] : 'null' ?>;

// ----- UTILITAIRES -----

function toast(msg, type = 'info') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.4s'; setTimeout(() => t.remove(), 400); }, 3500);
}

function ajax(action, data = {}) {
    return fetch('ajax.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(data)
    }).then(r => r.json());
}

function ouvrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function fermerModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Fermer en cliquant sur l'overlay
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) fermerModal(m.id); });
});

// ----- ÉVALUATION -----

function ouvrirModalEvaluation(modeEdit = false) {
    document.getElementById('modalEvalTitre').textContent = modeEdit ? 'Modifier l\'évaluation' : 'Créer l\'évaluation';
    if (modeEdit && ID_EVAL) {
        document.getElementById('evalId').value          = '<?= $evaluation ? $evaluation['id_evaluation'] : '' ?>';
        document.getElementById('evalTitre').value       = '<?= addslashes($evaluation ? $evaluation['titre_evaluation'] : '') ?>';
        document.getElementById('evalDescription').value = '<?= addslashes($evaluation ? ($evaluation['description'] ?? '') : '') ?>';
        document.getElementById('evalNoteRequise').value = '<?= $evaluation ? $evaluation['note_requise'] : 60 ?>';
        document.getElementById('evalTentatives').value  = '<?= $evaluation ? $evaluation['tentative_max'] : 3 ?>';
        document.getElementById('evalDuree').value       = '<?= $evaluation ? ($evaluation['duree'] ?? '') : '' ?>';
    } else {
        document.getElementById('evalId').value          = '';
        document.getElementById('evalTitre').value       = '';
        document.getElementById('evalDescription').value = '';
        document.getElementById('evalNoteRequise').value = '60';
        document.getElementById('evalTentatives').value  = '3';
        document.getElementById('evalDuree').value       = '';
    }
    ouvrirModal('modalEvaluation');
}

// Bouton "Créer l'évaluation" de l'en-tête
document.getElementById('btnCreerEval')?.addEventListener('click', () => ouvrirModalEvaluation(false));

function sauvegarderEvaluation() {
    const id    = document.getElementById('evalId').value;
    const titre = document.getElementById('evalTitre').value.trim();
    if (!titre) { toast('Le titre est obligatoire', 'danger'); return; }

    const donnees = {
        id_lecon:      ID_LECON,
        titre:         titre,
        description:   document.getElementById('evalDescription').value.trim(),
        note_requise:  document.getElementById('evalNoteRequise').value,
        tentative_max: document.getElementById('evalTentatives').value,
        duree:         document.getElementById('evalDuree').value || ''
    };

    const action = id ? 'modifier_evaluation' : 'ajouter_evaluation';
    if (id) donnees.id_evaluation = id;

    ajax(action, donnees).then(r => {
        if (r.success) {
            toast(r.message, 'succes');
            fermerModal('modalEvaluation');
            setTimeout(() => location.reload(), 800);
        } else {
            toast(r.message || 'Erreur', 'danger');
        }
    }).catch(() => toast('Erreur réseau', 'danger'));
}

function supprimerEvaluation(id) {
    if (!confirm('Supprimer cette évaluation et toutes ses questions ?')) return;
    ajax('supprimer_evaluation', { id_evaluation: id }).then(r => {
        if (r.success) { toast('Évaluation supprimée', 'succes'); setTimeout(() => location.reload(), 800); }
        else toast(r.message || 'Erreur', 'danger');
    });
}

// ----- QUESTIONS -----

function ouvrirModalQuestion(q = null) {
    document.getElementById('modalQTitre').textContent = q ? 'Modifier la question' : 'Ajouter une question';
    document.getElementById('questionId').value     = q ? q.id_question : '';
    document.getElementById('questionTexte').value  = q ? q.texte_question : '';
    document.getElementById('questionPoints').value = q ? q.points : 1;
    document.getElementById('questionTimer').value  = q && q.temps_limite ? q.temps_limite : '';
    ouvrirModal('modalQuestion');
}

function sauvegarderQuestion() {
    const id    = document.getElementById('questionId').value;
    const texte = document.getElementById('questionTexte').value.trim();
    if (!texte) { toast('Le texte est obligatoire', 'danger'); return; }
    if (!ID_EVAL) { toast('Créez d\'abord l\'évaluation', 'danger'); return; }

    const donnees = {
        id_evaluation:  ID_EVAL,
        texte_question: texte,
        points:         document.getElementById('questionPoints').value,
        temps_limite:   document.getElementById('questionTimer').value || ''
    };
    const action = id ? 'modifier_question' : 'ajouter_question';
    if (id) donnees.id_question = id;

    ajax(action, donnees).then(r => {
        if (r.success) {
            toast(r.message, 'succes');
            fermerModal('modalQuestion');
            setTimeout(() => location.reload(), 800);
        } else {
            toast(r.message || 'Erreur', 'danger');
        }
    }).catch(() => toast('Erreur réseau', 'danger'));
}

function supprimerQuestion(id) {
    if (!confirm('Supprimer cette question et ses options ?')) return;
    ajax('supprimer_question', { id_question: id }).then(r => {
        if (r.success) {
            document.getElementById('question-' + id)?.remove();
            toast('Question supprimée', 'succes');
        } else {
            toast(r.message || 'Erreur', 'danger');
        }
    });
}

// ----- OPTIONS -----

function ajouterOptionRapide(idQuestion) {
    const input     = document.getElementById('newOpt-' + idQuestion);
    const chkCorr   = document.getElementById('newOptCorrecte-' + idQuestion);
    const texte     = input.value.trim();
    if (!texte) { toast('Saisissez le texte de l\'option', 'danger'); return; }

    ajax('ajouter_option', {
        id_question:  idQuestion,
        texte_option: texte,
        est_correcte: chkCorr.checked ? '1' : '0'
    }).then(r => {
        if (r.success) {
            toast('Option ajoutée', 'succes');
            input.value = '';
            chkCorr.checked = false;
            setTimeout(() => location.reload(), 600);
        } else {
            toast(r.message || 'Erreur', 'danger');
        }
    });
}

function supprimerOption(idOption, idQuestion) {
    if (!confirm('Supprimer cette option ?')) return;
    ajax('supprimer_option', { id_option: idOption }).then(r => {
        if (r.success) {
            document.getElementById('opt-' + idOption)?.remove();
            toast('Option supprimée', 'succes');
        } else {
            toast(r.message || 'Erreur', 'danger');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
