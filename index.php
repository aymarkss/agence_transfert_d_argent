<?php
/**
 * Agence Transfert Argent - Page d'accueil (Dashboard principal)
 * 
 * Cette page est le point d'entrée principal après la connexion.
 * Elle affiche les liens de navigation selon le rôle de l'utilisateur.
 * 
 * Rôles possibles :
 *   - informaticien : accès complet (admin IT)
 *   - chef : gestion clients/réseaux, tableau de bord
 *   - agent : création transactions, historique personnel
 */

// Importation de la configuration de connexion et des utilitaires de sécurité.
include 'connexion.php';

// Vérification que l'utilisateur est connecté (redirection sinon vers login)
require_login();

// Récupération des informations de l'utilisateur connecté
$user = current_user();
$role = current_user_role();
$roleLabel = ucfirst($role);

// Libellé identité : en base, format « NomDeFamille Prénoms… » (cf. ajout_agent).
$identityLine = trim($user['nom_agent'] ?? '');
$identityParts = preg_split('/\s+/', $identityLine, 2);
$nomFamilleAffiche = $identityParts[0] ?? '';
$prenomsAffiche = $identityParts[1] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agence Transfert Argent</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Agence Transfert Argent</h1>
            <p class="site-subtitle">Tableau de bord — Choisissez une section autorisée selon votre rôle.</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="logout.php">Déconnexion</a>
        </div>
    </div>

    <section class="welcome-banner" aria-label="État de la session">
        <p class="welcome-banner-status">Vous êtes bien connecté · votre session est active sur votre compte.</p>
        <p class="welcome-banner-lead">Bienvenue,</p>
        <div class="welcome-banner-name">
            <?php if ($prenomsAffiche !== '' && $nomFamilleAffiche !== ''): ?>
                <span class="welcome-given"><?php echo clean($prenomsAffiche); ?></span>
                <span class="welcome-name-sep" aria-hidden="true"></span>
                <span class="welcome-family"><?php echo clean($nomFamilleAffiche); ?></span>
            <?php else: ?>
                <span class="welcome-fullname"><?php echo clean($identityLine !== '' ? $identityLine : $user['login_agent']); ?></span>
            <?php endif; ?>
        </div>
        <div class="welcome-banner-meta">
            <span class="welcome-meta-item"><strong>Rôle</strong> · <?php echo clean($roleLabel); ?></span>
            <span class="welcome-meta-item"><strong>Identifiant</strong> · <code class="welcome-login-code"><?php echo clean($user['login_agent']); ?></code></span>
        </div>
    </section>

    <div class="card-grid nav-grid">
        <?php if (user_has_role(['informaticien', 'chef'])): ?>
            <a class="nav-card" href="ajout_client.php">Ajouter un client</a>
            <a class="nav-card" href="liste_clients.php">Liste des clients</a>
            <a class="nav-card" href="ajout_reseau.php">Ajouter un réseau</a>
            <a class="nav-card" href="liste_reseaux.php">Liste des réseaux</a>
        <?php endif; ?>

        <?php if (user_has_role(['informaticien', 'chef'])): ?>
            <a class="nav-card" href="ajout_agent.php">Ajouter un agent</a>
            <a class="nav-card" href="liste_agents.php">Liste des agents</a>
        <?php endif; ?>

        <?php if (user_has_role(['informaticien'])): ?>
            <a class="nav-card" href="informaticien.php">Espace informaticien</a>
        <?php endif; ?>

        <?php if (user_has_role(['informaticien', 'agent'])): ?>
            <a class="nav-card" href="ajout_transac.php">Ajouter une transaction</a>
        <?php endif; ?>

        <?php if (user_has_role(['informaticien', 'chef', 'agent'])): ?>
            <a class="nav-card" href="liste_transaction.php">Liste des transactions</a>
        <?php endif; ?>

        <?php if (user_has_role(['chef', 'informaticien'])): ?>
            <a class="nav-card" href="chef.php">Tableau de bord Chef</a>
        <?php endif; ?>

        <a class="nav-card" href="profile.php">Mon profil</a>
        <?php if (user_has_role(['informaticien'])): ?>
            <a class="nav-card" href="presentation.php">Présentation de l'application</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
