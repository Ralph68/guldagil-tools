<?php
/**
 * Titre: Diagnostic spécialisé .htaccess et Apache
 * Chemin: /public/diagnostic_htaccess.php
 * Version: 0.5 beta + build auto
 * 
 * Script pour diagnostiquer les problèmes .htaccess/Apache causant erreur 500
 */

// Configuration
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🔍 Diagnostic .htaccess et Apache</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a2e; color: #eee; }
        .container { max-width: 1200px; margin: 0 auto; background: #16213e; padding: 20px; border-radius: 10px; }
        .header { background: linear-gradient(45deg, #e74c3c, #c0392b); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .section { background: #0f1419; padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #3498db; }
        .success { color: #2ecc71; background: rgba(46, 204, 113, 0.1); padding: 8px; border-radius: 4px; margin: 5px 0; }
        .error { color: #e74c3c; background: rgba(231, 76, 60, 0.1); padding: 8px; border-radius: 4px; margin: 5px 0; }
        .warning { color: #f39c12; background: rgba(243, 156, 18, 0.1); padding: 8px; border-radius: 4px; margin: 5px 0; }
        .info { color: #3498db; background: rgba(52, 152, 219, 0.1); padding: 8px; border-radius: 4px; margin: 5px 0; }
        .critical { border-left: 5px solid #e74c3c; background: rgba(231, 76, 60, 0.15); }
        pre { background: #000; color: #0f0; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 0.9em; }
        .htaccess-block { background: #2c3e50; padding: 15px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #9b59b6; }
        .rule-analysis { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 10px 0; }
        .rule-item { background: #34495e; padding: 10px; border-radius: 4px; }
        .conflict { background: rgba(231, 76, 60, 0.2) !important; border: 2px solid #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #34495e; padding: 8px; text-align: left; }
        th { background: #2c3e50; color: #ecf0f1; }
        .test-url { background: #27ae60; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; display: inline-block; margin: 2px; }
        .test-url:hover { background: #219a52; }
    </style>
</head>
<body>
<div class="container">';

echo '<div class="header">
    <h1>🔍 DIAGNOSTIC .HTACCESS & APACHE</h1>
    <p>Analyse spécialisée des règles de réécriture et configuration Apache</p>
    <p><strong>Focus erreur 500 liée aux conflits .htaccess</strong></p>
</div>';

// Détection ROOT_PATH
$current_dir = dirname(__FILE__);
$root_path = null;
$possible_roots = [dirname($current_dir), dirname(dirname($current_dir))];

foreach ($possible_roots as $path) {
    if ($path && file_exists($path . '/config/config.php')) {
        $root_path = $path;
        break;
    }
}

if (!$root_path) {
    echo '<div class="error critical">❌ ROOT_PATH introuvable - Impossible de continuer</div>';
    exit;
}

define('ROOT_PATH', $root_path);

// =====================================
// 1. ANALYSE DES FICHIERS .HTACCESS
// =====================================

echo '<div class="section">
<h2>📄 1. Analyse des fichiers .htaccess</h2>';

$htaccess_files = [
    'Principal (racine)' => $root_path . '/.htaccess',
    'Public' => $root_path . '/public/.htaccess'
];

$htaccess_contents = [];
$htaccess_issues = [];

foreach ($htaccess_files as $name => $path) {
    echo "<h3>🔧 $name</h3>";
    
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $htaccess_contents[$name] = $content;
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        
        echo "<div class='success'>✅ Fichier trouvé: $path</div>";
        echo "<div class='info'>📊 Taille: $size bytes | Modifié: $modified</div>";
        
        // Analyse basique du contenu
        $lines = explode("\n", $content);
        $line_count = count($lines);
        $rewrite_rules = 0;
        $redirects = 0;
        $errors = [];
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            
            if (strpos($line, 'RewriteRule') !== false) $rewrite_rules++;
            if (strpos($line, 'Redirect') !== false) $redirects++;
            
            // Vérifications de syntaxe basiques
            if (strpos($line, 'RewriteRule') !== false && substr_count($line, ' ') < 2) {
                $errors[] = "Ligne " . ($line_num + 1) . ": RewriteRule malformée";
            }
            if (strpos($line, 'RewriteCond') !== false && substr_count($line, ' ') < 2) {
                $errors[] = "Ligne " . ($line_num + 1) . ": RewriteCond malformée";
            }
        }
        
        echo "<div class='info'>📈 $line_count lignes | $rewrite_rules RewriteRule | $redirects Redirects</div>";
        
        if (!empty($errors)) {
            echo "<div class='error'>⚠️ Erreurs de syntaxe détectées:</div>";
            foreach ($errors as $error) {
                echo "<div class='error'>• $error</div>";
                $htaccess_issues[] = $error;
            }
        }
        
        // Affichage du contenu
        echo "<div class='htaccess-block'>";
        echo "<strong>Contenu:</strong>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
        echo "</div>";
        
    } else {
        echo "<div class='warning'>⚠️ Fichier manquant: $path</div>";
    }
}

echo '</div>';

// =====================================
// 2. DÉTECTION DE CONFLITS
// =====================================

echo '<div class="section">
<h2>⚔️ 2. Détection de conflits entre .htaccess</h2>';

if (count($htaccess_contents) >= 2) {
    echo "<div class='info'>🔍 Analyse des conflits entre les deux fichiers .htaccess...</div>";
    
    $conflicts = [];
    
    // Extraire les règles RewriteRule de chaque fichier
    foreach ($htaccess_contents as $file_name => $content) {
        preg_match_all('/RewriteRule\s+(.+)$/m', $content, $matches);
        $rules[$file_name] = $matches[1] ?? [];
    }
    
    // Vérifier les patterns qui pourraient entrer en conflit
    $problematic_patterns = [
        '^(.*)$' => 'Catch-all trop large',
        '^admin' => 'Redirection admin',
        '^auth' => 'Redirection auth',
        '^public' => 'Redirection public',
        '^$' => 'Racine du site'
    ];
    
    foreach ($rules as $file_name => $file_rules) {
        foreach ($file_rules as $rule) {
            $parts = preg_split('/\s+/', trim($rule));
            $pattern = $parts[0] ?? '';
            
            foreach ($problematic_patterns as $prob_pattern => $description) {
                if (strpos($pattern, $prob_pattern) !== false) {
                    $conflicts[] = [
                        'file' => $file_name,
                        'pattern' => $pattern,
                        'description' => $description,
                        'full_rule' => $rule
                    ];
                }
            }
        }
    }
    
    if (!empty($conflicts)) {
        echo "<div class='warning'>⚠️ Conflits potentiels détectés:</div>";
        foreach ($conflicts as $conflict) {
            echo "<div class='conflict'>";
            echo "<strong>{$conflict['file']}:</strong> {$conflict['description']}<br>";
            echo "<code>{$conflict['full_rule']}</code>";
            echo "</div>";
        }
    } else {
        echo "<div class='success'>✅ Aucun conflit évident détecté</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Un seul fichier .htaccess trouvé - pas de conflit possible</div>";
}

echo '</div>';

// =====================================
// 3. TEST DES MODULES APACHE
// =====================================

echo '<div class="section">
<h2>🔧 3. Modules Apache requis</h2>';

$required_modules = [
    'mod_rewrite' => 'Réécriture d\'URLs',
    'mod_headers' => 'Manipulation des headers',
    'mod_expires' => 'Gestion du cache',
    'mod_deflate' => 'Compression GZIP',
    'mod_php' => 'Support PHP (ou mod_php7/8)'
];

// Test basique via function_exists ou apache_get_modules si disponible
if (function_exists('apache_get_modules')) {
    $loaded_modules = apache_get_modules();
    echo "<div class='success'>✅ apache_get_modules() disponible</div>";
    
    foreach ($required_modules as $module => $description) {
        if (in_array($module, $loaded_modules) || in_array(str_replace('mod_', '', $module), $loaded_modules)) {
            echo "<div class='success'>✅ $module - $description</div>";
        } else {
            echo "<div class='error'>❌ $module - $description (MANQUANT)</div>";
        }
    }
} else {
    echo "<div class='warning'>⚠️ apache_get_modules() non disponible - Tests alternatifs...</div>";
    
    // Tests alternatifs
    $rewrite_test = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'index.php') === false;
    echo "<div class='" . ($rewrite_test ? 'success' : 'warning') . "'>";
    echo ($rewrite_test ? '✅' : '⚠️') . " mod_rewrite: " . ($rewrite_test ? 'Probablement actif' : 'Non testé');
    echo "</div>";
    
    $php_test = function_exists('phpinfo');
    echo "<div class='" . ($php_test ? 'success' : 'error') . "'>";
    echo ($php_test ? '✅' : '❌') . " PHP: " . ($php_test ? 'Fonctionne' : 'Problème');
    echo "</div>";
}

echo '</div>';

// =====================================
// 4. TEST DES REDIRECTIONS
// =====================================

echo '<div class="section">
<h2>🌐 4. Test des redirections clés</h2>';

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$test_urls = [
    '/' => 'Page d\'accueil',
    '/admin' => 'Module admin',
    '/auth' => 'Module authentification',
    '/user' => 'Module utilisateur',
    '/public/assets/css/portal.css' => 'Asset CSS',
    '/diagnostic_500.php' => 'Script diagnostic',
    '/nonexistent' => 'Page inexistante'
];

echo "<div class='info'>🔗 Base URL détectée: $base_url</div>";
echo "<div class='info'>💡 Cliquez sur les liens pour tester manuellement:</div>";

echo "<div style='margin: 15px 0;'>";
foreach ($test_urls as $path => $description) {
    $full_url = $base_url . $path;
    echo "<a href='$full_url' target='_blank' class='test-url'>$path - $description</a>";
}
echo "</div>";

echo '</div>';

// =====================================
// 5. ANALYSE DES LOGS APACHE
// =====================================

echo '<div class="section">
<h2>📊 5. Logs Apache récents</h2>';

$log_paths = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/usr/local/apache/logs/error_log',
    '/var/log/apache2/access.log',
    '/var/log/httpd/access_log'
];

$found_logs = false;
foreach ($log_paths as $log_path) {
    if (file_exists($log_path) && is_readable($log_path)) {
        $found_logs = true;
        echo "<div class='success'>✅ Log trouvé: $log_path</div>";
        
        $lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $recent_lines = array_slice($lines, -10);
            echo "<div class='info'>📝 10 dernières entrées:</div>";
            echo "<pre>";
            foreach ($recent_lines as $line) {
                // Highlight des erreurs 500
                if (strpos($line, '500') !== false || strpos($line, 'Internal Server Error') !== false) {
                    echo "<span style='color: #e74c3c; font-weight: bold;'>" . htmlspecialchars($line) . "</span>\n";
                } elseif (strpos($line, 'htaccess') !== false || strpos($line, '.htaccess') !== false) {
                    echo "<span style='color: #f39c12; font-weight: bold;'>" . htmlspecialchars($line) . "</span>\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo "</pre>";
        }
    }
}

if (!$found_logs) {
    echo "<div class='warning'>⚠️ Aucun log Apache accessible</div>";
    echo "<div class='info'>💡 Logs possibles selon votre configuration:</div>";
    echo "<ul>";
    foreach ($log_paths as $path) {
        echo "<li>$path</li>";
    }
    echo "</ul>";
}

echo '</div>';

// =====================================
// 6. CONFIGURATION APACHE DÉTECTÉE
// =====================================

echo '<div class="section">
<h2>⚙️ 6. Configuration Apache détectée</h2>';

$server_info = [
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible',
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'Non disponible',
    'SERVER_ADMIN' => $_SERVER['SERVER_ADMIN'] ?? 'Non disponible',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'Non disponible',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Non disponible',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'Vide',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Non disponible'
];

echo "<table>";
echo "<tr><th>Variable</th><th>Valeur</th><th>Analyse</th></tr>";

foreach ($server_info as $var => $value) {
    $analysis = '';
    switch ($var) {
        case 'SERVER_SOFTWARE':
            if (strpos($value, 'Apache') !== false) {
                $analysis = '✅ Apache détecté';
            } else {
                $analysis = '⚠️ Autre serveur web';
            }
            break;
        case 'DOCUMENT_ROOT':
            if (strpos($value, 'public_html') !== false || strpos($value, 'public') !== false) {
                $analysis = '✅ Document root standard';
            } else {
                $analysis = 'ℹ️ Document root personnalisé';
            }
            break;
        case 'REQUEST_URI':
            if (strpos($value, '.htaccess') !== false) {
                $analysis = '⚠️ Accès .htaccess bloqué';
            } else {
                $analysis = '✅ URI normale';
            }
            break;
    }
    
    echo "<tr><td>$var</td><td>" . htmlspecialchars($value) . "</td><td>$analysis</td></tr>";
}

echo "</table>";

echo '</div>';

// =====================================
// 7. RECOMMANDATIONS SPÉCIFIQUES
// =====================================

echo '<div class="section">
<h2>💡 7. Recommandations de correction</h2>';

$recommendations = [];

if (!empty($htaccess_issues)) {
    $recommendations[] = [
        'priority' => 'CRITIQUE',
        'title' => 'Corriger erreurs syntaxe .htaccess',
        'actions' => $htaccess_issues
    ];
}

if (!empty($conflicts)) {
    $recommendations[] = [
        'priority' => 'IMPORTANT',
        'title' => 'Résoudre conflits entre .htaccess',
        'actions' => ['Simplifier les règles de redirection', 'Éviter les catch-all trop larges', 'Tester l\'ordre des règles']
    ];
}

// Recommandations générales
$recommendations[] = [
    'priority' => 'MAINTENANCE',
    'title' => 'Optimisation .htaccess',
    'actions' => [
        'Commenter les règles complexes',
        'Tester les règles individuellement',
        'Sauvegarder avant modification',
        'Utiliser des conditions plus spécifiques'
    ]
];

foreach ($recommendations as $rec) {
    $priority_class = strtolower($rec['priority']);
    echo "<div class='$priority_class'>";
    echo "<h4>🔥 {$rec['priority']}: {$rec['title']}</h4>";
    echo "<ul>";
    foreach ($rec['actions'] as $action) {
        echo "<li>$action</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Commandes de test suggérées
echo "<div class='info'>";
echo "<h4>🛠️ Commandes de test suggérées:</h4>";
echo "<pre># Test syntaxe Apache
apache2ctl configtest

# Test .htaccess spécifique
apache2ctl -S

# Redémarrage Apache (si nécessaire)
sudo systemctl restart apache2

# Test permissions
ls -la /chemin/vers/.htaccess

# Voir les erreurs en temps réel
tail -f /var/log/apache2/error.log</pre>";
echo "</div>";

echo '</div>';

// =====================================
// 8. RÉSUMÉ ET SCORE
// =====================================

$total_issues = count($htaccess_issues) + count($conflicts);
$status = 'OK';
$status_class = 'success';

if ($total_issues > 2) {
    $status = 'CRITIQUE';
    $status_class = 'error';
} elseif ($total_issues > 0) {
    $status = 'AVERTISSEMENT';
    $status_class = 'warning';
}

echo '<div class="section">
<h2>📊 8. Résumé du diagnostic</h2>';

echo "<div class='$status_class' style='text-align: center; padding: 20px; font-size: 1.2em;'>";
echo "<strong>État: $status</strong><br>";
echo "Issues détectées: $total_issues<br>";
echo "Fichiers .htaccess analysés: " . count($htaccess_contents) . "<br>";
echo "Diagnostic terminé: " . date('Y-m-d H:i:s');
echo "</div>";

if ($total_issues === 0) {
    echo "<div class='success'>✅ Les fichiers .htaccess semblent corrects. L'erreur 500 pourrait venir d'ailleurs.</div>";
} else {
    echo "<div class='error'>🚨 Des problèmes .htaccess ont été détectés qui peuvent causer l'erreur 500.</div>";
}

echo '</div>';

echo '<div style="text-align: center; margin: 20px 0;">
    <a href="?" class="test-url">🔄 Relancer le diagnostic</a>
    <a href="/diagnostic_500.php" class="test-url">🔍 Diagnostic général</a>
</div>';

echo '</div></body></html>';
?>
