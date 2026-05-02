<?php
/**
 * Agence Transfert Argent - Suppression d'un client
 * 
 * Cette page supprime un client de la base de données.
 * La requête doit être en méthode POST avec un token CSRF valide.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Action de suppression d'un client via POST uniquement.
include "connexion.php";
require_role('informaticien', 'chef');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode de requête non autorisée.');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide.');
}

if (empty($_POST['id']) || !ctype_digit($_POST['id'])) {
    die('Identifiant client invalide.');
}

$id = $_POST['id'];
try {
    $prepare = $cnt->prepare('DELETE FROM clients WHERE id_client = :id');
    $execute = $prepare->execute([':id' => $id]);
    if ($execute) {
        header('Location: liste_clients.php');
        exit;
    }
    die('Impossible de supprimer le client.');
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>