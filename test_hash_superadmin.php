<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

$email = 'superadmin@gol.com';
$password = 'admin123';

echo "<h1>Test Super Admin</h1>";

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ Utilisateur non trouvé!<br>";
    echo "Créez-le avec la requête INSERT ci-dessus.";
    exit;
}

echo "✅ Utilisateur trouvé: " . $user['email'] . "<br>";
echo "Rôle: " . $user['role'] . "<br>";
echo "Statut: " . $user['statut'] . "<br>";
echo "Hash stocké: " . substr($user['mot_de_passe'], 0, 35) . "...<br><br>";

// Tester le mot de passe
if (password_verify($password, $user['mot_de_passe'])) {
    echo "✅ MOT DE PASSE VALIDE !<br>";
    
    // Démarrer la session
    session_start();
    $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['role'] = $user['role'];
    
    echo "Session créée! <a href='tableau_bord.php'>Aller au tableau de bord</a>";
} else {
    echo "❌ MOT DE PASSE INVALIDE<br><br>";
    
    // Générer un nouveau hash
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Nouveau hash à utiliser: <code>$new_hash</code><br><br>";
    
    echo "Exécutez cette requête SQL:<br>";
    echo "<code>UPDATE utilisateurs SET mot_de_passe = '$new_hash' WHERE email = '$email';</code>";
}
?>