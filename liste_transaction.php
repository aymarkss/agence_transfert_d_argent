<?php
/**
 * Agence Transfert Argent - Liste des transactions (Historique)
 * 
 * Cette page affiche l'historique complet des transactions.
 * Elle permet de :
 *   - Rechercher par client, agent ou type
 *   - Filtrer par année et/ou mois
 *   - Trier par date, montant ou client
 *   - Voir les détails d'une transaction
 *   - Modifier/supprimer (selon le rôle)
 * 
 * Les agents普通 (rôle agent) ne voient que leurs propres transactions.
 * Les rôles chef et informaticien voient toutes les transactions.
 */

// Page de consultation de l'historique des transactions.
// Récupère les transactions et leurs relations clients, réseaux et agents.
include 'connexion.php';
require_login();

$period = parse_transaction_period_filters();
$periodFrag = transaction_period_sql_fragment('t', $period['annee'], $period['mois']);
$search = parse_transaction_search();
$searchFrag = transaction_search_sql_fragment($search['search']);
$orderBy = parse_transaction_sort_order();

$years = [];
try {
    $yearsStmt = $cnt->query('SELECT DISTINCT YEAR(created_at) AS y FROM transactions ORDER BY y DESC');
    if ($yearsStmt) {
        $years = array_map('intval', array_column($yearsStmt->fetchAll(PDO::FETCH_ASSOC), 'y'));
    }
} catch (PDOException $e) {
    $years = [];
}
if (empty($years)) {
    $years = [(int) date('Y')];
}

try {
    if (user_has_role(['agent'])) {
        $sql = 'SELECT t.id_transac, t.type, t.montant, t.frais, t.created_at, c.nom_client, r.nom_reseau, a.nom_agent
             FROM transactions t
             LEFT JOIN clients c ON t.client_id = c.id_client
             LEFT JOIN reseaux r ON t.reseau_id = r.id_reseau
             LEFT JOIN agents a ON t.agent_id = a.id_agent
             WHERE t.agent_id = :agent_id' . $periodFrag['sql'] . $searchFrag['sql'] . '
             ORDER BY ' . $orderBy;
        $prepare = $cnt->prepare($sql);
        $params = array_merge([':agent_id' => current_user()['id_agent']], array_merge($periodFrag['params'], $searchFrag['params']));
        $prepare->execute($params);
        $transactions = $prepare->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = 'SELECT t.id_transac, t.type, t.montant, t.frais, t.created_at, c.nom_client, r.nom_reseau, a.nom_agent
             FROM transactions t
             LEFT JOIN clients c ON t.client_id = c.id_client
             LEFT JOIN reseaux r ON t.reseau_id = r.id_reseau
             LEFT JOIN agents a ON t.agent_id = a.id_agent
             WHERE 1=1' . $periodFrag['sql'] . $searchFrag['sql'] . '
             ORDER BY ' . $orderBy;
        $prepare = $cnt->prepare($sql);
        $prepare->execute(array_merge($periodFrag['params'], $searchFrag['params']));
        $transactions = $prepare->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas de problème lors de la requête, affichage d'un message d'erreur.
    die('Erreur : ' . $e->getMessage());
}

$triKeys = array_keys(transaction_sort_order_map());
$currentTri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'date_desc';
if (!in_array($currentTri, $triKeys, true)) {
    $currentTri = 'date_desc';
}

$moisNoms = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des transactions</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Historique des transactions</h1>
            <p class="site-subtitle">Suivez les opérations réalisées par votre agence. Filtrez par année ou par mois et triez la liste.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour au menu</a>
            <?php if (user_has_role(['informaticien', 'agent'])): ?>
                <a class="btn-link" href="ajout_transac.php">Nouvelle transaction</a>
            <?php endif; ?>
        </div>
    </div>
<section class="panel">
    <h2 class="page-title" style="margin-top:0;">Filtres et tri</h2>
    <form class="filter-toolbar" method="get" action="liste_transaction.php">
        <div class="form-field">
            <label for="search">Rechercher</label>
            <input type="text" id="search" name="search" placeholder="Client, agent ou type..." value="<?php echo htmlspecialchars($search['search'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-field">
            <label for="annee">Année</label>
            <select id="annee" name="annee">
                <option value="0">Toutes</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y; ?>"<?php echo $period['annee'] === $y ? ' selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="mois">Mois</label>
            <select id="mois" name="mois">
                <option value="0">Tous</option>
                <?php foreach ($moisNoms as $num => $label): ?>
                    <option value="<?php echo $num; ?>"<?php echo $period['mois'] === $num ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="tri">Trier par</label>
            <select id="tri" name="tri">
                <option value="date_desc"<?php echo $currentTri === 'date_desc' ? ' selected' : ''; ?>>Date (plus récent)</option>
                <option value="date_asc"<?php echo $currentTri === 'date_asc' ? ' selected' : ''; ?>>Date (plus ancien)</option>
                <option value="montant_desc"<?php echo $currentTri === 'montant_desc' ? ' selected' : ''; ?>>Montant (décroissant)</option>
                <option value="montant_asc"<?php echo $currentTri === 'montant_asc' ? ' selected' : ''; ?>>Montant (croissant)</option>
                <option value="type_az"<?php echo $currentTri === 'type_az' ? ' selected' : ''; ?>>Type (A → Z)</option>
                <option value="client_az"<?php echo $currentTri === 'client_az' ? ' selected' : ''; ?>>Client (A → Z)</option>
            </select>
        </div>
        <div class="form-field filter-toolbar-action">
            <input type="submit" value="Appliquer">
        </div>
    </form>
    <?php if (empty($transactions)): ?>
        <p>Aucune transaction trouvée pour ces critères.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Réseau</th>
                    <th>Agent</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Frais</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['id_transac'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['nom_client'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['nom_reseau'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['nom_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['montant'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['frais'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="view_transaction.php?id=<?php echo $t['id_transac']; ?>">Voir</a>
                            <?php if (user_has_role(['informaticien', 'agent'])): ?>
                                | <a href="edit_transaction.php?id=<?php echo $t['id_transac']; ?>">Modifier</a>
                            <?php endif; ?>
                            <?php if (user_has_role(['informaticien'])): ?>
                                | <form action="delete_transaction.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer cette transaction ?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['id_transac'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <button type="submit" class="link-button">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </section>
</div>
</body>
</html>
