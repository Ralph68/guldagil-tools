<?php
// tabs/rates-tab.php - Onglet gestion des tarifs
?>
<div id="tab-rates" class="tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>💰 Gestion des tarifs par transporteur</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary" id="add-rate-button">
                    <span>➕</span>
                    Ajouter un tarif
                </button>
                <button class="btn btn-success" onclick="importRates()">
                    <span>📥</span>
                    Importer CSV
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <!-- Barre de recherche et filtres améliorée -->
            <div class="search-filters">
                <div class="filter-group">
                    <label>🔍 Recherche</label>
                    <input type="text" 
                           id="search-rates" 
                           placeholder="Rechercher par département, nom..." 
                           class="form-control">
                </div>
                
                <div class="filter-group">
                    <label>🚚 Transporteur</label>
                    <select id="filter-carrier">
                        <option value="">Tous les transporteurs</option>
                        <option value="heppner">🚛 Heppner</option>
                        <option value="xpo">🚛 XPO</option>
                        <option value="kn">🚛 Kuehne + Nagel</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>📍 Département</label>
                    <select id="filter-department">
                        <option value="">Tous les départements</option>
                        <!-- Options ajoutées dynamiquement par JS -->
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>📊 Statut</label>
                    <select id="filter-status">
                        <option value="">Tous les statuts</option>
                        <option value="complet">✅ Complet</option>
                        <option value="partiel">⚠️ Partiel</option>
                        <option value="vide">❌ Vide</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button class="btn btn-secondary" id="search-button" title="Rechercher">
                        <span>🔍</span>
                    </button>
                    <button class="btn btn-secondary" id="clear-filters-button" title="Effacer les filtres">
                        <span>🔄</span>
                    </button>
                    <button class="btn btn-secondary" id="refresh-rates-button" title="Actualiser">
                        <span>↻</span>
                    </button>
                    <button class="btn btn-secondary" id="export-rates-button" title="Exporter">
                        <span>📥</span>
                    </button>
                </div>
            </div>

            <!-- Informations sur les filtres actifs -->
            <div id="filters-info" style="display: none; margin-bottom: 1rem;"></div>

            <!-- Résumé rapide des transporteurs -->
            <div class="carriers-summary">
                <div class="carrier-summary-item" data-carrier="heppner">
                    <div class="carrier-icon">🚛</div>
                    <div class="carrier-info">
                        <div class="carrier-name">Heppner</div>
                        <div class="carrier-stats">
                            <span id="heppner-count">-</span> tarifs
                            <span id="heppner-coverage">-</span>% couverture
                        </div>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="filterByCarrier('heppner')">Filtrer</button>
                </div>
                
                <div class="carrier-summary-item" data-carrier="xpo">
                    <div class="carrier-icon">🚛</div>
                    <div class="carrier-info">
                        <div class="carrier-name">XPO</div>
                        <div class="carrier-stats">
                            <span id="xpo-count">-</span> tarifs
                            <span id="xpo-coverage">-</span>% couverture
                        </div>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="filterByCarrier('xpo')">Filtrer</button>
                </div>
                
                <div class="carrier-summary-item" data-carrier="kn">
                    <div class="carrier-icon">🚛</div>
                    <div class="carrier-info">
                        <div class="carrier-name">Kuehne + Nagel</div>
                        <div class="carrier-stats">
                            <span id="kn-count">-</span> tarifs
                            <span id="kn-coverage">-</span>% couverture
                        </div>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="filterByCarrier('kn')">Filtrer</button>
                </div>
            </div>

            <!-- Tableau des tarifs -->
            <div class="table-container">
                <table class="data-table" id="rates-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all-rates" title="Sélectionner tout">
                                Transporteur
                            </th>
                            <th>Département</th>
                            <th>0-9kg</th>
                            <th>10-19kg</th>
                            <th>90-99kg</th>
                            <th>100-299kg</th>
                            <th>500-999kg</th>
                            <th>Délai</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rates-tbody">
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                    Chargement des tarifs...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Actions en lot -->
            <div id="bulk-actions" class="bulk-actions" style="display: none;">
                <div class="bulk-actions-content">
                    <span id="selected-count">0</span> tarif(s) sélectionné(s)
                    <div class="bulk-actions-buttons">
                        <button class="btn btn-warning btn-sm" onclick="bulkEdit()">
                            <span>✏️</span> Modifier en lot
                        </button>
                        <button class="btn btn-success btn-sm" onclick="bulkExport()">
                            <span>📥</span> Exporter sélection
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
                            <span>🗑️</span> Supprimer sélection
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div id="pagination-container"></div>
        </div>
    </div>

    <!-- Aide contextuelle -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>💡 Guide des tarifs</h3>
            <button class="btn btn-secondary btn-sm" onclick="showRatesHelp()">
                <span>❓</span>
                Aide détaillée
            </button>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div class="help-section">
                    <h5>🚛 Spécificités transporteurs</h5>
                    <ul>
                        <li><strong>Heppner</strong> : Colis + Palettes, forfait <100kg</li>
                        <li><strong>XPO</strong> : Palettes uniquement, toujours au poids</li>
                        <li><strong>K+N</strong> : International, forfait <100kg</li>
                    </ul>
                </div>
                
                <div class="help-section">
                    <h5>📊 Calcul des tarifs</h5>
                    <ul>
                        <li><strong>< 100kg</strong> : Forfait (sauf XPO)</li>
                        <li><strong>≥ 100kg</strong> : Au poids (base 100kg)</li>
                        <li><strong>Majorations</strong> : ADR, IDF, saisonnières</li>
                    </ul>
                </div>
                
                <div class="help-section">
                    <h5>⚠️ Points d'attention</h5>
                    <ul>
                        <li>Vérifier la cohérence des tranches</li>
                        <li>Compléter tous les départements</li>
                        <li>Tester après modification</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.search-filters {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: var(--bg-light);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
}

.filter-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    justify-content: flex-end;
}

.filter-actions .btn {
    padding: 0.6rem !important;
    min-width: 40px;
}

.carriers-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.carrier-summary-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.carrier-summary-item:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.carrier-summary-item[data-carrier="heppner"]:hover {
    border-color: #4CAF50;
}

.carrier-summary-item[data-carrier="xpo"]:hover {
    border-color: #2196F3;
}

.carrier-summary-item[data-carrier="kn"]:hover {
    border-color: #FF9800;
}

.carrier-icon {
    font-size: 1.5rem;
    opacity: 0.8;
}

.carrier-info {
    flex: 1;
}

.carrier-name {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.carrier-stats {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.bulk-actions {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    background: var(--primary-color);
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

.bulk-actions-content {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.bulk-actions-buttons {
    display: flex;
    gap: 0.5rem;
}

.bulk-actions-buttons .btn {
    background: rgba(255,255,255,0.2) !important;
    border: 1px solid rgba
