<?php
/**
 * Titre: Module Matériel - Index corrigé (sans redirection)
 * Chemin: /public/materiel/index.php
 * Version: 0.5 beta + build auto - CORRIGÉ
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

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Chargement sécurisé des modules
$modules = [];
if (file_exists(ROOT_PATH . '/config/modules.php')) {
    require_once ROOT_PATH . '/config/modules.php';
}

// Variables pour template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérification authentification simplifiée
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';

// Fonction canAccessModule si manquante
if (!function_exists('canAccessModule')) {
    function canAccessModule($module_id, $module, $user_role) {
        // Accès développement seulement pour admin/dev
        if (isset($module['status']) && $module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
            return false;
        }
        // Vérification rôles spécifiques
        if (isset($module['access_roles']) && !in_array($user_role, $module['access_roles'])) {
            return false;
        }
        return true;
    }
}

// Vérification accès module
$module_data = $modules['materiel'] ?? ['status' => 'active', 'name' => 'Matériel'];
if (!canAccessModule('materiel', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

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
    'total_materiel' => 0,
    'materiel_attribue' => 0,
    'demandes_attente' => 0,
    'maintenance_due' => 0
];

// Récupération stats réelles si BDD OK
if ($db_connected) {
    try {
        // Tables materiel_
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['total_materiel'] = $result['total'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = 'active'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['materiel_attribue'] = $result['attribues'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = 'en_attente'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['demandes_attente'] = $result['en_attente'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['maintenance_due'] = $result['maintenance'] ?? 0;
        }
        
    } catch (Exception $e) {
        error_log("Erreur stats matériel: " . $e->getMessage());
    }
}

$isResponsable = in_array($user_role, ['admin', 'dev']);
$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');

// INCLURE HEADER AVANT TOUT OUTPUT
include ROOT_PATH . '/templates/header.php';
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
            Erreur : <?= htmlspecialchars($error_message ?? 'Inconnue') ?><br>
            <a href="/admin/scanner.php" class="btn btn-sm btn-info" style="margin-top: 10px;">Diagnostic complet</a>
        </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-number"><?= $stats['total_materiel'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-tools"></i> Total matériel
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['materiel_attribue'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-user-check"></i> Matériel attribué
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['demandes_attente'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i> Demandes en attente
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['maintenance_due'] ?></div>
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

                <?php if ($db_connected && $stats['total_materiel'] > 0): ?>
                <div class="alert alert-success">
                    <strong>✅ Module fonctionnel</strong><br>
                    Base de données connectée, <?= $stats['total_materiel'] ?> éléments trouvés.
                </div>
                <?php elseif ($db_connected): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Base de données vide</strong><br>
                    Connexion OK mais aucune donnée trouvée.
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
                        <strong>Module :</strong> Matériel v<?= defined('VERSION') ? VERSION : '0.5' ?>
                    </div>
                    <div class="info-item">
                        <strong>Utilisateur :</strong> <?= htmlspecialchars($current_user['username']) ?>
                    </div>
                    <div class="info-item">
                        <strong>Rôle :</strong> <?= htmlspecialchars($current_user['role'] ?? 'guest') ?>
                    </div>
                    <div class="info-item">
                        <strong>Base de données :</strong>
                        <span class="badge badge-<?= $db_connected ? 'success' : 'danger' ?>">
                            <?= $db_connected ? 'Connectée' : 'Erreur' ?>
                        </span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Outils de diagnostic</h3>
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

<?php include ROOT_PATH . '/templates/footer.php'; ?>

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
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    background: #f8fafc;
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
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sidebar-section h3 {
    color: #1e293b;
    margin-bottom: 15px;
    font-size: 1.1rem;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 5px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0;
    font-size: 0.9rem;
}

.info-item strong {
    color: #1e293b;
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
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
    justify-content: center;
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

.btn-outline:hover {
    background: #3b82f6;
    color: white;
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
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
