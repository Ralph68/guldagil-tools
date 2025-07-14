<?php
/**
 * Titre: Script de debug pour diagnostiquer les erreurs 404
 * Chemin: /public/debug_paths.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Debug Chemins</title>';
echo '<style>body{font-family:monospace;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} .info{color:blue;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ccc;padding:8px;text-align:left;}</style>';
echo '</head><body>';

echo '<h1>🔧 Debug des chemins - Portail Guldagil</h1>';
echo '<p>Diagnostic des erreurs 404 pour les fichiers CSS/JS</p>';

// 1. Déterminer ROOT_PATH
echo '<h2>1. 📍 Détection ROOT_PATH</h2>';
$current_file = __FILE__;
$current_dir = dirname($current_file);
echo "<p><strong>Fichier actuel:</strong> $current_file</p>";
echo "<p><strong>Répertoire actuel:</strong> $current_dir</p>";

// Tentatives ROOT_PATH
$possible_roots = [
    dirname($current_dir), // /public/../ = racine
    dirname(dirname($current_dir)), // /public/../../ pour sous-dossiers
    getcwd() // répertoire de travail
];

$root_path = null;
foreach ($possible_roots as $path) {
    if (file_exists($path . '/config/config.php')) {
        $root_path = $path;
        echo "<p class='success'>✅ ROOT_PATH trouvé: $root_path</p>";
        break;
    } else {
        echo "<p class='error'>❌ Testé: $path (config/config.php manquant)</p>";
    }
}

if (!$root_path) {
    echo "<p class='error'>❌ ERREUR CRITIQUE: ROOT_PATH introuvable</p>";
    exit;
}

define('ROOT_PATH', $root_path);

// 2. Vérifier structure des dossiers
echo '<h2>2. 📁 Structure des dossiers</h2>';
$dirs_to_check = [
    'config',
    'core',
    'public',
    'public/assets',
    'public/assets/css',
    'public/assets/js',
    'templates',
    'templates/assets',
    'templates/assets/css',
    'templates/assets/js'
];

echo '<table><tr><th>Dossier</th><th>Chemin complet</th><th>Existe</th><th>Permissions</th></tr>';
foreach ($dirs_to_check as $dir) {
    $full_path = $root_path . '/' . $dir;
    $exists = is_dir($full_path);
    $perms = $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A';
    $class = $exists ? 'success' : 'error';
    echo "<tr class='$class'><td>$dir</td><td>$full_path</td><td>" . ($exists ? 'Oui' : 'Non') . "</td><td>$perms</td></tr>";
}
echo '</table>';

// 3. Vérifier fichiers CSS/JS critiques
echo '<h2>3. 🎨 Fichiers CSS/JS critiques</h2>';
$files_to_check = [
    'public/assets/css/portal.css',
    'public/assets/css/components.css', 
    'templates/assets/css/header.css',
    'templates/assets/css/footer.css',
    'public/user/assets/css/user.css',
    'public/admin/assets/css/admin.css'
];

echo '<table><tr><th>Fichier</th><th>Chemin complet</th><th>Existe</th><th>Taille</th><th>Modifié</th></tr>';
foreach ($files_to_check as $file) {
    $full_path = $root_path . '/' . $file;
    $exists = file_exists($full_path);
    $size = $exists ? filesize($full_path) : 'N/A';
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($full_path)) : 'N/A';
    $class = $exists ? 'success' : 'error';
    echo "<tr class='$class'><td>$file</td><td>$full_path</td><td>" . ($exists ? 'Oui' : 'Non') . "</td><td>$size bytes</td><td>$modified</td></tr>";
}
echo '</table>';

// 4. Vérifier configuration Apache/.htaccess
echo '<h2>4. ⚙️ Configuration Apache</h2>';
$htaccess_files = [
    '.htaccess',
    'public/.htaccess'
];

foreach ($htaccess_files as $htaccess) {
    $htaccess_path = $root_path . '/' . $htaccess;
    if (file_exists($htaccess_path)) {
        echo "<p class='success'>✅ $htaccess trouvé</p>";
        $content = file_get_contents($htaccess_path);
        echo "<details><summary>Contenu $htaccess</summary><pre>" . htmlspecialchars($content) . "</pre></details>";
    } else {
        echo "<p class='error'>❌ $htaccess manquant</p>";
    }
}

// 5. Simuler les URLs comme le ferait le navigateur
echo '<h2>5. 🌐 Test des URLs</h2>';
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if ($script_dir === '/') $script_dir = '';

$urls_to_test = [
    '/public/assets/css/portal.css',
    '/templates/assets/css/header.css', 
    '/public/assets/css/components.css'
];

echo '<table><tr><th>URL relative</th><th>URL complète</th><th>Fichier physique</th><th>Test</th></tr>';
foreach ($urls_to_test as $url) {
    $full_url = $base_url . $script_dir . $url;
    $physical_file = $root_path . $url;
    $file_exists = file_exists($physical_file);
    
    echo "<tr>";
    echo "<td>$url</td>";
    echo "<td><a href='$full_url' target='_blank'>$full_url</a></td>";
    echo "<td>$physical_file</td>";
    echo "<td class='" . ($file_exists ? 'success' : 'error') . "'>" . ($file_exists ? '✅ Fichier existe' : '❌ Fichier manquant') . "</td>";
    echo "</tr>";
}
echo '</table>';

// 6. Variables d'environnement importantes  
echo '<h2>6. 🔧 Variables serveur</h2>';
$server_vars = [
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A', 
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'
];

echo '<table><tr><th>Variable</th><th>Valeur</th></tr>';
foreach ($server_vars as $var => $value) {
    echo "<tr><td>$var</td><td>$value</td></tr>";
}
echo '</table>';

// 7. Test de header.php si trouvé
echo '<h2>7. 📄 Test inclusion header.php</h2>';
$header_path = $root_path . '/templates/header.php';
if (file_exists($header_path)) {
    echo "<p class='success'>✅ templates/header.php trouvé</p>";
    
    // Analyser le contenu pour voir les chemins CSS
    $header_content = file_get_contents($header_path);
    preg_match_all('/href=["\']([^"\']*\.css[^"\']*)["\']/', $header_content, $css_matches);
    
    if (!empty($css_matches[1])) {
        echo "<p><strong>CSS référencés dans header.php:</strong></p><ul>";
        foreach ($css_matches[1] as $css_path) {
            $resolved_path = $root_path . '/' . ltrim($css_path, '/');
            $exists = file_exists($resolved_path);
            $class = $exists ? 'success' : 'error';
            echo "<li class='$class'>$css_path → $resolved_path " . ($exists ? '✅' : '❌') . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ templates/header.php manquant</p>";
}

// 8. Recommandations
echo '<h2>8. 💡 Recommandations</h2>';
echo '<div class="info">';
echo '<h3>Solutions possibles pour les erreurs 404:</h3>';
echo '<ol>';
echo '<li><strong>Vérifier .htaccess</strong>: Les règles de réécriture peuvent bloquer l\'accès aux assets statiques</li>';
echo '<li><strong>Permissions des fichiers</strong>: Vérifier que Apache peut lire les fichiers CSS/JS (chmod 644)</li>';
echo '<li><strong>Chemins dans les templates</strong>: Vérifier que les chemins dans header.php correspondent aux fichiers réels</li>';
echo '<li><strong>Document root Apache</strong>: Vérifier que le document root pointe vers /public/ ou la racine appropriée</li>';
echo '</ol>';

echo '<h3>Commandes de correction suggérées:</h3>';
echo '<pre>';
echo '# Corriger les permissions
find ' . $root_path . '/public/assets -type f -name "*.css" -exec chmod 644 {} \;
find ' . $root_path . '/public/assets -type f -name "*.js" -exec chmod 644 {} \;
find ' . $root_path . '/templates/assets -type f -name "*.css" -exec chmod 644 {} \;

# Vérifier configuration Apache
apache2ctl -t
apache2ctl -S
';
echo '</pre>';
echo '</div>';

echo '<hr><p><em>Debug terminé le ' . date('Y-m-d H:i:s') . '</em></p>';
echo '</body></html>';
?>
