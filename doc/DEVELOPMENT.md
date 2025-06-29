# Guide de développement - Portail Guldagil

## Structure du projet
/
├── config/           # Configuration centralisée
├── core/            # Classes système (Auth, Database, etc.)
├── modules/         # Modules applicatifs
├── public/          # Point d'entrée web
├── storage/         # Stockage (logs, cache, uploads)
├── templates/       # Templates réutilisables
└── docs/           # Documentation

## Conventions de code

### Nommage des fichiers
- **Tous les fichiers en minuscules** : `authmanager.php` ❌ → `auth_manager.php` ✅
- **Classes en PascalCase** : `AuthManager.php` ✅
- **Pas d'espaces ni caractères spéciaux** dans les noms

### En-tête obligatoire
```php
<?php
/**
 * Titre: Description du fichier
 * Chemin: /chemin/complet/vers/fichier.php
 * Version: 0.5 beta + build auto
 */
Standards de sécurité

Jamais d'identifiants en dur dans le code
Validation de toutes les entrées utilisateur
Échappement HTML avec htmlspecialchars()
Sessions sécurisées avec régénération d'ID
Authentification obligatoire pour les pages protégées

Workflow de développement

Toujours vérifier l'existant avant de créer
Sauvegarder avant modification importante
Tester authentification après chaque changement
Valider responsive sur mobile/desktop
Vérifier sécurité (pas d'identifiants exposés)

Tests requis
Avant chaque commit

 Authentification fonctionne
 CSS s'affiche correctement
 Responsive mobile/desktop
 Aucune erreur PHP visible
 Déconnexion nettoie la session

Avant release

 Tous les modules accessibles selon droits
 Performance acceptable
 Sécurité validée (scan identifiants)
 Documentation à jour


## ⚡ Optimisations performance

### Script de minification CSS (optionnel)

Créer `scripts/minify-css.php` :

```php
<?php
/**
 * Minification CSS pour production
 * Usage: php scripts/minify-css.php
 */

function minifyCSS($css) {
    // Supprimer commentaires
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Supprimer espaces inutiles
    $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace(['; ', ': ', ' {', '{ ', ' }', '} '], [';', ':', '{', '{', '}', '}'], $css);
    
    return trim($css);
}

$source = __DIR__ . '/../public/assets/css/portal.css';
$target = __DIR__ . '/../public/assets/css/portal.min.css';

if (file_exists($source)) {
    $css = file_get_contents($source);
    $minified = minifyCSS($css);
    
    file_put_contents($target, $minified);
    
    $originalSize = filesize($source);
    $minifiedSize = filesize($target);
    $savings = round((1 - $minifiedSize / $originalSize) * 100, 1);
    
    echo "✅ CSS minifié avec succès\n";
    echo "Original: " . number_format($originalSize) . " bytes\n";
    echo "Minifié: " . number_format($minifiedSize) . " bytes\n";
    echo "Économie: {$savings}%\n";
} else {
    echo "❌ Fichier source non trouvé: $source\n";
}
?>
🎯 Prochaines étapes
Priorité immédiate

Déployer les corrections de sécurité
Configurer la base de données d'authentification
Tester l'authentification complète
Supprimer les fichiers de diagnostic après validation

Développement futur

Module ADR - Gestion marchandises dangereuses
Module contrôle qualité - Tests et validations
Dashboard analytics - Statistiques d'utilisation
API REST - Intégration externe
PWA - Application mobile progressive

Maintenance continue

Monitoring des logs d'erreur
Sauvegarde régulière de la base
Mise à jour des dépendances
Audit sécurité trimestriel
