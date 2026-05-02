<?php
/**
 * Agence Transfert Argent - Modification d'un client existant
 * 
 * Cette page permet de modifier les informations d'un client :
 *   - Nom du client
 *   - Numéro de téléphone
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de modification d'un client existant.
include "connexion.php";
require_role('informaticien', 'chef');

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Identifiant client invalide.');
}
$id = $_GET['id'];

try {
    $prepare = $cnt->prepare('SELECT id_client, nom_client, tel_client FROM clients WHERE id_client = :id');
    $prepare->execute([':id' => $id]);
    $client = $prepare->fetch();
    if (!$client) {
        die('Client introuvable.');
    }
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$message = '';
$nom_client = $client['nom_client'];
$tel_client = $client['tel_client'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    $nom_client = trim($_POST['nom_client'] ?? '');
    $tel_client = trim($_POST['tel_client'] ?? '');
    $postId = $_POST['id'] ?? '';

    if ($nom_client === '' || !ctype_digit($postId) || $postId !== $id) {
        $message = 'Données de client invalides.';
    } else {
        try {
            $prepare = $cnt->prepare('UPDATE clients SET nom_client = :nom_client, tel_client = :tel_client WHERE id_client = :id');
            $execute = $prepare->execute([
                ':nom_client' => $nom_client,
                ':tel_client' => $tel_client,
                ':id' => $id,
            ]);
            if ($execute) {
                header('Location: liste_clients.php');
                exit;
            }
            $message = 'Impossible de modifier le client.';
        } catch (PDOException $e) {
            $message = 'Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Client</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Modifier un client</h1>
            <p class="site-subtitle">Mettez à jour les informations du client.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="liste_clients.php">Retour à la liste</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <form action="edit_client.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="post">
            <label for="nom_client">Nom du client :</label>
            <input type="text" id="nom_client" name="nom_client" value="<?php echo htmlspecialchars($nom_client, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="tel_client">Téléphone :</label>
            <input type="text" id="tel_client" name="tel_client" value="<?php echo htmlspecialchars($tel_client, ENT_QUOTES, 'UTF-8'); ?>">

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Enregistrer les modifications">
        </form>
    </section>
</div>
</body>
</html>
