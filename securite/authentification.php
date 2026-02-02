<?php
// securite/authentification.php
require_once CHEMIN_BDD . '/fonctions-bdd.php';

class Authentification {
    private $fonctionsBDD;

    public function __construct() {
        $this->fonctionsBDD = new FonctionsBaseDeDonnees();
    }

    public function connecterEntraineur($email, $motDePasse) {
        // Nettoyer l'email
        $email = htmlspecialchars(trim($email));

        // Chercher l'entraîneur
        $sql = "SELECT * FROM Entraineur WHERE email_entraineur = :email";
        $entraineur = $this->fonctionsBDD->obtenirUnResultat($sql, [':email' => $email]);

        // DEBUG - à enlever après
        error_log("Email recherché: " . $email);
        error_log("Entraineur trouvé: " . ($entraineur ? 'OUI' : 'NON'));
        if ($entraineur) {
            error_log("Hash stocké: " . $entraineur['mot_de_passe_entraineur']);
            error_log("password_verify: " . (password_verify($motDePasse, $entraineur['mot_de_passe_entraineur']) ? 'OK' : 'NOK'));
        }

        // Vérifier le mot de passe hashé
        if ($entraineur && password_verify($motDePasse, $entraineur['mot_de_passe_entraineur'])) {
            // Créer la session
            $_SESSION['id_entraineur'] = $entraineur['id_entraineur'];
            $_SESSION['nom_entraineur'] = $entraineur['nom_entraineur'];
            $_SESSION['prenom_entraineur'] = $entraineur['prenom_entraineur'];
            $_SESSION['email_entraineur'] = $entraineur['email_entraineur'];

            return true;
        }

        return false;
    }

    public static function estConnecte() {
        return isset($_SESSION['id_entraineur']) && !empty($_SESSION['id_entraineur']);
    }

    public static function verifierConnexion() {
        if (!self::estConnecte()) {
            header('Location: ../connexion.php');
            exit();
        }
    }

    public static function deconnecter() {
        session_destroy();
        header('Location: ../connexion.php');
        exit();
    }
}
?>
