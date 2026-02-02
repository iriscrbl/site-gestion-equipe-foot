<?php
/**
 * Script pour hasher un mot de passe
 * À utiliser UNE SEULE FOIS puis à supprimer pour des raisons de sécurité
 */

// Remplacez 'votre_mot_de_passe' par le mot de passe que vous voulez utiliser
$motDePasse = 'entraineur123';

// Créer le hash
$hash = password_hash($motDePasse, PASSWORD_DEFAULT);

echo "Mot de passe : " . $motDePasse . "<br>";
echo "Hash généré : " . $hash . "<br><br>";

echo "Copiez ce hash et collez-le dans votre base de données :<br>";
echo "<textarea style='width:100%; height:100px;'>" . $hash . "</textarea><br><br>";

echo "<strong style='color:red;'>IMPORTANT : Supprimez ce fichier après utilisation pour des raisons de sécurité !</strong>";
?>
