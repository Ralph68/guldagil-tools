<?php
/**
 * Titre: Script de test et diagnostic des assets
 * Chemin: /test_assets.php (à la racine, à supprimer après tests)
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Charger config si disponible
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}

// Activer l'affichage des erreurs pour les tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔍 Test Assets - Portail Guldagil</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 2rem; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { background: white; margin: 1rem 0; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { margin: 0 0 1rem 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .card { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; }
        .status-ok { color: #38a169; font-weight: bold; }
        .status-error { color: #e53e3e; font-weight: bold; }
        .status-warning { color: #d69e2e; font-weight: bold; }
        .path { font-family: 'Courier New', monospace; background: #edf2f7; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
        .summary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #4299e1; color: white; text-decoration: none; border-radius: 4px; margin: 0.25rem; }
        .btn:hover { background: #3182ce; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        .file-size { font-size: 0.875rem; color: #718096; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnostic complet des Assets - Portail Guldagil</h1>";

// ===========================================
// SUMMARY SECTION
// ===========================================
echo "<div class='section summary'>";
echo "<h2>📊 Résumé Exécutif</h2>";

$modules = ['admin', 'user', 'auth', 'port', 'materiel', 'qualite'];
$criticalAssets = [
    '/public/assets/css/portal.css',
    '/public/assets/css/header.css', 
    '/public/assets/css/footer.css',
    '/public/assets/css/components.css'
];

$criticalOk = 0;
$moduleAssetsOk = 0;
$totalErrors = 0;

// Vérification rapide
foreach ($criticalAssets as $asset) {
    if (file_exists(ROOT_PATH . $asset)) $criticalOk++;
    else $totalErrors++;
}

foreach ($modules as $module) {
    $cssPath = ROOT_PATH . "/public/{$module}/assets/css/{$module}.css";
    if (file_exists($cssPath)) $moduleAssetsOk++;
}

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;'>";
echo "<div><strong>CSS Critiques:</strong><br><span class='" . ($criticalOk === 4 ? 'status-ok' : 'status-error') . "'>{$criticalOk}/4 OK</span></div>";
echo "<div><strong>Assets Modules:</strong><br><span class='status-ok'>{$moduleAssetsOk}/{count($modules)} trouvés</span></div>";
echo "<div><strong>Erreurs Totales:</strong><br><span class='" . ($totalErrors === 0 ? 'status-ok' : 'status-error') . "'>{$totalErrors}</span></div>";
echo "<div><strong>Statut Global:</strong><br><span class='" . ($totalErrors === 0 && $criticalOk === 4 ? 'status-ok' : 'status-warning') . "'>" . ($totalErrors === 0 && $criticalOk === 4 ? 'EXCELLENT' : 'À CORRIGER') . "</span></div>";
echo "</div>";
echo "</div>";

// ===========================================
// CSS CRITIQUES
// ===========================================
echo "<div class='section'>";
echo "<h2>🚨 CSS Critiques (OBLIGATOIRES)</h2>";
echo "<p>Ces fichiers <strong>NE DOIVENT JAMAIS</strong> être modifiés ou déplacés :</p>";

echo "<table>";
echo "<thead><tr><th>Fichier</th><th>Statut</th><th>Taille</th><th>Modifié</th></tr></thead><tbody>";

foreach ($criticalAssets as $asset) {
    $fullPath = ROOT_PATH . $asset;
    $webPath = str_replace('/public', '', $asset);
    
    if (file_exists($fullPath)) {
        $size = formatFileSize(filesize($fullPath));
        $modified = date('d/m/Y H:i', filemtime($fullPath));
        echo "<tr>";
        echo "<td><span class='path'>{$webPath}</span></td>";
        echo "<td><span class='status-ok'>✅ EXISTE</span></td>";
        echo "<td class='file-size'>{$size}</td>";
        echo "<td class='file-size'>{$modified}</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td><span class='path'>{$webPath}</span></td>";
        echo "<td><span class='status-error'>❌ MANQUANT</span></td>";
        echo "<td class='file-size'>-</td>";
        echo "<td class='file-size'>-</td>";
        echo "</tr>";
    }
}
echo "</tbody></table>";
echo "</div>";

// ===========================================
// MODULES ASSETS
// ===========================================
echo "<div class='section'>";
echo "<h2>🧩 Assets des Modules</h2>";
echo "<p>Diagnostic de la nouvelle architecture <code>/public/module/assets/</code> :</p>";

echo "<div class='grid'>";

foreach ($modules as $module) {
    echo "<div class='card'>";
    echo "<h3>📦 Module: {$module}</h3>";
    
    $modulePath = ROOT_PATH . "/public/{$module}";
    $assetsPath = "{$modulePath}/assets";
    $cssPath = "{$assetsPath}/css/{$module}.css";
    $jsPath = "{$assetsPath}/js/{$module}.js";
    
    // Vérifications
    $moduleExists = is_dir($modulePath);
    $assetsExists = is_dir($assetsPath);
    $cssExists = file_exists($cssPath);
    $jsExists = file_exists($jsPath);
    
    echo "<div style='margin: 0.5rem 0;'>";
    echo "<strong>📁 Dossier:</strong> " . ($moduleExists ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "<br>";
    echo "<strong>📂 Assets:</strong> " . ($assetsExists ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "<br>";
    echo "<strong>🎨 CSS:</strong> " . ($cssExists ? "<span class='status-ok'>✅</span>" : "<span class='status-warning'>⚠️</span>") . "<br>";
    echo "<strong>⚡ JS:</strong> " . ($jsExists ? "<span class='status-ok'>✅</span>" : "<span class='status-warning'>⚠️</span>") . "<br>";
    echo "</div>";
    
    if ($cssExists) {
        $size = formatFileSize(filesize($cssPath));
        echo "<div class='file-size'>CSS: {$size}</div>";
    }
    
    if ($jsExists) {
        $size = formatFileSize(filesize($jsPath));
        echo "<div class='file-size'>JS: {$size}</div>";
    }
    
    // Chemin web pour le CSS
    if ($cssExists) {
        $webCssPath = "/{$module}/assets/css/{$module}.css";
        echo "<div style='margin-top: 0.5rem;'>";
        echo "<code style='font-size: 0.75rem;'>{$webCssPath}</code>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "</div>";
echo "</div>";

// ===========================================
// TEST ASSETMANAGER
// ===========================================
echo "<div class='section'>";
echo "<h2>🔧 Test AssetManager</h2>";

try {
    // Tenter de charger AssetManager
    if (class_exists('AssetManager')) {
        echo "<p><span class='status-ok'>✅ AssetManager disponible</span></p>";
        
        $assetManager = AssetManager::getInstance();
        
        // Tester chaque module
        echo "<h3>Test de chargement par module:</h3>";
        echo "<table>";
        echo "<thead><tr><th>Module</th><th>CSS Détectés</th><th>JS Détectés</th><th>Statut</th></tr></thead><tbody>";
        
        foreach ($modules as $module) {
            $assetManager->reset(); // Reset pour test propre
            $assetManager->loadModuleAssets($module, true, true);
            
            $debug = $assetManager->debug();
            $cssCount = $debug['css_count'] - 5; // Enlever les 5 CSS critiques
            $jsCount = $debug['js_count'];
            
            echo "<tr>";
            echo "<td><strong>{$module}</strong></td>";
            echo "<td>{$cssCount} CSS</td>";
            echo "<td>{$jsCount} JS</td>";
            echo "<td>" . ($cssCount > 0 || $jsCount > 0 ? "<span class='status-ok'>✅ OK</span>" : "<span class='status-warning'>⚠️ Aucun asset</span>") . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        // Debug complet pour admin
        echo "<h3>Debug AssetManager (module admin):</h3>";
        $assetManager->reset();
        $assetManager->loadModuleAssets('admin', true, true);
        $debug = $assetManager->debug();
        
        echo "<pre style='background: #f7fafc; padding: 1rem; border-radius: 4px; font-size: 0.875rem;'>";
        echo json_encode($debug, JSON_PRETTY_PRINT);
        echo "</pre>";
        
    } else {
        echo "<p><span class='status-warning'>⚠️ AssetManager non chargé</span></p>";
        echo "<p>Pour activer l'AssetManager, ajoutez ceci à <code>config/config.php</code> :</p>";
        echo "<pre style='background: #f7fafc; padding: 1rem; border-radius: 4px;'>
spl_autoload_register(function (\$class) {
    \$coreClasses = [
        'AssetManager' => ROOT_PATH . '/core/assets/AssetManager.php',
    ];
    
    if (isset(\$coreClasses[\$class])) {
        require_once \$coreClasses[\$class];
    }
});
</pre>";
    }
    
} catch (Exception $e) {
    echo "<p><span class='status-error'>❌ Erreur AssetManager: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}

echo "</div>";

// ===========================================
// ANCIEN SYSTÈME VS NOUVEAU  
// ===========================================
echo "<div class='section'>";
echo "<h2>🔄 Comparaison Ancien vs Nouveau Système</h2>";

echo "<div class='grid'>";

// Ancien système
echo "<div class='card'>";
echo "<h3>❌ Ancien Système (Problématique)</h3>";
echo "<ul>";
echo "<li><strong>Logique dispersée</strong> dans header.php</li>";
echo "<li><strong>Chemins hardcodés</strong> multiples</li>";
echo "<li><strong>File_exists répétitifs</strong> sans cache</li>";
echo "<li><strong>Fallbacks manuels</strong> dans chaque template</li>";
echo "<li><strong>Debug difficile</strong> des assets manqués</li>";
echo "</ul>";

$problematicPaths = [
    'ROOT_PATH . $new_css_path', // ❌ Chemin cassé
    'ROOT_PATH . "/public" . $css_path', // ❌ Logique complexe
];

echo "<h4>Chemins problématiques détectés:</h4>";
foreach ($problematicPaths as $path) {
    echo "<div class='path' style='color: #e53e3e; margin: 0.25rem 0;'>{$path}</div>";
}
echo "</div>";

// Nouveau système
echo "<div class='card'>";
echo "<h3>✅ Nouveau Système (AssetManager)</h3>";
echo "<ul>";
echo "<li><strong>Centralisation</strong> dans une classe</li>";
echo "<li><strong>Fallbacks intelligents</strong> automatiques</li>";
echo "<li><strong>Cache file_exists</strong> intégré</li>";
echo "<li><strong>Debug complet</strong> des assets</li>";
echo "<li><strong>Configuration</strong> externalisable</li>";
echo "</ul>";

$newPaths = [
    'AssetManager::getInstance()',
    'loadModuleAssets($module)',
    'renderCss() / renderJs()',
];

echo "<h4>API simplifiée:</h4>";
foreach ($newPaths as $path) {
    echo "<div class='path' style='color: #38a169; margin: 0.25rem 0;'>{$path}</div>";
}
echo "</div>";

echo "</div>";
echo "</div>";

// ===========================================
// ACTIONS RECOMMANDÉES
// ===========================================
echo "<div class='section'>";
echo "<h2>🎯 Actions Recommandées</h2>";

$actions = [
    'immediate' => [
        'title' => '🚨 Actions Immédiates (Fix rapide)',
        'items' => [
            'Corriger le chemin ligne ~45 dans templates/header.php',
            'Ajouter "/public" devant {$new_css_path} dans file_exists()',
            'Tester tous les modules après correction',
            'Vérifier dans navigateur que CSS se chargent'
        ]
    ],
    'migration' => [
        'title' => '🔄 Migration AssetManager (Recommandée)',
        'items' => [
            'Créer /core/assets/AssetManager.php',
            'Modifier config/config.php pour autoload',
            'Remplacer templates/header.php par version AssetManager',
            'Tester exhaustivement tous les modules',
            'Documenter les changements'
        ]
    ],
    'optimization' => [
        'title' => '⚡ Optimisations Futures',
        'items' => [
            'Configuration externalisée des assets',
            'Minification automatique CSS/JS',
            'Cache avancé avec invalidation',
            'CDN pour assets statiques',
            'Lazy loading des assets non critiques'
        ]
    ]
];

foreach ($actions as $category => $section) {
    echo "<h3>{$section['title']}</h3>";
    echo "<ul>";
    foreach ($section['items'] as $item) {
        echo "<li>{$item}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// ===========================================
// FOOTER
// ===========================================
echo "<div class='section'>";
echo "<h2>🔗 Liens Utiles</h2>";
echo "<div>";
echo "<a href='/admin/scanner.php' class='btn'>🔍 Scanner d'erreurs</a>";
echo "<a href='/admin/' class='btn'>🔧 Administration</a>";
echo "<a href='/' class='btn'>🏠 Retour Portail</a>";
echo "</div>";

echo "<hr style='margin: 2rem 0;'>";
echo "<p style='text-align: center; color: #718096; font-size: 0.875rem;'>";
echo "🔍 Diagnostic généré le " . date('d/m/Y à H:i:s') . " | Build: " . (defined('BUILD_NUMBER') ? BUILD_NUMBER : 'N/A');
echo "<br><strong>⚠️ Supprimez ce fichier après diagnostic</strong>";
echo "</p>";

echo "</div>";

echo "</div></body></html>";

// ===========================================
// FONCTIONS UTILITAIRES
// ===========================================

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}