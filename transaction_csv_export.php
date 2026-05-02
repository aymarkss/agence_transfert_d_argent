<?php

declare(strict_types=1);

/*
 * Ajoute une ligne dans exports/sauvegarde_transactions.csv (UTF-8, séparateur ;).
 * Ouvre dans Excel. Enregistre toutes les transactions effectuées.
 */
function append_transaction_csv(string $evenement, int $idTransaction, string $type, float $montant, float $frais, int $idClient, string $nomClient, int $idReseau, string $nomReseau, int $idAgent, string $nomAgent): void
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'exports';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
    }

    $file = $dir . DIRECTORY_SEPARATOR . 'sauvegarde_transactions.csv';
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
            // BOM UTF-8 pour Excel
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, [
                'Date et heure',
                'Événement',
                'ID Transaction',
                'Type',
                'Montant',
                'Frais',
                'ID Client',
                'Nom Client',
                'ID Réseau',
                'Nom Réseau',
                'ID Agent',
                'Nom Agent'
            ], ';');
        }

        fputcsv($fp, [
            date('Y-m-d H:i:s'),
            $evenement,
            (string) $idTransaction,
            $type,
            number_format($montant, 2, ',', ''),
            number_format($frais, 2, ',', ''),
            (string) $idClient,
            $nomClient,
            (string) $idReseau,
            $nomReseau,
            (string) $idAgent,
            $nomAgent
        ], ';');
        fflush($fp);
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
