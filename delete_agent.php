<?php
/**
 * Agence Transfert Argent - Suppression d'un agent
 * 
 * Cette page supprime un agent de la base de données.
 * La requête doit être en méthode POST avec un token CSRF valide.
 * Un agent ne peut pas supprimer son propre compte.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Action de suppression d'un agent via POST uniquement.
include "connexion.php";
require_role('informaticien', 'chef');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode de requête non autorisée.');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide.');
}

if (empty($_POST['id']) || !ctype_digit($_POST['id'])) {
    die('Identifiant agent invalide.');
}

$id = $_POST['id'];
if ((int) $id === (int) (current_user()['id_agent'] ?? 0)) {
    die('Vous ne pouvez pas supprimer votre propre compte.');
}
try {
    $prepare = $cnt->prepare('DELETE FROM agents WHERE id_agent = :id');
    $execute = $prepare->execute([':id' => $id]);
    if ($execute) {
        header('Location: liste_agents.php');
        exit;
    }
    die('Impossible de supprimer l agent.');
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>