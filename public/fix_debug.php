<?php
/**
 * SCRIPT DE NETTOYAGE - Supprime toutes les définitions DEBUG
 * À exécuter UNE FOIS pour corriger le conflit
 * Chemin: /fix_debug.php (temporaire)
 */

echo "🔧 Nettoyage des conflits DEBUG...\n\n";

// Fichiers à nettoyer
$files_to_fix = [
    '/config/config.php',
    '/public/debug_auth.php',
    '/templates/header.php',
    '/public/adr/index.php',
    '/public/admin/audit.php'
];

$root_path = dirname(__DIR__); // Ajustez selon votre structure

foreach ($files_to_fix as $file_path) {
    $full_path = $root_path . $file_path;
    
    if (!file_exists($full_path)) {
        echo "⚠️ Fichier non trouvé: $file_path\n";
        continue;
    }
    
    echo "📝 Traitement: $file_path\n";
    
    // Lire le contenu
    $content = file_get_contents($full_path);
    $original_content = $content;
    
    // Patterns à rechercher et remplacer
    $patterns = [
        // define('DEBUG', quelque_chose);
        '/define\s*\(\s*[\'"]DEBUG[\'"]\s*,\s*[^)]+\)\s*;?/i',
        // $is_development = quelque_chose; (si lié à DEBUG)
        '/\$is_development\s*=\s*[^;]+;\s*\n?\s*define\s*\(\s*[\'"]DEBUG[\'"]/i',
        // Lignes complètes contenant define DEBUG
        '/^.*define\s*\(\s*[\'"]DEBUG[\'"].*$/m'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            echo "  ✂️ Suppression pattern DEBUG trouvé\n";
            $content = preg_replace($pattern, '// DEBUG removed - managed by debug.php', $content);
        }
    }
    
    // Cas spéciaux par fichier
    if (strpos($file_path, 'config.php') !== false) {
        // Dans config.php, supprimer aussi les lignes liées
        $content = preg_replace('/\$is_development\s*=\s*[^;]+;/', '// Environment detection moved to debug.php', $content);
        $content = preg_replace('/\/\/ ACTIVATION DEBUG FORCÉ.*?\n/', '', $content);
    }
    
    if (strpos($file_path, 'debug_auth.php') !== false) {
        // Dans debug_auth.php, remplacer define par vérification
        $content = str_replace(
            "define('ROOT_PATH', dirname(__DIR__));",
            "if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));"
        , $content);
    }
    
    // Sauvegarder si modifié
    if ($content !== $original_content) {
        // Backup
        copy($full_path, $full_path . '.backup.' . date('Ymd_His'));
        file_put_contents($full_path, $content);
        echo "  ✅ Fichier nettoyé et sauvegardé\n";
    } else {
        echo "  ℹ️ Aucune modification nécessaire\n";
    }
    
    echo "\n";
}

echo "🎯 Nettoyage terminé!\n\n";
echo "📋 Actions suivantes:\n";
echo "1. Remplacez /config/debug.php par la version corrigée\n";
echo "2. Testez votre application\n";
echo "3. Supprimez ce script fix_debug.php\n";
echo "4. Supprimez les fichiers .backup si tout fonctionne\n";
?>
