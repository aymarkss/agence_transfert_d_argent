<?php

declare(strict_types=1);

/**
 * Ajoute une ligne dans exports/sauvegarde_comptes_agents.csv (UTF-8, séparateur ;).
 * Ouvre dans Excel. Fichier sensible (mots de passe en clair) — à protéger sur le serveur.
 */
function append_compte_sauvegarde_csv(string $evenement, int $idAgent, string $nom, string $prenoms, string $login, ?string $motDePasse): void
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'exports';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
    }

    $file = $dir . DIRECTORY_SEPARATOR . 'sauvegarde_comptes_agents.csv';
    $needHeader = !is_file($file) || filesize($file) === 0;

    $fp = fopen($file, 'ab');
    if ($fp === false) {
        return;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return;
    }

    try {
        if ($needHeader) {
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, ['Date et heure', 'Événement', 'ID agent', 'Nom', 'Prénoms', 'Identifiant', 'Mot de passe'], ';');
        }

        fputcsv($fp, [
            date('Y-m-d H:i:s'),
            $evenement,
            (string) $idAgent,
            $nom,
            $prenoms,
            $login,
            $motDePasse ?? '',
        ], ';');
        fflush($fp);
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
