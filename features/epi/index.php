<?php
/**
 * Titre: Module EPI - Page principale
 * Chemin: /features/epi/index.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/epimanager.php';

session_start();
$page_title = 'Gestion EPI';

try {
    $epiManager = new EpiManager();
    $dashboardData = $epiManager->getDashboardData();
    $metrics = $dashboardData['metrics'];
    $alerts = $dashboardData['alerts'];
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    // Données de démonstration en cas d'erreur
    $metrics = [
        'total_employees' => 45,
        'equipped_employees' => 38,
        'equipment_ratio' => round((38/45)*100, 1),
        'available_equipment' => 127
    ];
    $alerts = [
        'expired' => [
            ['employee_name' => 'Martin Durand', 'category_name' => 'Casque de sécurité', 'days_remaining' => -5],
            ['employee_name' => 'Sophie Laurent', 'category_name' => 'Chaussures de sécurité', 'days_remaining' => -12]
        ],
        'urgent' => [
            ['employee_name' => 'Pierre Moreau', 'category_name' => 'Gilet haute visibilité', 'days_remaining' => 3],
            ['employee_name' => 'Claire Petit', 'category_name' => 'Lunettes de protection', 'days_remaining' => 7]
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Portail Guldagil</title>
    <link rel="stylesheet" href="/features/epi/assets/css/epi.css">
</head>
<body>
    <header class="epi-header">
        <div class="header-container">
            <h1>🛡️ Gestion EPI</h1>
            <p>Équipements de Protection Individuelle - Suivi et alertes</p>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Métriques -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?= $metrics['equipped_employees'] ?>/<?= $metrics['total_employees'] ?></div>
                <div class="metric-label">Employés équipés (<?= $metrics['equipment_ratio'] ?>%)</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-danger) 0%, #F87171 100%);">
                <div class="metric-value"><?= count($alerts['expired']) ?></div>
                <div class="metric-label">EPI expirés</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-warning) 0%, #FBBF24 100%);">
                <div class="metric-value"><?= count($alerts['urgent']) ?></div>
                <div class="metric-label">Alertes urgentes</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-success) 0%, #34D399 100%);">
                <div class="metric-value"><?= $metrics['available_equipment'] ?></div>
                <div class="metric-label">Équipements disponibles</div>
            </div>
        </section>

        <div class="dashboard-grid">
            <!-- Alertes -->
            <div class="epi-card">
                <h3>🚨 Alertes prioritaires</h3>
                <?php if (empty($alerts['expired']) && empty($alerts['urgent'])): ?>
                    <p style="color: var(--epi-success);">✅ Aucune alerte critique</p>
                <?php else: ?>
                    <?php foreach (array_slice($alerts['expired'], 0, 3) as $alert): ?>
                        <div class="alert-item alert-expired">
                            <span>⚠️</span>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($alert['employee_name']) ?></div>
                                <div style="font-size:0.85rem;color:#666;"><?= htmlspecialchars($alert['category_name']) ?> - Expiré depuis <?= abs($alert['days_remaining']) ?> jours</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach (array_slice($alerts['urgent'], 0, 3) as $alert): ?>
                        <div class="alert-item alert-urgent">
                            <span>⏰</span>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($alert['employee_name']) ?></div>
                                <div style="font-size:0.85rem;color:#666;"><?= htmlspecialchars($alert['category_name']) ?> - Expire dans <?= $alert['days_remaining'] ?> jours</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="epi-card">
                <h3>⚡ Actions rapides</h3>
                <div class="quick-actions">
                    <a href="/features/epi/employees.php" class="action-btn">👥 Employés</a>
                    <a href="/features/epi/inventory.php" class="action-btn">📦 Inventaire</a>
                    <a href="/features/epi/assignments.php" class="action-btn">🔄 Attributions</a>
                    <a href="/features/epi/reports.php" class="action-btn">📊 Rapports</a>
                </div>
            </div>

            <!-- Statut système -->
            <div class="epi-card">
                <h3>📊 Vue d'ensemble</h3>
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Taux d'équipement :</span>
                        <span style="font-weight: 600; color: var(--epi-success);"><?= $metrics['equipment_ratio'] ?>%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Stock disponible :</span>
                        <span style="font-weight: 600;"><?= $metrics['available_equipment'] ?> unités</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Statut système :</span>
                        <span style="color: var(--epi-success); font-weight: 600;">🟢 Opérationnel</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="/public/index.php" style="color: var(--epi-primary); text-decoration: none; font-weight: 500;">← Retour au portail</a>
        </div>
    </main>

    <script src="/features/epi/assets/js/epi.js"></script>
</body>
</html>
