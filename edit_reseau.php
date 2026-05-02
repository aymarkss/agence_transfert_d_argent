<?php
/**
 * Agence Transfert Argent - Modification d'un réseau existant
 * 
 * Cette page permet de modifier le nom d'un réseau de transfert.
 * Exemples : Wave, Western Union, RIA, etc.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de modification d'un réseau existant.
include "connexion.php";
require_role('informaticien', 'chef');

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Identifiant réseau invalide.');
}
$id = $_GET['id'];

try {
    $prepare = $cnt->prepare('SELECT id_reseau, nom_reseau FROM reseaux WHERE id_reseau = :id');
    $prepare->execute([':id' => $id]);
    $reseau = $prepare->fetch();
    if (!$reseau) {
        die('Réseau introuvable.');
    }
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$message = '';
$nom_reseau = $reseau['nom_reseau'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    $nom_reseau = trim($_POST['nom_reseau'] ?? '');
    $postId = $_POST['id'] ?? '';

    if ($nom_reseau === '' || !ctype_digit($postId) || $postId !== $id) {
        $message = 'Données de réseau invalides.';
    } else {
        try {
            $prepare = $cnt->prepare('UPDATE reseaux SET nom_reseau = :nom_reseau WHERE id_reseau = :id');
            $execute = $prepare->execute([
                ':nom_reseau' => $nom_reseau,
                ':id' => $id,
            ]);
            if ($execute) {
                header('Location: liste_reseaux.php');
                exit;
            }
            $message = 'Impossible de modifier le réseau.';
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
    <title>Modifier Réseau</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Modifier un réseau</h1>
            <p class="site-subtitle">Mettez à jour le nom du réseau.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="liste_reseaux.php">Retour à la liste</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <form action="edit_reseau.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="post">
            <label for="nom_reseau">Nom du réseau :</label>
            <input type="text" id="nom_reseau" name="nom_reseau" value="<?php echo htmlspecialchars($nom_reseau, ENT_QUOTES, 'UTF-8'); ?>" required>

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Enregistrer les modifications">
        </form>
    </section>
</div>
</body>
</html>
