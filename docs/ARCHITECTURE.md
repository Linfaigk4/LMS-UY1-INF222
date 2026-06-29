# Architecture technique — GOL LMS

---

## 1. Arborescence des fichiers

```
GOL/
├── includes/
│   ├── config.php          # Session, BDD, constantes
│   ├── fonctions.php       # ~1600 lignes — toutes les fonctions PHP
│   ├── header.php          # Navbar desktop + sidebar mobile + CSS inline
│   └── footer.php          # Fermeture HTML + scripts
├── assets/
│   ├── css/style.css       # ~2500 lignes — design system complet
│   ├── js/app.js           # ~350 lignes — JS vanilla ES2022
│   └── svg/icons.php       # Bibliothèque SVG inline
├── uploads/
│   ├── avatars/
│   ├── pdf/
│   ├── videos/
│   └── modules_images/
├── docs/                   # Documentation Markdown
├── database.sql            # Schéma SQL + données initiales
├── ajax.php                # Point d'entrée API AJAX (35 actions)
└── [pages].php             # 19 pages PHP
```

---

## 2. Flux de navigation

```
/ (index.php)
├── /connexion.php              ← POST connexion
├── /choix_inscription.php
│   ├── /inscription_etudiant.php   ← POST inscription
│   └── /inscription_enseignant.php ← POST inscription
├── /tableau_bord.php           ← auth requise
├── /profil.php                 ← auth requise
├── /module.php?id=X            ← public
├── /cours.php?id=X             ← public
├── /lecon.php?id=X             ← auth étudiant
├── /evaluation.php?id=X        ← auth étudiant
├── /certificat.php?id=X        ← auth étudiant
├── /gestion_cours.php          ← auth enseignant/promoteur
├── /gestion_lecons.php?id=X    ← auth enseignant/promoteur
├── /gestion_quiz.php           ← auth enseignant/promoteur
├── /creer_cours.php            ← auth enseignant/promoteur
├── /administration.php         ← auth super_admin
├── /apropos.php                ← public
└── /deconnexion.php            ← auth requise
```

---

## 3. Structure SQL (16 tables)

```sql
utilisateurs          -- Comptes (4 rôles)
modules               -- Unités pédagogiques
cours                 -- Cours rattachés à un module
lecons                -- Leçons d'un cours (texte/PDF/vidéo)
evaluations           -- Quiz associés à une leçon
questions             -- Questions d'un quiz
options               -- Choix de réponse (une correcte par question)
resultats_evaluations -- Historique des passages de quiz
progression_cours     -- % de complétion par cours et étudiant
progression_lecons    -- Statut 0/50/100 par leçon et étudiant
inscriptions_modules  -- Inscription étudiant ↔ module
certificats           -- Certificats générés (code unique)
notifications         -- Notifications in-app
logs_activite         -- Journal des actions utilisateurs
demandes_modification -- Demandes de modification de profil
demandes_certificats  -- Demandes de certificats exceptionnels
```

### Schéma de progression

```
progression_lecons.statut :
  0   = jamais ouverte
  50  = ouverte (INSERT via ouvrirLecon())
  100 = quiz réussi (UPDATE via validerLeconApresQuiz())

progression_cours.pourcentage :
  = SUM(progression_lecons.statut) / COUNT(lecons)
  Mis à jour par synchroniserProgressionCours()

inscriptions_modules.progression_globale :
  = moyenne des progression_cours de tous les cours publiés
  Mis à jour par synchroniserProgressionModule()
```

---

## 4. Architecture PHP

### includes/config.php

- Configure les cookies de session (`session_set_cookie_params`) avant `session_start()`
- Définit les constantes : `DB_*`, `SITE_URL`, `UPLOAD_*`, `MAX_FILE_SIZE`
- Crée l'instance `$pdo` (PDO avec socket Unix sous XAMPP Linux)
- Timezone : `Africa/Douala`

### includes/fonctions.php (~1600 lignes)

Groupes de fonctions :

| Groupe | Fonctions clés |
|---|---|
| Authentification | `estConnecte()`, `estEtudiant()`, `estEnseignant()`, `estPromoteur()`, `estSuperAdmin()` |
| Auth | `connecterUtilisateur()`, `inscrireUtilisateur()`, `deconnecterUtilisateur()` |
| Modules | `obtenirModules()`, `ajouterModule()`, `modifierModule()`, `supprimerModule()` |
| Cours | `obtenirCours()`, `ajouterCours()`, `modifierCours()`, `supprimerCours()`, `publierCours()` |
| Leçons | `obtenirLecons()`, `ajouterLecon()`, `modifierLecon()`, `supprimerLecon()` |
| Progression | `ouvrirLecon()`, `validerLeconApresQuiz()`, `synchroniserProgressionCours()`, `synchroniserProgressionModule()` |
| Quiz | `ajouterEvaluation()`, `modifierEvaluation()`, `ajouterQuestion()`, `ajouterOption()` |
| Certificats | `genererCertificat()`, `verifierEtGenererCertificat()`, `calculerNoteFinalModule()` |
| CSRF | `genererTokenCSRF()`, `verifierTokenCSRF()`, `champCSRF()` |
| Upload | `uploadFichier()` (avatars), `uploadFichierLecon()` (PDF/vidéo) |
| Stats | `obtenirStatistiquesGlobales()`, `obtenirStatistiquesEtudiant()`, `obtenirStatistiquesEnseignant()` |

---

## 5. Gestion des sessions

```php
// config.php — avant session_start()
session_set_cookie_params([
    'lifetime' => 0,        // Session navigateur uniquement
    'path'     => '/',
    'secure'   => $https,   // HTTPS si disponible
    'httponly' => true,      // Inaccessible en JS
    'samesite' => 'Lax',    // Protection CSRF
]);
session_start();

// fonctions.php — après connexion réussie
session_regenerate_id(true);  // Prévient la fixation de session
$_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
$_SESSION['role']           = $utilisateur['role'];
```

Variables de session :
- `$_SESSION['id_utilisateur']`
- `$_SESSION['email']`
- `$_SESSION['nom']`
- `$_SESSION['prenom']`
- `$_SESSION['role']`
- `$_SESSION['csrf_token']`

---

## 6. Gestion des rôles

Vérification centralisée dans `fonctions.php` :

```php
function estConnecte()   { return isset($_SESSION['id_utilisateur']); }
function estSuperAdmin() { return $_SESSION['role'] === 'super_admin'; }
function estPromoteur()  { return $_SESSION['role'] === 'promoteur'; }
function estEnseignant() { return $_SESSION['role'] === 'enseignant'; }
function estEtudiant()   { return $_SESSION['role'] === 'etudiant'; }
```

Chaque page protégée commence par :
```php
if (!estConnecte()) { header('Location: connexion.php'); exit; }
if (!estEnseignant() && !estSuperAdmin()) { header('Location: tableau_bord.php'); exit; }
```

Visibilité dans le header :

| Élément | Visiteur | Étudiant | Enseignant | Promoteur | Super Admin |
|---|---|---|---|---|---|
| Connexion / S'inscrire | ✅ | ✗ | ✗ | ✗ | ✗ |
| Profil | ✗ | ✅ | ✅ | ✅ | ✅ |
| Tableau de bord | ✗ | ✅ | ✅ | ✅ | ✅ |
| Gestion des cours | ✗ | ✗ | ✅ | ✅ | ✗ |
| Administration | ✗ | ✗ | ✗ | ✗ | ✅ |
| Déconnexion | ✗ | ✅ | ✅ | ✅ | ✅ |

---

## 7. Sécurité

### CSRF
- Token `bin2hex(random_bytes(32))` stocké en `$_SESSION['csrf_token']`
- Injecté dans les formulaires via `<?= champCSRF() ?>`
- Vérifié avec `hash_equals()` avant tout traitement POST
- Régénéré après chaque validation (one-time use)
- Transmis en AJAX via header `X-CSRF-Token` + meta `csrf-token` dans `<head>`
- Vérifié dans `ajax.php` via `$_SERVER['HTTP_X_CSRF_TOKEN']` ou `$_POST['csrf_token']`

### XSS
- PHP : `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` sur toutes les sorties
- JS : `escapeHtml(text)` via `document.createElement('div').textContent`

### SQL Injection
- 100 % requêtes PDO préparées (`$pdo->prepare()` + `execute([...])`)
- `PDO::ATTR_EMULATE_PREPARES => false`

### Upload
- Vérification extension + MIME réel via `finfo(FILEINFO_MIME_TYPE)`
- Taille max : 50 Mo (fichiers), 2 Mo (avatars)
- Noms de fichiers régénérés avec `uniqid()` (pas de noms utilisateur)

### IDOR
- `supprimerLecon($id, $id_enseignant)` vérifie la chaîne `leçon → cours → enseignant`
- `evaluationAppartientEnseignant()` et `questionAppartientEnseignant()` idem

---

## 8. API AJAX (ajax.php)

Point d'entrée : `ajax.php?action=NOM_ACTION`  
Méthode : `GET` ou `POST`  
Format réponse : JSON

Pré-conditions systématiques :
1. Header `X-Requested-With: XMLHttpRequest` obligatoire
2. Utilisateur connecté (`estConnecte()`)
3. Pour POST : token CSRF valide (header `X-CSRF-Token` ou `$_POST['csrf_token']`)

Voir `docs/API_AJAX.md` pour la liste complète des 35 actions.

---

## 9. JavaScript (app.js)

Fonctions exposées globalement :

| Fonction | Description |
|---|---|
| `changerTheme()` | Bascule light/dark, persiste en localStorage + cookie |
| `envoyerRequeteAjax(endpoint, method, data)` | fetch centralisé avec CSRF header |
| `afficherNotification(message, type)` | Toast auto-supprimé après 4 s |
| `ouvrirModal(id)` / `fermerModal(id)` | Gestion des modales |
| `escapeHtml(text)` | Encodage XSS côté JS |
| `ouvrirMenuMobile()` / `fermerMenuMobile()` | Sidebar mobile |
| `rechercherCours()` / `rechercherModules()` | Filtrage temps réel |
| `soumettreEvaluation(id)` | Soumission quiz via AJAX |
| `mettreAJourProgression(leconId)` | Progression via AJAX |

Aliases de compatibilité : `openModal(id)`, `closeModal(id)`

---

## 10. Responsive design

Breakpoints CSS :

| Point | Comportement |
|---|---|
| > 768px | Menu desktop (`.nav-menu` visible) |
| ≤ 768px | Menu hamburger → sidebar mobile (`.mobile-sidebar`) |
| ≤ 480px | Grilles 1 colonne, cartes pleine largeur |
| ≤ 320px | Padding réduit, textes compressés |

Sidebar mobile : slide depuis la droite avec overlay semi-transparent.  
`aria-expanded` mis à jour pour l'accessibilité.
