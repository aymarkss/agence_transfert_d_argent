<?php
/**
 * Agence Transfert Argent - Ajout d'un nouvel agent
 * 
 * Cette page permet de créer un nouveau compte agent.
 * Le login et le mot de passe sont générés automatiquement :
 * - Login : 3 premières lettres du nom + initiales prénom + 4 chiffres
 * - Mot de passe : préfixe nom - initiales - 5 chiffres - symbole
 * 
 * Rôles disponibles : agent, chef, informaticien
 * 
 * PREMIÈRE UTILISATION :
 * Si aucun agent n'existe,任何人 peut créer le premier compte (rôle informaticien par défaut).
 * Sinon, seul les rôles informaticien et chef peuvent ajouter des agents.
 */

// Importation de la connexion et des fonctions de sécurité.
include "connexion.php";

// Vérifie si des agents existent déjà dans la base
$noAgents = agents_table_is_empty($cnt);

if (!$noAgents) {
    require_role('informaticien', 'chef');
}

// Message d'état affiché sur le formulaire d'ajout d'agent.
$message = '';
$generatedLogin = '';
$generatedPassword = '';
$defaultRole = $noAgents ? 'informaticien' : 'agent';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protection CSRF sur l'envoi de formulaire.
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    // Nettoyage des données de formulaire reçues.
    $nom_agent = trim($_POST['nom_agent'] ?? '');
    $prenoms_agent = trim($_POST['prenoms_agent'] ?? '');

    if ($nom_agent === '' || $prenoms_agent === '') {
        $message = 'Le nom et les prénoms de l\'agent sont requis.';
    } else {
        try {
            $fullName = $nom_agent . ' ' . $prenoms_agent;
            $generatedLogin = generate_agent_login($cnt, $nom_agent, $prenoms_agent);
            $generatedPassword = generate_memorable_agent_password($nom_agent, $prenoms_agent);
            $role_agent = $_POST['role_agent'] ?? 'agent';
            $allowedRoles = ['agent', 'chef', 'informaticien'];
            if (!in_array($role_agent, $allowedRoles, true)) {
                $role_agent = 'agent';
            }

            $prepare = $cnt->prepare('INSERT INTO agents (nom_agent, login_agent, mdp_agent, role_agent) VALUES (:nom_agent, :login_agent, :mdp_agent, :role_agent)');
            $execute = $prepare->execute([
                ':nom_agent' => $fullName,
                ':login_agent' => $generatedLogin,
                ':mdp_agent' => password_hash($generatedPassword, PASSWORD_DEFAULT),
                ':role_agent' => $role_agent,
            ]);

            if ($execute) {
                $message = 'Agent ajouté avec succès. Voici ses informations de connexion :';
                $newId = (int) $cnt->lastInsertId();
                if ($newId > 0) {
                    require_once __DIR__ . '/compte_csv_export.php';
                    append_compte_sauvegarde_csv('création', $newId, $nom_agent, $prenoms_agent, $generatedLogin, $generatedPassword);
                }
            } else {
                $message = 'Impossible d\'ajouter l\'agent.';
            }
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
    <title>Ajouter Agent</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title"><?php echo $noAgents ? 'Créer le premier agent' : 'Ajouter un agent'; ?></h1>
            <p class="site-subtitle"><?php echo $noAgents ? 'Première utilisation : créez un compte administrateur pour démarrer le système.' : 'Le système génère automatiquement le login et le mot de passe.'; ?></p>
        </div>
        <?php if (!$noAgents): ?>
        <div class="actions">
            <a class="btn-link" href="index.php">Retour</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($generatedLogin && $generatedPassword): ?>
                <div style="margin-top: 12px; line-height: 1.6;">
                    <strong>Login :</strong> <?php echo htmlspecialchars($generatedLogin, ENT_QUOTES, 'UTF-8'); ?><br>
                    <strong>Mot de passe :</strong> <?php echo htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <form action="login.php" method="post" class="connect-new-account-form">
                    <input type="hidden" name="login_agent" value="<?php echo htmlspecialchars($generatedLogin, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="password" value="<?php echo htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn">Se connecter avec ce compte</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <section class="panel">
        <form action="ajout_agent.php" method="post">
            <label for="nom_agent">Nom de famille :</label>
            <input type="text" id="nom_agent" name="nom_agent" placeholder="Ex. Dupont" required autocomplete="family-name">

            <label for="prenoms_agent">Prénoms :</label>
            <input type="text" id="prenoms_agent" name="prenoms_agent" placeholder="Ex. Jean, Marie" required autocomplete="given-name">

            <label for="role_agent">Rôle :</label>
            <select id="role_agent" name="role_agent">
                <option value="agent"<?php echo $defaultRole === 'agent' ? ' selected' : ''; ?>>Agent</option>
                <option value="chef"<?php echo $defaultRole === 'chef' ? ' selected' : ''; ?>>Chef</option>
                <option value="informaticien"<?php echo $defaultRole === 'informaticien' ? ' selected' : ''; ?>>Informaticien</option>
            </select>

            <p class="small-note">Après validation : le <strong>login</strong> reprend les 3 premières lettres du nom + les initiales des prénoms + 4 chiffres (ex. Dupont + Jean-Marie → <code>dupjm4523</code>). Le <strong>mot de passe</strong> est plus long : préfixe du nom, tirets, initiales, 5 chiffres et un symbole (ex. <code>DUP-JM-58492!</code>). Une ligne est aussi enregistrée dans <code>exports/sauvegarde_comptes_agents.csv</code> pour Excel.</p>

            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Ajouter">
        </form>
    </section>
</div>
</body>
</html>
