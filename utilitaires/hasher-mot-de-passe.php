<?php
/**
 * Script utilitaire pour hasher les mots de passe
 * À exécuter UNE SEULE FOIS pour convertir les mots de passe en clair
 */

require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head><meta charset='UTF-8'><title>Hashage mot de passe</title></head>";
echo "<body style='font-family: Arial; padding: 40px; background: #f5f5f5;'>";

try {
    $fonctionsBDD = new FonctionsBaseDeDonnees();

    // Mot de passe en clair à hasher
    $motDePasseClair = 'entraineur123';

    // Générer le hash
    $hash = password_hash($motDePasseClair, PASSWORD_DEFAULT);

    echo "<div style='background: white; padding: 30px; border-radius: 10px; max-width: 600px;'>";
    echo "<h1 style='color: #27ae60;'>✅ Hashage réussi</h1>";
    echo "<p><strong>Mot de passe original :</strong> " . htmlspecialchars($motDePasseClair) . "</p>";
    echo "<p><strong>Hash généré :</strong></p>";
    echo "<code style='background: #f0f0f0; padding: 10px; display: block; word-wrap: break-word;'>" . $hash . "</code>";

    // Mettre à jour la base de données
    $sql = "UPDATE Entraineur
            SET mot_de_passe_entraineur = :hash
            WHERE email_entraineur = 'jean.dupont@club.com'";

    $resultat = $fonctionsBDD->executerRequete($sql, [':hash' => $hash]);

    if ($resultat) {
        echo "<p style='color: #27ae60; font-weight: bold; margin-top: 20px;'>✅ Base de données mise à jour avec succès !</p>";
        echo "<p>Le mot de passe de <strong>jean.dupont@club.com</strong> a été hashé.</p>";
    } else {
        echo "<p style='color: #e74c3c;'>❌ Erreur lors de la mise à jour de la base de données.</p>";
    }

    echo "<hr style='margin: 30px 0;'>";
    echo "<h2>⚠️ IMPORTANT</h2>";
    echo "<p style='color: #e67e22;'><strong>Supprimez ce fichier après utilisation pour des raisons de sécurité !</strong></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #fee; padding: 20px; border-left: 4px solid #e74c3c;'>";
    echo "<h2 style='color: #e74c3c;'>Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
