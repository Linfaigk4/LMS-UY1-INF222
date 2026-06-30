# Implementation Plan: Promoter Workflow & Quote Management

## Overview

Ce plan d'implémentation décompose le feature `promoter-workflow-quotes` en tâches codage séquentielles. Le workflow couvre 7 phases: infrastructure DB, backend fondation, authentification promoteur, système de devis, interface admin, intégration UI, et tests de sécurité.

Chaque tâche inclut critères d'acceptation clairs, fichiers affectés, dépendances, et estimation de complexité.

---

## Tasks

### Phase 1: Setup & Infrastructure

- [ ] 1. Créer la table `demandes_devis` en base de données
  - Créer migration SQL pour table `demandes_devis` avec toutes les colonnes, indexes, et types
  - Modifier l'ENUM `utilisateurs.role` pour ajouter 'promoteur'
  - Exécuter migration et vérifier structure
  - _Fichiers: database.sql (ou migration script)_
  - _Dépendances: Aucune_
  - _Complexité: Simple_

- [ ] 2. Ajouter les fonctions utilitaires à `includes/fonctions.php`
  - Ajouter `estPromoteur()` - vérifie rôle promoteur
  - Ajouter `estPromoteurOuEnseignant()` - vérifie promoteur OU enseignant
  - Ajouter `validerEmailDevi($email)` - validation format email
  - Ajouter `validerTelephoneDevi($telephone)` - validation format téléphone
  - Ajouter `compterDemandesDeviParStatut($statut)` - compte devis par état
  - _Fichiers: includes/fonctions.php_
  - _Dépendances: Tâche 1_
  - _Complexité: Simple_

- [ ] 3. Ajouter les fonctions de gestion des devis à `includes/fonctions.php`
  - Ajouter `creerDemandeDevi($data)` - insère nouvelle demande avec validation complète
  - Ajouter `obtenirDemandesDevis($filters, $limit, $offset)` - récupère avec filtrage/pagination
  - Ajouter `obtenirDemandeDevi($id_demande)` - récupère une demande spécifique
  - Ajouter `modifierDemandeDevi($id_demande, $statut, $notes_admin)` - modifie statut/notes
  - Ajouter `supprimerDemandeDevi($id_demande)` - supprime une demande
  - Ajouter `verifierDoublonDemandeDevi($email)` - vérifie doublon 5 min
  - _Fichiers: includes/fonctions.php_
  - _Dépendances: Tâche 1, 2_
  - _Complexité: Medium_

- [ ] 4. Ajouter la fonction d'email à `includes/fonctions.php`
  - Ajouter `envoyerEmailConfirmationDevis($email, $prenom, $nom_etablissement, $telephone)` - envoie email de confirmation
  - Template HTML du mail avec branding GOL
  - Gestion erreurs d'envoi (log + notification)
  - _Fichiers: includes/fonctions.php_
  - _Dépendances: Tâche 1, 3_
  - _Complexité: Medium_

- [ ] 5. Checkpoint - Vérifier toutes les fonctions
  - Exécuter chaque fonction avec données de test
  - Vérifier pas d'erreurs PHP/SQL
  - Vérifier retours des fonctions attendus
  - _Critères: Aucune erreur, toutes les fonctions opérationnelles_

---

### Phase 2: Backend Foundation - AJAX & API

- [ ] 6. Ajouter les endpoints AJAX à `ajax.php`
  - Ajouter endpoint `action=count_pending_quotes` - retourne nombre demandes en attente
  - Ajouter endpoint `action=search_quotes` - recherche avec filtres JSON
  - Ajouter endpoint `action=update_quote_status` - met à jour statut
  - Chaque endpoint retourne JSON cohérent avec gestion erreurs
  - _Fichiers: ajax.php_
  - _Dépendances: Tâche 3, 4_
  - _Complexité: Medium_

- [ ] 7. Checkpoint - Tester les endpoints AJAX
  - Tester chaque endpoint avec curl/Postman
  - Vérifier JSON valide, codes HTTP corrects
  - Vérifier erreurs CSRF/auth gérées
  - _Critères: Tous les endpoints fonctionnels_

---

### Phase 3: Authentication & User Pages

- [ ] 8. Créer la page d'inscription Promoteur `/inscription_promoteur.php`
  - Créer page avec formulaire: email, prenom, nom, password, confirm_password
  - Ajouter CSRF token via `champCSRF()`
  - Implémenter validation serveur (email unique, password 8+ chars)
  - Implémenter création utilisateur avec role='promoteur'
  - Implémenter session regeneration et redirection vers dashboard
  - Gestion erreurs (messages d'erreur inline)
  - _Fichiers: inscription_promoteur.php, includes/fonctions.php (si besoin helper)_
  - _Dépendances: Tâche 1, 2_
  - _Complexité: Medium_

- [ ] 9. Créer la page de choix d'inscription `/choix_inscription.php` (modification)
  - Ajouter bouton/option "Promoteur" au formulaire de choix
  - Ajouter lien vers `inscription_promoteur.php`
  - Ajouter description "Explorez la plateforme sans engagement"
  - Styling cohérent avec style.css existant
  - _Fichiers: choix_inscription.php_
  - _Dépendances: Tâche 8_
  - _Complexité: Simple_

- [ ] 10. Créer le tableau de bord Promoteur `/tableau_bord_promoteur.php`
  - Implémenter contrôle accès: `if (!estPromoteur()) redirect`
  - Ajouter section statistiques (4 cartes: modules, cours, users, progression)
  - Ajouter section "Découvrir les modules" avec pagination (6 modules/page)
  - Ajouter section "Fonctionnalités verrouillées" avec badges 🔒
  - Responsive design: 1-col mobile, 2-col tablet, 3-4-col desktop
  - _Fichiers: tableau_bord_promoteur.php, assets/css/style.css_
  - _Dépendances: Tâche 2, 8_
  - _Complexité: Medium_

- [ ] 11. Checkpoint - Tester authentification Promoteur
  - S'inscrire comme Promoteur
  - Vérifier accès au dashboard (accès autorisé)
  - Vérifier refus accès pour non-Promoteur
  - Vérifier données dashboard affichées
  - _Critères: Authentification + redirection fonctionnelle_

---

### Phase 4: Quote Request System

- [ ] 12. Créer le formulaire public de demande de devis `/demande_devis.php`
  - Créer page/modal avec tous les champs: prenom, nom, email, telephone, établissement, ville, pays, nombre_etudiants, nombre_enseignants, besoins (checkboxes), message_additionnel
  - Ajouter CSRF token
  - Implémenter validation côté serveur (email, téléphone, besoins)
  - Implémenter vérification doublon 5 min
  - Implémenter appel `creerDemandeDevi()`
  - Implémenter succès message + redirect
  - _Fichiers: demande_devis.php, assets/js/app.js (validation JS)_
  - _Dépendances: Tâche 3, 4_
  - _Complexité: Medium_

- [ ] 13. Modifier `/index.php` pour ajouter bouton de demande de devis
  - Ajouter bouton "Demander un devis" visible dans navbar/hero
  - Bouton ouvre modal ou navigue vers `/demande_devis.php`
  - Styling cohérent
  - _Fichiers: index.php, assets/css/style.css_
  - _Dépendances: Tâche 12_
  - _Complexité: Simple_

- [ ] 14. Implémenter l'envoi d'emails de confirmation
  - Vérifier fonction `envoyerEmailConfirmationDevis()` fonctionnelle
  - Configurer email sender (address, headers)
  - Tester envoi email avec données test
  - Implémenter fallback + notification admin si envoi échoue
  - _Fichiers: includes/fonctions.php_
  - _Dépendances: Tâche 4_
  - _Complexité: Simple_

- [ ] 15. Ajouter les notifications admin aux demandes de devis
  - Quand demande créée: créer notification "Nouvelle demande de devis"
  - Quand statut modifié: créer notification "Demande mise à jour"
  - Notifications liées à `/gestion_devis.php?id=X`
  - _Fichiers: includes/fonctions.php (dans creerDemandeDevi + modifierDemandeDevi)_
  - _Dépendances: Tâche 3_
  - _Complexité: Simple_

- [ ] 16. Checkpoint - Tester cycle demande de devis complet
  - Soumettre demande de devis avec tous les champs
  - Vérifier insertion BD
  - Vérifier email reçu
  - Vérifier notification admin créée
  - Vérifier doublon refusé après 2e soumission
  - _Critères: Cycle complet fonctionnel_

---

### Phase 5: Admin Management Interface

- [ ] 17. Créer l'interface de gestion des devis `/gestion_devis.php`
  - Implémenter contrôle accès: `if (!estSuperAdmin()) redirect`
  - Créer table avec colonnes: ID, Institution, Email, Date, Statut (badge coloré), Actions
  - Implémenter pagination (20 items/page)
  - Implémenter filtres: statut dropdown, date range
  - Implémenter recherche: email, nom, établissement
  - _Fichiers: gestion_devis.php, assets/css/style.css_
  - _Dépendances: Tâche 3_
  - _Complexité: Medium_

- [ ] 18. Ajouter modal/page de détails pour chaque demande dans `gestion_devis.php`
  - Créer modal avec tous les champs en read-only
  - Ajouter dropdown statut (editable)
  - Ajouter textarea notes admin (editable)
  - Ajouter boutons: "Sauvegarder", "Supprimer"
  - Implémenter appels `modifierDemandeDevi()` + `supprimerDemandeDevi()`
  - Gestion erreurs + success notifications
  - _Fichiers: gestion_devis.php, assets/js/app.js_
  - _Dépendances: Tâche 17_
  - _Complexité: Medium_

- [ ] 19. Ajouter lien "Gestion des devis" au menu administration
  - Modifier `/includes/header.php` pour ajouter lien (super_admin uniquement)
  - Lien pointe vers `/gestion_devis.php`
  - Afficher badge avec nombre demandes en attente (optionnel)
  - _Fichiers: includes/header.php, assets/js/app.js (optionnel badge AJAX)_
  - _Dépendances: Tâche 17_
  - _Complexité: Simple_

- [ ] 20. Checkpoint - Tester interface admin complète
  - Accès `gestion_devis.php` (super_admin)
  - Accès refusé (autre rôle)
  - Voir liste 20 demandes avec pagination
  - Filtrer par statut
  - Chercher par email
  - Ouvrir modal détails
  - Modifier statut + sauvegarder
  - Vérifier notification mise à jour
  - _Critères: Admin interface opérationnelle_

---

### Phase 6: Integration & Polish

- [ ] 21. Implémenter les redirects de contrôle d'accès Promoteur
  - Modifier `/creer_cours.php`: vérifier promoteur → redirect + message
  - Modifier `/gestion_cours.php`: vérifier promoteur → redirect
  - Modifier `/administration.php`: vérifier promoteur → redirect
  - Modifier `/lecon.php`: si promoteur tenté accès → afficher message verrouillé + bouton devis
  - _Fichiers: creer_cours.php, gestion_cours.php, administration.php, lecon.php_
  - _Dépendances: Tâche 2_
  - _Complexité: Simple_

- [ ] 22. Implémenter les badges "Verrouillé" pour lecteur Promoteur
  - Modifier `/module.php`: afficher message verrouillé si promoteur accède cours
  - Modifier `/cours.php`: afficher message verrouillé si promoteur accède leçon
  - Message standardisé: "Cette fonctionnalité est réservée aux utilisateurs payants. Demandez un devis pour accéder aux cours."
  - Bouton "Demander un devis" dans message verrouillé
  - _Fichiers: module.php, cours.php_
  - _Dépendances: Tâche 2_
  - _Complexité: Simple_

- [ ] 23. Optimiser responsive design du dashboard Promoteur
  - Tester à 320px (mobile), 480px (mobile), 768px (tablet), 1024px+ (desktop)
  - Ajuster layouts: 1-col mobile, 2-col tablet, 3-4-col desktop
  - Vérifier touch targets min 44px
  - Vérifier font-size min 16px
  - Vérifier navigation hamburger menu mobile
  - _Fichiers: tableau_bord_promoteur.php, assets/css/style.css_
  - _Dépendances: Tâche 10_
  - _Complexité: Medium_

- [ ] 24. Optimiser responsive design du formulaire demande devis
  - Tester à 320px, 480px, 768px, 1024px+
  - Layout: 1-col mobile, 2-col tablet/desktop
  - Ajuster taille inputs, buttons
  - Vérifier labels accessibles
  - _Fichiers: demande_devis.php, assets/css/style.css_
  - _Dépendances: Tâche 12_
  - _Complexité: Simple_

- [ ] 25. Ajouter notifications toast/success pour toutes les actions
  - Intégrer notifications AJAX success/error dans `afficherNotification()` existant
  - Inscription promoteur: success message
  - Demande devis: success message + confirmation
  - Modification devis (admin): success message
  - Suppression devis (admin): success message
  - _Fichiers: assets/js/app.js_
  - _Dépendances: Tâche 8, 12, 18_
  - _Complexité: Simple_

- [ ] 26. Checkpoint - Tester intégration UI complète
  - Promoteur accès pages: inscription, dashboard, modules
  - Promoteur tentent créer cours → redirect OK
  - Visiteur soumet devis → success + email
  - Admin gère devis → success notifications
  - Responsive 320px/480px/768px/1024px
  - _Critères: UI complète, responsive, redirects OK_

---

### Phase 7: Security & Testing

- [ ] 27. Implémenter validation CSRF pour tous les formulaires
  - Vérifier `champCSRF()` dans tous les forms POST: inscription_promoteur.php, demande_devis.php, gestion_devis.php
  - Vérifier `verifierTokenCSRF()` côté serveur pour chaque POST
  - Test: soumettre form sans token → erreur 403
  - _Fichiers: inscription_promoteur.php, demande_devis.php, gestion_devis.php, includes/fonctions.php_
  - _Dépendances: Tâche 8, 12, 17_
  - _Complexité: Simple_

- [ ] 28. Implémenter validation input + sanitization pour tous les formulaires
  - Validation côté serveur: email format, phone format, besoins array, nombres positifs
  - Sanitization: `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` avant INSERT/UPDATE
  - Vérifier no XSS possible (affiches données en admin)
  - _Fichiers: inscription_promoteur.php, demande_devis.php, gestion_devis.php, includes/fonctions.php_
  - _Dépendances: Tâche 3, 8, 12_
  - _Complexité: Medium_

- [ ] 29. Vérifier toutes les requêtes SQL utilisent PDO prepared statements
  - Audit: aucune concaténation de variables dans SQL
  - Audit: tous les placeholders nommés (`:param`) ou positionnels (?)
  - Audit: valeurs bindées avec `execute([...])`
  - _Fichiers: includes/fonctions.php, ajax.php, inscription_promoteur.php, demande_devis.php, gestion_devis.php_
  - _Dépendances: Tâche 3, 6_
  - _Complexité: Medium_

- [ ] 30. Implémenter écriture de logs pour actions sensibles
  - Log création demande devis
  - Log modification statut devis
  - Log suppression devis
  - Log tentatives CSRF échouées
  - Utiliser `logs_activite` table existante
  - _Fichiers: includes/fonctions.php_
  - _Dépendances: Tâche 3_
  - _Complexité: Simple_

- [ ] 31. Écrire tests unitaires pour les fonctions critiques
  - Tester `estPromoteur()` - rôle vérifié
  - Tester `verifierDoublonDemandeDevi()` - doublon en <5min détecté
  - Tester `validerEmailDevi()` - formats valides/invalides
  - Tester `validerTelephoneDevi()` - formats valides/invalides
  - Tester `creerDemandeDevi()` - validation, doublon, insert
  - _Fichiers: tests/unit/ (créer)_
  - _Dépendances: Tâche 2, 3_
  - _Complexité: Medium_

- [ ] 32. Écrire tests d'intégration pour les workflows complets
  - Test: inscription promoteur → dashboard accessible
  - Test: demande devis → email envoyé + notification créée
  - Test: admin modifie statut → notification + log créés
  - Test: promoteur tentent créer cours → redirect
  - _Fichiers: tests/integration/_
  - _Dépendances: Tâche 8, 12, 17_
  - _Complexité: Medium_

- [ ] 33. Checkpoint - Tests de sécurité et validation
  - Tester CSRF: soumettre form sans token → erreur
  - Tester SQL injection: soumettre email "test'; DROP TABLE--" → pas d'erreur DB
  - Tester XSS: soumettre message "<script>alert('xss')</script>" → échappé en affichage
  - Tester validation: email invalide → erreur; phone invalide → erreur
  - Tester access control: promoteur tentent admin → redirect
  - Exécuter tests unitaires + intégration
  - _Critères: Tous les tests passent, pas de vulnérabilités_

---

### Phase 8: Final Verification & Documentation

- [ ] 34. Vérifier pas de régressions sur fonctionnalités existantes
  - Tester inscription Étudiant (Req 1 ne change rien)
  - Tester inscription Enseignant (Req 1 ne change rien)
  - Tester Dashboard Enseignant (Req 1 ne change rien)
  - Tester Dashboard Étudiant (Req 1 ne change rien)
  - Tester gestion cours (Req 9 ajoute redirects seulement)
  - Tester gestion quiz (toujours accessible)
  - _Critères: Zéro régressions_

- [ ] 35. Documenter nouvelles routes et fonctions
  - Ajouter doc dans `/docs/API_AJAX.md`: endpoints demande devis
  - Ajouter doc dans `/docs/ARCHITECTURE.md`: nouvelle table + layer
  - Ajouter doc dans `/docs/FONCTIONS_PHARES.md`: 9 nouvelles fonctions
  - Créer `/docs/GUIDE_PROMOTEUR.md`: guide utilisateur promoteur
  - _Fichiers: docs/**_
  - _Dépendances: Toutes les tâches_
  - _Complexité: Simple_

- [ ] 36. Checkpoint final - Vérifier tous les requirements couverts
  - Req 1 ✓: Création compte Promoteur
  - Req 2 ✓: Dashboard Promoteur avec stats + modules + locked
  - Req 3 ✓: Accès module/cours avec messages verrouillés
  - Req 4 ✓: Formulaire demande devis
  - Req 5 ✓: Table `demandes_devis` en BD
  - Req 6 ✓: Interface admin gestion devis
  - Req 7 ✓: Notifications admin
  - Req 8 ✓: Suppression devis
  - Req 9 ✓: Permission model Promoteur
  - Req 10 ✓: CSRF protection
  - Req 11 ✓: PDO prepared statements
  - Req 12 ✓: Input validation
  - Req 13 ✓: Email confirmation
  - Req 14 ✓: Compat auth existante
  - Req 15 ✓: Responsive design
  - Req 16 ✓: Doublon prevention
  - _Critères: Tous les 16 requirements couverts et testés_

---

## Notes

### Architecture Générale

```
Database Layer (Phase 1):
├─ Table demandes_devis
└─ ENUM role + 'promoteur'

Business Logic (Phase 2-3):
├─ Fonctions.php (9 nouvelles)
└─ AJAX endpoints (6)

Presentation Layer (Phase 3-6):
├─ Pages Promoteur (3 nouvelles)
├─ Pages Admin (1 nouvelle)
└─ Modifications pages existantes (5)

Security Layer (Phase 7):
├─ CSRF protection
├─ Input validation + sanitization
├─ SQL injection prevention (PDO)
└─ Access control (estPromoteur, estSuperAdmin)

Testing Layer (Phase 7-8):
├─ Unit tests (fonctions)
├─ Integration tests (workflows)
└─ Security tests (CSRF, XSS, SQL injection)
```

### Dépendances entre Phases

- **Phase 1** (Infrastructure): Aucune dépendance
- **Phase 2** (Backend): Dépend Phase 1
- **Phase 3** (Auth): Dépend Phase 1-2
- **Phase 4** (Devis): Dépend Phase 1-2
- **Phase 5** (Admin): Dépend Phase 1-4
- **Phase 6** (UI): Dépend Phase 3-5
- **Phase 7** (Sécurité): Dépend Phase 3-6
- **Phase 8** (Final): Dépend Phase 1-7

### Points Critiques de Validation

1. **Après Tâche 5**: Toutes les fonctions DB opérationnelles
2. **Après Tâche 11**: Authentification Promoteur fonctionnelle
3. **Après Tâche 16**: Cycle demande devis complet
4. **Après Tâche 20**: Interface admin complète
5. **Après Tâche 26**: UI responsive + intégration OK
6. **Après Tâche 33**: Sécurité + tests passants
7. **Après Tâche 36**: Zéro régressions + tous les requirements

### Fichiers Affectés

**Nouveaux fichiers**: 4
- `/inscription_promoteur.php`
- `/tableau_bord_promoteur.php`
- `/demande_devis.php`
- `/gestion_devis.php`

**Fichiers modifiés**: 9
- `database.sql` (ou migration)
- `includes/fonctions.php` (+9 fonctions)
- `includes/header.php` (+1 lien admin)
- `choix_inscription.php` (+1 bouton)
- `index.php` (+1 bouton)
- `creer_cours.php` (+1 redirect check)
- `gestion_cours.php` (+1 redirect check)
- `administration.php` (+1 redirect check)
- `lecon.php` (+1 lock message)
- `module.php` (+1 lock message)
- `cours.php` (+1 lock message)
- `ajax.php` (+3 endpoints)
- `assets/js/app.js` (+notifications, validation)
- `assets/css/style.css` (+responsive layouts)

**Documentation**: 5
- `/docs/API_AJAX.md` (mise à jour)
- `/docs/ARCHITECTURE.md` (mise à jour)
- `/docs/FONCTIONS_PHARES.md` (mise à jour)
- `/docs/GUIDE_PROMOTEUR.md` (nouveau)
- Test files (nouveaux)

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": 0,
      "tasks": ["1", "2", "3", "4"]
    },
    {
      "id": 1,
      "tasks": ["5", "6"]
    },
    {
      "id": 2,
      "tasks": ["8", "9"]
    },
    {
      "id": 3,
      "tasks": ["10", "12", "14", "15"]
    },
    {
      "id": 4,
      "tasks": ["11", "13", "16"]
    },
    {
      "id": 5,
      "tasks": ["17", "18"]
    },
    {
      "id": 6,
      "tasks": ["19", "21", "22"]
    },
    {
      "id": 7,
      "tasks": ["23", "24", "25"]
    },
    {
      "id": 8,
      "tasks": ["27", "28", "29", "30"]
    },
    {
      "id": 9,
      "tasks": ["31", "32"]
    },
    {
      "id": 10,
      "tasks": ["34", "35"]
    }
  ]
}
```

---

## Summary

Ce plan couvre 36 tâches codage réparties en 8 phases, avec:
- **7 checkpoints** intermédiaires pour valider le progrès
- **16 requirements** adressés par les tâches
- **4 fichiers créés** (pages PHP)
- **12+ fichiers modifiés** (fonctions, styles, redirects)
- **Dépendances claires** entre tâches (11 waves d'exécution)
- **Tests inclus** (unitaires, intégration, sécurité)
- **Estimation**: ~120-150 heures dev (basé sur complexité Medium/Simple)

Chaque tâche est actionnable, avec critères d'acceptation clairs et dépendances explicites. Les checkpoints permettent validation incrémentale du feature.
