# Agence Transfert Argent - Documentation du Système

## Vue d'ensemble

**Agence Transfert Argent** est une application web PHP/MySQL permettant de gérer les opérations d'une agence de transfert d'argent (Western Union, Wave, etc.). Le système gère les clients, les agents, les réseaux de transfert et les transactions financières.

## Architecture du Système

### Structure des fichiers

```
agence_transfert_argent/
├── connexion.php         # Connexion DB et fonctions utilitaires
├── index.php            # Dashboard principal
├── login.php           # Authentication
├── logout.php          # Déconnexion
├── profile.php          # Profil utilisateur
│
├── ajout_*.php         # Formulaires d'ajout
├── edit_*.php          # Formulaires de modification
├── delete_*.php        # Suppressions
├── liste_*.php         # Listes et rapports
│
├── chef.php            # Tableau de bord chef
├── informaticien.php   # Espace administrateur IT
├── view_*.php         # Détails d'un enregistrement
│
├── transaction_csv_export.php  # Export CSV transactions
├── compte_csv_export.php        # Export CSV comptes agents
├── presentation.php            # Présentation technique
```

### Base de données

Tables principales :
- `agents` - Personnel de l'agence (login, mot de passe哈希, rôle)
- `clients` - Clients de l'agence
- `reseaux` - Réseaux de transfert (Wave, Western Union, etc.)
- `transactions` - Opérations financières

### Rôles système

| Rôle | Description | Permissions |
|------|-------------|-------------|
| `informaticien` | Administrateur IT | Accès complet, gestion des agents, maintenance |
| `chef` | Responsable d'agence | Gestion clients/réseaux, tableau de bord financier |
| `agent` | Guichetier | Création transactions, consultation historique |

## Fonctionnalités principales

### 1. Authentification et securité

- **Connexion sécurisée** avec mot de passe哈希 (password_hash)
- **Protection CSRF** via tokens pseudo-aléatoires
- **Filtrage XSS** avec htmlspecialchars
- **Rôles的用户验证** avec require_role()

### 2. Gestion des agents

- **Création automatique** de login (3 premières lettres du nom + initiales prénom + 4 chiffres)
- **Mot de passe mémorable généré** (préfixe nom - initiales - chiffres - symbole)
- **Rôles** : agent, chef, informaticien

### 3. Transactions financières

Types d'opérations :
- **Dépôt** : 0% de frais
- **Retrait** : 1% de frais (0% pour Wave)
- **Transfert** : 1% de frais

Calcul automatique des frais via `transaction_compute_frais()`.

### 4. Tableau de bord Chef

- Synthèse des clients, réseaux, agents
- Volumes financiers (dépôts, retraits, transferts)
- Frais encaissés
- Filtres par période (année/mois)

## Flux utilisateur

### Premiere utilisation

1. Accéder à login.php → redirection automatique vers ajout_agent.php
2. Créer le premier compte (rôle informaticien par défaut)
3. Se connecter avec les identifiants générés
4. Compléter le profil : ajouter des clients, réseaux

### Flux agent standard

1. Connexion login → Dashboard
2. Ajouter client (si inexistant)
3. Créer transaction (dépôt/retrait/transfert)
4. Voir historique personnel
5. Déconnexion

### Flux chef

1. Connexion → Dashboard
2. Voir tableau de bord financier
3. Consulter/gérer clients, réseaux, agents
4. Analyser historique transactions
5. Export CSV

## Mesures de sécurité

- **Sessions sécurisées** (httponly, samesite=Strict)
- **Mot de passe哈希** avec password_hash()
- **Tokens CSRF** pour tous les formulaires POST
- **Requêtes préparées PDO** (évite injection SQL)
- **Échappement HTML** (évite XSS)
- **Vérification de rôle** pour chaque page protégée

## Configuration

Base de données : MySQL
- Hôte : localhost
- Base : db_transfert_argent_2
- Encodage : utf8mb4

Créer les tables SQL nécessaires via phpMyAdmin ou équivalent.

## Technologies utilisées

- **PHP 8+** (programmation serveur)
- **MySQL/PDO** (base de données)
- **HTML5/CSS3** (interface)
- **JavaScript** (interactivité)
- **CSS** (stylisation)
