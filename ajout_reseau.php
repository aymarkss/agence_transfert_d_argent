<?php
/**
 * Agence Transfert Argent - Ajout d'un nouveau réseau
 * 
 * Cette page permet d'ajouter un nouveau réseau de transfert.
 * Exemples : Wave, Western Union, RIA, MoneyGram, etc.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Inclusion de la connexion à la base et des fonctions de sécurité.
include "connexion.php";
require_role('informaticien', 'chef');

// Message d'état à afficher au-dessus du formulaire.
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF envoyé avec le formulaire.
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    // Nettoyage et validation du nom de réseau.
    $nom_reseau = trim($_POST['nom_reseau'] ?? '');

    if ($nom_reseau === '') {
        $message = 'Le nom du réseau est requis.';
    } else {
        try {
            $prepare = $cnt->prepare('INSERT INTO reseaux (nom_reseau) VALUES (:nom_reseau)');
            $execute = $prepare->execute([':nom_reseau' => $nom_reseau]);
            $message = $execute ? 'Réseau ajouté avec succès.' : 'Impossible d\'ajouter le réseau.';
        } catch (PDOException $e) {
            $message = 'Erreur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Réseau</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Ajouter un réseau</h1>
            <p class="site-subtitle">Enregistrez les opérateurs accessibles pour vos transactions.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <section class="panel">
        <form action="ajout_reseau.php" method="post">
            <label for="nom_reseau">Nom du réseau :</label>
            <input type="text" id="nom_reseau" name="nom_reseau" required>

            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Ajouter">
        </form>
    </section>
</div>
</body>
</html>
