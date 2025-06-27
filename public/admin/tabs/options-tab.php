<?php
// tabs/options-tab.php - Onglet gestion des options supplémentaires
?>
<div id="tab-options" class="tab-content">
    <!-- Statistiques des options -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total options</div>
                <div class="stat-icon primary">⚙️</div>
            </div>
            <div class="stat-value" id="options-total"><?= $stats['total_options'] ?></div>
            <div class="stat-trend neutral">
                <span>📊</span>
                Toutes options confondues
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Options actives</div>
                <div class="stat-icon success">✅</div>
            </div>
            <div class="stat-value" id="options-active"><?= $stats['active_options'] ?></div>
            <div class="stat-trend positive">
                <span>▶️</span>
                En service
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Options inactives</div>
                <div class="stat-icon warning">⏸️</div>
            </div>
            <div class="stat-value" id="options-inactive"><?= $stats['inactive_options'] ?></div>
            <div class="stat-trend neutral">
                <span>⏸️</span>
                Désactivées
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Répartition</div>
                <div class="stat-icon primary">📊</div>
            </div>
            <div id="options-distribution" style="font-size: 0.8rem; margin-top: 0.5rem;">
                <!-- Répartition par transporteur - sera mise à jour par JS -->
                <div class="loading-text">Chargement...</div>
            </div>
        </div>
    </div>

    <!-- Gestion des options -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>⚙️ Gestion des options supplémentaires</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary" id="add-option-button">
                    <span>➕</span>
                    Ajouter une option
                </button>
                <button class="btn btn-success" onclick="importOptions()">
                    <span>📥</span>
                    Importer
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <!-- Barre de filtres -->
            <div class="options-filters">
                <div class="filter-group">
                    <label>🚚 Transporteur</label>
                    <select id="filter-options-carrier">
                        <option value="">Tous les transporteurs</option>
                        <option value="heppner">🚛 Heppner</option>
                        <option value="xpo">🚛 XPO</option>
                        <option value="kn">🚛 Kuehne + Nagel</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>📊 Statut</label>
                    <select id="filter-options-status">
                        <option value="">Tous les statuts</option>
                        <option value="active">✅ Actives seulement</option>
                        <option value="inactive">⏸️ Inactives seulement</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>💰 Type tarification</label>
                    <select id="filter-options-type">
                        <option value="">Tous les types</option>
                        <option value="forfait">Forfait</option>
                        <option value="palette">Par palette</option>
                        <option value="pourcentage">Pourcentage</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-secondary" id="refresh-options-button" title="Actualiser">
                        <span>🔄</span>
                    </button>
                    <button class="btn btn-secondary" onclick="exportOptions()" title="Exporter">
                        <span>📥</span>
                    </button>
                </div>
            </div>

            <!-- Tableau des options -->
            <div class="table-container">
                <table class="data-table" id="options-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all-options" title="Sélectionner tout">
                                Transporteur
                            </th>
                            <th>Code</th>
                            <th>Libellé</th>
                            <th>Montant</th>
                            <th>Unité</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="options-tbody">
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                    Chargement des options...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Guide des options disponibles -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>📋 Guide des options disponibles</h3>
            <button class="btn btn-secondary btn-sm" onclick="showOptionsGuide()">
                <span>❓</span>
                Guide complet
            </button>
        </div>
        <div class="admin-card-body">
            <div class="options-guide">
                <div class="guide-category">
                    <h5>🚛 Options de livraison</h5>
                    <div class="guide-options">
                        <div class="guide-option">
                            <code>rdv</code>
                            <span>Prise de rendez-vous</span>
                            <small>Forfait ~15€</small>
                        </div>
                        <div class="guide-option">
                            <code>premium13</code>
                            <span>Livraison avant 13h</span>
                            <small>Forfait ~22€</small>
                        </div>
                        <div class="guide-option">
                            <code>premium18</code>
                            <span>Livraison avant 18h</span>
                            <small>Forfait ~18€</small>
                        </div>
                        <div class="guide-option">
                            <code>datefixe</code>
                            <span>Livraison à date fixe</span>
                            <small>Forfait ~20€</small>
                        </div>
                    </div>
                </div>
                
                <div class="guide-category">
                    <h5>📦 Options de service</h5>
                    <div class="guide-options">
                        <div class="guide-option">
                            <code>enlevement</code>
                            <span>Enlèvement sur site</span>
                            <small>Forfait variable</small>
                        </div>
                        <div class="guide-option">
                            <code>palette</code>
                            <span>Frais par palette EUR</span>
                            <small>Par palette 6-8€</small>
                        </div>
                        <div class="guide-option">
                            <code>assurance</code>
                            <span>Assurance renforcée</span>
                            <small>% de la valeur</small>
                        </div>
                        <div class="guide-option">
                            <code>livraison_etage</code>
                            <span>Livraison étage</span>
                            <small>Forfait ~25€</small>
                        </div>
                    </div>
                </div>
                
                <div class="guide-category">
                    <h5>💰 Types de tarification</h5>
                    <div class="guide-tarification">
                        <div class="tarif-type">
                            <div class="tarif-icon">🏷️</div>
                            <div class="tarif-info">
                                <strong>Forfait</strong>
                                <p>Montant fixe quelque soit le poids/nombre de palettes</p>
                                <small>Exemple : RDV = 15€ forfait</small>
                            </div>
                        </div>
                        
                        <div class="tarif-type">
                            <div class="tarif-icon">📦</div>
                            <div class="tarif-info">
                                <strong>Par palette</strong>
                                <p>Montant multiplié par le nombre de palettes</p>
                                <small>Exemple : 6,50€ × nombre de palettes</small>
                            </div>
                        </div>
                        
                        <div class="tarif-type">
                            <div class="tarif-icon">📊</div>
                            <div class="tarif-info">
                                <strong>Pourcentage</strong>
                                <p>Pourcentage appliqué sur le tarif de base</p>
                                <small>Exemple : +20% pour ADR</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exemples d'utilisation -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>💡 Exemples d'utilisation</h3>
        </div>
        <div class="admin-card-body">
            <div class="examples-grid">
                <div class="example-scenario">
                    <h5>🎯 Scénario 1 : Livraison premium</h5>
                    <div class="scenario-details">
                        <p><strong>Client :</strong> Demande livraison avant 13h</p>
                        <p><strong>Configuration :</strong></p>
                        <ul>
                            <li>Code : <code>premium13</code></li>
                            <li>Libellé : "Premium avant 13h"</li>
                            <li>Montant : 22.00€</li>
                            <li>Unité : Forfait</li>
                        </ul>
                        <p><strong>Résultat :</strong> +22€ au tarif de base</p>
                    </div>
                </div>
                
                <div class="example-scenario">
                    <h5>🎯 Scénario 2 : Transport de palettes</h5>
                    <div class="scenario-details">
                        <p><strong>Client :</strong> 3 palettes EUR à transporter</p>
                        <p><strong>Configuration :</strong></p>
                        <ul>
                            <li>Code : <code>palette</code></li>
                            <li>Libellé : "Frais par palette EUR"</li>
                            <li>Montant : 6.50€</li>
                            <li>Unité : Par palette</li>
                        </ul>
                        <p><strong>Résultat :</strong> 6.50€ × 3 = +19.50€</p>
                    </div>
                </div>
                
                <div class="example-scenario">
                    <h5>🎯 Scénario 3 : Marchandise ADR</h5>
                    <div class="scenario-details">
                        <p><strong>Client :</strong> Transport de produits chimiques</p>
                        <p><strong>Configuration :</strong></p>
                        <ul>
                            <li>Code : <code>adr</code></li>
                            <li>Libellé : "Majoration ADR"</li>
                            <li>Montant : 20.00%</li>
                            <li>Unité : Pourcentage</li>
                        </ul>
                        <p><strong>Résultat :</strong> +20% du tarif de base</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.options-filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--bg-light);
    border-radius: var(--border-radius);
}

.guide-category {
    margin-bottom: 2rem;
}

.guide-category h5 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.1rem;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.guide-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 0.75rem;
}

.guide-option {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    transition: var(--transition);
}

.guide-option:hover {
    border-color: var(--primary-color);
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.guide-option code {
    background: var(--primary-color);
    color: white;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.guide-option span {
    font-weight: 500;
    color: #333;
}

.guide-option small {
    color: var(--text-muted);
    font-size: 0.75rem;
}

.guide-tarification {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.tarif-type {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.tarif-type:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow);
}

.tarif-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
    align-self: flex-start;
}

.tarif-info strong {
    color: var(--primary-color);
    display: block;
    margin-bottom: 0.5rem;
}

.tarif-info p {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.tarif-info small {
    color: var(--text-muted);
    font-style: italic;
}

.examples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.example-scenario {
    padding: 1.5rem;
    background: var(--bg-lighter);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--success-color);
}

.example-scenario h5 {
    margin: 0 0 1rem 0;
    color: var(--success-color);
    font-size: 1rem;
}

.scenario-details p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.scenario-details ul {
    margin: 0.5rem 0;
    padding-left: 1.2rem;
}

.scenario-details li {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.scenario-details code {
    background: var(--primary-color);
    color: white;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    font-size: 0.8rem;
}

.loading-text {
    color: var(--text-muted);
    font-style: italic;
}

@media (max-width: 768px) {
    .options-filters {
        grid-template-columns: 1fr;
    }
    
    .guide-options {
        grid-template-columns: 1fr;
    }
    
    .guide-tarification {
        grid-template-columns: 1fr;
    }
    
    .examples-grid {
        grid-template-columns: 1fr;
    }
}
</style>
