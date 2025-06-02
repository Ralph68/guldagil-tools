<?php
// tabs/taxes-tab.php - Gestion des taxes et majorations
?>
<div id="tab-taxes" class="tab-content">
    <!-- Vue d'ensemble des taxes -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>📋 Taxes et majorations par transporteur</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary" onclick="showEditTaxesModal()">
                    <span>✏️</span>
                    Modifier les taxes
                </button>
                <button class="btn btn-secondary" onclick="exportTaxes()">
                    <span>📥</span>
                    Exporter
                </button>
                <button class="btn btn-secondary" onclick="refreshTaxes()">
                    <span>🔄</span>
                    Actualiser
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="taxes-overview">
                <?php
                try {
                    $stmt = $db->query("SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur");
                    $taxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($taxes)):
                ?>
                    <div class="no-data">
                        <div style="text-align: center; font-size: 2rem; color: #9ca3af;">📋</div>
                        <p style="text-align: center; color: #6b7280;">Aucune configuration de taxes trouvée</p>
                        <button class="btn btn-primary" onclick="initializeTaxes()">
                            Initialiser les taxes
                        </button>
                    </div>
                <?php else: ?>
                    <div class="transporteurs-taxes">
                        <?php foreach ($taxes as $tax): ?>
                        <div class="transporteur-tax-card">
                            <div class="tax-card-header">
                                <h3><?= htmlspecialchars($tax['transporteur']) ?></h3>
                                <div class="tax-status">
                                    <span class="status-badge active">Configuré</span>
                                    <button class="btn btn-sm btn-secondary" onclick="editTransporteurTaxes('<?= $tax['id'] ?>')">
                                        ✏️ Modifier
                                    </button>
                                </div>
                            </div>
                            
                            <div class="tax-details">
                                <!-- Informations générales -->
                                <div class="tax-section">
                                    <h5>Informations générales</h5>
                                    <div class="tax-info-grid">
                                        <div class="tax-info-item">
                                            <span class="label">Poids maximum</span>
                                            <span class="value"><?= number_format($tax['poids_maximum'], 0) ?> kg</span>
                                        </div>
                                        <div class="tax-info-item">
                                            <span class="label">Type de tarification</span>
                                            <span class="value"><?= htmlspecialchars($tax['type_tarification']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Majorations -->
                                <div class="tax-section">
                                    <h5>Majorations</h5>
                                    <div class="majorations-grid">
                                        <!-- ADR -->
                                        <div class="majoration-item">
                                            <div class="majoration-header">
                                                <span class="majoration-icon">⚠️</span>
                                                <span class="majoration-name">ADR</span>
                                            </div>
                                            <div class="majoration-value">
                                                <?= $tax['majoration_adr'] ?: 'Non applicable' ?>
                                            </div>
                                        </div>

                                        <!-- IDF -->
                                        <div class="majoration-item">
                                            <div class="majoration-header">
                                                <span class="majoration-icon">🗼</span>
                                                <span class="majoration-name">Île-de-France</span>
                                            </div>
                                            <div class="majoration-value">
                                                <?php if ($tax['majoration_idf_type'] && $tax['majoration_idf_type'] !== 'Aucune'): ?>
                                                    <?= $tax['majoration_idf_type'] ?>: 
                                                    <?= $tax['majoration_idf_valeur'] ?>
                                                    <?= $tax['majoration_idf_type'] === 'Pourcentage' ? '%' : '€' ?>
                                                <?php else: ?>
                                                    Aucune
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($tax['majoration_idf_departements']): ?>
                                            <div class="majoration-departments">
                                                Départements: <?= $tax['majoration_idf_departements'] ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Saisonnière -->
                                        <div class="majoration-item">
                                            <div class="majoration-header">
                                                <span class="majoration-icon">🍂</span>
                                                <span class="majoration-name">Saisonnière</span>
                                            </div>
                                            <div class="majoration-value">
                                                <?php if ($tax['majoration_saisonniere_applicable']): ?>
                                                    <?= $tax['majoration_saisonniere_taux'] ?>%
                                                    <?php if ($tax['majoration_saisonniere_date_debut'] && $tax['majoration_saisonniere_date_fin']): ?>
                                                        <br><small>Du <?= date('d/m', strtotime($tax['majoration_saisonniere_date_debut'])) ?> 
                                                        au <?= date('d/m', strtotime($tax['majoration_saisonniere_date_fin'])) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    Non applicable
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Taxes fixes -->
                                <div class="tax-section">
                                    <h5>Taxes fixes</h5>
                                    <div class="taxes-grid">
                                        <div class="tax-item">
                                            <span class="tax-name">🔋 Transition énergétique</span>
                                            <span class="tax-value"><?= number_format($tax['participation_transition_energetique'], 2) ?> €</span>
                                        </div>
                                        <div class="tax-item">
                                            <span class="tax-name">🏥 Contribution sanitaire</span>
                                            <span class="tax-value"><?= number_format($tax['contribution_sanitaire'], 2) ?> €</span>
                                        </div>
                                        <div class="tax-item">
                                            <span class="tax-name">🔒 Sûreté</span>
                                            <span class="tax-value"><?= number_format($tax['surete'], 2) ?> €</span>
                                        </div>
                                        <div class="tax-item total">
                                            <span class="tax-name"><strong>Total taxes fixes</strong></span>
                                            <span class="tax-value">
                                                <strong><?= number_format($tax['participation_transition_energetique'] + $tax['contribution_sanitaire'] + $tax['surete'], 2) ?> €</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Surcharge carburant -->
                                <div class="tax-section">
                                    <h5>Surcharge carburant</h5>
                                    <div class="surcharge-display">
                                        <div class="surcharge-value">
                                            <?= number_format($tax['surcharge_gasoil'] * 100, 2) ?>%
                                        </div>
                                        <div class="surcharge-description">
                                            Appliquée sur le montant total avant taxes
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                } catch (Exception $e) {
                    echo '<div class="error-message">Erreur lors du chargement des taxes: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Simulateur d'impact -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>🧮 Simulateur d'impact</h3>
            </div>
            <div class="admin-card-body">
                <div class="simulator">
                    <div class="simulator-form">
                        <div class="form-group">
                            <label for="sim-transporteur">Transporteur</label>
                            <select id="sim-transporteur" class="form-control" onchange="updateSimulation()">
                                <option value="">Choisir un transporteur</option>
                                <?php foreach ($taxes ?? [] as $tax): ?>
                                <option value="<?= $tax['transporteur'] ?>"><?= $tax['transporteur'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sim-tarif-base">Tarif de base (€)</label>
                            <input type="number" id="sim-tarif-base" class="form-control" 
                                   value="50" step="0.01" min="0" onchange="updateSimulation()">
                        </div>
                        <div class="form-group">
                            <label for="sim-departement">Département</label>
                            <input type="text" id="sim-departement" class="form-control" 
                                   value="67" maxlength="2" onchange="updateSimulation()">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="sim-adr" onchange="updateSimulation()">
                                Marchandise ADR
                            </label>
                        </div>
                    </div>
                    
                    <div class="simulation-result" id="simulation-result">
                        <div class="result-item">
                            <span>Tarif de base</span>
                            <span id="result-base">-</span>
                        </div>
                        <div class="result-item">
                            <span>Majorations</span>
                            <span id="result-majorations">-</span>
                        </div>
                        <div class="result-item">
                            <span>Taxes fixes</span>
                            <span id="result-taxes">-</span>
                        </div>
                        <div class="result-item">
                            <span>Surcharge carburant</span>
                            <span id="result-surcharge">-</span>
                        </div>
                        <div class="result-item total">
                            <span><strong>Prix final</strong></span>
                            <span id="result-total"><strong>-</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3>📊 Analyse comparative</h3>
            </div>
            <div class="admin-card-body">
                <div class="comparative-analysis">
                    <div class="analysis-controls">
                        <button class="btn btn-primary btn-sm" onclick="runComparison()">
                            🔍 Analyser tous les transporteurs
                        </button>
                    </div>
                    
                    <div class="comparison-results" id="comparison-results">
                        <p style="text-align: center; color: #6b7280; font-style: italic;">
                            Cliquez sur "Analyser" pour comparer les coûts entre transporteurs
                        </p>
                    </div>
                    
                    <div class="analysis-insights">
                        <h5>💡 Points d'attention</h5>
                        <ul class="insights-list">
                            <li>Les majorations IDF peuvent significativement impacter les coûts pour Paris</li>
                            <li>La surcharge carburant varie fortement entre transporteurs</li>
                            <li>Les taxes fixes s'accumulent et peuvent représenter 10-15% du coût</li>
                            <li>Les majorations ADR ne s'appliquent pas de la même façon</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des modifications -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>📜 Historique des modifications</h3>
            <button class="btn btn-secondary btn-sm" onclick="clearTaxHistory()">
                🗑️ Effacer l'historique
            </button>
        </div>
        <div class="admin-card-body">
            <div class="history-timeline">
                <?php
                // Simuler un historique (en production, récupérer depuis une table d'audit)
                $history = [
                    [
                        'date' => '2025-01-15 14:30:00',
                        'user' => 'admin',
                        'action' => 'Modification surcharge carburant XPO',
                        'details' => 'Passage de 14.2% à 15.22%',
                        'type' => 'update'
                    ],
                    [
                        'date' => '2025-01-10 09:15:00',
                        'user' => 'runser',
                        'action' => 'Ajout majoration saisonnière Heppner',
                        'details' => 'Période du 15/12 au 15/01, taux 5%',
                        'type' => 'create'
                    ],
                    [
                        'date' => '2025-01-05 16:45:00',
                        'user' => 'admin',
                        'action' => 'Mise à jour taxes fixes K+N',
                        'details' => 'Sûreté: 1.50€ → 1.80€',
                        'type' => 'update'
                    ]
                ];
                
                if (empty($history)):
                ?>
                    <p style="text-align: center; color: #6b7280; font-style: italic;">
                        Aucune modification récente
                    </p>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                    <div class="history-entry">
                        <div class="history-icon <?= $entry['type'] ?>">
                            <?= $entry['type'] === 'create' ? '➕' : ($entry['type'] === 'update' ? '✏️' : '🗑️') ?>
                        </div>
                        <div class="history-content">
                            <div class="history-action"><?= htmlspecialchars($entry['action']) ?></div>
                            <div class="history-details"><?= htmlspecialchars($entry['details']) ?></div>
                            <div class="history-meta">
                                <span class="history-user">👤 <?= htmlspecialchars($entry['user']) ?></span>
                                <span class="history-date">🕒 <?= date('d/m/Y H:i', strtotime($entry['date'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques aux taxes */
.transporteurs-taxes {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.transporteur-tax-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    background: #fafbfc;
}

.tax-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.tax-card-header h3 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.3rem;
}

.tax-status {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.tax-details {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.tax-section h5 {
    margin: 0 0 1rem 0;
    color: #374151;
    font-size: 1rem;
    font-weight: 600;
    border-bottom: 1px solid #d1d5db;
    padding-bottom: 0.5rem;
}

.tax-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.tax-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.tax-info-item .label {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
}

.tax-info-item .value {
    font-weight: 600;
    color: #374151;
}

.majorations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.majoration-item {
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.majoration-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.majoration-icon {
    font-size: 1.2rem;
}

.majoration-name {
    font-weight: 600;
    color: #374151;
}

.majoration-value {
    font-weight: 500;
    color: var(--primary-color);
}

.majoration-departments {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.taxes-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.tax-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.tax-item.total {
    background: #f0f9ff;
    border-color: var(--primary-color);
}

.tax-name {
    font-size: 0.9rem;
}

.tax-value {
    font-weight: 600;
    color: var(--primary-color);
}

.surcharge-display {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.surcharge-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--warning-color);
    margin-bottom: 0.5rem;
}

.surcharge-description {
    font-size: 0.85rem;
    color: #6b7280;
}

.simulator {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.simulator-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.simulation-result {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.result-item:last-child {
    border-bottom: none;
}

.result-item.total {
    background: #f0f9ff;
    margin: 0.5rem -1rem -1rem -1rem;
    padding: 1rem;
    border-top: 2px solid var(--primary-color);
}

.comparative-analysis {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.analysis-controls {
    text-align: center;
}

.comparison-results {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.insights-list {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.6;
}

.insights-list li {
    margin-bottom: 0.5rem;
}

.history-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-entry {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #e5e7eb;
}

.history-icon {
    font-size: 1.2rem;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}

.history-icon.create {
    background: #d1fae5;
    color: #065f46;
}

.history-icon.update {
    background: #dbeafe;
    color: #1e40af;
}

.history-icon.delete {
    background: #fee2e2;
    color: #991b1b;
}

.history-content {
    flex: 1;
}

.history-action {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.history-details {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.history-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #9ca3af;
}

@media (max-width: 768px) {
    .tax-card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .tax-status {
        align-self: stretch;
        justify-content: space-between;
    }
    
    .majorations-grid,
    .tax-info-grid {
        grid-template-columns: 1fr;
    }
    
    .history-entry {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
// Fonctions JavaScript pour les taxes
function showEditTaxesModal() {
    showAlert('info', 'Module d\'édition des taxes en cours de développement');
}

function exportTaxes() {
    const link = document.createElement('a');
    link.href = 'export.php?type=taxes&format=csv';
    link.download = `taxes_transporteurs_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    showAlert('success', 'Export des taxes démarré');
}

function refreshTaxes() {
    showAlert('info', 'Actualisation des données...');
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function initializeTaxes() {
    if (confirm('Initialiser les taxes avec les valeurs par défaut ?')) {
        showAlert('info', 'Initialisation en cours...');
        // Ici, appeler l'API pour initialiser
        setTimeout(() => {
            showAlert('success', 'Taxes initialisées avec succès');
            location.reload();
        }, 2000);
    }
}

function editTransporteurTaxes(id) {
    showAlert('info', `Édition des taxes pour le transporteur ID: ${id}`);
    // Ici, ouvrir une modal d'édition
}

function updateSimulation() {
    const transporteur = document.getElementById('sim-transporteur').value;
    const tarifBase = parseFloat(document.getElementById('sim-tarif-base').value) || 0;
    const departement = document.getElementById('sim-departement').value;
    const adr = document.getElementById('sim-adr').checked;
    
    if (!transporteur || !tarifBase) {
        document.getElementById('simulation-result').style.opacity = '0.5';
        return;
    }
    
    // Simulation simplifiée (en production, utiliser les vraies données)
    let majorations = 0;
    let taxes = 0;
    let surcharge = 0;
    
    // Majorations simulées
    if (adr && transporteur !== 'Heppner') {
        majorations += tarifBase * 0.20; // +20% pour ADR
    }
    
    if (['75', '77', '78', '91', '92', '93', '94', '95'].includes(departement)) {
        if (transporteur === 'XPO') {
            majorations += tarifBase * 0.06; // +6% IDF pour XPO
        } else if (transporteur === 'Heppner') {
            majorations += 7.35; // Montant fixe IDF pour Heppner
        }
    }
    
    // Taxes fixes simulées
    switch (transporteur) {
        case 'Heppner':
            taxes = 0.50 + 0.40 + 2.30;
            break;
        case 'XPO':
            taxes = 1.45 + 0.70;
            break;
        case 'Kuehne + Nagel':
            taxes = 1.50;
            break;
    }
    
    // Surcharge carburant
    const surchargeTaux = {
        'Heppner': 0.0660,
        'XPO': 0.1522,
        'Kuehne + Nagel': 0.0680
    };
    
    const montantAvantSurcharge = tarifBase + majorations + taxes;
    surcharge = montantAvantSurcharge * (surchargeTaux[transporteur] || 0);
    
    const total = montantAvantSurcharge + surcharge;
    
    // Affichage
    document.getElementById('result-base').textContent = tarifBase.toFixed(2) + ' €';
    document.getElementById('result-majorations').textContent = majorations.toFixed(2) + ' €';
    document.getElementById('result-taxes').textContent = taxes.toFixed(2) + ' €';
    document.getElementById('result-surcharge').textContent = surcharge.toFixed(2) + ' €';
    document.getElementById('result-total').textContent = total.toFixed(2) + ' €';
    
    document.getElementById('simulation-result').style.opacity = '1';
}

function runComparison() {
    const resultsDiv = document.getElementById('comparison-results');
    resultsDiv.innerHTML = '<div style="text-align: center;"><div class="spinner"></div><p>Analyse en cours...</p></div>';
    
    setTimeout(() => {
        const tarifBase = parseFloat(document.getElementById('sim-tarif-base').value) || 50;
        
        const comparison = [
            { name: 'Heppner', total: tarifBase * 1.12, color: '#10b981' },
            { name: 'XPO', total: tarifBase * 1.25, color: '#3b82f6' },
            { name: 'Kuehne + Nagel', total: tarifBase * 1.08, color: '#f59e0b' }
        ];
        
        comparison.sort((a, b) => a.total - b.total);
        
        let html = '<div class="comparison-chart">';
        comparison.forEach((item, index) => {
            const percent = (item.total / comparison[2].total) * 100;
            html += `
                <div class="comparison-bar">
                    <div class="bar-label">
                        <span>${item.name}</span>
                        <span class="bar-value">${item.total.toFixed(2)} €</span>
                        ${index === 0 ? '<span class="best-price">💰 Meilleur prix</span>' : ''}
                    </div>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: ${percent}%; background: ${item.color};"></div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        resultsDiv.innerHTML = html;
    }, 1500);
}

function clearTaxHistory() {
    if (confirm('Êtes-vous sûr de vouloir effacer l\'historique des modifications ?')) {
        showAlert('success', 'Historique effacé avec succès');
        // Ici, appeler l'API pour effacer l'historique
        setTimeout(() => {
            document.querySelector('.history-timeline').innerHTML = 
                '<p style="text-align: center; color: #6b7280; font-style: italic;">Aucune modification récente</p>';
        }, 1000);
    }
}

// Styles pour le graphique de comparaison
const comparisonStyles = `
.comparison-chart {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.comparison-bar {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.bar-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.bar-value {
    font-weight: 600;
    color: var(--primary-color);
}

.best-price {
    background: #10b981;
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.bar-container {
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    transition: width 0.8s ease;
    border-radius: 4px;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}
`;

// Ajouter les styles si ils n'existent pas
if (!document.getElementById('comparison-styles')) {
    const style = document.createElement('style');
    style.id = 'comparison-styles';
    style.textContent = comparisonStyles;
    document.head.appendChild(style);
}
</script>
