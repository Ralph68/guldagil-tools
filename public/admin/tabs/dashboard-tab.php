<?php
// tabs/dashboard-tab.php - Onglet tableau de bord
?>
<div id="tab-dashboard" class="tab-content active">
    <!-- Actions rapides -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>⚡ Actions rapides</h2>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                Session active depuis <?= date('H:i', $userInfo['login_time']) ?>
            </div>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <button class="btn btn-primary quick-action-btn" onclick="showTab('rates')" data-count="<?= $stats['total_rates'] ?>">
                    <span style="font-size: 1.5rem;">💰</span>
                    <span>Gérer les tarifs</span>
                    <small><?= $stats['total_rates'] ?> tarifs configurés</small>
                </button>
                
                <button class="btn btn-success quick-action-btn" onclick="exportData()">
                    <span style="font-size: 1.5rem;">📥</span>
                    <span>Export complet</span>
                    <small>CSV, JSON, Excel</small>
                </button>
                
                <button class="btn btn-warning quick-action-btn" onclick="showTab('options')" data-count="<?= $stats['active_options'] ?>">
                    <span style="font-size: 1.5rem;">⚙️</span>
                    <span>Options transport</span>
                    <small><?= $stats['active_options'] ?> options actives</small>
                </button>
                
                <button class="btn btn-secondary quick-action-btn" onclick="checkServerStatus()">
                    <span style="font-size: 1.5rem;">🔍</span>
                    <span>Test système</span>
                    <small>Vérifier la santé</small>
                </button>
                
                <button class="btn btn-info quick-action-btn" onclick="showTab('analytics')">
                    <span style="font-size: 1.5rem;">📈</span>
                    <span>Analytics</span>
                    <small>Couverture <?= $stats['coverage'] ?>%</small>
                </button>
                
                <button class="btn btn-primary quick-action-btn" onclick="window.open('../', '_blank')">
                    <span style="font-size: 1.5rem;">🧮</span>
                    <span>Calculateur</span>
                    <small>Interface publique</small>
                </button>
            </div>
        </div>
    </div>

    <!-- Aperçu des tarifs par transporteur -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>📊 Aperçu des tarifs par transporteur</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary btn-sm" onclick="showTab('rates')">
                    <span>👁️</span>
                    Voir tous les tarifs
                </button>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">
                    <span>🔄</span>
                    Actualiser
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <?php
            // Récupérer des exemples de tarifs pour l'aperçu
            try {
                echo '<div class="table-container">';
                echo '<table class="data-table">';
                echo '<thead><tr>';
                echo '<th>Transporteur</th>';
                echo '<th>Département</th>';
                echo '<th>Exemple tarifs</th>';
                echo '<th>Délai</th>';
                echo '<th>Statut</th>';
                echo '<th class="text-center">Actions</th>';
                echo '</tr></thead><tbody>';
                
                // Exemple Heppner
                $stmt = $db->query("SELECT * FROM gul_heppner_rates WHERE tarif_0_9 IS NOT NULL ORDER BY num_departement LIMIT 1");
                $heppner = $stmt->fetch();
                if ($heppner) {
                    echo '<tr>';
                    echo '<td><div class="font-semibold text-primary">🚛 Heppner</div><div style="font-size: 0.8rem; color: #666;">Transport routier</div></td>';
                    echo '<td><strong>' . htmlspecialchars($heppner['num_departement']) . '</strong> - ' . htmlspecialchars($heppner['departement'] ?: 'N/A') . '</td>';
                    echo '<td>';
                    echo '<div style="font-size: 0.85rem;">';
                    echo '0-9kg: <strong>' . number_format($heppner['tarif_0_9'], 2) . '€</strong><br>';
                    echo '100-299kg: <strong>' . number_format($heppner['tarif_100_299'], 2) . '€</strong>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td><span class="badge badge-success">' . htmlspecialchars($heppner['delais'] ?: '24h') . '</span></td>';
                    echo '<td><span class="badge badge-success">✅ Actif</span></td>';
                    echo '<td class="text-center">';
                    echo '<button class="btn btn-secondary btn-sm" onclick="editRate(\'heppner\', \'' . $heppner['num_departement'] . '\')" title="Modifier">✏️</button>';
                    echo '</td></tr>';
                }
                
                // Exemple XPO
                $stmt = $db->query("SELECT * FROM gul_xpo_rates WHERE tarif_0_99 IS NOT NULL ORDER BY num_departement LIMIT 1");
                $xpo = $stmt->fetch();
                if ($xpo) {
                    echo '<tr>';
                    echo '<td><div class="font-semibold text-primary">🚚 XPO</div><div style="font-size: 0.8rem; color: #666;">Palettes uniquement</div></td>';
                    echo '<td><strong>' . htmlspecialchars($xpo['num_departement']) . '</strong> - ' . htmlspecialchars($xpo['departement'] ?: 'N/A') . '</td>';
                    echo '<td>';
                    echo '<div style="font-size: 0.85rem;">';
                    echo '0-99kg: <strong>' . number_format($xpo['tarif_0_99'], 2) . '€</strong><br>';
                    echo '100-499kg: <strong>' . number_format($xpo['tarif_100_499'], 2) . '€</strong>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td><span class="badge badge-info">' . htmlspecialchars($xpo['delais'] ?: '24h-48h') . '</span></td>';
                    echo '<td><span class="badge badge-success">✅ Actif</span></td>';
                    echo '<td class="text-center">';
                    echo '<button class="btn btn-secondary btn-sm" onclick="editRate(\'xpo\', \'' . $xpo['num_departement'] . '\')" title="Modifier">✏️</button>';
                    echo '</td></tr>';
                }
                
                // K+N (souvent vide)
                $stmt = $db->query("SELECT COUNT(*) as count FROM gul_kn_rates WHERE tarif_100_299 IS NOT NULL AND tarif_100_299 > 0");
                $knCount = $stmt->fetch()['count'] ?? 0;
                
                echo '<tr>';
                echo '<td><div class="font-semibold text-primary">🚛 Kuehne + Nagel</div><div style="font-size: 0.8rem; color: #666;">Transport international</div></td>';
                echo '<td>Exemple: <strong>75</strong> - Paris</td>';
                echo '<td>';
                if ($knCount > 0) {
                    echo '<div style="color: var(--success-color); font-weight: bold;">✅ Configuré</div>';
                } else {
                    echo '<div style="color: #999; font-style: italic;">⚠️ Aucun tarif configuré</div>';
                }
                echo '</td>';
                echo '<td><span class="badge badge-info">24h-48h</span></td>';
                echo '<td><span class="badge badge-warning">⏳ En attente</span></td>';
                echo '<td class="text-center">';
                echo '<button class="btn btn-primary btn-sm" onclick="addRate(\'kn\', \'75\')" title="Ajouter tarif">➕ Ajouter</button>';
                echo '</td></tr>';
                
                echo '</tbody></table>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div style="text-align: center; padding: 2rem; color: var(--error-color);">';
                echo '<div style="font-size: 2rem;">⚠️</div>';
                echo '<p><strong>Erreur lors du chargement des données</strong></p>';
                echo '<p style="font-size: 0.9rem;">' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<button class="btn btn-secondary" onclick="location.reload()">🔄 Recharger</button>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Activité récente et métriques -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Activité récente -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>🕒 Activité système</h3>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">
                    <span>🔄</span>
                    Actualiser
                </button>
            </div>
            <div class="admin-card-body">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="activity-item">
                        <div class="stat-icon success">✅</div>
                        <div style="flex: 1;">
                            <div class="font-medium">Interface d'administration connectée</div>
                            <div class="activity-time">Maintenant - <?= htmlspecialchars($userInfo['username']) ?></div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="stat-icon primary">📊</div>
                        <div style="flex: 1;">
                            <div class="font-medium"><?= $stats['calculations_today'] ?> calculs effectués</div>
                            <div class="activity-time">Aujourd'hui - Système opérationnel</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="stat-icon warning">⚙️</div>
                        <div style="flex: 1;">
                            <div class="font-medium">Base de données : <?= $stats['total_rates'] ?> tarifs</div>
                            <div class="activity-time">Couverture <?= $stats['coverage'] ?>% - <?= $stats['departments'] ?> départements</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="stat-icon <?= $stats['system_status']['color'] ?>"><?= $stats['system_status']['icon'] ?></div>
                        <div style="flex: 1;">
                            <div class="font-medium">Statut : <?= $stats['system_status']['text'] ?></div>
                            <div class="activity-time">Dernière vérification : <?= date('H:i:s') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métriques rapides -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>📈 Métriques rapides</h3>
                <button class="btn btn-secondary btn-sm" onclick="showTab('analytics')">
                    <span>📊</span>
                    Analytics
                </button>
            </div>
            <div class="admin-card-body">
                <div class="metrics-grid">
                    <div class="metric-item">
                        <div class="metric-label">Transporteurs</div>
                        <div class="metric-value"><?= $stats['carriers'] ?>/3</div>
                        <div class="metric-bar">
                            <div style="width: <?= ($stats['carriers']/3)*100 ?>%; background: var(--success-color);"></div>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label">Couverture</div>
                        <div class="metric-value"><?= $stats['coverage'] ?>%</div>
                        <div class="metric-bar">
                            <div style="width: <?= $stats['coverage'] ?>%; background: <?= $stats['coverage'] >= 50 ? 'var(--success-color)' : 'var(--warning-color)' ?>;"></div>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label">Options actives</div>
                        <div class="metric-value"><?= $stats['active_options'] ?></div>
                        <div class="metric-bar">
                            <div style="width: <?= min(100, $stats['active_options'] * 10) ?>%; background: var(--primary-color);"></div>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label">Performance</div>
                        <div class="metric-value">
                            <?php 
                            $perf = $stats['coverage'] >= 80 ? 'Excellent' : ($stats['coverage'] >= 50 ? 'Bon' : 'Moyen');
                            echo $perf;
                            ?>
                        </div>
                        <div class="metric-bar">
                            <div style="width: <?= $stats['coverage'] ?>%; background: var(--primary-color);"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-action-btn {
    padding: 1.5rem !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 0.5rem !important;
    text-align: center !important;
    transition: all 0.3s ease !important;
}

.quick-action-btn:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.quick-action-btn small {
    opacity: 0.8 !important;
    font-size: 0.75rem !important;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    transition: var(--transition);
}

.activity-item:hover {
    background: #f0f0f0;
    transform: translateX(4px);
}

.activity-time {
    font-size: 0.875rem;
    color: #6b7280;
}

.metrics-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.metric-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.metric-label {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.metric-value {
    font-size: 1.1rem;
    font-weight: bold;
    color: var(--primary-color);
}

.metric-bar {
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
}

.metric-bar div {
    height: 100%;
    border-radius: 3px;
    transition: width 0.8s ease;
}

@media (max-width: 768px) {
    .admin-card:last-child > div {
        grid-template-columns: 1fr !important;
    }
}
</style>
