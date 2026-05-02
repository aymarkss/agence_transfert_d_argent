<?php
/**
 * Agence Transfert Argent - Espace Informaticien
 * 
 * Cette page est destinée à l'informaticien (administrateur IT).
 * Elle contient :
 *   - Vue d'ensemble du système
 *   - Structure du projet
 *   - Environnement de développement
 *   - Conseils de maintenance
 *   - Prévention de la fraude
 * 
 * Accès : uniquement le rôle informaticien
 */

include 'connexion.php';
require_role('informaticien');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Informaticien</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="page-container">
        <div class="site-header">
            <div>
                <h1 class="site-title">Espace Informaticien</h1>
                <p class="site-subtitle">Informations système et guide de l'environnement de travail.</p>
            </div>
            <div class="actions">
                <a class="btn-link" href="index.php">Retour</a>
                <a class="btn-link" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <section class="panel">
            <h2>Vue d'ensemble du système</h2>
            <p>Cette application de gestion d'agence de transfert repose sur :</p>
            <ul>
                <li><strong>PHP</strong> pour la logique serveur et le traitement des formulaires.</li>
                <li><strong>MySQL</strong> pour la base de données des clients, réseaux, transactions et agents.</li>
                <li><strong>PDO</strong> pour les connexions sécurisées et les requêtes préparées.</li>
                <li><strong>WAMP</strong> pour l'environnement local avec Apache, PHP et MySQL.</li>
            </ul>
            <p>Le fichier de connexion principal est <code>connexion.php</code>. Il gère :</p>
            <ul>
                <li>la connexion à la base de données,</li>
                <li>la vérification du rôle utilisateur,</li>
                <li>la protection des pages selon le rôle.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Structure du projet</h2>
            <p>Les pages principales sont :</p>
            <ul>
                <li><code>index.php</code> : tableau de bord principal selon le rôle.</li>
                <li><code>chef.php</code> : tableau de bord du chef d'agence avec synthèse financière.</li>
                <li><code>informaticien.php</code> : documentation interne et information technique.</li>
                <li><code>presentation.php</code> : document de présentation de l'application.</li>
                <li><code>ajout_agent.php</code>, <code>edit_agent.php</code>, <code>liste_agents.php</code> : gestion des comptes agents.</li>
                <li><code>ajout_client.php</code>, <code>edit_client.php</code>, <code>liste_clients.php</code> : gestion des clients.</li>
                <li><code>ajout_reseau.php</code>, <code>edit_reseau.php</code>, <code>liste_reseaux.php</code> : gestion des réseaux.</li>
                <li><code>ajout_transac.php</code>, <code>edit_transaction.php</code>, <code>delete_transaction.php</code>, <code>liste_transaction.php</code> : gestion des transactions.</li>
                <li><code>view_transaction.php</code> : fiche détaillée d'une transaction.</li>
                <li><code>profile.php</code> : profil utilisateur.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Environnement de développement</h2>
            <p>Pour une nouvelle arrivée, voici les points importants :</p>
            <ul>
                <li><strong>Emplacement du projet :</strong> <code>c:\wamp64\www\agence_transfert_argent</code></li>
                <li><strong>Accès à WAMP :</strong> démarrer Apache et MySQL depuis l'icône Wampmanager.</li>
                <li><strong>Configuration DB :</strong> si besoin, modifiez les identifiants dans <code>connexion.php</code>.</li>
                <li><strong>Base de données :</strong> la table <code>agents</code> contient les colonnes <code>login_agent</code>, <code>mdp_agent</code> et <code>role_agent</code>.</li>
                <li><strong>Mot de passe :</strong> les mots de passe sont hachés avec <code>password_hash()</code> et vérifiés avec <code>password_verify()</code>.</li>
                <li><strong>Sauvegarde Excel (CSV) agents :</strong> à chaque <strong>création</strong> ou <strong>modification</strong> d'agent, une ligne est ajoutée dans le dossier <code>exports/</code>, fichier <code>sauvegarde_comptes_agents.csv</code> (ouvrable avec Excel). Les mots de passe en clair n'y figurent que lors d'une création ou d'un changement de mot de passe ; sinon la colonne reste vide. Ouvrir le fichier depuis l'explorateur Windows, pas via l'URL web (accès HTTP bloqué par sécurité).</li>
                <li><strong>Sauvegarde Excel (CSV) transactions :</strong> à chaque <strong>création</strong>, <strong>modification</strong> ou <strong>suppression</strong> de transaction, une ligne est enregistrée dans <code>exports/sauvegarde_transactions.csv</code>. Ce fichier suit l'historique complet des opérations pour audit et contrôle. Ouvrir depuis l'explorateur Windows.</li>
                <li><strong>Protection CSRF :</strong> les formulaires utilisent un token anti-CSRF via <code>csrf_token()</code> et <code>verify_csrf_token()</code> pour prévenir les requêtes malveillantes.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Conseils pour la maintenance</h2>
            <ul>
                <li>Utiliser des requêtes préparées pour toute nouvelle requête SQL.</li>
                <li>Ne jamais afficher les erreurs SQL en production.</li>
                <li>Vérifier les droits dans <code>connexion.php</code> avant d'ajouter une page.</li>
                <li>Ajouter un accès restreint à l'aide de <code>require_role('informaticien')</code> si la page est sensible.</li>
                <li>Tous les formulaires doivent inclure un champ CSRF token caché.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Prévention de la fraude</h2>
            <p>Limiter la fraude interne et externe repose sur des <strong>règles métier</strong> et sur des <strong>contrôles dans l'application</strong>. Ce qui suit sert de référence pour l'équipe et pour les évolutions du système.</p>

            <h3 class="doc-subheading">Ce qu'il faut faire en agence (hors logiciel)</h3>
            <ul>
                <li><strong>Séparation des tâches :</strong> idéalement, la personne qui enregistre les opérations n'est pas la seule à valider les totaux de fin de période ; le chef peut utiliser le tableau de bord et l'historique filtré pour contrôler.</li>
                <li><strong>Rapprochement régulier :</strong> comparer les totaux du système (montants, frais) avec la caisse, la banque ou les relevés des opérateurs ; tout écart doit être expliqué avant clôture.</li>
                <li><strong>Contrôles ponctuels :</strong> décomptes de caisse surprise, politique claire sur les annulations et les réclamations.</li>
                <li><strong>Personnel :</strong> consignes écrites, formation aux arnaques (faux justificatifs, manipulation), et canal confidentiel pour signaler un doute.</li>
                <li><strong>Conformité :</strong> respecter la réglementation locale (KYC, obligations de déclaration) ; un conseil juridique ou un expert conformité reste nécessaire pour les obligations légales.</li>
            </ul>

            <h3 class="doc-subheading">Ce qu'il faut dans le système pour limiter la fraude</h3>
            <ul>
                <li><strong>Comptes et règles :</strong> chaque utilisateur a un identifiant distinct ; les droits sont limités au besoin (agent, chef, informaticien). Ne pas partage</li>
                <li><strong>Traçabilité des comptes agents :</strong> le fichier <code>exports/sauvegarde_comptes_agents.csv</code> enregistre les créations et modifications de comptes (voir section environnement). À compléter si besoin par une politique de révision des accès.</li>
                <li><strong>Transactions :</strong> les agents ne voient que leurs propres opérations ; le chef et l'informaticien ont une vue plus large. La suppression de transactions est réservée à l'informaticien.</li>
                <li><strong>Historique transactions :</strong> le fichier <code>exports/sauvegarde_transactions.csv</code> enregistre chaque opération (création, modification, suppression) pour audit.</li>
                <li><strong>Piste d'audit complète (recommandé à développer) :</strong> journal des actions sensibles (création / modification / suppression de transactions, changements de rôles, connexions suspectes) avec date, utilisateur et détail de l'action, stocké de façon consultable et difficile à altérer.</li>
                <li><strong>Règles métier renforcées (évolutions possibles) :</strong> double validation pour les corrections ou suppressions, alertes sur montants ou fréquences anormales, export automatique périodique pour archivage et contrôle externe.</li>
                <li><strong>Sécurité technique :</strong> HTTPS en production, sauvegardes de la base de données, mises à jour PHP/MySQL, et ne pas exposer les fichiers de configuration ou les exports via le web.</li>
            </ul>
            <p class="small-note">Les fonctionnalités marquées "à développer" ou "évolutions possibles" ne sont pas toutes implémentées dans la version actuelle ; elles constituent une feuille de route pour réduire encore le risque.</p>
        </section>

<section class="panel">
            <h2>Points d'accès utiles</h2>
            <div class="card-grid nav-grid">
                <a class="nav-card" href="ajout_agent.php">Ajouter un agent</a>
                <a class="nav-card" href="liste_agents.php">Liste des agents</a>
                <a class="nav-card" href="ajout_client.php">Ajouter un client</a>
                <a class="nav-card" href="liste_reseaux.php">Liste des réseaux</a>
                <a class="nav-card" href="liste_transaction.php">Liste des transactions</a>
                <a class="nav-card" href="presentation.php">Présentation</a>
                <a class="nav-card" href="voir_audit.php">📋 Journal d'audit</a>
            </div>
        </section>
    </div>
</body>
</html>
