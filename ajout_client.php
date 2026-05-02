<?php
/**
 * Agence Transfert Argent - Ajout d'un nouveau client
 * 
 * Cette page permet d'enregistrer un nouveau client dans la base.
 * Le client pourra ensuite effectuer des transactions.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Importation de la configuration de connexion et des utilitaires de sécurité.
include "connexion.php";
require_role('informaticien', 'chef');

// Message d'état pour le rendu du formulaire.
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF : le formulaire ne doit être soumis que depuis l'interface légitime.
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    // Lecture et nettoyage des données de formulaire.
    $nom_client = trim($_POST['nom_client'] ?? '');
    $tel_client = trim($_POST['tel_client'] ?? '');

    if ($nom_client === '') {
        $message = 'Le nom du client est requis.';
    } else {
        try {
            $prepare = $cnt->prepare('INSERT INTO clients (nom_client, tel_client) VALUES (:nom_client, :tel_client)');
            $execute = $prepare->execute([
                ':nom_client' => $nom_client,
                ':tel_client' => $tel_client,
            ]);
            $message = $execute ? 'Client ajouté avec succès.' : 'Impossible d\'ajouter le client.';
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
    <title>Ajouter Client</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Ajouter un client</h1>
            <p class="site-subtitle">Créez un client en quelques secondes pour commencer vos transactions.</p>
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
        <form action="ajout_client.php" method="post">
            <label for="nom_client">Nom du client :</label>
            <input type="text" id="nom_client" name="nom_client" required>

            <label for="tel_client">Téléphone :</label>
            <input type="text" id="tel_client" name="tel_client">

            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Ajouter">
        </form>
    </section>
</div>
</body>
</html>
