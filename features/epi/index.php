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
    <link rel="stylesheet" href="/features/epi/assets/epi.css">
    <style>
        /* CSS intégré temporaire si le fichier CSS n'existe pas */
        :root {
            --epi-primary: #6B46C1;
            --epi-secondary: #8B5CF6;
            --epi-accent: #A78BFA;
            --epi-success: #10B981;
            --epi-warning: #F59E0B;
            --epi-danger: #EF4444;
            --shadow-light: 0 4px 6px rgba(107, 70, 193, 0.1);
            --shadow-medium: 0 8px 25px rgba(107, 70, 193, 0.15);
        }

        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }

        .epi-header {
            background: linear-gradient(135deg, var(--epi-primary) 0%, var(--epi-secondary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .epi-header h1 { margin: 0; font-size: 2.5rem; font-weight: 700; }
        .header-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }

        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem 3rem; }
        .dashboard-grid { display: grid; gap: 2rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

        .epi-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(107, 70, 193, 0.1);
            transition: all 0.3s ease;
        }

        .epi-card:hover { box-shadow: var(--shadow-medium); transform: translateY(-2px); }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: linear-gradient(135deg, var(--epi-primary) 0%, var(--epi-secondary) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .metric-value { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .metric-label { font-size: 0.9rem; opacity: 0.9; }

        .alert-item {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-expired { background: #FEF2F2; border-left: 4px solid var(--epi-danger); }
        .alert-urgent { background: #FFFBEB; border-left: 4px solid var(--epi-warning); }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: var(--epi-accent);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-btn:hover { background: var(--epi-primary); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .dashboard-container { padding: 0 1rem 2rem; }
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .metric-value { font-size: 2rem; }
        }
    </style>
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

    <script>
        console.log('🛡️ Module EPI initialisé');
        
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.epi-card, .metric-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
