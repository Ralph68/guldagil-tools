<?php
/**
 * Titre: Script de test et diagnostic des assets - CORRIGÉ
 * Chemin: /test_assets_corrected.php (supprimer après tests)
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Charger config si disponible
if (file_exists(ROOT_PATH . '/config/config.php')) {
    try {
        require_once ROOT_PATH . '/config/config.php';
    } catch (Exception $e) {
        // Config non chargée, continuer avec valeurs par défaut
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔍 Test Assets Corrigé - Portail Guldagil</title>
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
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        .file-size { font-size: 0.875rem; color: #718096; }
        .debug-info { background: #edf2f7; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.875rem; margin: 1rem 0; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnostic Assets CORRIGÉ - Portail Guldagil</h1>";

// Debug ROOT_PATH
echo "<div class='debug-info'>";
echo "<strong>ROOT_PATH:</strong> " . ROOT_PATH . "<br>";
echo "<strong>Fichier actuel:</strong> " . __FILE__ . "<br>";
echo "<strong>Structure détectée:</strong><br>";
echo "- /public/assets/css/ : " . (is_dir(ROOT_PATH . '/public/assets/css/') ? '✅' : '❌') . "<br>";
echo "- /public/admin/assets/css/ : " . (is_dir(ROOT_PATH . '/public/admin/assets/css/') ? '✅' : '❌') . "<br>";
echo "</div>";

// ===========================================
// SUMMARY SECTION - CORRIGÉ
// ===========================================
echo "<div class='section summary'>";
echo "<h2>📊 Résumé Exécutif</h2>";

// Modules selon la vraie structure
$modules = ['admin', 'user', 'auth', 'port', 'adr', 'materiel', 'qualite', 'epi'];

// CSS critiques - CHEMIN CORRIGÉ
$criticalAssets = [
    'portal.css',
    'header.css', 
    'footer.css',
    'components.css'
];

$criticalOk = 0;
$moduleAssetsOk = 0;
$totalErrors = 0;

// Vérification CSS critiques - CORRIGÉE
foreach ($criticalAssets as $asset) {
    $fullPath = ROOT_PATH . "/public/assets/css/{$asset}";
    if (file_exists($fullPath)) $criticalOk++;
    else $totalErrors++;
}

// Vérification modules - CORRIGÉE
foreach ($modules as $module) {
    $cssPath = ROOT_PATH . "/public/{$module}/assets/css/{$module}.css";
    if (file_exists($cssPath)) $moduleAssetsOk++;
}

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;'>";
echo "<div><strong>CSS Critiques:</strong><br><span class='" . ($criticalOk === 4 ? 'status-ok' : 'status-error') . "'>{$criticalOk}/4 OK</span></div>";
echo "<div><strong>Assets Modules:</strong><br><span class='status-ok'>{$moduleAssetsOk}/" . count($modules) . " trouvés</span></div>";
echo "<div><strong>Erreurs Totales:</strong><br><span class='" . ($totalErrors === 0 ? 'status-ok' : 'status-error') . "'>{$totalErrors}</span></div>";
echo "<div><strong>Statut Global:</strong><br><span class='" . ($totalErrors === 0 && $criticalOk === 4 ? 'status-ok' : 'status-warning') . "'>" . ($totalErrors === 0 && $criticalOk === 4 ? 'EXCELLENT' : 'À CORRIGER') . "</span></div>";
echo "</div>";
echo "</div>";

// ===========================================
// CSS CRITIQUES - CORRIGÉ
// ===========================================
echo "<div class='section'>";
echo "<h2>🚨 CSS Critiques dans /public/assets/css/</h2>";

echo "<table>";
echo "<thead><tr><th>Fichier</th><th>Statut</th><th>Taille</th><th>Modifié</th></tr></thead><tbody>";

foreach ($criticalAssets as $asset) {
    $fullPath = ROOT_PATH . "/public/assets/css/{$asset}";
    $webPath = "/assets/css/{$asset}";
    
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
// MODULES ASSETS - CORRIGÉ SELON VRAIE STRUCTURE
// ===========================================
echo "<div class='section'>";
echo "<h2>🧩 Assets des Modules (Structure Réelle)</h2>";

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
        echo "<code style='font-size: 0.75rem; color: #38a169;'>{$webCssPath}</code>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "</div>";
echo "</div>";

// ===========================================
// STRUCTURE DÉTAILLÉE
// ===========================================
echo "<div class='section'>";
echo "<h2>📋 Structure Détaillée Détectée</h2>";

$structureChecks = [
    'CSS Globaux' => [
        '/public/assets/css/portal.css',
        '/public/assets/css/header.css',
        '/public/assets/css/footer.css',
        '/public/assets/css/components.css'
    ],
    'CSS Modules' => [
        '/public/admin/assets/css/admin.css',
        '/public/user/assets/css/user.css',
        '/public/auth/assets/css/login.css',
        '/public/port/assets/css/port.css',
        '/public/adr/assets/css/adr.css',
        '/public/materiel/assets/css/materiel.css',
        '/public/qualite/assets/css/qualite.css',
        '/public/epi/assets/css/epi.css'
    ],
    'JavaScript Modules' => [
        '/public/admin/assets/js/admin.js',
        '/public/user/assets/js/user.js',
        '/public/auth/assets/js/login.js',
        '/public/port/assets/js/port.js',
        '/public/adr/assets/js/adr.js',
        '/public/materiel/assets/js/materiel.js',
        '/public/qualite/assets/js/qualite.js',
        '/public/epi/assets/js/epi.js'
    ]
];

foreach ($structureChecks as $category => $files) {
    echo "<h3>{$category}</h3>";
    echo "<table>";
    echo "<thead><tr><th>Fichier</th><th>Statut</th><th>Taille</th></tr></thead><tbody>";
    
    foreach ($files as $file) {
        $fullPath = ROOT_PATH . $file;
        $exists = file_exists($fullPath);
        
        echo "<tr>";
        echo "<td><span class='path'>{$file}</span></td>";
        echo "<td>" . ($exists ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td>";
        echo "<td>" . ($exists ? "<span class='file-size'>" . formatFileSize(filesize($fullPath)) . "</span>" : "-") . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
}

echo "</div>";

// ===========================================
// ANALYSE HEADER ACTUEL
// ===========================================
echo "<div class='section'>";
echo "<h2>🔧 Analyse du Header Actuel</h2>";

$headerPath = ROOT_PATH . '/templates/header.php';
if (file_exists($headerPath)) {
    echo "<p><span class='status-ok'>✅ Header trouvé</span></p>";
    
    $headerContent = file_get_contents($headerPath);
    
    // Recherche des chemins CSS dans le header
    preg_match_all('/href="([^"]*\.css[^"]*)"/', $headerContent, $cssMatches);
    preg_match_all('/src="([^"]*\.js[^"]*)"/', $headerContent, $jsMatches);
    
    echo "<h3>CSS chargés par le header:</h3>";
    if (!empty($cssMatches[1])) {
        echo "<ul>";
        foreach ($cssMatches[1] as $cssPath) {
            echo "<li><code>{$cssPath}</code></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun CSS trouvé dans le header</p>";
    }
    
    echo "<h3>JS chargés par le header:</h3>";
    if (!empty($jsMatches[1])) {
        echo "<ul>";
        foreach ($jsMatches[1] as $jsPath) {
            echo "<li><code>{$jsPath}</code></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun JS trouvé dans le header</p>";
    }
    
} else {
    echo "<p><span class='status-error'>❌ Header non trouvé</span></p>";
}

echo "</div>";

// ===========================================
// RECOMMANDATIONS
// ===========================================
echo "<div class='section'>";
echo "<h2>🎯 Recommandations basées sur la structure réelle</h2>";

echo "<h3>✅ Ce qui fonctionne déjà:</h3>";
echo "<ul>";
$workingModules = [];
foreach ($modules as $module) {
    $cssPath = ROOT_PATH . "/public/{$module}/assets/css/{$module}.css";
    if (file_exists($cssPath)) {
        $workingModules[] = $module;
        echo "<li>Module <strong>{$module}</strong> : CSS présent</li>";
    }
}
echo "</ul>";

echo "<h3>⚠️ À corriger/créer:</h3>";
echo "<ul>";
foreach ($modules as $module) {
    $cssPath = ROOT_PATH . "/public/{$module}/assets/css/{$module}.css";
    if (!file_exists($cssPath)) {
        echo "<li>Créer : <code>/public/{$module}/assets/css/{$module}.css</code></li>";
    }
    
    $jsPath = ROOT_PATH . "/public/{$module}/assets/js/{$module}.js";
    if (!file_exists($jsPath)) {
        echo "<li>Créer : <code>/public/{$module}/assets/js/{$module}.js</code></li>";
    }
}
echo "</ul>";

echo "<h3>🔧 Header à corriger:</h3>";
echo "<p>Le header dans <code>templates/header.php</code> doit utiliser ces chemins :</p>";
echo "<ul>";
echo "<li>CSS critiques : <code>/assets/css/{fichier}.css</code></li>";
echo "<li>CSS modules : <code>/{module}/assets/css/{module}.css</code></li>";
echo "<li>JS modules : <code>/{module}/assets/js/{module}.js</code></li>";
echo "</ul>";

echo "</div>";

// ===========================================
// FOOTER
// ===========================================
echo "<div class='section'>";
echo "<p style='text-align: center; color: #718096; font-size: 0.875rem;'>";
echo "🔍 Diagnostic corrigé généré le " . date('d/m/Y à H:i:s') . "<br>";
echo "<strong>⚠️ Supprimez ce fichier après diagnostic</strong>";
echo "</p>";
echo "</div>";

echo "</div></body></html>";

// ===========================================
// FONCTION UTILITAIRE
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