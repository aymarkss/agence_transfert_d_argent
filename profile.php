<?php
/**
 * Agence Transfert Argent - Profil utilisateur
 * 
 * Cette page affiche les informations du'utilisateur connecté :
 *   - Nom complet
 *   - Login
 *   - Rôle (informaticien, chef, agent)
 * 
 * Accessible à tout utilisateur connecté.
 */

// Inclusion de la connexion et vérification de la connexion.
include 'connexion.php';
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil utilisateur</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Profil utilisateur</h1>
            <p class="site-subtitle">Informations de connexion et rôle.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
            <a class="btn-link" href="logout.php">Déconnexion</a>
        </div>
    </div>

    <section class="panel">
        <table>
            <tr>
                <th>Nom</th>
                <td><?php echo clean($user['nom_agent']); ?></td>
            </tr>
            <tr>
                <th>Login</th>
                <td><?php echo clean($user['login_agent']); ?></td>
            </tr>
            <tr>
                <th>Rôle</th>
                <td><?php echo clean(ucfirst($user['role_agent'])); ?></td>
            </tr>
        </table>
    </section>
</div>
</body>
</html>
