<?php
// pages-principales/modifier-match.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérifier que l’utilisateur est bien connecté
Authentification::verifierConnexion();

// informations utilisées dans l’en-tête de la page
$titrePage = 'Modifier un Match';
$descriptionPage = 'Modifier les informations d\'un match';

// instance de la classe d’accès à la base de données
$fonctionsBDD = new FonctionsBaseDeDonnees();

// messages affichés à l’utilisateur
$messageSucces = '';
$messageErreur = '';

// vérifier qu’un identifiant de match est présent dans l’url
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gestion-matchs.php');
    exit();
}

// récupération et sécurisation de l’id du match
$idMatch = intval($_GET['id']);

// récupération des données du match depuis la base
$match = $fonctionsBDD->obtenirMatchParId($idMatch);

// si le match n’existe pas, redirection
if (!$match) {
    header('Location: gestion-matchs.php');
    exit();
}

// séparation de la date et de l’heure stockées ensemble en base
$dateHeure = new DateTime($match['date_heure_match']);
$dateMatch = $dateHeure->format('Y-m-d');
$heureMatch = $dateHeure->format('H:i');

// initialisation des champs du formulaire avec les données du match
$donneesFormulaire = [
    'adversaire' => $match['equipe_adverse'],
    'date_match' => $dateMatch,
    'heure_match' => $heureMatch,
    'type_match' => $match['lieu_match'],
    'commentaires_match' => $match['commentaires_match'] ?? '',
    'score_equipe' => $match['score_equipe'] ?? '',
    'score_adversaire' => $match['score_adverse'] ?? '',
    'resultat_match' => $match['resultat_match']
];

// traitement du formulaire lors de la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== début modification match ===");

    // récupération et nettoyage des données envoyées
    $donneesFormulaire = [
        'adversaire' => trim($_POST['adversaire'] ?? ''),
        'date_match' => trim($_POST['date_match'] ?? ''),
        'heure_match' => trim($_POST['heure_match'] ?? ''),
        'type_match' => trim($_POST['type_match'] ?? 'Domicile'),
        'commentaires_match' => trim($_POST['commentaires_match'] ?? ''),
        'score_equipe' => trim($_POST['score_equipe'] ?? ''),
        'score_adversaire' => trim($_POST['score_adversaire'] ?? ''),
        'resultat_match' => 'À venir'
    ];

    // tableau pour stocker les erreurs de validation
    $erreurs = [];

    // vérification des champs obligatoires
    if (empty($donneesFormulaire['adversaire'])) {
        $erreurs[] = 'le nom de l\'équipe adverse est requis';
    }
    if (empty($donneesFormulaire['date_match'])) {
        $erreurs[] = 'la date du match est requise';
    }
    if (empty($donneesFormulaire['heure_match'])) {
        $erreurs[] = 'l\'heure du match est requise';
    }

    // calcul automatique du résultat si les scores sont renseignés
    if ($donneesFormulaire['score_equipe'] !== '' && $donneesFormulaire['score_adversaire'] !== '') {
        $scoreEquipe = intval($donneesFormulaire['score_equipe']);
        $scoreAdversaire = intval($donneesFormulaire['score_adversaire']);

        if ($scoreEquipe > $scoreAdversaire) {
            $donneesFormulaire['resultat_match'] = 'Victoire';
        } elseif ($scoreEquipe < $scoreAdversaire) {
            $donneesFormulaire['resultat_match'] = 'Défaite';
        } else {
            $donneesFormulaire['resultat_match'] = 'Nul';
        }
    } else {
        // aucun score saisi : match non joué
        $donneesFormulaire['score_equipe'] = 0;
        $donneesFormulaire['score_adversaire'] = 0;
        $donneesFormulaire['resultat_match'] = 'À venir';
    }

    // si aucune erreur, mise à jour en base
    if (empty($erreurs)) {
        try {
            // reconstruction de la date et de l’heure pour la base de données
            $dateHeure = $donneesFormulaire['date_match'] . ' ' . $donneesFormulaire['heure_match'] . ':00';

            // paramètres envoyés à la requête sql
            $parametres = [
                ':equipe_adverse' => $donneesFormulaire['adversaire'],
                ':date_heure_match' => $dateHeure,
                ':lieu_match' => $donneesFormulaire['type_match'],
                ':score_equipe' => $donneesFormulaire['score_equipe'],
                ':score_adverse' => $donneesFormulaire['score_adversaire'],
                ':resultat_match' => $donneesFormulaire['resultat_match'],
                ':statut_match' => ($donneesFormulaire['resultat_match'] == 'À venir') ? 'À venir' : 'Terminé',
                ':commentaires_match' => $donneesFormulaire['commentaires_match'],
                ':id_match' => $idMatch
            ];

            // exécution de la mise à jour
            $resultat = $fonctionsBDD->mettreAJourMatch($idMatch, $parametres);

            if ($resultat) {
                $messageSucces = 'le match a été modifié avec succès';

                // redirection automatique après 2 secondes
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "gestion-matchs.php";
                    }, 2000);
                </script>';
            } else {
                $messageErreur = 'erreur lors de la modification du match';
            }
        } catch (Exception $e) {
            // gestion des erreurs techniques
            $messageErreur = 'erreur technique : ' . $e->getMessage();
        }
    } else {
        // affichage des erreurs de validation
        $messageErreur = implode('<br>', $erreurs);
    }

    error_log("=== fin modification match ===");
}

// limites de dates autorisées dans le formulaire
$dateMin = date('Y-m-d', strtotime('-1 year'));
$dateMax = date('Y-m-d', strtotime('+2 years'));

// inclusion de l’en-tête de page
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
            <h2><i class="fas fa-edit"></i> Modifier le match</h2>
            <p>Match contre : <?php echo htmlspecialchars($match['equipe_adverse']); ?></p>
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
                        <select id="type_match" name="type_match" required>
                            <option value="Domicile" <?php echo $donneesFormulaire['type_match'] == 'Domicile' ? 'selected' : ''; ?>>
                                Domicile
                            </option>
                            <option value="Extérieur" <?php echo $donneesFormulaire['type_match'] == 'Extérieur' ? 'selected' : ''; ?>>
                                Extérieur
                            </option>
                        </select>
                        <small class="aide-champ">Match à domicile ou à l'extérieur</small>
                    </div>
                </div>

                <!-- Section Score -->
                <div class="section-formulaire">
                    <h3 class="titre-section">Score</h3>
                    <p class="info-section">
                        <i class="fas fa-info-circle"></i>
                        Le résultat sera calculé automatiquement selon les scores.
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

                    <?php if ($donneesFormulaire['resultat_match'] != 'À venir'): ?>
                    <div class="resultat-actuel">
                        <i class="fas fa-flag-checkered"></i>
                        Résultat actuel : <strong><?php echo $donneesFormulaire['resultat_match']; ?></strong>
                    </div>
                    <?php endif; ?>
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
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="gestion-matchs.php" class="bouton-secondaire">
                    <i class="fas fa-arrow-left"></i> Annuler et retour
                </a>
            </div>
        </form>
    </div>
</div>

<script src="../scripts/ajouter-match.js"></script>

<style>
.resultat-actuel {
    background: #e3f2fd;
    padding: 12px 15px;
    border-radius: 6px;
    color: #1976d2;
    font-size: 14px;
    margin-top: 15px;
    border-left: 4px solid #2196f3;
    display: flex;
    align-items: center;
    gap: 10px;
}

.resultat-actuel i {
    font-size: 18px;
}
</style>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
