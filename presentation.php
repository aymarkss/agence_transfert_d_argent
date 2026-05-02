<?php
include 'connexion.php';
require_role('informaticien');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Présentation — Agence Transfert Argent</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .presentation-page .presentation-lead {
            font-size: 1.15rem;
            line-height: 1.55;
            color: rgba(23, 35, 58, 0.92);
            margin: 0 0 16px;
        }
        .presentation-page .panel h2 {
            margin-top: 0;
            color: #17233a;
            font-size: clamp(1.25rem, 2vw, 1.5rem);
        }
        .presentation-page .panel ul {
            margin: 12px 0;
            padding-left: 1.25rem;
            line-height: 1.55;
            color: #243456;
        }
        .presentation-page .panel li {
            margin-bottom: 8px;
        }
        .presentation-page .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }
        .presentation-page .tag {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(93, 140, 255, 0.15);
            color: #1e3a8a;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .presentation-page .presentation-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 20px;
        }
        .presentation-page .presentation-actions a.btn-link {
            text-decoration: none;
        }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .presentation-page .welcome-banner { border: 1px solid #ccc; }
        }
    </style>
</head>
<body class="loaded">
    <div class="page-container presentation-page">
        <header class="site-header">
            <div>
                <h1 class="site-title">Agence Transfert Argent</h1>
                <p class="site-subtitle">Document de présentation à destination de l'entreprise — application interne de gestion.</p>
            </div>
            <div class="actions">
                <a class="btn-link" href="index.php">Retour</a>
            </div>
        </header>

        <section class="welcome-banner" aria-label="Synthèse">
            <p class="welcome-banner-status">Prototype / outil métier</p>
            <p class="welcome-banner-lead">Vue d'ensemble</p>
            <div class="welcome-banner-name">
                <span class="welcome-fullname">Gestion des transferts, clients, réseaux et agents</span>
            </div>
            <div class="welcome-banner-meta">
                <span class="welcome-meta-item"><strong>Accès</strong> · par compte et rôle</span>
                <span class="welcome-meta-item"><strong>Environnement</strong> · Web (navigateur)</span>
            </div>
        </section>

        <section class="panel">
            <h2>Objectif</h2>
            <p class="presentation-lead">
                Cette application permet à une agence de transfert d'argent de <strong>centraliser</strong> les opérations&nbsp;:
                suivi des <strong>clients</strong> et des <strong>réseaux</strong>, enregistrement des <strong>transactions</strong>,
                et gestion des <strong>comptes utilisateurs</strong> (agents) selon les responsabilités de chacun.
            </p>
        </section>

<section class="panel">
            <h2>Fonctionnalités principales</h2>
            <ul>
                <li>Tableau de bord personnalisé selon le rôle (informaticien, chef d'agence, agent).</li>
                <li>Gestion des clients et des réseaux de transfert (création, listes, modification).</li>
                <li>Saisie et consultation des transactions (montants, frais, liaisons client / réseau / agent).</li>
                <li>Création de comptes agents avec identifiants générés et connexion sécurisée.</li>
                <li>Recherche, filtres et tri dans les listes (transactions, clients, agents).</li>
                <li>Tableau de bord Chef avec synthèse financière (dépôts, retraits, transferts, frais).</li>
                <li>Export CSV pour Excel (sauvegarde transactions et comptes agents).</li>
                <li>Espace documentaire pour l'équipe technique (informaticien).</li>
            </ul>
            <div class="tag-list">
                <span class="tag">Clients</span>
                <span class="tag">Réseaux</span>
                <span class="tag">Transactions</span>
                <span class="tag">Agents &amp; rôles</span>
                <span class="tag">Export CSV</span>
            </div>
        </section>

        <section class="panel">
            <h2>Rôles et responsabilités</h2>
            <ul>
                <li><strong>Informaticien</strong> — administration des comptes agents, accès technique et maintenance.</li>
                <li><strong>Chef d'agence</strong> — pilotage élargi (clients, réseaux, tableau de bord dédié).</li>
                <li><strong>Agent</strong> — opérations courantes, notamment les transactions autorisées.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Sécurité (rappel)</h2>
            <p class="presentation-lead">
                Les mots de passe sont stockés sous forme de <strong>hash</strong> (non lisibles en clair).
                En cas d'oubli, un informaticien peut <strong>définir un nouveau mot de passe</strong> depuis la fiche agent.
                Les pages sensibles sont protégées par session et par vérification du rôle.
            </p>
        </section>

        <section class="panel">
            <h2>Environnement technique</h2>
            <ul>
                <li><strong>Serveur :</strong> Apache (ex. WAMP en développement local).</li>
                <li><strong>Langage :</strong> PHP avec PDO et requêtes préparées.</li>
                <li><strong>Base de données :</strong> MySQL (schéma métier&nbsp;: agents, clients, réseaux, transactions, etc.).</li>
                <li><strong>Interface :</strong> HTML, CSS et JavaScript — navigation simple et lisible.</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Première utilisation</h2>
            <p class="presentation-lead">
                Au premier lancement, lorsqu'aucun agent n'existe encore, l'application guide vers la
                <strong>création du premier compte</strong> (en général avec le rôle informaticien).
                Ensuite, les utilisateurs se connectent depuis la page de connexion avec le login et le mot de passe fournis.
            </p>
            <div class="presentation-actions">
                <a class="btn-link" href="login.php">Accéder à la page de connexion</a>
                <a class="btn-link" href="index.php">Ouvrir le tableau de bord</a>
            </div>
            <p class="small-note" style="margin-top: 16px;">
                Le lien «&nbsp;Tableau de bord&nbsp;» redirige vers la connexion si vous n'êtes pas encore identifié.
            </p>
        </section>

        <footer class="site-header" style="margin-top: 32px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.12);">
            <p class="site-subtitle" style="margin: 0;">
                Document interne <code>presentation.php</code> — réservé aux informaticiens.
            </p>
        </footer>
    </div>
</body>
</html>
