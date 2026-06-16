<?php
session_start();
echo "<pre>";
echo "Session avant déconnexion :<br>";
print_r($_SESSION);
echo "</pre>";

session_destroy();

echo "<pre>";
echo "Session après déconnexion :<br>";
print_r($_SESSION);
echo "</pre>";

echo "<br><a href='index.php'>Retour à l'accueil</a>";
?>