<?php
// base-de-donnees/connexion-bdd.php
class ConnexionBaseDeDonnees {
    private static $instance = null;
    private $connexion;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . HOTE_BDD . ";dbname=" . NOM_BDD . ";charset=utf8mb4";
            $this->connexion = new PDO($dsn, UTILISATEUR_BDD, MOT_DE_PASSE_BDD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            error_log("Connexion PDO établie avec succès");
        } catch (PDOException $e) {
            error_log("Erreur de connexion PDO: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public static function obtenirInstance() {
        if (self::$instance === null) {
            self::$instance = new ConnexionBaseDeDonnees();
        }
        return self::$instance;
    }

    public function obtenirConnexion() {
        return $this->connexion; 
    }
}
?>
