<?php
/**
 * Agence Transfert Argent - Tableau de bord Chef (Responsable d'agence)
 * 
 * Cette page est le centre de contrôle pour le chef d'agence.
 * Elle affiche :
 *   - Résumé des effectifs (clients, réseaux, agents)
 *   - Statistiques financières (volumes de transactions, frais encaissés)
 *   - Filtres par période (année/mois)
 *   - Actions rapides vers les listes
 * 
 * Accès : uniquement le rôle chef (ou informaticien)
 */

// Importation de la configuration de connexion et des utilitaires de sécurité.
include 'connexion.php';

// Vérification du rôle chef
require_role('chef');

$period = parse_transaction_period_filters();
$periodFrag = transaction_period_sql_fragment('t', $period['annee'], $period['mois']);

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

$moisNoms = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
];

$finance = [
    'sum_depot' => 0.0,
    'sum_retrait' => 0.0,
    'sum_transfert' => 0.0,
    'sum_frais' => 0.0,
    'count' => 0,
];

try {
    $clientsCount = $cnt->query('SELECT COUNT(*) FROM clients')->fetchColumn();
    $reseauxCount = $cnt->query('SELECT COUNT(*) FROM reseaux')->fetchColumn();
    $transactionsCount = $cnt->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    $agentsCount = $cnt->query('SELECT COUNT(*) FROM agents')->fetchColumn();

    $finSql = 'SELECT
        COALESCE(SUM(CASE WHEN t.type = \'depot\' THEN t.montant ELSE 0 END), 0) AS sum_depot,
        COALESCE(SUM(CASE WHEN t.type = \'retrait\' THEN t.montant ELSE 0 END), 0) AS sum_retrait,
        COALESCE(SUM(CASE WHEN t.type = \'transfert\' THEN t.montant ELSE 0 END), 0) AS sum_transfert,
        COALESCE(SUM(t.frais), 0) AS sum_frais,
        COUNT(*) AS cnt
        FROM transactions t
        WHERE 1=1' . $periodFrag['sql'];
    $finStmt = $cnt->prepare($finSql);
    $finStmt->execute($periodFrag['params']);
    $row = $finStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $finance['sum_depot'] = (float) $row['sum_depot'];
        $finance['sum_retrait'] = (float) $row['sum_retrait'];
        $finance['sum_transfert'] = (float) $row['sum_transfert'];
        $finance['sum_frais'] = (float) $row['sum_frais'];
        $finance['count'] = (int) $row['cnt'];
    }
} catch (PDOException $e) {
    die('Erreur : ' . clean($e->getMessage()));
}

$listeQuery = http_build_query([
    'annee' => $period['annee'],
    'mois' => $period['mois'],
    'tri' => 'date_desc',
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Chef</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Tableau de bord du Chef</h1>
            <p class="site-subtitle">Vue d'ensemble des données de l'agence et des flux financiers.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
            <a class="btn-link" href="logout.php">Déconnexion</a>
        </div>
    </div>

    <section class="panel summary-grid">
        <div class="summary-card">
            <strong><?php echo intval($clientsCount); ?></strong>
            <span>Clients</span>
        </div>
        <div class="summary-card">
            <strong><?php echo intval($reseauxCount); ?></strong>
            <span>Réseaux</span>
        </div>
        <div class="summary-card">
            <strong><?php echo intval($transactionsCount); ?></strong>
            <span>Transactions (total)</span>
        </div>
        <div class="summary-card">
            <strong><?php echo intval($agentsCount); ?></strong>
            <span>Agents</span>
        </div>
    </section>

    <section class="panel">
        <h2 class="page-title" style="margin-top:0;">Finances — volumes et frais</h2>
        <p class="small-note">Choisissez une période pour agréger les montants (dépôts, retraits, transferts) et les frais perçus. Les totaux portent sur les transactions correspondant aux filtres.</p>
        <form class="filter-toolbar" method="get" action="chef.php">
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
            <div class="form-field filter-toolbar-action">
                <input type="submit" value="Actualiser">
            </div>
        </form>

        <div class="finance-grid">
            <div class="finance-card">
                <span class="finance-label">Dépôts (volume)</span>
                <strong class="finance-value"><?php echo number_format($finance['sum_depot'], 2, ',', ' '); ?></strong>
            </div>
            <div class="finance-card">
                <span class="finance-label">Retraits (volume)</span>
                <strong class="finance-value"><?php echo number_format($finance['sum_retrait'], 2, ',', ' '); ?></strong>
            </div>
            <div class="finance-card">
                <span class="finance-label">Transferts (volume)</span>
                <strong class="finance-value"><?php echo number_format($finance['sum_transfert'], 2, ',', ' '); ?></strong>
            </div>
            <div class="finance-card finance-card-accent">
                <span class="finance-label">Frais encaissés</span>
                <strong class="finance-value"><?php echo number_format($finance['sum_frais'], 2, ',', ' '); ?></strong>
            </div>
            <div class="finance-card">
                <span class="finance-label">Transactions (période)</span>
                <strong class="finance-value"><?php echo intval($finance['count']); ?></strong>
            </div>
        </div>

        <p style="margin-top:20px;">
            <a class="btn-link" href="liste_transaction.php?<?php echo htmlspecialchars($listeQuery, ENT_QUOTES, 'UTF-8'); ?>">Ouvrir l'historique avec cette période</a>
        </p>
    </section>

    <section class="panel">
        <h2>Actions rapides</h2>
        <div class="card-grid nav-grid">
            <a class="nav-card" href="liste_clients.php">Consulter les clients</a>
            <a class="nav-card" href="liste_reseaux.php">Consulter les réseaux</a>
            <a class="nav-card" href="liste_transaction.php">Voir l'historique</a>
            <a class="nav-card" href="liste_agents.php">Gérer les agents</a>
        </div>
    </section>
</div>
</body>
</html>
