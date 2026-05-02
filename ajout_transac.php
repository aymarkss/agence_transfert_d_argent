<?php
/**
 * Agence Transfert Argent - Ajout d'une nouvelle transaction
 * 
 * Cette page permet d'enregistrer une nouvelle opération financière.
 * Types de transactions disponibles :
 *   - depot  : dépôt d'argent (frais 0%)
 *   - retrait : retrait d'argent (frais 1%, sauf Wave = 0%)
 *   - transfert : transfert d'argent (frais 1%)
 * 
 * Les frais sont calculés automatiquement selon le type et le réseau.
 * 
 * Accès : informaticien (tous), agent (uniquement sa propre activité)
 */

// Chargement de la connexion PDO et des fonctions utilitaires.
include "connexion.php";
require_role('informaticien', 'agent');

// Récupération des listes de clients, réseaux et agents pour les menus déroulants.
try {
    $requete = $cnt->query('SELECT id_client, nom_client FROM clients ORDER BY nom_client');
    $clients = $requete->fetchAll(PDO::FETCH_ASSOC);

    $requete = $cnt->query('SELECT id_reseau, nom_reseau FROM reseaux ORDER BY nom_reseau');
    $reseaux = $requete->fetchAll(PDO::FETCH_ASSOC);

    if (user_has_role(['agent'])) {
        $agents = [
            ['id_agent' => current_user()['id_agent'], 'nom_agent' => current_user()['nom_agent']],
        ];
    } else {
        $requete = $cnt->query('SELECT id_agent, nom_agent FROM agents ORDER BY nom_agent');
        $agents = $requete->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur PDO, on stoppe proprement avec un message clair.
    die('Erreur lors de la récupération des données : ' . $e->getMessage());
}

// Initialisation du message à afficher sur le formulaire.
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protection CSRF : uniquement les requêtes légitimes de ce formulaire sont acceptées.
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    // Lecture et nettoyage des champs POST.
    $client_id = $_POST['client_id'] ?? '';
    $reseau_id = $_POST['reseau_id'] ?? '';
    $agent_id = $_POST['agent_id'] ?? '';
    $type = $_POST['type'] ?? '';
    $montant = $_POST['montant'] ?? '';

    if (user_has_role(['agent'])) {
        $agent_id = current_user()['id_agent'];
    }

    // Validation des données avant insertion en base.
    if ($client_id === '' || $reseau_id === '' || $agent_id === '' || $type === '' || $montant === '') {
        $message = 'Tous les champs obligatoires doivent être renseignés.';
    } elseif (!in_array($type, ['depot', 'retrait', 'transfert'], true)) {
        $message = 'Type de transaction invalide.';
} elseif (!ctype_digit((string)$client_id) || !ctype_digit((string)$reseau_id) || !ctype_digit((string)$agent_id)) {
        $message = 'Sélection de client, réseau ou agent invalide.';
    } elseif (!is_numeric($montant) || $montant <= 0) {
        $message = 'Le montant doit être un nombre positif.';
    } else {
        $nomReseauStmt = $cnt->prepare('SELECT nom_reseau FROM reseaux WHERE id_reseau = :id LIMIT 1');
        $nomReseauStmt->execute([':id' => $reseau_id]);
        $reseauRow = $nomReseauStmt->fetch(PDO::FETCH_ASSOC);
if (!$reseauRow) {
            $message = 'Réseau introuvable.';
        } else {
            $frais = transaction_compute_frais((float) $montant, $type, (string) $reseauRow['nom_reseau']);
            try {
                $prepare = $cnt->prepare('INSERT INTO transactions (type, montant, frais, client_id, reseau_id, agent_id) VALUES (:type, :montant, :frais, :client_id, :reseau_id, :agent_id)');
                $execute = $prepare->execute([
                    ':type' => $type,
                    ':montant' => $montant,
                    ':frais' => $frais,
                    ':client_id' => $client_id,
                    ':reseau_id' => $reseau_id,
                    ':agent_id' => $agent_id,
                ]);

if ($execute) {
                    $message = 'Transaction ajoutée avec succès.';
                    
                    // Enregistrer dans le fichier Excel (CSV)
                    require_once __DIR__ . '/transaction_csv_export.php';
                    $newTransacId = (int) $cnt->lastInsertId();
                    
                    // Journal d'audit
                    log_audit_create('transactions', $newTransacId, "Type: {$type}, Montant: {$montant}, Frais: {$frais}");
                    
                    // Récupérer les noms du client, réseau et agent
                    $clientStmt = $cnt->prepare('SELECT nom_client FROM clients WHERE id_client = :id LIMIT 1');
                    $clientStmt->execute([':id' => $client_id]);
                    $clientRow = $clientStmt->fetch(PDO::FETCH_ASSOC);
                    $nomClient = $clientRow ? $clientRow['nom_client'] : '';
                    
                    $reseauStmt = $cnt->prepare('SELECT nom_reseau FROM reseaux WHERE id_reseau = :id LIMIT 1');
                    $reseauStmt->execute([':id' => $reseau_id]);
                    $reseauRow = $reseauStmt->fetch(PDO::FETCH_ASSOC);
                    $nomReseau = $reseauRow ? $reseauRow['nom_reseau'] : '';
                    
                    $agentStmt = $cnt->prepare('SELECT nom_agent FROM agents WHERE id_agent = :id LIMIT 1');
                    $agentStmt->execute([':id' => $agent_id]);
                    $agentRow = $agentStmt->fetch(PDO::FETCH_ASSOC);
                    $nomAgent = $agentRow ? $agentRow['nom_agent'] : '';
                    
                    append_transaction_csv('création', $newTransacId, $type, (float) $montant, (float) $frais, (int) $client_id, $nomClient, (int) $reseau_id, $nomReseau, (int) $agent_id, $nomAgent);
                } else {
                    $message = 'Impossible d\'ajouter la transaction.';
                }
            } catch (PDOException $e) {
                $message = 'Erreur lors de l\'ajout de la transaction : ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout de Transaction</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
    <div class="site-header">
        <div>
            <h1 class="site-title">Ajouter une transaction</h1>
            <p class="site-subtitle">Enregistrez facilement un nouveau dépôt, retrait ou transfert.</p>
        </div>
        <a class="btn-link" href="liste_transaction.php">Voir l'historique</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2 class="page-title">Formulaire transaction</h2>
        <form action="ajout_transac.php" method="post">
            <div class="form-field">
                <label for="client_id">Client :</label>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Sélectionnez un client --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id_client']; ?>"><?php echo htmlspecialchars($client['nom_client'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="reseau_id">Réseau :</label>
                <select id="reseau_id" name="reseau_id" required>
                    <option value="">-- Sélectionnez un réseau --</option>
                    <?php foreach ($reseaux as $reseau): ?>
                        <option value="<?php echo $reseau['id_reseau']; ?>" data-nom-reseau="<?php echo htmlspecialchars($reseau['nom_reseau'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($reseau['nom_reseau'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (user_has_role(['agent'])): ?>
                <div class="form-field">
                    <label>Agent :</label>
                    <p><?php echo clean(current_user()['nom_agent']); ?></p>
                    <input type="hidden" name="agent_id" value="<?php echo current_user()['id_agent']; ?>">
                </div>
            <?php else: ?>
                <div class="form-field">
                    <label for="agent_id">Agent :</label>
                    <select id="agent_id" name="agent_id" required>
                        <option value="">-- Sélectionnez un agent --</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['id_agent']; ?>"><?php echo htmlspecialchars($agent['nom_agent'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="type">Type d'opération :</label>
                <select id="type" name="type" required>
                    <option value="">-- Sélectionnez un type --</option>
                    <option value="depot">Dépôt</option>
                    <option value="retrait">Retrait</option>
                    <option value="transfert">Transfert</option>
                </select>
            </div>

            <div class="form-field">
                <label for="montant">Montant :</label>
                <input type="number" id="montant" name="montant" step="0.01" min="0.01" required>
            </div>

            <div class="form-field">
                <p class="small-note">Frais calculés automatiquement : <strong id="frais-preview">0,00</strong>. <span id="frais-rule-hint">Dépôt&nbsp;: 0&nbsp;%. Transfert&nbsp;: 1&nbsp;%. Retrait&nbsp;: 1&nbsp;% sauf réseau <strong>Wave</strong> (0&nbsp;%).</span></p>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="submit" value="Ajouter la transaction">
        </form>
    </section>
</div>
<script>
(function () {
    function reseauEstWave(nom) {
        return String(nom || '').trim().toLowerCase() === 'wave';
    }
    function computeFrais(montant, type, nomReseau) {
        var v = parseFloat(String(montant).replace(',', '.'), 10);
        if (!isFinite(v) || v <= 0) return null;
        type = String(type || '').toLowerCase();
        if (type === 'depot') return 0;
        if (type === 'transfert') return Math.round(v * 0.01 * 100) / 100;
        if (type === 'retrait') return reseauEstWave(nomReseau) ? 0 : Math.round(v * 0.01 * 100) / 100;
        return Math.round(v * 0.01 * 100) / 100;
    }
    function selectedReseauNom(selectEl) {
        if (!selectEl || selectEl.selectedIndex < 0) return '';
        var opt = selectEl.options[selectEl.selectedIndex];
        return opt ? (opt.getAttribute('data-nom-reseau') || opt.textContent || '').trim() : '';
    }
    var montantEl = document.getElementById('montant');
    var typeEl = document.getElementById('type');
    var reseauEl = document.getElementById('reseau_id');
    var previewEl = document.getElementById('frais-preview');
    if (!montantEl || !previewEl || !typeEl || !reseauEl) return;
    function syncFraisPreview() {
        var frais = computeFrais(montantEl.value, typeEl.value, selectedReseauNom(reseauEl));
        if (frais === null) {
            previewEl.textContent = '—';
            return;
        }
        previewEl.textContent = frais.toFixed(2).replace('.', ',');
    }
    montantEl.addEventListener('input', syncFraisPreview);
    montantEl.addEventListener('change', syncFraisPreview);
    typeEl.addEventListener('change', syncFraisPreview);
    reseauEl.addEventListener('change', syncFraisPreview);
    syncFraisPreview();
})();
</script>
</body>
</html>
