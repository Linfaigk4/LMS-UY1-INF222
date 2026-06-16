# PLAN DE REFACTORISATION CONTRÔLÉE V2 — GOL LMS
**Version** : 2.0 — Opérationnel  
**Basé sur** : AUDIT_SQL.md + ETAT_ACTUEL_HEADER.md  
**Règle absolue** : Ne modifier aucun fichier fonctionnel sans commit préalable.

---

## LÉGENDE DES RISQUES
- 🟢 FAIBLE — Nouveau fichier ou modification isolée
- 🟡 MOYEN — Modification d'un fichier existant utilisé par d'autres
- 🔴 ÉLEVÉ — Modification d'un fichier central (fonctions.php, ajax.php, header.php)

---

## PHASE 1 — Suppression table enseignants fantôme + correction inscription

**Durée estimée : 1h**  
**Risque : 🟢 FAIBLE**

### 1.1 — Supprimer la référence à la table `enseignants` inexistante

**Problème** : `inscription_enseignant.php` ligne 70 tente un INSERT dans une table qui n'existe pas → crash SQL fatal.

**Fichiers impactés**
- `inscription_enseignant.php` (modification)
- `database.sql` (migration SQL)

**Requête SQL de migration**
```sql
-- Ajouter la colonne specialite dans utilisateurs (absente du schéma)
ALTER TABLE utilisateurs 
ADD COLUMN specialite VARCHAR(255) DEFAULT NULL 
AFTER bio;
```

**Fonctions PHP à modifier**
- Dans `inscription_enseignant.php` : supprimer les lignes 69-71 :
```php
// SUPPRIMER CES LIGNES :
// Ajouter à la table enseignants
$stmt = $pdo->prepare("INSERT INTO enseignants (id_utilisateur, specialite, biographie, statut) VALUES (?, ?, ?, 'en_attente')");
$stmt->execute([$resultat['id'], $specialite, $biographie]);
```
- Remplacer par le stockage dans `utilisateurs.specialite` :
```php
// REMPLACER PAR :
if (!empty($specialite)) {
    $updates[] = "specialite = ?";
    $params[] = $specialite;
}
```

**Endpoints AJAX concernés** : Aucun  

**Risques de régression**
- Aucun — la table n'existe pas, le crash est garanti sans cette correction

**Tests après correction**
1. Accéder à `inscription_enseignant.php`
2. Soumettre le formulaire avec des données valides
3. Vérifier : pas d'erreur SQL, redirection vers `connexion.php`
4. Vérifier en base : `SELECT specialite FROM utilisateurs ORDER BY id_utilisateur DESC LIMIT 1`
5. Se connecter avec le compte enseignant créé → tableau de bord accessible

**Commit** : `git commit -m "Phase 1 — Correction inscription_enseignant.php, suppression table enseignants fantôme"`

---

## PHASE 2 — Création gestion_quiz.php complet

**Durée estimée : 4h**  
**Risque : 🟢 FAIBLE (nouveau fichier + extensions)**

### 2.1 — Migration SQL (timer par question)

**Requêtes SQL de migration**
```sql
-- Ajouter le timer par question
ALTER TABLE questions 
ADD COLUMN temps_limite INT DEFAULT NULL 
COMMENT 'Durée en secondes : 30, 45, 60, 90, 120. NULL = pas de timer individuel'
AFTER ordre_affichage;
```

### 2.2 — Fonctions PHP à ajouter dans `includes/fonctions.php`

```php
// --- CRUD ÉVALUATIONS ---
function ajouterEvaluation($titre, $description, $note_requise, $duree, $id_lecon, $tentative_max = 3)
function modifierEvaluation($id_evaluation, $titre, $description, $note_requise, $duree, $tentative_max)
function supprimerEvaluation($id_evaluation)
function obtenirEvaluationParLecon($id_lecon) // retourne UNE évaluation (1 par leçon)

// --- CRUD QUESTIONS ---
function ajouterQuestion($texte, $points, $temps_limite, $id_evaluation, $ordre = 0)
function modifierQuestion($id_question, $texte, $points, $temps_limite)
function supprimerQuestion($id_question)
function obtenirQuestionsAvecOptions($id_evaluation) // joint avec options

// --- CRUD OPTIONS ---
function ajouterOption($texte, $est_correcte, $id_question)
function modifierOption($id_option, $texte, $est_correcte)
function supprimerOption($id_option)
function definirBonneReponse($id_question, $id_option) // met est_correcte=0 sur toutes, puis =1 sur celle-ci
```

### 2.3 — Endpoints AJAX à créer dans `ajax.php`

```php
case 'ajouter_evaluation':      // POST — estEnseignant() requis
case 'modifier_evaluation':     // POST — estEnseignant() + propriétaire
case 'supprimer_evaluation':    // POST — estEnseignant() + propriétaire
case 'ajouter_question':        // POST — estEnseignant() requis
case 'modifier_question':       // POST — estEnseignant() requis
case 'supprimer_question':      // POST — estEnseignant() requis
case 'ajouter_option':          // POST — estEnseignant() requis
case 'modifier_option':         // POST — estEnseignant() requis
case 'supprimer_option':        // POST — estEnseignant() requis
case 'definir_bonne_reponse':   // POST — estEnseignant() requis
```

### 2.4 — Fichier `gestion_quiz.php` à créer

**Structure de la page** :
- Inclut `header.php` et `footer.php`
- Paramètre : `?lecon_id=X&cours_id=Y`
- Vérifie : `estEnseignant()` + que le cours appartient à l'enseignant
- Section 1 : Formulaire création/modification de l'évaluation
- Section 2 : Liste des questions avec boutons CRUD
- Section 3 : Modal ajout/modification question avec options et timer
- Timer : `<select>` avec valeurs 30, 45, 60, 90, 120 secondes + option "Aucun"

**Risques de régression**
- Aucun (nouveau fichier)
- Le lien `gestion_lecons.php → gestion_quiz.php` était déjà présent → se résout automatiquement

**Tests après correction**
1. Accéder à `gestion_lecons.php?cours_id=1` en tant qu'enseignant
2. Cliquer "Gérer le quiz" → doit ouvrir `gestion_quiz.php` sans 404
3. Créer une évaluation avec titre et note requise → vérifier en base
4. Ajouter 3 questions avec options et bonne réponse → vérifier en base
5. Tester le timer : ajouter une question avec `temps_limite = 60`
6. Tester la suppression d'une option et d'une question
7. `SELECT * FROM questions WHERE temps_limite IS NOT NULL LIMIT 5`

**Commit** : `git commit -m "Phase 2 — Création gestion_quiz.php, CRUD questions/options, timer par question"`

---

## PHASE 3 — Correction progression + synchronisation progression_globale

**Durée estimée : 3h**  
**Risque : 🔴 ÉLEVÉ (modification fonctions.php + nouvelle table)**

### 3.1 — Migration SQL

```sql
-- Nouvelle table de progression par leçon
CREATE TABLE progression_lecons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_lecon INT NOT NULL,
    statut TINYINT DEFAULT 0 COMMENT '0=non commencé, 50=ouvert, 100=quiz réussi',
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_completion DATETIME,
    UNIQUE KEY unique_prog (id_utilisateur, id_lecon),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_lecon) REFERENCES lecons(id_lecon) ON DELETE CASCADE,
    INDEX idx_utilisateur (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Corriger la table ajax doublon inscrire_module (endpoint manquant)
-- Aucune migration SQL, uniquement code PHP
```

### 3.2 — Fonctions PHP à modifier/créer dans `includes/fonctions.php`

**Nouvelle formule officielle** :
```
Leçon non commencée = 0
Leçon ouverte       = 50
Quiz réussi         = 100
Progression cours   = moyenne(statuts de toutes les leçons du cours)
Progression module  = moyenne(progressions de tous les cours publiés)
```

**Fonctions à créer** :
```php
// Ouvrir une leçon (statut 50) — appelé quand l'étudiant clique sur la leçon
function ouvrirLecon($id_utilisateur, $id_lecon)
// INSERT INTO progression_lecons ... ON DUPLICATE KEY UPDATE statut = IF(statut < 50, 50, statut)

// Marquer quiz réussi (statut 100) — appelé après soumettreEvaluation réussie
function validerLeconApresQuiz($id_utilisateur, $id_lecon)
// INSERT INTO progression_lecons ... ON DUPLICATE KEY UPDATE statut = 100, date_completion = NOW()

// Calculer progression cours (0-100)
function calculerProgressionCours($id_utilisateur, $id_cours)
// SELECT AVG(pl.statut) FROM progression_lecons pl
// JOIN lecons l ON pl.id_lecon = l.id_lecon
// WHERE l.id_cours = ? AND pl.id_utilisateur = ?

// Calculer et synchroniser progression module
function synchroniserProgressionModule($id_utilisateur, $id_module)
// 1. calculerProgressionModule()
// 2. UPDATE inscriptions_modules SET progression_globale = ?, statut = IF(?>= 100, 'termine', 'en_cours')
//    WHERE id_utilisateur = ? AND id_module = ?
```

**Fonctions à modifier** :
```php
// marquerLeconTerminee() → REMPLACER par ouvrirLecon() (statut 50)
// L'ancienne logique JSON lecons_terminees reste en place pour rétrocompatibilité
// mais n'est plus la source de vérité

// calculerProgressionModule() → utiliser la nouvelle table progression_lecons
```

### 3.3 — Endpoints AJAX à modifier dans `ajax.php`

```php
// Modifier case 'maj_progression' :
// - action 'ouvrir' → ouvrirLecon() → statut 50
// - action 'valider' → validerLeconApresQuiz() → statut 100
// - Après chaque action : synchroniserProgressionModule()
// - Retourner : pourcentage_cours, pourcentage_module

// Ajouter case 'inscrire_module' (MANQUANT — appelé dans module.php) :
case 'inscrire_module':
    if (estEtudiant()) {
        $id_module = $_POST['id_module'] ?? 0;
        $resultat = inscrireEtudiantModule($_SESSION['id_utilisateur'], $id_module);
        $response = $resultat;
    }
    break;

// Ajouter case 'rechercherGlobal' (fonction inexistante dans fonctions.php) :
// Créer fonction rechercherGlobal($terme) dans fonctions.php
```

### 3.4 — Correction doublon AJAX dans `ajax.php`

**Supprimer** les lignes 307-339 (second bloc `case 'generer_certificat'` et `case 'obtenir_certificat'`).  
**Conserver** uniquement les versions lignes 247-303.

**Risques de régression**
- La table `progression_cours` reste intacte → ancien code continuerait à fonctionner
- `progression_lecons` est additive → pas de perte de données
- Vérifier que `evaluation.php` appelle bien `validerLeconApresQuiz()` après un quiz réussi

**Tests après correction**
1. S'inscrire à un module en tant qu'étudiant → vérifier `inscrire_module` AJAX fonctionne
2. Ouvrir une leçon → `SELECT statut FROM progression_lecons WHERE id_utilisateur = X AND id_lecon = Y` → doit être 50
3. Réussir un quiz → statut doit passer à 100
4. Vérifier `inscriptions_modules.progression_globale` mis à jour
5. Tester avec toutes les leçons à 100% → `progression_globale` doit atteindre 100

**Commit** : `git commit -m "Phase 3 — Correction progression 0/50/100, synchronisation progression_globale, fix inscrire_module AJAX"`

---

## PHASE 4 — Génération automatique certificat + demandes_certificats + validation Super Admin

**Durée estimée : 4h**  
**Risque : 🟡 MOYEN**

### 4.1 — Migration SQL

```sql
-- Table demandes de certificats exceptionnels
CREATE TABLE demandes_certificats (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    id_etudiant INT NOT NULL,
    id_module INT NOT NULL,
    motif TEXT NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente','approuve','refuse') DEFAULT 'en_attente',
    date_traitement DATETIME,
    id_admin_traitant INT,
    commentaire_admin TEXT,
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    FOREIGN KEY (id_admin_traitant) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL,
    INDEX idx_etudiant (id_etudiant),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 — Fonctions PHP à créer dans `includes/fonctions.php`

```php
// Vérifier si toutes les leçons d'un module sont validées (statut 100)
function moduleValideParEtudiant($id_utilisateur, $id_module)
// Retourne true si tous les id_lecon des cours du module ont statut=100

// Génération automatique (appelée après synchroniserProgressionModule si = 100)
function verifierEtGenererCertificat($id_utilisateur, $id_module)
// 1. Si progression_globale = 100 ET moduleValideParEtudiant() = true
// 2. Appeler genererCertificat() existant
// 3. Créer une notification pour l'étudiant

// Demande de certificat exceptionnel
function creerDemandeCertificat($id_etudiant, $id_module, $motif)
// INSERT INTO demandes_certificats ...

// Approuver une demande — SUPER ADMIN UNIQUEMENT
function approuverDemandeCertificat($id_demande, $id_admin)
// 1. Récupérer la demande
// 2. Générer le certificat via genererCertificat()
// 3. UPDATE demandes_certificats SET statut='approuve', date_traitement=NOW(), id_admin_traitant=?
// 4. Notification à l'étudiant

// Refuser une demande — SUPER ADMIN UNIQUEMENT
function refuserDemandeCertificat($id_demande, $id_admin, $commentaire)
// UPDATE demandes_certificats SET statut='refuse', commentaire_admin=?

// Obtenir demandes en attente (pour administration.php)
function obtenirDemandesCertificatsEnAttente()
```

### 4.3 — Endpoints AJAX à créer dans `ajax.php`

```php
case 'creer_demande_certificat':
    // estEtudiant() requis
    // POST : id_module, motif

case 'approuver_demande_certificat':
    // estSuperAdmin() UNIQUEMENT (pas promoteur)
    // POST : id_demande

case 'refuser_demande_certificat':
    // estSuperAdmin() UNIQUEMENT
    // POST : id_demande, commentaire
```

### 4.4 — Fichiers UI à modifier

**`certificat.php`** :
- Ajouter section "Demander un certificat exceptionnel" si progression < 100
- Formulaire : motif + bouton soumettre → appel AJAX `creer_demande_certificat`
- Afficher l'enseignant du cours sur le certificat (manquant actuellement)
- Corriger la query : ajouter `JOIN cours c ON...` pour récupérer l'enseignant

**`administration.php`** :
- Ajouter `?section=certificats` visible uniquement pour `estSuperAdmin()`
- Tableau des demandes en attente avec boutons Approuver/Refuser
- Confirmation modale avant approbation

**Risques de régression**
- `genererCertificat()` existant reste intact
- La condition actuelle `progression_globale >= 100` dans `certificat.php` sera désormais vraie après Phase 3
- Tester que les certificats existants ne sont pas re-générés en doublon (contrainte UNIQUE sur id_utilisateur + id_module protège)

**Tests après correction**
1. Compléter toutes les leçons d'un module à 100% → certificat généré automatiquement
2. Vérifier `SELECT * FROM certificats WHERE id_utilisateur = X`
3. Accéder à `certificat.php` → certificat affiché avec nom enseignant
4. Soumettre une demande exceptionnelle → vérifier `demandes_certificats`
5. En tant que Super Admin → approuver la demande → certificat créé
6. En tant que Promoteur → tenter d'approuver → accès refusé

**Commit** : `git commit -m "Phase 4 — Génération automatique certificats, workflow demandes_certificats, validation Super Admin"`

---

## PHASE 5 — Unification app.js + suppression duplications JS

**Durée estimée : 2h30**  
**Risque : 🔴 ÉLEVÉ (modification header.php + toutes les pages)**

### 5.1 — Problèmes à résoudre

| Problème | Fichier | Ligne | Solution |
|----------|---------|-------|----------|
| `app.js` jamais chargé | header.php | — | Ajouter `<script src="...app.js" defer>` |
| `chargerTheme()` en double | header.php + app.js | — | Supprimer version header.php |
| `changerTheme()` en double | header.php + app.js | — | Supprimer version header.php |
| `envoyerRequeteAjax()` redéfinie inline | cours.php, lecon.php, evaluation.php... | — | Supprimer les redéfinitions inline |
| `afficherNotification()` redéfinie inline | cours.php, lecon.php... | — | Supprimer les redéfinitions inline |
| `openModal()/closeModal()` ≠ `ouvrirModal()/fermerModal()` | administration.php, gestion_lecons.php | — | Unifier sur `ouvrirModal()/fermerModal()` dans app.js |

### 5.2 — Fichier `includes/header.php` — modifications

**Ajouter avant `</head>`** :
```html
<link rel="stylesheet" href="<?= SITE_URL ?>assets/css/style.css">
```

**Ajouter avant `</body>` (ou dans footer.php)** :
```html
<script src="<?= SITE_URL ?>assets/js/app.js" defer></script>
```

**Supprimer** le bloc `<script>` inline de header.php contenant `chargerTheme()` et `changerTheme()`.

### 5.3 — Fichier `assets/js/app.js` — modifications

**Unifier le thème sur `document.documentElement`** (déjà correct dans app.js — rien à changer).

**Ajouter les alias pour rétrocompatibilité** :
```javascript
// Aliases pour les pages qui appellent openModal/closeModal
function openModal(id) { ouvrirModal(id); }
function closeModal(id) { fermerModal(id); }
```

**Ajouter les styles toast dans `assets/css/style.css`** :
```css
.toast-notification { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; ... }
.toast-succes { background: var(--succes); color: white; }
.toast-danger { background: var(--danger); color: white; }
.toast-info { background: var(--primaire); color: white; }
.toast-avertissement { background: var(--avertissement); color: white; }
```

### 5.4 — Fichiers à purger de leurs redéfinitions inline

| Fichier | Fonctions à supprimer |
|---------|-----------------------|
| `cours.php` | `envoyerRequeteAjax()`, `afficherNotification()` |
| `lecon.php` | `envoyerRequeteAjax()`, `afficherNotification()` |
| `evaluation.php` | `envoyerRequeteAjax()`, `afficherNotification()` |
| `module.php` | `envoyerRequeteAjax()`, `afficherNotification()` |
| `certificat.php` | `escapeHtml()` (déjà dans app.js) |
| `administration.php` | `openModal()`, `closeModal()` redéfinis localement |
| `gestion_lecons.php` | `openModal()`, `closeModal()` non définis localement → corriger |

**Risques de régression**
- Si une page utilise `envoyerRequeteAjax` avant le chargement de app.js → erreur JS
- Mitigation : utiliser `defer` sur le script + DOMContentLoaded dans app.js (déjà en place)
- Tester chaque page individuellement après suppression des redéfinitions

**Tests après correction**
1. Ouvrir chaque page dans le navigateur → console JS : zéro erreur
2. Tester un appel AJAX (marquer leçon terminée) → toast de confirmation visible
3. Tester le changement de thème sur toutes les pages → thème persistant après rechargement
4. Tester `openModal()` dans administration.php → modal s'ouvre
5. Vérifier DevTools Network : `app.js` chargé en 200, `style.css` chargé en 200

**Commit** : `git commit -m "Phase 5 — Unification app.js, suppression duplications JS/CSS, chargement global"`

---

## PHASE 6 — Correction thème sombre + variables CSS

**Durée estimée : 2h**  
**Risque : 🟡 MOYEN**

### 6.1 — Variables CSS à ajouter dans `assets/css/style.css` section `:root`

```css
/* Variables manquantes — à ajouter dans :root {} */
--ombre-xl:       0 20px 25px -5px rgba(0,0,0,0.15);   /* utilisé dans 8 fichiers */
--ombre-glow:     0 0 20px rgba(37,99,235,0.4);          /* index.php, certificat.php, lecon.php */
--glass-bg:       rgba(255,255,255,0.05);                 /* index.php, choix_inscription.php */
--glass-border:   rgba(255,255,255,0.1);                  /* index.php, choix_inscription.php */
--radius-md:      0.5rem;                                 /* administration.php, lecon.php, certificat.php */
--radius-sm:      0.25rem;                                /* index.php */
--primaire-gradient: linear-gradient(135deg, #2563eb, #06b6d4); /* profil.php, inscriptions */
--secondaire:     #0f172a;                                /* module.php, cours.php, index.php */
--info:           #8b5cf6;                                /* notifications */
--spacing-1:      0.25rem;
--spacing-2:      0.5rem;
--spacing-3:      0.75rem;
--spacing-5:      1.25rem;
--spacing-10:     2.5rem;
--spacing-12:     3rem;
```

**Mode sombre — valeurs à conserver dans `[data-theme="dark"]`** (aucun changement requis, déjà dans style.css).

### 6.2 — Variables manquantes dans `includes/header.php` `:root`

Ajouter dans le `:root` inline de header.php (pour les pages qui n'incluent pas style.css via link) :
```css
--ombre-xl: 0 20px 25px -5px rgba(0,0,0,0.15);
--primaire-gradient: linear-gradient(135deg, #2563eb, #06b6d4);
--secondaire: #0f172a;
--info: #8b5cf6;
--radius-md: 0.5rem;
--radius-sm: 0.25rem;
--ombre-glow: 0 0 20px rgba(37,99,235,0.4);
--glass-bg: rgba(255,255,255,0.05);
--glass-border: rgba(255,255,255,0.1);
--spacing-1: 0.25rem;
--spacing-2: 0.5rem;
--spacing-3: 0.75rem;
--spacing-5: 1.25rem;
--spacing-12: 3rem;
```

### 6.3 — Intégrer `creer_cours.php` et `gestion_cours.php` dans le design system

**Pour chaque fichier** :
1. Remplacer le `<!DOCTYPE html>...` jusqu'à `<body>` par `<?php include 'includes/header.php'; ?>`
2. Remplacer `</body></html>` par `<?php include 'includes/footer.php'; ?>`
3. Supprimer les styles inline qui dupliquent le design system
4. Utiliser les variables CSS du design system dans les styles restants
5. S'assurer que `$page_title` est défini avant l'include header

**Fichiers impactés** :
- `creer_cours.php` — a son propre DOCTYPE, styles inline, pas de navbar
- `gestion_cours.php` — idem, hors design system complètement

**Risques de régression**
- Les styles inline peuvent avoir une spécificité différente → tester visuellement
- Les formulaires d'upload doivent conserver `enctype="multipart/form-data"` (inchangé)

**Tests après correction**
1. Ouvrir chaque page → navbar visible, thème appliqué
2. Activer le thème sombre → toutes les pages l'appliquent
3. DevTools → vérifier absence d'erreurs CSS `undefined variable`
4. Inspecter les éléments utilisant `--ombre-glow`, `--glass-bg` → valeurs appliquées
5. Recharger après toggle thème → préférence persiste

**Commit** : `git commit -m "Phase 6 — Variables CSS manquantes, thème sombre complet, creer_cours et gestion_cours intégrés"`

---

## PHASE 7 — Responsive mobile + sidebar mobile + notifications globales

**Durée estimée : 3h**  
**Risque : 🟡 MOYEN**

### 7.1 — Sidebar mobile dans `includes/header.php`

**HTML à ajouter dans header.php** (juste après la `<nav>`) :
```html
<!-- Bouton hamburger — visible uniquement mobile -->
<button class="hamburger-btn" id="hamburgerBtn" aria-label="Menu">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
</button>

<!-- Overlay + sidebar mobile -->
<div class="mobile-overlay" id="mobileOverlay"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="mobile-sidebar-header">
        <!-- Logo GOL -->
        <button class="mobile-close-btn" id="mobileCloseBtn">
            <svg .../><!-- SVG croix -->
        </button>
    </div>
    <nav class="mobile-nav">
        <!-- Mêmes liens que .nav-menu desktop -->
    </nav>
</div>
```

**CSS à ajouter dans `assets/css/style.css`** :
```css
/* Hamburger — visible uniquement mobile */
.hamburger-btn { display: none; }

@media (max-width: 768px) {
    .nav-menu { display: none; }
    .hamburger-btn { display: flex; /* ... */ }
    .mobile-sidebar { 
        position: fixed; top: 0; left: -280px; width: 280px; height: 100vh;
        background: var(--carte); z-index: 1100;
        transition: left 0.3s ease;
        border-right: 1px solid var(--bordure);
    }
    .mobile-sidebar.open { left: 0; }
    .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1099; }
    .mobile-overlay.visible { display: block; }
}
```

**JS à ajouter dans `assets/js/app.js`** (les fonctions `ouvrirMenuMobile()` / `fermerMenuMobile()` existent déjà, connecter les events) :
```javascript
document.getElementById('hamburgerBtn')?.addEventListener('click', ouvrirMenuMobile);
document.getElementById('mobileCloseBtn')?.addEventListener('click', fermerMenuMobile);
document.getElementById('mobileOverlay')?.addEventListener('click', fermerMenuMobile);
```

### 7.2 — Breakpoints complets dans `assets/css/style.css`

```css
/* Tablette : 768px–1024px */
@media (min-width: 768px) and (max-width: 1024px) {
    .admin-sidebar { width: 220px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Ultra-wide : > 1440px */
@media (min-width: 1440px) {
    .container, .nav-container { max-width: 1440px; }
}
```

### 7.3 — Badge notifications dans la navbar

**HTML à ajouter dans header.php** (dans `.nav-actions`) :
```html
<?php if (estConnecte()): ?>
<div class="notif-btn-wrapper" style="position:relative;">
    <button class="theme-btn" id="notifBtn">
        <svg><!-- icône cloche SVG --></svg>
    </button>
    <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
</div>
<?php endif; ?>
```

**JS dans `assets/js/app.js`** — polling 30 secondes :
```javascript
function actualiserNotifications() {
    fetch('ajax.php?action=obtenir_notifications', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const badge = document.getElementById('notifBadge');
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'flex' : 'none';
        }
    });
}
if (document.getElementById('notifBadge')) {
    actualiserNotifications();
    setInterval(actualiserNotifications, 30000);
}
```

**Risques de régression**
- Le badge requiert `estConnecte()` → aucun impact pages publiques
- Le polling AJAX est silencieux (try/catch) → pas d'impact si ajax.php inaccessible

**Tests après correction**
1. Réduire navigateur < 768px → hamburger visible, nav-menu masqué
2. Cliquer hamburger → sidebar s'ouvre avec animation slide
3. Cliquer overlay → sidebar se ferme
4. Vérifier badge notifications : créer une notification en base → badge s'actualise en < 30s
5. Tester sur tablette (768-1024px) → sidebar admin compacte
6. Chrome DevTools → onglet mobile : tester iPhone SE, iPad

**Commit** : `git commit -m "Phase 7 — Responsive mobile, sidebar hamburger SVG, polling notifications 30s"`

---

## PHASE 8 — Suppression emojis + bibliothèque SVG

**Durée estimée : 3h**  
**Risque : 🟢 FAIBLE**

### 8.1 — Inventaire des fichiers avec emojis (48 occurrences)

| Fichier | Emojis présents | Priorité |
|---------|----------------|----------|
| `gestion_lecons.php` | ✏️ 🗑️ 📝 📄 🎥 ⏱️ | Haute |
| `gestion_cours.php` | 📖 ✅ ❌ 📄 🎥 🗑️ | Haute |
| `evaluation.php` | 📝 🎯 ❓ ⏱️ 🔄 🎉 | Haute |
| `certificat.php` | 🎓 📜 🖨️ | Haute |
| `creer_cours.php` | 📚 | Haute |
| `inscription_enseignant.php` | 🎓 | Moyenne |
| `profil.php` | Plusieurs | Moyenne |
| `fix_enseignants.php` | ✅ | Basse (fichier utilitaire) |
| `fix_password.php` | ✅ | Basse |
| `test_*.php` | Divers | Basse (fichiers de test) |

### 8.2 — Créer `assets/svg/icons.php` — bibliothèque SVG réutilisable

```php
<?php
/**
 * Bibliothèque d'icônes SVG — GOL LMS
 * Utilisation : <?= icone('cours', 20) ?>
 */
function icone(string $nom, int $taille = 18, string $classe = ''): string {
    $attrs = "width=\"{$taille}\" height=\"{$taille}\" viewBox=\"0 0 24 24\" 
              fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" 
              stroke-linecap=\"round\" stroke-linejoin=\"round\"
              class=\"{$classe}\"";
    $icones = [
        'cours'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'lecon'       => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'quiz'        => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'pdf'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'video'       => '<rect x="2" y="6" width="20" height="12" rx="2"/><polygon points="10 9 16 12 10 15"/>',
        'succes'      => '<polyline points="20 6 9 17 4 12"/>',
        'erreur'      => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'info'        => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'avertissement' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'supprimer'   => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'modifier'    => '<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
        'publier'     => '<polyline points="22 4 12 14.01 9 11.01"/>',
        'certificat'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'utilisateur' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'module'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'statistiques'=> '<path d="M18 20V10M12 20V4M6 20v-6"/>',
        'hamburger'   => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'fermer'      => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'soleil'      => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>',
        'lune'        => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'timer'       => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'imprimer'    => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
    ];
    $path = $icones[$nom] ?? $icones['info'];
    return "<svg {$attrs}>{$path}</svg>";
}
```

### 8.3 — Stratégie de remplacement

**Approche** : Chercher/remplacer emoji par emoji dans chaque fichier.

Exemples de remplacements :
```
✅ → <?= icone('succes', 16) ?>
❌ → <?= icone('erreur', 16) ?>
🗑️ → <?= icone('supprimer', 16) ?>
✏️ → <?= icone('modifier', 16) ?>
📄 → <?= icone('pdf', 16) ?>
🎥 → <?= icone('video', 16) ?>
📝 → <?= icone('quiz', 16) ?>
🎓 → <?= icone('certificat', 24) ?>
⏱️ → <?= icone('timer', 16) ?>
📚 → <?= icone('cours', 24) ?>
```

**Risques de régression**
- Aucun impact fonctionnel
- Impact purement visuel
- Vérifier que `assets/svg/icons.php` est inclus dans chaque fichier qui utilise `icone()`

**Tests après correction**
1. Parcourir chaque page → aucun emoji Unicode visible
2. Vérifier l'affichage des SVG avec le thème sombre (stroke hérite de `currentColor`)
3. `grep -rn "📚\|✅\|❌\|🗑\|📄\|🎥" --include="*.php"` → 0 résultat

**Commit** : `git commit -m "Phase 8 — Suppression tous les emojis, bibliothèque SVG locale assets/svg/icons.php"`

---

## PHASE 9 — Initialisation Git + premier commit stable + stratégie push GitHub

**Durée estimée : 30min**  
**Risque : 🟢 FAIBLE**

### 9.1 — Initialisation du dépôt (à faire EN PREMIER, avant toute modification)

```bash
cd /opt/lampp/htdocs/GOL

# Initialiser Git
git init

# Configurer l'identité locale (si non configurée globalement)
git config user.name "ESSENGUE BILOA VICTORIEN MICHEL"
git config user.email "votre-email@domaine.com"

# Créer .gitignore avant le premier commit
cat > .gitignore << 'EOF'
# Uploads utilisateurs (ne pas versionner les fichiers uploadés)
uploads/pdf/*.pdf
uploads/videos/*.mp4
uploads/videos/*.webm
uploads/videos/*.ogg
uploads/avatars/*.jpg
uploads/avatars/*.jpeg
uploads/avatars/*.png
uploads/avatars/*.gif
uploads/modules_images/*.jpg
uploads/modules_images/*.png

# Garder les dossiers vides
!uploads/pdf/.gitkeep
!uploads/videos/.gitkeep
!uploads/avatars/.gitkeep
!uploads/modules_images/.gitkeep

# Fichiers système
.DS_Store
Thumbs.db
*.log

# Fichiers de test (optionnel — à décommenter si souhaité)
# test_*.php
# fix_*.php
EOF

# Créer les fichiers .gitkeep pour les dossiers vides
touch uploads/pdf/.gitkeep
touch uploads/videos/.gitkeep
touch uploads/avatars/.gitkeep
touch uploads/modules_images/.gitkeep

# Premier commit — snapshot état initial (avant toute modification)
git add .
git commit -m "Initial commit — Projet GOL LMS état initial (pré-refactorisation)"
```

### 9.2 — Stratégie de branches

```
main (ou master)
  └── refactorisation (branche de travail)
        ├── phase-1-inscription-enseignant
        ├── phase-2-gestion-quiz
        ├── phase-3-progression
        ├── phase-4-certificats
        ├── phase-5-app-js
        ├── phase-6-theme-css
        ├── phase-7-responsive
        ├── phase-8-svg
        └── merge final vers main
```

**Commandes par phase** :
```bash
# Avant chaque phase — créer une branche
git checkout -b phase-X-nom

# Après chaque phase — merger sur main
git checkout main
git merge phase-X-nom --no-ff
git push origin main
```

### 9.3 — Création du dépôt GitHub

```bash
# Option A : Via GitHub CLI (gh)
gh repo create gol-lms --private --source=. --remote=origin --push

# Option B : Manuellement
# 1. Créer le repo sur github.com (privé)
# 2. Copier l'URL HTTPS ou SSH

# Lier le remote
git remote add origin https://github.com/USERNAME/gol-lms.git
# ou SSH :
git remote add origin git@github.com:USERNAME/gol-lms.git

# Premier push
git push -u origin main
```

### 9.4 — Vérifications avant chaque push

```bash
# 1. Vérifier syntaxe PHP (tous les fichiers modifiés)
php -l includes/fonctions.php
php -l ajax.php
php -l [fichier_modifie].php

# 2. Vérifier qu'aucun fichier de configuration avec mot de passe n'est inclus
git diff --staged includes/config.php
# includes/config.php DOIT être dans .gitignore si contient des credentials réels

# 3. Vérifier le statut du projet
git status
git diff --staged --stat

# 4. Test de connexion rapide
# Ouvrir http://localhost/GOL/connexion.php → se connecter
```

### 9.5 — Protection du fichier de configuration

```bash
# Ajouter config.php au .gitignore et créer un template
echo "includes/config.php" >> .gitignore

# Créer un template de configuration
cp includes/config.php includes/config.example.php
# Remplacer les valeurs sensibles par des placeholders dans config.example.php
# Versionner uniquement config.example.php
```

**Commit final** :
```bash
git add .
git commit -m "Phase 9 — Initialisation Git, .gitignore, stratégie branches"
git push origin main
```

---

## TABLEAU RÉCAPITULATIF FINAL

| Phase | Contenu | Durée | Risque | Migrations SQL | Endpoints AJAX | Fichiers créés | Fichiers modifiés |
|-------|---------|-------|--------|---------------|----------------|---------------|------------------|
| 1 | Table enseignants + inscription | 1h | 🟢 | 1 ALTER | 0 | 0 | 1 |
| 2 | gestion_quiz.php complet | 4h | 🟢 | 1 ALTER | 9 | 1 | 2 |
| 3 | Progression + progression_globale | 3h | 🔴 | 1 CREATE | 2 modifiés + 1 ajouté | 0 | 2 |
| 4 | Certificats auto + demandes | 4h | 🟡 | 1 CREATE | 3 | 0 | 2 |
| 5 | Unification app.js | 2h30 | 🔴 | 0 | 0 | 0 | 8 |
| 6 | Thème sombre + CSS | 2h | 🟡 | 0 | 0 | 0 | 4 |
| 7 | Responsive + hamburger | 3h | 🟡 | 0 | 0 | 0 | 2 |
| 8 | Emojis → SVG | 3h | 🟢 | 0 | 0 | 1 | 8 |
| 9 | Git + GitHub | 30min | 🟢 | 0 | 0 | 2 | 0 |
| **TOTAL** | | **23h** | | **3 migrations** | **14 endpoints** | **4 fichiers** | **29 fichiers** |

---

## ORDRE D'EXÉCUTION ABSOLU

```
[AVANT TOUT] Phase 9.1 — git init + commit initial
      ↓
Phase 1  — inscription_enseignant.php (crash bloquant)
      ↓
Phase 2  — gestion_quiz.php (fonctionnalité absente critique)
      ↓
Phase 3  — progression (dépendance de Phase 4)
      ↓
Phase 4  — certificats (dépend de Phase 3)
      ↓
Phase 5  — unification JS (prépare Phases 6-8)
      ↓
Phase 6  — CSS variables + thème (prépare Phase 7)
      ↓
Phase 7  — responsive + hamburger + notifications
      ↓
Phase 8  — emojis → SVG
      ↓
Phase 9.2/9.3 — branches + push GitHub final
```

**Aucune phase ne doit démarrer sans que la précédente soit commitée et testée.**

---

*Document produit à partir des audits AUDIT_SQL.md et ETAT_ACTUEL_HEADER.md.*  
*Aucun fichier n'a été modifié lors de la production de ce document.*  
*En attente de validation pour démarrer la Phase 1.*
