<?php
/**
 * Agence Transfert Argent - Suppression d'un réseau
 * 
 * Cette page supprime un réseau de la base de données.
 * La requête doit être en méthode POST avec un token CSRF valide.
 * 
 * Accès : uniquement les rôles informaticien et chef
 */

// Action de suppression d'un réseau via POST uniquement.
include "connexion.php";
require_role('informaticien', 'chef');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode de requête non autorisée.');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide.');
}

if (empty($_POST['id']) || !ctype_digit($_POST['id'])) {
    die('Identifiant réseau invalide.');
}

$id = $_POST['id'];
try {
    $prepare = $cnt->prepare('DELETE FROM reseaux WHERE id_reseau = :id');
    $execute = $prepare->execute([':id' => $id]);
    if ($execute) {
        header('Location: liste_reseaux.php');
        exit;
    }
    die('Impossible de supprimer le réseau.');
} catch (PDOException $e) {
    die('Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>