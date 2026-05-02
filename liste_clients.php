<?php
/**
 * Agence Transfert Argent - Liste des clients
 * 
 * Cette page affiche tous les clients enregistrés dans l'agence.
 * Elle permet de :
 *   - Rechercher par nom ou téléphone
 *   - Trier par nom, téléphone ou ID
 *   - Modifier un client
 *   - Supprimer un client
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de liste des clients enregistrés dans l'agence.
include "connexion.php";
require_role('informaticien', 'chef');

$orderBy = parse_client_sort_order();
$search = parse_client_search();
$searchFrag = client_search_sql_fragment($search['search']);

try {
    $sql = 'SELECT id_client, nom_client, tel_client FROM clients WHERE 1=1' . $searchFrag['sql'] . ' ORDER BY ' . $orderBy;
    $prepare = $cnt->prepare($sql);
    $prepare->execute($searchFrag['params']);
    $clients = $prepare->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}

$triKeys = array_keys(client_sort_order_map());
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
    <title>Liste des clients</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Liste des clients</h1>
            <p class="site-subtitle">Retrouvez l’ensemble des clients enregistrés dans votre agence.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
            <a class="btn-link" href="ajout_client.php">Ajouter un client</a>
        </div>
</div>

    <section class="panel">
<h2 class="page-title" style="margin-top:0;">Recherche et tri</h2>
    <form class="filter-toolbar" method="get" action="liste_clients.php">
        <div class="form-field">
            <label for="search">Rechercher</label>
            <input type="text" id="search" name="search" placeholder="Nom ou téléphone..." value="<?php echo htmlspecialchars($search['search'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-field">
            <label for="tri">Trier par</label>
            <select id="tri" name="tri">
                <option value="nom_asc"<?php echo $currentTri === 'nom_asc' ? ' selected' : ''; ?>>Nom (A → Z)</option>
                <option value="nom_desc"<?php echo $currentTri === 'nom_desc' ? ' selected' : ''; ?>>Nom (Z → A)</option>
                <option value="tel_asc"<?php echo $currentTri === 'tel_asc' ? ' selected' : ''; ?>>Téléphone (0 → 9)</option>
                <option value="tel_desc"<?php echo $currentTri === 'tel_desc' ? ' selected' : ''; ?>>Téléphone (9 → 0)</option>
                <option value="id_asc"<?php echo $currentTri === 'id_asc' ? ' selected' : ''; ?>>ID (croissant)</option>
                <option value="id_desc"<?php echo $currentTri === 'id_desc' ? ' selected' : ''; ?>>ID (décroissant)</option>
            </select>
        </div>
        <div class="form-field filter-toolbar-action">
            <input type="submit" value="Appliquer">
        </div>
    </form>
    <?php if (empty($clients)): ?>
        <p>Aucun client trouvé.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($client['id_client'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($client['nom_client'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($client['tel_client'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="edit_client.php?id=<?php echo htmlspecialchars($client['id_client'], ENT_QUOTES, 'UTF-8'); ?>">Modifier</a>
                            | <form action="delete_client.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer ce client ?');">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($client['id_client'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <button type="submit" class="link-button">Supprimer</button>
                            </form>
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
