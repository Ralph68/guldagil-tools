# 🔍 Scanner d'erreurs - Guide d'utilisation

**Chemin :** `/public/admin/scanner.php`  
**Version :** 0.5 beta + build auto  
**Accès :** Administrateurs et développeurs uniquement

## 🎯 Objectif

Le scanner diagnostique automatiquement les problèmes du portail et fournit des recommandations concrètes pour les résoudre.

## 🚀 Utilisation

### Accès rapide
```
http://votre-domaine/admin/scanner.php
```

### Types de scan

| Type | Durée | Description |
|------|-------|-------------|
| **⚡ Rapide** | ~2-5s | Vérifications essentielles |
| **🔬 Approfondi** | ~10-30s | Analyse complète + modules + logs |

## 📊 Sections analysées

### 1. 📁 **Structure des dossiers**
- Vérification des dossiers requis
- Permissions et droits d'accès
- Architecture modulaire

**✅ Ce qui est vérifié :**
- `/config/`, `/core/`, `/public/`, `/storage/`
- Modules admin, auth, user
- Dossiers assets CSS/JS

### 2. 📄 **Fichiers critiques**
- Existence et accessibilité
- Taille et permissions
- Fichiers de configuration

**✅ Fichiers vérifiés :**
- `config/config.php` - Configuration principale
- `templates/header.php` - Header global
- `public/index.php` - Point d'entrée
- `public/.htaccess` - Réécriture URLs
- CSS/JS principaux

### 3. 🐛 **Erreurs de syntaxe**
- Analyse PHP automatique
- Détection erreurs fatales
- Limite 20 fichiers pour performance

**🔧 Comment ça marche :**
```php
php -l fichier.php  // Vérification syntaxe
```

### 4. ⚙️ **Configuration**
- Test connexion base de données
- Vérification constantes requises
- Validation configuration modules

**✅ Tests effectués :**
- Connexion PDO à la BDD
- Constantes : `ROOT_PATH`, `BASE_URL`, `DB_HOST`
- Chargement des configs modules

### 5. 🎨 **Assets CSS/JS**
- Existence des fichiers
- Validation basique CSS
- Taille et cohérence

**🔧 Validation CSS :**
```php
// Test simple : nombre d'accolades
substr_count($css, '{') === substr_count($css, '}')
```

### 6. 🗄️ **Base de données**
- Tables auth obligatoires
- Comptage des enregistrements
- État de la connexion

**✅ Tables vérifiées :**
- `auth_users` - Utilisateurs
- `auth_sessions` - Sessions actives

### 7. 🔧 **Modules** *(scan approfondi)*
- État des modules installés
- Fichiers index.php
- Structure assets

### 8. 📊 **Logs** *(scan approfondi)*
- Analyse des erreurs récentes
- Taille des fichiers logs
- Détection problèmes fréquents

## 🎨 Interface

### États visuels
- 🟢 **OK** - Vert - Tout fonctionne
- 🟡 **Avertissement** - Orange - À surveiller
- 🔴 **Erreur** - Rouge - Action requise

### Statistiques globales
- Total vérifications
- Nombre d'erreurs/avertissements
- Durée du scan

## 🛠️ Actions disponibles

### Boutons d'action
| Action | Description |
|--------|-------------|
| 🔄 **Relancer** | Nouveau scan |
| 📥 **Télécharger** | Rapport JSON complet |
| 📊 **Voir logs** | Accès aux logs système |
| 🗑️ **Vider cache** | Nettoyage cache |

### Rapport téléchargeable
```json
{
  "timestamp": "2025-07-16T...",
  "scan_type": "deep",
  "duration": 1234.56,
  "summary": {
    "total_checks": 45,
    "errors": 2,
    "warnings": 3,
    "ok": 40
  },
  "results": { ... }
}
```

## 💡 Recommandations automatiques

### Erreurs critiques
- Fichiers manquants à créer
- Problèmes de base de données
- Erreurs de syntaxe PHP

### Avertissements
- Permissions à ajuster
- Fichiers CSS/JS à vérifier
- Modules incomplets

### Conseils généraux
- Fréquence de scan recommandée
- Maintenance préventive
- Sauvegarde avant corrections

## 🔧 Fonctionnement technique

### Algorithme principal
```php
function scanPortal($deep_scan = false) {
    return [
        'structure' => scanStructure(),     // Dossiers
        'files' => scanCriticalFiles(),     // Fichiers
        'syntax' => scanSyntaxErrors(),     // PHP
        'config' => scanConfiguration(),    // Config
        'css_js' => scanAssets(),          // Assets
        'database' => scanDatabase(),       // BDD
        // Mode approfondi
        'modules' => scanModules(),         // Si deep
        'logs' => scanLogs()               // Si deep
    ];
}
```

### Performance
- **Scan rapide :** 2-5 secondes
- **Scan approfondi :** 10-30 secondes
- **Limite :** 20 fichiers PHP max pour syntaxe
- **Cache :** Pas de cache, données temps réel

### Sécurité
- **Validation :** Toutes les entrées utilisateur
- **Échappement :** HTML dans l'affichage
- **Permissions :** Vérification lecture/écriture
- **Isolation :** Pas d'exécution de code externe

## 🚨 Cas d'erreur courants

### 1. Fichier de config manquant
**Erreur :** `config/config.php` introuvable  
**Solution :** Vérifier ROOT_PATH et créer le fichier

### 2. Base de données inaccessible
**Erreur :** Connexion PDO échoue  
**Solution :** Vérifier DB_HOST, DB_USER, DB_PASS dans config

### 3. Permissions insuffisantes
**Erreur :** Dossier non accessible en écriture  
**Solution :** `chmod 755` pour dossiers, `chmod 644` pour fichiers

### 4. Assets CSS cassés
**Erreur :** Accolades non équilibrées  
**Solution :** Vérifier syntaxe CSS dans l'éditeur

## 📈 Maintenance

### Utilisation recommandée
- **Quotidienne :** Scan rapide
- **Hebdomadaire :** Scan approfondi
- **Après modifs :** Scan rapide
- **En cas de bug :** Scan approfondi

### Intégration workflow
1. Développement → Scan rapide
2. Test → Scan approfondi  
3. Production → Scan programmé
4. Maintenance → Analyse des logs

## 🎯 Résultats attendus

### Portail sain
- ✅ 0 erreur critique
- ⚠️ Moins de 5 avertissements
- 📊 Tous modules fonctionnels
- 🗄️ BDD connectée et tables OK

### Actions si problèmes
1. **Prioriser** les erreurs critiques
2. **Corriger** une par une
3. **Re-scanner** après chaque correction
4. **Documenter** les changements

---

## 🔗 Liens utiles

- **Interface scanner :** `/admin/scanner.php`
- **Dashboard admin :** `/admin/`
- **Logs système :** `/admin/logs.php`
- **Documentation :** `/admin/scanner.php?help=1`

**💡 Astuce :** Ajoutez le scanner dans vos favoris pour un accès rapide !
