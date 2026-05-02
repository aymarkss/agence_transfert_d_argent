<?php
/*
 * Agence Transfert Argent - Journal d'audit (Vue des actions)
 * 
 * Cette page affiche l'historique des actions des utilisateurs.
 * Accessible uniquement par l'informaticien.
 */

include 'connexion.php';
require_role('informaticien');

// Configuration de la pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filtres
$filterAction = $_GET['action'] ?? '';
$filterTable = $_GET['table'] ?? '';
$filterDate = $_GET['date'] ?? '';

try {
    // Compter le total
    $countSql = "SELECT COUNT(*) FROM audit_log WHERE 1=1";
    $countParams = [];
    
    if ($filterAction) {
        $countSql .= " AND action = :action";
        $countParams[':action'] = $filterAction;
    }
    if ($filterTable) {
        $countSql .= " AND table_concernee = :table";
        $countParams[':table'] = $filterTable;
    }
    if ($filterDate) {
        $countSql .= " AND DATE(created_at) = :date";
        $countParams[':date'] = $filterDate;
    }
    
    $stmt = $cnt->prepare($countSql);
    $stmt->execute($countParams);
    $total = (int) $stmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // Requête principale
    $sql = "SELECT * FROM audit_log WHERE 1=1";
    $params = [];
    
    if ($filterAction) {
        $sql .= " AND action = :action";
        $params[':action'] = $filterAction;
    }
    if ($filterTable) {
        $sql .= " AND table_concernee = :table";
        $params[':table'] = $filterTable;
    }
if ($filterDate) {
        $sql .= " AND DATE(created_at) = :date";
        $params[':date'] = $filterDate;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $cnt->prepare($sql);
    foreach ($params as $key => $value) {
        // LIMIT and OFFSET must be bound as integers
        $paramType = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $logs = [];
    $total = 0;
    $totalPages = 1;
    $error = $e->getMessage();
}

// Fonctions pour le style
function getActionColor(string $action): string {
    switch ($action) {
        case 'login_success': return 'color: green; font-weight: bold;';
        case 'login_failed': return 'color: red; font-weight: bold;';
        case 'create': return 'color: #27ae60;';
        case 'update': return 'color: #f39c12;';
        case 'delete': return 'color: #c0392b;';
        default: return '';
    }
}

function getActionLabel(string $action): string {
    switch ($action) {
        case 'login_success': return '✅ Connexion';
        case 'login_failed': return '❌ Échec connexion';
        case 'create': return '➕ Création';
        case 'update': return '✏️ Modification';
        case 'delete': return '🗑️ Suppression';
        default: return $action;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'Audit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .page-container { max-width: 1200px; margin: 0 auto; }
        .site-header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .site-header h1 { margin: 0; }
        .panel { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f9f9f9; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar select, .filter-bar input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .pagination { display: flex; gap: 5px; justify-content: center; margin-top: 20px; }
        .pagination a { padding: 8px 12px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .pagination a:hover { background: #2980b9; }
        .pagination span { padding: 8px 12px; background: #ddd; border-radius: 4px; }
        .empty { text-align: center; padding: 40px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="site-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>📋 Journal d'Audit</h1>
                <div>
                    <a href="index.php" style="color: white; margin-right: 20px;">🏠 Tableau de bord</a>
                    <a href="informaticien.php" style="color: white;">⚙️ Informaticien</a>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Filtres</h2>
            <form class="filter-bar" method="get">
                <select name="action">
                    <option value="">Toutes les actions</option>
                    <option value="login_success" <?= $filterAction === 'login_success' ? 'selected' : '' ?>>✅ Connexions réussies</option>
                    <option value="login_failed" <?= $filterAction === 'login_failed' ? 'selected' : '' ?>>❌ Connexions échouées</option>
                    <option value="create" <?= $filterAction === 'create' ? 'selected' : '' ?>>➕ Créations</option>
                    <option value="update" <?= $filterAction === 'update' ? 'selected' : '' ?>>✏️ Modifications</option>
                    <option value="delete" <?= $filterAction === 'delete' ? 'selected' : '' ?>>🗑️ Suppressions</option>
                </select>
                <select name="table">
                    <option value="">Toutes les tables</option>
                    <option value="agents" <?= $filterTable === 'agents' ? 'selected' : '' ?>>Agents</option>
                    <option value="clients" <?= $filterTable === 'clients' ? 'selected' : '' ?>>Clients</option>
                    <option value="transactions" <?= $filterTable === 'transactions' ? 'selected' : '' ?>>Transactions</option>
                    <option value="reseaux" <?= $filterTable === 'reseaux' ? 'selected' : '' ?>> Réseaux</option>
                </select>
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                <button type="submit" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">Filtrer</button>
                <a href="voir_audit.php" style="padding: 8px 16px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px;">Réinitialiser</a>
            </form>
        </div>

<div class="panel">
            <h2>Historique des actions (<?= $total ?> enregistrements)</h2>
            
            <?php if (!empty($error)): ?>
                <div style="background: #ffdddd; border: 1px solid red; padding: 10px; margin-bottom: 10px; color: red;">
                    <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($logs)): ?>
                <div class="empty">
                    <p>Aucune action trouvée.</p>
                    <p>La table d'audit n'existe peut-être pas encore.</p>
                    <p><a href="installer_audit.php" style="color: #3498db;">Créer la table d'audit</a></p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>ID</th>
                            <th>Détails</th>
                            <th>Utilisateur</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td style="<?= getActionColor($log['action']) ?>"><?= getActionLabel($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['table_concernee']) ?></td>
                                <td><?= $log['id_enregistrement'] ? htmlspecialchars($log['id_enregistrement']) : '-' ?></td>
                                <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['login_agent'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&action=<?= urlencode($filterAction) ?>&table=<?= urlencode($filterTable) ?>&date=<?= urlencode($filterDate) ?>">◀ Précédent</a>
                        <?php endif; ?>
                        
                        <span>Page <?= $page ?> / <?= $totalPages ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&action=<?= urlencode($filterAction) ?>&table=<?= urlencode($filterTable) ?>&date=<?= urlencode($filterDate) ?>">Suivant ▶</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
