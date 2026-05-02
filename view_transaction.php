<?php
/**
 * Agence Transfert Argent - Détails d'une transaction
 * 
 * Cette page affiche les détails complets d'une transaction :
 *   - Client, réseau, agent
 *   - Type, montant, frais
 *   - Date de l'opération
 * 
 * Les agents (rôle agent) ne peuvent voir que leurs propres transactions.
 * Les rôles chef et informaticien peuvent voir toutes les transactions.
 */

// Page de détail d'une transaction sélectionnée.
include "connexion.php";
require_login();

// Validation de l'identifiant de transaction transmis par GET.
if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Identifiant de transaction invalide.');
}
$id = $_GET['id'];
try {
    // Requête sécurisée pour récupérer les informations associées à la transaction.
    $prepare = $cnt->prepare(
        'SELECT t.id_transac, t.type, t.montant, t.frais, t.created_at, t.agent_id, c.nom_client, r.nom_reseau, a.nom_agent
         FROM transactions t
         LEFT JOIN clients c ON t.client_id = c.id_client
         LEFT JOIN reseaux r ON t.reseau_id = r.id_reseau
         LEFT JOIN agents a ON t.agent_id = a.id_agent
         WHERE t.id_transac = :id'
    );
    $execute = $prepare->execute([':id' => $id]);
    $transaction = $prepare->fetch(PDO::FETCH_ASSOC);
    if (!$transaction) {
        die('Transaction introuvable.');
    }
    if (user_has_role(['agent']) && $transaction['agent_id'] !== current_user()['id_agent']) {
        die('Accès refusé : transaction non autorisée.');
    }
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail transaction</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Détail de la transaction</h1>
            <p class="site-subtitle">Consultez toutes les informations de cette opération.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="liste_transaction.php">Retour</a>
        </div>
    </div>
    <section class="panel">
    <table>
        <tr>
            <th>ID</th>
            <td><?php echo htmlspecialchars($transaction['id_transac'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Client</th>
            <td><?php echo htmlspecialchars($transaction['nom_client'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Réseau</th>
            <td><?php echo htmlspecialchars($transaction['nom_reseau'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Agent</th>
            <td><?php echo htmlspecialchars($transaction['nom_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Type</th>
            <td><?php echo htmlspecialchars($transaction['type'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Montant</th>
            <td><?php echo htmlspecialchars($transaction['montant'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Frais</th>
            <td><?php echo htmlspecialchars($transaction['frais'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Date</th>
            <td><?php echo htmlspecialchars($transaction['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </table>
    </section>
</div>
</body>

</html>