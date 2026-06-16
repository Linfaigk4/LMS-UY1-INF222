# ÉTAT ACTUEL — includes/header.php

## Responsabilités actuelles

Le fichier `includes/header.php` est le point d'entrée HTML de **toutes les pages** incluant le design system.  
Il génère :
1. Le `<!DOCTYPE html>`, `<html lang="fr">`, `<head>` complet
2. L'import Google Fonts Inter (CDN externe)
3. Le bloc `:root {}` des variables CSS (version réduite)
4. Le bloc `[data-theme="dark"]` (version réduite)
5. La barre de navigation `.navbar-premium` (HTML + CSS inline)
6. L'ouverture du `<main class="main-content">`
7. Les fonctions JS inline : `chargerTheme()`, `changerTheme()`
8. L'initialisation du thème au `DOMContentLoaded`

---

## Dépendances CSS

### Variables définies dans header.php (`:root`)
| Variable | Valeur | Présente dans style.css ? |
|----------|--------|--------------------------|
| `--primaire` | #2563eb | ✅ |
| `--primaire-clair` | #3b82f6 | ✅ |
| `--primaire-sombre` | #1d4ed8 | ✅ |
| `--accent` | #06b6d4 | ✅ |
| `--succes` | #22c55e | ✅ |
| `--danger` | #ef4444 | ✅ |
| `--avertissement` | #f59e0b | ✅ |
| `--fond` | #f8fafc | ✅ |
| `--fond-secondaire` | #f1f5f9 | ✅ |
| `--carte` | #ffffff | ✅ |
| `--carte-hover` | #f8fafc | ✅ |
| `--carte-border` | #e2e8f0 | ✅ |
| `--texte` | #0f172a | ✅ |
| `--texte-secondaire` | #475569 | ✅ |
| `--texte-tertiaire` | #64748b | ✅ |
| `--bordure` | #e2e8f0 | ✅ |
| `--ombre-sm` | shadow | ✅ |
| `--ombre-md` | shadow | ✅ |
| `--ombre-lg` | shadow | ✅ |
| `--radius-lg` | 0.75rem | ✅ |
| `--radius-xl` | 1rem | ✅ |
| `--radius-2xl` | 1.5rem | ✅ |
| `--radius-full` | 9999px | ✅ |
| `--spacing-1..8` | 0.25rem..2rem | Partiel |
| `--transition-base` | 0.3s ease | ✅ |

### Variables MANQUANTES dans header.php (présentes dans style.css uniquement)
- `--ombre-xl` — utilisé dans 8+ fichiers PHP
- `--primaire-gradient` — utilisé dans inscriptions, profil
- `--secondaire` — utilisé dans module.php, cours.php, index.php
- `--info` — utilisé dans notifications

### Variables MANQUANTES partout (ni header.php ni style.css)
| Variable manquante | Utilisée dans |
|--------------------|---------------|
| `--ombre-glow` | index.php, certificat.php, inscription_etudiant.php, lecon.php |
| `--glass-bg` | index.php, choix_inscription.php |
| `--glass-border` | index.php, choix_inscription.php |
| `--radius-md` | administration.php, lecon.php, certificat.php |
| `--radius-sm` | index.php |
| `--radius-2xl` | Header définit, style.css aussi — ✅ OK |
| `--spacing-1..3,5` | Nombreuses pages — définies dans style.css mais pas dans header.php |

**Impact** : Ces variables CSS non définies provoquent des valeurs `undefined` silencieuses → les propriétés CSS correspondantes sont ignorées par le navigateur.

---

## Dépendances JS

### Fonctions définies DANS header.php (inline)
| Fonction | Rôle | Conflit avec app.js ? |
|----------|------|-----------------------|
| `chargerTheme()` | Lit localStorage, applique `data-theme` sur `<body>` | ⚠️ **OUI** — app.js définit la même fonction sur `document.documentElement` |
| `changerTheme()` | Toggle thème, écrit localStorage | ⚠️ **OUI** — app.js définit une version différente |

**Problème critique** : `header.php` applique le thème sur `document.body`, `app.js` l'applique sur `document.documentElement`. Les deux coexistent → comportement incohérent selon la page.

### Fonctions attendues mais NON définies dans header.php
Ces fonctions sont appelées dans les pages mais ne sont définies QUE dans `app.js` :
- `envoyerRequeteAjax()` — utilisé dans cours.php, evaluation.php, lecon.php, module.php, certificat.php, administration.php
- `afficherNotification()` — utilisé dans cours.php, lecon.php, evaluation.php, administration.php
- `ouvrirMenuMobile()` / `fermerMenuMobile()` — définis dans app.js, non appelés faute de bouton hamburger

### Problème majeur : app.js n'est jamais inclus
**`assets/js/app.js` n'est référencé dans aucun fichier PHP** (`grep -rn "app.js"` → 0 résultat).  
→ `envoyerRequeteAjax()` et `afficherNotification()` ne sont pas disponibles via ce fichier.  
→ Ces fonctions sont **redéfinies inline** dans chaque page qui en a besoin.

### Fonctions modal — Incohérence de nommage
| Appel dans les pages | Défini dans app.js | Définition réelle |
|---------------------|-------------------|-------------------|
| `openModal()` | `ouvrirModal()` | Redéfini localement dans administration.php |
| `closeModal()` | `fermerModal()` | Redéfini localement dans administration.php |
| `gestion_lecons.php` appelle `openModal()/closeModal()` | — | Fonction locale non définie dans ce fichier |

---

## Problèmes identifiés

### P1 — CRITIQUE : app.js non chargé
`assets/js/app.js` n'est jamais inclus. Toutes les fonctions globales (`envoyerRequeteAjax`, `afficherNotification`, `ouvrirMenuMobile`) sont inaccessibles via le système centralisé.

### P2 — CRITIQUE : Duplication et conflit du système de thème
- `header.php` : `document.body.setAttribute('data-theme', theme)` + cookie
- `app.js` : `document.documentElement.setAttribute('data-theme', theme)` + localStorage + cookie
- Le thème sombre ne fonctionnera pas sur les pages qui n'ont pas les deux

### P3 — IMPORTANT : Navigation mobile absente
- `header.php` masque `.nav-menu` à `max-width: 768px` via `display: none`
- Aucun bouton hamburger dans le HTML du header
- `ouvrirMenuMobile()` défini dans app.js mais sans élément `#mobileSidebar` dans le header
- **Navigation impossible sur mobile**

### P4 — IMPORTANT : Toast notifications manquantes
- `afficherNotification()` est redéfini localement dans les pages qui en ont besoin
- Aucune règle CSS pour `.toast-notification`, `.toast-succes`, `.toast-danger`, `.toast-info` dans header.php ni style.css

### P5 — IMPORTANT : Variables CSS manquantes
- 6 variables utilisées dans des pages non définies dans header.php ni style.css
- Impact visuel silencieux : effets glow, glass, radius-md ignorés

### P6 — CONFORT : thème sombre incomplet sur 4 pages
- `creer_cours.php`, `gestion_cours.php` : leur propre DOCTYPE, pas d'inclusion de header.php → thème ignoré
- `app.js` non chargé → `changerTheme()` inaccessible

### P7 — CONFORT : Style CSS non inclus comme fichier externe
- `assets/css/style.css` n'est **jamais inclus** via `<link>` dans header.php
- Chaque page redéfinit ses propres styles en inline
- `style.css` existe mais n'est utilisé par aucune page (sauf potentiellement index.php)

---

## Risques de modification de header.php

| Risque | Niveau | Mitigation |
|--------|--------|-----------|
| Modifier le thème JS → casser toutes les pages | CRITIQUE | Unifier sur `document.documentElement`, supprimer la version body |
| Ajouter app.js → double définition de fonctions | ÉLEVÉ | Supprimer les redéfinitions inline dans chaque page avant |
| Ajouter menu hamburger → besoin d'un `#mobileSidebar` HTML | MOYEN | Ajouter le HTML de la sidebar mobile dans header.php même |
| Modifier les variables CSS → impact toutes les pages | MOYEN | Ajouter uniquement les variables manquantes |
| Inclure style.css → risque de surcharge des styles inline | MOYEN | Vérifier spécificité avant ; style.css utilise des sélecteurs globaux |
