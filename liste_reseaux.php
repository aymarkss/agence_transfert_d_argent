<?php
/**
 * Agence Transfert Argent - Liste des réseaux
 * 
 * Cette page affiche tous les réseaux de transfert disponibles.
 * Examples : Wave, Western Union, RIA, etc.
 * Elle permet de :
 *   - Rechercher par nom
 *   - Trier par nom ou ID
 *   - Modifier un réseau
 *   - Supprimer un réseau
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de liste des réseaux disponibles dans l'agence.
include "connexion.php";
require_role('informaticien', 'chef');

$orderBy = parse_reseau_sort_order();
$search = parse_reseau_search();
$searchFrag = reseau_search_sql_fragment($search['search']);

try {
    $sql = 'SELECT id_reseau, nom_reseau FROM reseaux WHERE 1=1' . $searchFrag['sql'] . ' ORDER BY ' . $orderBy;
    $prepare = $cnt->prepare($sql);
    $prepare->execute($searchFrag['params']);
    $reseaux = $prepare->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Affiche un message en cas de problème d'accès à la base.
    die('Erreur : ' . $e->getMessage());
}

$triKeys = array_keys(reseau_sort_order_map());
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
    <title>Liste des réseaux</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Liste des réseaux</h1>
            <p class="site-subtitle">Les opérateurs disponibles dans votre base de données.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
            <a class="btn-link" href="ajout_reseau.php">Ajouter un réseau</a>
        </div>
    </div>
    <section class="panel">
    <h2 class="page-title" style="margin-top:0;">Recherche et tri</h2>
    <form class="filter-toolbar" method="get" action="liste_reseaux.php">
        <div class="form-field">
            <label for="search">Rechercher</label>
            <input type="text" id="search" name="search" placeholder="Nom du réseau..." value="<?php echo htmlspecialchars($search['search'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-field">
            <label for="tri">Trier par</label>
            <select id="tri" name="tri">
                <option value="nom_asc"<?php echo $currentTri === 'nom_asc' ? ' selected' : ''; ?>>Nom (A → Z)</option>
                <option value="nom_desc"<?php echo $currentTri === 'nom_desc' ? ' selected' : ''; ?>>Nom (Z → A)</option>
                <option value="id_asc"<?php echo $currentTri === 'id_asc' ? ' selected' : ''; ?>>ID (croissant)</option>
                <option value="id_desc"<?php echo $currentTri === 'id_desc' ? ' selected' : ''; ?>>ID (décroissant)</option>
            </select>
        </div>
        <div class="form-field filter-toolbar-action">
            <input type="submit" value="Appliquer">
        </div>
    </form>
    <?php if (empty($reseaux)): ?>
        <p>Aucun réseau trouvé.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom du réseau</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reseaux as $reseau): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reseau['id_reseau'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reseau['nom_reseau'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="edit_reseau.php?id=<?php echo htmlspecialchars($reseau['id_reseau'], ENT_QUOTES, 'UTF-8'); ?>">Modifier</a>
                            | <form action="delete_reseau.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer ce réseau ?');">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reseau['id_reseau'], ENT_QUOTES, 'UTF-8'); ?>">
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
