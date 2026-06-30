# GOL — Gugle Online Learning

> Plateforme LMS (Learning Management System) développée en PHP/MySQL.  
> **Auteur :** ESSENGUE BILOA VICTORIEN MICHEL — Matricule 23U2628  
> **Université de Yaoundé 1 — INF-L2**  
> **Version :** 1.0.0

---

## Présentation

GOL est une plateforme d'apprentissage en ligne complète permettant à des étudiants de suivre des modules de formation, à des enseignants de créer et gérer des cours, et à des administrateurs de piloter l'ensemble du système.

---

## Fonctionnalités

### Étudiants
- Inscription et connexion sécurisée
- Consultation et inscription aux modules de formation
- Suivi de progression (0 / 50 / 100 % par leçon)
- Passage d'évaluations QCM avec score et feedback
- Obtention automatique de certificats (progression 100 % + note suffisante)
- Demande de certificat exceptionnel

### Enseignants
- Création et gestion de cours
- Ajout de leçons (texte, PDF, vidéo)
- Création de quiz (questions, options, score requis)
- Suivi de la progression des étudiants

### Promoteurs
- Mêmes droits que les enseignants
- Accès aux statistiques globales

### Super Admin
- Gestion complète des utilisateurs (activation/suspension)
- Gestion des modules
- Validation des demandes de modification de profil
- Validation des demandes de certificats exceptionnels
- Accès à tous les journaux d'activité

---

## Technologies utilisées
____________________________________________________
| Couche          | Technologie                    |
|--------------------------------------------------|
| Backend         | PHP 8+                         |
|--------------------------------------------------|
| Base de données | MySQL 8 (InnoDB / utf8mb4)     |
|--------------------------------------------------|
| Frontend        | HTML5, CSS3, JavaScript ES2022 |
|--------------------------------------------------|
| Serveur local   | XAMPP (Apache + MySQL)         |
|--------------------------------------------------|
| Fonts           | Google Fonts — Inter           |
|--------------------------------------------------|
| Icônes          | SVG inline natifs              |
----------------------------------------------------

---

## Prérequis

- XAMPP ≥ 8.0 (Apache + MySQL + PHP)
- PHP ≥ 8.0 avec extensions : PDO, PDO_MySQL, fileinfo, json
- MySQL ≥ 8.0
- Navigateur moderne (Chrome 90+, Firefox 88+, Edge 90+)

---

## Installation

### 1. Cloner ou copier le projet

```bash
cp -r GOL/ /opt/lampp/htdocs/GOL
# ou sous Windows :
# xcopy GOL C:\xampp\htdocs\GOL /E /I
```

### 2. Démarrer XAMPP

```bash
sudo /opt/lampp/lampp start
```

### 3. Importer la base de données

```bash
mysql -u root -p < /opt/lampp/htdocs/GOL/database.sql
```

Ou via phpMyAdmin : `http://localhost/phpmyadmin`  
→ Importer le fichier `database.sql`

### 4. Vérifier la configuration

Ouvrir `/opt/lampp/htdocs/GOL/includes/config.php` et vérifier :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gol_lms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/GOL/');
```

### 5. Permissions des dossiers uploads

```bash
chmod -R 755 /opt/lampp/htdocs/GOL/uploads/
```

### 6. Lancer l'application

Ouvrir : `http://localhost/GOL/`

---

## Comptes de test

> Créer les comptes via phpMyAdmin ou les formulaires d'inscription, puis modifier le rôle en BDD.

| Rôle        | Email exemple    | Rôle en BDD   |
|-------------|------------------|---------------|
| Super Admin | admin@gol.cm     | `super_admin` |
| Promoteur   | promoteur@gol.cm | `promoteur`   |
| Enseignant  | prof@gol.cm      | `enseignant`  |
| Étudiant    | etudiant@gol.cm  | `etudiant`    |

---

## Structure des dossiers

```
GOL/
├── includes/
│   ├── config.php          # Configuration BDD, sessions, constantes
│   ├── fonctions.php       # Toutes les fonctions PHP (auth, CRUD, CSRF...)
│   ├── header.php          # Navbar + sidebar mobile
│   └── footer.php          # Pied de page + scripts
├── assets/
│   ├── css/style.css       # Styles CSS (variables, composants, responsive)
│   ├── js/app.js           # JavaScript (thème, AJAX, modales, hamburger)
│   └── svg/icons.php       # Bibliothèque d'icônes SVG inline
├── uploads/
│   ├── avatars/            # Photos de profil
│   ├── pdf/                # PDFs des leçons
│   ├── videos/             # Vidéos des leçons
│   └── modules_images/     # Images des modules
├── docs/                   # Documentation
├── database.sql            # Script SQL complet
├── index.php               # Accueil public
├── connexion.php           # Formulaire de connexion
├── choix_inscription.php   # Choix du type de compte
├── inscription_etudiant.php
├── inscription_enseignant.php
├── tableau_bord.php        # Dashboard (adapté au rôle)
├── profil.php              # Gestion du profil utilisateur
├── module.php              # Page d'un module
├── cours.php               # Page d'un cours
├── lecon.php               # Lecteur de leçon
├── evaluation.php          # Passage d'un quiz
├── certificat.php          # Affichage d'un certificat
├── gestion_cours.php       # CRUD cours (enseignant)
├── gestion_lecons.php      # CRUD leçons (enseignant)
├── gestion_quiz.php        # CRUD quiz (enseignant)
├── creer_cours.php         # Formulaire création cours
├── administration.php      # Interface Super Admin
├── apropos.php             # Page À propos
├── deconnexion.php         # Déconnexion + destruction session
└── ajax.php                # API AJAX centralisée (35 actions)
```

---

## Architecture

```
Navigateur
    │
    ▼
header.php ──► config.php ──► PDO ──► MySQL (gol_lms)
    │               │
    │           fonctions.php
    │
    ▼
Page PHP (index, cours, tableau_bord...)
    │
    ▼ (requêtes AJAX)
ajax.php ──► fonctions.php ──► PDO
    │
    ▼
JSON response ──► app.js ──► DOM
```

---

## Sécurité

- **CSRF** : token `bin2hex(random_bytes(32))` sur tous les formulaires POST + header `X-CSRF-Token` sur AJAX
- **Sessions** : `session_regenerate_id(true)` après connexion, cookies `httponly + samesite=Lax`
- **SQL** : 100 % requêtes PDO préparées
- **XSS** : `htmlspecialchars()` sur toutes les sorties, `escapeHtml()` côté JS
- **Uploads** : vérification MIME réel via `finfo`, extensions et taille limitées
- **IDOR** : vérification de propriété (leçon → cours → enseignant) avant toute opération

---

*Pour plus de détails, voir les guides dans le dossier `docs/`.*
