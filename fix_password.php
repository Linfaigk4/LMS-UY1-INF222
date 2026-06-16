<?php
require_once 'includes/config.php';

$email = 'etudiant1@test.com';
$password = 'admin123';

echo "<h1>Diagnostic et correction du mot de passe</h1>";

// 1. Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT id_utilisateur, email, mot_de_passe FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    die("❌ Utilisateur non trouvé");
}

echo "Utilisateur: " . $user['email'] . "<br>";
echo "Hash actuel: " . substr($user['mot_de_passe'], 0, 30) . "...<br>";

// 2. Tester le hash
if (password_verify($password, $user['mot_de_passe'])) {
    echo "✅ Le hash est valide !<br>";
} else {
    echo "❌ Le hash est invalide. Recréation...<br>";
    
    // 3. Re-créer le hash
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Nouveau hash: " . $new_hash . "<br>";
    
    // 4. Mettre à jour
    $update = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
    if ($update->execute([$new_hash, $email])) {
        echo "✅ Mot de passe mis à jour avec succès !<br>";
    } else {
        echo "❌ Erreur lors de la mise à jour<br>";
    }
}

// 5. Tester avec le nouveau hash
$stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
$new_user = $stmt->fetch();

if (password_verify($password, $new_user['mot_de_passe'])) {
    echo "<br>🎉 CONNEXION RÉUSSIE ! Vous pouvez maintenant vous connecter.<br>";
} else {
    echo "<br>❌ Problème persistant. Vérifiez votre version de PHP.<br>";
    echo "Version PHP: " . phpversion() . "<br>";
}

echo "<br><a href='connexion.php'>→ Aller à la page de connexion</a>";
?>
