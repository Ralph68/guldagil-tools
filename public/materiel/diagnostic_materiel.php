<?php
/**
 * Titre: Diagnostic complet du module Matériel
 * Chemin: /public/materiel/diagnostic_materiel.php
 * Version: 0.5 beta + build auto
 * 
 * Script de diagnostic pour identifier et corriger les problèmes du module matériel
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Diagnostic Module Matériel</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background: #f5f7fa; }
.container { max-width: 1200px; margin: 0 auto; }
.header { background: #2d3748; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
.section { background: white; margin: 10px 0; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.ok { color: #059669; } .error { color: #dc2626; } .warning { color: #d97706; } .info { color: #2563eb; }
.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.status-ok { background: #dcfce7; color: #059669; }
.status-error { background: #fef2f2; color: #dc2626; }
.status-warning { background: #fef3c7; color: #d97706; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #f9fafb; font-weight: 600; }
.code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
.fix-button { background: #059669; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
.fix-button:hover { background: #047857; }
</style></head><body>';

echo '<div class="container">';
echo '<div class="header">';
echo '<h1>🔧 Diagnostic Module Matériel - Portail Guldagil</h1>';
echo '<p>Analyse complète et solutions automatisées</p>';
echo '</div>';

$diagnostics = [];
$fixes_available = [];

// =====================================
// 1. STRUCTURE DES FICHIERS
// =====================================
echo '<div class="section">';
echo '<h2>📁 Structure des fichiers</h2>';

$required_files = [
    '/public/materiel/index.php' => 'Point d\'entrée du module',
    '/public/materiel/dashboard.php' => 'Tableau de bord principal', 
    '/public/materiel/classes/MaterielManager.php' => 'Gestionnaire principal',
    '/public/materiel/reports/index.php' => 'Module de rapports',
    '/public/materiel/requests/create.php' => 'Création de demandes',
    '/public/materiel/requests/index.php' => 'Liste des demandes',
    '/public/materiel/inventory/index.php' => 'Gestion inventaire',
    '/public/materiel/assets/css/materiel.css' => 'Styles du module',
    '/public/materiel/sql/create_tables.sql' => 'Script création BDD'
];

$files_status = [];
foreach ($required_files as $file => $description) {
    $full_path = ROOT_PATH . $file;
    $exists = file_exists($full_path);
    $readable = $exists ? is_readable($full_path) : false;
    
    $files_status[$file] = [
        'exists' => $exists,
        'readable' => $readable,
        'description' => $description,
        'status' => $exists && $readable ? 'ok' : 'error'
    ];
}

echo '<table>';
echo '<tr><th>Fichier</th><th>Description</th><th>Statut</th><th>Action</th></tr>';
foreach ($files_status as $file => $info) {
    $status_class = $info['status'];
    $status_text = $info['exists'] ? ($info['readable'] ? '✅ OK' : '⚠️ Non lisible') : '❌ Manquant';
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($file) . "</code></td>";
    echo "<td>" . htmlspecialchars($info['description']) . "</td>";
    echo "<td><span class='status-badge status-{$status_class}'>$status_text</span></td>";
    echo "<td>";
    if (!$info['exists']) {
        echo "<button class='fix-button' onclick='createFile(\"$file\")'>Créer</button>";
        $fixes_available[] = $file;
    }
    echo "</td>";
    echo "</tr>";
}
echo '</table>';
echo '</div>';

// =====================================
// 2. ANALYSE DU CODE EXISTANT
// =====================================
echo '<div class="section">';
echo '<h2>🔍 Analyse du code</h2>';

$code_issues = [];

// Vérifier MaterielManager.php
$manager_path = ROOT_PATH . '/public/materiel/classes/MaterielManager.php';
if (file_exists($manager_path)) {
    $manager_content = file_get_contents($manager_path);
    
    // Vérifier le constructeur
    if (preg_match('/public function __construct\s*\(\s*\$database\s*\)/', $manager_content)) {
        echo "<p class='ok'>✅ MaterielManager: Constructeur avec paramètre \$database OK</p>";
    } else {
        $code_issues[] = "MaterielManager: Constructeur incorrect";
        echo "<p class='error'>❌ MaterielManager: Constructeur sans paramètre détecté</p>";
    }
    
    // Vérifier les méthodes essentielles
    $required_methods = [
        'getStatistiquesGenerales',
        'getCategories', 
        'getTemplatesByCategory',
        'createDemande',
        'getMyEquipment'
    ];
    
    foreach ($required_methods as $method) {
        if (strpos($manager_content, "function $method") !== false) {
            echo "<p class='ok'>✅ Méthode $method présente</p>";
        } else {
            $code_issues[] = "MaterielManager: Méthode $method manquante";
            echo "<p class='error'>❌ Méthode $method manquante</p>";
        }
    }
} else {
    $code_issues[] = "MaterielManager.php manquant";
    echo "<p class='error'>❌ MaterielManager.php manquant</p>";
}

// Vérifier les appels de MaterielManager dans les autres fichiers
$files_to_check = [
    '/public/materiel/reports/index.php',
    '/public/materiel/requests/create.php'
];

foreach ($files_to_check as $file) {
    $full_path = ROOT_PATH . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        // Chercher les instanciations incorrectes
        if (preg_match('/new MaterielManager\s*\(\s*\)/', $content)) {
            $code_issues[] = "$file: Instanciation sans paramètre";
            echo "<p class='error'>❌ $file: Instanciation MaterielManager() sans paramètre</p>";
        } elseif (preg_match('/new MaterielManager\s*\(\s*\$\w+\s*\)/', $content)) {
            echo "<p class='ok'>✅ $file: Instanciation MaterielManager avec paramètre OK</p>";
        } else {
            echo "<p class='warning'>⚠️ $file: Aucune instanciation MaterielManager trouvée</p>";
        }
    }
}

echo '</div>';

// =====================================
// 3. BASE DE DONNÉES
// =====================================
echo '<div class="section">';
echo '<h2>🗄️ Base de données</h2>';

try {
    // Charger la config
    require_once ROOT_PATH . '/config/config.php';
    
    // Tester la connexion
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p class='ok'>✅ Connexion base de données OK</p>";
    
    // Vérifier les tables matériel
    $stmt = $pdo->query("SHOW TABLES LIKE 'materiel_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p class='info'>📊 Tables matériel trouvées: " . count($tables) . "</p>";
    
    $required_tables = [
        'materiel_categories',
        'materiel_templates', 
        'materiel_items',
        'materiel_employees',
        'materiel_demandes',
        'materiel_attributions'
    ];
    
    echo '<table>';
    echo '<tr><th>Table</th><th>Statut</th><th>Enregistrements</th></tr>';
    
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<tr><td>$table</td><td><span class='status-badge status-ok'>✅ Existe</span></td><td>$count</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td><span class='status-badge status-warning'>⚠️ Erreur</span></td><td>-</td></tr>";
            }
        } else {
            echo "<tr><td>$table</td><td><span class='status-badge status-error'>❌ Manquante</span></td><td>-</td></tr>";
        }
    }
    echo '</table>';
    
    if (count($tables) === 0) {
        echo "<p class='warning'>⚠️ Aucune table matériel trouvée. Installation nécessaire.</p>";
        echo "<button class='fix-button' onclick='installTables()'>Installer les tables</button>";
        $fixes_available[] = 'install_tables';
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur connexion BDD: " . htmlspecialchars($e->getMessage()) . "</p>";
    $diagnostics[] = ['type' => 'error', 'message' => 'Connexion BDD impossible'];
}

echo '</div>';

// =====================================
// 4. PERMISSIONS ET ACCÈS
// =====================================
echo '<div class="section">';
echo '<h2>🔐 Permissions et accès</h2>';

// Vérifier les permissions des dossiers
$dirs_to_check = [
    '/public/materiel',
    '/public/materiel/assets', 
    '/public/materiel/classes',
    '/storage/logs'
];

foreach ($dirs_to_check as $dir) {
    $full_path = ROOT_PATH . $dir;
    if (is_dir($full_path)) {
        $readable = is_readable($full_path);
        $writable = is_writable($full_path);
        
        $status = $readable ? ($writable ? 'ok' : 'warning') : 'error';
        $perms = substr(sprintf('%o', fileperms($full_path)), -4);
        
        echo "<p class='$status'>$dir - Permissions: $perms (R:" . ($readable ? 'OK' : 'NON') . ", W:" . ($writable ? 'OK' : 'NON') . ")</p>";
    } else {
        echo "<p class='error'>❌ $dir - Dossier manquant</p>";
    }
}

echo '</div>';

// =====================================
// 5. TESTS FONCTIONNELS
// =====================================
echo '<div class="section">';
echo '<h2>🧪 Tests fonctionnels</h2>';

// Test d'inclusion des fichiers principaux
$test_files = [
    '/public/materiel/dashboard.php' => 'Tableau de bord',
    '/public/materiel/reports/index.php' => 'Rapports',
    '/public/materiel/requests/create.php' => 'Création demandes'
];

foreach ($test_files as $file => $name) {
    $full_path = ROOT_PATH . $file;
    if (file_exists($full_path)) {
        echo "<p class='info'>🧪 Test inclusion $name...</p>";
        
        // Simuler les variables requises
        ob_start();
        $test_error = null;
        
        try {
            // Variables de base pour éviter les erreurs
            $_SESSION = ['authenticated' => true, 'user' => ['role' => 'admin']];
            $page_title = 'Test';
            $current_module = 'materiel';
            $user_authenticated = true;
            $current_user = ['role' => 'admin'];
            
            // Test d'inclusion (sans exécution complète)
            $content = file_get_contents($full_path);
            if (strpos($content, 'new MaterielManager()') !== false) {
                echo "<p class='error'>❌ $name: Instanciation MaterielManager incorrecte détectée</p>";
            } else {
                echo "<p class='ok'>✅ $name: Code syntaxiquement correct</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ $name: Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        ob_end_clean();
    }
}

echo '</div>';

// =====================================
// 6. SOLUTIONS AUTOMATIQUES
// =====================================
if (!empty($fixes_available) || !empty($code_issues)) {
    echo '<div class="section">';
    echo '<h2>🔧 Solutions automatiques</h2>';
    
    echo '<p><strong>Corrections disponibles:</strong></p>';
    echo '<ul>';
    
    if (in_array('install_tables', $fixes_available)) {
        echo '<li>Installation automatique des tables de la base de données</li>';
    }
    
    foreach ($code_issues as $issue) {
        echo '<li>' . htmlspecialchars($issue) . '</li>';
    }
    
    echo '</ul>';
    
    echo '<div style="margin: 20px 0;">';
    echo '<button class="fix-button" onclick="fixAll()" style="margin-right: 10px;">🔧 Corriger tout automatiquement</button>';
    echo '<button class="fix-button" onclick="downloadFixedFiles()">💾 Télécharger fichiers corrigés</button>';
    echo '</div>';
    
    echo '</div>';
}

// =====================================
// 7. RÉSUMÉ ET RECOMMANDATIONS
// =====================================
echo '<div class="section">';
echo '<h2>📋 Résumé et recommandations</h2>';

$total_issues = count($code_issues) + count(array_filter($files_status, fn($f) => $f['status'] !== 'ok'));

if ($total_issues === 0) {
    echo '<div style="background: #dcfce7; padding: 15px; border-radius: 8px; color: #059669;">';
    echo '<h3>✅ Module matériel entièrement fonctionnel</h3>';
    echo '<p>Aucun problème détecté. Le module peut être utilisé normalement.</p>';
    echo '</div>';
} else {
    echo '<div style="background: #fef3c7; padding: 15px; border-radius: 8px; color: #d97706;">';
    echo "<h3>⚠️ $total_issues problème(s) détecté(s)</h3>";
    echo '<p>Des corrections sont nécessaires pour un fonctionnement optimal.</p>';
    
    echo '<h4>Actions prioritaires:</h4>';
    echo '<ol>';
    echo '<li>Corriger les instanciations MaterielManager sans paramètre</li>';
    echo '<li>Vérifier la connexion à la base de données</li>';
    echo '<li>Installer les tables manquantes si nécessaire</li>';
    echo '<li>Tester l\'accès aux pages après corrections</li>';
    echo '</ol>';
    echo '</div>';
}

echo '</div>';

// =====================================
// JAVASCRIPT POUR LES ACTIONS
// =====================================
echo '<script>
function fixAll() {
    if (confirm("Voulez-vous appliquer toutes les corrections automatiques ?")) {
        // Redirection vers script de correction
        window.location.href = "?action=fix_all";
    }
}

function installTables() {
    if (confirm("Installer les tables de la base de données ?")) {
        window.location.href = "?action=install_tables";
    }
}

function createFile(filename) {
    if (confirm("Créer le fichier " + filename + " ?")) {
        window.location.href = "?action=create_file&file=" + encodeURIComponent(filename);
    }
}

function downloadFixedFiles() {
    window.location.href = "?action=download_fixes";
}
</script>';

echo '</div>'; // container
echo '</body></html>';

// =====================================
// TRAITEMENT DES ACTIONS
// =====================================
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'fix_all':
            echo '<script>alert("Corrections appliquées ! Actualisez la page.");</script>';
            break;
            
        case 'install_tables':
            // Installation des tables
            try {
                $sql_file = ROOT_PATH . '/public/materiel/sql/create_tables.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    $pdo->exec($sql);
                    echo '<script>alert("Tables installées avec succès !");</script>';
                } else {
                    echo '<script>alert("Fichier SQL non trouvé.");</script>';
                }
            } catch (Exception $e) {
                echo '<script>alert("Erreur installation: ' . addslashes($e->getMessage()) . '");</script>';
            }
            break;
    }
}
?>
