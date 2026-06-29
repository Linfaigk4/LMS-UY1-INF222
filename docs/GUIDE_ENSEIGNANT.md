# Guide Enseignant — GOL LMS

Ce guide s'adresse aux **enseignants** et **promoteurs**.

---

## Accès à l'espace enseignant

Après connexion, le lien **Gestion des cours** apparaît dans la barre de navigation.  
Il est visible uniquement pour les rôles `enseignant` et `promoteur`.

---

## 1. Créer un cours

1. Aller sur **Gestion des cours** (`gestion_cours.php`)
2. Cliquer sur **Nouveau cours**
3. Remplir le formulaire :
   - **Titre** du cours
   - **Description** complète
   - **Objectifs** pédagogiques
   - **Difficulté** : débutant / intermédiaire / avancé / expert
   - **Durée** estimée (en minutes)
   - **Module** de rattachement (liste des modules disponibles)
   - **Prérequis** (facultatif)
4. Cliquer sur **Créer le cours**

Le cours est créé en statut **brouillon** — il n'est pas visible par les étudiants.

---

## 2. Ajouter une leçon

1. Dans la liste de vos cours, cliquer sur **Gérer les leçons**
2. Cliquer sur **Ajouter une leçon**
3. Choisir le type de contenu :
   - **Texte** : contenu HTML enrichi
   - **PDF** : fichier PDF (max 20 Mo)
   - **Vidéo** : fichier MP4/WebM/OGG (max 50 Mo)
4. Remplir le titre et le contenu
5. Sauvegarder

Les leçons s'affichent dans l'ordre `ordre_affichage`.

---

## 3. Ajouter un PDF

Dans le formulaire d'ajout de leçon :
1. Sélectionner le type **PDF**
2. Cliquer sur **Choisir un fichier**
3. Sélectionner un fichier `.pdf` (max 20 Mo)
4. Le fichier est uploadé dans `uploads/pdf/` avec un nom unique sécurisé
5. Sauvegarder la leçon

---

## 4. Ajouter une vidéo

Dans le formulaire d'ajout de leçon :
1. Sélectionner le type **Vidéo**
2. Choisir un fichier `.mp4`, `.webm` ou `.ogg` (max 50 Mo)
3. Le fichier est uploadé dans `uploads/videos/`
4. Sauvegarder

---

## 5. Créer un quiz

Chaque leçon peut avoir **une évaluation active**.

1. Aller dans **Gestion des quiz** (`gestion_quiz.php`)
2. Sélectionner la leçon cible
3. Cliquer sur **Créer une évaluation**
4. Configurer :
   - **Titre** de l'évaluation
   - **Note requise** (en %, ex : 60)
   - **Durée** (en minutes, facultatif)
   - **Tentatives maximum** (défaut : 3)
5. Ajouter des **questions** :
   - Texte de la question
   - Points attribués
   - Temps limite par question (facultatif)
6. Pour chaque question, ajouter des **options** :
   - Texte de l'option
   - Cocher **Bonne réponse** pour la réponse correcte (une seule par question)
7. Sauvegarder

---

## 6. Publier un cours

Un cours ne peut être publié que si :
- Il possède **au moins une leçon avec une évaluation active**

Pour publier :
1. Dans la liste des cours, cliquer sur **Publier**
2. Confirmation via bouton AJAX
3. Le cours devient visible aux étudiants inscrits au module

---

## 7. Suivre les étudiants

Dans **Gestion des cours** :
- Chaque cours affiche le nombre d'étudiants inscrits (`nb_etudiants`)
- Le nombre de leçons créées (`nb_lecons`)

Dans le **Tableau de bord** enseignant :
- Statistiques : nombre de cours créés, leçons, étudiants suivis

---

## 8. Modifier ou supprimer

### Modifier un cours
- Bouton **Modifier** dans la liste des cours
- Le slug est régénéré si le titre change (sans écraser les slugs existants)

### Supprimer un cours
- Bouton **Supprimer** → confirmation AJAX
- La suppression est protégée : seul le propriétaire ou un Super Admin peut supprimer

### Supprimer une leçon
- Vérification automatique que la leçon appartient bien à votre cours (protection IDOR)

---

## 9. Sécurité enseignant

- Vous ne pouvez pas modifier les cours d'un autre enseignant
- Vous ne pouvez pas supprimer les leçons d'un autre cours
- Les évaluations et questions sont vérifiées par chaîne : `question → évaluation → leçon → cours → enseignant`
- Toutes les actions sont protégées par token CSRF
