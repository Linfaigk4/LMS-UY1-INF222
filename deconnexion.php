<?php
/**
 * GOL (Gugle Online Learning) - Déconnexion
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

// Forcer le démarrage de la session
session_start();

// Vider le tableau de session
$_SESSION = array();

// Supprimer le cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger
header('Location: connexion.php');
exit;
?>