<?php
/**
 * Diagnostic Assets - Fix ROOT_PATH
 */

// Fix ROOT_PATH - détection intelligente
if (!defined('ROOT_PATH')) {
    // Si on est dans /public/, remonter d'un niveau
    if (basename(__DIR__) === 'public') {
        define('ROOT_PATH', dirname(__DIR__));
    } else {
        define('ROOT_PATH', __DIR__);
    }
}

// Test de detection
$possibleRoots = [
    ROOT_PATH,
    dirname(ROOT_PATH),
    ROOT_PATH . '/..',
    __DIR__ . '/..',
    dirname(__FILE__) . '/..'
];

$actualRoot = null;
foreach ($possibleRoots as $testRoot) {
    $realPath = realpath($testRoot);
    if ($realPath && file_exists($realPath . '/public/assets/css/portal.css')) {
        $actualRoot = $realPath;
        break;
    }
}

if (!$actualRoot) {
    die("❌ Impossible de trouver la racine du projet");
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Assets Diagnostic</title>
<style>
body{font-family:system-ui;margin:2rem;background:#f8fafc}
.ok{color:#059669;font-weight:bold}
.error{color:#dc2626;font-weight:bold}
.section{background:white;margin:1rem 0;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
table{width:100%;border-collapse:collapse;margin:1rem 0}
th,td{padding:0.75rem;text-align:left;border-bottom:1px solid #e5e7eb}
th{background:#f9fafb;font-weight:600}
.path{font-family:monospace;background:#f3f4f6;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.875rem}
</style>
</head><body>";

echo "<h1>🔍 Diagnostic Assets - ROOT_PATH: " . $actualRoot . "</h1>";

// CSS Critiques
echo "<div class='section'><h2>🚨 CSS Critiques</h2><table><thead><tr><th>Fichier</th><th>Statut</th><th>Taille</th></tr></thead><tbody>";

$criticals = ['portal.css', 'header.css', 'footer.css', 'components.css'];
$criticalOk = 0;

foreach ($criticals as $css) {
    $path = $actualRoot . "/public/assets/css/{$css}";
    $exists = file_exists($path);
    if ($exists) $criticalOk++;
    
    echo "<tr>";
    echo "<td><span class='path'>/assets/css/{$css}</span></td>";
    echo "<td><span class='" . ($exists ? 'ok' : 'error') . "'>" . ($exists ? '✅' : '❌') . "</span></td>";
    echo "<td>" . ($exists ? number_format(filesize($path)) . ' B' : '-') . "</td>";
    echo "</tr>";
}
echo "</tbody></table></div>";

// Modules
echo "<div class='section'><h2>🧩 Assets Modules</h2><table><thead><tr><th>Module</th><th>CSS</th><th>JS</th></tr></thead><tbody>";

$modules = ['admin', 'user', 'auth', 'port', 'adr', 'materiel', 'qualite', 'epi'];
$moduleOk = 0;

foreach ($modules as $module) {
    $cssPath = $actualRoot . "/public/{$module}/assets/css/{$module}.css";
    $jsPath = $actualRoot . "/public/{$module}/assets/js/{$module}.js";
    
    $cssExists = file_exists($cssPath);
    $jsExists = file_exists($jsPath);
    
    if ($cssExists) $moduleOk++;
    
    echo "<tr>";
    echo "<td><strong>{$module}</strong></td>";
    echo "<td><span class='" . ($cssExists ? 'ok' : 'error') . "'>" . ($cssExists ? '✅' : '❌') . "</span></td>";
    echo "<td><span class='" . ($jsExists ? 'ok' : 'error') . "'>" . ($jsExists ? '✅' : '❌') . "</span></td>";
    echo "</tr>";
}
echo "</tbody></table></div>";

// Résumé
echo "<div class='section' style='background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white'>";
echo "<h2>📊 Résumé</h2>";
echo "<p><strong>CSS Critiques:</strong> {$criticalOk}/4</p>";
echo "<p><strong>Assets Modules:</strong> {$moduleOk}/" . count($modules) . "</p>";
echo "<p><strong>ROOT_PATH:</strong> {$actualRoot}</p>";
echo "</div>";

// Debug paths
echo "<div class='section'><h2>🔧 Debug Paths</h2>";
echo "<p><strong>__DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>__FILE__:</strong> " . __FILE__ . "</p>";
echo "<p><strong>ROOT_PATH défini:</strong> " . ROOT_PATH . "</p>";
echo "<p><strong>ROOT_PATH réel:</strong> " . $actualRoot . "</p>";

$testPaths = [
    $actualRoot . "/public/assets/css/portal.css",
    $actualRoot . "/public/admin/assets/css/admin.css",
    $actualRoot . "/public/user/assets/css/user.css"
];

echo "<h3>Tests de fichiers:</h3><ul>";
foreach ($testPaths as $testPath) {
    $exists = file_exists($testPath);
    echo "<li><span class='" . ($exists ? 'ok' : 'error') . "'>" . ($exists ? '✅' : '❌') . "</span> " . $testPath . "</li>";
}
echo "</ul></div>";

echo "</body></html>";