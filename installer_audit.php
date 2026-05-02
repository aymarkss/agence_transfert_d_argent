<?php
/*
 * Agence Transfert Argent - Script d'installation de la table d'audit
 * 
 * Ce script crée la table audit_log pour enregistrer toutes les actions des utilisateurs.
 * Exécuter ce fichier une fois via le navigateur pour installer la table.
 * URL : http://localhost/agence_transfert_argent/installer_audit.php
 */

// Importation de la connexion
include 'connexion.php';

echo '<h1>Installation du journal d\'audit</h1>';

try {
    // Vérifier si la table existe déjà
    $stmt = $cnt->query("SHOW TABLES LIKE 'audit_log'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo '<p>La table audit_log existe déjà.</p>';
    } else {
        // Créer la table audit_log
        $sql = "CREATE TABLE audit_log (
            id_audit INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            table_concernee VARCHAR(50) NOT NULL,
            id_enregistrement INT,
            details TEXT,
            id_agent INT,
            login_agent VARCHAR(50),
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_table (table_concernee),
            INDEX idx_agent (id_agent),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $cnt->exec($sql);
        echo '<p>Table audit_log créée avec succès !</p>';
    }
    
    // Afficher les informations
    echo '<p>Le journal d\'audit est prêt à être utilisé.</p>';
    echo '<p>Vous pouvez maintenant utiliser les fonctions d\'audit dans votre application.</p>';
    echo '<p><a href="index.php">Retour au tableau de bord</a></p>';
    
} catch (PDOException $e) {
    echo '<p>Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation Audit</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        h1 { color: #2c3e50; }
        p { line-height: 1.6; }
        a { color: #3498db; }
    </style>
</head>
<body>
</body>
</html>
