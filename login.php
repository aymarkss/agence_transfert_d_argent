<?php
/**
 * Agence Transfert Argent - Page de connexion utilisateur
 * 
 * Cette page permet aux agents de s'authentifier pour accéder au système.
 * Elle vérifie les identifiants (login/mot de passe) et crée la session.
 * 
 * PREMIÈRE UTILISATION :
 * Si aucun agent n'existe encore, l'utilisateur est redirigé vers ajout_agent.php
 * pour créer le premier compte administrateur.
 */

// Importation de la configuration de connexion et des utilitaires de sécurité.
include 'connexion.php';

// Message d'état affiché en cas d'erreur d'authentification
$message = '';
$login = '';
$password = '';

// Afficher le message de session expirée
if (isset($_SESSION['session_expired']) && $_SESSION['session_expired'] === true) {
    $message = 'Votre session a expiré par inactivité. Veuillez vous reconnecter.';
    unset($_SESSION['session_expired']);
}

if (agents_table_is_empty($cnt)) {
    header('Location: ajout_agent.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login_agent'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($login === '' || $password === '') {
        $message = 'Veuillez renseigner votre login et votre mot de passe.';
    } elseif (is_account_locked($login)) {
        $remaining = LOCKOUT_DURATION;
        $minutes = ceil($remaining / 60);
        $message = "Compte verrouillé. Veuillez réessayer dans {$minutes} minutes.";
} elseif (login_user($cnt, $login, $password)) {
        reset_login_attempts($login);
        log_audit_login_success();
        header('Location: index.php');
        exit;
    } else {
        record_failed_login($login);
        log_audit_login_failed($login, 'Mot de passe incorrect');
        $remaining = remaining_login_attempts($login);
        if ($remaining > 0) {
            $message = "Login ou mot de passe incorrect. Il vous reste {$remaining} tentative(s) avant verrouillage.";
        } else {
            $minutes = ceil(LOCKOUT_DURATION / 60);
            $message = "Compte verrouillé après plusieurs tentatives échouées. Veuillez réessayer dans {$minutes} minutes.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Connexion utilisateur</h1>
            <p class="site-subtitle">Accédez à votre espace selon votre rôle.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message error"><?php echo clean($message); ?></div>
    <?php endif; ?>

    <section class="panel">
        <form action="login.php" method="post">
            <label for="login_agent">Login :</label>
            <input type="text" id="login_agent" name="login_agent" value="<?php echo clean($login); ?>" required>

            <label for="password">Mot de passe :</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="button" class="password-toggle" aria-pressed="false" aria-label="Afficher le mot de passe" title="Afficher ou masquer le mot de passe">
                    <svg class="password-toggle-icon--show" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="password-toggle-icon--hide" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>

            <input type="submit" value="Se connecter">
        </form>
        <p class="login-register-hint">Première utilisation ou pas encore de compte ? <a href="ajout_agent.php">Créer un compte administrateur</a></p>
        <p class="login-help-hint">Mot de passe oublié ? On ne peut pas afficher l’ancien (il est sécurisé). Demandez à un <strong>informaticien</strong> ou au <strong>chef d’agence</strong> de vous en définir un nouveau via <strong>Liste des agents</strong> → <strong>Modifier</strong> sur votre ligne.</p>
    </section>
    </div>
</body>
</html>
