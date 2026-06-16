<?php
// test_enseignant.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier si connecté en tant qu'enseignant
if (!estConnecte() || !estEnseignant()) {
    echo "❌ Vous n'êtes pas connecté en tant qu'enseignant<br>";
    echo "Connectez-vous d'abord avec prof.ndamba@gol.com / admin123";
    exit;
}

$id_enseignant = $_SESSION['id_utilisateur'];
echo "<h1>Test Enseignant</h1>";
echo "✅ Connecté en tant que: " . $_SESSION['email'] . "<br>";
echo "Rôle: " . $_SESSION['role'] . "<br>";

// Tester obtention des cours
if (function_exists('obtenirCoursParEnseignant')) {
    $cours = obtenirCoursParEnseignant($id_enseignant);
    echo "<h3>Mes cours (" . count($cours) . ")</h3>";
    foreach ($cours as $c) {
        echo "- " . $c['titre_cours'] . " (" . ($c['statut'] ?? 'brouillon') . ")<br>";
    }
} else {
    echo "❌ Fonction obtenirCoursParEnseignant() manquante<br>";
}

// Tester statistiques
if (function_exists('obtenirStatistiquesEnseignant')) {
    $stats = obtenirStatistiquesEnseignant($id_enseignant);
    echo "<h3>Statistiques</h3>";
    echo "Cours créés: " . ($stats['nb_cours'] ?? 0) . "<br>";
    echo "Leçons: " . ($stats['nb_lecons'] ?? 0) . "<br>";
    echo "Étudiants inscrits: " . ($stats['nb_etudiants'] ?? 0) . "<br>";
} else {
    echo "❌ Fonction obtenirStatistiquesEnseignant() manquante<br>";
}
?>