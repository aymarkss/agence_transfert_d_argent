# Agence Transfert Argent - System Documentation

## Objectif
Documenter tout le système pour expliquer son fonctionnement.

## Tâches à effectuer

### 1. Fichiers de base (core)
- [x] README.md - Vue d'ensemble du système
- [x] connexion.php - Deja bien commenté
- [x] login.php - Authentification
- [x] logout.php - Deconnexion
- [x] index.php - Dashboard principal

### 2. Gestion des agents
- [x] ajout_agent.php - Ajout nouvel agent
- [x] edit_agent.php - Modification agent
- [x] delete_agent.php - Suppression agent
- [x] liste_agents.php - Liste des agents

### 3. Gestion des clients
- [x] ajout_client.php - Ajout nouveau client
- [x] edit_client.php - Modification client
- [x] delete_client.php - Suppression client
- [x] liste_clients.php - Liste des clients

### 4. Gestion des reseaux
- [x] ajout_reseau.php - Ajout nouveau reseau
- [x] edit_reseau.php - Modification reseau
- [x] delete_reseau.php - Suppression reseau
- [x] liste_reseaux.php - Liste des reseaux

### 5. Transactions
- [x] ajout_transac.php - Ajout nouvelle transaction
- [x] edit_transaction.php - Modification transaction
- [x] delete_transaction.php - Suppression transaction
- [x] liste_transaction.php - Liste des transactions
- [x] view_transaction.php - Details transaction

### 6. Roles speciaux
- [x] chef.php - Tableau de bord chef
- [x] informaticien.php - Espace informaticien
- [x] profile.php - Profil utilisateur

### 7. Export et autres
- [x] transaction_csv_export.php - Export CSV transactions
- [x] compte_csv_export.php - Export CSV comptes
- [x] presentation.php - Presentation application
- [ ] script.js - JavaScript client
- [ ] style.css - Styles CSS

## Résumé des commentaires ajoutés

Chaque fichier PHP dispose maintenant d'un bloc de commentaires d'en-tête qui explique :
- Le nom du fichier et sa fonction principale
- Les données affichées ou traitées
- Les rôles autorisé à accéder à la page
- La protection CSRF utilisée

## Nouvelles fonctionnalités de sécurité (2025)

### 1. Délai de session (Session Timeout)
- [x] Déconnexion automatique après 15 minutes d'inactivité
- [x] Message explicatif lors de la reconnexion
- [x] Mise à jour du timestamp à chaque action

### 2. Verrouillage après échecs de connexion
- [x] Maximum 5 tentatives avant verrouillage
- [x] Verrouillage pendant 30 minutes
- [x] Compteur de tentatives restantes affiché

### 3. Journal d'audit (Audit Log)
- [x] Installation automatique de la table audit_log
- [x] Enregistrement des connexions (succès/échec)
- [x] Enregistrement des création/modification/suppression
- [x] Fallback CSV si table n'existe pas

### Fichiers modifiés
- connexion.php : nouvelles fonctions de sécurité et audit
- login.php : vérification verrouillage, messages adaptés
- ajout_transac.php : audit des transactions
- installer_audit.php : script d'installation de la table
