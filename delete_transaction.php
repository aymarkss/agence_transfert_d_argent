<?php
// Inclusion de la configuration de connexion et des fonctions de sécurité.
include "connexion.php";
require_role('informaticien');

// Cette action ne doit être accessible que via POST pour éviter la suppression par URL.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode de requête non autorisée.');
}

// Vérifie que le token CSRF POST est valide.
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide.');
}

// Validations supplémentaires sur l'identifiant reçu.
if (empty($_POST['id']) || !ctype_digit($_POST['id'])) {
    die('Identifiant de transaction invalide.');
}

$id = $_POST['id'];
try {
    // Récupérer les détails de la transaction avant suppression pour le fichier Excel
    $prepare_select = $cnt->prepare('SELECT type, montant, frais, client_id, reseau_id, agent_id FROM transactions WHERE id_transac = :id');
    $prepare_select->execute([':id' => $id]);
    $transaction = $prepare_select->fetch(PDO::FETCH_ASSOC);
    
    $prepare = $cnt->prepare('DELETE FROM transactions WHERE id_transac = :id');
    $execute = $prepare->execute([':id' => $id]);
    
    if ($execute && $transaction) {
        // Enregistrer dans le fichier Excel (CSV)
        require_once __DIR__ . '/transaction_csv_export.php';
        
        // Récupérer les noms du client, réseau et agent
        $clientStmt = $cnt->prepare('SELECT nom_client FROM clients WHERE id_client = :id LIMIT 1');
        $clientStmt->execute([':id' => $transaction['client_id']]);
        $clientRow = $clientStmt->fetch(PDO::FETCH_ASSOC);
        $nomClient = $clientRow ? $clientRow['nom_client'] : '';
        
        $reseauStmt = $cnt->prepare('SELECT nom_reseau FROM reseaux WHERE id_reseau = :id LIMIT 1');
        $reseauStmt->execute([':id' => $transaction['reseau_id']]);
        $reseauRow = $reseauStmt->fetch(PDO::FETCH_ASSOC);
        $nomReseau = $reseauRow ? $reseauRow['nom_reseau'] : '';
        
        $agentStmt = $cnt->prepare('SELECT nom_agent FROM agents WHERE id_agent = :id LIMIT 1');
        $agentStmt->execute([':id' => $transaction['agent_id']]);
        $agentRow = $agentStmt->fetch(PDO::FETCH_ASSOC);
        $nomAgent = $agentRow ? $agentRow['nom_agent'] : '';
        
        append_transaction_csv('suppression', (int) $id, $transaction['type'], (float) $transaction['montant'], (float) $transaction['frais'], (int) $transaction['client_id'], $nomClient, (int) $transaction['reseau_id'], $nomReseau, (int) $transaction['agent_id'], $nomAgent);
        
        header('Location: liste_transaction.php');
        exit;
    }
    if ($execute) {
        header('Location: liste_transaction.php');
        exit;
    }
    die('Impossible de supprimer la transaction.');
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>