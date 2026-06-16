# PLAN DE REFACTORISATION CONTRÔLÉE — GOL LMS
**Version** : 1.0 — Pré-implémentation  
**Date** : 2026-06-15  
**Stratégie** : Refactorisation progressive sans rupture des fonctionnalités existantes

---

## PRINCIPES DIRECTEURS

1. **Sécuriser avant d'embellir** — corriger les bugs bloquants avant tout travail UI
2. **Une étape = un commit Git** — chaque étape est atomique et réversible
3. **Tester après chaque modification** — vérification PHP, SQL et AJAX systématique
4. **Ne jamais casser ce qui fonctionne** — analyser les impacts avant de toucher un fichier
5. **Initialiser Git immédiatement** — avant la première modification

---

## ÉTAPE 0 — INITIALISATION GIT (30 min) — RISQUE : NUL

### Objectif
Créer le dépôt Git et un snapshot de l'état initial avant toute modification.

### Actions
```bash
cd /opt/lampp/htdocs/GOL
git init
git add .
git commit -m "Initial commit — État du projet avant refactorisation (audit complet effectué)"
```

### Fichiers concernés
- Tous les fichiers du projet

### Impact BD / AJAX / UI
- Aucun

---

## PHASE 1 — CORRECTIONS CRITIQUES (Priorité absolue)

---

### TÂCHE C1 — Corriger la table manquante et inscription_enseignant.php (1h) — RISQUE : FAIBLE

**Problème** : `inscription_enseignant.php` tente d'insérer dans la table `enseignants` inexistante → crash fatal.

**Fichiers concernés**
- `inscription_enseignant.php`
- `database.sql`

**Actions**
1. Supprimer les lignes 69-70 de `inscription_enseignant.php` (`INSERT INTO enseignants`)
2. S'assurer que `specialite` est stocké dans `utilisateurs.bio` (ou ajouter colonne `specialite` à `utilisateurs`)
3. Ajouter colonne `specialite VARCHAR(255)` dans `database.sql` (ALTER TABLE)

**Impact BD** : ALTER TABLE utilisateurs — rétrocompatible  
**Impact AJAX** : Aucun  
**Impact UI** : Le message de confirmation s'affichera correctement  
**Rollback** : Remettre les 2 lignes supprimées

**Commit** : `git commit -m "Correction inscription_enseignant.php — suppression référence table inexistante"`

---

### TÂCHE C2 — Corriger le doublon AJAX + fonction rechercherGlobal (45 min) — RISQUE : MOYEN

**Problèmes**
1. `ajax.php` contient deux `case 'obtenir_certificat'` et deux `case 'generer_certificat'` → le second écrase le premier
2. `ajax.php` appelle `rechercherGlobal()` qui n'existe pas dans `fonctions.php` → Fatal Error

**Fichiers concernés**
- `ajax.php`
- `includes/fonctions.php`

**Actions**
1. Dans `ajax.php` : supprimer les deux `case` dupliqués (lignes 307-339), conserver uniquement les versions correctes (lignes 247-303)
2. Dans `fonctions.php` : ajouter la fonction `rechercherGlobal($terme)` qui recherche dans modules et cours
3. Dans `ajax.php` : ajouter le `case 'inscrire_module'` manquant (appelé dans module.php mais absent du switch)

**Impact BD** : Aucun  
**Impact AJAX** : Corrige 3 endpoints cassés  
**Impact UI** : Recherche fonctionnelle, certificats accessibles, inscription modules fonctionnelle  
**Rollback** : Restaurer ajax.php depuis Git

**Commit** : `git commit -m "Correction ajax.php — suppression doublons case, ajout rechercherGlobal et inscrire_module"`

---

### TÂCHE C3 — Corriger le système d'upload PDF et vidéo (2h) — RISQUE : MOYEN

**Problèmes**
1. `gestion_cours.php` redéclare `uploadFichier()` → Fatal Error si `fonctions.php` déjà chargé
2. `gestion_lecons.php` redéclare `uploadFichierLecon()` — nom différent, pas de conflit, mais incohérent
3. Chemins PDF/vidéo stockés sans préfixe SITE_URL → liens brisés dans les vues
4. Pas de vérification MIME type réel (seulement extension)
5. Pas de vérification taille vidéo

**Fichiers concernés**
- `gestion_cours.php` (pages hors design system — à corriger aussi)
- `gestion_lecons.php`
- `includes/fonctions.php` (améliorer `uploadFichier()`)
- `cours.php`, `lecon.php` (correction affichage chemins)

**Actions**
1. Dans `gestion_cours.php` : supprimer la fonction locale `uploadFichier()`, utiliser celle de `fonctions.php`
2. Dans `gestion_lecons.php` : renommer `uploadFichierLecon()` → utiliser `uploadFichier()` de `fonctions.php`
3. Dans `fonctions.php` : améliorer `uploadFichier()` avec :
   - Vérification `finfo_file()` pour MIME type réel
   - Vérification taille `MAX_FILE_SIZE`
   - Support mp4/webm/ogg pour vidéos
4. Dans `cours.php` et `lecon.php` : préfixer les chemins PDF/vidéo locaux avec `SITE_URL`
5. Dans `lecon.php` : corriger la détection vidéo locale vs YouTube/Vimeo

**Impact BD** : Aucun  
**Impact AJAX** : Aucun (upload direct via formulaire POST)  
**Impact UI** : PDF et vidéos s'affichent correctement  
**Rollback** : Restaurer les 4 fichiers depuis Git

**Commit** : `git commit -m "Correction système upload PDF et vidéo — MIME type, chemins, taille"`

---

### TÂCHE C4 — Corriger la progression et la mise à jour automatique (2h) — RISQUE : ÉLEVÉ

**Problèmes**
1. Formule de progression incorrecte (nombre de leçons au lieu de 0%/50%/100%)
2. `inscriptions_modules.progression_globale` jamais mis à jour → certificats bloqués
3. Création table `progression_lecons` nécessaire

**Fichiers concernés**
- `database.sql`
- `includes/fonctions.php`
- `ajax.php`

**Nouvelle formule officielle**
```
Leçon non commencée = 0%
Leçon ouverte = 50%
Quiz réussi = 100%
Progression cours = moyenne(statuts leçons)
Progression module = moyenne(progressions cours)
```

**Actions**
1. Créer table `progression_lecons` (id, id_utilisateur, id_lecon, statut [0/50/100], dates)
2. Dans `fonctions.php` :
   - Créer `ouvrirLecon($id_utilisateur, $id_lecon)` → statut 50
   - Modifier `marquerLeconTerminee()` → calculer progression cours + module + mise à jour `inscriptions_modules.progression_globale`
   - Créer `marquerEvaluationReussie($id_utilisateur, $id_lecon)` → statut 100
3. Dans `ajax.php` : modifier `case 'maj_progression'` pour supporter les nouveaux statuts
4. Vérifier déclenchement automatique du certificat si progression = 100%

**Impact BD** : Nouvelle table, modification de la logique d'update — migration rétrocompatible  
**Impact AJAX** : Modifier `maj_progression` dans ajax.php  
**Impact UI** : Barres de progression affichent des valeurs correctes  
**Rollback** : DROP TABLE progression_lecons + restaurer fonctions.php

**Commit** : `git commit -m "Correction progression — nouvelle formule 0/50/100, mise à jour module automatique"`

---

### TÂCHE C5 — Corriger la génération de certificats (1h) — RISQUE : MOYEN

**Problèmes**
1. `genererCertificat()` ne vérifie pas `progression_globale` (jamais à 100%) → jamais déclenché
2. La condition est dans `certificat.php` côté UI plutôt que dans la logique métier
3. Après correction C4, `progression_globale` sera mis à jour → il faut déclencher la génération

**Fichiers concernés**
- `includes/fonctions.php`
- `certificat.php`
- `ajax.php`

**Actions**
1. Dans `fonctions.php` : modifier `genererCertificat()` pour appeler la fonction après chaque mise à jour de progression_globale = 100
2. Ajouter `verifierEtGenererCertificat($id_utilisateur, $id_module)` qui :
   - Vérifie toutes les leçons validées (statut 100 dans progression_lecons)
   - Vérifie toutes les évaluations réussies
   - Si OK → génère le certificat automatiquement + notification
3. Corriger l'endpoint AJAX `obtenir_certificat` (déjà fait en C2 pour les doublons)
4. Dans `certificat.php` : afficher l'enseignant du cours (manquant actuellement)

**Impact BD** : Aucun (table certificats déjà correcte)  
**Impact AJAX** : Endpoint `generer_certificat` fonctionnel  
**Impact UI** : Certificats générés automatiquement, liste affichée dans certificat.php  
**Rollback** : Restaurer fonctions.php et certificat.php

**Commit** : `git commit -m "Correction génération certificats automatiques — déclenchement après progression 100%"`

---

### TÂCHE C6 — Corriger les contrôles de rôle et sécurité IDOR (1h) — RISQUE : FAIBLE

**Problèmes**
1. `gestion_cours.php` ne vérifie que `isset($_SESSION['id_utilisateur'])` (sans rôle)
2. Suppression de leçon sans vérification que la leçon appartient à l'enseignant connecté
3. `approuver_demande` dans ajax.php autorise le Promoteur (incorrect pour certificats exceptionnels)

**Fichiers concernés**
- `gestion_cours.php`
- `gestion_lecons.php`
- `ajax.php`

**Actions**
1. Dans `gestion_cours.php` : remplacer la vérification par `if (!estConnecte() || (!estEnseignant() && !estPromoteur() && !estSuperAdmin()))`
2. Dans `gestion_cours.php` : avant `DELETE FROM lecons`, vérifier `lecons.id_cours = cours.id_enseignant = $_SESSION['id_utilisateur']`
3. Dans `gestion_lecons.php` : même correction pour la suppression
4. Dans `ajax.php` : créer `case 'approuver_demande_certificat'` réservé à `estSuperAdmin()` uniquement

**Impact BD** : Aucun  
**Impact AJAX** : Restriction de permissions plus stricte  
**Impact UI** : Messages d'accès refusé pour les rôles incorrects  
**Rollback** : Restaurer les 3 fichiers

**Commit** : `git commit -m "Correction sécurité — contrôles de rôle et protection IDOR suppressions"`

---

### TÂCHE C7 — Créer gestion_quiz.php (4h) — RISQUE : FAIBLE (nouveau fichier)

**Problème** : Page manquante, lien 404 depuis `gestion_lecons.php`.

**Fichiers concernés**
- `gestion_quiz.php` (à créer)
- `includes/fonctions.php` (ajouter fonctions quiz)
- `ajax.php` (ajouter cases CRUD quiz)
- `database.sql` (colonne `questions.temps_limite`)

**Fonctionnalités**
- Créer/modifier/supprimer une évaluation pour une leçon
- Créer/modifier/supprimer des questions QCM
- Gérer les options (bonne réponse + faux)
- Configurer les points et le timer par question (30/45/60/90/120 sec)
- Inclure `header.php` / `footer.php` (design system complet)

**Actions**
1. Ajouter `ALTER TABLE questions ADD COLUMN temps_limite INT DEFAULT NULL`
2. Ajouter dans `fonctions.php` : `ajouterEvaluation()`, `ajouterQuestion()`, `ajouterOption()`, `supprimerQuestion()`, `supprimerEvaluation()`
3. Créer `gestion_quiz.php` avec l'interface complète
4. Dans `ajax.php` : cases `add_question`, `delete_question`, `add_option`, `delete_option`

**Impact BD** : ALTER TABLE questions (ajout colonne nullable — rétrocompatible)  
**Impact AJAX** : Nouveaux endpoints  
**Impact UI** : Page de gestion quiz opérationnelle, lien depuis gestion_lecons.php résolu  
**Rollback** : Supprimer gestion_quiz.php, supprimer ALTER TABLE

**Commit** : `git commit -m "Création gestion_quiz.php — CRUD complet évaluations et questions avec timer"`

---

## PHASE 2 — CORRECTIONS IMPORTANTES

---

### TÂCHE I1 — Implémenter demandes_certificats et workflow exceptionnel (3h) — RISQUE : FAIBLE

**Fichiers concernés**
- `database.sql` (nouvelle table)
- `includes/fonctions.php`
- `ajax.php`
- `certificat.php` (section demande)
- `administration.php` (section validation Super Admin)

**Actions**
1. Créer table `demandes_certificats` (voir AUDIT_SQL.md)
2. Dans `fonctions.php` : `creerDemandeCertificat()`, `approuverDemandeCertificat()`, `refuserDemandeCertificat()`
3. Dans `certificat.php` : ajouter formulaire "Demander un certificat exceptionnel"
4. Dans `administration.php` : ajouter section `?section=certificats` pour Super Admin uniquement
5. Dans `ajax.php` : cases `creer_demande_certificat`, `approuver_demande_certificat` (Super Admin only), `refuser_demande_certificat`

**Commit** : `git commit -m "Implémentation workflow certificats exceptionnels — table demandes_certificats"`

---

### TÂCHE I2 — Correction manuelle de progression par Super Admin (2h) — RISQUE : FAIBLE

**Fichiers concernés**
- `administration.php`
- `ajax.php`
- `includes/fonctions.php`

**Actions**
1. Ajouter `case 'corriger_progression'` dans `ajax.php` (Super Admin only)
2. Créer `corrigerProgressionEtudiant($id_utilisateur, $id_cours, $pourcentage)` dans `fonctions.php`
3. Ajouter section dans `administration.php` : recherche d'étudiant → correction de progression par cours

**Commit** : `git commit -m "Super Admin — correction manuelle de progression étudiant"`

---

### TÂCHE I3 — Résultats étudiants pour enseignant (2h) — RISQUE : FAIBLE

**Fichiers concernés**
- `includes/fonctions.php` (nouvelle fonction)
- `tableau_bord.php` (section enseignant)
- `gestion_cours.php` (refonte) ou nouveau fichier `resultats_enseignant.php`

**Actions**
1. Créer `obtenirResultatsParCours($id_enseignant)` dans `fonctions.php`
2. Ajouter lien "Voir les résultats" dans le tableau de bord enseignant
3. Créer page de résultats (tableau : étudiant, leçon, score, date, tentative)

**Commit** : `git commit -m "Résultats étudiants — consultation par enseignant"`

---

### TÂCHE I4 — Gestion des cours enseignant + publication avec vérification (3h) — RISQUE : MOYEN

**Problèmes**
1. `gestion_cours.php` est en réalité une page de gestion de leçons d'un cours spécifique, pas une liste de cours
2. `publierCours()` n'impose aucune vérification préalable

**Fichiers concernés**
- `gestion_cours.php` (refonte complète — hors design system actuellement)
- `includes/fonctions.php`
- `ajax.php`

**Actions**
1. Refondre `gestion_cours.php` en liste des cours de l'enseignant (avec `header.php`)
2. Ajouter bouton "Publier" qui vérifie : au moins une leçon ET au moins une évaluation
3. Dans `ajax.php` : `case 'publier_cours'` avec validation complète
4. Ajouter états visuels : brouillon / incomplet / prêt à publier / publié / archivé
5. Lier correctement vers `gestion_lecons.php` pour l'édition du contenu

**Commit** : `git commit -m "Refonte gestion_cours.php — liste cours enseignant avec publication contrôlée"`

---

### TÂCHE I5 — Créer modules.php (2h) — RISQUE : FAIBLE

**Fichiers concernés**
- `modules.php` (à créer)
- `includes/fonctions.php` (améliorer `obtenirModules()`)

**Actions**
1. Créer `modules.php` avec liste paginée de tous les modules actifs
2. Filtres par niveau, recherche
3. Afficher pour chaque module : nombre de cours, enseignants contributeurs, bouton inscription
4. Inclure `header.php` / `footer.php`

**Commit** : `git commit -m "Création modules.php — liste paginée avec filtres et enseignants contributeurs"`

---

### TÂCHE I6 — Afficher enseignants contributeurs dans module.php (45 min) — RISQUE : FAIBLE

**Fichiers concernés**
- `module.php`
- `includes/fonctions.php`

**Actions**
1. Créer `obtenirEnseignantsParModule($id_module)` dans `fonctions.php`
2. Dans `module.php` : afficher la liste des enseignants contributeurs avec leur(s) cours

**Commit** : `git commit -m "module.php — affichage enseignants contributeurs par cours"`

---

### TÂCHE I7 — Créer les droits Promoteur (créer utilisateurs) (1h30) — RISQUE : FAIBLE

**Problème** : `ajax.php` case `add_user` réservé à `estSuperAdmin()` uniquement, alors que le Promoteur doit pouvoir créer des enseignants et étudiants.

**Fichiers concernés**
- `ajax.php`
- `administration.php`

**Actions**
1. Dans `ajax.php` : modifier `add_user` pour autoriser `estSuperAdmin() || estPromoteur()`
   - Mais restreindre le Promoteur : il ne peut créer que `enseignant` ou `etudiant` (pas `super_admin` ni `promoteur`)
2. Dans `administration.php` : afficher le bouton "Ajouter utilisateur" pour le Promoteur aussi

**Commit** : `git commit -m "Droits Promoteur — création enseignants et étudiants autorisée"`

---

## PHASE 3 — TÂCHES DE CONFORT

---

### TÂCHE CO1 — Unifier le design system (3h) — RISQUE : MOYEN

**Problème** : `creer_cours.php` et `gestion_cours.php` ont leur propre DOCTYPE, pas de header.php, pas de thème sombre, pas de design system.

**Fichiers concernés**
- `creer_cours.php`
- `gestion_cours.php`

**Actions**
1. Remplacer le DOCTYPE inline par `include 'includes/header.php'` en haut
2. Remplacer la balise `</body></html>` par `include 'includes/footer.php'`
3. Adapter les styles inline pour utiliser les variables CSS du design system
4. S'assurer que les styles inline n'écrasent pas les styles globaux

**Commit** : `git commit -m "Design system — creer_cours.php et gestion_cours.php intégrés au thème global"`

---

### TÂCHE CO2 — Corriger le système de thème sombre (2h) — RISQUE : MOYEN

**Problèmes**
1. Conflit `header.php` (applique sur `body`) vs `app.js` (applique sur `documentElement`)
2. `app.js` n'est jamais chargé
3. Variables CSS manquantes : `--ombre-glow`, `--glass-bg`, `--glass-border`, `--radius-md`, `--radius-sm`

**Fichiers concernés**
- `includes/header.php`
- `assets/css/style.css`
- `assets/js/app.js`

**Actions**
1. Unifier : `chargerTheme()` applique sur `document.documentElement` partout
2. Ajouter `<link rel="stylesheet" href="assets/css/style.css">` dans `header.php`
3. Ajouter `<script src="assets/js/app.js" defer></script>` dans `header.php`  
   → Mais d'abord supprimer les fonctions JS redéfinies inline dans les pages
4. Ajouter les variables manquantes dans `:root` de `style.css` :
   ```css
   --ombre-glow: 0 0 20px rgba(37, 99, 235, 0.4);
   --glass-bg: rgba(255, 255, 255, 0.05);
   --glass-border: rgba(255, 255, 255, 0.1);
   --radius-md: 0.5rem;
   --radius-sm: 0.25rem;
   --spacing-1: 0.25rem;
   --spacing-2: 0.5rem;
   --spacing-3: 0.75rem;
   --spacing-5: 1.25rem;
   --spacing-10: 2.5rem;
   --spacing-12: 3rem;
   --secondaire: #0f172a;
   ```

**Commit** : `git commit -m "Thème sombre — unification système, variables CSS manquantes, app.js chargé"`

---

### TÂCHE CO3 — Menu hamburger mobile (2h) — RISQUE : MOYEN

**Problèmes**
1. `.nav-menu` masqué sur mobile sans alternative
2. `ouvrirMenuMobile()` défini dans app.js mais pas de bouton dans header.php
3. Pas de `#mobileSidebar` dans le DOM

**Fichiers concernés**
- `includes/header.php`
- `assets/css/style.css`

**Actions**
1. Ajouter bouton hamburger SVG dans la navbar (côté droit sur mobile)
2. Ajouter `<div id="mobileSidebar">` dans header.php avec tous les liens de navigation
3. Ajouter overlay de fermeture
4. Ajouter styles responsive dans style.css : slide-in sidebar animée
5. Connecter les appels `ouvrirMenuMobile()` / `fermerMenuMobile()` du bouton

**Commit** : `git commit -m "Navigation mobile — menu hamburger SVG et sidebar animée"`

---

### TÂCHE CO4 — Supprimer tous les emojis et créer bibliothèque SVG (3h) — RISQUE : FAIBLE

**48 emojis** identifiés dans 12 fichiers PHP.

**Fichiers concernés**
- `profil.php`, `creer_cours.php`, `fix_enseignants.php`, `test_enseignant.php`, `certificat.php`, `inscription_enseignant.php`, `fix_password.php`, `gestion_cours.php`, `test_fonctions.php`, `gestion_lecons.php`, `evaluation.php`, `test_hash_superadmin.php`

**Actions**
1. Créer `assets/svg/icons.php` — bibliothèque SVG réutilisable avec fonctions PHP :
   ```php
   function icone($nom, $taille = 18, $classe = '') { ... }
   ```
   Icônes requises : cours, leçon, quiz, PDF, vidéo, succès, erreur, info, avertissement, suppression, modification, publication, certificat, utilisateur, module, statistiques, hamburger, fermer, soleil, lune
2. Remplacer chaque emoji par l'appel `icone('nom')` ou par le SVG inline correspondant

**Commit** : `git commit -m "Suppression emojis — bibliothèque SVG locale, remplacement dans 12 fichiers"`

---

### TÂCHE CO5 — Animations premium et skeleton loaders (3h) — RISQUE : FAIBLE

**Fichiers concernés**
- `assets/css/style.css`
- `assets/js/app.js`
- Pages concernées : `tableau_bord.php`, `module.php`, `cours.php`, `certificat.php`

**Actions**
1. Ajouter classes `.skeleton` et `@keyframes loading` dans style.css
2. Implémenter skeleton loaders pour les listes de modules, cours, leçons au chargement
3. Ajouter animations d'entrée : `animate-slideUp`, `animate-scaleIn` (déjà référencées dans app.js)
4. Ajouter effet glassmorphism sur les cartes premium
5. Polir les transitions hover sur les cartes

**Commit** : `git commit -m "Animations premium — skeleton loaders, glassmorphism, transitions"`

---

### TÂCHE CO6 — Responsive complet (2h) — RISQUE : FAIBLE

**Fichiers concernés**
- `assets/css/style.css`
- Fichiers avec styles inline : `administration.php`, `tableau_bord.php`, `cours.php`

**Actions**
1. Compléter les breakpoints dans style.css :
   - Mobile : < 768px
   - Tablette : 768px–1024px
   - Desktop : 1024px–1440px
   - Ultra-wide : > 1440px
2. Sidebar admin : overlay mobile, compacte tablette
3. Grilles adaptatives sur toutes les pages
4. Tests sur Chrome DevTools mobile

**Commit** : `git commit -m "Responsive complet — mobile, tablette, desktop, ultra-wide"`

---

### TÂCHE CO7 — Notifications avec polling (1h) — RISQUE : FAIBLE

**Fichiers concernés**
- `assets/js/app.js`
- `includes/header.php`

**Actions**
1. Ajouter badge notifications dans la navbar avec compteur
2. Dans app.js : polling toutes les 30 secondes sur `ajax.php?action=obtenir_notifications`
3. Mettre à jour le badge sans rechargement de page

**Commit** : `git commit -m "Notifications — polling 30s et badge compteur dans la navbar"`

---

### TÂCHE CO8 — Push Git final (15 min)

```bash
git push origin main
# ou selon la branche :
git push origin master
```

---

## TABLEAU DE SYNTHÈSE

| ID | Tâche | Phase | Durée | Risque | Priorité |
|----|-------|-------|-------|--------|----------|
| C1 | Corriger inscription_enseignant.php | 1 | 1h | Faible | CRITIQUE |
| C2 | Corriger doublons AJAX + rechercherGlobal | 1 | 45 min | Moyen | CRITIQUE |
| C3 | Corriger upload PDF/vidéo | 1 | 2h | Moyen | CRITIQUE |
| C4 | Corriger progression (formule + mise à jour module) | 1 | 2h | Élevé | CRITIQUE |
| C5 | Corriger génération certificats | 1 | 1h | Moyen | CRITIQUE |
| C6 | Corriger contrôles rôle + IDOR | 1 | 1h | Faible | CRITIQUE |
| C7 | Créer gestion_quiz.php | 1 | 4h | Faible | CRITIQUE |
| I1 | Workflow demandes_certificats | 2 | 3h | Faible | IMPORTANT |
| I2 | Correction manuelle progression (Super Admin) | 2 | 2h | Faible | IMPORTANT |
| I3 | Résultats étudiants pour enseignant | 2 | 2h | Faible | IMPORTANT |
| I4 | Refonte gestion_cours.php + publication | 2 | 3h | Moyen | IMPORTANT |
| I5 | Créer modules.php | 2 | 2h | Faible | IMPORTANT |
| I6 | Enseignants contributeurs dans module.php | 2 | 45 min | Faible | IMPORTANT |
| I7 | Droits Promoteur création utilisateurs | 2 | 1h30 | Faible | IMPORTANT |
| CO1 | Unifier design system | 3 | 3h | Moyen | CONFORT |
| CO2 | Thème sombre complet | 3 | 2h | Moyen | CONFORT |
| CO3 | Menu hamburger mobile | 3 | 2h | Moyen | CONFORT |
| CO4 | Supprimer emojis + bibliothèque SVG | 3 | 3h | Faible | CONFORT |
| CO5 | Animations premium + skeletons | 3 | 3h | Faible | CONFORT |
| CO6 | Responsive complet | 3 | 2h | Faible | CONFORT |
| CO7 | Notifications avec polling | 3 | 1h | Faible | CONFORT |

**Durée totale estimée** : ~44 heures de développement

---

## ORDRE OPTIMAL D'EXÉCUTION

```
0. Git init
   ↓
1. C1 (inscription_enseignant — critique, faible risque)
   ↓
2. C2 (doublons AJAX — critique, bloque les tests AJAX)
   ↓
3. C3 (upload — critique, indépendant)
   ↓
4. C4 (progression — critique, bloque C5)
   ↓
5. C5 (certificats — dépend de C4)
   ↓
6. C6 (sécurité — critique, ne dépend de rien)
   ↓
7. C7 (gestion_quiz.php — critique, nouveau fichier, faible risque)
   ↓
8. I1 (demandes_certificats — dépend de C5)
   ↓
9. I2 (correction progression admin — dépend de C4)
   ↓
10. I3 (résultats enseignant — indépendant)
    ↓
11. I4 (gestion_cours.php — dépend de C3/C6)
    ↓
12. I5 + I6 (modules.php + contributeurs — indépendants, faible risque)
    ↓
13. I7 (droits promoteur)
    ↓
14. CO1 (design system — doit être fait avant CO2)
    ↓
15. CO2 (thème sombre — dépend de CO1)
    ↓
16. CO3 (hamburger — dépend de CO2)
    ↓
17. CO4 (emojis → SVG — indépendant)
    ↓
18. CO5 (animations — dépend de CO1/CO2)
    ↓
19. CO6 (responsive — dépend de CO1)
    ↓
20. CO7 (notifications — dépend de CO2/app.js)
    ↓
21. Push Git final
```

---

## IMPACTS BASE DE DONNÉES

| Tâche | SQL | Rétrocompatible |
|-------|-----|----------------|
| C1 | `ALTER TABLE utilisateurs ADD COLUMN specialite VARCHAR(255)` | ✅ |
| C4 | `CREATE TABLE progression_lecons (...)` | ✅ |
| C7 | `ALTER TABLE questions ADD COLUMN temps_limite INT DEFAULT NULL` | ✅ |
| I1 | `CREATE TABLE demandes_certificats (...)` | ✅ |

**Aucune modification destructive de schéma existant.**

---

## STRATÉGIE DE ROLLBACK

### Rollback partiel (par tâche)
Chaque tâche est commitée séparément → rollback possible avec :
```bash
git revert HEAD         # annule le dernier commit
git checkout HEAD~1 -- fichier.php  # restaure un fichier spécifique
```

### Rollback complet
```bash
git reset --hard <hash_commit_initial>
```

### Protection des données
- Les ALTER TABLE et CREATE TABLE sont rétrocompatibles → pas de rollback SQL nécessaire
- En cas de besoin : `DROP TABLE progression_lecons` / `DROP TABLE demandes_certificats`
- Aucun `DROP COLUMN` ni `DROP TABLE` sur les tables existantes

---

## CHECKLIST PRÉ-COMMIT (à exécuter avant chaque commit)

```bash
# Vérification PHP
php -l fichier_modifie.php

# Vérification AJAX (dans le navigateur ou curl)
curl -s -X POST http://localhost/GOL/ajax.php?action=obtenir_notifications \
  -H "X-Requested-With: XMLHttpRequest"

# Vérification de connexion
# Se connecter avec : superadmin@gol.com / loi770Messi2026

# Vérification SQL (si applicable)
# Tester la requête dans phpMyAdmin avant de l'intégrer
```

---

**Document produit après audit technique et métier complet.  
Aucune modification de code n'a été effectuée à ce stade.  
En attente de validation avant démarrage de la Phase 1.**
