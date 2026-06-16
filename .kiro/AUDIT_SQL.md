# AUDIT SQL — GOL LMS

## Tables définies dans database.sql

| Table | Présente | Utilisée dans le code | Problèmes |
|-------|----------|-----------------------|-----------|
| `utilisateurs` | ✅ | ✅ Partout | OK |
| `demandes_modification` | ✅ | ✅ fonctions.php, administration.php | OK |
| `modules` | ✅ | ✅ Partout | OK |
| `inscriptions_modules` | ✅ | ✅ fonctions.php, module.php | `progression_globale` jamais mise à jour |
| `cours` | ✅ | ✅ Partout | OK |
| `lecons` | ✅ | ✅ Partout | `ordre_affichage` ignoré dans gestion_cours.php |
| `evaluations` | ✅ | ✅ evaluation.php, lecon.php, cours.php | OK — lié aux leçons (conforme) |
| `questions` | ✅ | ✅ evaluation.php, fonctions.php | Colonne `temps_limite` manquante |
| `options` | ✅ | ✅ evaluation.php | OK |
| `resultats_evaluations` | ✅ | ✅ evaluation.php, ajax.php | Contrainte UNIQUE sur (id_utilisateur, id_evaluation, tentative_numero) → peut bloquer si mal gérée |
| `progression_cours` | ✅ | ✅ fonctions.php, cours.php | Formule à corriger ; `lecons_terminees` JSON |
| `certificats` | ✅ | ✅ certificat.php, ajax.php | Lié au MODULE (conforme aux règles métier officielles) |
| `notifications` | ✅ | ✅ fonctions.php, tableau_bord.php | OK |
| `logs_activite` | ✅ | ✅ fonctions.php | OK — peu utilisé |

---

## Tables manquantes

### `enseignants` — CRITIQUE
- **Référencée dans** : `inscription_enseignant.php` ligne 70
- **SQL** : `INSERT INTO enseignants (id_utilisateur, specialite, biographie, statut) VALUES (?, ?, ?, 'en_attente')`
- **Absente** du schéma `database.sql`
- **Impact** : Crash SQL fatal à chaque inscription enseignant
- **Solution** : Supprimer l'INSERT dans `inscription_enseignant.php` (les champs `specialite` et `bio` existent déjà dans `utilisateurs`)

### `demandes_certificats` — IMPORTANTE
- **Référencée dans** : Aucun fichier PHP actuellement
- **Requise par** : Cahier des charges (workflow certificat exceptionnel)
- **Structure à créer** :
```sql
CREATE TABLE demandes_certificats (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    id_etudiant INT NOT NULL,
    id_module INT NOT NULL,
    motif TEXT NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'approuve', 'refuse') DEFAULT 'en_attente',
    date_traitement DATETIME,
    id_admin_traitant INT,
    commentaire_admin TEXT,
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    FOREIGN KEY (id_admin_traitant) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL,
    INDEX idx_etudiant (id_etudiant),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Colonnes manquantes

### `questions.temps_limite` — IMPORTANTE
- **Requis par** : Timer par question (30/45/60/90/120 sec)
- **Actuellement** : Seule `evaluations.duree` (timer global) existe
- **SQL à ajouter** :
```sql
ALTER TABLE questions ADD COLUMN temps_limite INT DEFAULT NULL COMMENT 'Durée en secondes (30/45/60/90/120). NULL = pas de timer individuel';
```

### `progression_cours.statut_lecon` — NON NÉCESSAIRE
- Le cahier des charges révisé utilise : 0% / 50% / 100% par leçon
- La table `progression_cours` stocke un JSON `lecons_terminees` et un `pourcentage` global
- La nouvelle formule nécessite de stocker l'état **par leçon** (0=non commencé, 50=ouvert, 100=quiz réussi)
- **Solution** : Utiliser le JSON `lecons_terminees` existant avec une structure enrichie :
  ```json
  {"id_lecon": 12, "statut": 50}
  ```
  OU créer une table dédiée `progression_lecons` (recommandé)
- **SQL recommandé** :
```sql
CREATE TABLE progression_lecons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_lecon INT NOT NULL,
    statut TINYINT DEFAULT 0 COMMENT '0=non commencé, 50=ouvert, 100=quiz réussi',
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_completion DATETIME,
    UNIQUE KEY unique_prog (id_utilisateur, id_lecon),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_lecon) REFERENCES lecons(id_lecon) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Incohérences de nommage

| Problème | Localisation | Impact |
|----------|-------------|--------|
| `certificats.id_module` correct | database.sql | ✅ Conforme (certificat lié au module) |
| `cours.id_enseignant` → pointe vers `utilisateurs` | database.sql | ✅ OK — pas de table `enseignants` séparée |
| `inscriptions_modules.progression_globale` jamais mis à jour | fonctions.php | ❌ Bug bloquant certificats |
| `progression_cours.lecons_terminees` JSON simple (liste d'IDs) | fonctions.php ligne 390 | ⚠️ Incompatible avec la nouvelle formule 0/50/100 |
| `resultats_evaluations` UNIQUE sur (id_utilisateur, id_evaluation, tentative_numero) | database.sql | ⚠️ Si tentative_numero mal calculé → erreur SQL |
| `rechercherGlobal()` appelée dans ajax.php ligne 192 | ajax.php | ❌ Fonction inexistante dans fonctions.php → Fatal Error |
| `inscrire_module` appelé dans module.php | ajax.php | ❌ Case inexistant dans le switch de ajax.php |

---

## État de la table `demandes_certificats`

**ABSENTE** du schéma SQL et de toute la codebase.

Doit être créée comme décrit ci-dessus.

---

## Calcul de la progression — Comportement réel documenté

### Niveau leçon (fonction `marquerLeconTerminee()`)
```
Formule actuelle :
  lecons_terminees[] += id_lecon  (si pas déjà dedans)
  pourcentage = (count(lecons_terminees) / total_lecons_cours) × 100

Formule attendue (règles métier officielles) :
  leçon non commencée = 0%
  leçon ouverte = 50%
  quiz réussi = 100%
  → Progression cours = moyenne(statut de chaque leçon)
```
**Écart** : La formule actuelle ne distingue pas "ouvert" de "terminé sans quiz".

### Niveau cours (dans `marquerLeconTerminee()`)
```
Actuel : pourcentage par cours = leçons terminées / total leçons × 100
Attendu : moyenne des statuts de leçons (0/50/100)
```

### Niveau module (fonction `calculerProgressionModule()`)
```
Actuel : somme(progression_cours.pourcentage) / nb_cours_publiés
→ Correct mathématiquement SI progression cours est correct
→ JAMAIS synchronisé avec inscriptions_modules.progression_globale
```

### Mise à jour de `inscriptions_modules.progression_globale`
**JAMAIS effectuée** dans le code actuel.  
→ Reste à 0% pour tous les étudiants.  
→ La condition `im.progression_globale >= 100` dans `certificat.php` n'est donc JAMAIS vraie.  
→ Aucun certificat ne peut être généré automatiquement.

---

## Clés étrangères — Vérification

| Relation | Définie | Cohérente |
|----------|---------|-----------|
| cours → modules | ✅ | ✅ |
| cours → utilisateurs (enseignant) | ✅ | ✅ |
| lecons → cours | ✅ | ✅ |
| evaluations → lecons | ✅ | ✅ (conforme aux règles métier) |
| questions → evaluations | ✅ | ✅ |
| options → questions | ✅ | ✅ |
| resultats_evaluations → utilisateurs + evaluations | ✅ | ✅ |
| progression_cours → utilisateurs + cours | ✅ | ✅ |
| certificats → utilisateurs + modules | ✅ | ✅ (lié module — conforme) |
| inscriptions_modules → utilisateurs + modules | ✅ | ✅ |
| notifications → utilisateurs | ✅ | ✅ |
| demandes_modification → utilisateurs × 2 | ✅ | ✅ |
| enseignants → utilisateurs | ❌ TABLE ABSENTE | ❌ |
| demandes_certificats → utilisateurs + modules | ❌ TABLE ABSENTE | ❌ |
