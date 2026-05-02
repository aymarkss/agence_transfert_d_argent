<?php
/**
 * Agence Transfert Argent - Liste des agents
 * 
 * Cette page affiche tous les agents enregistrés dans le système.
 * Elle permet de :
 *   - Rechercher par nom ou login
 *   - Trier par nom, login, rôle ou ID
 *   - Modifier un agent
 *   - Supprimer un agent (sauf soi-même)
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de liste des agents enregistrés dans le système.
include "connexion.php";
require_role('informaticien', 'chef');

$orderBy = parse_agent_sort_order();
$search = parse_agent_search();
$searchFrag = agent_search_sql_fragment($search['search']);

try {
    $sql = 'SELECT id_agent, nom_agent, login_agent, role_agent FROM agents WHERE 1=1' . $searchFrag['sql'] . ' ORDER BY ' . $orderBy;
    $prepare = $cnt->prepare($sql);
    $prepare->execute($searchFrag['params']);
    $agents = $prepare->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}

$triKeys = array_keys(agent_sort_order_map());
$currentTri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'nom_asc';
if (!in_array($currentTri, $triKeys, true)) {
    $currentTri = 'nom_asc';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des agents</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Liste des agents</h1>
            <p class="site-subtitle">Tous les agents enregistrés et prêts à traiter les transactions.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
            <a class="btn-link" href="ajout_agent.php">Ajouter un agent</a>
        </div>
</div>
    <section class="panel">
<h2 class="page-title" style="margin-top:0;">Recherche et tri</h2>
    <form class="filter-toolbar" method="get" action="liste_agents.php">
        <div class="form-field">
            <label for="search">Rechercher</label>
            <input type="text" id="search" name="search" placeholder="Nom ou login..." value="<?php echo htmlspecialchars($search['search'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-field">
            <label for="tri">Trier par</label>
            <select id="tri" name="tri">
                <option value="nom_asc"<?php echo $currentTri === 'nom_asc' ? ' selected' : ''; ?>>Nom (A → Z)</option>
                <option value="nom_desc"<?php echo $currentTri === 'nom_desc' ? ' selected' : ''; ?>>Nom (Z → A)</option>
                <option value="login_asc"<?php echo $currentTri === 'login_asc' ? ' selected' : ''; ?>>Login (A → Z)</option>
                <option value="login_desc"<?php echo $currentTri === 'login_desc' ? ' selected' : ''; ?>>Login (Z → A)</option>
                <option value="role_asc"<?php echo $currentTri === 'role_asc' ? ' selected' : ''; ?>>Rôle (A → Z)</option>
                <option value="role_desc"<?php echo $currentTri === 'role_desc' ? ' selected' : ''; ?>>Rôle (Z → A)</option>
                <option value="id_asc"<?php echo $currentTri === 'id_asc' ? ' selected' : ''; ?>>ID (croissant)</option>
                <option value="id_desc"<?php echo $currentTri === 'id_desc' ? ' selected' : ''; ?>>ID (décroissant)</option>
            </select>
        </div>
        <div class="form-field filter-toolbar-action">
            <input type="submit" value="Appliquer">
        </div>
    </form>
    <?php if (empty($agents)): ?>
        <p>Aucun agent trouvé.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom et prénoms</th>
                    <th>Login</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($agent['id_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($agent['nom_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($agent['login_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($agent['role_agent']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="edit_agent.php?id=<?php echo htmlspecialchars($agent['id_agent'], ENT_QUOTES, 'UTF-8'); ?>">Modifier</a>
                            <?php if ((int) $agent['id_agent'] !== (int) (current_user()['id_agent'] ?? 0)): ?>
                            | <form action="delete_agent.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer cet agent ?');">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($agent['id_agent'], ENT_QUOTES, 'UTF-8'); ?>">
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
