# FONCTIONS PHARES — GOL (Gugle Online Learning)

**Développeur :** ESSENGUE BILOA VICTORIEN MICHEL  
**Matricule :** 23U2628  
**Université de Yaoundé 1 — INF-L2**  
**Version :** 1.0.0

---

## Table des matières

### PHP
1. [connexionBDD()](#1-connexionbdd)
2. [connecterUtilisateur()](#2-connecrerutilisateur)
3. [uploadFichierLecon()](#3-uploadfichierlecon)
4. [genererSlugUnique()](#4-genererslugUnique)
5. [verifierEtGenererCertificat()](#5-verifieregenercertificat)
6. [synchroniserProgressionCours()](#6-synchroniserprogressioncours)
7. [synchroniserProgressionModule()](#7-synchroniserprogressionmodule)
8. [validerLeconApresQuiz()](#8-validerleconapresquiz)
9. [rechercherGlobal()](#9-rechercherglobal)
10. [supprimerLecon()](#10-supprimerlecon)

### JavaScript
11. [afficherNotification()](#11-affichernotification)
12. [envoyerRequeteAjax()](#12-envoyerrequeteajax)
13. [chargerTheme()](#13-chargertheme)
14. [changerTheme()](#14-changertheme)
15. [escapeHtml()](#15-escapehtml)
16. [ouvrirModal()](#16-ouvrirmodal)
17. [fermerModal()](#17-fermermodal)
18. [ouvrirMenuMobile()](#18-ouvrirmenumobile)
19. [fermerMenuMobile()](#19-fermermenumobile)
20. [Initialisation responsive DOMContentLoaded](#20-initialisation-responsive-domcontentloaded)

---

## PHP


---

### 1. connexionBDD()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Facile |

**Signature**
```php
function connexionBDD(): PDO
```

**Description**  
Fournit un accès centralisé à l'instance PDO globale. Toutes les fonctions du projet utilisent `global $pdo` mais `connexionBDD()` sert de point d'entrée explicite lorsqu'un composant externe a besoin de la connexion sans manipuler directement la variable globale.

**Paramètres**  
Aucun.

**Valeur de retour**  
`PDO` — l'objet connexion déjà initialisé dans `includes/config.php`.

**Algorithme**
1. Accède à la variable globale `$pdo`.
2. La retourne directement.

**Exemple d'utilisation**
```php
$db = connexionBDD();
$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs");
```

**Pourquoi importante**  
Centralise l'accès à la BDD. Évite la dépendance directe à `global $pdo` dans du code externe ou des tests unitaires.

---

### 2. connecterUtilisateur()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Moyen |

**Signature**
```php
function connecterUtilisateur(string $email, string $mot_de_passe): array
```

**Description**  
Authentifie un utilisateur par email et mot de passe hashé. En cas de succès, initialise la session PHP et met à jour `derniere_connexion` en base.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$email` | string | Email de l'utilisateur |
| `$mot_de_passe` | string | Mot de passe en clair (comparé via `password_verify`) |

**Valeur de retour**  
`array` — `['success' => true, 'role' => '...']` ou `['success' => false, 'message' => '...']`

**Algorithme**
1. Prépare une requête SELECT sur `utilisateurs` avec `email = ?` et `statut = 'actif'`.
2. Si aucun résultat → retourne `success: false`.
3. Appelle `password_verify()` pour comparer le mot de passe clair au hash stocké.
4. Si vérification OK → remplit `$_SESSION` (id, email, nom, prénom, rôle).
5. Met à jour `derniere_connexion = NOW()`.
6. Retourne `['success' => true, 'role' => ...]`.
7. En cas d'exception PDO → retourne `success: false, message: 'Erreur de connexion'`.

**Exemple d'utilisation**
```php
$result = connecterUtilisateur('alice@example.com', 'MonMotDePasse');
if ($result['success']) {
    header('Location: tableau_bord.php');
}
```

**Pourquoi importante**  
Cœur du système d'authentification. Gère à la fois la vérification sécurisée du mot de passe et l'initialisation de session en une seule fonction.


---

### 3. uploadFichierLecon()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Avancé |

**Signature**
```php
function uploadFichierLecon(array $fichier, string $type): array
```

**Description**  
Upload sécurisé centralisé pour les ressources pédagogiques d'une leçon. Accepte les PDF (max 20 Mo) et les vidéos MP4/WebM/OGG (max 50 Mo). Effectue une triple vérification : code d'erreur PHP, extension de fichier, et MIME réel via `finfo`.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$fichier` | array | Entrée `$_FILES['...']` du formulaire HTML |
| `$type` | string | `'pdf'` ou `'video'` |

**Valeur de retour**  
`array` — `['success' => true, 'chemin' => 'uploads/pdf/pdf_xxx.pdf', 'fichier' => 'pdf_xxx.pdf']`  
ou `['success' => false, 'message' => '...']`

**Algorithme**
1. Vérifie `$fichier['error'] === UPLOAD_ERR_OK` ; retourne message d'erreur lisible sinon.
2. Extrait l'extension avec `pathinfo(..., PATHINFO_EXTENSION)`.
3. Selon `$type`, définit : extensions acceptées, MIMEs acceptés, taille max, dossier cible, préfixe de nom.
4. Vérifie l'extension contre la liste blanche.
5. Vérifie la taille contre `$taille_max`.
6. Vérifie le MIME réel du fichier via `finfo(FILEINFO_MIME_TYPE)`.
7. Crée le dossier cible si absent (`mkdir` récursif).
8. Génère un nom unique : `pdf_` ou `video_` + `uniqid()` + extension.
9. Déplace le fichier avec `move_uploaded_file()`.
10. Retourne le chemin relatif stockable en base de données.

**Exemple d'utilisation**
```php
$result = uploadFichierLecon($_FILES['fichier_pdf'], 'pdf');
if ($result['success']) {
    $chemin = $result['chemin']; // 'uploads/pdf/pdf_64a1b2c3d.pdf'
}
```

**Pourquoi importante**  
Unique point d'entrée pour tout upload de contenu pédagogique. La vérification MIME réelle empêche le déguisement d'un exécutable PHP en PDF.

---

### 4. genererSlugUnique()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Moyen |

**Signature**
```php
function genererSlugUnique(string $titre, int $id_cours_exclu = 0): string
```

**Description**  
Génère un slug URL-safe unique pour un cours. Si "cours-test" existe déjà, retourne "cours-test-2", puis "cours-test-3", etc. Le paramètre `$id_cours_exclu` permet d'exclure le cours en cours de modification.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$titre` | string | Titre du cours source |
| `$id_cours_exclu` | int | ID du cours à ignorer lors du test d'unicité (utile pour les modifications) |

**Valeur de retour**  
`string` — Slug unique, ex : `'introduction-au-python-2'`

**Algorithme**
1. Normalise le titre : minuscules, remplacement des caractères non alphanumériques par `-`, trim des tirets.
2. Initialise `$slug = $base`, `$compteur = 1`.
3. Boucle `do/while` : vérifie en BDD si `slug = ?` AND `id_cours != $id_cours_exclu`.
4. Si trouvé → incrémente compteur, `$slug = $base . '-' . $compteur`, recommence.
5. Si non trouvé → sort de la boucle et retourne `$slug`.

**Exemple d'utilisation**
```php
$slug = genererSlugUnique('Introduction au Python');
// Retourne : 'introduction-au-python' ou 'introduction-au-python-2'
```

**Pourquoi importante**  
Garantit l'unicité des URLs de cours. Sans cela, deux cours au même titre généreraient un conflit de slug et des URLs ambiguës.


---

### 5. verifierEtGenererCertificat()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Avancé |

**Signature**
```php
function verifierEtGenererCertificat(int $id_utilisateur, int $id_module): bool
```

**Description**  
Orchestre la génération automatique de certificat. Vérifie deux conditions cumulatives avant d'émettre : progression globale = 100 % ET note finale ≥ note requise moyenne du module. Déclenche également une notification à l'étudiant.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$id_utilisateur` | int | ID de l'étudiant |
| `$id_module` | int | ID du module |

**Valeur de retour**  
`bool` — `true` si le certificat a été généré, `false` sinon (conditions non remplies ou certificat déjà existant).

**Algorithme**
1. Lit `progression_globale` dans `inscriptions_modules`.
2. Si `progression_globale < 100` → retourne `false` immédiatement.
3. Calcule la note finale via `calculerNoteFinalModule()`.
4. Récupère la moyenne de `note_requise` des évaluations actives du module.
5. Si `$note < $note_requise` → retourne `false`.
6. Appelle `genererCertificat($id_utilisateur, $id_module, $note, false)`.
7. Si certificat généré → appelle `ajouterNotification()` pour informer l'étudiant.
8. Retourne `(bool)$cert`.

**Exemple d'utilisation**
```php
// Appelé automatiquement à la fin de synchroniserProgressionModule()
verifierEtGenererCertificat($id_utilisateur, $id_module);
```

**Pourquoi importante**  
Automatise entièrement l'obtention de certificat. Sans cette fonction, l'attribution manuelle serait nécessaire pour chaque étudiant.

---

### 6. synchroniserProgressionCours()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Avancé |

**Signature**
```php
function synchroniserProgressionCours(int $id_utilisateur, int $id_cours): float
```

**Description**  
Recalcule et persiste la progression d'un étudiant sur un cours. La progression = somme des statuts des leçons (0, 50 ou 100) divisée par le nombre total de leçons. Déclenche ensuite la synchronisation du module parent.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$id_utilisateur` | int | ID de l'étudiant |
| `$id_cours` | int | ID du cours |

**Valeur de retour**  
`float` — Pourcentage calculé (0 à 100), ou 0 si le cours n'a aucune leçon.

**Algorithme**
1. Compte le nombre total de leçons du cours.
2. Si 0 → retourne 0.
3. Somme les statuts (`progression_lecons.statut`) de l'utilisateur sur toutes les leçons via LEFT JOIN.
4. Calcule `$pourcentage = round($somme / $total, 2)`.
5. Détermine `$statut` : `'termine'` si ≥ 100, sinon `'en_cours'`.
6. Effectue un UPSERT dans `progression_cours` (INSERT … ON DUPLICATE KEY UPDATE).
7. Récupère `id_module` du cours et appelle `synchroniserProgressionModule()`.
8. Retourne le pourcentage.

**Exemple d'utilisation**
```php
$pct = synchroniserProgressionCours(42, 7);
// $pct = 50.0 si l'étudiant a ouvert la moitié des leçons
```

**Pourquoi importante**  
Garantit la cohérence des données de progression après chaque interaction de l'étudiant (ouverture ou validation d'une leçon). Évite tout calcul à la volée côté affichage.


---

### 7. synchroniserProgressionModule()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Avancé |

**Signature**
```php
function synchroniserProgressionModule(int $id_utilisateur, int $id_module): float
```

**Description**  
Recalcule la progression globale d'un étudiant sur un module entier, en faisant la moyenne des progressions de tous les cours publiés. Met à jour `inscriptions_modules.progression_globale` et déclenche la génération automatique de certificat si 100 % atteint.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$id_utilisateur` | int | ID de l'étudiant |
| `$id_module` | int | ID du module |

**Valeur de retour**  
`float` — Progression globale du module (0–100), ou 0 si aucun cours publié.

**Algorithme**
1. Récupère la liste des cours publiés du module.
2. Si vide → retourne 0.
3. Pour chaque cours, lit `progression_cours.pourcentage` (0 si absent).
4. Calcule la moyenne : `round($somme / $total, 2)`.
5. Détermine le statut : `'termine'` si ≥ 100, sinon `'en_cours'`.
6. Met à jour `inscriptions_modules` : `progression_globale`, `statut`, `date_completion` (NOW() si 100%).
7. Si `$progression_globale >= 100` → appelle `verifierEtGenererCertificat()`.
8. Retourne la progression calculée.

**Exemple d'utilisation**
```php
$prog = synchroniserProgressionModule(42, 3);
// Met à jour la ligne inscriptions_modules et génère le certificat si 100%
```

**Pourquoi importante**  
Maillon final de la chaîne de progression. C'est ici que se décide l'obtention automatique du certificat.

---

### 8. validerLeconApresQuiz()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Moyen |

**Signature**
```php
function validerLeconApresQuiz(int $id_utilisateur, int $id_lecon): bool
```

**Description**  
Marque une leçon comme entièrement complétée (statut = 100) après la réussite d'un quiz. Utilise un UPSERT pour ne jamais rétrograder une leçon déjà à 100 %. Déclenche ensuite la synchronisation de la progression du cours parent.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$id_utilisateur` | int | ID de l'étudiant |
| `$id_lecon` | int | ID de la leçon validée |

**Valeur de retour**  
`bool` — `true` si l'enregistrement a réussi, `false` sinon.

**Algorithme**
1. Exécute un `INSERT INTO progression_lecons ... ON DUPLICATE KEY UPDATE statut = 100, date_completion = NOW()`.
2. Si succès → lit `id_cours` de la leçon.
3. Appelle `synchroniserProgressionCours($id_utilisateur, $id_cours)`.
4. Retourne `true`.
5. Si échec PDO → retourne `false`.

**Exemple d'utilisation**
```php
// Appelé dans ajax.php après un quiz réussi
if ($score >= $evaluation['note_requise']) {
    validerLeconApresQuiz($id_utilisateur, $id_lecon);
}
```

**Pourquoi importante**  
Assure que la réussite d'un quiz se traduit immédiatement en progression persistée. La logique ON DUPLICATE KEY empêche toute régression de statut.

---

### 9. rechercherGlobal()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Facile |

**Signature**
```php
function rechercherGlobal(string $terme): array
```

**Description**  
Effectue une recherche plein-texte partielle (LIKE `%terme%`) sur les modules actifs et les cours publiés. Retourne au maximum 5 modules et 5 cours correspondants. Utilisée par l'endpoint AJAX `recherche` dans `ajax.php`.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$terme` | string | Terme de recherche saisi par l'utilisateur |

**Valeur de retour**  
`array` — `['modules' => [...], 'cours' => [...]]`

**Algorithme**
1. Construit `$like = '%' . $terme . '%'`.
2. Requête sur `modules` : `actif = 1` AND (`nom_module LIKE ?` OR `description LIKE ?`) LIMIT 5.
3. Requête sur `cours` : `statut = 'publie'` AND (`titre_cours LIKE ?` OR `description LIKE ?`), avec JOIN modules, LIMIT 5.
4. Retourne le tableau associatif des résultats.

**Exemple d'utilisation**
```php
$resultats = rechercherGlobal('python');
// ['modules' => [...], 'cours' => [...]]
```

**Pourquoi importante**  
Point d'entrée unique de la recherche côté serveur. Centralise la logique pour les deux entités interrogeables depuis la barre de recherche.


---

### 10. supprimerLecon()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `includes/fonctions.php` |
| **Difficulté** | Moyen |

**Signature**
```php
function supprimerLecon(int $id_lecon, int|null $id_enseignant = null): bool
```

**Description**  
Supprime une leçon de la base de données avec vérification anti-IDOR. Si `$id_enseignant` est fourni, vérifie que la leçon appartient bien à un cours de cet enseignant avant de supprimer. Le Super Admin peut passer `null` pour bypasser la vérification.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `$id_lecon` | int | ID de la leçon à supprimer |
| `$id_enseignant` | int\|null | ID de l'enseignant propriétaire (null = Super Admin, pas de vérification) |

**Valeur de retour**  
`bool` — `true` si supprimé, `false` si refusé (IDOR détecté) ou erreur PDO.

**Algorithme**
1. Si `$id_enseignant !== null` :
   - Requête `lecons JOIN cours WHERE id_lecon = ? AND id_enseignant = ?`.
   - Si aucun résultat → retourne `false` (tentative d'accès non autorisé bloquée).
2. Exécute `DELETE FROM lecons WHERE id_lecon = ?`.
3. Retourne le résultat de `execute()`.

**Exemple d'utilisation**
```php
// Enseignant tente de supprimer sa propre leçon
$ok = supprimerLecon(15, $_SESSION['id_utilisateur']);
if (!$ok) { echo "Accès refusé ou leçon introuvable."; }

// Super Admin supprime n'importe quelle leçon
supprimerLecon(15, null);
```

**Pourquoi importante**  
Protège contre les attaques IDOR (Insecure Direct Object Reference) : un enseignant ne peut pas supprimer la leçon d'un collègue en manipulant l'ID dans la requête.

---

## JavaScript

---

### 11. afficherNotification()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function afficherNotification(message, type = 'info')
```

**Description**  
Affiche une notification toast en bas à droite de l'écran pendant 4 secondes, puis la retire avec une animation de sortie. Supporte les types `'info'`, `'succes'`, `'danger'`, `'avertissement'`.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `message` | string | Texte à afficher dans le toast |
| `type` | string | Type visuel : `'info'` (bleu), `'succes'` (vert), `'danger'` (rouge), `'avertissement'` (orange) |

**Valeur de retour**  
`void`

**Algorithme**
1. Crée un élément `<div>` avec les classes `toast-notification toast-{type}`.
2. Injecte le HTML du message.
3. Appende le toast à `document.body`.
4. Après 4000 ms → applique l'animation `slideOutRight` (0.3 s).
5. Après 300 ms supplémentaires → supprime l'élément du DOM.

**Exemple d'utilisation**
```js
afficherNotification('Cours enregistré avec succès', 'succes');
afficherNotification('Erreur lors de la connexion', 'danger');
```

**Pourquoi importante**  
Interface unifiée de feedback utilisateur. Tous les résultats AJAX (succès, erreurs) passent par cette fonction — une modification ici impacte toute l'UX applicative.


---

### 12. envoyerRequeteAjax()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Moyen |

**Signature**
```js
async function envoyerRequeteAjax(endpoint, method = 'GET', data = null)
```

**Description**  
Couche d'abstraction centralisée sur l'API `fetch`. Construit automatiquement les headers JSON, sérialise les données, gère les erreurs HTTP et affiche les notifications d'erreur. Toutes les interactions AJAX du projet passent par cette fonction.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `endpoint` | string | Action AJAX (ex : `'recherche'`, `'soumettre_evaluation'`) |
| `method` | string | Méthode HTTP : `'GET'` ou `'POST'` |
| `data` | object\|null | Données à envoyer (sérialisées en JSON pour POST/PUT) |

**Valeur de retour**  
`Promise<object>` — Objet JSON retourné par `ajax.php`. Lance une erreur si `response.ok === false`.

**Algorithme**
1. Construit l'objet `options` : method, headers (`Content-Type: application/json`, `X-Requested-With: XMLHttpRequest`), credentials.
2. Si `data` et méthode POST/PUT → `options.body = JSON.stringify(data)`.
3. Appelle `fetch('ajax.php?action=' + endpoint, options)`.
4. Parse la réponse en JSON.
5. Si `!response.ok` → lance une erreur avec le message du serveur.
6. En cas d'erreur → appelle `afficherNotification(error.message, 'danger')` et relance l'erreur.
7. Retourne le résultat JSON.

**Exemple d'utilisation**
```js
const res = await envoyerRequeteAjax('recherche', 'GET');
const res2 = await envoyerRequeteAjax('soumettre_evaluation', 'POST', { evaluation_id: 3, reponses: {} });
```

**Pourquoi importante**  
Évite la duplication de logique fetch dans chaque page. Un seul endroit pour gérer l'authentification CSRF, les erreurs réseau et le parsing JSON.

---

### 13. chargerTheme()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function chargerTheme()
```

**Description**  
Applique le thème sauvegardé (clair ou sombre) dès le chargement de la page. Lit `localStorage` et synchronise simultanément l'attribut `data-theme` du `<html>` et le cookie PHP `gol_theme` (durée 1 an).

**Paramètres**  
Aucun.

**Valeur de retour**  
`void`

**Algorithme**
1. Lit `localStorage.getItem('gol_theme')` ; utilise `'light'` par défaut si absent.
2. Applique `document.documentElement.setAttribute('data-theme', theme)`.
3. Écrit le cookie `gol_theme={theme}; path=/; max-age=31536000`.

**Exemple d'utilisation**
```js
// Appelé automatiquement dans DOMContentLoaded
chargerTheme();
```

**Pourquoi importante**  
Empêche le flash de thème (FOUC) au rechargement. La synchronisation cookie permet au PHP de connaître le thème actif pour la génération SSR.

---

### 14. changerTheme()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function changerTheme()
```

**Description**  
Bascule le thème entre clair (`'light'`) et sombre (`'dark'`). Persiste le choix dans `localStorage` et le cookie PHP, met à jour l'attribut HTML, et affiche un toast de confirmation.

**Paramètres**  
Aucun.

**Valeur de retour**  
`void`

**Algorithme**
1. Lit l'attribut `data-theme` actuel de `<html>`.
2. Calcule le nouveau thème : `dark` si actuel est `light`, et vice-versa.
3. Applique `data-theme` au `<html>`.
4. Persiste dans `localStorage.setItem('gol_theme', newTheme)`.
5. Persiste dans le cookie `gol_theme`.
6. Appelle `afficherNotification('Thème sombre/clair activé', 'info')`.

**Exemple d'utilisation**
```js
// Lié au bouton toggle thème dans le header
document.getElementById('themeToggle').addEventListener('click', changerTheme);
```

**Pourquoi importante**  
Implémente le toggle thème accessible sans rechargement de page. La double persistance (localStorage + cookie) assure la cohérence entre JS et PHP.


---

### 15. escapeHtml()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function escapeHtml(text)
```

**Description**  
Encode les caractères spéciaux HTML (`<`, `>`, `&`, `"`, etc.) dans une chaîne de texte arbitraire, en utilisant le mécanisme natif du DOM (`textContent`). Protège contre les injections XSS lors de l'insertion dynamique de données utilisateur dans le HTML.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `text` | any | Valeur à échapper (convertie en string ; `null`/`undefined` retournent `''`) |

**Valeur de retour**  
`string` — Texte avec caractères HTML encodés, ou chaîne vide si `text` est `null`/`undefined`.

**Algorithme**
1. Si `text === null || text === undefined` → retourne `''`.
2. Crée un `<div>` temporaire en mémoire.
3. Assigne `div.textContent = String(text)` (encodage automatique par le navigateur).
4. Retourne `div.innerHTML` (texte encodé HTML).

**Exemple d'utilisation**
```js
const titre = escapeHtml('<script>alert("xss")</script>');
// Retourne : '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
element.innerHTML = `<h2>${titre}</h2>`;
```

**Pourquoi importante**  
Défense XSS côté client. Utilisée systématiquement avant toute insertion via `innerHTML` de données provenant du serveur ou de l'utilisateur.

---

### 16. ouvrirModal()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function ouvrirModal(modalId)
```

**Description**  
Affiche une modale identifiée par son ID HTML. Ajoute la classe `active`, force `display: flex` et bloque le scroll du body pour maintenir l'expérience utilisateur pendant l'ouverture de la modale.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `modalId` | string | Valeur de l'attribut `id` de l'élément modale |

**Valeur de retour**  
`void`

**Algorithme**
1. `document.getElementById(modalId)`.
2. Si l'élément existe : ajoute la classe `active`, `style.display = 'flex'`, `document.body.style.overflow = 'hidden'`.

**Exemple d'utilisation**
```js
ouvrirModal('modalCreerCours');
// Alias disponible : openModal('modalCreerCours')
```

**Pourquoi importante**  
Point d'entrée unique pour l'affichage des modales. Centralise la gestion du scroll-lock et évite la duplication de code dans chaque page.

---

### 17. fermerModal()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function fermerModal(modalId)
```

**Description**  
Masque une modale et restaure le scroll du body. Symétrique à `ouvrirModal()`. Également déclenché automatiquement au clic sur le fond de la modale (overlay) via le gestionnaire DOMContentLoaded.

**Paramètres**
| Nom | Type | Description |
|-----|------|-------------|
| `modalId` | string | Valeur de l'attribut `id` de l'élément modale |

**Valeur de retour**  
`void`

**Algorithme**
1. `document.getElementById(modalId)`.
2. Si l'élément existe : retire la classe `active`, `style.display = 'none'`, `document.body.style.overflow = ''`.

**Exemple d'utilisation**
```js
fermerModal('modalCreerCours');
// Alias disponible : closeModal('modalCreerCours')
// Déclenché également au clic sur l'overlay de la modale
```

**Pourquoi importante**  
Assure la fermeture propre des modales et le rétablissement de l'expérience de scroll. L'alias `closeModal` garantit la rétrocompatibilité avec les pages qui utilisaient l'ancien nom.


---

### 18. ouvrirMenuMobile()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function ouvrirMenuMobile()
```

**Description**  
Ouvre le menu de navigation mobile (sidebar). Ajoute la classe `open` au panneau latéral, rend visible l'overlay de fond, met à jour l'attribut ARIA `aria-expanded` du bouton hamburger, et bloque le scroll du body.

**Paramètres**  
Aucun.

**Valeur de retour**  
`void`

**Algorithme**
1. Récupère les éléments `mobileSidebar`, `mobileOverlay` et `hamburgerBtn`.
2. Si `mobileSidebar` absent → retourne immédiatement (guard clause).
3. `sidebar.classList.add('open')`.
4. `overlay?.classList.add('visible')`.
5. `btn?.setAttribute('aria-expanded', 'true')`.
6. `document.body.style.overflow = 'hidden'`.

**Exemple d'utilisation**
```js
// Lié automatiquement au clic sur hamburgerBtn dans DOMContentLoaded
document.getElementById('hamburgerBtn').addEventListener('click', ouvrirMenuMobile);
```

**Pourquoi importante**  
Gère la navigation mobile accessible. La mise à jour ARIA garantit la conformité avec les lecteurs d'écran ; l'overlay permet de fermer le menu en tapant à côté.

---

### 19. fermerMenuMobile()

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Facile |

**Signature**
```js
function fermerMenuMobile()
```

**Description**  
Ferme le menu de navigation mobile. Retire la classe `open` du panneau, masque l'overlay, remet `aria-expanded` à `false` et restaure le scroll. Également déclenché au clic sur un lien de navigation et au redimensionnement de la fenêtre vers desktop.

**Paramètres**  
Aucun.

**Valeur de retour**  
`void`

**Algorithme**
1. Récupère `mobileSidebar`, `mobileOverlay`, `hamburgerBtn`.
2. Si `mobileSidebar` absent → retourne immédiatement.
3. `sidebar.classList.remove('open')`.
4. `overlay?.classList.remove('visible')`.
5. `btn?.setAttribute('aria-expanded', 'false')`.
6. `document.body.style.overflow = ''`.

**Exemple d'utilisation**
```js
// Fermeture au clic sur l'overlay
document.getElementById('mobileOverlay').addEventListener('click', fermerMenuMobile);

// Fermeture automatique au resize vers desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) fermerMenuMobile();
});
```

**Pourquoi importante**  
Symétrique de `ouvrirMenuMobile()`. Assure une fermeture propre du menu mobile dans tous les scénarios : clic bouton fermeture, clic overlay, sélection d'un lien, et redimensionnement.

---

### 20. Initialisation responsive DOMContentLoaded

| Propriété | Valeur |
|-----------|--------|
| **Fichier** | `assets/js/app.js` |
| **Difficulté** | Moyen |

**Signature**
```js
document.addEventListener('DOMContentLoaded', function() { ... })
```

**Description**  
Bloc d'initialisation responsive exécuté à la fin du chargement du DOM. Câble tous les événements du menu mobile (hamburger, bouton fermeture, overlay, liens de navigation), et installe le listener de redimensionnement pour fermer automatiquement le menu si la fenêtre passe en mode desktop (> 768 px).

> **Note :** Ce bloc remplace les fonctions nommées `gestionMenuHamburger()`, `gestionOverlay()` et `gestionResponsive()` qui n'existent pas dans le projet. L'initialisation est directement intégrée dans l'écouteur `DOMContentLoaded`.

**Paramètres**  
Aucun (écouteur d'événement global).

**Valeur de retour**  
`void`

**Algorithme**
1. `hamburgerBtn` → écoute `click` → `ouvrirMenuMobile()`.
2. `mobileCloseBtn` → écoute `click` → `fermerMenuMobile()`.
3. `mobileOverlay` → écoute `click` → `fermerMenuMobile()`.
4. Chaque `.mobile-nav-link` → écoute `click` → si `window.innerWidth <= 768` → `fermerMenuMobile()`.
5. `window` → écoute `resize` → si `window.innerWidth > 768` → `fermerMenuMobile()`.

**Exemple d'utilisation**
```js
// Exécuté automatiquement — aucun appel manuel nécessaire
// Le HTML doit fournir : id="hamburgerBtn", id="mobileCloseBtn",
// id="mobileOverlay", id="mobileSidebar", classes ".mobile-nav-link"
```

**Pourquoi importante**  
Centralise toute la logique d'interactivité mobile dans un bloc unique. Le listener resize garantit qu'un menu laissé ouvert sur mobile ne bloque pas la navigation si l'utilisateur passe en mode paysage ou desktop.

---

*Documentation générée le 29 juin 2026 — GOL LMS v1.0.0*
