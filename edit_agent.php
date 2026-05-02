<?php
/**
 * Agence Transfert Argent - Modification d'un agent existant
 * 
 * Cette page permet de modifier les informations d'un agent :
 *   - Nom et prénoms
 *   - Login (lecture seule, généré automatiquement)
 *   - Rôle (agent, chef, informaticien)
 *   - Mot de passe (réinitialisationpossible)
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Page de modification d'un agent existant.
include "connexion.php";
require_role('informaticien', 'chef');

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Identifiant agent invalide.');
}
$id = $_GET['id'];

try {
    $prepare = $cnt->prepare('SELECT id_agent, nom_agent, login_agent, role_agent FROM agents WHERE id_agent = :id');
    $prepare->execute([':id' => $id]);
    $agent = $prepare->fetch();
    if (!$agent) {
        die('Agent introuvable.');
    }
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$message = '';
$shownNewPassword = '';
$nom_agent = $agent['nom_agent'];
$login_agent = $agent['login_agent'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    $nom_agent = trim($_POST['nom_agent'] ?? '');
    $login_agent = trim($_POST['login_agent'] ?? '');
    $role_agent = $_POST['role_agent'] ?? 'agent';
    $allowedRoles = ['agent', 'chef', 'informaticien'];
    if (!in_array($role_agent, $allowedRoles, true)) {
        $role_agent = 'agent';
    }
    $postId = $_POST['id'] ?? '';

    $passwordPlain = null;
    $passwordError = '';
    $reset_random = isset($_POST['reset_mdp_aleatoire']) && $_POST['reset_mdp_aleatoire'] === '1';
    $nouveau_mdp = trim($_POST['nouveau_mdp'] ?? '');
    $nouveau_mdp_confirm = trim($_POST['nouveau_mdp_confirm'] ?? '');

    if ($reset_random) {
        $passwordPlain = generate_random_password();
    } elseif ($nouveau_mdp !== '' || $nouveau_mdp_confirm !== '') {
        if ($nouveau_mdp !== $nouveau_mdp_confirm) {
            $passwordError = 'Les deux champs du nouveau mot de passe ne correspondent pas.';
        } elseif (strlen($nouveau_mdp) < 8) {
            $passwordError = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            $passwordPlain = $nouveau_mdp;
        }
    }

    if ($nom_agent === '' || $login_agent === '' || !ctype_digit($postId) || $postId !== $id) {
        $message = 'Données d\'agent invalides.';
    } elseif ($passwordError !== '') {
        $message = $passwordError;
    } else {
        try {
            if ($passwordPlain !== null) {
                $prepare = $cnt->prepare('UPDATE agents SET nom_agent = :nom_agent, login_agent = :login_agent, role_agent = :role_agent, mdp_agent = :mdp_agent WHERE id_agent = :id');
                $execute = $prepare->execute([
                    ':nom_agent' => $nom_agent,
                    ':login_agent' => $login_agent,
                    ':role_agent' => $role_agent,
                    ':mdp_agent' => password_hash($passwordPlain, PASSWORD_DEFAULT),
                    ':id' => $id,
                ]);
            } else {
                $prepare = $cnt->prepare('UPDATE agents SET nom_agent = :nom_agent, login_agent = :login_agent, role_agent = :role_agent WHERE id_agent = :id');
                $execute = $prepare->execute([
                    ':nom_agent' => $nom_agent,
                    ':login_agent' => $login_agent,
                    ':role_agent' => $role_agent,
                    ':id' => $id,
                ]);
            }
            if ($execute) {
                $agent['role_agent'] = $role_agent;
                require_once __DIR__ . '/compte_csv_export.php';
                $identityParts = preg_split('/\s+/', $nom_agent, 2);
                $nomExport = $identityParts[0] ?? '';
                $prenomsExport = $identityParts[1] ?? '';
                append_compte_sauvegarde_csv('modification', (int) $id, $nomExport, $prenomsExport, $login_agent, $passwordPlain);

                if ($passwordPlain !== null) {
                    $message = 'Agent mis à jour. Nouveau mot de passe (notez-le, il ne pourra pas être affiché à nouveau) :';
                    $shownNewPassword = $passwordPlain;
                } else {
                    header('Location: liste_agents.php');
                    exit;
                }
            } else {
                $message = 'Impossible de modifier l\'agent.';
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
    <title>Modifier Agent</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Modifier un agent</h1>
            <p class="site-subtitle">Mettez à jour le profil et, si besoin, définissez un nouveau mot de passe (l’ancien n’est pas récupérable).</p>
        </div>
        <div class="actions">
            <a class="btn-link" href="liste_agents.php">Retour à la liste</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'succès') !== false || strpos($message, 'mis à jour') !== false) ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($shownNewPassword !== ''): ?>
                <div style="margin-top: 12px; line-height: 1.6;">
                    <strong>Nouveau mot de passe :</strong> <?php echo htmlspecialchars($shownNewPassword, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <form action="edit_agent.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="post">
            <label for="nom_agent">Nom et prénoms de l'agent :</label>
            <input type="text" id="nom_agent" name="nom_agent" value="<?php echo htmlspecialchars($nom_agent, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="login_agent">Login généré :</label>
            <input type="text" id="login_agent" name="login_agent" value="<?php echo htmlspecialchars($login_agent, ENT_QUOTES, 'UTF-8'); ?>" readonly>

            <label for="role_agent">Rôle :</label>
            <select id="role_agent" name="role_agent">
                <option value="agent" <?php echo $agent['role_agent'] === 'agent' ? 'selected' : ''; ?>>Agent</option>
                <option value="chef" <?php echo $agent['role_agent'] === 'chef' ? 'selected' : ''; ?>>Chef</option>
                <option value="informaticien" <?php echo $agent['role_agent'] === 'informaticien' ? 'selected' : ''; ?>>Informaticien</option>
            </select>

            <fieldset class="password-reset-fieldset">
                <legend>Mot de passe</legend>
                <p class="small-note">Pour des raisons de sécurité, le mot de passe actuel n’est jamais affiché. Pour qu’un agent se reconnecte après une perte, définissez un nouveau mot de passe ici.</p>
                <label class="checkbox-label">
                    <input type="checkbox" name="reset_mdp_aleatoire" value="1">
                    Générer un nouveau mot de passe aléatoire
                </label>
                <p class="small-note">Ou choisissez un mot de passe (minimum 8 caractères) :</p>
                <label for="nouveau_mdp">Nouveau mot de passe</label>
                <div class="password-field">
                    <input type="password" id="nouveau_mdp" name="nouveau_mdp" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-pressed="false" aria-label="Afficher le mot de passe" title="Afficher ou masquer le mot de passe">
                        <svg class="password-toggle-icon--show" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-toggle-icon--hide" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>

                <label for="nouveau_mdp_confirm">Confirmer le mot de passe</label>
                <div class="password-field">
                    <input type="password" id="nouveau_mdp_confirm" name="nouveau_mdp_confirm" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-pressed="false" aria-label="Afficher le mot de passe" title="Afficher ou masquer le mot de passe">
                        <svg class="password-toggle-icon--show" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-toggle-icon--hide" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </fieldset>

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Enregistrer les modifications">
        </form>
    </section>
</div>
</body>
</html>
