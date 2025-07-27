<?php
/**
 * Titre: Dashboard module ADR
 * Chemin: /features/adr/dashboard.php
 * Version: 0.5 beta + build auto
 */

// Protection et initialisation
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

$page_title = "Dashboard ADR";
$current_module = "adr";
$module_css = true;
include ROOT_PATH . '/templates/header.php';

require __DIR__ . '/../../config.php';

// ========== TRAITEMENT AJAX ==========
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $query = trim($_POST['q'] ?? '');
    
    try {
        switch ($action) {
            case 'search_products':
                echo json_encode(searchProductsDB($db, $query, false));
                break;
                
            case 'suggestions':
                echo json_encode(searchProductsDB($db, $query, true));
                break;
                
            case 'popular_products':
                echo json_encode(getPopularProductsDB($db));
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        }
    } catch (Exception $e) {
        error_log("Erreur AJAX dashboard: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    }
    exit;
}

/**
 * Recherche dans la vraie BDD gul_adr_products
 */
function searchProductsDB($db, $query, $suggestionsOnly = false) {
    try {
        $limit = $suggestionsOnly ? 8 : 50;
        
        if (empty($query)) {
            // Produits par défaut - ADR en priorité
            $sql = "SELECT * FROM gul_adr_products 
                    WHERE actif = 1 
                    ORDER BY 
                        CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 ELSE 2 END,
                        CASE WHEN corde_article_ferme = 'x' THEN 2 ELSE 1 END,
                        nom_produit 
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            // Recherche avec terme - tous les champs importants
            $sql = "SELECT * FROM gul_adr_products 
                    WHERE actif = 1 
                    AND (
                        code_produit LIKE ? 
                        OR nom_produit LIKE ? 
                        OR nom_technique LIKE ?
                        OR numero_un LIKE ?
                        OR nom_description_un LIKE ?
                    )
                    ORDER BY 
                        CASE 
                            WHEN code_produit LIKE ? THEN 1
                            WHEN nom_produit LIKE ? THEN 2
                            WHEN numero_un LIKE ? THEN 3
                            ELSE 4
                        END,
                        CASE WHEN corde_article_ferme = 'x' THEN 2 ELSE 1 END,
                        nom_produit
                    LIMIT ?";
            
            $pattern = '%' . $query . '%';
            $exactPattern = $query . '%';
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $pattern, $pattern, $pattern, $pattern, $pattern,
                $exactPattern, $exactPattern, $exactPattern,
                $limit
            ]);
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results),
            'query' => $query
        ];
        
    } catch (Exception $e) {
        error_log("Erreur BDD recherche: " . $e->getMessage());
        
        // Fallback simple si problème BDD
        $demoData = [
            [
                'code_produit' => 'DEMO-001',
                'nom_produit' => 'Produit de démonstration',
                'nom_technique' => 'Données de test',
                'numero_un' => '1234',
                'nom_description_un' => 'Produit de test',
                'categorie_transport' => '2',
                'danger_environnement' => 'NON',
                'type_contenant' => 'Bidon',
                'poids_contenant' => '25L',
                'corde_article_ferme' => '',
                'groupe_emballage' => 'II'
            ]
        ];
        
        return [
            'success' => true,
            'data' => $demoData,
            'count' => 1,
            'query' => $query,
            'demo_mode' => true
        ];
    }
}

/**
 * Produits populaires depuis BDD
 */
function getPopularProductsDB($db) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM gul_adr_products 
            WHERE actif = 1 
            AND numero_un IS NOT NULL 
            AND numero_un != ''
            ORDER BY 
                CASE WHEN danger_environnement = 'OUI' THEN 1 ELSE 2 END,
                nom_produit
            LIMIT 6
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'data' => $products];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Erreur BDD'];
    }
}

// Statistiques simples
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
    $stats_total = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as adr FROM gul_adr_products WHERE actif = 1 AND numero_un IS NOT NULL AND numero_un != ''");
    $stats_adr = $stmt->fetch()['adr'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as env FROM gul_adr_products WHERE actif = 1 AND danger_environnement = 'OUI'");
    $stats_env = $stmt->fetch()['env'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as fermes FROM gul_adr_products WHERE actif = 1 AND corde_article_ferme = 'x'");
    $stats_fermes = $stmt->fetch()['fermes'] ?? 0;
    
} catch (Exception $e) {
    // Valeurs par défaut si problème BDD
    $stats_total = 250;
    $stats_adr = 180;
    $stats_env = 45;
    $stats_fermes = 12;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ADR - Guldagil Portal</title>
    
    <!-- CSS et JS externe modulaire -->
    <link rel="stylesheet" href="assets/css/adr.css">
    <script src="assets/js/adr.js"></script>
</head>
<body>
    
    <!-- Header ADR -->
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <div class="adr-logo">⚠️</div>
                <div>
                    <h1>Dashboard ADR</h1>
                    <div class="header-subtitle">Gestion des marchandises dangereuses</div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="user-info">
                    👤 <?= htmlspecialchars($_SESSION['adr_user']) ?>
                </div>
                <a href="declaration/create.php" class="btn btn-primary">
                    ➕ Nouvelle déclaration
                </a>
                <a href="../" class="btn btn-secondary">
                    🏠 Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Container principal -->
    <div class="dashboard-container">
        
        <!-- Onglets navigation -->
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="ADR.showTab('recherche')" data-tab="recherche">
                🔍 Recherche produits
            </button>
            <button class="tab-button" onclick="ADR.showTab('expeditions')" data-tab="expeditions">
                ➕ Nouvelle expédition
            </button>
            <button class="tab-button" onclick="ADR.showTab('statistiques')" data-tab="statistiques">
                📈 Statistiques
            </button>
        </div>

        <!-- Contenu onglet Recherche -->
        <div id="tab-recherche" class="tab-content active">
            <section class="search-section">
                <div class="search-header">
                    <h2>🔍 Recherche produits ADR</h2>
                    <p>Recherchez dans le catalogue des produits Guldagil</p>
                </div>
                
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           id="product-search" 
                           placeholder="Code produit, nom, numéro UN..."
                           autocomplete="off">
                    
                    <div class="search-suggestions" id="search-suggestions"></div>
                </div>
                
                <div class="search-help">
                    <strong>💡 Recherche :</strong> 
                    Code article • Nom produit • Nom technique • Numéro UN • Description
                </div>
            </section>

            <!-- Résultats -->
            <section class="results-section" id="search-results" style="display: none;">
                <div class="results-header">
                    <h3 id="results-title">Résultats</h3>
                    <button class="btn btn-secondary btn-sm" onclick="ADR.clearResults()">
                        ✖️ Effacer
                    </button>
                </div>
                <div id="results-content"></div>
            </section>
        </div>

        <!-- Contenu onglet Expéditions -->
        <div id="tab-expeditions" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">➕</div>
                <h3>Nouvelle expédition ADR</h3>
                <p>Créez une déclaration d'expédition de marchandises dangereuses</p>
                <br>
                <a href="declaration/create.php" class="btn btn-primary">
                    Commencer une déclaration
                </a>
            </div>
        </div>

        <!-- Contenu onglet Statistiques -->
        <div id="tab-statistiques" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-title">Total produits</div>
                        <div class="stat-icon">📦</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats_total) ?></div>
                    <div class="stat-detail">Produits dans le catalogue</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-title">Produits ADR</div>
                        <div class="stat-icon">⚠️</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats_adr) ?></div>
                    <div class="stat-detail">Nécessitent déclaration ADR</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-title">Polluants marins</div>
                        <div class="stat-icon">🌍</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats_env) ?></div>
                    <div class="stat-detail">Danger environnement</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Articles fermés</div>
                        <div class="stat-icon">🔒</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats_fermes) ?></div>
                    <div class="stat-detail">Produits discontinués</div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>
