<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si l'utilisateur est connecté et est enseignant
if (!estConnecte() || !estEnseignant()) {
    header('Location: connexion.php');
    exit;
}

$message = '';
$error = '';

// Récupérer les modules
$modules = $pdo->query("SELECT * FROM modules WHERE actif = 1 ORDER BY nom_module")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $objectifs = trim($_POST['objectifs'] ?? '');
    $difficulte = $_POST['difficulte'] ?? 'debutant';
    $duree = (int)($_POST['duree'] ?? 0);
    $id_module = (int)($_POST['id_module'] ?? 0);
    $prerequis = trim($_POST['prerequis'] ?? '');
    
    if (empty($titre) || empty($id_module)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        // Créer le slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
        
        // Insérer le cours directement
        $stmt = $pdo->prepare("
            INSERT INTO cours (titre_cours, slug, description, objectifs, difficulte, duree, id_module, id_enseignant, prerequis, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'brouillon')
        ");
        
        if ($stmt->execute([$titre, $slug, $description, $objectifs, $difficulte, $duree, $id_module, $_SESSION['id_utilisateur'], $prerequis])) {
            $message = 'Cours créé avec succès !';
            // Rediriger vers la gestion des leçons
            $id_cours = $pdo->lastInsertId();
            header("Location: gestion_lecons.php?cours_id=" . $id_cours);
            exit;
        } else {
            $error = 'Erreur lors de la création du cours.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un cours - GOL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 20px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 24px; font-size: 1.75rem; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.875rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.875rem; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #2563eb; }
        .form-textarea { resize: vertical; min-height: 100px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; }
        .btn-primary { background: #2563eb; color: white; width: 100%; }
        .btn-primary:hover { background: #1d4ed8; }
        .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2563eb; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="gestion_cours.php" class="back-link">← Retour à la liste des cours</a>
    
    <div class="card">
        <h1>📚 Créer un nouveau cours</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Titre du cours *</label>
                <input type="text" name="titre" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Module *</label>
                <select name="id_module" class="form-select" required>
                    <option value="">Sélectionnez un module</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= $module['id_module'] ?>"><?= htmlspecialchars($module['nom_module']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" placeholder="Décrivez le contenu du cours..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Objectifs pédagogiques</label>
                <textarea name="objectifs" class="form-textarea" placeholder="Un objectif par ligne..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Prérequis</label>
                <textarea name="prerequis" class="form-textarea" placeholder="Prérequis nécessaires..."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Difficulté</label>
                    <select name="difficulte" class="form-select">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Durée (minutes)</label>
                    <input type="number" name="duree" class="form-input" value="60">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Créer le cours</button>
        </form>
    </div>
</div>
</body>
</html>