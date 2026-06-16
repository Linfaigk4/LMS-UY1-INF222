# Bugfix Requirements Document — GOL LMS : Audit, Correction & Refonte Premium

## Introduction

Le projet **GOL (Gugle Online Learning)** est un LMS (Learning Management System) partiellement développé sous XAMPP (PHP 8.2, MySQL, HTML5, CSS3, JavaScript Vanilla, AJAX Fetch API). L'audit complet de la codebase a révélé une série de bugs fonctionnels critiques, d'incohérences architecturales, de fichiers manquants, et de lacunes de sécurité, qui empêchent le bon fonctionnement de la plateforme et nuisent à l'expérience utilisateur.

L'objectif de ce document est de capturer l'intégralité des comportements défectueux actuels (current behavior), les comportements corrects attendus (expected behavior), et les comportements existants qui doivent être préservés (unchanged behavior), conformément à la méthodologie bug condition.

---

## Bug Analysis

### Current Behavior (Defect)

#### GROUPE 1 — Fichiers manquants / pages cassées

1.1 WHEN un enseignant clique sur "Gérer le quiz" dans `gestion_lecons.php` THEN le système renvoie une erreur 404 car `gestion_quiz.php` est absent du projet

1.2 WHEN un utilisateur tente d'accéder à `modules.php` depuis le bouton "Voir tous les modules" de `index.php` THEN le système renvoie une erreur 404 car `modules.php` n'existe pas

1.3 WHEN `inscription_enseignant.php` soumet le formulaire THEN le système tente d'insérer dans une table `enseignants` qui n'existe pas dans `database.sql`, provoquant une erreur SQL fatale

1.4 WHEN un enseignant se connecte THEN le système n'affiche pas de page dédiée à la gestion de ses cours (la page `gestion_cours.php` est en réalité une copie de `gestion_lecons.php` avec une structure incohérente)

1.5 WHEN un utilisateur accède à la page de connexion THEN le système affiche deux fois le même bloc "Nouveau sur GOL ? Créer un compte" et deux balises `<div>` séparateur redondantes

#### GROUPE 2 — Upload PDF et Vidéo cassé

1.6 WHEN un enseignant uploade un fichier PDF dans une leçon via `gestion_cours.php` THEN le système utilise une fonction locale `uploadFichier()` redéfinie qui entre en conflit avec la fonction globale du même nom dans `fonctions.php`, causant un crash PHP (fatal error : cannot redeclare function)

1.7 WHEN un enseignant uploade un fichier vidéo MP4 THEN le système ne vérifie pas la taille du fichier par rapport à `MAX_FILE_SIZE` (50 Mo) défini dans `config.php`, et ne limite pas la taille au niveau PHP (`upload_max_filesize` / `post_max_size` non configurés), causant des uploads silencieusement tronqués ou échoués

1.8 WHEN un fichier PDF est uploadé et stocké sous `uploads/pdf/` THEN dans la vue d'affichage de `lecon.php`, le chemin `$lecon['fichier_pdf']` est utilisé directement sans préfixe de base URL, rendant les PDF inaccessibles dans le navigateur (chemin relatif cassé)

1.9 WHEN un fichier vidéo local est uploadé et stocké sous `uploads/videos/` THEN dans `cours.php` et `lecon.php`, la condition `strpos($video_url, 'youtube.com')` est testée sur un chemin local, ce qui échoue silencieusement et n'affiche pas le lecteur vidéo natif `<video>` correctement

#### GROUPE 3 — Affichage des cours et leçons (étudiants)

1.10 WHEN un étudiant non inscrit à un module consulte la page `cours.php?id=X` THEN le système affiche quand même le contenu du cours sans vérifier l'inscription préalable au module parent, contournant le workflow pédagogique

1.11 WHEN un étudiant consulte la liste de ses modules dans `profil.php` THEN le système exécute une requête SQL brute directement dans le template PHP (ligne `$stmt = $pdo->prepare(...)` imbriquée dans le `foreach`) au lieu d'utiliser une fonction dédiée, violant la séparation des couches et causant des erreurs si `$pdo` n'est pas dans scope

1.12 WHEN les leçons d'un cours sont listées dans la sidebar de `cours.php` THEN le système classe les leçons par `id_lecon` croissant au lieu de `ordre_affichage`, rompant l'ordre pédagogique défini par l'enseignant

1.13 WHEN un étudiant marque une leçon comme terminée depuis `cours.php` THEN le système appelle `marquerTermine(idLecon, idCours)` en JS qui envoie l'action `maj_progression`, mais `ajouterRequeteAjax` appelle `envoyerRequeteAjax` (nom incohérent : la fonction JS dans `app.js` s'appelle `envoyerRequeteAjax` mais dans `cours.php` l'appel JS est identique — le bug réside dans le fait qu'aucun rechargement asynchrone de la progression sidebar n'est effectué, forçant un rechargement complet de page)

#### GROUPE 4 — Progression et certificats

1.14 WHEN la progression globale d'un module est calculée par `calculerProgressionModule()` THEN le système calcule la moyenne des pourcentages des cours publiés sans prendre en compte les cours auxquels l'étudiant n'a pas encore accédé (division incorrecte si l'étudiant n'a pas de ligne dans `progression_cours`), retournant une progression surestimée

1.15 WHEN un étudiant tente de générer son certificat depuis `certificat.php` THEN le système vérifie `progression_globale >= 100` dans la table `inscriptions_modules`, mais cette colonne n'est jamais mise à jour automatiquement lors de la complétion des cours (la mise à jour de `inscriptions_modules.progression_globale` est absente de la fonction `marquerLeconTerminee()`), empêchant toute génération de certificat

1.16 WHEN l'endpoint AJAX `obtenir_certificat` est appelé dans `ajax.php` THEN le système contient deux `case 'obtenir_certificat':` et deux `case 'generer_certificat':` dans le switch, le second écrasant le premier ; le premier `case 'obtenir_certificat'` utilise une expression SQL booléenne incorrecte (`OR ?` avec un boolean PHP) causant une requête SQL invalide

1.17 WHEN un étudiant accède à `evaluation.php` après avoir terminé une leçon THEN le système vérifie `in_array($evaluation['id_lecon'], $lecons_terminees)` mais `$lecons_terminees` peut être `null` si aucune progression n'existe encore, causant un warning PHP et un accès refusé silencieux

#### GROUPE 5 — Notifications

1.18 WHEN le tableau de bord affiche les notifications THEN le système appelle `time_elapsed_string($notif['date_creation'])` sans vérifier si la fonction existe dans le scope (elle est définie dans `fonctions.php` mais peut ne pas être chargée sur certaines pages), causant une erreur fatale

1.19 WHEN l'action AJAX `obtenir_notifications` retourne des données THEN aucun mécanisme de polling ou de WebSocket n'existe — les notifications ne s'actualisent jamais sans rechargement manuel de la page

#### GROUPE 6 — Design, UI/UX et navigation

1.20 WHEN le navbar est affiché sur mobile (largeur < 768px) THEN le menu principal `.nav-menu` est masqué via `display: none` sans fournir de menu hamburger ou de navigation mobile alternative, rendant la navigation impossible sur mobile

1.21 WHEN la page `creer_cours.php` est chargée THEN elle utilise son propre DOCTYPE/HTML/head/body complet au lieu d'inclure `includes/header.php`, créant un design incohérent (pas de thème sombre, pas de variables CSS, pas de navbar)

1.22 WHEN `gestion_cours.php` (qui gère les leçons, pas les cours) est chargé THEN le fichier utilise également son propre DOCTYPE complet sans le système de design global, et est fonctionnellement identique à `gestion_lecons.php`, causant une confusion de nommage et une duplication de code

1.23 WHEN les pages utilisent des emojis comme indicateurs visuels (📚, ✅, ❌, 📄, 🎥, etc.) THEN le système affiche des caractères Unicode non-SVG qui s'affichent différemment selon les OS/navigateurs et violent les règles de design du projet

1.24 WHEN le thème sombre est activé via localStorage THEN `creer_cours.php` et `gestion_cours.php` ignorent le thème car ils n'incluent pas `header.php` et n'ont pas accès aux variables CSS `[data-theme="dark"]`

1.25 WHEN la sidebar admin est affichée sur mobile THEN la classe `admin-sidebar` a `position: sticky` et une hauteur fixe `calc(100vh - 80px)` qui déborde sur mobile, et le responsive ne s'adapte pas correctement à la tablette (breakpoint unique à 768px)

#### GROUPE 7 — Sécurité et validation

1.26 WHEN un utilisateur soumet le formulaire de profil dans `profil.php` avec l'action `update_profile` THEN le système met à jour les champs `nom`, `prenom`, `telephone`, `bio` sans valider ni échapper les valeurs au-delà du `trim()`, et stocke directement en base sans contrôle de longueur maximale

1.27 WHEN la suppression d'une leçon est déclenchée via `?delete=ID` dans `gestion_cours.php` THEN le système supprime sans vérifier que la leçon appartient bien au cours de l'enseignant connecté, permettant la suppression de leçons d'autres enseignants

1.28 WHEN un utilisateur accède à `gestion_cours.php` THEN la vérification de session ne teste que `isset($_SESSION['id_utilisateur'])` sans vérifier le rôle, permettant potentiellement à un étudiant d'accéder à la page de gestion

1.29 WHEN le formulaire de création de cours dans `creer_cours.php` est soumis THEN le slug est généré sans vérifier l'unicité en base avant insertion, causant une erreur SQL sur la contrainte `UNIQUE KEY` de `cours.slug`

1.30 WHEN l'upload d'avatar est traité dans `ajax.php` case `upload_avatar` THEN le système n'effectue pas de vérification MIME type réel (seulement l'extension), permettant l'upload de fichiers malveillants avec une extension `.jpg`

---

### Expected Behavior (Correct)

#### GROUPE 1 — Fichiers manquants / pages cassées

2.1 WHEN un enseignant clique sur "Gérer le quiz" THEN le système SHALL afficher la page `gestion_quiz.php` permettant de créer, modifier et supprimer les questions/options d'une évaluation associée à la leçon

2.2 WHEN un utilisateur clique sur "Voir tous les modules" THEN le système SHALL afficher la page `modules.php` listant tous les modules actifs avec filtres par niveau et recherche

2.3 WHEN le formulaire d'inscription enseignant est soumis THEN le système SHALL insérer les données uniquement dans la table `utilisateurs` avec le rôle `enseignant` (la table `enseignants` séparée n'existe pas dans le schéma) et afficher un message de confirmation

2.4 WHEN un enseignant se connecte THEN le système SHALL le rediriger vers `tableau_bord.php` qui affiche ses cours, ses statistiques et ses actions de gestion

2.5 WHEN la page de connexion est chargée THEN le système SHALL afficher exactement un seul lien "Créer un compte" et un seul séparateur "OU" dans le formulaire

#### GROUPE 2 — Upload PDF et Vidéo

2.6 WHEN un enseignant uploade un PDF via le formulaire de leçon THEN le système SHALL utiliser exclusivement la fonction `uploadFichier()` de `fonctions.php` (sans redéclaration locale), valider l'extension ET le MIME type (`application/pdf`), vérifier la taille (≤ 50 Mo), et stocker le fichier sous `uploads/pdf/`

2.7 WHEN un enseignant uploade une vidéo THEN le système SHALL valider l'extension (mp4/webm/ogg), vérifier que la taille est ≤ 50 Mo, stocker sous `uploads/videos/`, et afficher une barre de progression d'upload

2.8 WHEN un PDF est stocké en base et affiché dans une leçon THEN le système SHALL construire l'URL complète avec `SITE_URL . $lecon['fichier_pdf']` pour garantir l'accessibilité depuis n'importe quelle page

2.9 WHEN une vidéo locale est stockée sous `uploads/videos/` THEN le système SHALL détecter qu'il s'agit d'un chemin local (ne commençant pas par `http`) et SHALL utiliser systématiquement le lecteur `<video>` HTML5 natif avec l'URL construite correctement

#### GROUPE 3 — Affichage des cours et leçons

2.10 WHEN un étudiant non inscrit tente d'accéder à `cours.php?id=X` THEN le système SHALL vérifier l'inscription au module parent via `estInscritModule()` et SHALL rediriger vers `module.php?id=Y` avec un message invitant à s'inscrire

2.11 WHEN `profil.php` affiche les modules et cours d'un étudiant THEN le système SHALL utiliser une fonction dédiée `obtenirCoursParModule()` déjà présente dans `fonctions.php` sans effectuer de requête SQL inline dans le template

2.12 WHEN les leçons d'un cours sont listées THEN le système SHALL les trier par `ordre_affichage ASC` dans toutes les requêtes de récupération des leçons

2.13 WHEN un étudiant marque une leçon comme terminée THEN le système SHALL mettre à jour le pourcentage affiché dans la sidebar sans rechargement complet de la page, via une réponse AJAX

#### GROUPE 4 — Progression et certificats

2.14 WHEN la progression globale d'un module est calculée THEN le système SHALL diviser la somme des progressions par le nombre total de cours du module (et non seulement ceux ayant une entrée `progression_cours`), retournant une valeur entre 0 et 100% précise

2.15 WHEN un étudiant complète le dernier cours d'un module (100%) THEN le système SHALL automatiquement mettre à jour `inscriptions_modules.progression_globale = 100` et `statut = 'termine'` dans la fonction `marquerLeconTerminee()`, permettant ainsi la génération du certificat

2.16 WHEN l'endpoint AJAX `obtenir_certificat` est appelé THEN le système SHALL contenir exactement un seul `case 'obtenir_certificat'` et un seul `case 'generer_certificat'` dans le switch, avec une requête SQL correcte pour vérifier les droits d'accès

2.17 WHEN un étudiant accède à `evaluation.php` THEN le système SHALL initialiser `$lecons_terminees` à un tableau vide `[]` si aucune progression n'existe encore, et effectuer la vérification `in_array()` sans warning

#### GROUPE 5 — Notifications

2.18 WHEN le tableau de bord affiche les notifications THEN le système SHALL s'assurer que `fonctions.php` est toujours inclus avant l'appel à `time_elapsed_string()`, et la fonction SHALL être robuste aux valeurs nulles ou invalides

2.19 WHEN de nouvelles notifications existent THEN le système SHALL les actualiser automatiquement toutes les 30 secondes via un polling AJAX sur l'endpoint `obtenir_notifications`

#### GROUPE 6 — Design, UI/UX et navigation

2.20 WHEN la navbar est affichée sur mobile THEN le système SHALL afficher un bouton hamburger SVG ouvrant une sidebar mobile animée avec tous les liens de navigation, sans aucun emoji

2.21 WHEN `creer_cours.php` est chargé THEN le système SHALL inclure `header.php` et `footer.php` pour hériter du design system complet (variables CSS, thème sombre, navbar, police Inter)

2.22 WHEN la gestion des cours enseignant est affichée THEN le système SHALL disposer d'une page `gestion_cours.php` distincte listant les cours de l'enseignant (avec statut, nombre de leçons, actions publier/archiver), et d'une page `gestion_lecons.php` dédiée aux leçons d'un cours spécifique

2.23 WHEN des indicateurs visuels sont nécessaires (statut, type, action) THEN le système SHALL utiliser exclusivement des icônes SVG inline, sans aucun caractère emoji Unicode

2.24 WHEN le thème sombre est activé THEN le système SHALL appliquer les variables CSS `[data-theme="dark"]` sur toutes les pages sans exception, y compris les pages de gestion enseignant

2.25 WHEN la sidebar admin est affichée sur tablette (768px–1024px) ou mobile (<768px) THEN le système SHALL appliquer un responsive design adapté : sidebar rétractable en overlay sur mobile, compacte sur tablette

#### GROUPE 7 — Sécurité et validation

2.26 WHEN un utilisateur soumet le formulaire de profil THEN le système SHALL valider chaque champ (longueur max : nom/prénom 100 chars, téléphone 20 chars, bio 1000 chars), et stocker en base via des requêtes PDO préparées uniquement

2.27 WHEN la suppression d'une leçon est déclenchée THEN le système SHALL vérifier que `lecons.id_cours` correspond à un cours dont `id_enseignant = $_SESSION['id_utilisateur']` avant toute suppression

2.28 WHEN un utilisateur accède à `gestion_lecons.php` ou `gestion_cours.php` THEN le système SHALL vérifier `estEnseignant() || estPromoteur() || estSuperAdmin()` et rediriger les autres rôles

2.29 WHEN un cours est créé THEN le système SHALL vérifier l'unicité du slug généré et l'incrémenter automatiquement (`intro-html-css-2`, etc.) en cas de collision

2.30 WHEN un avatar est uploadé THEN le système SHALL vérifier le MIME type réel via `finfo_file()` en plus de l'extension, et rejeter tout fichier dont le MIME type n'est pas `image/jpeg`, `image/png`, `image/gif`, ou `image/svg+xml`

---

### Unchanged Behavior (Regression Prevention)

3.1 WHEN un utilisateur se connecte avec des identifiants valides THEN le système SHALL CONTINUE TO créer la session PHP avec `id_utilisateur`, `email`, `nom`, `prenom`, `role` et rediriger vers `tableau_bord.php`

3.2 WHEN un super_admin se connecte THEN le système SHALL CONTINUE TO rediriger automatiquement vers `administration.php` depuis `tableau_bord.php`

3.3 WHEN la page d'accueil `index.php` est chargée THEN le système SHALL CONTINUE TO afficher les modules actifs, les statistiques globales, les sections rôles, fonctionnalités et témoignages

3.4 WHEN un étudiant s'inscrit à un module THEN le système SHALL CONTINUE TO insérer une ligne dans `inscriptions_modules` avec `statut = 'inscrit'` et `progression_globale = 0`

3.5 WHEN un étudiant passe une évaluation avec succès THEN le système SHALL CONTINUE TO enregistrer le résultat dans `resultats_evaluations` avec le score, les réponses JSON et le temps consacré

3.6 WHEN l'administration affiche les utilisateurs THEN le système SHALL CONTINUE TO lister tous les utilisateurs via `obtenirTousUtilisateurs()` dans un tableau avec rôle et statut

3.7 WHEN une demande de modification est approuvée par un admin THEN le système SHALL CONTINUE TO appliquer la modification en base (mot de passe, email, téléphone, nom complet), mettre à jour le statut de la demande, et envoyer une notification à l'utilisateur

3.8 WHEN un module est créé par le super_admin THEN le système SHALL CONTINUE TO insérer en base avec un slug unique, le niveau, la description et l'ID du promoteur

3.9 WHEN l'authentification vérifie les credentials THEN le système SHALL CONTINUE TO utiliser `password_verify()` avec le hash `PASSWORD_DEFAULT` de PHP

3.10 WHEN la connexion PDO est établie dans `config.php` THEN le système SHALL CONTINUE TO utiliser le charset `utf8mb4`, le mode `ERRMODE_EXCEPTION`, et `FETCH_ASSOC` comme mode de fetch par défaut

3.11 WHEN un utilisateur change le thème clair/sombre THEN le système SHALL CONTINUE TO stocker la préférence dans `localStorage` et l'appliquer via l'attribut `data-theme` sur le `<body>`

3.12 WHEN un certificat valide existe pour un utilisateur THEN le système SHALL CONTINUE TO l'afficher dans `certificat.php` avec le code unique, la date d'émission et le nom du module, et permettre son impression via une fenêtre d'impression dédiée

3.13 WHEN la déconnexion est effectuée THEN le système SHALL CONTINUE TO appeler `session_destroy()` et rediriger vers la page de connexion

3.14 WHEN les évaluations QCM sont chargées dans `evaluation.php` THEN le système SHALL CONTINUE TO charger les questions via `obtenirQuestions()`, afficher les options parsées depuis le champ JSON `options`, et gérer la navigation question par question en JavaScript

3.15 WHEN la barre de navigation est affichée sur desktop THEN le système SHALL CONTINUE TO afficher le logo GOL SVG, les liens principaux, le bouton de changement de thème et le bouton de déconnexion

---

## Appendice : Inventaire des fichiers manquants à créer

Les fichiers suivants sont référencés dans le code mais absents du projet et DOIVENT être créés lors de la refonte :

- `gestion_quiz.php` — Gestion des évaluations et questions par leçon (enseignant)
- `modules.php` — Liste paginée de tous les modules avec filtres
- `gestion_cours.php` (refonte complète) — Liste des cours de l'enseignant avec actions CRUD
- `assets/svg/` — Dossier contenant tous les SVG réutilisables du projet

## Appendice : Règles de design imposées (non-régression design)

Toutes les pages de la refonte DOIVENT respecter les contraintes suivantes sans exception :
- PHP procédural avec PDO uniquement, aucun framework
- Variables et commentaires en français
- Glassmorphism, cartes flottantes, dégradés, sidebar moderne
- Skeleton loaders sur les chargements asynchrones
- Modales élégantes avec backdrop-filter blur
- Notifications toast (succès/erreur/info) via `afficherNotification()` JS
- Mode clair/sombre via `[data-theme="dark"]` et localStorage
- SVG uniquement — zéro emoji dans le HTML/PHP
- Mobile First : breakpoints mobile (<768px), tablette (768px–1024px), desktop (>1024px), ultra-wide (>1440px)
- Police : Inter (Google Fonts), tailles de 0.7rem à 2.5rem
- Palette : bleu primaire #2563eb, accent cyan #06b6d4, fond clair #f8fafc, fond sombre #0a0a0f
