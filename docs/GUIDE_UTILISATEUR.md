# Guide Utilisateur — GOL LMS

Ce guide s'adresse aux **étudiants** souhaitant utiliser la plateforme GOL.

---

## 1. Inscription

1. Aller sur `http://localhost/GOL/`
2. Cliquer sur **S'inscrire** (bouton vert dans la barre de navigation)
3. Choisir **Étudiant**
4. Remplir le formulaire :
   - Prénom, Nom
   - Adresse email (unique)
   - Mot de passe (minimum recommandé : 8 caractères)
5. Cliquer sur **Créer mon compte**
6. Vous êtes automatiquement connecté et redirigé vers votre tableau de bord.

---

## 2. Connexion

1. Cliquer sur **Connexion** dans la barre de navigation
2. Saisir votre email et votre mot de passe
3. Cliquer sur **Se connecter**

> Si votre compte est suspendu, un message d'erreur s'affiche. Contacter l'administrateur.

---

## 3. Tableau de bord

Après connexion, le tableau de bord affiche :
- **Vos modules en cours** avec leur progression
- **Vos statistiques** : modules inscrits, progression moyenne, certificats obtenus
- **Vos activités récentes**
- **Vos notifications** non lues

---

## 4. S'inscrire à un module

1. Aller sur **Accueil** (`index.php`)
2. Parcourir les modules disponibles
3. Cliquer sur un module pour voir sa description
4. Cliquer sur **S'inscrire à ce module**
5. Le module apparaît dans votre tableau de bord

---

## 5. Suivre un cours

1. Dans votre tableau de bord, cliquer sur un module
2. Sélectionner un cours dans la liste
3. Choisir une leçon
4. La leçon s'ouvre : texte, PDF ou vidéo selon le type
5. Votre progression passe automatiquement à **50 %** à l'ouverture

---

## 6. Passer une évaluation (quiz)

1. Depuis la page d'une leçon, cliquer sur **Passer l'évaluation**
2. Répondre à toutes les questions à choix multiple
3. Cliquer sur **Soumettre**
4. Votre score s'affiche immédiatement
5. Si le score ≥ note requise :
   - La leçon passe à **100 %**
   - La progression du cours est mise à jour
6. Si le score est insuffisant, vous pouvez réessayer (selon le nombre de tentatives autorisé)

---

## 7. Progression

La progression fonctionne sur 3 niveaux :

| Statut | Valeur | Condition |
|---|---|---|
| Non commencé | 0 % | Leçon jamais ouverte |
| En cours | 50 % | Leçon ouverte, quiz non réussi |
| Terminé | 100 % | Quiz réussi |

La progression du **cours** est la moyenne des statuts de toutes ses leçons.  
La progression du **module** est la moyenne des progressions de tous ses cours.

---

## 8. Obtenir un certificat

Le certificat est généré **automatiquement** lorsque :
1. Vous avez complété **toutes les leçons** du module (100 % chacune)
2. Votre note finale est supérieure ou égale à la note requise

Le certificat apparaît dans votre tableau de bord avec un **code unique** (format `GOL-XXXX-YYYYMMDD`).

### Demande de certificat exceptionnel

Si vous estimez mériter un certificat sans avoir atteint 100 % :
1. Aller dans votre profil
2. Section **Certificats**
3. Cliquer sur **Demander un certificat**
4. Rédiger votre justification
5. Attendre la validation du Super Admin

---

## 9. Profil

Accessible via **Profil** dans la barre de navigation.

Vous pouvez :
- Modifier votre photo de profil (formats JPG, PNG, WebP — max 2 Mo)
- Consulter vos statistiques personnelles
- Voir vos demandes de modification en attente
- Soumettre une demande de modification (email, nom, téléphone, mot de passe)

> Les modifications de données sensibles nécessitent une validation par l'administrateur.

---

## 10. Déconnexion

Cliquer sur **Déconnexion** (bouton rouge) dans la barre de navigation.  
La session est détruite, le cookie de session supprimé. Vous êtes redirigé vers la page de connexion.

---

## 11. Thème clair / sombre

Cliquer sur l'icône soleil/lune dans la barre de navigation pour basculer entre le thème clair et sombre.  
Votre préférence est sauvegardée dans `localStorage` et un cookie, persistant entre les sessions.
