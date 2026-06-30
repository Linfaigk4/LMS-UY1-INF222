# Design Document: Promoter Workflow & Quote Management

## Overview

Ce design technique décrit l'implémentation complète du workflow Promoteur et du système de gestion des demandes de devis pour la plateforme GOL. Le système comprend:

1. **Système d'authentification Promoteur** - Nouveau rôle utilisateur avec accès limité
2. **Tableau de bord Promoteur** - Statistiques et découverte de modules en lecture seule
3. **Formulaire de demande de devis** - Interface publique pour les visiteurs
4. **Interface de gestion administrative** - Gestion complète des demandes par les Super Admins
5. **Système de notifications** - Alertes pour les nouvelles demandes
6. **Sécurité multi-couches** - CSRF, SQL injection prevention, XSS protection

---

## 1. Architecture de la Base de Données

### 1.1 Nouvelle Table: `demandes_devis`

```sql
CREATE TABLE demandes_devis (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    prenom VARCHAR(100) NOT NULL COMMENT 'Prénom du demandeur',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom du demandeur',
    email VARCHAR(255) NOT NULL COMMENT 'Email de contact',
    telephone VARCHAR(20) NULL COMMENT 'Numéro de téléphone',
    nom_etablissement VARCHAR(255) NOT NULL COMMENT 'Nom de l''établissement/organisation',
    ville VARCHAR(100) NULL COMMENT 'Ville de l''établissement',
    pays VARCHAR(100) NOT NULL COMMENT 'Pays de l''établissement',
    nombre_etudiants VARCHAR(50) NOT NULL COMMENT 'Nombre approximatif d''étudiants (ex: 50-100)',
    nombre_enseignants VARCHAR(50) NOT NULL COMMENT 'Nombre approximatif d''enseignants',
    besoins JSON NOT NULL COMMENT 'Besoins sélectionnés (array JSON)',
    message_additionnel TEXT NULL COMMENT 'Message libre du demandeur',
    statut ENUM('en_attente', 'contacte', 'en_negociation', 'accepte', 'refuse') DEFAULT 'en_attente' COMMENT 'État de la demande',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière modification',
    date_traitement DATETIME NULL COMMENT 'Date du traitement final',
    notes_admin TEXT NULL COMMENT 'Notes privées de l''administrateur',
    
    INDEX idx_statut (statut),
    INDEX idx_email (email),
    INDEX idx_date_creation (date_creation DESC),
    INDEX idx_nom_etablissement (nom_etablissement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.2 Modification: Colonne `role` dans `utilisateurs`

```sql
-- Modifier l'ENUM pour ajouter le rôle 'promoteur'
ALTER TABLE utilisateurs 
MODIFY COLUMN role ENUM('super_admin', 'promoteur', 'enseignant', 'etudiant') 
NOT NULL DEFAULT 'etudiant';
```

**Ordre des rôles** (importance décroissante):
1. `super_admin` - Accès complet
2. `promoteur` - Accès limité (nouveau)
3. `enseignant` - Création de cours, gestion
4. `etudiant` - Suivi de cours

### 1.3 Indexes Optimisés

- `idx_statut`: Filtrage rapide par état (admin, notifications)
- `idx_email`: Vérification doublons, recherche
- `idx_date_creation DESC`: Tri des demandes récentes
- `idx_nom_etablissement`: Recherche par nom établissement

### 1.4 Relationships

**Table `demandes_devis`** (pas de foreign key intentionnellement - demandes anonymes acceptées):
- Ne référence pas `utilisateurs` (visiteur non connecté)
- Standalone table pour collecte de leads

**Notifications liées**:
- Quand `demandes_devis.statut` change → notification créée dans table `notifications`
- `notifications.lien_action` peut pointer à `/gestion_devis.php?id=X`

---

## Architecture

Ce design utilise une architecture en couches:

```
┌─────────────────────────────────┐
│    Présentation (UI/Views)      │  Formulaires PHP, templates HTML
├─────────────────────────────────┤
│   Logique Métier (Fonctions)    │  Validation, CRUD, notifications
├─────────────────────────────────┤
│   Accès Données (PDO/Database)  │  Requêtes préparées, transactions
├─────────────────────────────────┤
│   Infrastructure (Email, Logs)  │  Mail, logs_activite
└─────────────────────────────────┘
```

---

## Components and Interfaces

### Composants PHP (Pages)

1. **inscription_promoteur.php** - Formulaire de création de compte Promoteur
2. **tableau_bord_promoteur.php** - Dashboard en lecture seule
3. **demande_devis.php** - Formulaire public de demande de devis
4. **gestion_devis.php** - Interface Admin pour gérer les devis

### Interfaces Publiques

- **API Email** - mail() PHP natif
- **Notifications** - Table notifications existante
- **Session** - PDO session management existant

---

## Data Models

### Model: Utilisateur (Promoteur)

```
{
  id_utilisateur: int,
  email: string,
  nom: string,
  prenom: string,
  role: 'promoteur',
  statut: 'actif',
  date_inscription: datetime,
  derniere_connexion: datetime
}
```

### Model: DemandeDevi

```
{
  id_demande: int,
  prenom: string,
  nom: string,
  email: string,
  telephone: string|null,
  nom_etablissement: string,
  ville: string|null,
  pays: string,
  nombre_etudiants: string,  // "0-50", "50-100", etc.
  nombre_enseignants: string,
  besoins: array<string>,    // JSON
  message_additionnel: string|null,
  statut: enum,              // 'en_attente', 'contacte', etc.
  date_creation: datetime,
  date_modification: datetime,
  date_traitement: datetime|null,
  notes_admin: string|null
}
```

---

## 2. Endpoints et Routes

### 2.1 Pages PHP à Créer

| Page | Accès | Rôle | Paramètres | Redirection |
|------|-------|------|-----------|-------------|
| `/inscription_promoteur.php` | Public | Aucun | Aucun | Tableau bord après succès |
| `/tableau_bord_promoteur.php` | Privé | promoteur | `?page=N` (modules) | connexion.php si non-connecté |
| `/demande_devis.php` | Public | Aucun | `?source=X` (optionnel) | index.php après succès |
| `/gestion_devis.php` | Privé | super_admin | `?id=X`, `?page=N`, `?filter=status` | tableau_bord.php si non-admin |

### 2.2 Pages à Modifier

| Page | Modifications |
|------|---|
| `/choix_inscription.php` | Ajouter bouton "Promoteur" + lien vers `/inscription_promoteur.php` |
| `/index.php` | Ajouter bouton "Demander un devis" → ouvre modal ou `/demande_devis.php` |
| `/includes/header.php` | Ajouter lien "Gestion devis" dans menu admin (super_admin uniquement) |
| `/includes/fonctions.php` | Ajouter 9 nouvelles fonctions (voir section 3) |

### 2.3 Paramètres GET/POST

#### `/inscription_promoteur.php` - POST
```
email: string (email)
mot_de_passe: string (min 8 chars)
confirm_mot_de_passe: string
prenom: string (max 100)
nom: string (max 100)
csrf_token: string (via champCSRF())
```

#### `/demande_devis.php` - POST
```
prenom: string (max 100) - REQUIRED
nom: string (max 100) - REQUIRED
email: string (email) - REQUIRED
telephone: string (digits/+/-/() only) - OPTIONAL
nom_etablissement: string (max 255) - REQUIRED
ville: string (max 100) - OPTIONAL
pays: string (max 100) - REQUIRED
nombre_etudiants: string (range: "0-50", "50-100", etc) - REQUIRED
nombre_enseignants: string (range: same) - REQUIRED
besoins: array (checkboxes) - REQUIRED (min 1)
message_additionnel: text (max 2000) - OPTIONAL
csrf_token: string - REQUIRED
```

#### `/tableau_bord_promoteur.php` - GET
```
page: int (default: 1)
```

#### `/gestion_devis.php` - GET/POST
```
GET:
  id: int (view details)
  page: int (listing page, default: 1)
  filter_status: enum (filter by statut)
  search: string (search email/name/institution)
  date_from: date (filter by date range)
  date_to: date

POST (update):
  id_demande: int
  statut: enum
  notes_admin: text
  csrf_token: string
```

### 2.4 Redirections de Contrôle d'Accès

```php
// /tableau_bord_promoteur.php - ligne 1-5
if (!estConnecte()) {
    header('Location: connexion.php');
    exit;
}
if (!estPromoteur()) {
    header('Location: tableau_bord.php?error=forbidden');
    exit;
}
```

```php
// /gestion_devis.php - ligne 1-5
if (!estConnecte()) {
    header('Location: connexion.php');
    exit;
}
if (!estSuperAdmin()) {
    header('Location: tableau_bord.php?error=forbidden');
    exit;
}
```

```php
// Tentative d'accès aux cours par Promoteur
// /creer_cours.php - ligne 1-5
if (!estConnecte()) {
    header('Location: connexion.php');
    exit;
}
if (!estEnseignant() && !estSuperAdmin()) {
    header('Location: tableau_bord_promoteur.php?error=restricted');
    exit;
}
```

---

## 3. Fonctions à Ajouter dans `includes/fonctions.php`

### 3.1 Fonctions d'Authentification Promoteur

#### `estPromoteur()`
```php
function estPromoteur() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'promoteur';
}
```
**Retour**: bool - TRUE si rôle='promoteur'
**Utilisation**: Vérification simple du rôle

#### `estPromoteurOuEnseignant()`
```php
function estPromoteurOuEnseignant() {
    $role = $_SESSION['role'] ?? null;
    return $role === 'promoteur' || $role === 'enseignant';
}
```
**Retour**: bool - TRUE si promoteur OU enseignant
**Utilisation**: Fonctionnalités partagées (accès modules, cours publics)

### 3.2 Fonctions de Gestion des Demandes de Devis

#### `creerDemandeDevi($data)`
```php
/**
 * Insère une nouvelle demande de devis dans la base de données.
 * 
 * @param array $data Tableau associatif:
 *   - prenom: string (required)
 *   - nom: string (required)
 *   - email: string (required, validated)
 *   - telephone: string (optional)
 *   - nom_etablissement: string (required)
 *   - ville: string (optional)
 *   - pays: string (required)
 *   - nombre_etudiants: string (required)
 *   - nombre_enseignants: string (required)
 *   - besoins: array (required, min 1)
 *   - message_additionnel: string (optional)
 * 
 * @return array ['success' => bool, 'id_demande' => int|null, 'message' => string]
 */
```

**Logique**:
1. Valider tous les champs
2. Convertir `besoins` array → JSON string
3. Vérifier doublon (email + 5 min) via `verifierDoublonDemandeDevi()`
4. Si doublon → retourner erreur
5. Insérer via PDO prepared statement
6. Envoyer email confirmation
7. Créer notification pour super_admin
8. Retourner `['success' => true, 'id_demande' => $lastId, 'message' => 'Demande enregistrée']`

#### `obtenirDemandesDevis($filters = [], $limit = 20, $offset = 0)`
```php
/**
 * Récupère les demandes de devis avec filtrage et pagination.
 * 
 * @param array $filters Optionnel:
 *   - statut: string ou array (filter by status)
 *   - search: string (search email/prenom/nom/etablissement)
 *   - date_from: string (YYYY-MM-DD)
 *   - date_to: string (YYYY-MM-DD)
 * 
 * @param int $limit Nombre de résultats par page (default: 20)
 * @param int $offset Décalage pour pagination
 * 
 * @return array Array de demandes avec COUNT total pour pagination
 */
```

**Retour**:
```php
[
    'total' => 145,
    'rows' => [
        ['id_demande' => 1, 'email' => '...', 'statut' => 'en_attente', ...],
        ...
    ]
]
```

#### `obtenirDemandeDevi($id_demande)`
```php
/**
 * Récupère une demande spécifique par ID.
 * 
 * @param int $id_demande
 * @return array|false Tableau associatif ou FALSE si non trouvé
 */
```

#### `modifierDemandeDevi($id_demande, $statut = null, $notes_admin = null)`
```php
/**
 * Modifie le statut et/ou les notes d'une demande.
 * Met à jour date_modification et date_traitement si statut change.
 * 
 * @param int $id_demande
 * @param string|null $statut Nouveau statut ENUM
 * @param string|null $notes_admin Notes administrateur
 * 
 * @return bool TRUE si succès
 */
```

**Logique**:
1. Vérifier que $statut est dans l'ENUM si fourni
2. UPDATE date_modification=NOW()
3. Si statut change → date_traitement=NOW()
4. Créer notification "Demande mise à jour"
5. Logger l'action

#### `supprimerDemandeDevi($id_demande)`
```php
/**
 * Supprime une demande de devis (super_admin uniquement).
 * 
 * @param int $id_demande
 * @return bool TRUE si succès
 */
```

#### `verifierDoublonDemandeDevi($email)`
```php
/**
 * Vérifie si une demande avec le même email a été créée dans les 5 dernières minutes.
 * 
 * @param string $email
 * @return bool TRUE si doublon détecté (moins de 5 min)
 */
```

**Query**:
```sql
SELECT id_demande FROM demandes_devis 
WHERE email = :email AND date_creation > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
LIMIT 1
```

#### `envoyerEmailConfirmationDevis($email, $prenom, $nom_etablissement, $telephone = null)`
```php
/**
 * Envoie un email de confirmation au demandeur.
 * Utilise la fonction mail() ou un queue système.
 * 
 * @param string $email
 * @param string $prenom
 * @param string $nom_etablissement
 * @param string|null $telephone
 * 
 * @return bool TRUE si succès (ou queue)
 */
```

**Template HTML**:
```html
<h2>Bonjour {prenom},</h2>
<p>Nous avons reçu votre demande de devis pour <strong>{nom_etablissement}</strong>.</p>
<p>Nous vous contacterons dans les <strong>48 heures</strong> au numéro {telephone} ou par email.</p>
<p>Merci d'avoir choisi GOL !</p>
<hr>
<p>Contact: <strong>contact@gol-platform.com</strong></p>
```

### 3.3 Fonctions Utilitaires

#### `validerEmailDevi($email)`
```php
function validerEmailDevi($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

#### `validerTelephoneDevi($telephone)`
```php
function validerTelephoneDevi($telephone) {
    // Vérifier: digits, +, -, (, ), espaces, points uniquement
    return preg_match('/^[0-9+\-\(\)\s\.]+$/', $telephone) === 1;
}
```

#### `compterDemandesDeviParStatut($statut)`
```php
/**
 * Compte le nombre de demandes par statut (pour badge notifications).
 * 
 * @param string $statut ENUM value
 * @return int
 */
```

---

## 4. Flux de Données et Diagrammes

### 4.1 Flux d'Inscription Promoteur

```
[Visiteur sur choix_inscription.php]
              ↓
       [Clique "Promoteur"]
              ↓
  [Navigate vers inscription_promoteur.php]
              ↓
  [Remplit: email, password, prenom, nom]
              ↓
    [Clique "S'inscrire"]
              ↓
  [POST inscription_promoteur.php]
              ↓
  [Validation: email unique, password 8+ chars]
              ↓
     [Validation échoue? ← Error message]
              ↓
     [INSERT utilisateurs (role='promoteur')]
              ↓
  [Génère CSRF token session]
              ↓
  [session_regenerate_id() + $_SESSION['role']='promoteur']
              ↓
  [Redirect tableau_bord_promoteur.php]
              ↓
    [Tableau de bord affiché]
```

### 4.2 Flux de Demande de Devis

```
[Visiteur sur index.php]
              ↓
   [Clique "Demander un devis"]
              ↓
   [Ouvre modal ou /demande_devis.php]
              ↓
   [Remplit formulaire:]
   - prenom, nom, email (required)
   - nom_etablissement (required)
   - besoins (checkboxes, min 1)
   - (autres champs optionnels)
              ↓
   [Clique "Envoyer"]
              ↓
   [POST /demande_devis.php]
              ↓
   [Valide CSRF token]
              ↓
   [Valide tous les champs]
   - Email format
   - Téléphone format (si fourni)
   - Besoins array non-vide
              ↓
   [Validation échoue? ← Display inline errors]
              ↓
   [Vérifie doublon (email + 5 min)]
              ↓
   [Doublon trouvé? ← Error "5 minutes"]
              ↓
   [appelle creerDemandeDevi($data)]
              ↓
   [INSERT demandes_devis]
              ↓
   [envoyerEmailConfirmationDevis()]
              ↓
   [Crée notification pour super_admin]
              ↓
   [Affiche success message + redirect]
              ↓
   [Visiteur voit "Merci, 48h"]
```

### 4.3 Flux de Gestion des Devis (Admin)

```
[Super Admin sur administration.php]
              ↓
   [Clique "Gestion des devis"]
              ↓
   [Navigate /gestion_devis.php]
              ↓
   [Affiche tableau: 20 devis/page]
   - Colonnes: ID, Institution, Email, Date, Statut, Actions
   - Filters: Status, Date range, Search
              ↓
   [Admin clique "View Details"]
              ↓
   [Affiche modal/page avec tous les champs]
   - Tous les champs read-only
   - Statut dropdown (editable)
   - Notes textarea (editable)
   - Boutons: Save, Delete
              ↓
   [Admin change statut → "accepte"]
              ↓
   [Admin ajoute notes]
              ↓
   [Admin clique "Save Changes"]
              ↓
   [POST /gestion_devis.php avec id, statut, notes]
              ↓
   [Valide CSRF]
              ↓
   [appelle modifierDemandeDevi()]
              ↓
   [UPDATE date_modification, date_traitement]
              ↓
   [Crée notification "Devis accepté"]
              ↓
   [Log l'action en logs_activite]
              ↓
   [Affiche success toast]
              ↓
   [Retour au tableau]
```

### 4.4 Architecture AJAX vs Synchrone

| Action | Type | Endpoint | Retour |
|--------|------|----------|--------|
| Inscription Promoteur | Synchrone | POST /inscription_promoteur.php | Redirect + Header |
| Demande de devis | Synchrone | POST /demande_devis.php | JSON + Redirect |
| Gestion devis (tableau) | Synchrone | GET /gestion_devis.php | HTML page |
| Modifier statut devis | Synchrone | POST /gestion_devis.php | JSON + redirect |
| Supprimer devis | Synchrone | POST /gestion_devis.php?action=delete | JSON |
| Badge notifications | AJAX | GET /ajax.php?action=compter_devis | JSON: {count: N} |
| Recherche devis (future) | AJAX | GET /ajax.php?action=rechercher_devis | JSON: [devis] |

**Décision**: Synchrone pour garantir cohérence et simplicité. AJAX optionnel pour polling badges.

### 4.5 Points de Validation

```
FORMULAIRE INSCRIPTION PROMOTEUR:
├─ Email format
├─ Email unique (query DB)
├─ Password 8+ caractères
├─ Password confirmation match
└─ CSRF token valide

FORMULAIRE DEMANDE DEVIS:
├─ CSRF token valide
├─ Email format (filter_var)
├─ Téléphone format (regex) si fourni
├─ Noms/établissement length validité
├─ Besoins array non-vide
├─ Nombres positifs entiers
├─ Doublon 5 min (query DB)
└─ HTML sanitization avant INSERT

MODIFICATION STATUT DEVIS:
├─ CSRF token valide
├─ Super Admin verif
├─ ID demande existe
├─ Statut valeur ENUM
└─ HTML sanitization avant UPDATE
```

### 4.6 Sécurité Couche par Couche

```
1. FRONT-END:
   - HTML: champCSRF() dans tous les forms
   - JS: validation basic (email format)

2. SESSION/AUTH:
   - estConnecte() avant page protégée
   - estPromoteur() / estSuperAdmin() pour accès
   - session_regenerate_id() après login

3. REQUEST:
   - Header X-Requested-With pour AJAX
   - hash_equals() CSRF token validation

4. VALIDATION:
   - filter_var() email
   - preg_match() phone
   - htmlspecialchars() sanitization

5. DATABASE:
   - PDO prepared statements TOUJOURS
   - Named placeholders ou positional (?)
   - NO string concatenation

6. OUTPUT:
   - htmlspecialchars() sur affichage
   - JSON encode UTF-8
   - Content-Type headers corrects
```

---

## 5. UI/UX Components

### 5.1 Formulaire d'Inscription Promoteur (`/inscription_promoteur.php`)

**Layout**: 1 colonne, centré, 600px max-width

```
┌─────────────────────────────────────────┐
│ GOL - Inscription Promoteur              │
├─────────────────────────────────────────┤
│                                         │
│ Champ: Email                            │
│ [____________________________]           │
│ Erreur inline (rouge) si invalide       │
│                                         │
│ Champ: Prénom                           │
│ [____________________________]           │
│                                         │
│ Champ: Nom                              │
│ [____________________________]           │
│                                         │
│ Champ: Mot de passe (8+ chars)          │
│ [password field]                        │
│ ℹ️ Minimum 8 caractères                 │
│                                         │
│ Champ: Confirmer mot de passe           │
│ [password field]                        │
│                                         │
│ [ CSRF token hidden field ]             │
│                                         │
│ [S'inscrire]  [Déjà inscrit? Se conn.] │
│                                         │
│ Footer: "En s'inscrivant, vous acceptez│
│ nos CGU" + link                         │
└─────────────────────────────────────────┘
```

**Champs HTML**:
```html
<form method="POST" action="inscription_promoteur.php">
    <!-- Email -->
    <label for="email">Email *</label>
    <input type="email" name="email" id="email" required maxlength="255">
    <span id="email_error" class="error"></span>
    
    <!-- Prenom -->
    <label for="prenom">Prénom *</label>
    <input type="text" name="prenom" id="prenom" required maxlength="100">
    
    <!-- Nom -->
    <label for="nom">Nom *</label>
    <input type="text" name="nom" id="nom" required maxlength="100">
    
    <!-- Mot de passe -->
    <label for="pwd">Mot de passe (8+ caractères) *</label>
    <input type="password" name="mot_de_passe" id="pwd" required minlength="8">
    <small>Minimum 8 caractères, mélange de lettres et chiffres recommandé</small>
    
    <!-- Confirmer mot de passe -->
    <label for="pwd2">Confirmer mot de passe *</label>
    <input type="password" name="confirm_mot_de_passe" id="pwd2" required minlength="8">
    
    <!-- CSRF -->
    <?= champCSRF() ?>
    
    <!-- Buttons -->
    <button type="submit" class="btn btn-primary btn-lg">S'inscrire</button>
    <a href="connexion.php" class="link-secondary">Déjà inscrit? Se connecter</a>
</form>
```

### 5.2 Tableau de Bord Promoteur (`/tableau_bord_promoteur.php`)

**Layout**: 
- Header navbar + user profile section
- 4-col stats grid (desktop), 2-col (tablet), 1-col (mobile)
- "Browse Modules" section with pagination
- "Feature Unlock" section (locked features)

```
┌──────────────────────────────────────────────────────────────────┐
│ GOL Platform | Bonjour Jean Dupont (Promoteur) | [Profile] [Logout]
├──────────────────────────────────────────────────────────────────┤
│                    TABLEAU DE BORD PROMOTEUR                     │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  STATISTIQUES GLOBALES:                                         │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ 📚 Modules      │ 📖 Cours        │ 👥 Utilisateurs │ 📊 Prog.│
│  │ disponibles     │ disponibles     │ actifs         │ moyenne │
│  │    15           │      127        │      2,345     │  68%    │
│  │ (card coloré)   │ (card coloré)   │ (card coloré)  │(card)   │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  DÉCOUVRIR LES MODULES (Pagination: page 1 de 3):              │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Module: "Développement Web"    │ Module: "Marketing"       │ │
│  │ Desc: "Apprenez les bases de.. │ Desc: "Stratégies marke.."│ │
│  │ Niveau: Intermédiaire          │ Niveau: Avancé            │ │
│  │ Cours: 8                       │ Cours: 12                 │ │
│  │ [Voir détails] →               │ [Voir détails] →          │ │
│  │                                                             │ │
│  │ Module: "Data Science" | "IA"  │ Module: "Cybersecurity"   │ │
│  │ ...                            │ ...                       │ │
│  └─────────────────────────────────────────────────────────────┘ │
│  [◄ Précédent] [1] [2] [3] [Suivant ►]                          │
│                                                                  │
│  FONCTIONNALITÉS VERROUILLÉES (PREMIUM):                        │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ 🔒 Création de cours                                        │ │
│  │    Créez et gérez vos propres cours. [Demander un devis]   │ │
│  │                                                             │ │
│  │ 🔒 Gestion des étudiants                                    │ │
│  │    Suivez la progression de vos apprenants. [Demander...]   │ │
│  │                                                             │ │
│  │ 🔒 Génération de certificats                               │ │
│  │    Créez des certificats pour vos modules. [Demander...]    │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

**Sections PHP**:

```php
<?php
// Statistiques
$stats = [
    'modules' => obtenirModules(true), // count
    'cours' => $pdo->query("SELECT COUNT(*) as nb FROM cours WHERE statut='publie'")->fetch()['nb'],
    'utilisateurs' => $pdo->query("SELECT COUNT(*) as nb FROM utilisateurs WHERE statut='actif'")->fetch()['nb'],
    'progression_moy' => round($moyenne_progression, 1) // from inscriptions_modules
];

// Modules avec pagination
$page = $_GET['page'] ?? 1;
$par_page = 6;
$offset = ($page - 1) * $par_page;
$modules = obtenirModules(true);
$total_modules = count($modules);
$total_pages = ceil($total_modules / $par_page);
$modules_page = array_slice($modules, $offset, $par_page);
?>
```

### 5.3 Formulaire de Demande de Devis (`/demande_devis.php` ou modal)

**Layout**: Modal 700px ou full page

```
┌────────────────────────────────────────────────────────┐
│ Demander un Devis - Plateforme GOL               [✕]  │
├────────────────────────────────────────────────────────┤
│                                                        │
│ Nous aimerions en savoir plus sur vos besoins!        │
│                                                        │
│ SECTION 1: INFORMATIONS DE CONTACT                    │
│                                                        │
│ Prénom *              Nom *                           │
│ [_____________]       [_____________]                 │
│                                                        │
│ Email *               Téléphone                       │
│ [_____________]       [_____________]                 │
│                                                        │
│ SECTION 2: ÉTABLISSEMENT                              │
│                                                        │
│ Nom établissement *                                   │
│ [_____________________________]                       │
│                                                        │
│ Ville              Pays *                             │
│ [_____________]    [_____________]                    │
│                                                        │
│ SECTION 3: CAPACITÉS                                  │
│                                                        │
│ Nombre d'étudiants *       Nombre d'enseignants *    │
│ [○ 0-50]                   [○ 0-50]                   │
│  [○ 50-100]                 [○ 50-100]                │
│  [○ 100-500]                [○ 100-500]               │
│  [○ 500+]                   [○ 500+]                  │
│                                                        │
│ SECTION 4: BESOINS                                    │
│                                                        │
│ Sélectionnez vos besoins * (min 1):                  │
│ [☑] Gestion d'apprentissage (LMS)                    │
│ [☐] Évaluation & notation                            │
│ [☐] Génération de certificats                        │
│ [☐] Support mobile                                   │
│ [☐] Hébergement vidéo                                │
│ [☐] Intégration systèmes existants                   │
│ [☐] Autre: [________________]                        │
│                                                        │
│ Message additionnel                                   │
│ [_____________________________]                       │
│ [Max 2000 caractères]                                │
│                                                        │
│ [ ] J'accepte de recevoir des informations marketing  │
│                                                        │
│ [ CSRF hidden ]                                       │
│                                                        │
│ [Envoyer demande]  [Annuler]                         │
│                                                        │
└────────────────────────────────────────────────────────┘
```

**Validation Front-End (JS en app.js)**:
```javascript
// Sur submit du form:
1. Email format (email input type + validity check)
2. Phone format (digits/+/- only)
3. Besoins: at least 1 checked
4. Required fields présents
5. Si erreur → affiche inline, scroll to error
```

### 5.4 Interface de Gestion des Devis (`/gestion_devis.php`)

**Layout**: Table responsive avec filters/search

```
┌────────────────────────────────────────────────────────────────┐
│ GESTION DES DEMANDES DE DEVIS                                  │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ FILTRES & RECHERCHE:                                          │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Statut: [Tous ▼] | Date: [__/__/__] à [__/__/__]  │ [🔍]     │ │
│ │ Chercher: [___________________________________] [Rechercher] │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                │
│ RÉSULTATS (20/page):                                          │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ ID │Institution      │Email           │Date    │Statut │Act.│
│ ├────┼────────────────┼────────────────┼────────┼────────┼───┤
│ │001 │Lycée St. Michel│admin@lycee.fr  │24/11/23│En attt │▼  │
│ │002 │Univ Yaoundé 1  │info@uy1.cm     │23/11/23│Contact│▼  │
│ │003 │Ecole privée    │director@ep.cm  │22/11/23│Négoci │▼  │
│ │    │                │                │        │       │    │
│ └─────────────────────────────────────────────────────────────┘ │
│ [◄ Précédent] Page 1 de 8 [Suivant ►]  Affichage: 20/page [v] │
│                                                                │
└────────────────────────────────────────────────────────────────┘

MODAL DÉTAILS (au clic sur une ligne):

┌──────────────────────────────────────────────────┐
│ Demande #001 - Lycée St. Michel         [✕]     │
├──────────────────────────────────────────────────┤
│                                                  │
│ INFORMATIONS (READ-ONLY):                       │
│ Prenom:          Jean                           │
│ Nom:             Dupont                         │
│ Email:           admin@lycee.fr                 │
│ Téléphone:       +237 6 XX XX XX XX             │
│ Établissement:   Lycée St. Michel               │
│ Ville:           Douala                         │
│ Pays:            Cameroun                       │
│ Étudiants:       100-500                        │
│ Enseignants:     10-50                          │
│ Besoins:         • Gestion d'apprentissage      │
│                  • Certificats                  │
│ Message:         "Nous sommes intéressés par..." │
│                                                  │
│ GESTION (EDITABLE):                             │
│ Statut:          [En attente ▼]                 │
│ Notes admin:                                    │
│ [____________________________]                  │
│ [Appelez mercredi 14h30 - offre 20%]           │
│                                                  │
│ [Enregistrer changes] [Supprimer] [Fermer]     │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Badges de statut (couleurs)**:
- `en_attente` - 🟡 Gray/Yellow
- `contacte` - 🔵 Blue
- `en_negociation` - 🟠 Orange
- `accepte` - 🟢 Green
- `refuse` - 🔴 Red

### 5.5 Lock Badges

Affiché quand un Promoteur essaie d'accéder à une fonctionnalité verrouillée:

```html
<div class="lock-badge" style="background: linear-gradient(135deg, #fee2e2, #fecaca); border: 2px solid #fca5a5; padding: 1.5rem; border-radius: 0.5rem;">
    <h3 style="display: flex; align-items: center; gap: 0.5rem;">
        🔒 Cette fonctionnalité est réservée aux utilisateurs payants
    </h3>
    <p>Vous êtes actuellement connecté en tant que <strong>Promoteur</strong>. Pour accéder à la création de cours et la gestion des étudiants, demandez un devis.</p>
    <button class="btn btn-primary" onclick="window.location.href='/?modal=quote_request'">
        → Demander un devis
    </button>
</div>
```

---

## 6. Intégration avec Code Existant

### 6.1 Modifications à `/includes/header.php`

**Ligne de header - Ajouter lien "Gestion devis" pour Super Admin**:

```php
<?php if (estConnecte() && estSuperAdmin()): ?>
    <li class="nav-item">
        <a href="gestion_devis.php" class="nav-link">
            <?= icone('devis', 18) ?> Gestion devis
            <?php 
                $stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM demandes_devis WHERE statut='en_attente'");
                $stmt->execute();
                $count = $stmt->fetch()['nb'];
                if ($count > 0): ?>
                    <span class="badge badge-danger"><?= $count ?></span>
                <?php endif; ?>
        </a>
    </li>
<?php endif; ?>
```

**Navigation Promoteur** (dans header):
```php
<?php if (estConnecte() && estPromoteur()): ?>
    <li class="nav-item">
        <a href="tableau_bord_promoteur.php" class="nav-link">
            📊 Mon tableau de bord
        </a>
    </li>
<?php endif; ?>
```

### 6.2 Modifications à `/choix_inscription.php`

**Ajouter bouton Promoteur aux options d'inscription**:

```php
<!-- Ligne 1-20, après section existante pour Étudiant/Enseignant -->

<section class="inscription-options">
    <div class="option-card">
        <h3>👔 Promoteur</h3>
        <p>Explorez la plateforme et ses capacités sans engagement</p>
        <ul>
            <li>Accès aux modules et cours publiés</li>
            <li>Consultation des statistiques</li>
            <li>Demandez un devis pour débloquer les fonctionnalités premium</li>
        </ul>
        <a href="inscription_promoteur.php" class="btn btn-secondary">
            S'inscrire en tant que Promoteur
        </a>
    </div>
</section>
```

### 6.3 Modifications à `/index.php`

**Ajouter bouton "Demander un devis" dans hero section**:

```html
<!-- Dans la section hero, avant ou après CTA principal -->
<div class="cta-group">
    <a href="choix_inscription.php" class="btn btn-primary btn-lg">
        S'inscrire maintenant
    </a>
    <button class="btn btn-outline-primary btn-lg" onclick="ouvrirModalDevis()">
        💼 Demander un devis
    </button>
</div>

<script>
function ouvrirModalDevis() {
    // Option 1: Navigate to demande_devis.php
    window.location.href = 'demande_devis.php';
    
    // Option 2: Ouvrir modal inline
    // ouvrirModal('modal-demande-devis');
}
</script>
```

### 6.4 Modifications à `/includes/fonctions.php`

**Ajouter toutes les fonctions listées en Section 3** (environ 250-300 lignes de code):

```php
// À ajouter avant la dernière ligne de fermeture ?>

// ============================================
// FONCTIONS AUTHENTIFICATION PROMOTEUR
// ============================================

function estPromoteur() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'promoteur';
}

function estPromoteurOuEnseignant() {
    $role = $_SESSION['role'] ?? null;
    return $role === 'promoteur' || $role === 'enseignant';
}

// ============================================
// FONCTIONS GESTION DEVIS
// ============================================

function creerDemandeDevi($data) {
    global $pdo;
    
    // Validation
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email invalide'];
    }
    if (empty($data['prenom']) || strlen($data['prenom']) > 100) {
        return ['success' => false, 'message' => 'Prénom invalide'];
    }
    // ... autres validations
    
    // Vérifier doublon
    if (verifierDoublonDemandeDevi($data['email'])) {
        return ['success' => false, 'message' => 'Une demande a déjà été soumise avec cette adresse email. Veuillez réessayer dans 5 minutes.'];
    }
    
    // Convertir besoins en JSON
    $besoins_json = json_encode($data['besoins'], JSON_UNESCAPED_UNICODE);
    
    // Sanitizer les données
    $data = array_map(function($val) {
        return is_string($val) ? htmlspecialchars($val, ENT_QUOTES, 'UTF-8') : $val;
    }, $data);
    
    // INSERT
    $stmt = $pdo->prepare("
        INSERT INTO demandes_devis 
        (prenom, nom, email, telephone, nom_etablissement, ville, pays, nombre_etudiants, nombre_enseignants, besoins, message_additionnel, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
    ");
    
    if ($stmt->execute([
        $data['prenom'], $data['nom'], $data['email'], $data['telephone'] ?? null,
        $data['nom_etablissement'], $data['ville'] ?? null, $data['pays'],
        $data['nombre_etudiants'], $data['nombre_enseignants'],
        $besoins_json, $data['message_additionnel'] ?? null
    ])) {
        $id_demande = $pdo->lastInsertId();
        
        // Envoyer email
        envoyerEmailConfirmationDevis(
            $data['email'], $data['prenom'], $data['nom_etablissement'], $data['telephone'] ?? null
        );
        
        // Créer notification pour super_admin
        $msg = "Nouvelle demande de {$data['nom_etablissement']} ({$data['ville']}, {$data['pays']}) reçue.";
        $stmt2 = $pdo->prepare("
            SELECT id_utilisateur FROM utilisateurs WHERE role='super_admin' LIMIT 1
        ");
        $stmt2->execute();
        $admin = $stmt2->fetch();
        if ($admin) {
            ajouterNotification($admin['id_utilisateur'], 'Nouvelle demande de devis', $msg, 'info', "gestion_devis.php?id={$id_demande}");
        }
        
        // Log l'action
        $stmt3 = $pdo->prepare("
            INSERT INTO logs_activite (action, description, utilisateur_id, date_action)
            VALUES ('DEVIS_CREATION', ?, 0, NOW())
        ");
        $stmt3->execute([$data['email']]);
        
        return ['success' => true, 'id_demande' => $id_demande];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'insertion'];
}

function verifierDoublonDemandeDevi($email) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id_demande FROM demandes_devis
        WHERE email = ? AND date_creation > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        LIMIT 1
    ");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

function obtenirDemandesDevis($filters = [], $limit = 20, $offset = 0) {
    global $pdo;
    
    $sql = "SELECT * FROM demandes_devis WHERE 1=1";
    $params = [];
    
    if (!empty($filters['statut'])) {
        $sql .= " AND statut = ?";
        $params[] = $filters['statut'];
    }
    if (!empty($filters['search'])) {
        $search = "%{$filters['search']}%";
        $sql .= " AND (email LIKE ? OR prenom LIKE ? OR nom LIKE ? OR nom_etablissement LIKE ?)";
        $params = array_merge($params, [$search, $search, $search, $search]);
    }
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(date_creation) >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(date_creation) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Count total
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM ({$sql}) as t");
    $stmt_count->execute($params);
    $total = $stmt_count->fetch()['total'];
    
    // Fetch rows
    $sql .= " ORDER BY date_creation DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    
    return [
        'total' => $total,
        'rows' => $stmt->fetchAll()
    ];
}

function obtenirDemandeDevi($id_demande) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM demandes_devis WHERE id_demande = ?");
    $stmt->execute([$id_demande]);
    return $stmt->fetch();
}

function modifierDemandeDevi($id_demande, $statut = null, $notes_admin = null) {
    global $pdo;
    
    $updates = [];
    $params = [];
    
    if ($statut !== null) {
        $updates[] = "statut = ?";
        $params[] = $statut;
        $updates[] = "date_traitement = NOW()";
    }
    if ($notes_admin !== null) {
        $updates[] = "notes_admin = ?";
        $params[] = htmlspecialchars($notes_admin, ENT_QUOTES, 'UTF-8');
    }
    
    if (empty($updates)) return false;
    
    $updates[] = "date_modification = NOW()";
    $params[] = $id_demande;
    
    $sql = "UPDATE demandes_devis SET " . implode(', ', $updates) . " WHERE id_demande = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($params)) {
        // Log l'action
        $demande = obtenirDemandeDevi($id_demande);
        $msg = "Devis #{$id_demande} ({$demande['email']}) mis à jour";
        $stmt2 = $pdo->prepare("
            INSERT INTO logs_activite (action, description, utilisateur_id, date_action)
            VALUES ('DEVIS_UPDATE', ?, ?, NOW())
        ");
        $stmt2->execute([$msg, $_SESSION['id_utilisateur'] ?? 0]);
        
        return true;
    }
    
    return false;
}

function supprimerDemandeDevi($id_demande) {
    global $pdo;
    $demande = obtenirDemandeDevi($id_demande);
    if (!$demande) return false;
    
    $stmt = $pdo->prepare("DELETE FROM demandes_devis WHERE id_demande = ?");
    if ($stmt->execute([$id_demande])) {
        // Log l'action
        $stmt2 = $pdo->prepare("
            INSERT INTO logs_activite (action, description, utilisateur_id, date_action)
            VALUES ('DEVIS_DELETE', ?, ?, NOW())
        ");
        $stmt2->execute(["Devis #{$id_demande} supprimé", $_SESSION['id_utilisateur'] ?? 0]);
        
        return true;
    }
    
    return false;
}

function envoyerEmailConfirmationDevis($email, $prenom, $nom_etablissement, $telephone = null) {
    $subject = "Confirmation de votre demande de devis - GOL Platform";
    
    $message = "<html><body style='font-family: Arial, sans-serif; color: #333;'>";
    $message .= "<h2>Bonjour {$prenom},</h2>";
    $message .= "<p>Nous avons reçu votre demande de devis pour <strong>{$nom_etablissement}</strong>.</p>";
    
    if ($telephone) {
        $message .= "<p>Nous vous contacterons dans les <strong>48 heures</strong> au numéro <strong>{$telephone}</strong> ou par email.</p>";
    } else {
        $message .= "<p>Nous vous contacterons dans les <strong>48 heures</strong> pour discuter de votre demande.</p>";
    }
    
    $message .= "<p>Merci d'avoir choisi <strong>GOL - Gugle Online Learning</strong>!</p>";
    $message .= "<hr>";
    $message .= "<p><strong>Contact:</strong> contact@gol-platform.com</p>";
    $message .= "</body></html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: contact@gol-platform.com\r\n";
    
    return mail($email, $subject, $message, $headers);
}

function validerTelephoneDevi($telephone) {
    return preg_match('/^[0-9+\-\(\)\s\.]+$/', $telephone) === 1;
}

function compterDemandesDeviParStatut($statut) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM demandes_devis WHERE statut = ?");
    $stmt->execute([$statut]);
    return (int)$stmt->fetch()['nb'];
}

// ... fin des fonctions
```

### 6.5 Intégration CSRF (existant - pas de changement)

La fonction `champCSRF()` est déjà implémentée. Utilisation:

```php
// Dans les formulaires:
<?= champCSRF() ?>

// À la réception POST:
if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit;
}
```

### 6.6 Intégration Email Sending (existant)

La fonction `mail()` est utilisée pour l'email de confirmation. Alternative future:
- Implémenter une queue système (Redis/DB)
- Utiliser un service comme SendGrid/Mailgun

Pour le MVP, utiliser `mail()` PHP natif.



## Correctness Properties

**Note de Design**: Ce feature ne bénéficie pas de property-based testing (PBT) pour les raisons suivantes:

1. **Pas de "pure functions"** - Les fonctions impliquent des side-effects (DB insert, email sending, session management)
2. **Logique déterministe** - La validation et les transitions d'état sont déterministes, pas d'inputs aléatoires pertinents
3. **Infrastructure testing** - Les tests d'intégration (email, DB) sont mieux traités en integration tests
4. **Approche recommandée** - Unit tests + Integration tests (basés sur des exemples concrets)

Par conséquent, cette section est intentionnellement omise. Voir **Testing Strategy** pour la stratégie de test recommandée.

## Testing Strategy

Ce feature utilise une approche **example-based testing** plutôt que property-based testing:

- **Unit Tests** - Validation de formulaires, vérification de doublons, sanitization
- **Integration Tests** - Flux complets (inscription → DB → email → notification)
- **Acceptance Tests** - Scénarios utilisateur manuels ou automatisés (Selenium/Playwright)
- **Security Tests** - CSRF, SQL injection, XSS prevention
- **Performance Tests** - Load time, responsive design

Voir **Section 8** pour la stratégie de test détaillée et les checklist d'acceptation.



| Erreur | Affichage | Code HTTP | Logging |
|--------|-----------|-----------|---------|
| Email format invalide | Inline message: "Email invalide" | 422 | Non |
| Email déjà utilisé (inscription) | Inline message: "Cet email est déjà utilisé" | 422 | Non |
| Password < 8 caractères | Inline message + helper text | 422 | Non |
| Phone format invalide | Inline message: "Format invalide (ex: +237...)" | 422 | Non |
| Besoins vides | Inline message: "Sélectionner au moins 1 besoin" | 422 | Non |
| Doublon devis (5 min) | Message: "Une demande a déjà été soumise..." | 429 Too Many Requests | Oui (logs_activite) |
| CSRF token absent/invalide | HTTP 403 Forbidden + silent fail | 403 | Oui (logs_activite) |

### 7.2 Erreurs Système (Exceptions)

| Erreur | Affichage | Code HTTP | Logging |
|--------|-----------|-----------|---------|
| DB connection fail | 500 page générique | 500 | Oui (error_log) |
| Email sending fail | Page succès (devis inséré) + note admin | 500 (partiel) | Oui + notification admin |
| Query SQL error | 500 page générique (prod) | 500 | Oui (error_log) |
| Fichier upload fail | Message utilisateur | 400 | Oui |

### 7.3 Erreurs Sécurité

| Erreur | Affichage | Code HTTP | Logging |
|--------|-----------|-----------|---------|
| Promotion non-admin accès admin | Redirect + message | 302 | Oui (logs_activite) |
| IDOR (accès autre devis) | HTTP 403 Forbidden | 403 | Oui (logs_activite) |
| SQL injection attempt | HTTP 403 + PDO exception | 403 | Oui (security.log) |
| XSS attempt in form | Input rejected + message | 422 | Oui |

### 7.4 Messages d'Erreur (UX-Friendly)

```
Validation échouée:
❌ Email invalide
✓ Prénom requis
✓ Au moins 1 besoin sélectionné

---

Erreur système (public):
"Une erreur s'est produite. Veuillez réessayer plus tard.
Si le problème persiste, contactez contact@gol-platform.com"

Erreur système (admin):
"Erreur: [Details techniques pour débogage]"
```

### 7.5 Redirections d'Erreur

```php
// Promotion tente accès non-autorisé
if (!estPromoteur()) {
    $_SESSION['error'] = 'Vous n\'avez pas accès à cette fonctionnalité. Demandez un devis.';
    header('Location: tableau_bord_promoteur.php');
    exit;
}

// CSRF fail
if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit;
}

// DB error
catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    http_response_code(500);
    include 'error_500.php';
    exit;
}
```

---

## 8. Testing Strategy

Ce feature n'est pas idéal pour property-based testing car:
- Beaucoup d'interactions UI/form
- Statut machine → comportement déterministe (pas d'inputs aléatoires pertinents)
- Infrastructure (email, DB) non testable en PBT
- Meilleur approche: integration tests + exemple-based unit tests

### 8.1 Unit Tests (Exemples)

**Test Suite: Quote Form Validation**

```php
// test_quote_validation.php

function test_email_validation() {
    // Valid email
    assert(filter_var('admin@lycee.fr', FILTER_VALIDATE_EMAIL) !== false);
    
    // Invalid email
    assert(filter_var('invalid-email', FILTER_VALIDATE_EMAIL) === false);
    assert(filter_var('', FILTER_VALIDATE_EMAIL) === false);
}

function test_phone_validation() {
    // Valid phone
    assert(validerTelephoneDevi('+237 6 XX XX XX XX') === true);
    assert(validerTelephoneDevi('(+33) 1-23-45.67') === true);
    
    // Invalid phone
    assert(validerTelephoneDevi('admin@123') === false);
    assert(validerTelephoneDevi('XX') === false);
}

function test_duplicate_prevention() {
    // Create devis at T=0
    creerDemandeDevi(['email' => 'test@test.cm', ...]);
    
    // Try create at T=2min → should FAIL (within 5 min window)
    $result = creerDemandeDevi(['email' => 'test@test.cm', ...]);
    assert($result['success'] === false);
    assert(str_contains($result['message'], '5 minutes'));
}
```

### 8.2 Integration Tests

**Test: Complete Quote Request Flow**

```gherkin
Scenario: Visitor submits quote request → Email sent → Admin sees notification

Given: No existing requests for "contact@school.cm"
When: Visitor submits form with valid data
Then: 
  - Record inserted in demandes_devis table
  - Email sent to contact@school.cm
  - Notification created for super_admin
  - Page displays success message
  - Duplicate check blocks request within 5 minutes

---

Scenario: Admin manages quote request

Given: 3 pending requests, 1 contacted, 0 accepted
When: Admin filters by "en_attente"
Then: Table shows 3 rows
When: Admin clicks "View Details"
Then: Modal opens with full request data
When: Admin changes status to "accepte"
Then: 
  - Database updated (date_traitement set)
  - Success notification shown
  - Log created
```

### 8.3 Acceptance Tests (Manual)

**Test Checklist: Promoter Account Creation**

- [ ] Visit `/choix_inscription.php` → "Promoter" option visible
- [ ] Click "S'inscrire Promoteur" → navigate to `/inscription_promoteur.php`
- [ ] Fill form: email, prenom, nom, password
- [ ] Submit → account created, session set, redirect to dashboard
- [ ] Login with same email → "role" shows "Promoteur"
- [ ] Try access `/creer_cours.php` → lock message displayed

**Test Checklist: Quote Request**

- [ ] On homepage → "Demander un devis" button visible
- [ ] Click button → form opens (modal or page)
- [ ] Fill form with valid data
- [ ] Submit → success message shown
- [ ] Check email → confirmation email received
- [ ] Admin logs in → notification badge shows new request
- [ ] Admin clicks "Gestion devis" → new request appears in table
- [ ] Admin opens details → all fields populated correctly
- [ ] Admin changes status → database updated, log created
- [ ] Try duplicate within 5 min → error displayed

### 8.4 Security Tests

**CSRF Token Validation**

```php
// Test: POST without CSRF token → HTTP 403
$response = post('/demande_devis.php', [
    'prenom' => 'Jean',
    'nom' => 'Dupont',
    'email' => 'test@test.cm'
    // No csrf_token
]);
assert($response['status'] === 403);
```

**SQL Injection Prevention**

```php
// Test: Payload containing SQL commands
$payload = "'; DROP TABLE demandes_devis; --";
$result = creerDemandeDevi([
    'nom_etablissement' => $payload,
    ...
]);

// Verify: table still exists, payload escaped
assert(checkTableExists('demandes_devis') === true);
$devis = obtenirDemandeDevi($result['id_demande']);
assert($devis['nom_etablissement'] === htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'));
```

**XSS Prevention**

```php
// Test: JavaScript payload in form field
$payload = "<script>alert('xss')</script>";
$result = creerDemandeDevi([
    'message_additionnel' => $payload,
    ...
]);

// Verify: payload escaped when displayed
$devis = obtenirDemandeDevi($result['id_demande']);
$html_output = htmlspecialchars($devis['message_additionnel'], ENT_QUOTES, 'UTF-8');
assert(str_contains($html_output, '&lt;script&gt;') === true);
assert(str_contains($html_output, '<script>') === false);
```

### 8.5 Responsive Design Tests

**Mobile (320px - iPhone SE)**
- [ ] Signup form displays 1 column
- [ ] Quote form displays 1 column
- [ ] Dashboard stats stack vertically
- [ ] Module cards full width
- [ ] Buttons have min 44px height

**Tablet (768px - iPad)**
- [ ] Form displays 2 columns where appropriate
- [ ] Dashboard stats 2-column grid
- [ ] Module cards 2-column grid
- [ ] Navigation mobile menu (hamburger)

**Desktop (1024px+)**
- [ ] Form displays 2-column layout
- [ ] Dashboard stats 4-column grid
- [ ] Module cards 3-column grid
- [ ] Navigation horizontal menu

### 8.6 Performance Targets

- Signup form: < 2s load time
- Quote form: < 2s load time
- Dashboard: < 3s load time (with 100+ modules)
- Admin list: < 2s load time (20 requests per page)
- Email sending: async/queue (non-blocking)

---

## 9. Deployment Checklist

### 9.1 Database Setup

```sql
-- 1. Create table
CREATE TABLE demandes_devis (...);

-- 2. Update ENUM
ALTER TABLE utilisateurs MODIFY COLUMN role ENUM(...);

-- 3. Verify
SELECT COUNT(*) FROM demandes_devis;
SELECT DISTINCT role FROM utilisateurs;
```

### 9.2 File System

```bash
# Create new PHP files
touch /var/www/html/GOL/inscription_promoteur.php
touch /var/www/html/GOL/tableau_bord_promoteur.php
touch /var/www/html/GOL/gestion_devis.php
touch /var/www/html/GOL/demande_devis.php

# Set permissions
chmod 644 /var/www/html/GOL/*.php
chmod 755 /var/www/html/GOL/includes/
```

### 9.3 Configuration

- Email sending: Verify `mail()` configured or SMTP setup
- CSRF tokens: Verify session configured in config.php
- File uploads: Verify permissions for `/uploads/` directory

### 9.4 Verification

- [ ] Database table exists and indexed
- [ ] All PHP files executable
- [ ] Navigation links working
- [ ] CSRF tokens generated correctly
- [ ] Email sending works
- [ ] Logs are written to `logs_activite`
- [ ] Responsive design verified on mobile/tablet/desktop

---

## 10. Future Enhancements

1. **Quote Template System** - Admin can customize email templates
2. **Automated Follow-up** - Emails sent at intervals if status not updated
3. **Quote Analytics Dashboard** - Charts: conversion rate, response time, source
4. **Bulk Import** - Import quote requests from CSV
5. **Integration with CRM** - Sync with HubSpot/Salesforce
6. **SMS Notifications** - Send SMS to admin when new request arrives
7. **Self-Service Quote Builder** - Automated pricing based on institution size
8. **Multi-language Support** - Form in French/English/Spanish

---

## Summary

Ce design technique fournit une architecture complète et sécurisée pour:

1. ✅ Permettre aux visiteurs de créer des comptes Promoteur limités
2. ✅ Offrir un tableau de bord en lecture seule avec découverte de modules
3. ✅ Collecter les demandes de devis via un formulaire public
4. ✅ Permettre aux Super Admins de gérer les demandes en centralisé
5. ✅ Protéger contre CSRF, SQL injection, XSS
6. ✅ Maintenir la compatibilité avec le code existant
7. ✅ Supporter le responsive design sur tous les appareils
8. ✅ Fournir une UX claire et intuitive

Le design suit les patterns établis dans GOL (PDO, session management, role-based access) et s'intègre seamlessly avec l'architecture existante.

