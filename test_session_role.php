<?php
session_start();
echo "<pre>";
echo "Session complète :\n";
print_r($_SESSION);
echo "\n";
echo "Rôle dans session : " . ($_SESSION['role'] ?? 'non défini') . "\n";
echo "ID utilisateur : " . ($_SESSION['id_utilisateur'] ?? 'non défini') . "\n";
echo "</pre>";
?>