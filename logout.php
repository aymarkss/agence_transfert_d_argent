<?php
/*
 * Agence Transfert Argent - Déconnexion utilisateur
 * 
 * Cette page détruit la session et redirige vers la page de connexion.
 * Elle appelle la fonction logout_user() qui :
 *   - Efface les données de session
 *   - Détruit le cookie de session
 *   - Detruit la session
 */

include 'connexion.php';
logout_user();
header('Location: login.php');
exit;
