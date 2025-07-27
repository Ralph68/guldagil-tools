<?php
/**
 * Titre: Vérification Header propre - Aucun React
 * Chemin: /debug_react_check.php
 * Version: 0.5 beta + build auto
 */

// Script de diagnostic pour éliminer les sources React
echo "<!DOCTYPE html>\n<html lang='fr'>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>Check React - Portail Guldagil</title>\n";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🔍 Diagnostic React/JS - Portail Guldagil</h1>\n";

// 1. Vérifier templates
$templates = [
    'header' => '/templates/header.php',
    'footer' => '/templates/footer.php'
];

foreach ($templates as $name => $path) {
    if (file_exists(__DIR__ . $path)) {
        $content = file_get_contents(__DIR__ . $path);
        
        echo "<h2>📄 $name.php</h2>\n";
        
        // Rechercher React/CDN suspects
        $react_patterns = [
            'react' => '/react/i',
            'jsx' => '/jsx/i', 
            'inspector' => '/inspector/i',
            'cdn.jsdelivr' => '/cdn\.jsdelivr/i',
            'unpkg.com' => '/unpkg\.com/i',
            'cdnjs.cloudflare' => '/cdnjs\.cloudflare/i'
        ];
        
        foreach ($react_patterns as $pattern_name => $pattern) {
            if (preg_match($pattern, $content)) {
                echo "<span style='color:red'>❌ TROUVÉ: $pattern_name</span><br>\n";
                
                // Extraire les lignes concernées
                $lines = explode("\n", $content);
                foreach ($lines as $num => $line) {
                    if (preg_match($pattern, $line)) {
                        echo "<pre style='background:#ffe6e6;padding:5px;'>Ligne " . ($num+1) . ": " . htmlspecialchars(trim($line)) . "</pre>\n";
                    }
                }
            } else {
                echo "<span style='color:green'>✅ OK: $pattern_name</span><br>\n";
            }
        }
        echo "<hr>\n";
    }
}

// 2. Vérifier modules suspects
$modules_check = [
    '/public/materiel/dashboard.php',
    '/public/admin/index.php',
    '/public/adr/index.php'
];

echo "<h2>📦 Modules Check</h2>\n";
foreach ($modules_check as $module_path) {
    if (file_exists(__DIR__ . $module_path)) {
        $content = file_get_contents(__DIR__ . $module_path);
        echo "<h3>" . basename($module_path) . "</h3>\n";
        
        // Compter les scripts externes
        preg_match_all('/<script[^>]*src=["\']([^"\']*)["\'][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            echo "<ul>\n";
            foreach ($matches[1] as $script_src) {
                $is_external = (strpos($script_src, 'http') === 0);
                $color = $is_external ? 'orange' : 'green';
                echo "<li style='color:$color'>" . htmlspecialchars($script_src) . "</li>\n";
            }
            echo "</ul>\n";
        } else {
            echo "<span style='color:green'>✅ Aucun script externe</span><br>\n";
        }
    }
}

// 3. JS Files check
echo "<h2>📜 Fichiers JS Check</h2>\n";
$js_files = [
    '/public/assets/js/header.js',
    '/public/assets/js/portal.js',
    '/public/port/assets/js/port.js'
];

foreach ($js_files as $js_path) {
    if (file_exists(__DIR__ . $js_path)) {
        $content = file_get_contents(__DIR__ . $js_path);
        echo "<h3>" . basename($js_path) . "</h3>\n";
        
        // Vérifier imports/requires React
        if (preg_match('/import.*react|require.*react|React\.|ReactDOM/i', $content)) {
            echo "<span style='color:red'>❌ CONTIENT REACT</span><br>\n";
        } else {
            echo "<span style='color:green'>✅ JS Vanilla pur</span><br>\n";
        }
        
        // Taille du fichier
        echo "<small>Taille: " . round(strlen($content)/1024, 1) . " KB</small><br>\n";
    } else {
        echo "<h3 style='color:gray'>" . basename($js_path) . " - NON TROUVÉ</h3>\n";
    }
}

// 4. Recommandations
echo "<h2>💡 Recommandations</h2>\n";
echo "<ol>\n";
echo "<li><strong>Supprimer tous les CDN externes</strong> de Chart.js, FontAwesome, etc.</li>\n";
echo "<li><strong>Utiliser versions locales</strong> des bibliothèques si nécessaires</li>\n";
echo "<li><strong>Vérifier extensions navigateur</strong> (React DevTools, etc.)</li>\n";
echo "<li><strong>Clear cache complet</strong> (Ctrl+Shift+R)</li>\n";
echo "<li><strong>Tester en navigation privée</strong></li>\n";
echo "</ol>\n";

echo "<hr>\n";
echo "<h2>🧹 Version header.js propre</h2>\n";
echo "<p>Utilisez uniquement du JavaScript vanilla ES6+ sans aucune dépendance externe.</p>\n";

echo "</body>\n</html>";
?>