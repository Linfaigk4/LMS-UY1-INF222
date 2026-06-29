# GOL — Guide d'installation

## Prérequis système

| Composant | Version minimale |
|---|---|
| PHP | 8.0 |
| MySQL | 8.0 |
| Apache | 2.4 |
| XAMPP | 8.0 |

Extensions PHP requises : `pdo`, `pdo_mysql`, `fileinfo`, `json`, `mbstring`

---

## 1. Installation XAMPP (Linux)

```bash
# Télécharger XAMPP depuis https://www.apachefriends.org/
chmod +x xampp-linux-x64-*.run
sudo ./xampp-linux-x64-*.run
```

Démarrer les services :
```bash
sudo /opt/lampp/lampp start
```

Vérifier que Apache et MySQL sont actifs :
```
XAMPP: Starting Apache...  ok.
XAMPP: Starting MySQL...   ok.
```

---

## 2. Déployer les fichiers du projet

```bash
# Copier le projet dans le dossier htdocs
sudo cp -r GOL/ /opt/lampp/htdocs/GOL

# Ou avec Git :
cd /opt/lampp/htdocs
git clone [url-du-repo] GOL
```

---

## 3. Importer la base de données

### Via la ligne de commande

```bash
mysql -u root /opt/lampp/htdocs/GOL/database.sql
```

Si MySQL utilise un socket Unix (XAMPP Linux) :
```bash
mysql -u root --socket=/opt/lampp/var/mysql/mysql.sock < /opt/lampp/htdocs/GOL/database.sql
```

### Via phpMyAdmin

1. Ouvrir `http://localhost/phpmyadmin`
2. Créer une base `gol_lms` (charset `utf8mb4_unicode_ci`)
3. Onglet **Importer** → sélectionner `database.sql` → **Exécuter**

---

## 4. Configuration

Ouvrir `/opt/lampp/htdocs/GOL/includes/config.php` :

```php
// Base de données
define('DB_HOST', 'localhost');    // Hôte MySQL
define('DB_NAME', 'gol_lms');     // Nom de la base
define('DB_USER', 'root');        // Utilisateur MySQL
define('DB_PASS', '');            // Mot de passe MySQL (vide par défaut XAMPP)

// URL du site (doit se terminer par /)
define('SITE_URL', 'http://localhost/GOL/');
```

> **Production :** Modifier `DB_PASS` avec un mot de passe fort et `SITE_URL` avec le vrai domaine.

---

## 5. Permissions des dossiers uploads

Les dossiers d'upload doivent être accessibles en écriture par Apache :

```bash
sudo chmod -R 755 /opt/lampp/htdocs/GOL/uploads/
sudo chown -R daemon:daemon /opt/lampp/htdocs/GOL/uploads/
```

Dossiers concernés :
- `uploads/avatars/` — photos de profil
- `uploads/pdf/` — fichiers PDF des leçons
- `uploads/videos/` — vidéos des leçons
- `uploads/modules_images/` — images de couverture des modules

---

## 6. Configuration Apache (optionnel)

Pour les URLs propres, activer `mod_rewrite` :

```bash
sudo /opt/lampp/bin/apachectl -M | grep rewrite
# Si absent :
sudo sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /opt/lampp/etc/httpd.conf
sudo /opt/lampp/lampp restart
```

---

## 7. Vérification de l'installation

```bash
# Test syntaxe PHP de tous les fichiers
find /opt/lampp/htdocs/GOL -name "*.php" -not -path "*/.git/*" | xargs php -l 2>&1 | grep -v "No syntax errors"
```

Aucune sortie = aucune erreur.

Ouvrir dans le navigateur : `http://localhost/GOL/`  
La page d'accueil doit s'afficher sans erreur.

---

## 8. Créer le premier compte Super Admin

1. Aller sur `http://localhost/GOL/choix_inscription.php`
2. Créer un compte **Enseignant** (formulaire disponible publiquement)
3. Via phpMyAdmin, modifier le `role` de ce compte en `super_admin` :

```sql
UPDATE utilisateurs SET role = 'super_admin' WHERE email = 'votre@email.com';
```

---

## 9. Vérifier la connexion BDD

```bash
mysql -u root --socket=/opt/lampp/var/mysql/mysql.sock -e "SHOW TABLES FROM gol_lms;"
```

Tables attendues (16) : `utilisateurs`, `modules`, `cours`, `lecons`, `evaluations`, `questions`, `options`, `resultats_evaluations`, `progression_cours`, `progression_lecons`, `inscriptions_modules`, `certificats`, `notifications`, `logs_activite`, `demandes_modification`, `demandes_certificats`

---

## Problèmes courants

| Symptôme | Cause | Solution |
|---|---|---|
| Page blanche | Erreur PHP silencieuse | Activer `display_errors` temporairement dans config.php |
| Erreur BDD 2002 | MySQL utilise un socket | Vérifier `DB_SOCKET` dans config.php |
| Upload échoue | Permissions insuffisantes | `chmod -R 755 uploads/` |
| Erreur 403 CSRF | Session expirée | Se reconnecter |
| Police ne charge pas | Pas d'Internet | Installer Inter localement ou retirer le lien Google Fonts |
