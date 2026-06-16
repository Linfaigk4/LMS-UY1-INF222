<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

echo "<h1>Vérification des fonctions</h1>";

$fonctions = [
    'obtenirCoursParEnseignant',
    'obtenirStatistiquesEnseignant', 
    'obtenirModulesInscrits',
    'obtenirActivitesRecentes',
    'obtenirDemandesEnAttente'
];

foreach ($fonctions as $f) {
    if (function_exists($f)) {
        echo "✅ $f() existe<br>";
    } else {
        echo "❌ $f() MANQUANTE<br>";
    }
}
?>