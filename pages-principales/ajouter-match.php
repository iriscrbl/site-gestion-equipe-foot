<?php
// pages-principales/ajouter-match.php

//activer rapport d'erreurs pour débogage
error_reporting(E_ALL);    // affiche toutes les erreurs PHP
ini_set('display_errors', 1);  //affiche les erreurs direct dans le navigateur

//inclure les fichiers nécessaires
require_once '../configuration/config.php';       
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

//vérifier que l'entraineur est connecté sinon redirection vers login
Authentification::verifierConnexion();

// def titre et description
$titrePage = 'Ajouter un Match';
$descriptionPage = 'Planifier un nouveau match';

// Initialiser les objets et variables
$fonctionsBDD = new FonctionsBaseDeDonnees();       // créer une instance de la classe de gestion BDD

//initialiser les messages de retour
$messageSucces = '';                // Message de succès (vide au départ)
$messageErreur = '';                // Message d'erreur (vide au départ)

// valeurs par défaut
$donneesFormulaire = [
    'adversaire' => '',
    'date_match' => '',
    'heure_match' => '',
    'type_match' => 'Domicile',
    'lieu_rencontre' => '',
    'commentaires_match' => '',
    'score_equipe' => '',
    'score_adversaire' => '',
    'resultat_match' => 'À venir'
];

// traitement du formulaire lorsque l'utilisateur clique sur "Enregistrer"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {    //vérifier que le formulaire est soumis
    //message pour debug
    error_log("=== DÉBUT AJOUT MATCH ===");

    //récuperer et nettoyer les données du formulaire
    $donneesFormulaire = [
        'adversaire' => trim($_POST['adversaire'] ?? ''),
        'date_match' => trim($_POST['date_match'] ?? ''),
        'heure_match' => trim($_POST['heure_match'] ?? ''),
        'type_match' => trim($_POST['type_match'] ?? 'Domicile'),
        'lieu_rencontre' => trim($_POST['lieu_rencontre'] ?? ''),
        'commentaires_match' => trim($_POST['commentaires_match'] ?? ''),
        'score_equipe' => trim($_POST['score_equipe'] ?? ''),
        'score_adversaire' => trim($_POST['score_adversaire'] ?? ''),
        'resultat_match' => 'À venir'
    ];

    //afficher les données reçues dans le log
    error_log("Données formulaire reçues: " . print_r($donneesFormulaire, true));

    //tableau pour stocker les erreurs de validation
    $erreurs = [];

    // Validation des champs obligatoires
    if (empty($donneesFormulaire['adversaire'])) {
        $erreurs[] = 'Le nom de l\'équipe adverse est requis';
    }
    if (empty($donneesFormulaire['date_match'])) {
        $erreurs[] = 'La date du match est requise';
    }
    if (empty($donneesFormulaire['heure_match'])) {
        $erreurs[] = 'L\'heure du match est requise';
    }
    if (empty($donneesFormulaire['lieu_rencontre'])) {
        $erreurs[] = 'Le lieu de rencontre est requis';
    }

    // Vérifier que la date n'est pas dans le passé (sauf si un score est saisi)
    if (!empty($donneesFormulaire['date_match'])) {
        $dateMatch = new DateTime($donneesFormulaire['date_match']);    // convertit la date en objet DateTime
        $aujourdhui = new DateTime();   
        $aujourdhui->setTime(0, 0, 0);    //on ignore l'heure : on compare seulement la date

        //si la date renseignée est avant la date actuelle
        if ($dateMatch < $aujourdhui && empty($donneesFormulaire['score_equipe'])) {
            //message d'erreur
            $erreurs[] = 'La date du match ne peut pas être dans le passé (sauf si vous saisissez le score)';
        }
    }

    // Si des scores sont saisis, calculer automatiquement le résultat (victoire/defaite/nul)
    if (!empty($donneesFormulaire['score_equipe']) && !empty($donneesFormulaire['score_adversaire'])) {
        $scoreEquipe = intval($donneesFormulaire['score_equipe']);    //convertit en entier
        $scoreAdversaire = intval($donneesFormulaire['score_adversaire']);   //pareil

        //score supérieur : victoire
        if ($scoreEquipe > $scoreAdversaire) {
            $donneesFormulaire['resultat_match'] = 'Victoire';
        } 
        // score inférieur : défaite
        elseif ($scoreEquipe < $scoreAdversaire) {
            $donneesFormulaire['resultat_match'] = 'Défaite';
        } 
        // score égal : match nul
        else {
            $donneesFormulaire['resultat_match'] = 'Nul';
        }
    } else {
        // Pas de score = match à venir
        $donneesFormulaire['score_equipe'] = 0;     // on mets 0 par défaut pour les deux
        $donneesFormulaire['score_adversaire'] = 0;
        $donneesFormulaire['resultat_match'] = 'À venir';
    }

    // si pas d'erreurs alors insertion dans la BDD
    if (empty($erreurs)) {
        try {
            //log debug
            error_log("Validation réussie, insertion du match...");

            // Combiner date et heure pour date_heure_match
            $dateHeure = $donneesFormulaire['date_match'] . ' ' . $donneesFormulaire['heure_match'] . ':00';

            // Préparer les données pour la méthode dédiée 
            //on utilise un tableau associatif
            $donneesInsertion = [
                ':equipe_adverse' => $donneesFormulaire['adversaire'],
                ':date_heure_match' => $dateHeure,
                ':lieu_match' => $donneesFormulaire['type_match'],
                ':score_equipe' => $donneesFormulaire['score_equipe'],
                ':score_adverse' => $donneesFormulaire['score_adversaire'],
                ':resultat_match' => $donneesFormulaire['resultat_match'],
                ':statut_match' => ($donneesFormulaire['resultat_match'] == 'À venir') ? 'À venir' : 'Terminé',
                ':commentaires_match' => $donneesFormulaire['commentaires_match']
            ];

            //log debug
            error_log("Données prêtes pour insertion: " . print_r($donneesInsertion, true));

            // Utiliser la méthode dédiée pour insérer un match
            $idMatch = $fonctionsBDD->insererMatch($donneesInsertion);

            //log résultat 
            error_log("ID du match inséré: " . $idMatch);

            //si l'insertion a marché
            if ($idMatch) {
                // afficher un message de succes 
                $messageSucces = 'Le match contre ' . $donneesFormulaire['adversaire'] . ' a été ajouté avec succès!';

                // on réinitialise le formulaire
                $donneesFormulaire = [
                    'adversaire' => '',
                    'date_match' => '',
                    'heure_match' => '',
                    'type_match' => 'Domicile',
                    'lieu_rencontre' => '',
                    'commentaires_match' => '',
                    'score_equipe' => '',
                    'score_adversaire' => '',
                    'resultat_match' => 'À venir'
                ];

                // redirection vers la page de gestion après 2 secondes
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "gestion-matchs.php";
                    }, 2000);
                </script>';
            } else {   // si l'insertion a échoué 
                $messageErreur = 'Erreur lors de l\'ajout du match. Veuillez réessayer.';
            }
        } catch (Exception $e) {  // si une exception survient 
            error_log("Exception attrapée: " . $e->getMessage());
            $messageErreur = 'Erreur technique: ' . $e->getMessage();
        }
    } else {  // s'il y a des erreurs de validation on affiche 
        $messageErreur = implode('<br>', $erreurs);
    }

    //fin du traitement 
    error_log("=== FIN AJOUT MATCH ===");
}

// Dates min et max pour le calendrier
$dateMin = date('Y-m-d', strtotime('-1 year'));
$dateMax = date('Y-m-d', strtotime('+2 years'));

include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-formulaire">
    <?php if ($messageSucces || $messageErreur): ?>
    <div class="messages-systeme">
        <?php if ($messageSucces): ?>
            <div class="message-succes"><i class="fas fa-check-circle"></i> <?php echo $messageSucces; ?></div>
        <?php endif; ?>
        <?php if ($messageErreur): ?>
            <div class="message-erreur"><i class="fas fa-exclamation-circle"></i> <?php echo $messageErreur; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="carte-formulaire">
        <div class="entete-formulaire">
            <h2><i class="fas fa-calendar-plus"></i> Planifier un nouveau match</h2>
            <p>Remplissez le formulaire pour ajouter un match au calendrier</p>
        </div>

        <form method="POST" action="" class="formulaire-ajout">
            <div class="corps-formulaire">
            <div class="grille-formulaire">
                <!-- Section Informations du match -->
                <div class="section-formulaire">
                    <h3 class="titre-section">Informations du match</h3>

                    <div class="groupe-formulaire">
                        <label for="adversaire" class="obligatoire">Équipe adverse</label>
                        <input type="text" id="adversaire" name="adversaire"
                               value="<?php echo htmlspecialchars($donneesFormulaire['adversaire']); ?>"
                               required maxlength="100"
                               placeholder="Ex: FC Barcelone">
                        <small class="aide-champ">Nom de l'équipe adverse</small>
                    </div>

                    <div class="groupe-formulaire">
                        <label for="date_match" class="obligatoire">Date du match</label>
                        <input type="date" id="date_match" name="date_match"
                               value="<?php echo htmlspecialchars($donneesFormulaire['date_match']); ?>"
                               required min="<?php echo $dateMin; ?>" max="<?php echo $dateMax; ?>">
                        <small class="aide-champ">Date de la rencontre</small>
                    </div>

                    <div class="groupe-formulaire">
                        <label for="heure_match" class="obligatoire">Heure du match</label>
                        <input type="time" id="heure_match" name="heure_match"
                               value="<?php echo htmlspecialchars($donneesFormulaire['heure_match']); ?>"
                               required>
                        <small class="aide-champ">Heure de coup d'envoi</small>
                    </div>
                </div>

                <!-- Section Localisation -->
                <div class="section-formulaire">
                    <h3 class="titre-section">Localisation</h3>

                    <div class="groupe-formulaire">
                        <label for="type_match" class="obligatoire">Type de match</label>
                        <select id="type_match" name="type_match" required onchange="updateLieuPlaceholder()">
                            <option value="Domicile" <?php echo $donneesFormulaire['type_match'] == 'Domicile' ? 'selected' : ''; ?>>
                                Domicile
                            </option>
                            <option value="Extérieur" <?php echo $donneesFormulaire['type_match'] == 'Extérieur' ? 'selected' : ''; ?>>
                                Extérieur
                            </option>
                        </select>
                        <small class="aide-champ">Match à domicile ou à l'extérieur</small>
                    </div>

                    <div class="groupe-formulaire">
                        <label for="lieu_rencontre" class="obligatoire">Lieu de rencontre</label>
                        <input type="text" id="lieu_rencontre" name="lieu_rencontre"
                               value="<?php echo htmlspecialchars($donneesFormulaire['lieu_rencontre']); ?>"
                               required maxlength="200"
                               placeholder="Ex: Stade Municipal">
                        <small class="aide-champ">Nom du stade ou de la salle</small>
                    </div>
                </div>

                <!-- Section Score (optionnel) -->
                <div class="section-formulaire">
                    <h3 class="titre-section">Score (optionnel)</h3>
                    <p class="info-section">
                        <i class="fas fa-info-circle"></i>
                        Laissez vide si le match n'a pas encore eu lieu. Le résultat sera calculé automatiquement.
                    </p>

                    <div class="grille-score">
                        <div class="groupe-formulaire">
                            <label for="score_equipe">Score de votre équipe</label>
                            <input type="number" id="score_equipe" name="score_equipe"
                                   value="<?php echo htmlspecialchars($donneesFormulaire['score_equipe']); ?>"
                                   min="0" max="99" placeholder="0">
                        </div>

                        <div class="separateur-score">
                            <span>-</span>
                        </div>

                        <div class="groupe-formulaire">
                            <label for="score_adversaire">Score adverse</label>
                            <input type="number" id="score_adversaire" name="score_adversaire"
                                   value="<?php echo htmlspecialchars($donneesFormulaire['score_adversaire']); ?>"
                                   min="0" max="99" placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Section Commentaires -->
                <div class="section-formulaire pleine-largeur">
                    <h3 class="titre-section">Commentaires et notes</h3>

                    <div class="groupe-formulaire">
                        <label for="commentaires_match">Observations</label>
                        <textarea id="commentaires_match" name="commentaires_match"
                                  rows="4"
                                  placeholder="Notes stratégiques, état du terrain, conditions météo, absences prévues..."
                                  maxlength="1000"><?php echo htmlspecialchars($donneesFormulaire['commentaires_match']); ?></textarea>
                        <small class="aide-champ">Maximum 1000 caractères</small>
                    </div>
                </div>
            </div>
            </div>

            <!-- Aperçu du résultat -->
            <div id="apercu-resultat" class="apercu-resultat" style="display: none;">
                <div class="apercu-header">
                    <i class="fas fa-eye"></i> Aperçu du résultat
                </div>
                <div class="apercu-body">
                    <span id="apercu-texte"></span>
                </div>
            </div>

            <div class="actions-formulaire">
                <button type="submit" class="bouton-principal">
                    <i class="fas fa-save"></i> Enregistrer le match
                </button>
                <a href="gestion-matchs.php" class="bouton-secondaire">
                    <i class="fas fa-arrow-left"></i> Annuler et retour
                </a>
            </div>
        </form>
    </div>
</div>

<script src="../scripts/ajouter-match.js"></script>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
