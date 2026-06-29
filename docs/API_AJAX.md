# API AJAX — GOL LMS

Point d'entrée unique : `ajax.php`  
Format : JSON  
Authentification : session PHP active obligatoire  
CSRF : header `X-CSRF-Token` ou champ `csrf_token` en POST (requis pour toutes les requêtes POST)

---

## Pré-conditions systématiques

Chaque requête doit satisfaire :
1. Header `X-Requested-With: XMLHttpRequest`
2. Utilisateur connecté (`$_SESSION['id_utilisateur']` défini)
3. Pour méthode POST : token CSRF valide

Réponse en cas d'échec global :
```json
{ "success": false, "message": "Session expirée. Veuillez vous reconnecter." }
{ "success": false, "message": "Token CSRF invalide" }
```

---

## Format standard des réponses

```json
{ "success": true,  "message": "...", "data": {...} }
{ "success": false, "message": "..." }
```

---

## Actions disponibles (35 actions)

### Demandes de modification

#### `approuver_demande`
- **Méthode :** POST
- **Rôle :** super_admin, promoteur
- **Paramètres :** `id_demande` (int)
- **Réponse :** `{ "success": true }`

#### `refuser_demande`
- **Méthode :** POST
- **Rôle :** super_admin, promoteur
- **Paramètres :** `id_demande` (int), `commentaire` (string)
- **Réponse :** `{ "success": true, "message": "Demande refusée" }`

---

### Modules

#### `supprimer_module`
- **Méthode :** POST
- **Rôle :** super_admin
- **Paramètres :** `id_module` (int)
- **Réponse :** `{ "success": true, "message": "Module supprimé" }`

#### `toggle_module_statut`
- **Méthode :** POST
- **Rôle :** super_admin, promoteur
- **Paramètres :** `id_module` (int)
- **Réponse :** `{ "success": true, "message": "Statut modifié" }`

---

### Utilisateurs

#### `toggle_user_status`
- **Méthode :** POST
- **Rôle :** super_admin
- **Paramètres :** `id_utilisateur` (int, ≠ soi-même)
- **Réponse :** `{ "success": true, "message": "Statut utilisateur modifié" }`

---

### Évaluations / Quiz

#### `soumettre_evaluation`
- **Méthode :** POST
- **Rôle :** etudiant
- **Paramètres :** `evaluation_id` (int), `reponses` (object), `temps_consacre` (int, optionnel)
- **Réponse :**
```json
{ "success": true, "score": 80, "reussi": true, "message": "Évaluation réussie !" }
```

#### `ajouter_evaluation`
- **Méthode :** POST
- **Rôle :** enseignant, super_admin
- **Paramètres :** `id_lecon` (int), `titre` (string), `description` (string), `note_requise` (float), `duree` (int), `tentative_max` (int)
- **Réponse :** `{ "success": true, "message": "Évaluation créée", "id": 12 }`

#### `modifier_evaluation`
- **Méthode :** POST
- **Rôle :** enseignant, super_admin
- **Paramètres :** `id_evaluation` (int), `titre`, `description`, `note_requise`, `duree`, `tentative_max`

#### `supprimer_evaluation`
- **Méthode :** POST
- **Rôle :** enseignant, super_admin
- **Paramètres :** `id_evaluation` (int)

#### `obtenir_evaluation_complete`
- **Méthode :** GET
- **Rôle :** tous connectés
- **Paramètres :** `id_lecon` (int, en GET)
- **Réponse :** évaluation avec questions et options imbriquées

---

### Questions

#### `ajouter_question`
- **Méthode :** POST
- **Rôle :** enseignant, super_admin
- **Paramètres :** `id_evaluation` (int), `texte_question` (string), `points` (int), `temps_limite` (int, optionnel)
- **Réponse :** `{ "success": true, "id": 45 }`

#### `modifier_question`
- **Méthode :** POST
- **Paramètres :** `id_question` (int), `texte_question`, `points`, `temps_limite`

#### `supprimer_question`
- **Méthode :** POST
- **Paramètres :** `id_question` (int)

---

### Options de réponse

#### `ajouter_option`
- **Méthode :** POST
- **Paramètres :** `id_question` (int), `texte_option` (string), `est_correcte` (1/0)
- **Remarque :** Si `est_correcte=1`, toutes les autres options de la question passent à 0

#### `modifier_option`
- **Méthode :** POST
- **Paramètres :** `id_option` (int), `texte_option`, `est_correcte`

#### `supprimer_option`
- **Méthode :** POST
- **Paramètres :** `id_option` (int)

---

### Progression

#### `maj_progression`
- **Méthode :** POST
- **Rôle :** etudiant
- **Paramètres :** `lecon_id` (int), `action_progression` (`ouvrir` | `valider`)
- **Réponse :**
```json
{ "success": true, "pourcentage": 66, "pct_module": 33, "message": "Progression mise à jour" }
```

---

### Inscription module

#### `inscrire_module`
- **Méthode :** POST
- **Rôle :** etudiant
- **Paramètres :** `id_module` (int)
- **Réponse :** `{ "success": true }`

---

### Recherche

#### `recherche`
- **Méthode :** GET
- **Rôle :** tous connectés
- **Paramètres :** `q` (string, min 2 caractères)
- **Réponse :**
```json
{
  "success": true,
  "data": {
    "modules": [{ "id_module": 1, "nom_module": "...", "niveau": "..." }],
    "cours":   [{ "id_cours": 1, "titre_cours": "...", "nom_module": "..." }]
  }
}
```

---

### Notifications

#### `marquer_notification_lue`
- **Méthode :** POST
- **Paramètres :** `id_notification` (int)

#### `obtenir_notifications`
- **Méthode :** GET
- **Réponse :** `{ "success": true, "data": [...], "count": 3 }`

---

### Statistiques

#### `statistiques_globales`
- **Méthode :** GET
- **Rôle :** super_admin, promoteur
- **Réponse :** `{ "success": true, "data": { "nb_etudiants": 120, "nb_enseignants": 8, "nb_modules": 5, "nb_certificats": 45 } }`

#### `statistiques_utilisateur`
- **Méthode :** GET
- **Rôle :** etudiant, enseignant
- **Réponse :** selon le rôle — stats étudiant ou enseignant

---

### Certificats

#### `generer_certificat`
- **Méthode :** POST
- **Rôle :** etudiant
- **Paramètres :** `id_module` (int)
- **Condition :** progression 100 % + note ≥ note_requise
- **Réponse :** données du certificat généré

#### `obtenir_certificat`
- **Méthode :** GET
- **Paramètres :** `id` (int, en GET)

#### `demander_certificat` (alias : `creer_demande_certificat`)
- **Méthode :** POST
- **Rôle :** etudiant
- **Paramètres :** `id_module` (int), `motif` (string)

#### `approuver_demande_certificat`
- **Méthode :** POST
- **Rôle :** super_admin
- **Paramètres :** `id_demande` (int)

#### `refuser_demande_certificat`
- **Méthode :** POST
- **Rôle :** super_admin
- **Paramètres :** `id_demande` (int), `commentaire` (string)

---

### Avatar

#### `upload_avatar`
- **Méthode :** POST (multipart/form-data)
- **Rôle :** tous connectés
- **Paramètres :** `avatar` (fichier, JPG/PNG/WebP/GIF/SVG, max 2 Mo)
- **Réponse :** `{ "success": true, "avatar": "avatar_xxx.jpg", "message": "Avatar mis à jour" }`

---

### Cours

#### `supprimer_cours`
- **Méthode :** POST
- **Rôle :** enseignant (propriétaire), super_admin
- **Paramètres :** `id_cours` (int)

#### `publier_cours`
- **Méthode :** POST
- **Rôle :** enseignant (propriétaire), super_admin
- **Paramètres :** `id_cours` (int)
- **Condition :** le cours doit avoir ≥ 1 leçon avec évaluation active

---

### Leçons

#### `supprimer_lecon`
- **Méthode :** POST
- **Rôle :** enseignant (propriétaire), super_admin
- **Paramètres :** `id_lecon` (int)
- **Sécurité :** vérification IDOR via `supprimerLecon($id, $id_enseignant)`

---

## Codes HTTP retournés

| Code | Signification |
|---|---|
| 200 | Succès ou échec métier (dans le JSON) |
| 403 | Token CSRF invalide ou permission refusée |
| 503 | Erreur de connexion BDD |
