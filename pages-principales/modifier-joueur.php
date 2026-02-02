<?php
// pages-principales/modifier-joueur.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

Authentification::verifierConnexion();

$titrePage = 'Modifier un Joueur';
$descriptionPage = 'Modifier les informations d\'un joueur';

// création de l'objet d'accès à la base de données
$fonctionsBDD = new FonctionsBaseDeDonnees();

// messages pour l'utilisateur
$messageSucces = '';
$messageErreur = '';

// tableau pour stocker les données du formulaire
$donneesFormulaire = [];

// vérifie qu'un id de joueur est fourni dans l'url
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gestion-joueurs.php');
    exit();
}

// sécurisation de l'id du joueur
$idJoueur = intval($_GET['id']);

// récupération des informations du joueur
$joueur = $fonctionsBDD->obtenirJoueurParId($idJoueur);

// redirection si le joueur n'existe pas
if (!$joueur) {
    header('Location: gestion-joueurs.php');
    exit();
}

// initialisation du formulaire avec les données existantes du joueur
$donneesFormulaire = [
    'nom_joueur' => $joueur['nom_joueur'],
    'prenom_joueur' => $joueur['prenom_joueur'],
    'numero_licence' => $joueur['numero_licence'],
    'date_naissance' => $joueur['date_naissance'],
    'taille_cm' => $joueur['taille_cm'],
    'poids_kg' => $joueur['poids_kg'],
    'statut_joueur' => $joueur['statut_joueur'],
    'commentaires_joueur' => $joueur['commentaires_joueur'] ?? ''
];

// traitement du formulaire lors de l'envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // log de début de traitement
    error_log("=== début modification joueur ===");

    // récupération et nettoyage des données envoyées
    $donneesFormulaire = [
        'nom_joueur' => trim($_POST['nom_joueur'] ?? ''),
        'prenom_joueur' => trim($_POST['prenom_joueur'] ?? ''),
        'numero_licence' => trim($_POST['numero_licence'] ?? ''),
        'date_naissance' => trim($_POST['date_naissance'] ?? ''),
        'taille_cm' => trim($_POST['taille_cm'] ?? ''),
        'poids_kg' => trim($_POST['poids_kg'] ?? ''),
        'statut_joueur' => trim($_POST['statut_joueur'] ?? 'Actif'),
        'commentaires_joueur' => trim($_POST['commentaires_joueur'] ?? '')
    ];

    // tableau pour stocker les erreurs de validation
    $erreurs = [];

    // validations des champs obligatoires
    if (empty($donneesFormulaire['nom_joueur'])) $erreurs[] = 'Le nom est requis';
    if (empty($donneesFormulaire['prenom_joueur'])) $erreurs[] = 'Le prénom est requis';
    if (empty($donneesFormulaire['numero_licence'])) $erreurs[] = 'Le numéro de licence est requis';
    if (empty($donneesFormulaire['date_naissance'])) $erreurs[] = 'La date de naissance est requise';

    // validation de la taille
    if (empty($donneesFormulaire['taille_cm'])) {
        $erreurs[] = 'La taille est requise';
    } else {
        $taille = str_replace(',', '.', $donneesFormulaire['taille_cm']);
        if (!is_numeric($taille) || $taille < 100 || $taille > 250) {
            $erreurs[] = 'La taille doit être entre 100 et 250 cm';
        }
    }

    // validation du poids
    if (empty($donneesFormulaire['poids_kg'])) {
        $erreurs[] = 'Le poids est requis';
    } else {
        $poids = str_replace(',', '.', $donneesFormulaire['poids_kg']);
        if (!is_numeric($poids) || $poids < 30 || $poids > 150) {
            $erreurs[] = 'Le poids doit être entre 30 et 150 kg';
        }
    }

    // si aucune erreur de validation
    if (empty($erreurs)) {
        try {
            // vérifie que le numéro de licence n'est pas déjà utilisé
            $licenceExiste = $fonctionsBDD->verifierLicenceExiste(
                $donneesFormulaire['numero_licence'],
                $idJoueur
            );

            if ($licenceExiste) {
                $messageErreur = 'Ce numéro de licence est déjà utilisé par un autre joueur';
            } else {

                // préparation des paramètres pour la mise à jour
                $parametres = [
                    ':numero_licence' => $donneesFormulaire['numero_licence'],
                    ':nom_joueur' => $donneesFormulaire['nom_joueur'],
                    ':prenom_joueur' => $donneesFormulaire['prenom_joueur'],
                    ':date_naissance' => $donneesFormulaire['date_naissance'],
                    ':taille_cm' => floatval(str_replace(',', '.', $donneesFormulaire['taille_cm'])),
                    ':poids_kg' => floatval(str_replace(',', '.', $donneesFormulaire['poids_kg'])),
                    ':statut_joueur' => $donneesFormulaire['statut_joueur'],
                    ':commentaires_joueur' => $donneesFormulaire['commentaires_joueur'],
                    ':id_joueur' => $idJoueur
                ];

                // exécution de la mise à jour
                $resultat = $fonctionsBDD->mettreAJourJoueur($idJoueur, $parametres);

                if ($resultat) {
                    // message de succès
                    $messageSucces = 'Le joueur a été modifié avec succès !';

                    // redirection automatique après 2 secondes
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "gestion-joueurs.php";
                        }, 2000);
                    </script>';
                } else {
                    $messageErreur = 'Erreur lors de la modification du joueur.';
                }
            }
        } catch (Exception $e) {
            // gestion des erreurs techniques
            $messageErreur = 'Erreur technique: ' . $e->getMessage();
        }
    } else {
        // affichage des erreurs de validation
        $messageErreur = implode('<br>', $erreurs);
    }

    // log de fin de traitement
    error_log("=== fin modification joueur ===");
}

// bornes de date pour l'âge du joueur
$dateMin = date('Y-m-d', strtotime('-16 years'));
$dateMax = date('Y-m-d', strtotime('-50 years'));

// inclusion de l'entête
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
            <h2><i class="fas fa-user-edit"></i> Modifier le joueur</h2>
            <p>Modifiez les informations du joueur : <?php echo htmlspecialchars($joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur']); ?></p>
        </div>

        <form method="POST" action="" class="formulaire-ajout">
            <div class="grille-formulaire">
                <div class="section-formulaire">
                    <h3 class="titre-section">Informations personnelles</h3>

                    <div class="groupe-formulaire">
                        <label for="nom_joueur" class="obligatoire">Nom</label>
                        <input type="text" id="nom_joueur" name="nom_joueur"
                               value="<?php echo htmlspecialchars($donneesFormulaire['nom_joueur']); ?>"
                               required maxlength="50" placeholder="Ex: Martin">
                    </div>

                    <div class="groupe-formulaire">
                        <label for="prenom_joueur" class="obligatoire">Prénom</label>
                        <input type="text" id="prenom_joueur" name="prenom_joueur"
                               value="<?php echo htmlspecialchars($donneesFormulaire['prenom_joueur']); ?>"
                               required maxlength="50" placeholder="Ex: Pierre">
                    </div>

                    <div class="groupe-formulaire">
                        <label for="date_naissance" class="obligatoire">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance"
                               value="<?php echo htmlspecialchars($donneesFormulaire['date_naissance']); ?>"
                               required max="<?php echo $dateMin; ?>" min="<?php echo $dateMax; ?>">
                        <small class="aide-champ">Entre 16 et 50 ans</small>
                    </div>
                </div>

                <div class="section-formulaire">
                    <h3 class="titre-section">Informations administratives</h3>

                    <div class="groupe-formulaire">
                        <label for="numero_licence" class="obligatoire">Numéro de licence</label>
                        <input type="text" id="numero_licence" name="numero_licence"
                               value="<?php echo htmlspecialchars($donneesFormulaire['numero_licence']); ?>"
                               required maxlength="20" placeholder="Ex: LIC001">
                    </div>

                    <div class="groupe-formulaire">
                        <label for="statut_joueur" class="obligatoire">Statut</label>
                        <select id="statut_joueur" name="statut_joueur" required>
                            <option value="Actif" <?php echo $donneesFormulaire['statut_joueur'] == 'Actif' ? 'selected' : ''; ?>>Actif</option>
                            <option value="Blessé" <?php echo $donneesFormulaire['statut_joueur'] == 'Blessé' ? 'selected' : ''; ?>>Blessé</option>
                            <option value="Suspendu" <?php echo $donneesFormulaire['statut_joueur'] == 'Suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                            <option value="Absent" <?php echo $donneesFormulaire['statut_joueur'] == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        </select>
                    </div>
                </div>

                <div class="section-formulaire">
                    <h3 class="titre-section">Informations physiques</h3>

                    <div class="groupe-formulaire">
                        <label for="taille_cm" class="obligatoire">Taille (cm)</label>
                        <div class="groupe-avec-unite">
                            <input type="number" id="taille_cm" name="taille_cm"
                                   value="<?php echo htmlspecialchars($donneesFormulaire['taille_cm']); ?>"
                                   required min="100" max="250" step="1" placeholder="180">
                            <span class="unite">cm</span>
                        </div>
                        <small class="aide-champ">Entre 100 et 250 cm</small>
                    </div>

                    <div class="groupe-formulaire">
                        <label for="poids_kg" class="obligatoire">Poids (kg)</label>
                        <div class="groupe-avec-unite">
                            <input type="number" id="poids_kg" name="poids_kg"
                                   value="<?php echo htmlspecialchars($donneesFormulaire['poids_kg']); ?>"
                                   required min="30" max="150" step="0.1" placeholder="75.5">
                            <span class="unite">kg</span>
                        </div>
                        <small class="aide-champ">Entre 30 et 150 kg</small>
                    </div>
                </div>

                <div class="section-formulaire pleine-largeur">
                    <h3 class="titre-section">Commentaires et observations</h3>

                    <div class="groupe-formulaire">
                        <label for="commentaires_joueur">Observations</label>
                        <textarea id="commentaires_joueur" name="commentaires_joueur"
                                  rows="4" placeholder="Notes sur le joueur, points forts, faiblesses, etc."
                                  maxlength="500"><?php echo htmlspecialchars($donneesFormulaire['commentaires_joueur']); ?></textarea>
                        <small class="aide-champ">Maximum 500 caractères</small>
                    </div>
                </div>
            </div>

            <div class="actions-formulaire">
                <button type="submit" class="bouton-principal">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="gestion-joueurs.php" class="bouton-secondaire">
                    <i class="fas fa-arrow-left"></i> Annuler et retour
                </a>
            </div>
        </form>
    </div>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
