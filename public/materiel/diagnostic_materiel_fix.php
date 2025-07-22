<?php
/**
 * Titre: Diagnostic et Correction Module Matériel
 * Chemin: /public/materiel/diagnostic_materiel_fix.php
 * Version: 0.5 beta + build auto
 * 
 * ⚠️ INSTRUCTIONS D'UTILISATION :
 * 1. Placer ce fichier dans /public/materiel/
 * 2. Accéder via http://votre-domaine/materiel/diagnostic_materiel_fix.php
 * 3. Suivre les corrections proposées
 * 4. SUPPRIMER ce fichier après utilisation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Interface HTML avec style moderne
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Diagnostic Module Matériel - Portail Guldagil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container {
            max-width: 1400px; margin: 0 auto;
            background: white; border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white; padding: 40px; text-align: center;
            position: relative; overflow: hidden;
        }
        .header::before {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="3" fill="white" opacity="0.1"/><circle cx="40" cy="70" r="1" fill="white" opacity="0.15"/></svg>');
        }
        .header h1 { font-size: 3rem; margin-bottom: 15px; position: relative; z-index: 1; }
        .header p { opacity: 0.9; font-size: 1.2rem; position: relative; z-index: 1; }
        .content { padding: 40px; }
        .section {
            background: #f8fafc; border-radius: 12px;
            padding: 30px; margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            position: relative; overflow: hidden;
        }
        .section::before {
            content: ""; position: absolute; top: 0; left: 0;
            width: 6px; height: 100%; background: var(--section-color, #3b82f6);
        }
        .section h2 {
            color: #1e40af; margin-bottom: 20px;
            font-size: 1.6rem; display: flex; align-items: center;
            padding-left: 20px;
        }
        .section h2::before {
            margin-right: 12px; font-size: 1.4em;
        }
        .section.files { --section-color: #3b82f6; }
        .section.database { --section-color: #10b981; }
        .section.code { --section-color: #f59e0b; }
        .section.tests { --section-color: #ef4444; }
        .section.fixes { --section-color: #8b5cf6; }
        
        .status-ok { color: #059669; font-weight: 600; }
        .status-error { color: #dc2626; font-weight: 600; }
        .status-warning { color: #d97706; font-weight: 600; }
        .status-info { color: #2563eb; font-weight: 600; }
        
        .code {
            background: #1e293b; color: #e2e8f0; padding: 20px;
            border-radius: 8px; font-family: 'Courier New', monospace;
            overflow-x: auto; margin: 15px 0; white-space: pre-wrap;
            font-size: 0.9rem; line-height: 1.4;
        }
        .fix-btn {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white; padding: 12px 24px; border: none; border-radius: 8px;
            cursor: pointer; font-weight: 600; margin: 8px 8px 8px 0;
            transition: all 0.3s ease; font-size: 0.95rem;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .fix-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
        }
        .fix-btn.danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        .fix-btn.danger:hover {
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }
        .fix-btn.info {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        .table {
            width: 100%; border-collapse: collapse; margin: 20px 0;
            background: white; border-radius: 8px; overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        .table th, .table td {
            padding: 16px; text-align: left; border-bottom: 1px solid #f1f5f9;
        }
        .table th {
            background: #f8fafc; font-weight: 600; color: #475569;
            font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .table tbody tr:hover { background: #f8fafc; }
        
        .alert {
            padding: 20px; border-radius: 10px; margin: 20px 0;
            border: 1px solid; position: relative; overflow: hidden;
        }
        .alert::before {
            content: ""; position: absolute; top: 0; left: 0;
            width: 6px; height: 100%; background: var(--alert-color);
        }
        .alert.success {
            --alert-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-color: #a7f3d0; color: #065f46;
        }
        .alert.error {
            --alert-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #fca5a5; color: #991b1b;
        }
        .alert.warning {
            --alert-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-color: #fde68a; color: #92400e;
        }
        .alert.info {
            --alert-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd; color: #1e40af;
        }
        
        .progress {
            height: 8px; background: #e5e7eb; border-radius: 4px;
            overflow: hidden; margin: 20px 0; position: relative;
        }
        .progress-bar {
            height: 100%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.6s ease; position: relative;
        }
        .progress-bar::after {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px; margin: 20px 0;
        }
        .card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07); border: 1px solid #f1f5f9;
        }
        .card h3 {
            color: #1e293b; margin-bottom: 15px; font-size: 1.2rem;
            display: flex; align-items: center; gap: 10px;
        }
        .score {
            font-size: 3rem; font-weight: bold; text-align: center;
            margin: 20px 0; color: #1e293b;
        }
        .score.good { color: #059669; }
        .score.medium { color: #d97706; }
        .score.bad { color: #dc2626; }
        
        .icon { width: 1.2em; height: 1.2em; display: inline-block; }
        .spinning { animation: spin 2s linear infinite; }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Diagnostic Module Matériel</h1>
            <p>Portail Guldagil - Analyse complète et corrections automatiques</p>
        </div>
        
        <div class="content">
<?php

// =====================================
// VARIABLES GLOBALES
// =====================================
$errors = [];
$warnings = [];
$fixes_applied = [];
$total_checks = 0;
$successful_checks = 0;
$critical_issues = [];

// =====================================
// FONCTIONS UTILITAIRES
// =====================================

function checkProgress() {
    global $total_checks;
    $total_checks++;
}

function markSuccess() {
    global $successful_checks;
    $successful_checks++;
}

function addError($message, $critical = false) {
    global $errors, $critical_issues;
    $errors[] = $message;
    if ($critical) {
        $critical_issues[] = $message;
    }
}

function addWarning($message) {
    global $warnings;
    $warnings[] = $message;
}

function addFix($message) {
    global $fixes_applied;
    $fixes_applied[] = $message;
}

// =====================================
// 1. VÉRIFICATION STRUCTURE FICHIERS
// =====================================
echo '<div class="section files">';
echo '<h2>📁 Structure des fichiers</h2>';

$required_files = [
    '/public/materiel/index.php' => 'Point d\'entrée du module',
    '/public/materiel/dashboard.php' => 'Tableau de bord principal',
    '/public/materiel/classes/MaterielManager.php' => 'Gestionnaire principal',
    '/public/materiel/assets/css/materiel.css' => 'Styles du module',
    '/public/materiel/sql/create_tables.sql' => 'Script création BDD'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    checkProgress();
    $full_path = ROOT_PATH . $file;
    $exists = file_exists($full_path);
    
    if ($exists) {
        echo "<p class='status-ok'>✅ $file - $description</p>";
        markSuccess();
    } else {
        echo "<p class='status-error'>❌ $file - $description (MANQUANT)</p>";
        $missing_files[] = $file;
        addError("Fichier manquant : $file", true);
    }
}

echo '</div>';

// =====================================
// 2. VÉRIFICATION CONFIGURATION
// =====================================
echo '<div class="section database">';
echo '<h2>🗄️ Base de données et configuration</h2>';

checkProgress();
if (file_exists(ROOT_PATH . '/config/config.php')) {
    echo "<p class='status-ok'>✅ Configuration principale trouvée</p>";
    require_once ROOT_PATH . '/config/config.php';
    markSuccess();
} else {
    echo "<p class='status-error'>❌ Configuration principale manquante</p>";
    addError("Fichier config/config.php manquant", true);
}

// Test connexion BDD
$db_connected = false;
$db = null;
$table_issues = [];

checkProgress();
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
    echo "<p class='status-ok'>✅ Connexion base de données OK</p>";
    markSuccess();
    
    // Vérifier tables matériel vs outillage
    $stmt = $db->query("SHOW TABLES LIKE 'materiel_%'");
    $materiel_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->query("SHOW TABLES LIKE 'outillage_%'");
    $outillage_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='card'>";
    echo "<h3>📊 Analyse des tables</h3>";
    echo "<p class='status-info'>Tables materiel_ : " . count($materiel_tables) . "</p>";
    echo "<p class='status-info'>Tables outillage_ : " . count($outillage_tables) . "</p>";
    
    if (count($materiel_tables) === 0 && count($outillage_tables) === 0) {
        echo "<p class='status-error'>❌ Aucune table trouvée - Installation requise</p>";
        addError("Tables du module manquantes", true);
        $table_issues[] = "no_tables";
    } elseif (count($materiel_tables) > 0 && count($outillage_tables) > 0) {
        echo "<p class='status-warning'>⚠️ Confusion : tables matériel ET outillage présentes</p>";
        addWarning("Incohérence nomenclature BDD");
        $table_issues[] = "mixed_tables";
    } elseif (count($materiel_tables) > 0) {
        echo "<p class='status-ok'>✅ Tables matériel trouvées</p>";
        markSuccess();
    } else {
        echo "<p class='status-warning'>⚠️ Seulement tables outillage (migration nécessaire ?)</p>";
        addWarning("Tables outillage au lieu de matériel");
        $table_issues[] = "outillage_only";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur connexion BDD: " . htmlspecialchars($e->getMessage()) . "</p>";
    addError("Impossible de se connecter à la base de données", true);
}

echo '</div>';

// =====================================
// 3. ANALYSE PROBLÈME REDIRECTION
// =====================================
echo '<div class="section code">';
echo '<h2>🔍 Analyse problème de redirection</h2>';

$index_path = ROOT_PATH . '/public/materiel/index.php';
if (file_exists($index_path)) {
    checkProgress();
    $index_content = file_get_contents($index_path);
    
    if (strpos($index_content, "header('Location: ./dashboard.php')") !== false) {
        echo "<p class='status-error'>❌ PROBLÈME CRITIQUE : Redirection infinie détectée !</p>";
        echo "<div class='alert error'>";
        echo "<strong>🚨 Cause du chargement infini identifiée :</strong><br>";
        echo "Le fichier <code>index.php</code> redirige vers <code>dashboard.php</code><br>";
        echo "Si dashboard.php a un problème, la page charge indéfiniment.";
        echo "</div>";
        addError("Redirection infinie dans index.php", true);
    } else {
        markSuccess();
    }
}

// Vérifier dashboard.php
$dashboard_path = ROOT_PATH . '/public/materiel/dashboard.php';
if (file_exists($dashboard_path)) {
    checkProgress();
    $dashboard_content = file_get_contents($dashboard_path);
    
    // Vérifier erreurs communes
    if (strpos($dashboard_content, 'new MaterielManager()') !== false) {
        echo "<p class='status-error'>❌ MaterielManager instancié sans paramètre dans dashboard.php</p>";
        addError("MaterielManager mal instancié", true);
    } elseif (strpos($dashboard_content, 'new MaterielManager($db)') !== false) {
        echo "<p class='status-ok'>✅ MaterielManager correctement instancié</p>";
        markSuccess();
    }
    
    // Vérifier tables utilisées
    if (strpos($dashboard_content, 'outillage_items') !== false) {
        echo "<p class='status-warning'>⚠️ Dashboard utilise des tables outillage_ au lieu de materiel_</p>";
        addWarning("Incohérence tables dans dashboard");
    }
} else {
    checkProgress();
    echo "<p class='status-error'>❌ dashboard.php manquant</p>";
    addError("dashboard.php manquant", true);
}

echo '</div>';

// =====================================
// 4. CALCUL DU SCORE
// =====================================
$success_rate = $total_checks > 0 ? ($successful_checks / $total_checks) * 100 : 0;

echo '<div class="section">';
echo '<h2>📊 Résumé du diagnostic</h2>';

echo '<div class="progress">';
echo '<div class="progress-bar" style="width: ' . $success_rate . '%"></div>';
echo '</div>';

$score_class = $success_rate >= 80 ? 'good' : ($success_rate >= 50 ? 'medium' : 'bad');
echo "<div class='score $score_class'>" . round($success_rate, 1) . "%</div>";
echo "<p style='text-align: center; color: #64748b; margin-bottom: 30px;'>$successful_checks/$total_checks vérifications réussies</p>";

if (count($critical_issues) > 0) {
    echo '<div class="alert error">';
    echo '<strong>🚨 PROBLÈMES CRITIQUES :</strong><br><br>';
    foreach ($critical_issues as $issue) {
        echo "• " . htmlspecialchars($issue) . "<br>";
    }
    echo '</div>';
}

if (count($warnings) > 0) {
    echo '<div class="alert warning">';
    echo '<strong>⚠️ Avertissements :</strong><br><br>';
    foreach ($warnings as $warning) {
        echo "• " . htmlspecialchars($warning) . "<br>";
    }
    echo '</div>';
}

echo '</div>';

// =====================================
// 5. CORRECTIONS AUTOMATIQUES
// =====================================
echo '<div class="section fixes">';
echo '<h2>🔧 Corrections automatiques</h2>';

if ($_POST['action'] ?? '' === 'fix_critical') {
    echo '<div class="alert success">';
    echo '<strong>🔧 Application des corrections critiques...</strong><br><br>';
    
    // Fix 1: Corriger la redirection dans index.php
    if (file_exists($index_path) && strpos(file_get_contents($index_path), "header('Location: ./dashboard.php')") !== false) {
        $fixed_index = getFixedIndexTemplate();
        if (file_put_contents($index_path . '.backup', file_get_contents($index_path))) {
            if (file_put_contents($index_path, $fixed_index)) {
                echo "✅ index.php corrigé (backup créé)<br>";
                addFix("Redirection corrigée dans index.php");
            }
        }
    }
    
    // Fix 2: Créer dashboard.php fonctionnel s'il manque
    if (!file_exists($dashboard_path) || filesize($dashboard_path) < 1000) {
        $dashboard_content = getDashboardTemplate();
        if (file_put_contents($dashboard_path, $dashboard_content)) {
            echo "✅ dashboard.php créé/corrigé<br>";
            addFix("dashboard.php fonctionnel créé");
        }
    }
    
    // Fix 3: Créer CSS s'il manque
    $css_path = ROOT_PATH . '/public/materiel/assets/css/materiel.css';
    if (!file_exists($css_path)) {
        @mkdir(dirname($css_path), 0755, true);
        $css_content = getMaterielCSSTemplate();
        if (file_put_contents($css_path, $css_content)) {
            echo "✅ CSS matériel créé<br>";
            addFix("CSS module créé");
        }
    }
    
    echo '<br><strong>🎉 Corrections appliquées ! Testez maintenant le module.</strong>';
    echo '</div>';
} else {
    echo '<p><strong>Corrections critiques disponibles :</strong></p>';
    echo '<ul style="margin: 15px 0; padding-left: 30px;">';
    echo '<li>Corriger la redirection infinie dans index.php</li>';
    echo '<li>Créer/corriger dashboard.php fonctionnel</li>';
    echo '<li>Créer les assets CSS manquants</li>';
    echo '<li>Standardiser l\'accès base de données</li>';
    echo '</ul>';
    
    echo '<form method="post" style="margin: 20px 0;">';
    echo '<input type="hidden" name="action" value="fix_critical">';
    echo '<button type="submit" class="fix-btn">🔧 Appliquer les corrections critiques</button>';
    echo '</form>';
}

echo '<div class="alert info">';
echo '<strong>💡 Après les corrections :</strong><br>';
echo '1. Accédez à <code>/materiel/</code> pour tester<br>';
echo '2. Vérifiez que les données s\'affichent<br>';
echo '3. Si problème persiste, utilisez le scanner admin : <code>/admin/scanner.php</code>';
echo '</div>';

echo '</div>';

// =====================================
// TEMPLATES DE CORRECTION
// =====================================

function getFixedIndexTemplate() {
    return '<?php
/**
 * Titre: Module Matériel - Index corrigé
 * Chemin: /public/materiel/index.php
 * Version: 0.5 beta + build auto - CORRIGÉ
 */

if (!defined(\'ROOT_PATH\')) {
    define(\'ROOT_PATH\', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . \'/config/config.php}

echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>
require_once ROOT_PATH . \'/config/version.php\';

// Variables pour template
$page_title = \'Gestion du Matériel\';
$page_subtitle = \'Outillage et Équipements\';
$current_module = \'materiel\';
$module_css = true;

// Vérification authentification simplifiée
$user_authenticated = isset($_SESSION[\'authenticated\']) && $_SESSION[\'authenticated\'] === true;
if (!$user_authenticated) {
    header(\'Location: /auth/login.php?redirect=\' . urlencode($_SERVER[\'REQUEST_URI\']));
    exit;
}

// CORRECTION : Au lieu de rediriger, incluons directement le dashboard
include __DIR__ . \'/dashboard.php\';
?>';
}

function getDashboardTemplate() {
    return '<?php
/**
 * Titre: Dashboard Module Matériel - Version corrigée
 * Chemin: /public/materiel/dashboard.php
 * Version: 0.5 beta + build auto - CORRIGÉ
 */

if (!defined(\'ROOT_PATH\')) {
    define(\'ROOT_PATH\', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . \'/config/config.php\';
require_once ROOT_PATH . \'/config/version.php\';

// Variables pour template
$page_title = \'Gestion du Matériel\';
$page_subtitle = \'Tableau de bord\';
$current_module = \'materiel\';
$module_css = true;
$user_authenticated = isset($_SESSION[\'authenticated\']) && $_SESSION[\'authenticated\'] === true;
$current_user = $_SESSION[\'user\'] ?? [\'username\' => \'Anonyme\', \'role\' => \'guest\'];

// Connexion BDD sécurisée
$db_connected = false;
$db = null;
$error_message = null;

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Erreur BDD Matériel: " . $e->getMessage());
}

// Statistiques par défaut
$stats = [
    \'total_materiel\' => 0,
    \'materiel_attribue\' => 0,
    \'demandes_attente\' => 0,
    \'maintenance_due\' => 0
];

// Récupération stats réelles si BDD OK
if ($db_connected) {
    try {
        // Essayer materiel_ en premier
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats[\'total_materiel\'] = $result[\'total\'] ?? 0;
        }
    } catch (Exception $e) {
        // Si materiel_ n\'existe pas, essayer outillage_
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM outillage_items");
            if ($stmt) {
                $result = $stmt->fetch();
                $stats[\'total_materiel\'] = $result[\'total\'] ?? 0;
            }
        } catch (Exception $e2) {
            // Tables n\'existent pas, garder 0
        }
    }
    
    // Autres stats si première requête réussie
    if ($stats[\'total_materiel\'] > 0) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = \'active\'");
            $result = $stmt->fetch();
            $stats[\'materiel_attribue\'] = $result[\'attribues\'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = \'en_attente\'");
            $result = $stmt->fetch();
            $stats[\'demandes_attente\'] = $result[\'en_attente\'] ?? 0;
        } catch (Exception $e) {
            // Ignorer erreurs sur tables secondaires
        }
    }
}

$isResponsable = in_array($current_user[\'role\'] ?? \'guest\', [\'admin\', \'dev\']);

include ROOT_PATH . \'/templates/header.php\';
?>

<main class="main-content">
    <div class="container">
        <!-- En-tête du module -->
        <div class="module-header">
            <div class="module-title">
                <h1>🔧 <?= htmlspecialchars($page_title) ?></h1>
                <p class="module-description"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            
            <div class="module-actions">
                <a href="./inventory/" class="btn btn-outline">
                    <i class="fas fa-boxes"></i> Inventaire
                </a>
                <a href="./requests/create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </a>
            </div>
        </div>

        <?php if (!$db_connected): ?>
        <div class="alert alert-danger">
            <strong>⚠️ Problème de connexion base de données</strong><br>
            Erreur : <?= htmlspecialchars($error_message ?? \'Inconnue\') ?><br>
            <a href="/admin/scanner.php" class="btn btn-sm btn-info" style="margin-top: 10px;">Diagnostic complet</a>
        </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-number"><?= $stats[\'total_materiel\'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-tools"></i> Total matériel
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats[\'materiel_attribue\'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-user-check"></i> Matériel attribué
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats[\'demandes_attente\'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i> Demandes en attente
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats[\'maintenance_due\'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-wrench"></i> Maintenance due
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="main-content-area">
                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h3>Actions rapides</h3>
                    <div class="actions-grid">
                        <a href="./inventory/" class="action-card">
                            <i class="fas fa-boxes"></i>
                            <h4>Inventaire</h4>
                            <p>Voir tout le matériel disponible</p>
                        </a>
                        
                        <a href="./requests/create.php" class="action-card">
                            <i class="fas fa-plus-circle"></i>
                            <h4>Faire une demande</h4>
                            <p>Demander du nouveau matériel</p>
                        </a>
                        
                        <?php if ($isResponsable): ?>
                        <a href="./admin/" class="action-card">
                            <i class="fas fa-cogs"></i>
                            <h4>Administration</h4>
                            <p>Gérer les catégories et modèles</p>
                        </a>
                        <?php endif; ?>
                        
                        <a href="./reports/" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Rapports</h4>
                            <p>Statistiques et analyses</p>
                        </a>
                    </div>
                </div>

                <?php if ($db_connected && $stats[\'total_materiel\'] > 0): ?>
                <div class="alert alert-success">
                    <strong>✅ Module fonctionnel</strong><br>
                    Base de données connectée, <?= $stats[\'total_materiel\'] ?> éléments trouvés.
                </div>
                <?php elseif ($db_connected): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Base de données vide</strong><br>
                    Connexion OK mais aucune donnée trouvée. Installation des tables nécessaire ?
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <strong>🔧 Configuration requise</strong><br>
                    Veuillez corriger la connexion base de données pour utiliser ce module.
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>Informations</h3>
                    <div class="info-item">
                        <strong>Module :</strong> Matériel v<?= defined(\'VERSION\') ? VERSION : \'0.5\' ?>
                    </div>
                    <div class="info-item">
                        <strong>Utilisateur :</strong> <?= htmlspecialchars($current_user[\'username\']) ?>
                    </div>
                    <div class="info-item">
                        <strong>Rôle :</strong> <?= htmlspecialchars($current_user[\'role\'] ?? \'guest\') ?>
                    </div>
                    <div class="info-item">
                        <strong>Base de données :</strong>
                        <span class="badge badge-<?= $db_connected ? \'success\' : \'danger\' ?>">
                            <?= $db_connected ? \'Connectée\' : \'Erreur\' ?>
                        </span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Outils de diagnostic</h3>
                    <a href="/admin/scanner.php" class="btn btn-outline btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-search"></i> Scanner système
                    </a>
                    <a href="./diagnostic_materiel_fix.php" class="btn btn-info btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-wrench"></i> Diagnostic module
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ROOT_PATH . \'/templates/footer.php\'; ?>

<style>
/* Styles CSS intégrés pour éviter les dépendances */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-box {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #3b82f6;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #3b82f6;
    margin-bottom: 10px;
}

.stat-label {
    color: #64748b;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    margin: 30px 0;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.quick-actions {
    margin: 20px 0;
}

.quick-actions h3 {
    margin-bottom: 15px;
    color: #1e293b;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    display: block;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #3b82f6;
    text-decoration: none;
    color: inherit;
}

.action-card i {
    font-size: 2rem;
    color: #3b82f6;
    margin-bottom: 10px;
    display: block;
}

.action-card h4 {
    margin-bottom: 8px;
    color: #1e293b;
}

.action-card p {
    color: #64748b;
    font-size: 0.9rem;
    margin: 0;
}

.sidebar {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    height: fit-content;
}

.sidebar-section {
    margin-bottom: 25px;
}

.sidebar-section:last-child {
    margin-bottom: 0;
}

.sidebar-section h3 {
    color: #1e293b;
    margin-bottom: 15px;
    font-size: 1.1rem;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 5px;
}

.info-item {
    margin: 10px 0;
    font-size: 0.9rem;
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-success {
    background: #dcfce7;
    color: #059669;
}

.badge-danger {
    background: #fef2f2;
    color: #dc2626;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin: 20px 0;
    border-left: 4px solid;
}

.alert-success {
    background: #ecfdf5;
    border-color: #10b981;
    color: #065f46;
}

.alert-danger {
    background: #fef2f2;
    border-color: #ef4444;
    color: #991b1b;
}

.alert-warning {
    background: #fffbeb;
    border-color: #f59e0b;
    color: #92400e;
}

.alert-info {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1e40af;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-outline {
    background: transparent;
    color: #3b82f6;
    border-color: #3b82f6;
}

.btn-info {
    background: #0ea5e9;
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    text-decoration: none;
    color: inherit;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.module-title h1 {
    color: #1e293b;
    margin-bottom: 5px;
}

.module-description {
    color: #64748b;
    margin: 0;
}

.module-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .module-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .module-actions {
        width: 100%;
        justify-content: stretch;
    }
    
    .module-actions .btn {
        flex: 1;
        text-align: center;
    }
}
</style>
\';
}

function getMaterielCSSTemplate() {
    return \'/* 
 * Styles Module Matériel - Correction automatique
 * Chemin: /public/materiel/assets/css/materiel.css
 * Version: 0.5 beta + build auto - CORRIGÉ
 */

:root {
    --materiel-primary: #3b82f6;
    --materiel-secondary: #64748b;
    --materiel-success: #10b981;
    --materiel-warning: #f59e0b;
    --materiel-danger: #ef4444;
    --materiel-light: #f8fafc;
    --materiel-accent: #1e293b;
}

/* Reset et base */
* {
    box-sizing: border-box;
}

/* Module container */
.materiel-module {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Statistics overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-box {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #f1f5f9;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-box::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--materiel-primary), var(--materiel-success));
}

.stat-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
}

.stat-number {
    font-size: 2.75rem;
    font-weight: 700;
    color: var(--materiel-primary);
    margin-bottom: 0.75rem;
    line-height: 1;
}

.stat-label {
    color: var(--materiel-secondary);
    font-weight: 500;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.stat-label i {
    color: var(--materiel-primary);
    font-size: 1.1em;
}

/* Dashboard grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

/* Quick actions */
.quick-actions {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #f1f5f9;
}

.quick-actions h3 {
    color: var(--materiel-accent);
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-actions h3::before {
    content: "⚡";
    font-size: 1.2em;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-card {
    display: block;
    background: var(--materiel-light);
    padding: 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s ease;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
    border-color: var(--materiel-primary);
    text-decoration: none;
    color: inherit;
}

.action-card:hover::before {
    left: 100%;
}

.action-card i {
    font-size: 2.5rem;
    color: var(--materiel-primary);
    margin-bottom: 1rem;
    display: block;
}

.action-card h4 {
    margin-bottom: 0.5rem;
    color: var(--materiel-accent);
    font-size: 1.1rem;
}

.action-card p {
    color: var(--materiel-secondary);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.4;
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sidebar-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #f1f5f9;
}

.sidebar-section h3 {
    color: var(--materiel-accent);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    border-bottom: 2px solid var(--materiel-primary);
    padding-bottom: 0.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0.75rem 0;
    font-size: 0.9rem;
}

.info-item strong {
    color: var(--materiel-accent);
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.badge-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--materiel-success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.badge-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--materiel-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.badge-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--materiel-warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
    border-left: 4px solid;
    position: relative;
    background-size: 400% 400%;
}

.alert-success {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: var(--materiel-success);
    color: #065f46;
}

.alert-danger {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-color: var(--materiel-danger);
    color: #991b1b;
}

.alert-warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-color: var(--materiel-warning);
    color: #92400e;
}

.alert-info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-color: var(--materiel-primary);
    color: #1e40af;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, var(--materiel-primary) 0%, #2563eb 100%);
    color: white;
}

.btn-outline {
    background: transparent;
    color: var(--materiel-primary);
    border-color: var(--materiel-primary);
}

.btn-info {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    text-decoration: none;
}

.btn-primary:hover {
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.btn-outline:hover {
    background: var(--materiel-primary);
    color: white;
}

/* Module header */
.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f1f5f9;
}

.module-title h1 {
    color: var(--materiel-accent);
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
    font-weight: 700;
}

.module-description {
    color: var(--materiel-secondary);
    margin: 0;
    font-size: 1.1rem;
}

.module-actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .stats-overview {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .module-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .module-actions {
        width: 100%;
        justify-content: stretch;
    }
    
    .module-actions .btn {
        flex: 1;
    }
}

/* Animation utilities */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Loading states */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--materiel-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
\';
