<?php
// Démarrage de la session
session_start();

// Configuration de la base de données
define('HOTE_BDD', 'localhost');
define('NOM_BDD', 'projetequipe');
define('UTILISATEUR_BDD', 'root');
define('MOT_DE_PASSE_BDD', '');

// Paramètres de l'application
define('NOM_APPLICATION', 'Manager Football Pro');
define('NOM_EQUIPE', 'Les Champions FC');
define('VERSION_APPLICATION', '1.0.0');

// Limites
define('JOUEURS_TITULAIRES_MAX', 11);
define('JOUEURS_REMPLACANTS_MAX', 7);
define('JOUEURS_TOTAL_MAX', 23);

// Chemins des dossiers
define('CHEMIN_RACINE', dirname(__DIR__));
define('CHEMIN_PAGES', CHEMIN_RACINE . '/pages-principales');
define('CHEMIN_STYLES', CHEMIN_RACINE . '/styles');
define('CHEMIN_INCLUDES', CHEMIN_RACINE . '/interface');  // C'est ici !
define('CHEMIN_BDD', CHEMIN_RACINE . '/base-de-donnees');
define('CHEMIN_SECURITE', CHEMIN_RACINE . '/securite');

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Encodage
header('Content-Type: text/html; charset=utf-8');

// Inclusion des fichiers nécessaires
require_once CHEMIN_SECURITE . '/authentification.php';
?>
