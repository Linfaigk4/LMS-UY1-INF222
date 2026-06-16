<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

$password = 'admin123';
$new_hash = password_hash($password, PASSWORD_DEFAULT);

// Liste des emails enseignants
$enseignants = [
    'prof.ndamba@gol.com',
    'prof.essomba@gol.com',
    'prof.mbarga@gol.com',
    'prof.ngono@gol.com',
    'prof.atangana@gol.com'
];

echo "<h1>Correction des mots de passe enseignants</h1>";
echo "Nouveau hash: " . $new_hash . "<br><br>";

foreach ($enseignants as $email) {
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
    if ($stmt->execute([$new_hash, $email])) {
        echo "✅ Mot de passe mis à jour pour: $email<br>";
    } else {
        echo "❌ Erreur pour: $email<br>";
    }
}

// Vérification
echo "<h2>Vérification</h2>";
$stmt = $pdo->prepare("SELECT email, LEFT(mot_de_passe, 30) as hash FROM utilisateurs WHERE role = 'enseignant'");
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "📧 " . $user['email'] . " - Hash: " . $user['hash'] . "...<br>";
}

echo "<br><a href='connexion.php'>Aller à la page de connexion</a>";
?>