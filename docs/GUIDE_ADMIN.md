# Guide Administrateur — GOL LMS

Ce guide s'adresse au **Super Admin** et aux **Promoteurs**.

---

## Accès

Le lien **Administration** apparaît dans la navbar uniquement pour le rôle `super_admin`.  
Les promoteurs ont accès à certaines statistiques et à la validation des demandes.

---

## 1. Tableau de bord administrateur

URL : `tableau_bord.php`

Affiche selon le rôle :
- Nombre d'étudiants actifs
- Nombre d'enseignants actifs
- Nombre de modules
- Nombre de certificats délivrés
- Demandes de modification en attente
- Demandes de certificats exceptionnels en attente

---

## 2. Gestion des utilisateurs

URL : `administration.php`

### Voir tous les utilisateurs

La liste affiche pour chaque utilisateur :
- Nom, prénom, email
- Rôle (`etudiant`, `enseignant`, `promoteur`, `super_admin`)
- Statut (`actif`, `suspendu`, `en_attente`)
- Date d'inscription

### Activer / Suspendre un compte

Bouton **Activer/Suspendre** → appel AJAX `toggle_user_status`.  
Un Super Admin ne peut pas se suspendre lui-même.

### Ajouter un utilisateur manuellement

Formulaire dans `administration.php` :
- Nom, prénom, email, mot de passe, rôle
- Le mot de passe est hashé avec `password_hash(PASSWORD_DEFAULT)`
- Token CSRF requis

---

## 3. Gestion des modules

URL : `administration.php` (section Modules)

### Créer un module

Formulaire :
- Nom du module
- Description
- Objectifs
- Niveau (débutant / intermédiaire / avancé / expert)
- Image de couverture

### Activer / Désactiver un module

Bouton toggle → appel AJAX `toggle_module_statut`.  
Un module désactivé n'est plus visible par les étudiants.

### Supprimer un module

Bouton **Supprimer** → appel AJAX `supprimer_module`.  
⚠️ La suppression est en cascade : tous les cours, leçons et évaluations associés sont supprimés.

---

## 4. Validation des demandes de modification

Les utilisateurs peuvent soumettre des demandes de modification de données (email, nom, téléphone, mot de passe) via leur profil.

Dans le tableau de bord :
1. Section **Demandes en attente**
2. Cliquer **Approuver** → la modification est appliquée, l'utilisateur est notifié
3. Cliquer **Refuser** → saisir un commentaire → l'utilisateur est notifié

Actions AJAX : `approuver_demande`, `refuser_demande`

---

## 5. Certificats exceptionnels

Les étudiants peuvent demander un certificat exceptionnel sans avoir 100 % de progression.

Dans le tableau de bord :
1. Section **Demandes de certificats**
2. Lire la justification de l'étudiant
3. **Approuver** → le certificat est généré avec `validation_exceptionnelle = 1`
4. **Refuser** → commentaire obligatoire → l'étudiant est notifié

Actions AJAX : `approuver_demande_certificat`, `refuser_demande_certificat`

---

## 6. Statistiques

Accessible via AJAX `statistiques_globales` (rôle `super_admin` ou `promoteur`) :
- `nb_etudiants` : étudiants actifs
- `nb_enseignants` : enseignants actifs
- `nb_modules` : modules actifs
- `nb_certificats` : certificats valides

---

## 7. Journaux d'activité

La table `logs_activite` enregistre automatiquement :
- L'ID de l'utilisateur
- L'action effectuée
- Les détails (JSON)
- L'adresse IP
- Le User-Agent
- La date

Actuellement consultable via phpMyAdmin ou requête SQL directe.

---

## 8. Maintenance

### Sauvegarder la base de données

```bash
mysqldump -u root --socket=/opt/lampp/var/mysql/mysql.sock gol_lms > backup_gol_$(date +%Y%m%d).sql
```

### Vider les uploads non utilisés

```bash
# Identifier les fichiers orphelins (non référencés en BDD)
# À faire manuellement via phpMyAdmin + inspection des dossiers uploads/
```

### Réinitialiser un mot de passe

```sql
UPDATE utilisateurs 
SET mot_de_passe = '$2y$10$...' -- hash généré par password_hash()
WHERE email = 'user@example.com';
```

Ou approuver une demande de modification de mot de passe soumise par l'utilisateur.

---

## 9. Sécurité

- Tous les formulaires POST sont protégés par token CSRF
- Les sessions expirent à la fermeture du navigateur (cookie `lifetime=0`)
- Les cookies de session sont `httponly` et `samesite=Lax`
- `session_regenerate_id(true)` est appelé après chaque connexion
- Les mots de passe ne sont jamais stockés en clair (`PASSWORD_DEFAULT`)
- Les erreurs BDD ne sont pas affichées à l'utilisateur (loggées via `error_log`)
