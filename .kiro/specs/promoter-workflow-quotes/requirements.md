# Requirements Document: Promoter Workflow & Quote Management

## Introduction

Cette spécification définit les exigences fonctionnelles pour deux features interdépendantes sur la plateforme GOL:

1. **Parcours Promoteur (Promoter Dashboard)**: Un espace personnel limité permettant aux promoteurs de découvrir la plateforme sans accès aux fonctionnalités de gestion complètes
2. **Gestion des Demandes de Devis (Quote Request Management)**: Un système permettant aux visiteurs de soumettre des demandes de tarification et aux administrateurs de gérer ces demandes

Ces fonctionnalités visent à augmenter l'engagement des visiteurs et à convertir les demandes de devis en clients payants.

---

## Glossary

- **Promoter**: Rôle utilisateur ayant accès à un tableau de bord limité en lecture seule
- **Quote Request**: Demande de tarification/devis soumise par un visiteur ou établissement
- **Dashboard**: Tableau de bord affichant des statistiques et aperçus en lecture seule
- **Module**: Unité pédagogique (ensemble de cours)
- **Course**: Cours appartenant à un module
- **Super Admin**: Administrateur système avec accès complet
- **Lock Badge**: Badge/message indiquant qu'une fonctionnalité est verrouillée (non disponible pour Promoteur)
- **Request Status**: État d'une demande de devis (Pending, Contacted, Negotiating, Accepted, Declined)
- **Educational Institution**: Établissement d'enseignement (école, université, centre de formation)
- **Quote Notification**: Alerte/notification lorsqu'une nouvelle demande de devis est reçue

---

## Requirements

### Requirement 1: Promoter Account Creation

**User Story**: As a visitor, I want to create a Promoter account easily, so that I can explore the platform features without full commitment.

#### Acceptance Criteria

1. WHEN a visitor navigates to `/choix_inscription.php`, THE System SHALL display a "Promoter" account type option alongside existing options (Student, Teacher)
2. WHEN a visitor selects "Promoter" account type, THE System SHALL redirect to a Promoter-specific registration form at `/inscription_promoteur.php`
3. WHEN a Promoter completes the registration form (email, password, first name, last name), THE System SHALL validate all fields using the same rules as Student/Teacher registration
4. WHEN registration is successful, THE System SHALL create a Promoter user record with `role='promoteur'` in the `utilisateurs` table
5. WHEN registration is successful, THE System SHALL set session variables (`$_SESSION['role']='promoteur'`, `$_SESSION['id_utilisateur']`, etc.) and redirect to the Promoter dashboard
6. IF email already exists, THEN THE System SHALL return an error message "Cet email est déjà utilisé"
7. IF password is less than 8 characters, THEN THE System SHALL return an error message "Le mot de passe doit contenir au moins 8 caractères"
8. THE Registration Form SHALL include CSRF protection token via `champCSRF()` helper

### Requirement 2: Promoter Dashboard (Read-Only Overview)

**User Story**: As a Promoter, I want to access a dashboard showing platform statistics and available modules, so that I can understand the platform's capabilities.

#### Acceptance Criteria

1. WHEN a Promoter logs in, THE System SHALL display a `/tableau_bord_promoteur.php` page (accessible only to role='promoteur')
2. IF non-Promoter tries to access `/tableau_bord_promoteur.php`, THEN THE System SHALL redirect to `/tableau_bord.php` or `/connexion.php`
3. THE Dashboard SHALL display read-only statistics cards showing:
   - Total number of available modules (count from `modules` where `actif=TRUE`)
   - Total number of available courses (count from `cours` where `statut='publie'`)
   - Total number of active users (count from `utilisateurs` where `statut='actif'`)
   - Average course completion rate across all students (aggregate from `inscriptions_modules.progression_globale`)
4. WHEN Dashboard loads, THE Statistics_Cards SHALL display data calculated from database aggregates (no hardcoded values)
5. THE Dashboard SHALL include a "Browse Modules" section displaying module cards (max 6 modules per page) with:
   - Module name (`nom_module`)
   - Module description (first 150 characters truncated with "...")
   - Difficulty level (`niveau` field)
   - Total number of courses in the module
   - "View Details" button linking to `/module.php?id=X`
6. THE Dashboard SHALL include pagination controls (if modules > 6) using GET parameter `?page=N`
7. THE Dashboard SHALL have a "Feature Unlock" section explaining premium features not available for Promoters:
   - Course creation, student management, grading, certificate generation
   - Each feature SHALL display a "Locked" badge and brief description
8. THE Dashboard header/navbar SHALL display Promoter role indication
9. THE Dashboard SHALL display user's profile information (email, name, registration date) in a sidebar or profile card
10. THE Dashboard page SHALL be fully responsive (mobile-first design, tested at 320px, 480px, 768px breakpoints)

### Requirement 3: Module Discovery (Promoter Access)

**User Story**: As a Promoter, I want to view module details and course listings, so that I can evaluate the platform's content quality.

#### Acceptance Criteria

1. WHEN a Promoter accesses `/module.php?id=X`, THE System SHALL display module details including:
   - Module name, description, objectives, difficulty level
   - Total course count, total student enrollments (aggregate from `inscriptions_modules`)
   - Average module completion rate
   - List of all published courses in the module
2. WHEN a Promoter clicks on a course from the module listing, THE System SHALL redirect to `/cours.php?id=X`
3. WHEN a Promoter accesses `/cours.php?id=X`, THE System SHALL display course details including:
   - Course title, description, objectives, difficulty level
   - Teacher name (from `utilisateurs` table)
   - Total lesson count, estimated duration
   - List of lessons (title only, no content access)
4. WHEN a Promoter tries to access `/lecon.php?id=X`, THE System SHALL display a lock message and offer to upgrade
5. THE Lock Message SHALL read: "Cette fonctionnalité est réservée aux utilisateurs payants. Demandez un devis pour accéder aux cours."
6. WHEN a Promoter tries to access Lesson content, course creation, or student management features, THE System SHALL display the lock message with a button linking to "Request a Quote"

### Requirement 4: Quote Request Form (Visitor Access)

**User Story**: As a school administrator or institution representative, I want to submit a quote request easily from the homepage, so that I can inquire about platform pricing and features.

#### Acceptance Criteria

1. WHEN a visitor is on `/index.php`, THE System SHALL display a "Request a Quote" button or link in the main navigation or hero section
2. WHEN visitor clicks "Request a Quote", THE System SHALL open a modal dialog (or navigate to `/demande_devis.php`) with a form
3. THE Quote Request Form SHALL include the following required fields:
   - First name (`prenom`) — text input, max 100 characters
   - Last name (`nom`) — text input, max 100 characters
   - Email address (`email`) — email input, validated with `filter_var(..., FILTER_VALIDATE_EMAIL)`
   - Phone number (`telephone`) — text input, max 20 characters (optional)
   - Institution/Organization name (`nom_etablissement`) — text input, max 255 characters
   - City (`ville`) — text input, max 100 characters (optional)
   - Country (`pays`) — dropdown select or text input
   - Approximate number of students (`nombre_etudiants`) — numeric input or select range (0-50, 50-100, 100-500, 500+)
   - Approximate number of teachers (`nombre_enseignants`) — numeric input or select range (same ranges as students)
   - Needs/Requirements (`besoins`) — multi-select checkboxes OR textarea for free text (max 1000 characters):
     - Learning management
     - Student assessment & grading
     - Certificate generation
     - Mobile app support
     - Video hosting & delivery
     - Integration with existing systems
     - Other (free text field)
   - Additional message (`message_additionnel`) — textarea, max 2000 characters (optional)
4. THE Form SHALL include CSRF protection token via `champCSRF()` helper
5. WHEN form is submitted with all required fields filled, THE System SHALL validate inputs:
   - Email must be valid format
   - Phone must contain only digits and + characters (if provided)
   - Numbers must be positive integers
6. IF validation fails, THE System SHALL display inline error messages beneath each invalid field
7. WHEN form is submitted successfully, THE System SHALL:
   - Insert a record into a new table `demandes_devis` with all form data
   - Set `statut='en_attente'`
   - Set `date_creation=CURRENT_TIMESTAMP`
   - Generate a unique `id_demande` (auto-increment)
8. WHEN quote request is successfully submitted, THE System SHALL:
   - Display a success message: "Votre demande de devis a été reçue. Nous vous contacterons dans les 48 heures."
   - Send an automatic confirmation email to the visitor's email address
   - Redirect visitor to homepage or display modal close
9. THE Form SHALL be optimized for mobile (single-column layout, large touch targets)
10. THE Form SHALL prevent duplicate submissions within 5 minutes (use session-based tracking or database timestamp check)

### Requirement 5: Quote Request Database Table

**User Story**: As a database designer, I want to persist all quote request data securely, so that Admin can retrieve and process requests.

#### Acceptance Criteria

1. THE System SHALL create a new table `demandes_devis` with the following structure:
   - `id_demande` INT PRIMARY KEY AUTO_INCREMENT
   - `prenom` VARCHAR(100) NOT NULL
   - `nom` VARCHAR(100) NOT NULL
   - `email` VARCHAR(255) NOT NULL
   - `telephone` VARCHAR(20) NULL
   - `nom_etablissement` VARCHAR(255) NOT NULL
   - `ville` VARCHAR(100) NULL
   - `pays` VARCHAR(100) NOT NULL
   - `nombre_etudiants` VARCHAR(50) — store as range or number
   - `nombre_enseignants` VARCHAR(50) — store as range or number
   - `besoins` JSON — store selected needs as JSON array
   - `message_additionnel` TEXT NULL
   - `statut` ENUM('en_attente', 'contacte', 'en_negociation', 'accepte', 'refuse') DEFAULT 'en_attente'
   - `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
   - `date_modification` DATETIME ON UPDATE CURRENT_TIMESTAMP
   - `date_traitement` DATETIME NULL
   - `notes_admin` TEXT NULL
   - INDEX idx_statut (statut)
   - INDEX idx_email (email)
   - INDEX idx_date_creation (date_creation DESC)
2. THE Table SHALL use UTF-8 charset and InnoDB engine (consistent with existing tables)
3. THE Table SHALL have appropriate indexes for fast filtering and searching

### Requirement 6: Admin Interface for Quote Requests

**User Story**: As a Super Admin, I want to view and manage all quote requests in a single interface, so that I can follow up with interested institutions.

#### Acceptance Criteria

1. WHEN a Super Admin logs in, THE System SHALL display a new "Quote Requests" option in the administration menu/sidebar
2. WHEN Super Admin clicks "Quote Requests", THE System SHALL navigate to `/gestion_devis.php` (accessible only to role='super_admin')
3. IF non-Super-Admin tries to access `/gestion_devis.php`, THEN THE System SHALL redirect to `/tableau_bord.php`
4. THE Quote Requests Page SHALL display a paginated table with the following columns:
   - ID (clickable, links to details)
   - Institution name (`nom_etablissement`)
   - Contact email (`email`)
   - Submission date (`date_creation`, formatted as "DD/MM/YYYY HH:MM")
   - Current status (`statut`, displayed as colored badge: gray=pending, blue=contacted, orange=negotiating, green=accepted, red=declined)
   - Last modified date (`date_modification`)
   - Action buttons: "View Details", "Delete"
5. THE Table SHALL display 20 requests per page with pagination controls
6. THE Table header SHALL include filters for:
   - Status dropdown (show all, pending, contacted, negotiating, accepted, declined)
   - Date range picker (from/to dates)
7. THE Table header SHALL include a search box for searching by:
   - Institution name (`nom_etablissement`)
   - Contact email (`email`)
   - Contact name (search both `prenom` and `nom` fields)
8. WHEN Admin clicks "View Details", THE System SHALL display a modal or separate page with full request information:
   - All form fields populated and read-only
   - Current status dropdown (editable)
   - Notes field (`notes_admin`) with textarea for admin notes (editable)
   - "Save Changes" button
   - "Delete Request" button with confirmation dialog
9. WHEN Admin changes the status dropdown and clicks "Save Changes", THE System SHALL:
   - Update the `statut` field in the database
   - Set `date_modification=CURRENT_TIMESTAMP`
   - Log the action in `logs_activite` table
   - Display a success toast notification
10. WHEN Admin updates notes and clicks "Save Changes", THE System SHALL:
    - Update the `notes_admin` field
    - Set `date_modification=CURRENT_TIMESTAMP`
    - Display a success notification

### Requirement 7: Quote Request Notifications

**User Story**: As a Super Admin, I want to be notified when new quote requests arrive, so that I can prioritize follow-up actions.

#### Acceptance Criteria

1. WHEN a new quote request is submitted via the form, THE System SHALL:
   - Create a notification record in the `notifications` table with:
     - `id_utilisateur` = Super Admin user ID
     - `titre` = "Nouvelle demande de devis"
     - `message` = "Nouvelle demande de {institution_name} de {city}, {country} reçue."
     - `type` = "info"
     - `est_lue` = FALSE
     - `date_creation` = CURRENT_TIMESTAMP
2. WHEN Super Admin logs in after a new request, THE System SHALL display an unread notification badge/indicator on the "Quote Requests" menu item (showing count of unread notifications)
3. WHEN Super Admin views the Quote Requests page, unread notifications related to quote requests SHALL be marked as read (`est_lue=TRUE`)
4. WHEN Admin navigates to Admin dashboard, THE System SHALL optionally display a widget showing latest 5 pending quote requests

### Requirement 8: Quote Request Deletion

**User Story**: As a Super Admin, I want to delete outdated or invalid quote requests, so that I can keep the request list clean.

#### Acceptance Criteria

1. WHEN Admin clicks "Delete Request" button in the details view, THE System SHALL display a confirmation dialog: "Êtes-vous sûr de vouloir supprimer cette demande? Cette action est irréversible."
2. IF Admin confirms deletion, THE System SHALL:
   - Delete the record from `demandes_devis` table
   - Log the deletion in `logs_activite` table
   - Display a success message: "Demande supprimée avec succès"
   - Return Admin to the Quote Requests listing page
3. IF Admin clicks "Cancel", THE System SHALL close the confirmation dialog without making changes

### Requirement 9: Security - Promoter Permission Model

**User Story**: As a security architect, I want to ensure Promoters cannot access restricted features, so that the platform remains secure.

#### Acceptance Criteria

1. WHEN a Promoter tries to create a course (`/creer_cours.php`), THE System SHALL check:
   - `estPromoteur()` function verifies role='promoteur'
   - Redirect to `/tableau_bord_promoteur.php` with error message: "Vous n'avez pas accès à cette fonctionnalité. Demandez un devis pour l'accès complet."
2. WHEN a Promoter tries to access `/gestion_cours.php`, THE System SHALL redirect with same message
3. WHEN a Promoter tries to access `/administration.php`, THE System SHALL redirect to `/tableau_bord_promoteur.php`
4. WHEN a Promoter tries to enroll in a course (via AJAX), THE System SHALL:
   - Check `estPromoteur()` in the AJAX handler
   - Return JSON: `{"success": false, "message": "Fonction réservée aux utilisateurs payants"}`
5. ALL Promoter-restricted redirects SHALL use `header('Location: ...')` with exit, never display restricted content
6. THE `estPromoteur()` function SHALL already exist in `fonctions.php` for consistency

### Requirement 10: Security - CSRF Protection for Quote Form

**User Story**: As a security engineer, I want to protect the quote request form from CSRF attacks, so that quote requests cannot be spoofed.

#### Acceptance Criteria

1. THE Quote Request Form SHALL include a CSRF token field generated by `champCSRF()` function
2. WHEN form is submitted (POST), THE System SHALL verify the CSRF token using `verifierTokenCSRF()` function
3. IF CSRF token is missing or invalid, THEN THE System SHALL:
   - Return HTTP 403 Forbidden
   - Log the failed attempt in `logs_activite`
   - Display no error message to user (silent failure for security)
4. IF CSRF token is valid and form is processed, THE token SHALL be regenerated for next use

### Requirement 11: Security - SQL Injection Prevention for Quote Management

**User Story**: As a data protection officer, I want all database queries to use parameterized statements, so that SQL injection is impossible.

#### Acceptance Criteria

1. ALL database queries for `demandes_devis` table SHALL use PDO prepared statements:
   - `$pdo->prepare()` with named placeholders (`:param_name`) or positional (`?`)
   - `execute([...])` with parameter values
2. WHEN searching by email or name, THE System SHALL use LIKE with escaped wildcards: `WHERE email LIKE :search` with bound value
3. NO raw SQL strings SHALL be concatenated with user input
4. ALL functions handling quote data SHALL follow PDO conventions established in `fonctions.php`

### Requirement 12: Security - Input Validation for Quote Form

**User Story**: As a security analyst, I want all quote form inputs validated, so that malicious data cannot be stored.

#### Acceptance Criteria

1. WHEN form is submitted, THE System SHALL validate:
   - Email: `filter_var($email, FILTER_VALIDATE_EMAIL)`
   - Phone: regex `/^[0-9+\-\(\)\s\.]+$/` (digits, +, -, (), spaces, dots only)
   - Names: max 100 characters, alphanumeric + spaces + accents
   - Institution name: max 255 characters
   - Numbers (students/teachers): positive integers only
   - Message: max 2000 characters
2. IF validation fails, THE System SHALL NOT insert data and return error message
3. BEFORE storing in database, THE System SHALL sanitize with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`
4. WHEN displaying data in admin interface, THE System SHALL escape output with `htmlspecialchars()` to prevent XSS

### Requirement 13: Email Notification for Quote Request Confirmation

**User Story**: As a quote requester, I want to receive an email confirming my request submission, so that I know it was received.

#### Acceptance Criteria

1. WHEN a quote request is successfully submitted, THE System SHALL send an email to the requester's email address
2. THE Email Subject SHALL be: "Confirmation de votre demande de devis - GOL Platform"
3. THE Email Body SHALL include:
   - Greeting: "Bonjour {prenom} {nom},"
   - Confirmation message: "Nous avons reçu votre demande de devis pour {nom_etablissement}."
   - "We will contact you within 48 hours at {telephone}" (if phone provided)
   - Contact email: "contact@gol-platform.com"
   - Footer with platform information
4. THE Email SHALL be sent using PHP `mail()` or a mail queue function (if available)
5. IF email sending fails, THE System SHALL:
   - Still insert the quote request in database
   - Log the error in application logs
   - Notify Super Admin via notification that email delivery failed
6. THE Email content SHALL be HTML-formatted and mobile-friendly

### Requirement 14: Compatibility with Existing Authentication System

**User Story**: As a platform integrator, I want Promoter role to work seamlessly with existing auth, so that no legacy code breaks.

#### Acceptance Criteria

1. THE Promoter role SHALL be added as a 4th value in the `utilisateurs.role` ENUM: `('super_admin', 'promoteur', 'enseignant', 'etudiant')`
2. THE `estConnecte()` function SHALL continue to work for all roles including Promoter
3. NEW functions SHALL be added to `fonctions.php`:
   - `estPromoteur()` — returns TRUE if `$_SESSION['role'] === 'promoteur'`
   - `estPromoteurOuEnseignant()` — returns TRUE if role is promoteur OR enseignant (for shared features)
4. THE Session system SHALL handle Promoter role same as other roles (set/unset `$_SESSION['role']` after login)
5. WHEN Promoter logs out, THE System SHALL destroy session and redirect to `/connexion.php` (same as other roles)
6. THE header/navbar SHALL display Promoter role in profile menu or role indicator
7. EXISTING role checks (e.g., `if (!estEnseignant() && !estSuperAdmin())`) SHALL continue working without modification

### Requirement 15: Responsive Design for Promoter Dashboard

**User Story**: As a mobile user, I want the Promoter dashboard to work on my phone, so that I can explore the platform on the go.

#### Acceptance Criteria

1. THE Dashboard page SHALL be tested and functional at breakpoints:
   - Mobile: 320px (iPhone SE), 480px (small Android)
   - Tablet: 768px (iPad)
   - Desktop: 1024px+ (standard desktop)
2. AT Mobile breakpoints (≤480px), THE Dashboard SHALL:
   - Display statistics in 1-column layout (not side-by-side)
   - Module cards shall be full-width
   - Action buttons shall have min-height 44px for touch accessibility
3. AT Tablet breakpoints (≤768px), THE Dashboard SHALL:
   - Display statistics in 2-column grid
   - Module cards in 2-column grid
4. AT Desktop breakpoints (>768px), THE Dashboard SHALL:
   - Display statistics in 4-column grid
   - Module cards in 3-column grid
5. THE Navigation menu SHALL:
   - Display horizontally on desktop (>768px)
   - Display as hamburger menu on mobile (≤768px) with slide-in sidebar
   - Use `aria-expanded` attribute for accessibility
6. ALL text SHALL be readable without zooming (min font-size 16px on mobile)
7. THE Form inputs SHALL stack vertically on mobile, horizontally on desktop (where appropriate)

### Requirement 16: Quote Request Form Duplicate Prevention

**User Story**: As a platform owner, I want to prevent spam/duplicate quote requests, so that the request list remains useful.

#### Acceptance Criteria

1. WHEN a visitor submits a quote request, THE System SHALL check if a request with the same email address was submitted within the last 5 minutes
2. IF a duplicate request is detected, THEN THE System SHALL display error message: "Une demande a déjà été soumise avec cette adresse email. Veuillez réessayer dans 5 minutes."
3. THE Duplicate check SHALL be based on email address + submission timestamp
4. THE Check SHALL query `demandes_devis` table: `WHERE email=:email AND date_creation > DATE_SUB(NOW(), INTERVAL 5 MINUTE)`
5. THE Error message SHALL be displayed to user WITHOUT creating a new record
6. LEGITIMATE repeat requests (after 5 minutes) SHALL be allowed

---

## Constraints & Dependencies

### Database Changes
- Add `demandes_devis` table (detailed in Requirement 5)
- Update `utilisateurs.role` ENUM to include 'promoteur'
- No changes to existing tables

### File System Changes
- Create new PHP page: `/inscription_promoteur.php`
- Create new PHP page: `/tableau_bord_promoteur.php`
- Create new PHP page: `/gestion_devis.php`
- Create new PHP page: `/demande_devis.php` (optional modal version)
- Update `/choix_inscription.php` to include Promoter option
- Update `/index.php` to include "Request a Quote" button
- Update `/includes/fonctions.php` to add Promoter-related functions
- Update `/includes/header.php` to show Promoter role in navigation

### Functions to Add/Modify
- Add `estPromoteur()` to `fonctions.php`
- Add `estPromoteurOuEnseignant()` to `fonctions.php`
- Add `inscrirePromoteur()` to `fonctions.php` (or use existing `inscrireUtilisateur()` with role parameter)
- Add `creerDemandeDevi()` (insert quote request)
- Add `obtenirDemandesDevis()` (fetch with filters/search)
- Add `modifierDemandeDevi()` (update status/notes)
- Add `supprimerDemandeDevi()` (delete request)
- Add `verifierDoublonDemandeDevi()` (check duplicate within 5 min)

### Dependencies on Existing Code
- Must use `champCSRF()` and `verifierTokenCSRF()` for CSRF protection
- Must use `estConnecte()`, `estSuperAdmin()` for authorization checks
- Must use PDO prepared statements (already established pattern)
- Must use `afficherNotification()` for toast notifications (from app.js)
- Must integrate with existing `notifications` table for admin alerts

---

## Testing Strategy (Informative)

### Unit Tests
- Quote form validation (email, phone, numbers)
- Duplicate prevention logic (5-minute check)
- Promoter permission model (role-based redirects)

### Integration Tests
- Quote request form submission → database insert
- Admin listing/filtering/search
- CSRF token validation
- Email notification sending

### Acceptance Tests
- Promoter account creation flow
- Promoter dashboard display (statistics, modules, lock badges)
- Admin quote request management (view, edit, delete)
- Mobile responsiveness

---

## Success Criteria

A feature is considered complete when:

1. All 16 requirements are implemented and tested
2. Promoter users can create accounts and access the dashboard
3. Visitors can submit quote requests from homepage
4. Admins can manage all quote requests in a centralized interface
5. CSRF, SQL injection, and XSS vulnerabilities are prevented
6. Mobile responsiveness verified at 320px, 480px, 768px
7. Duplicate quote submissions are prevented within 5-minute window
8. Email notifications work correctly
9. All database queries use PDO prepared statements
10. Existing functionality (Student, Teacher, Admin) continues to work without regression

