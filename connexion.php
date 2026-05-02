<?php
// Paramètres sécurisés du cookie de session.
// httponly empêche l'accès au cookie depuis JavaScript.
// samesite=Strict réduit les risques de requêtes intersites (CSRF).
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
]);

// Démarre la session PHP pour stocker le token CSRF et d'autres données utilisateur.
session_start();

try {
    // Options PDO pour une connexion plus sûre et plus prévisible.
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lever une exception en cas d'erreur SQL.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retourner des tableaux associatifs par défaut.
        PDO::ATTR_EMULATE_PREPARES => false, // Utiliser les vrais prepared statements MySQL.
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4', // Forcer l'encodage UTF-8 complet.
    ];

    // Crée l'objet PDO pour la base de données.
    // Remplacez les identifiants par ceux de votre environnement de production si nécessaire.
    $cnt = new PDO('mysql:host=localhost;dbname=db_transfert_argent_2;charset=utf8mb4', 'root', '', $options);

    // Assure l'existence de la colonne de rôle dans la table des agents.
    ensure_agent_role_column_exists($cnt);
} catch (PDOException $e) {
    // En cas d'erreur de connexion, afficher un message sécurisé et arrêter l'exécution.
    die('Erreur de connexion : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/**
 * Retourne un token CSRF unique pour la session en cours.
 * Si le token n'existe pas encore, il est généré et stocké en session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie l'existence de la colonne 'role_agent' dans la table agents.
 * Si la colonne est manquante, elle est ajoutée automatiquement.
 */
function ensure_agent_role_column_exists(PDO $cnt): void
{
    try {
        $stmt = $cnt->prepare("SHOW COLUMNS FROM agents LIKE 'role_agent'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $cnt->exec("ALTER TABLE agents ADD COLUMN role_agent VARCHAR(20) NOT NULL DEFAULT 'agent'");
        }
    } catch (PDOException $e) {
        // Si la table agents n'existe pas encore ou si la requête échoue, on ignore.
    }
}

/**
 * Vérifie qu'un token CSRF reçu en entrée correspond bien au token stocké en session.
 * hash_equals est utilisé pour éviter les attaques par timing.
 */
function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Échappe une chaîne avant affichage HTML pour éviter les attaques XSS.
 */
function clean(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Indique si le réseau est Wave (retrait sans frais).
 * Le nom en base doit être exactement « Wave » (insensible à la casse).
 */
function reseau_est_wave(string $nomReseau): bool
{
    return strcasecmp(trim($nomReseau), 'wave') === 0;
}

/**
 * Frais selon le type et le réseau :
 * - dépôt : aucun frais ;
 * - transfert : 1 % du montant ;
 * - retrait : 1 % sauf si réseau Wave (0 %).
 */
function transaction_compute_frais(float $montant, string $type, string $nomReseau): float
{
    $type = strtolower(trim($type));
    switch ($type) {
        case 'depot':
            return 0.0;
        case 'transfert':
            return round($montant * 0.01, 2);
        case 'retrait':
            if (reseau_est_wave($nomReseau)) {
                return 0.0;
            }

            return round($montant * 0.01, 2);
        default:
            return round($montant * 0.01, 2);
    }
}

/**
 * Lit année / mois depuis la requête (historique, tableau de bord chef).
 * Si un mois est choisi sans année, l'année en cours est utilisée.
 *
 * @return array{annee: int, mois: int}
 */
function parse_transaction_period_filters(): array
{
    $annee = isset($_GET['annee']) ? (int) $_GET['annee'] : 0;
    $mois = isset($_GET['mois']) ? (int) $_GET['mois'] : 0;
    if ($mois < 0 || $mois > 12) {
        $mois = 0;
    }
    if ($mois > 0 && $annee === 0) {
        $annee = (int) date('Y');
    }

    return ['annee' => $annee, 'mois' => $mois];
}

/**
 * Fragment SQL sécurisé pour filtrer par période (année seule ou année + mois).
 *
 * @return array{sql: string, params: array<string, int>}
 */
function transaction_period_sql_fragment(string $tableAlias, int $annee, int $mois): array
{
    if ($annee > 0 && $mois > 0) {
        return [
            'sql' => " AND YEAR({$tableAlias}.created_at) = :tf_y AND MONTH({$tableAlias}.created_at) = :tf_m",
            'params' => [':tf_y' => $annee, ':tf_m' => $mois],
        ];
    }
    if ($annee > 0) {
        return [
            'sql' => " AND YEAR({$tableAlias}.created_at) = :tf_y",
            'params' => [':tf_y' => $annee],
        ];
    }

    return ['sql' => '', 'params' => []];
}

/**
 * Ordre de tri autorisé pour la liste des transactions (identifiant → clause ORDER BY).
 */
function transaction_sort_order_map(): array
{
    return [
        'date_desc' => 't.created_at DESC',
        'date_asc' => 't.created_at ASC',
        'montant_desc' => 't.montant DESC, t.created_at DESC',
        'montant_asc' => 't.montant ASC, t.created_at DESC',
        'type_az' => 't.type ASC, t.created_at DESC',
        'client_az' => 'c.nom_client ASC, t.created_at DESC',
    ];
}

/**
 * Retourne la clause ORDER BY pour la liste des transactions (liste blanche).
 */
function parse_transaction_sort_order(): string
{
    $tri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'date_desc';
    $map = transaction_sort_order_map();
    if (!isset($map[$tri])) {
        $tri = 'date_desc';
    }

    return $map[$tri];
}

/**
 * Ordre de tri autorisé pour la liste des agents (identifiant → clause ORDER BY).
 */
function agent_sort_order_map(): array
{
    return [
        'nom_asc' => 'nom_agent ASC',
        'nom_desc' => 'nom_agent DESC',
        'login_asc' => 'login_agent ASC',
        'login_desc' => 'login_agent DESC',
        'role_asc' => 'role_agent ASC, nom_agent ASC',
        'role_desc' => 'role_agent DESC, nom_agent ASC',
        'id_asc' => 'id_agent ASC',
        'id_desc' => 'id_agent DESC',
    ];
}

/**
 * Retourne la clause ORDER BY pour la liste des agents (liste blanche).
 */
function parse_agent_sort_order(): string
{
    $tri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'nom_asc';
    $map = agent_sort_order_map();
    if (!isset($map[$tri])) {
        $tri = 'nom_asc';
    }

    return $map[$tri];
}

/**
 * Ordre de tri autorisé pour la liste des clients (identifiant → clause ORDER BY).
 */
function client_sort_order_map(): array
{
    return [
        'nom_asc' => 'nom_client ASC',
        'nom_desc' => 'nom_client DESC',
        'tel_asc' => 'tel_client ASC',
        'tel_desc' => 'tel_client DESC',
        'id_asc' => 'id_client ASC',
        'id_desc' => 'id_client DESC',
    ];
}

/**
 * Filtre de recherche pour les transactions (terme → tableau params).
 * Recherche par nom client, nom agent, type de transaction.
 *
 * @return array{search: string, params: array<string, string>}
 */
function parse_transaction_search(): array
{
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    if ($search === '') {
        return ['search' => '', 'params' => []];
    }

    return [
        'search' => $search,
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Fragment SQL sécurisé pour le filtre de recherche transactions.
 *
 * @return array{sql: string, params: array<string, string>}
 */
function transaction_search_sql_fragment(string $search): array
{
    if ($search === '') {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' AND (c.nom_client LIKE :search_term_c OR a.nom_agent LIKE :search_term_a OR t.type LIKE :search_term_t)',
        'params' => [
            ':search_term_c' => '%' . $search . '%',
            ':search_term_a' => '%' . $search . '%',
            ':search_term_t' => '%' . $search . '%',
        ],
    ];
}

/**
 * Filtre de recherche pour les agents (terme → tableau params).
 * Recherche par nom agent, login.
 *
 * @return array{search: string, params: array<string, string>}
 */
function parse_agent_search(): array
{
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    if ($search === '') {
        return ['search' => '', 'params' => []];
    }

    return [
        'search' => $search,
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Fragment SQL sécurisé pour le filtre de recherche agents.
 *
 * @return array{sql: string, params: array<string, string>}
 */
function agent_search_sql_fragment(string $search): array
{
    if ($search === '') {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' AND (nom_agent LIKE :search_term OR login_agent LIKE :search_term)',
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Filtre de recherche pour les clients (terme → tableau params).
 * Recherche par nom client, téléphone.
 *
 * @return array{search: string, params: array<string, string>}
 */
function parse_client_search(): array
{
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    if ($search === '') {
        return ['search' => '', 'params' => []];
    }

    return [
        'search' => $search,
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Fragment SQL sécurisé pour le filtre de recherche clients.
 *
 * @return array{sql: string, params: array<string, string>}
 */
function client_search_sql_fragment(string $search): array
{
    if ($search === '') {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' AND (nom_client LIKE :search_term OR tel_client LIKE :search_term)',
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Filtre de recherche pour les réseaux (terme → tableau params).
 * Recherche par nom réseau.
 *
 * @return array{search: string, params: array<string, string>}
 */
function parse_reseau_search(): array
{
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    if ($search === '') {
        return ['search' => '', 'params' => []];
    }

    return [
        'search' => $search,
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Fragment SQL sécurisé pour le filtre de recherche réseaux.
 *
 * @return array{sql: string, params: array<string, string>}
 */
function reseau_search_sql_fragment(string $search): array
{
    if ($search === '') {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' AND nom_reseau LIKE :search_term',
        'params' => [':search_term' => '%' . $search . '%'],
    ];
}

/**
 * Ordre de tri autorisé pour la liste des réseaux (identifiant → clause ORDER BY).
 */
function reseau_sort_order_map(): array
{
    return [
        'nom_asc' => 'nom_reseau ASC',
        'nom_desc' => 'nom_reseau DESC',
        'id_asc' => 'id_reseau ASC',
        'id_desc' => 'id_reseau DESC',
    ];
}

/**
 * Retourne la clause ORDER BY pour la liste des réseaux (liste blanche).
 */
function parse_reseau_sort_order(): string
{
    $tri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'nom_asc';
    $map = reseau_sort_order_map();
    if (!isset($map[$tri])) {
        $tri = 'nom_asc';
    }

    return $map[$tri];
}

/**
 * Retourne la clause ORDER BY pour la liste des clients (liste blanche).
 */
function parse_client_sort_order(): string
{
    $tri = isset($_GET['tri']) ? (string) $_GET['tri'] : 'nom_asc';
    $map = client_sort_order_map();
    if (!isset($map[$tri])) {
        $tri = 'nom_asc';
    }

    return $map[$tri];
}

/**
 * Normalise une chaîne pour un identifiant : accents → ASCII, minuscules, lettres seules.
 */
function agent_normalize_for_login(string $s): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t === false) {
        $t = $s;
    }

    return strtolower(preg_replace('/[^a-z]/', '', $t));
}

/**
 * Initiales des prénoms (espaces et tirets séparent Jean, Marie, Jean-Marie → JM).
 */
function agent_first_name_initials(string $prenoms): string
{
    $parts = preg_split('/[\s\-]+/', trim($prenoms), -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    foreach ($parts as $p) {
        $n = agent_normalize_for_login($p);
        if ($n !== '') {
            $out .= strtoupper(substr($n, 0, 1));
        }
    }

    return substr($out, 0, 5);
}

/**
 * Login lisible : 3 lettres du nom de famille + initiales des prénoms + 4 chiffres (unicité).
 * Ex. Dupont + Jean-Marie → dupjm4523
 */
function generate_agent_login(PDO $cnt, string $nomFamille, string $prenoms): string
{
    $nomNorm = agent_normalize_for_login($nomFamille);
    $ini = agent_first_name_initials($prenoms);
    if ($nomNorm === '') {
        $nomNorm = 'usr';
    }
    if ($ini === '') {
        $ini = 'X';
    }
    $nomChunk = substr($nomNorm, 0, 3);
    $base = strtolower(substr($nomChunk . $ini, 0, 14));
    $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $login = $base . $suffix;

    $prepare = $cnt->prepare('SELECT COUNT(*) FROM agents WHERE login_agent = :login');
    $prepare->execute([':login' => $login]);
    if ($prepare->fetchColumn() > 0) {
        return generate_agent_login($cnt, $nomFamille, $prenoms);
    }

    return $login;
}

/**
 * Mot de passe plus robuste mais lié à l’identité : préfixe nom + tirets + initiales + chiffres + symbole.
 * Ex. DUP-JM-58492!
 */
function generate_memorable_agent_password(string $nomFamille, string $prenoms): string
{
    $n = agent_normalize_for_login($nomFamille);
    $ini = agent_first_name_initials($prenoms);
    if ($ini === '') {
        $ini = 'X';
    }
    $prefix = strtoupper(strlen($n) >= 3 ? substr($n, 0, 3) : str_pad($n, 3, 'x'));
    $digits = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    $symbols = '!@#$%&';
    $sym = $symbols[random_int(0, strlen($symbols) - 1)];

    return $prefix . '-' . $ini . '-' . $digits . $sym;
}

/**
 * Génère un mot de passe pseudo-aléatoire lisible (syllabes + chiffres), pour réinitialisations sans nom/prénoms séparés.
 */
function generate_random_password(int $length = 10): string
{
    $syllables = ['ka', 'lo', 'mi', 'na', 're', 'si', 'ta', 'vo', 'ze', 'du', 'pa', 'lu'];
    $password = '';
    while (strlen($password) < $length) {
        $password .= $syllables[array_rand($syllables)];
    }
    $password = substr($password, 0, $length - 2);
    $password .= random_int(10, 99);

    return ucfirst($password);
}

/**
 * Configuration de sécurité
 * Durée d'inactivité avant déconnexion automatique (en secondes).
 * Par défaut : 15 minutes (900 secondes).
 */
define('SESSION_TIMEOUT', 900);

/**
 * Nombre maximum de tentatives de connexion avant verrouillage.
 * Par défaut : 5 tentatives.
 */
define('MAX_LOGIN_ATTEMPTS', 5);

/**
 * Durée de verrouillage après trop de tentatives (en secondes).
 * Par défaut : 30 minutes.
 */
define('LOCKOUT_DURATION', 1800);

/**
 * Retourne l'utilisateur connecté ou null si aucune session active.
 * Vérifie également le délai d'inactivité.
 */
function current_user(): ?array
{
    // Vérifier le délai d'inactivité
    if (!check_session_timeout()) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Vérifie si la session a expiré par inactivité.
 * Met à jour le timestamp de dernière activité si valide.
 * Returns true si la session est valide, false sinon.
 */
function check_session_timeout(): bool
{
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }

    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_TIMEOUT) {
        // Session expirée
        return false;
    }

    // Mettre à jour le timestamp
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Met à jour le timestamp de dernière activité.
 * À appeler après chaque action utilisateur.
 */
function update_session_activity(): void
{
    $_SESSION['last_activity'] = time();
}

/**
 * Force la déconnexion pour expiration de session.
 */
function session_expired_logout(): void
{
    $_SESSION['session_expired'] = true;
    logout_user();
}

/**
 * Enregistre une tentative de connexion échouée.
 * Verrouille le compte après MAX_LOGIN_ATTEMPTS échecs.
 */
function record_failed_login(string $login): void
{
    global $cnt;
    
    $key = 'failed_logins_' . strtolower($login);
    $attempts = isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
    $attempts++;
    $_SESSION[$key] = $attempts;
    
    // Enregistrer le moment du premier échec pour le verrouillage
    if ($attempts === 1) {
        $_SESSION['first_failure_time_' . strtolower($login)] = time();
    }
}

/**
 * Vérifie si un compte est verrouillé après trop de tentatives.
 * Returns false si verrouillé, true sinon.
 */
function is_account_locked(string $login): bool
{
    global $cnt;
    
    $key = strtolower($login);
    $attempts = isset($_SESSION['failed_logins_' . $key]) ? (int) $_SESSION['failed_logins_' . $key] : 0;
    
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $firstFailure = isset($_SESSION['first_failure_time_' . $key]) ? (int) $_SESSION['first_failure_time_' . $key] : 0;
        
        // Vérifier si le verrouillage a expiré
        if ($firstFailure > 0 && (time() - $firstFailure) > LOCKOUT_DURATION) {
            // Réinitialiser après la durée de verrouillage
            unset($_SESSION['failed_logins_' . $key]);
            unset($_SESSION['first_failure_time_' . $key]);
            return false;
        }
        return true;
    }
    
    return false;
}

/**
 * Réinitialise les tentatives de connexion après succès.
 */
function reset_login_attempts(string $login): void
{
    $key = strtolower($login);
    unset($_SESSION['failed_logins_' . $key]);
    unset($_SESSION['first_failure_time_' . $key]);
}

/**
 * Retourne le nombre de tentatives restantes avant verrouillage.
 */
function remaining_login_attempts(string $login): int
{
    $key = strtolower($login);
    $attempts = isset($_SESSION['failed_logins_' . $key]) ? (int) $_SESSION['failed_logins_' . $key] : 0;
    return max(0, MAX_LOGIN_ATTEMPTS - $attempts);
}

/**
 * Enregistre une action dans le journal d'audit.
 * Utilise la table audit_log si elle existe, sinon enregistre dans un fichier CSV.
 * 
 * @param string $action Action effectuée (create, update, delete, login, logout, etc.)
 * @param string $table 表concernée (agents, clients, transactions, etc.)
 * @param int|null $recordId ID de l'enregistrement concerné
 * @param string|null $details Détails supplémentaires (JSON ou texte libre)
 * @param int|null $agentId ID de l'agent (optionnel, utilise la session si absent)
 */
function log_audit(string $action, string $table, ?int $recordId = null, ?string $details = null, ?int $agentId = null): void
{
    global $cnt;
    
    $login = current_user()['login_agent'] ?? 'unknown';
    $idAgent = $agentId ?? (current_user()['id_agent'] ?? null);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Tronquer user_agent si trop long
    if (strlen($userAgent) > 255) {
        $userAgent = substr($userAgent, 0, 252) . '...';
    }
    
    try {
        // Essayer d'abord d'utiliser la table audit_log
        $stmt = $cnt->query("SHOW TABLES LIKE 'audit_log'");
        if ($stmt->rowCount() > 0) {
            $prepare = $cnt->prepare('INSERT INTO audit_log (action, table_concernee, id_enregistrement, details, id_agent, login_agent, ip_address, user_agent) VALUES (:action, :table, :record_id, :details, :agent_id, :login, :ip, :user_agent)');
            $prepare->execute([
                ':action' => $action,
                ':table' => $table,
                ':record_id' => $recordId,
                ':details' => $details,
                ':agent_id' => $idAgent,
                ':login' => $login,
                ':ip' => $ipAddress,
                ':user_agent' => $userAgent,
            ]);
            return;
        }
    } catch (PDOException $e) {
        // Table n'existe pas, utiliser le fichier CSV
    }
    
    // fallback : enregistrer dans un fichier CSV
    $csvFile = __DIR__ . '/exports/audit_actions.csv';
    $line = sprintf(
        "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
        date('Y-m-d H:i:s'),
        $action,
        $table,
        $recordId ?? '',
        $details ?? '',
        $idAgent ?? '',
        $login,
        $ipAddress,
        $userAgent
    );
    
    // Ajouter l'en-tête si le fichier n'existe pas
    if (!file_exists($csvFile)) {
        $header = "datetime,action,table,record_id,details,agent_id,login,ip,user_agent\n";
        file_put_contents($csvFile, $header);
    }
    
    file_put_contents($csvFile, $line, FILE_APPEND);
}

/**
 * Enregistre une connexion réussie dans l'audit.
 */
function log_audit_login_success(?int $agentId = null): void
{
    $login = current_user()['login_agent'] ?? $_POST['login_agent'] ?? 'unknown';
    log_audit('login_success', 'agents', $agentId, 'Connexion réussie', $agentId);
}

/**
 * Enregistre une tentative de connexion échouée.
 */
function log_audit_login_failed(string $login, string $reason = 'Mot de passe incorrect'): void
{
    log_audit('login_failed', 'agents', null, $reason . ' - Login: ' . $login);
}

/**
 * Enregistre une création dans l'audit.
 */
function log_audit_create(string $table, int $recordId, ?string $details = null): void
{
    log_audit('create', $table, $recordId, $details);
}

/**
 * Enregistre une modification dans l'audit.
 */
function log_audit_update(string $table, int $recordId, ?string $details = null): void
{
    log_audit('update', $table, $recordId, $details);
}

/**
 * Enregistre une suppression dans l'audit.
 */
function log_audit_delete(string $table, int $recordId, ?string $details = null): void
{
    log_audit('delete', $table, $recordId, $details);
}

/**
 * Indique si un utilisateur est connecté.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id_agent']);
}

/**
 * Indique si la table agents est vide (première utilisation).
 * En cas d'erreur SQL (table absente, etc.), on considère qu'un compte doit être créé.
 */
function agents_table_is_empty(PDO $cnt): bool
{
    try {
        $stmt = $cnt->query('SELECT COUNT(*) FROM agents');

        return ((int) $stmt->fetchColumn()) === 0;
    } catch (PDOException $e) {
        return true;
    }
}

/**
 * Retourne le rôle de l'utilisateur connecté.
 */
function current_user_role(): string
{
    return $_SESSION['user']['role_agent'] ?? 'agent';
}

/**
 * Vérifie que l'utilisateur connecté possède l'un des rôles autorisés.
 */
function user_has_role(array $allowedRoles): bool
{
    if (!is_logged_in()) {
        return false;
    }
    $role = strtolower(current_user_role());
    foreach ($allowedRoles as $allowed) {
        if ($role === strtolower($allowed)) {
            return true;
        }
    }
    return false;
}

/**
 * Force la connexion avant d'accéder à une page.
 * Si aucun agent n'existe encore, envoie vers la création du premier compte.
 * Gère également l'expiration de session par inactivité.
 */
function require_login(): void
{
    if (is_logged_in()) {
        update_session_activity();
        return;
    }
    
    // Vérifier si la session a expiré
    if (isset($_SESSION['last_activity'])) {
        $_SESSION['session_expired'] = true;
    }
    
    global $cnt;
    if ($cnt instanceof PDO && agents_table_is_empty($cnt)) {
        header('Location: ajout_agent.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

/**
 * Force la connexion et vérifie le rôle.
 */
function require_role(string ...$roles): void
{
    require_login();
    if (!user_has_role($roles)) {
        http_response_code(403);
        die('Accès refusé : vous n\'avez pas les droits pour voir cette page.');
    }
}

/**
 * Authentifie un agent avec son login et son mot de passe.
 */
function login_user(PDO $cnt, string $login, string $password): bool
{
    ensure_agent_role_column_exists($cnt);
    $prepare = $cnt->prepare('SELECT id_agent, nom_agent, login_agent, mdp_agent, role_agent FROM agents WHERE login_agent = :login LIMIT 1');
    $prepare->execute([':login' => $login]);
    $agent = $prepare->fetch(PDO::FETCH_ASSOC);
    if ($agent && password_verify($password, $agent['mdp_agent'])) {
        $_SESSION['user'] = [
            'id_agent' => $agent['id_agent'],
            'nom_agent' => $agent['nom_agent'],
            'login_agent' => $agent['login_agent'],
            'role_agent' => $agent['role_agent'] ?? 'agent',
        ];
        return true;
    }
    return false;
}

/**
 * Déconnecte l'utilisateur actif.
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
?>