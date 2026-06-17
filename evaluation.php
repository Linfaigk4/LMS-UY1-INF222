<?php
/**
 * GOL (Gugle Online Learning) - Passer une évaluation
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

$id_evaluation = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = obtenirEvaluation($id_evaluation);

if (!$evaluation) {
    header('Location: index.php');
    exit;
}

// Charger les questions avec leurs options et le timer par question
$questions_brutes = obtenirQuestions($id_evaluation);
$questions = [];
foreach ($questions_brutes as $q) {
    $stmt_opt = $pdo->prepare("SELECT id_option, texte_option, est_correcte FROM options WHERE id_question = ?");
    $stmt_opt->execute([$q['id_question']]);
    $q['options_liste'] = $stmt_opt->fetchAll();
    $questions[] = $q;
}
$lecon = obtenirLecon($evaluation['id_lecon']);

// Vérifier si la leçon a été terminée
$progression_data = obtenirProgressionCours($_SESSION['id_utilisateur'], $lecon['id_cours']);
$lecons_terminees = $progression_data ? json_decode($progression_data['lecons_terminees'], true) : [];

if (!in_array($evaluation['id_lecon'], $lecons_terminees)) {
    header('Location: lecon.php?id=' . $evaluation['id_lecon'] . '&error=eval_not_available');
    exit;
}

// Vérifier le nombre de tentatives
$stmt = connexionBDD()->prepare("
    SELECT COUNT(*) as nb_tentatives, MAX(score) as meilleur_score
    FROM resultats_evaluations
    WHERE id_utilisateur = ? AND id_evaluation = ?
");
$stmt->execute([$_SESSION['id_utilisateur'], $id_evaluation]);
$tentatives = $stmt->fetch();

$tentatives_restantes = $evaluation['tentative_max'] - $tentatives['nb_tentatives'];
$deja_reussi = ($tentatives['meilleur_score'] ?? 0) >= $evaluation['note_requise'];

$page_title = $evaluation['titre_evaluation'] . ' - GOL';
?>

<?php include 'includes/header.php'; ?>

<style>
.evaluation-container {
    max-width: 800px;
    margin: 0 auto;
    padding: var(--spacing-6);
}

.evaluation-header {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-6);
    text-align: center;
}

.evaluation-title {
    font-size: 1.75rem;
    margin-bottom: var(--spacing-2);
}

.evaluation-meta {
    display: flex;
    justify-content: center;
    gap: var(--spacing-6);
    margin-top: var(--spacing-4);
    flex-wrap: wrap;
}

.meta-badge {
    padding: var(--spacing-1) var(--spacing-3);
    background: var(--fond-secondaire);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid var(--avertissement);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    color: var(--avertissement);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid var(--succes);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    color: var(--succes);
}

/* Timer */
.timer-container {
    background: linear-gradient(135deg, var(--primaire), var(--accent));
    border-radius: var(--radius-xl);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    text-align: center;
    color: white;
}

.timer-value {
    font-size: 2rem;
    font-weight: 700;
    font-family: monospace;
}

.timer-label {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Progression des questions */
.questions-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--spacing-4);
    flex-wrap: wrap;
    gap: var(--spacing-2);
}

.question-dot {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-full);
    background: var(--fond-secondaire);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-base);
}

.question-dot.answered {
    background: var(--succes);
    color: white;
}

.question-dot.current {
    background: var(--primaire);
    color: white;
    transform: scale(1.1);
}

/* Carte question */
.question-card {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--bordure);
    overflow: hidden;
    margin-bottom: var(--spacing-6);
}

.question-header {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--bordure);
    background: var(--fond-secondaire);
}

.question-number {
    font-size: 0.75rem;
    color: var(--texte-tertiaire);
    margin-bottom: var(--spacing-2);
}

.question-text {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.4;
}

.question-body {
    padding: var(--spacing-6);
}

/* Options QCM */
.options-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.option-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--fond-secondaire);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-base);
    border: 2px solid transparent;
}

.option-item:hover {
    background: var(--carte-hover);
    transform: translateX(4px);
}

.option-item.selected {
    border-color: var(--primaire);
    background: rgba(37, 99, 235, 0.05);
}

.option-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--bordure);
    background: var(--carte);
    display: flex;
    align-items: center;
    justify-content: center;
}

.option-radio.selected::after {
    content: '';
    width: 10px;
    height: 10px;
    background: var(--primaire);
    border-radius: 50%;
}

.option-text {
    flex: 1;
    font-size: 0.875rem;
}

/* Navigation questions */
.questions-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: var(--spacing-6);
}

.btn-nav-question {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-6);
    background: var(--fond-secondaire);
    border: none;
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-nav-question:hover {
    background: var(--carte-hover);
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-8);
    background: var(--succes);
    color: white;
    border: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-base);
    width: 100%;
    justify-content: center;
    margin-top: var(--spacing-6);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(34, 197, 94, 0.3);
}

/* Modal résultat */
.result-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.result-modal.active {
    display: flex;
}

.result-content {
    background: var(--carte);
    border-radius: var(--radius-2xl);
    max-width: 500px;
    width: 90%;
    padding: var(--spacing-8);
    text-align: center;
}

.result-score {
    font-size: 4rem;
    font-weight: 800;
    margin: var(--spacing-4) 0;
}

.result-score.success {
    color: var(--succes);
}

.result-score.fail {
    color: var(--danger);
}

.result-actions {
    display: flex;
    gap: var(--spacing-3);
    margin-top: var(--spacing-6);
    justify-content: center;
}

@media (max-width: 768px) {
    .questions-progress {
        display: none;
    }
    
    .evaluation-title {
        font-size: 1.25rem;
    }
    
    .question-text {
        font-size: 1rem;
    }
}
</style>

<div class="evaluation-container">
    <!-- En-tête -->
    <div class="evaluation-header">
        <h1 class="evaluation-title">📝 <?= htmlspecialchars($evaluation['titre_evaluation']) ?></h1>
        <p><?= htmlspecialchars($evaluation['description'] ?? 'Testez vos connaissances sur cette leçon.') ?></p>
        <div class="evaluation-meta">
            <span class="meta-badge">🎯 Score requis : <?= $evaluation['note_requise'] ?>%</span>
            <span class="meta-badge">❓ <?= count($questions) ?> questions</span>
            <?php if ($evaluation['duree']): ?>
                <span class="meta-badge">⏱️ Durée : <?= $evaluation['duree'] ?> minutes</span>
            <?php endif; ?>
            <span class="meta-badge">🔄 Tentative <?= $tentatives['nb_tentatives'] + 1 ?> / <?= $evaluation['tentative_max'] ?></span>
        </div>
    </div>

    <?php if ($tentatives_restantes <= 0): ?>
        <div class="alert-warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                <strong>Nombre maximum de tentatives atteint</strong><br>
                Vous avez utilisé toutes vos <?= $evaluation['tentative_max'] ?> tentatives pour cette évaluation.
            </div>
        </div>
        <a href="lecon.php?id=<?= $evaluation['id_lecon'] ?>" class="btn-submit" style="background: var(--primaire);">Retour à la leçon</a>
    <?php elseif ($deja_reussi): ?>
        <div class="alert-success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            <div>
                <strong>Évaluation déjà réussie !</strong><br>
                Vous avez déjà obtenu la note requise pour cette évaluation. Félicitations !
            </div>
        </div>
        <a href="lecon.php?id=<?= $evaluation['id_lecon'] ?>" class="btn-submit" style="background: var(--primaire);">Retour à la leçon</a>
    <?php else: ?>
        <!-- Timer -->
        <?php if ($evaluation['duree']): ?>
        <div class="timer-container">
            <div class="timer-label">Temps restant</div>
            <div class="timer-value" id="timer"><?= sprintf('%02d:%02d', floor($evaluation['duree']), $evaluation['duree'] * 60 % 60) ?></div>
        </div>
        <?php endif; ?>

        <!-- Progression -->
        <div class="questions-progress" id="questionsProgress"></div>

        <!-- Formulaire -->
        <form id="evaluationForm">
            <div id="questionsContainer"></div>
            
            <div class="questions-navigation">
                <button type="button" class="btn-nav-question" id="prevQuestion">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Précédent
                </button>
                <span id="questionCounter" style="font-size: 0.875rem; color: var(--texte-tertiaire);">Question 1 / <?= count($questions) ?></span>
                <button type="button" class="btn-nav-question" id="nextQuestion">
                    Suivant
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
            
            <button type="button" class="btn-submit" id="submitEvaluation">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle;margin-right:8px;">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                Soumettre l'évaluation
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Modal résultat -->
<div id="resultModal" class="result-modal">
    <div class="result-content">
    <h2>
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
            stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"
            style="vertical-align:middle;margin-right:8px;">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="8 12 11 15 16 9"></polyline>
        </svg>
        Résultat de l'évaluation
    </h2>
        <div class="result-score" id="resultScore">0%</div>
        <p id="resultMessage"></p>
        <div class="result-actions">
            <a href="lecon.php?id=<?= $evaluation['id_lecon'] ?>" class="btn-submit" style="background: var(--primaire); padding: var(--spacing-2) var(--spacing-6);">Continuer</a>
            <button onclick="location.reload()" class="btn-submit" style="background: var(--carte); color: var(--texte); border: 1px solid var(--bordure);">Réessayer</button>
        </div>
    </div>
</div>

<script>
const questions = <?= json_encode($questions) ?>;
const noteRequise = <?= $evaluation['note_requise'] ?>;
let currentQuestion = 0;
let userAnswers = {};
let timerInterval = null;
let timeRemaining = <?= $evaluation['duree'] ? $evaluation['duree'] * 60 : 0 ?>;

// Initialiser
function init() {
    renderQuestions();
    renderProgressDots();
    updateNavigation();
    
    // Timer global
    if (timeRemaining > 0) {
        startTimer();
    }
    // Timer première question
    if (questions.length > 0) {
        demarrerTimerQuestion(questions[0]);
    }
}

function renderQuestions() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';
    
    questions.forEach((q, index) => {
        const questionDiv = document.createElement('div');
        questionDiv.className = 'question-card';
        questionDiv.id = `question-${index}`;
        questionDiv.style.display = index === currentQuestion ? 'block' : 'none';
        
        const optionsHtml = (q.options_liste && q.options_liste.length) ? q.options_liste.map(opt => `
            <div class="option-item" data-option-id="${opt.id_option}" onclick="selectOption(${q.id_question}, ${opt.id_option})">
                <div class="option-radio" id="radio-${q.id_question}-${opt.id_option}"></div>
                <div class="option-text">${escapeHtml(opt.texte_option)}</div>
            </div>
        `).join('') : '<p style="color:var(--texte-tertiaire);font-size:0.875rem">Aucune option définie pour cette question.</p>';
        
        questionDiv.innerHTML = `
            <div class="question-header">
                <div class="question-number">Question ${index + 1} / ${questions.length}</div>
                <div class="question-text">${escapeHtml(q.texte_question)}</div>
                <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
                    ${q.points ? `<span style="font-size:0.7rem;color:var(--texte-tertiaire);">${q.points} point(s)</span>` : ''}
                    ${q.temps_limite ? `<span id="timer-q-${q.id_question}" style="font-size:0.7rem;font-weight:600;color:var(--avertissement);padding:2px 8px;background:rgba(245,158,11,0.1);border-radius:999px;">&#9201; ${q.temps_limite}s</span>` : ''}
                </div>
            </div>
            <div class="question-body">
                <div class="options-list">
                    ${optionsHtml}
                </div>
            </div>
        `;
        
        container.appendChild(questionDiv);
        
        // Restaurer la réponse si existante
        if (userAnswers[q.id_question]) {
            const optionDiv = questionDiv.querySelector(`[data-option-id="${userAnswers[q.id_question]}"]`);
            if (optionDiv) {
                optionDiv.classList.add('selected');
                const radio = optionDiv.querySelector('.option-radio');
                if (radio) radio.classList.add('selected');
            }
        }
    });
}

function renderProgressDots() {
    const container = document.getElementById('questionsProgress');
    container.innerHTML = '';
    
    questions.forEach((q, index) => {
        const dot = document.createElement('div');
        dot.className = 'question-dot';
        dot.textContent = index + 1;
        if (userAnswers[q.id_question]) dot.classList.add('answered');
        if (index === currentQuestion) dot.classList.add('current');
        dot.onclick = () => goToQuestion(index);
        container.appendChild(dot);
    });
}

function selectOption(questionId, optionId) {
    userAnswers[questionId] = optionId;
    
    // Mettre à jour l'affichage
    document.querySelectorAll(`#question-${currentQuestion} .option-item`).forEach(item => {
        item.classList.remove('selected');
        const radio = item.querySelector('.option-radio');
        if (radio) radio.classList.remove('selected');
    });
    
    const selectedItem = document.querySelector(`#question-${currentQuestion} .option-item[data-option-id="${optionId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('selected');
        const radio = selectedItem.querySelector('.option-radio');
        if (radio) radio.classList.add('selected');
    }
    
    renderProgressDots();
}

// Timer par question
let timerQuestion = null;

function goToQuestion(index) {
    if (index >= 0 && index < questions.length) {
        document.getElementById(`question-${currentQuestion}`).style.display = 'none';
        currentQuestion = index;
        document.getElementById(`question-${currentQuestion}`).style.display = 'block';
        updateNavigation();
        renderProgressDots();
        // Démarrer le timer par question si défini
        demarrerTimerQuestion(questions[index]);
    }
}

function demarrerTimerQuestion(q) {
    // Annuler le timer précédent
    if (timerQuestion) clearInterval(timerQuestion);
    if (!q.temps_limite) return;
    let restant = parseInt(q.temps_limite);
    const el = document.getElementById('timer-q-' + q.id_question);
    if (!el) return;
    timerQuestion = setInterval(() => {
        restant--;
        if (el) el.textContent = '⏱ ' + restant + 's';
        if (restant <= 0) {
            clearInterval(timerQuestion);
            // Question expirée : aucune réponse enregistrée = incorrecte
            if (!userAnswers[q.id_question]) userAnswers[q.id_question] = null;
            // Avancer à la question suivante si possible
            if (currentQuestion < questions.length - 1) {
                goToQuestion(currentQuestion + 1);
            }
        }
    }, 1000);
}

function updateNavigation() {
    const counter = document.getElementById('questionCounter');
    if (counter) {
        counter.textContent = `Question ${currentQuestion + 1} / ${questions.length}`;
    }
}

function nextQuestion() {
    if (currentQuestion < questions.length - 1) {
        goToQuestion(currentQuestion + 1);
    }
}

function prevQuestion() {
    if (currentQuestion > 0) {
        goToQuestion(currentQuestion - 1);
    }
}

function startTimer() {
    const timerElement = document.getElementById('timer');
    if (!timerElement) return;
    
    timerInterval = setInterval(() => {
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            soumettreEvaluation();
        } else {
            timeRemaining--;
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeRemaining <= 60) {
                timerElement.style.color = '#ff6b6b';
            }
        }
    }, 1000);
}

function soumettreEvaluation() {
    if (timerInterval) clearInterval(timerInterval);
    
    // Vérifier que toutes les questions ont été répondues
    const totalQuestions = questions.length;
    const answeredCount = Object.keys(userAnswers).length;
    
    if (answeredCount < totalQuestions) {
        if (!confirm(`Vous avez répondu à ${answeredCount}/${totalQuestions} questions. Voulez-vous vraiment soumettre ?`)) {
            if (timeRemaining > 0) startTimer();
            return;
        }
    }
    
    // Envoyer les réponses
    envoyerRequeteAjax('soumettre_evaluation', 'POST', {
        evaluation_id: <?= $id_evaluation ?>,
        reponses: userAnswers,
        temps_consacre: <?= $evaluation['duree'] ? ($evaluation['duree'] * 60 - timeRemaining) : 0 ?>
    }).then(result => {
        if (result.success) {
            const score = result.score;
            const reussi = score >= noteRequise;
            
            document.getElementById('resultScore').textContent = score + '%';
            document.getElementById('resultScore').className = `result-score ${reussi ? 'success' : 'fail'}`;
            document.getElementById('resultMessage').innerHTML = reussi 
            ? `
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             style="vertical-align:middle;margin-right:6px;">
            <path d="M12 15l-3.5 2 1-4-3-2.5 4-.5L12 6l1.5 4 4 .5-3 2.5 1 4z"/>
        </svg>
        Félicitations ! Vous avez réussi cette évaluation.
      `
    : `
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             style="vertical-align:middle;margin-right:6px;">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
        Score requis : ${noteRequise}%. Vous pouvez réessayer.
        Tentatives restantes : ${result.tentatives_restantes}
      `;
            
            document.getElementById('resultModal').classList.add('active');
        } else {
            afficherNotification(result.message || 'Erreur lors de la soumission', 'danger');
        }
    }).catch(error => {
        afficherNotification('Erreur de communication', 'danger');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Événements
document.getElementById('nextQuestion')?.addEventListener('click', nextQuestion);
document.getElementById('prevQuestion')?.addEventListener('click', prevQuestion);
document.getElementById('submitEvaluation')?.addEventListener('click', soumettreEvaluation);

// Fermer la modal au clic en dehors
document.getElementById('resultModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('active');
    }
});

// Initialiser
init();
</script>

<?php include 'includes/footer.php'; ?>