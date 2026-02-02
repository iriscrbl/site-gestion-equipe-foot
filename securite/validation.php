<?php
class ValidationDonnees {

    // Valider une adresse email
    public static function validerEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Valider une date
    public static function validerDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    // Valider un numéro de licence
    public static function validerNumeroLicence($numero) {
        return preg_match('/^[A-Z0-9]{6,20}$/', $numero);
    }

    // Valider un nom/prénom
    public static function validerNomPrenom($texte) {
        return preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/', $texte);
    }

    // Valider une taille (en cm)
    public static function validerTaille($taille) {
        return is_numeric($taille) && $taille >= 100 && $taille <= 250;
    }

    // Valider un poids (en kg)
    public static function validerPoids($poids) {
        return is_numeric($poids) && $poids >= 30 && $poids <= 150;
    }

    // Nettoyer une chaîne de caractères
    public static function nettoyerChaine($texte) {
        $texte = trim($texte);
        $texte = stripslashes($texte);
        $texte = htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
        return $texte;
    }

    // Valider une note de performance
    public static function validerNotePerformance($note) {
        return is_numeric($note) && $note >= 1 && $note <= 5;
    }

    // Valider un score de match
    public static function validerScore($score) {
        return is_numeric($score) && $score >= 0 && $score <= 50;
    }
}
?>
