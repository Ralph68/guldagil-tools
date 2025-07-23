<?php
/**
 * Titre: Page Recherche Avancée - Module Contrôle Qualité
 * Chemin: /public/qualite/search.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
session_start();
define('PORTAL_ACCESS', true);

// Chargement de la configuration
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (file_exists(__DIR__ . '/../../config/version.php')) {
    require_once __DIR__ . '/../../config/version.php';
}

// Variables d'environnement
$current_module = 'qualite';
$page_title = 'Recherche Avancée';
$page_description = 'Recherche avancée dans les contrôles qualité';

// Traitement de la recherche
$search_performed = false;
$search_results = [];
$total_results = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['search'])) {
    $search_performed = true;
    
    // Paramètres de recherche
    $search_text = $_POST['search_text'] ?? $_GET['search'] ?? '';
    $control_number = $_POST['control_number'] ?? $_GET['control_number'] ?? '';
    $equipment_type = $_POST['equipment_type'] ?? $_GET['equipment_type'] ?? '';
    $agency = $_POST['agency'] ?? $_GET['agency'] ?? '';
    $status = $_POST['status'] ?? $_GET['status'] ?? '';
    $technician = $_POST['technician'] ?? $_GET['technician'] ?? '';
    $date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? '';
    $has_anomalies = $_POST['has_anomalies'] ?? $_GET['has_anomalies'] ?? '';
    
    // Données simulées pour la recherche
    $mock_results = [
        [
            'id' => 1,
            'control_number' => 'ADOU_20250123_001',
            'equipment_type' => 'Adoucisseur',
            'equipment_model' => 'Clack CI',
            'installation_name' => 'Hotel Meridien',
            'agency_code' => 'GUL31',
            'status' => 'completed',
            'created_at' => '2025-01-23 14:30:00',
            'prepared_by' => 'J. Technicien',
            'client_name' => 'SARL Hôtellerie Plus',
            'anomalies_count' => 2,
            'conformity_rate' => 95.5
        ],
        [
            'id' => 2,
            'control_number' => 'POMPE_20250123_002',
            'equipment_type' => 'Pompe Doseuse',
            'equipment_model' => 'DOS4-8V',
            'installation_name' => 'Usine Agroalimentaire',
            'agency_code' => 'GUL82',
            'status' => 'in_progress',
            'created_at' => '2025-01-23 10:15:00',
            'prepared_by' => 'M. Contrôleur',
            'client_name' => 'Industries Alimentaires SA',
            'anomalies_count' => 0,
            'conformity_rate' => 100.0
        ],
        [
            'id' => 3,
            'control_number' => 'ADOU_20250122_003',
            'equipment_type' => 'Adoucisseur',
            'equipment_model' => 'Fleck SXT',
            'installation_name' => 'Centre Commercial',
            'agency_code' => 'GUL31',
            'status' => 'validated',
            'created_at' => '2025-01-22 16:45:00',
            'prepared_by' => 'A. Expert',
            'client_name' => 'Gestion Immobilière SARL',
            'anomalies_count' => 1,
            'conformity_rate' => 87.3
        ]
    ];
    
    // Simulation du filtrage
    $search_results = $mock_results;
    
    // Filtrage par texte libre
    if ($search_text) {
        $search_results = array_filter($search_results, function($result) use ($search_text) {
            return stripos($result['control_number'], $search_text) !== false ||
                   stripos($result['installation_name'], $search_text) !== false ||
                   stripos($result['client_name'], $search_text) !== false ||
                   stripos($result['prepared_by'], $search_text) !== false;
        });
    }
    
    // Filtrage par numéro de contrôle
    if ($control_number) {
        $search_results = array_filter($search_results, function($result) use ($control_number) {
            return stripos($result['control_number'], $control_number) !== false;
        });
    }
    
    // Filtrage par type d'équipement
    if ($equipment_type) {
        $search_results = array_filter($search_results, function($result) use ($equipment_type) {
            return stripos($result['equipment_type'], $equipment_type) !== false;
        });
    }
    
    // Filtrage par agence
    if ($agency) {
        $search_results = array_filter($search_results, function($result) use ($agency) {
            return $result['agency_code'] === $agency;
        });
    }
    
    // Filtrage par statut
    if ($status) {
        $search_results = array_filter($search_results, function($result) use ($status) {
            return $result['status'] === $status;
        });
    }
    
    // Filtrage par présence d'anomalies
    if ($has_anomalies === 'yes') {
        $search_results = array_filter($search_results, function($result) {
            return $result['anomalies_count'] > 0;
        });
    } elseif ($has_anomalies === 'no') {
        $search_results = array_filter($search_results, function($result) {
            return $result['anomalies_count'] === 0;
        });
    }
    
    $total_results = count($search_results);
}

// Labels pour les statuts
$status_labels = [
    'draft' => 'Brouillon',
    'in_progress' => 'En cours',
    'completed' => 'Terminé',
    'validated' => 'Validé',
    'sent' => 'Envoyé'
];

// Statistiques de recherche
$search_stats = [
    'total_controls' => 156,
    'avg_conformity' => 92.4,
    'total_anomalies' => 23,
    'active_technicians' => 8
];

// Chargement du header
if (file_exists(__DIR__ . '/../../templates/header.php')) {
    require_once __DIR__ . '/../../templates/header.php';
}
?>

<div class="qualite-module">
    <!-- Header du module -->
    <div class="module-header">
        <div class="breadcrumb">
            <a href="/" class="breadcrumb-item">🏠 Accueil</a>
            <span class="breadcrumb-separator">›</span>
            <a href="/qualite/" class="breadcrumb-item">🔬 Contrôle Qualité</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">🔍 Recherche Avancée</span>
        </div>
        
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">🔍</div>
                <div class="module-info">
                    <h1>Recherche Avancée</h1>
                    <div class="module-version">
                        <?= $search_performed ? $total_results . ' résultat(s) trouvé(s)' : 'Prêt pour la recherche' ?>
                    </div>
                </div>
            </div>
            
            <div class="module-actions">
                <a href="/qualite/" class="btn btn-secondary">
                    ← Retour Dashboard
                </a>
                <a href="/qualite/list.php" class="btn btn-info">
                    📋 Voir tous les contrôles
                </a>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <main class="main-content">
        <!-- Statistiques globales -->
        <section class="stats-overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $search_stats['total_controls'] ?></div>
                        <div class="stat-label">Contrôles total</div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $search_stats['avg_conformity'] ?>%</div>
                        <div class="stat-label">Conformité moyenne</div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $search_stats['total_anomalies'] ?></div>
                        <div class="stat-label">Anomalies en cours</div>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $search_stats['active_technicians'] ?></div>
                        <div class="stat-label">Techniciens actifs</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Formulaire de recherche avancée -->
        <section class="search-form-section">
            <div class="search-form-card">
                <h2>🎯 Critères de recherche</h2>
                
                <form method="POST" class="advanced-search-form" id="searchForm">
                    <div class="search-tabs">
                        <button type="button" class="tab-btn active" onclick="showTab('general')">
                            🔍 Général
                        </button>
                        <button type="button" class="tab-btn" onclick="showTab('technical')">
                            ⚙️ Technique
                        </button>
                        <button type="button" class="tab-btn" onclick="showTab('filters')">
                            📊 Filtres
                        </button>
                    </div>
                    
                    <!-- Onglet Général -->
                    <div id="general-tab" class="tab-content active">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="search_text">🔍 Recherche libre</label>
                                <input type="text" id="search_text" name="search_text" 
                                       value="<?= htmlspecialchars($search_text ?? '') ?>"
                                       placeholder="Nom installation, client, technicien...">
                                <small>Recherche dans tous les champs texte</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="control_number">📋 N° Contrôle</label>
                                <input type="text" id="control_number" name="control_number" 
                                       value="<?= htmlspecialchars($control_number ?? '') ?>"
                                       placeholder="ADOU_20250123_001">
                            </div>
                            
                            <div class="form-group">
                                <label for="agency">🏢 Agence</label>
                                <select id="agency" name="agency">
                                    <option value="">Toutes les agences</option>
                                    <option value="GUL31" <?= ($agency ?? '') === 'GUL31' ? 'selected' : '' ?>>GUL31 - Toulouse</option>
                                    <option value="GUL82" <?= ($agency ?? '') === 'GUL82' ? 'selected' : '' ?>>GUL82 - Montauban</option>
                                    <option value="GUL09" <?= ($agency ?? '') === 'GUL09' ? 'selected' : '' ?>>GUL09 - Foix</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">📊 Statut</label>
                                <select id="status" name="status">
                                    <option value="">Tous les statuts</option>
                                    <?php foreach ($status_labels as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($status ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Technique -->
                    <div id="technical-tab" class="tab-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="equipment_type">⚙️ Type d'équipement</label>
                                <select id="equipment_type" name="equipment_type">
                                    <option value="">Tous les types</option>
                                    <option value="Adoucisseur" <?= ($equipment_type ?? '') === 'Adoucisseur' ? 'selected' : '' ?>>
                                        Adoucisseur
                                    </option>
                                    <option value="Pompe" <?= ($equipment_type ?? '') === 'Pompe' ? 'selected' : '' ?>>
                                        Pompe Doseuse
                                    </option>
                                    <option value="Filtration" <?= ($equipment_type ?? '') === 'Filtration' ? 'selected' : '' ?>>
                                        Filtration
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="technician">👤 Technicien</label>
                                <input type="text" id="technician" name="technician" 
                                       value="<?= htmlspecialchars($technician ?? '') ?>"
                                       placeholder="Nom du technicien">
                            </div>
                            
                            <div class="form-group">
                                <label for="has_anomalies">⚠️ Anomalies</label>
                                <select id="has_anomalies" name="has_anomalies">
                                    <option value="">Avec ou sans anomalies</option>
                                    <option value="yes" <?= ($has_anomalies ?? '') === 'yes' ? 'selected' : '' ?>>
                                        Avec anomalies
                                    </option>
                                    <option value="no" <?= ($has_anomalies ?? '') === 'no' ? 'selected' : '' ?>>
                                        Sans anomalies
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="conformity_min">📈 Conformité minimale (%)</label>
                                <input type="number" id="conformity_min" name="conformity_min" 
                                       min="0" max="100" step="0.1"
                                       placeholder="Ex: 85.0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Filtres -->
                    <div id="filters-tab" class="tab-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="date_from">📅 Date début</label>
                                <input type="date" id="date_from" name="date_from" 
                                       value="<?= htmlspecialchars($date_from ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">📅 Date fin</label>
                                <input type="date" id="date_to" name="date_to" 
                                       value="<?= htmlspecialchars($date_to ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="sort_by">📊 Trier par</label>
                                <select id="sort_by" name="sort_by">
                                    <option value="date_desc">Date (plus récent)</option>
                                    <option value="date_asc">Date (plus ancien)</option>
                                    <option value="conformity_desc">Conformité (élevée)</option>
                                    <option value="conformity_asc">Conformité (faible)</option>
                                    <option value="anomalies_desc">Anomalies (plus)</option>
                                    <option value="anomalies_asc">Anomalies (moins)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="limit">📄 Nombre de résultats</label>
                                <select id="limit" name="limit">
                                    <option value="25">25 résultats</option>
                                    <option value="50">50 résultats</option>
                                    <option value="100">100 résultats</option>
                                    <option value="all">Tous les résultats</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions du formulaire -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            🔍 Lancer la recherche
                        </button>
                        <button type="button" onclick="resetForm()" class="btn btn-secondary">
                            🔄 Réinitialiser
                        </button>
                        <button type="button" onclick="saveSearch()" class="btn btn-info">
                            💾 Sauvegarder recherche
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Résultats de recherche -->
        <?php if ($search_performed): ?>
        <section class="search-results-section">
            <div class="results-header">
                <h2>📋 Résultats de recherche</h2>
                <div class="results-actions">
                    <button onclick="exportResults()" class="btn btn-info">
                        📄 Exporter résultats
                    </button>
                    <button onclick="createReport()" class="btn btn-warning">
                        📊 Générer rapport
                    </button>
                </div>
            </div>
            
            <?php if (!empty($search_results)): ?>
            <div class="results-table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>N° Contrôle</th>
                            <th>Installation</th>
                            <th>Client</th>
                            <th>Type/Modèle</th>
                            <th>Agence</th>
                            <th>Statut</th>
                            <th>Technicien</th>
                            <th>Date</th>
                            <th>Conformité</th>
                            <th>Anomalies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $result): ?>
                        <tr class="result-row">
                            <td>
                                <strong class="control-number">
                                    <?= htmlspecialchars($result['control_number']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="installation-name">
                                    <?= htmlspecialchars($result['installation_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="client-name">
                                    <?= htmlspecialchars($result['client_name']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="equipment-info">
                                    <span class="equipment-type">
                                        <?= htmlspecialchars($result['equipment_type']) ?>
                                    </span>
                                    <br>
                                    <small class="equipment-model">
                                        <?= htmlspecialchars($result['equipment_model']) ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="agency-badge">
                                    <?= htmlspecialchars($result['agency_code']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $result['status'] ?>">
                                    <?= $status_labels[$result['status']] ?>
                                </span>
                            </td>
                            <td>
                                <span class="technician-name">
                                    <?= htmlspecialchars($result['prepared_by']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="control-date">
                                    <?= date('d/m/Y H:i', strtotime($result['created_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="conformity-rate conformity-<?= $result['conformity_rate'] >= 95 ? 'high' : ($result['conformity_rate'] >= 85 ? 'medium' : 'low') ?>">
                                    <?= number_format($result['conformity_rate'], 1) ?>%
                                </span>
                            </td>
                            <td>
                                <span class="anomalies-count <?= $result['anomalies_count'] > 0 ? 'has-anomalies' : 'no-anomalies' ?>">
                                    <?= $result['anomalies_count'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-small btn-secondary" 
                                            onclick="viewControl(<?= $result['id'] ?>)"
                                            title="Voir le détail">
                                        👁️
                                    </button>
                                    <button class="btn btn-small btn-info"
                                            onclick="exportControl(<?= $result['id'] ?>)"
                                            title="Exporter PDF">
                                        📄
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php else: ?>
            <!-- Aucun résultat -->
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>Aucun résultat trouvé</h3>
                <p>Aucun contrôle ne correspond à vos critères de recherche.</p>
                <div class="no-results-suggestions">
                    <h4>💡 Suggestions :</h4>
                    <ul>
                        <li>Élargissez la période de recherche</li>
                        <li>Réduisez le nombre de filtres appliqués</li>
                        <li>Vérifiez l'orthographe des termes de recherche</li>
                        <li>Utilisez des mots-clés plus généraux</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Recherches sauvegardées -->
        <section class="saved-searches-section">
            <h3>💾 Recherches sauvegardées</h3>
            <div class="saved-searches-grid">
                <div class="saved-search-card">
                    <h4>🔍 Contrôles en cours</h4>
                    <p>Tous les contrôles avec statut "En cours"</p>
                    <button onclick="loadSavedSearch('in_progress')" class="btn btn-small btn-secondary">
                        Charger
                    </button>
                </div>
                <div class="saved-search-card">
                    <h4>⚠️ Avec anomalies</h4>
                    <p>Contrôles ayant des anomalies non résolues</p>
                    <button onclick="loadSavedSearch('anomalies')" class="btn btn-small btn-secondary">
                        Charger
                    </button>
                </div>
                <div class="saved-search-card">
                    <h4>📅 Semaine dernière</h4>
                    <p>Tous les contrôles de la semaine passée</p>
                    <button onclick="loadSavedSearch('last_week')" class="btn btn-small btn-secondary">
                        Charger
                    </button>
                </div>
            </div>
        </section>
    </main>
</div>

<style>
/* Statistiques overview */
.stats-overview {
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid #6b7280;
}

.stat-card.success { border-left-color: #10b981; }
.stat-card.warning { border-left-color: #f59e0b; }
.stat-card.info { border-left-color: #3b82f6; }

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Formulaire de recherche */
.search-form-section {
    margin-bottom: 2rem;
}

.search-form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-form-card h2 {
    margin: 0 0 1.5rem 0;
    color: #1f2937;
}

/* Onglets */
.search-tabs {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 2rem;
}

.tab-btn {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
}

.tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tab-btn:hover {
    color: #3b82f6;
}

/* Contenu des onglets */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group small {
    margin-top: 0.25rem;
    color: #6b7280;
    font-size: 0.75rem;
}

/* Actions du formulaire */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1rem;
}

/* Résultats */
.search-results-section {
    margin-bottom: 2rem;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.results-header h2 {
    margin: 0;
    color: #1f2937;
}

.results-actions {
    display: flex;
    gap: 0.5rem;
}

.results-table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}

.results-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.result-row:hover {
    background: #f9fafb;
}

/* Badges et statuts dans les résultats */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft { background: #fef3c7; color: #92400e; }
.status-in_progress { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-validated { background: #dcfce7; color: #166534; }
.status-sent { background: #e0e7ff; color: #3730a3; }

.agency-badge {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.conformity-rate {
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.conformity-high { background: #d1fae5; color: #065f46; }
.conformity-medium { background: #fef3c7; color: #92400e; }
.conformity-low { background: #fef2f2; color: #991b1b; }

.anomalies-count {
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.has-anomalies { background: #fef2f2; color: #991b1b; }
.no-anomalies { background: #d1fae5; color: #065f46; }

/* Actions dans le tableau */
.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.btn-small {
    padding: 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

/* Aucun résultat */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.no-results h3 {
    color: #1f2937;
    margin-bottom: 1rem;
}

.no-results-suggestions {
    margin-top: 2rem;
    text-align: left;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.no-results-suggestions h4 {
    color: #374151;
    margin-bottom: 1rem;
}

.no-results-suggestions ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    color: #6b7280;
}

.no-results-suggestions li {
    margin-bottom: 0.5rem;
}

/* Recherches sauvegardées */
.saved-searches-section {
    margin-top: 3rem;
}

.saved-searches-section h3 {
    margin-bottom: 1rem;
    color: #1f2937;
}

.saved-searches-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.saved-search-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #3b82f6;
}

.saved-search-card h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.saved-search-card p {
    color: #6b7280;
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .results-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .results-table-container {
        overflow-x: auto;
    }
    
    .saved-searches-grid {
        grid-template-columns: 1fr;
    }
    
    .search-tabs {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        min-width: 120px;
    }
}

/* Animations */
.tab-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.result-row {
    transition: background-color 0.2s ease;
}

.saved-search-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>

<!-- JavaScript -->
<script>
// Gestion des onglets
function showTab(tabName) {
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglet
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Afficher l'onglet sélectionné
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Activer le bouton correspondant
    event.target.classList.add('active');
}

// Réinitialiser le formulaire
function resetForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser tous les champs ?')) {
        document.getElementById('searchForm').reset();
        showTab('general');
    }
}

// Sauvegarder une recherche
function saveSearch() {
    const searchName = prompt('Nom de la recherche sauvegardée :');
    if (searchName) {
        alert(`✅ Recherche "${searchName}" sauvegardée`);
        // Ici on ajouterait la logique de sauvegarde
    }
}

// Charger une recherche sauvegardée
function loadSavedSearch(type) {
    const form = document.getElementById('searchForm');
    
    // Réinitialiser le formulaire
    form.reset();
    
    switch (type) {
        case 'in_progress':
            document.getElementById('status').value = 'in_progress';
            break;
        case 'anomalies':
            document.getElementById('has_anomalies').value = 'yes';
            break;
        case 'last_week':
            const lastWeek = new Date();
            lastWeek.setDate(lastWeek.getDate() - 7);
            const today = new Date();
            document.getElementById('date_from').value = lastWeek.toISOString().split('T')[0];
            document.getElementById('date_to').value = today.toISOString().split('T')[0];
            break;
    }
    
    // Soumettre automatiquement
    form.submit();
}

// Actions sur les résultats
function viewControl(id) {
    window.location.href = `view.php?id=${id}`;
}

function exportControl(id) {
    window.location.href = `export.php?id=${id}&format=pdf`;
}

function exportResults() {
    if (confirm('Exporter tous les résultats au format Excel ?')) {
        window.location.href = 'export_search.php?format=excel&' + new URLSearchParams(new FormData(document.getElementById('searchForm')));
    }
}

function createReport() {
    if (confirm('Générer un rapport détaillé de cette recherche ?')) {
        window.location.href = 'generate_report.php?' + new URLSearchParams(new FormData(document.getElementById('searchForm')));
    }
}

// Validation du formulaire
document.getElementById('searchForm').addEventListener('submit', function(e) {
    const formData = new FormData(this);
    let hasData = false;
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            hasData = true;
            break;
        }
    }
    
    if (!hasData) {
        e.preventDefault();
        alert('⚠️ Veuillez renseigner au moins un critère de recherche');
        return false;
    }
});

// Auto-save du formulaire dans le localStorage
function autoSaveForm() {
    const formData = new FormData(document.getElementById('searchForm'));
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    localStorage.setItem('qualite_search_form', JSON.stringify(data));
}

// Restaurer le formulaire depuis le localStorage
function restoreForm() {
    try {
        const saved = localStorage.getItem('qualite_search_form');
        if (saved) {
            const data = JSON.parse(saved);
            const form = document.getElementById('searchForm');
            
            for (let [key, value] of Object.entries(data)) {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = value;
                }
            }
        }
    } catch (e) {
        console.warn('Impossible de restaurer le formulaire:', e);
    }
}

// Sauvegarde automatique lors des changements
document.getElementById('searchForm').addEventListener('input', autoSaveForm);
document.getElementById('searchForm').addEventListener('change', autoSaveForm);

// Animation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Restaurer le formulaire
    restoreForm();
    
    // Animation des cartes
    const cards = document.querySelectorAll('.stat-card, .saved-search-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Mise en surbrillance des termes de recherche dans les résultats
    const searchText = document.getElementById('search_text')?.value;
    if (searchText && searchText.length > 2) {
        highlightSearchTerms(searchText);
    }
});

// Fonction pour mettre en surbrillance les termes de recherche
function highlightSearchTerms(searchText) {
    const resultsTable = document.querySelector('.results-table tbody');
    if (!resultsTable) return;
    
    const regex = new RegExp(`(${searchText})`, 'gi');
    
    resultsTable.querySelectorAll('td').forEach(cell => {
        const text = cell.textContent;
        if (regex.test(text)) {
            cell.innerHTML = text.replace(regex, '<mark style="background: #fef3c7; padding: 0.1em 0.2em; border-radius: 2px;">$1</mark>');
        }
    });
}

console.log('🔍 Module Recherche Avancée Qualité chargé');
</script>

<?php
// Chargement du footer
if (file_exists(__DIR__ . '/../../templates/footer.php')) {
    require_once __DIR__ . '/../../templates/footer.php';
}
?>
