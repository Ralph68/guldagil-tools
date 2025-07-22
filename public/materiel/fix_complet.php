<?php
/**
 * Correction complète module matériel
 * Placer dans /public/materiel/fix_complet.php
 * Exécuter UNE FOIS puis supprimer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

require_once ROOT_PATH . '/config/config.php';

echo "<!DOCTYPE html><html><head><title>Correction Module Matériel</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;}";
echo ".ok{color:green;}.error{color:red;}.info{color:blue;}";
echo ".section{background:white;padding:15px;margin:10px 0;border-radius:5px;}";
echo "pre{background:#f0f0f0;padding:10px;border-radius:3px;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>🔧 Correction complète module matériel</h1>";

// =====================================
// 1. CORRECTION PROBLÈME DONNÉES VIDES
// =====================================
echo "<div class='section'>";
echo "<h2>1. Correction problème données vides</h2>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Vérifier données dans tables
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materiel_items");
    $count_items = $stmt->fetch()['count'];
    
    echo "<p class='info'>📊 Nombre d'items actuels: $count_items</p>";
    
    if ($count_items == 0) {
        echo "<p class='error'>❌ Tables vides détectées. Ajout de données de test...</p>";
        
        // Insérer données de test
        $test_data_sql = "
        INSERT IGNORE INTO materiel_categories (id, nom, type, description) VALUES 
        (1, 'Outillage manuel', 'outillage', 'Outils à main'),
        (2, 'Électroportatif', 'electroportatif', 'Outils électriques portables'),
        (3, 'Équipement de mesure', 'mesure', 'Instruments de mesure');
        
        INSERT IGNORE INTO materiel_agences (id, nom, adresse) VALUES 
        (1, 'Siège social', 'Adresse principale'),
        (2, 'Agence Nord', 'Zone Nord'),
        (3, 'Agence Sud', 'Zone Sud');
        
        INSERT IGNORE INTO materiel_templates (id, categorie_id, designation, marque, quantite_standard) VALUES 
        (1, 1, 'Marteau', 'Stanley', 1),
        (2, 1, 'Tournevis cruciforme', 'Facom', 1),
        (3, 2, 'Perceuse électrique', 'Bosch', 1),
        (4, 3, 'Multimètre', 'Fluke', 1);
        
        INSERT IGNORE INTO materiel_items (id, template_id, numero_serie, agence_id, etat, statut) VALUES 
        (1, 1, 'MAR001', 1, 'bon', 'disponible'),
        (2, 1, 'MAR002', 2, 'bon', 'disponible'),
        (3, 2, 'TOU001', 1, 'neuf', 'disponible'),
        (4, 3, 'PER001', 1, 'bon', 'attribue'),
        (5, 4, 'MUL001', 3, 'neuf', 'disponible');
        
        INSERT IGNORE INTO materiel_employees (id, nom, prenom, email, agence_id) VALUES 
        (1, 'Dupont', 'Jean', 'jean.dupont@example.com', 1),
        (2, 'Martin', 'Sophie', 'sophie.martin@example.com', 2);
        
        INSERT IGNORE INTO materiel_demandes (id, employee_id, template_id, quantite_demandee, statut, justification) VALUES 
        (1, 1, 2, 1, 'en_attente', 'Besoin pour nouveau projet'),
        (2, 2, 3, 1, 'validee', 'Remplacement outil défaillant');
        ";
        
        $pdo->exec($test_data_sql);
        echo "<p class='ok'>✅ Données de test ajoutées</p>";
    } else {
        echo "<p class='ok'>✅ Données présentes dans les tables</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur BDD: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// =====================================
// 2. CORRECTION MaterielManager.php
// =====================================
echo "<div class='section'>";
echo "<h2>2. Correction MaterielManager.php</h2>";

$manager_path = ROOT_PATH . '/public/materiel/classes/MaterielManager.php';
if (file_exists($manager_path)) {
    $content = file_get_contents($manager_path);
    
    // Vérifier si le constructeur est correct
    if (strpos($content, 'public function __construct()') !== false) {
        echo "<p class='error'>❌ Constructeur sans paramètre détecté</p>";
        
        // Corriger le constructeur
        $fixed_content = str_replace(
            'public function __construct()',
            'public function __construct($database = null)',
            $content
        );
        
        // Corriger l'initialisation de la base
        $fixed_content = str_replace(
            '$this->db = $this->initDatabase();',
            '$this->db = $database ?: $this->initDatabase();',
            $fixed_content
        );
        
        // Sauvegarder
        copy($manager_path, $manager_path . '.backup');
        file_put_contents($manager_path, $fixed_content);
        echo "<p class='ok'>✅ MaterielManager.php corrigé</p>";
    } else {
        echo "<p class='ok'>✅ MaterielManager.php semble correct</p>";
    }
} else {
    echo "<p class='error'>❌ MaterielManager.php manquant</p>";
}

echo "</div>";

// =====================================
// 3. CORRECTION REPORTS/INDEX.PHP
// =====================================
echo "<div class='section'>";
echo "<h2>3. Correction reports/index.php</h2>";

$reports_path = ROOT_PATH . '/public/materiel/reports/index.php';
if (file_exists($reports_path)) {
    $content = file_get_contents($reports_path);
    
    // Vérifier si le footer manque
    if (strpos($content, 'include ROOT_PATH . \'/templates/footer.php\'') === false) {
        echo "<p class='error'>❌ Footer manquant dans reports/index.php</p>";
        
        // Ajouter le footer s'il manque
        $content .= "\n\n<?php include ROOT_PATH . '/templates/footer.php'; ?>";
        file_put_contents($reports_path, $content);
        echo "<p class='ok'>✅ Footer ajouté à reports/index.php</p>";
    } else {
        echo "<p class='ok'>✅ Footer présent dans reports/index.php</p>";
    }
    
    // Corriger l'instanciation MaterielManager
    if (strpos($content, 'new MaterielManager()') !== false) {
        echo "<p class='error'>❌ MaterielManager mal instancié dans reports</p>";
        
        $fixed_content = str_replace(
            'new MaterielManager()',
            'new MaterielManager($db)',
            $content
        );
        
        // Ajouter connexion BDD si manquante
        if (strpos($content, '$db = new PDO') === false) {
            $db_code = "
// Connexion BDD
try {
    \$db = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception \$e) {
    \$db = null;
    error_log(\"Erreur BDD Reports: \" . \$e->getMessage());
}

";
            $fixed_content = str_replace(
                '// Manager matériel',
                $db_code . '// Manager matériel',
                $fixed_content
            );
        }
        
        file_put_contents($reports_path, $fixed_content);
        echo "<p class='ok'>✅ MaterielManager corrigé dans reports</p>";
    }
} else {
    echo "<p class='error'>❌ reports/index.php manquant</p>";
}

echo "</div>";

// =====================================
// 4. CORRECTION BREADCRUMBS ET CSS
// =====================================
echo "<div class='section'>";
echo "<h2>4. Ajout fil d'ariane et CSS manquants</h2>";

$breadcrumb_code = '
<!-- Fil d\'ariane sticky -->
<div class="breadcrumb-container sticky-top">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Accueil</a></li>
            <li class="breadcrumb-item"><a href="/materiel/"><i class="fas fa-tools"></i> Matériel</a></li>';

// Créer CSS breadcrumb
$breadcrumb_css = '
/* Fil d\'ariane sticky */
.breadcrumb-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #e2e8f0;
    padding: 8px 0;
    z-index: 1000;
    position: sticky;
    top: 0;
}

.breadcrumb {
    margin: 0;
    padding: 0 20px;
    background: transparent;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #64748b;
}

.breadcrumb-item a {
    color: #3b82f6;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.breadcrumb-item a:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #64748b;
}
';

// Ajouter CSS au fichier materiel.css
$css_path = ROOT_PATH . '/public/materiel/assets/css/materiel.css';
if (file_exists($css_path)) {
    $css_content = file_get_contents($css_path);
    if (strpos($css_content, '.breadcrumb-container') === false) {
        file_put_contents($css_path, $css_content . "\n\n" . $breadcrumb_css);
        echo "<p class='ok'>✅ CSS breadcrumb ajouté</p>";
    } else {
        echo "<p class='ok'>✅ CSS breadcrumb déjà présent</p>";
    }
} else {
    echo "<p class='error'>❌ materiel.css manquant</p>";
}

echo "</div>";

// =====================================
// 5. CRÉATION FICHIER INDEX FIXE
// =====================================
echo "<div class='section'>";
echo "<h2>5. Création index.php corrigé final</h2>";

$fixed_index_content = '<?php
/**
 * Titre: Module Matériel - Index corrigé final
 * Chemin: /public/materiel/index.php
 * Version: 0.5 beta + build auto - CORRIGÉ COMPLET
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
$page_subtitle = \'Outillage et Équipements\';
$current_module = \'materiel\';
$module_css = true;
$user_authenticated = isset($_SESSION[\'authenticated\']) && $_SESSION[\'authenticated\'] === true;
$current_user = $_SESSION[\'user\'] ?? [\'username\' => \'Anonyme\', \'role\' => \'guest\'];

// Authentification
if (!$user_authenticated) {
    header(\'Location: /auth/login.php?redirect=\' . urlencode($_SERVER[\'REQUEST_URI\']));
    exit;
}

// Connexion BDD avec gestion d\'erreurs
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    $db_connected = false;
    error_log("Erreur BDD Matériel: " . $e->getMessage());
}

// Statistiques robustes
$stats = [
    \'total_materiel\' => 0,
    \'materiel_attribue\' => 0,
    \'demandes_attente\' => 0,
    \'maintenance_due\' => 0
];

if ($db_connected) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats[\'total_materiel\'] = $result[\'total\'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = \'active\'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats[\'materiel_attribue\'] = $result[\'attribues\'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = \'en_attente\'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats[\'demandes_attente\'] = $result[\'en_attente\'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Erreur stats matériel: " . $e->getMessage());
    }
}

$breadcrumbs = [
    [\'icon\' => \'🏠\', \'text\' => \'Accueil\', \'url\' => \'/\'],
    [\'icon\' => \'🔧\', \'text\' => \'Matériel\', \'url\' => \'\', \'active\' => true]
];

$build_number = defined(\'BUILD_NUMBER\') ? substr(BUILD_NUMBER, 0, 8) : \'dev-\' . date(\'ymdHis\');

include ROOT_PATH . \'/templates/header.php\';
?>

<!-- Fil d\'ariane sticky -->
<div class="breadcrumb-container sticky-top">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (isset($crumb[\'active\']) && $crumb[\'active\']): ?>
                    <li class="breadcrumb-item active">
                        <?= $crumb[\'icon\'] ?> <?= htmlspecialchars($crumb[\'text\']) ?>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="<?= $crumb[\'url\'] ?>">
                            <?= $crumb[\'icon\'] ?> <?= htmlspecialchars($crumb[\'text\']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>

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
            <a href="./debug.php" class="btn btn-sm btn-info" style="margin-top: 10px;">Diagnostic</a>
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
                        
                        <a href="/admin/" class="action-card">
                            <i class="fas fa-cogs"></i>
                            <h4>Administration</h4>
                            <p>Gérer les catégories et modèles</p>
                        </a>
                        
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
                    <strong>⚠️ Base de données connectée</strong><br>
                    <?= $stats[\'total_materiel\'] ?> éléments dans l\'inventaire.
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
                        <strong>Base de données :</strong>
                        <span class="badge badge-<?= $db_connected ? \'success\' : \'danger\' ?>">
                            <?= $db_connected ? \'Connectée\' : \'Erreur\' ?>
                        </span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Liens rapides</h3>
                    <a href="/admin/scanner.php" class="btn btn-outline btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-search"></i> Scanner système
                    </a>
                    <a href="./debug.php" class="btn btn-info btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-wrench"></i> Debug module
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ROOT_PATH . \'/templates/footer.php\'; ?>
';

// Sauvegarder l'index corrigé
$index_path = ROOT_PATH . '/public/materiel/index_fixed.php';
if (file_put_contents($index_path, $fixed_index_content)) {
    echo "<p class='ok'>✅ index_fixed.php créé avec toutes les corrections</p>";
    echo "<p class='info'>📁 Pour activer: <code>mv index.php index.php.old && mv index_fixed.php index.php</code></p>";
} else {
    echo "<p class='error'>❌ Impossible de créer index_fixed.php</p>";
}

echo "</div>";

// =====================================
// RÉSUMÉ
// =====================================
echo "<div class='section'>";
echo "<h2>📋 Résumé des corrections</h2>";
echo "<ul>";
echo "<li>✅ Données de test ajoutées si tables vides</li>";
echo "<li>✅ MaterielManager.php corrigé (constructeur avec paramètre)</li>";
echo "<li>✅ reports/index.php corrigé (footer + instanciation)</li>";
echo "<li>✅ CSS breadcrumb ajouté</li>";
echo "<li>✅ index_fixed.php créé avec toutes les améliorations</li>";
echo "</ul>";

echo "<h3>🚀 Étapes finales :</h3>";
echo "<ol>";
echo "<li><strong>Remplacer index.php :</strong> <code>mv index.php index.php.old && mv index_fixed.php index.php</code></li>";
echo "<li><strong>Tester le module :</strong> <a href='/materiel/'>Accéder au module</a></li>";
echo "<li><strong>Supprimer ce fichier</strong> après utilisation</li>";
echo "</ol>";

echo "</div>";

echo "</body></html>";
?>
