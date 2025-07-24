# 🚀 Portail Guldagil - Calc Frais de Port

> **Portail web professionnel** pour la gestion des achats, logistique et transport  
> **Version :** `0.5 beta + build auto` | **Secteur :** Traitement de l'eau et solutions industrielles

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Beta-orange.svg)](https://github.com)

---

## 📋 Table des matières

- [🎯 Présentation](#-présentation)
- [✨ Fonctionnalités](#-fonctionnalités)
- [🏗️ Architecture](#️-architecture)
- [🛠️ Technologies](#️-technologies)
- [📦 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [🚀 Utilisation](#-utilisation)
- [🔧 Modules](#-modules)
- [🔐 Authentification](#-authentification)
- [📊 Base de données](#-base-de-données)
- [🎨 Assets & Design](#-assets--design)
- [🔍 Diagnostic & Maintenance](#-diagnostic--maintenance)
- [📚 Documentation](#-documentation)
- [🤝 Contribution](#-contribution)

---

## 🎯 Présentation

**Portail Guldagil** est une solution web modulaire et professionnelle dédiée à l'optimisation des processus d'achats et de logistique pour le secteur du traitement de l'eau. Le module principal **Calc Frais de Port** permet le calcul intelligent des frais de transport selon différents transporteurs (XPO, Heppner, Kuehne+Nagel).

### 🎖️ Objectifs du projet

- **Interface fluide et intuitive** pour les utilisateurs métier
- **Architecture modulaire** favorisant l'évolutivité
- **Calculs automatisés** des frais de transport
- **Gestion centralisée** des configurations
- **Sécurité renforcée** avec authentification obligatoire

---

## ✨ Fonctionnalités

### 🚚 Calculateur de Frais de Port
- **Calcul automatique** multi-transporteurs (XPO, Heppner, K+N)
- **Intelligence artificielle** : sélection automatique du type d'envoi selon le poids
- **Gestion ADR** : transport de marchandises dangereuses
- **Zones tarifaires** personnalisées par département
- **Surcharges et options** (palette, urgence, livraison étage...)
- **Interface moderne** avec progression intelligente

### 👥 Gestion des Utilisateurs
- **Authentification sécurisée** avec sessions PHP
- **Rôles différenciés** : dev, admin, user, logistique
- **Dashboard personnalisé** par type d'utilisateur
- **Historique d'activité** et logs détaillés

### ⚙️ Administration
- **Interface d'administration** complète
- **Scanner de diagnostic** automatique
- **Gestion de la base de données** en temps réel
- **Configuration globale** du portail
- **Monitoring** et maintenance

---

## 🏗️ Architecture

### 📁 Structure du projet

```
📦 portail-guldagil/
├── 📂 config/                    # Configuration globale
│   ├── config.php               # Config principale
│   ├── version.php              # Gestion des versions
│   └── roles.php                # Système de rôles
├── 📂 core/                      # Classes communes (autoload)
│   ├── auth/
│   │   └── AuthManager.php      # Gestionnaire authentification
│   └── transport/
│       └── transport.php        # Classes de calcul transport
├── 📂 public/                    # Fichiers publics accessibles
│   ├── index.php                # Point d'entrée principal
│   ├── .htaccess                # Réécriture URLs
│   ├── 📂 port/                 # Module calculateur (principal)
│   │   ├── index.php
│   │   ├── calculate.php
│   │   └── 📂 assets/
│   │       ├── 📂 css/
│   │       │   └── port.css     # Styles dédiés module
│   │       └── 📂 js/
│   │           └── port.js      # JavaScript interactif
│   ├── 📂 admin/                # Module administration
│   │   ├── index.php
│   │   ├── scanner.php          # Diagnostic automatique
│   │   └── 📂 assets/
│   ├── 📂 user/                 # Module utilisateur
│   │   └── index.php
│   ├── 📂 auth/                 # Module authentification
│   │   ├── login.php
│   │   └── logout.php
│   └── 📂 assets/               # Assets globaux
│       ├── 📂 css/
│       │   ├── portal.css       # CSS principal portail ✅
│       │   ├── header.css       # Header global ✅
│       │   ├── footer.css       # Footer global ✅
│       │   └── components.css   # Composants globaux ✅
│       └── 📂 js/
├── 📂 templates/                 # Templates HTML
│   ├── header.php               # En-tête avec auth obligatoire
│   └── footer.php               # Pied de page avec version
├── 📂 storage/                   # Données temporaires
│   ├── 📂 logs/                 # Fichiers de logs
│   └── 📂 cache/module/         # Cache par module
└── 📂 sql/                       # Scripts base de données
    └── structure.sql            # Tables auth + transport
```

### 🎯 Principes architecturaux

- **Séparation stricte** : HTML, CSS, JS dans des fichiers dédiés
- **Architecture modulaire** : modules indépendants dans `/public/nomdumodule/`
- **BDD unique partagée** entre tous les modules
- **Autoloading** des classes selon convention `nom_fichier.php`
- **Sessions PHP simples** pour l'authentification
- **Convention de nommage** : `minuscules_avec_underscores.php`

---

## 🛠️ Technologies

### Backend
- **PHP 8.1+** avec POO moderne
- **MySQL/MariaDB** pour les données
- **Sessions PHP natives** pour l'authentification
- **PDO** pour les accès base de données sécurisés

### Frontend
- **HTML5** sémantique et accessible
- **CSS3 moderne** avec variables CSS et Flexbox/Grid
- **JavaScript ES6+** natif (sans framework)
- **Design responsive** mobile-first

### Infrastructure
- **Apache** avec mod_rewrite
- **Architecture modulaire** évolutive
- **Cache applicatif** par module
- **Logs centralisés** par type d'événement

### Sécurité
- **Authentification obligatoire** sur toutes les pages
- **Protection CSRF** et XSS
- **Validation stricte** des entrées utilisateur
- **Headers de sécurité** configurés

---

## 📦 Installation

### Prérequis
- **PHP 8.1** ou supérieur
- **MySQL 5.7** ou **MariaDB 10.3+**
- **Apache** avec mod_rewrite activé
- **Composer** (optionnel pour les dépendances futures)

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-org/portail-guldagil.git
   cd portail-guldagil
   ```

2. **Configuration Apache**
   ```apache
   # Pointer DocumentRoot vers le dossier /public
   DocumentRoot "/path/to/portail-guldagil/public"
   
   # Ou créer un VirtualHost dédié
   <VirtualHost *:80>
       ServerName guldagil.local
       DocumentRoot "/path/to/portail-guldagil/public"
       DirectoryIndex index.php
   </VirtualHost>
   ```

3. **Configuration base de données**
   ```bash
   # Créer la base de données
   mysql -u root -p
   CREATE DATABASE guldagil_portail;
   
   # Importer la structure
   mysql -u root -p guldagil_portail < sql/structure.sql
   ```

4. **Configuration du portail**
   ```bash
   # Copier le fichier de configuration
   cp config/config.example.php config/config.php
   
   # Éditer la configuration
   nano config/config.php
   ```

5. **Permissions des dossiers**
   ```bash
   # Droits d'écriture sur les dossiers de cache et logs
   chmod -R 755 storage/
   chown -R www-data:www-data storage/
   ```

---

## ⚙️ Configuration

### Fichier principal : `/config/config.php`

```php
<?php
// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'guldagil_portail');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');

// Configuration portail
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', 'https://votre-domaine.com');
define('APP_ENV', 'production'); // development | production

// Sécurité
define('SESSION_TIMEOUT', 7200); // 2 heures par défaut
```

### Configuration des transporteurs

Les tarifs sont stockés en base de données dans les tables :
- `gul_xpo_rates` - Tarifs XPO Logistics
- `gul_heppner_rates` - Tarifs Heppner
- `gul_kn_rates` - Tarifs Kuehne+Nagel
- `gul_taxes_transporteurs` - Surcharges et options

---

## 🚀 Utilisation

### Accès au portail

1. **Page d'accueil** : `https://votre-domaine.com/`
2. **Connexion** : `https://votre-domaine.com/auth/login.php`
3. **Calculateur** : `https://votre-domaine.com/port/`
4. **Administration** : `https://votre-domaine.com/admin/`

### Utilisation du calculateur

1. **Saisir le département** de destination (ex: 75)
2. **Indiquer le poids** → sélection automatique du type d'envoi
3. **Choisir ADR** (marchandises dangereuses) Oui/Non
4. **Observer le calcul automatique** des frais
5. **Consulter l'encart de débogage** en bas à droite pour les détails

### Comptes par défaut

```bash
# Développeur (accès total)
Utilisateur : dev
Mot de passe : dev123

# Administrateur
Utilisateur : admin
Mot de passe : admin123

# Utilisateur standard
Utilisateur : user
Mot de passe : user123
```

---

## 🔧 Modules

### 📦 Port (Calculateur) - **Actif**
**Chemin :** `/public/port/`
- Calcul automatique des frais de transport
- Interface moderne avec progression intelligente
- Support multi-transporteurs
- Gestion ADR intégrée

### ⚙️ Admin - **Actif** 
**Chemin :** `/public/admin/`
- Dashboard d'administration complet
- Scanner de diagnostic automatique
- Gestion BDD en temps réel
- Configuration système

### 👤 User - **Actif**
**Chemin :** `/public/user/`
- Dashboard utilisateur personnalisé
- Profil et préférences
- Historique d'activité
- Modules accessibles selon le rôle

### 🔐 Auth - **Actif**
**Chemin :** `/public/auth/`
- Système d'authentification sécurisé
- Gestion des sessions
- Logout et timeouts automatiques

### 🆕 Modules en développement

- **⚠️ ADR** (Gestion marchandises dangereuses) - 15%
- **📋 EPI** (Équipements de protection) - 25%  
- **🔧 Outillages** (Gestion des outils) - 1%

---

## 🔐 Authentification

### Système de rôles

| Rôle | Permissions | Modules accessibles |
|------|-------------|-------------------|
| **dev** | Accès total + debug | Tous modules + diagnostic |
| **admin** | Administration complète | Admin, port, user, auth |
| **logistique** | Calculs + gestion transport | Port, user |
| **user** | Utilisation standard | Port, user |

### Sécurité

- **Authentification obligatoire** sur toutes les pages (sauf login)
- **Sessions sécurisées** avec timeout configurable
- **Protection CSRF** sur les formulaires
- **Validation stricte** des entrées utilisateur
- **Logs d'audit** pour toutes les actions sensibles

---

## 📊 Base de données

### Tables principales

#### Authentification
```sql
auth_users          # Utilisateurs du portail
auth_sessions       # Sessions actives
```

#### Transport et logistique
```sql
gul_xpo_rates           # Tarifs XPO Logistics
gul_heppner_rates       # Tarifs Heppner Transport  
gul_kn_rates            # Tarifs Kuehne+Nagel
gul_taxes_transporteurs # Surcharges et majorations
```

#### Système
```sql
system_logs         # Logs applicatifs
system_config       # Configuration globale
```

### Scripts SQL

- `sql/structure.sql` - Structure complète des tables
- `sql/data-sample.sql` - Données d'exemple pour les tests
- `sql/migration-*.sql` - Scripts de migration entre versions

---

## 🎨 Assets & Design

### CSS Architecture

#### CSS Globaux (obligatoires) ✅
```html
<link rel="stylesheet" href="/assets/css/portal.css">     <!-- CSS principal -->
<link rel="stylesheet" href="/assets/css/header.css">     <!-- Header global -->
<link rel="stylesheet" href="/assets/css/footer.css">     <!-- Footer global -->
<link rel="stylesheet" href="/assets/css/components.css"> <!-- Composants -->
```

#### CSS par module
```html
<!-- CSS spécifique au module port -->
<link rel="stylesheet" href="/port/assets/css/port.css">
```

### Design System

- **Palette principale** : Thème bleu professionnel (secteur traitement eau)
- **Responsive design** : Mobile-first approach
- **Variables CSS** pour cohérence visuelle
- **Components réutilisables** dans components.css

### JavaScript

- **Vanilla JavaScript ES6+** (pas de jQuery)
- **Modules séparés** par fonctionnalité
- **API async/await** pour les appels AJAX
- **Progressive enhancement**

---

## 🔍 Diagnostic & Maintenance

### Scanner automatique

**Accès :** `/admin/scanner.php` (admin/dev uniquement)

#### Types de scan
- **⚡ Rapide** (2-5s) : Vérifications essentielles
- **🔬 Approfondi** (10-30s) : Analyse complète + modules + logs

#### Éléments vérifiés
- ✅ Structure des dossiers et permissions
- ✅ Fichiers critiques et configuration
- ✅ Syntaxe PHP (détection erreurs)
- ✅ Connexion base de données
- ✅ Assets CSS/JS (existence et validité)
- ✅ Modules et leurs dépendances
- ✅ Logs et erreurs système

### Logs et monitoring

```bash
# Logs par catégorie
storage/logs/error.log      # Erreurs système
storage/logs/auth.log       # Authentification
storage/logs/transport.log  # Calculs transport
storage/logs/admin.log      # Actions admin
```

### Outils de diagnostic

- **Vérificateur de structure** : `public/port/verify_and_fix.php`
- **Diagnostic 500** : `public/diagnostic_500.php`
- **Scanner global** : `public/admin/scanner.php`

---

## 📚 Documentation

### Documentation technique

- `public/admin/scanner-about.md` - Guide du scanner
- `public/admin/about.md` - Structure module admin
- `templates/README.md` - Documentation templates
- `config/README.md` - Guide configuration

### En-têtes de fichiers

**Format obligatoire** pour tous les fichiers PHP :
```php
<?php
/**
 * Titre: Description précise du fichier
 * Chemin: /chemin/complet/vers/fichier.php
 * Version: 0.5 beta + build auto
 */
```

### Conventions de code

- **Nommage** : `minuscules_avec_underscores.php`
- **Pas d'espaces** ni caractères spéciaux dans les noms
- **Cohérence absolue** dans tout le projet
- **Commentaires** en français pour la documentation métier

---

## 🔄 Déploiement & Versions

### Versioning

- **Version actuelle** : `0.5 beta + build auto`
- **Build automatique** : Format `YYYYMMDDHHMMSS`
- **Passage v1.0** : Sur décision du responsable projet

### Critères version 1.0

- ✅ Validation complète des fonctionnalités
- ✅ Tests exhaustifs de stabilité
- ✅ Documentation complète utilisateur
- ✅ Conformité totale aux instructions projet
- ✅ **Décision du responsable projet**

### Process de mise à jour

1. **Sauvegarde** complète (fichiers + BDD)
2. **Tests** sur environnement de staging
3. **Migration** des données si nécessaire
4. **Vérification** avec scanner automatique
5. **Mise en production**

---

## 🤝 Contribution

### Workflow de développement

1. **Analyser l'existant** avant tout changement
2. **Vérifier compatibilité** avec le code actuel
3. **Proposer migration** si restructuration nécessaire
4. **Tester** avec scanner automatique
5. **Documenter** toutes les modifications

### Règles de contribution

- **TOUJOURS** vérifier l'existence d'un fichier avant création
- **Respecter** les conventions de nommage strictement
- **Éviter absolument** les doublons dans l'arborescence
- **Préserver** les fonctionnalités existantes
- **Tester** avant de valider

### Standards qualité

- ✅ **Zéro doublon** dans l'arborescence
- ✅ **Conventions respectées** à 100%
- ✅ **Architecture modulaire** claire
- ✅ **Performance** optimale
- ✅ **Sécurité** renforcée

---

## 📞 Support & Contact

### Informations projet

- **Entreprise** : Guldagil
- **Secteur** : Traitement de l'eau et solutions industrielles
- **Développeur** : Jean-Thomas RUNSER
- **Version** : 0.5 beta + build auto

### Support technique

- **Issues GitHub** : [Créer un ticket](https://github.com/votre-org/portail-guldagil/issues)
- **Documentation** : Consultez le dossier `/docs`
- **Scanner diagnostic** : `/admin/scanner.php` pour diagnostiquer les problèmes

---

## 📄 License

**Proprietary License** - Tous droits réservés Guldagil © 2024

Ce logiciel est la propriété exclusive de Guldagil. Toute reproduction, distribution ou modification non autorisée est strictement interdite.

---

<div align="center">

**🚀 Portail Guldagil** - *Solutions professionnelles pour l'achats et la logistique*

[![Version](https://img.shields.io/badge/Version-0.5_beta-orange)](CHANGELOG.md)
[![Build](https://img.shields.io/badge/Build-Auto-green)](config/version.php)
[![Status](https://img.shields.io/badge/Status-En_développement-blue)]()

*Développé avec ❤️ pour l'efficacité opérationnelle*

</div>