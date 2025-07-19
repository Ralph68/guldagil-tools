<?php
/**
 * Titre: Rapports et analyses EPI
 * Chemin: /public/epi/reports.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/epimanager.php';

session_start();
$page_title = 'Rapports EPI';

try {
    $epiManager = new EpiManager();
    
    // Paramètres
    $reportType = $_GET['type'] ?? 'overview';
    $period = $_GET['period'] ?? '30';
    $employeeId = $_GET['employee_id'] ?? null;
    $categoryId = $_GET['category_id'] ?? null;
    
    // Données pour les filtres
    $employees = $epiManager->getEmployees();
    $categories = $epiManager->getCategories();
    
    // Génération du rapport selon le type
    $reportData = generateReport($reportType, $period, $employeeId, $categoryId);
    
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    $reportData = [];
}

/**
 * Générer les données du rapport
 */
function generateReport($type, $period, $employeeId = null, $categoryId = null) {
    global $db;
    
    $data = [];
    $dateFilter = getDateFilter($period);
    
    switch ($type) {
        case 'overview':
            $data = getOverviewReport($dateFilter);
            break;
        case 'inventory':
            $data = getInventoryReport($categoryId);
            break;
        case 'employee':
            $data = getEmployeeReport($employeeId, $dateFilter);
            break;
        case 'expiry':
            $data = getExpiryReport($dateFilter);
            break;
        case 'activity':
            $data = getActivityReport($dateFilter);
            break;
        default:
            $data = getOverviewReport($dateFilter);
    }
    
    return $data;
}

function getDateFilter($period) {
    switch ($period) {
        case '7': return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
        case '30': return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        case '90': return "DATE_SUB(NOW(), INTERVAL 90 DAY)";
        case '365': return "DATE_SUB(NOW(), INTERVAL 365 DAY)";
        default: return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

function getOverviewReport($dateFilter) {
    global $db;
    
    // Statistiques générales
    $stats = [];
    
    // Total employés et équipés
    $stmt = $db->query("SELECT COUNT(*) as total FROM epi_employees WHERE status = 'active'");
    $stats['total_employees'] = $stmt->fetchColumn();
    
    $stmt = $db->query("
        SELECT COUNT(DISTINCT e.id) as equipped 
        FROM epi_employees e 
        JOIN epi_assignments a ON e.id = a.employee_id 
        WHERE e.status = 'active' AND a.status = 'active'
    ");
    $stats['equipped_employees'] = $stmt->fetchColumn();
    
    // Attributions par période
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM epi_assignments 
        WHERE assigned_date >= $dateFilter
    ");
    $stmt->execute();
    $stats['assignments_period'] = $stmt->fetchColumn();
    
    // EPI expirés
    $stmt = $db->query("
        SELECT COUNT(*) as expired 
        FROM epi_assignments 
        WHERE status = 'active' AND expiry_date < NOW()
    ");
    $stats['expired_assignments'] = $stmt->fetchColumn();
    
    // EPI à expirer bientôt (15 jours)
    $stmt = $db->query("
        SELECT COUNT(*) as urgent 
        FROM epi_assignments 
        WHERE status = 'active' 
        AND expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 DAY)
    ");
    $stats['urgent_assignments'] = $stmt->fetchColumn();
    
    // Répartition par catégorie
    $stmt = $db->query("
        SELECT 
            c.name as category,
            COUNT(a.id) as count
        FROM epi_categories c
        LEFT JOIN epi_assignments a ON c.id = a.category_id AND a.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.id, c.name
        ORDER BY count DESC
    ");
    $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Répartition par département
    $stmt = $db->query("
        SELECT 
            COALESCE(e.department, 'Non défini') as department,
            COUNT(a.id) as count
        FROM epi_employees e
        LEFT JOIN epi_assignments a ON e.id = a.employee_id AND a.status = 'active'
        WHERE e.status = 'active'
        GROUP BY e.department
        ORDER BY count DESC
    ");
    $stats['by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

function getInventoryReport($categoryId = null) {
    global $db;
    
    $sql = "
        SELECT 
            c.name as category_name,
            i.quantity_total,
            i.quantity_available,
            i.minimum_stock,
            i.location,
            (i.quantity_total - i.quantity_available) as assigned_count,
            CASE 
                WHEN i.quantity_available <= 0 THEN 'Rupture'
                WHEN i.quantity_available <= i.minimum_stock THEN 'Stock bas'
                ELSE 'OK'
            END as stock_status,
            ROUND((i.quantity_available / i.quantity_total) * 100, 1) as availability_percentage
        FROM epi_inventory i
        JOIN epi_categories c ON i.category_id = c.id
        WHERE i.status = 'active'
    ";
    
    $params = [];
    if ($categoryId) {
        $sql .= " AND c.id = :category_id";
        $params['category_id'] = $categoryId;
    }
    
    $sql .= " ORDER BY c.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEmployeeReport($employeeId = null, $dateFilter) {
    global $db;
    
    $sql = "
        SELECT 
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.department,
            e.hire_date,
            COUNT(a.id) as total_assignments,
            COUNT(CASE WHEN a.expiry_date < NOW() THEN 1 END) as expired_count,
            COUNT(CASE WHEN a.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 DAY) THEN 1 END) as urgent_count,
            MAX(a.assigned_date) as last_assignment
        FROM epi_employees e
        LEFT JOIN epi_assignments a ON e.id = a.employee_id AND a.status = 'active'
        WHERE e.status = 'active'
    ";
    
    $params = [];
    if ($employeeId) {
        $sql .= " AND e.id = :employee_id";
        $params['employee_id'] = $employeeId;
    }
    
    $sql .= " GROUP BY e.id ORDER BY e.last_name, e.first_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getExpiryReport($dateFilter) {
    global $db;
    
    return [
        'expired' => $db->query("
            SELECT 
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.expiry_date,
                DATEDIFF(NOW(), a.expiry_date) as days_expired
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.status = 'active' AND a.expiry_date < NOW()
            ORDER BY a.expiry_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC),
        
        'expiring_soon' => $db->query("
            SELECT 
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.expiry_date,
                DATEDIFF(a.expiry_date, NOW()) as days_remaining
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.status = 'active' 
            AND a.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY a.expiry_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function getActivityReport($dateFilter) {
    global $db;
    
    return [
        'recent_assignments' => $db->prepare("
            SELECT 
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.assigned_date,
                a.expiry_date
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.assigned_date >= $dateFilter
            ORDER BY a.assigned_date DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC),
        
        'monthly_stats' => $db->query("
            SELECT 
                DATE_FORMAT(assigned_date, '%Y-%m') as month,
                COUNT(*) as assignments_count
            FROM epi_assignments
            WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(assigned_date, '%Y-%m')
            ORDER BY month DESC
        ")->fetchAll(PDO::FETCH_ASSOC)
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Portail Guldagil</title>
    <link rel="stylesheet" href="assets/css/epi.css">
    <style>
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
        }
        .progress-bar {
            background: #e5e7eb;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--epi-primary);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--epi-gray);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header class="epi-header">
        <div class="header-container">
            <h1>📊 Rapports EPI</h1>
            <p>Analyses et statistiques des équipements de protection</p>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="./index.php">🛡️ EPI</a>
            <span>›</span>
            <span>Rapports</span>
        </nav>

        <!-- Filtres -->
        <div class="epi-card mb-3">
            <div class="d-flex justify-between align-center">
                <h3>🔍 Filtres et options</h3>
                <div class="d-flex gap-2">
                    <a href="?type=overview&period=<?= $period ?>" class="btn <?= $reportType === 'overview' ? 'btn-primary' : 'btn' ?>" style="<?= $reportType !== 'overview' ? 'background: var(--epi-light-gray); color: var(--epi-gray);' : '' ?>">📋 Vue d'ensemble</a>
                    <a href="?type=inventory&period=<?= $period ?>" class="btn <?= $reportType === 'inventory' ? 'btn-primary' : 'btn' ?>" style="<?= $reportType !== 'inventory' ? 'background: var(--epi-light-gray); color: var(--epi-gray);' : '' ?>">📦 Inventaire</a>
                    <a href="?type=expiry&period=<?= $period ?>" class="btn <?= $reportType === 'expiry' ? 'btn-primary' : 'btn' ?>" style="<?= $reportType !== 'expiry' ? 'background: var(--epi-light-gray); color: var(--epi-gray);' : '' ?>">⏰ Expirations</a>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-2">
                <select onchange="updatePeriod(this.value)" class="form-select">
                    <option value="7" <?= $period === '7' ? 'selected' : '' ?>>7 derniers jours</option>
                    <option value="30" <?= $period === '30' ? 'selected' : '' ?>>30 derniers jours</option>
                    <option value="90" <?= $period === '90' ? 'selected' : '' ?>>3 derniers mois</option>
                    <option value="365" <?= $period === '365' ? 'selected' : '' ?>>12 derniers mois</option>
                </select>
                
                <?php if ($reportType === 'employee'): ?>
                    <select onchange="updateEmployee(this.value)" class="form-select">
                        <option value="">Tous les employés</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $employeeId == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['last_name'] . ' ' . $emp['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                
                <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimer</button>
                <button onclick="exportReport()" class="btn btn-success">📄 Exporter CSV</button>
            </div>
        </div>

        <?php if ($reportType === 'overview'): ?>
            <!-- Rapport vue d'ensemble -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $reportData['total_employees'] ?></div>
                    <div class="stat-label">Total employés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $reportData['equipped_employees'] ?></div>
                    <div class="stat-label">Employés équipés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $reportData['assignments_period'] ?></div>
                    <div class="stat-label">Attributions (période)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--epi-danger);"><?= $reportData['expired_assignments'] ?></div>
                    <div class="stat-label">EPI expirés</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Répartition par catégorie -->
                <div class="epi-card">
                    <h3>📊 Répartition par catégorie</h3>
                    <?php if (!empty($reportData['by_category'])): ?>
                        <?php $maxCount = max(array_column($reportData['by_category'], 'count')); ?>
                        <?php foreach ($reportData['by_category'] as $item): ?>
                            <div style="margin-bottom: 1rem;">
                                <div class="d-flex justify-between">
                                    <span><?= htmlspecialchars($item['category']) ?></span>
                                    <span><strong><?= $item['count'] ?></strong></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $maxCount > 0 ? ($item['count'] / $maxCount) * 100 : 0 ?>%; background: var(--epi-primary);"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>

                <!-- Répartition par département -->
                <div class="epi-card">
                    <h3>🏢 Répartition par département</h3>
                    <?php if (!empty($reportData['by_department'])): ?>
                        <?php $maxCount = max(array_column($reportData['by_department'], 'count')); ?>
                        <?php foreach ($reportData['by_department'] as $item): ?>
                            <div style="margin-bottom: 1rem;">
                                <div class="d-flex justify-between">
                                    <span><?= htmlspecialchars($item['department']) ?></span>
                                    <span><strong><?= $item['count'] ?></strong></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $maxCount > 0 ? ($item['count'] / $maxCount) * 100 : 0 ?>%; background: var(--epi-secondary);"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($reportType === 'inventory'): ?>
            <!-- Rapport inventaire -->
            <div class="epi-card">
                <h3>📦 État des stocks</h3>
                <?php if (!empty($reportData)): ?>
                    <div style="overflow-x: auto;">
                        <table class="epi-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Stock total</th>
                                    <th>Disponible</th>
                                    <th>Attribué</th>
                                    <th>Stock min.</th>
                                    <th>Disponibilité</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['category_name']) ?></strong></td>
                                        <td class="text-center"><?= $item['quantity_total'] ?></td>
                                        <td class="text-center">
                                            <span style="color: <?= $item['quantity_available'] > 0 ? 'var(--epi-success)' : 'var(--epi-danger)' ?>;">
                                                <?= $item['quantity_available'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= $item['assigned_count'] ?></td>
                                        <td class="text-center"><?= $item['minimum_stock'] ?></td>
                                        <td class="text-center">
                                            <div class="progress-bar" style="width: 100px; height: 16px;">
                                                <div class="progress-fill" style="width: <?= $item['availability_percentage'] ?>%; background: <?= $item['availability_percentage'] < 20 ? 'var(--epi-danger)' : ($item['availability_percentage'] < 50 ? 'var(--epi-warning)' : 'var(--epi-success)') ?>;"></div>
                                            </div>
                                            <small><?= $item['availability_percentage'] ?>%</small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match($item['stock_status']) {
                                                'Rupture' => 'status-expired',
                                                'Stock bas' => 'status-urgent',
                                                default => 'status-ok'
                                            };
                                            ?>
                                            <span class="status <?= $statusClass ?>">
                                                <?= $item['stock_status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Aucun stock disponible</p>
                <?php endif; ?>
            </div>

        <?php elseif ($reportType === 'expiry'): ?>
            <!-- Rapport expirations -->
            <div class="dashboard-grid">
                <div class="epi-card">
                    <h3>❌ EPI expirés (<?= count($reportData['expired']) ?>)</h3>
                    <?php if (!empty($reportData['expired'])): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($reportData['expired'] as $item): ?>
                                <div class="alert-item alert-expired">
                                    <span>⚠️</span>
                                    <div>
                                        <div style="font-weight:600;"><?= htmlspecialchars($item['employee_name']) ?></div>
                                        <div style="font-size:0.85rem;color:#666;">
                                            <?= htmlspecialchars($item['category_name']) ?> - 
                                            Expiré depuis <?= $item['days_expired'] ?> jour<?= $item['days_expired'] > 1 ? 's' : '' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--epi-success);">✅ Aucun EPI expiré</p>
                    <?php endif; ?>
                </div>

                <div class="epi-card">
                    <h3>⏰ À expirer bientôt (<?= count($reportData['expiring_soon']) ?>)</h3>
                    <?php if (!empty($reportData['expiring_soon'])): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($reportData['expiring_soon'] as $item): ?>
                                <div class="alert-item alert-urgent">
                                    <span>📅</span>
                                    <div>
                                        <div style="font-weight:600;"><?= htmlspecialchars($item['employee_name']) ?></div>
                                        <div style="font-size:0.85rem;color:#666;">
                                            <?= htmlspecialchars($item['category_name']) ?> - 
                                            Expire dans <?= $item['days_remaining'] ?> jour<?= $item['days_remaining'] > 1 ? 's' : '' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--epi-success);">✅ Aucune expiration prochaine</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="epi-card mt-3">
            <h3>⚡ Actions rapides</h3>
            <div class="quick-actions">
                <a href="assignments.php" class="action-btn">🔄 Gestion attributions</a>
                <a href="inventory.php" class="action-btn">📦 Gestion inventaire</a>
                <a href="employees.php" class="action-btn">👥 Gestion employés</a>
                <a href="./index.php" class="action-btn">🏠 Tableau de bord</a>
            </div>
        </div>

        <!-- Retour -->
        <div class="text-center mt-3">
            <a href="./index.php" style="color: var(--epi-primary);">← Retour au tableau de bord EPI</a>
        </div>
    </main>

    <script src="assets/js/epi.js"></script>
    <script>
        function updatePeriod(period) {
            const url = new URL(window.location);
            url.searchParams.set('period', period);
            window.location.href = url.toString();
        }

        function updateEmployee(employeeId) {
            const url = new URL(window.location);
            if (employeeId) {
                url.searchParams.set('employee_id', employeeId);
            } else {
                url.searchParams.delete('employee_id');
            }
            window.location.href = url.toString();
        }

        function exportReport() {
            // Simulation export CSV
            window.epiManager.showNotification('Export en cours...', 'info');
            
            // Ici on pourrait faire un appel AJAX vers un script d'export
            setTimeout(() => {
                window.epiManager.showNotification('Rapport exporté avec succès', 'success');
            }, 1500);
        }

        // Animation des barres de progression
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, index * 100);
            });
        });
    </script>
</body>
</html>
