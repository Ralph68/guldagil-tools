<?php
// public/admin/index.php
require __DIR__ . '/auth.php'; // Vérification d'authentification
require __DIR__ . '/../../config.php';

// Vérifier si Transport est déjà inclus
if (!class_exists('Transport')) {
    require __DIR__ . '/../../lib/Transport.php';
}

$adminUser = getAdminUser();

// Récupération des statistiques
try {
    // Compter les transporteurs actifs
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs");
    $totalCarriers = $stmt->fetch()['count'];
    
    // Compter les départements avec tarifs (union des tables)
    $sql = "SELECT COUNT(DISTINCT num_departement) as count FROM (
                SELECT num_departement FROM gul_heppner_rates WHERE num_departement IS NOT NULL
                UNION 
                SELECT num_departement FROM gul_xpo_rates WHERE num_departement IS NOT NULL
                UNION 
                SELECT num_departement FROM gul_kn_rates WHERE num_departement IS NOT NULL
            ) as all_departments";
    $stmt = $db->query($sql);
    $totalDepartments = $stmt->fetch()['count'];
    
    // Compter les options actives
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE actif = 1");
    $totalOptions = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $totalCarriers = 3;
    $totalDepartments = 95;
    $totalOptions = 0;
}

// Gérer la déconnexion
if (isset($_GET['logout'])) {
    destroyAdminSession();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Guldagil Port Calculator</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <h1>
            ⚙️ Administration
            <span class="subtitle">Guldagil Port Calculator</span>
        </h1>
        <nav class="admin-nav">
            <a href="../">🏠 Retour au calculateur</a>
            <a href="export.php">📥 Export</a>
            <span class="user-info">👤 <?= htmlspecialchars($adminUser['username']) ?></span>
            <a href="?logout=1" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?')">🚪 Déconnexion</a>
        </nav>
    </header>

    <div class="admin-container">
        <!-- Dashboard stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">🚚</span>
                <div class="stat-value" id="total-carriers"><?= $totalCarriers ?></div>
                <div class="stat-label">Transporteurs</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📍</span>
                <div class="stat-value" id="total-departments"><?= $totalDepartments ?></div>
                <div class="stat-label">Départements</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">⚙️</span>
                <div class="stat-value" id="total-options"><?= $totalOptions ?></div>
                <div class="stat-label">Options actives</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🔄</span>
                <div class="stat-value">Aujourd'hui</div>
                <div class="stat-label">Dernière maj</div>
            </div>
        </div>

        <!-- Navigation par onglets -->
        <div class="tab-navigation">
            <button class="tab-button active" onclick="showTab('rates')">
                📊 Gestion des tarifs
            </button>
            <button class="tab-button" onclick="showTab('options')">
                ⚙️ Options supplémentaires
            </button>
            <button class="tab-button" onclick="showTab('taxes')">
                💰 Taxes et majorations
            </button>
            <button class="tab-button" onclick="showTab('import')">
                📤 Import/Export
            </button>
        </div>

        <!-- Messages d'alerte -->
        <div id="alert-container"></div>

        <!-- Onglet Gestion des tarifs -->
        <div id="tab-rates" class="tab-content active">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📊 Gestion des tarifs par transporteur</h2>
                    <button class="btn btn-primary" onclick="addRate()">
                        ➕ Ajouter un tarif
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="search-filters">
                        <input type="text" class="form-control search-input" id="search-rates" placeholder="🔍 Rechercher par département...">
                        <select class="form-control filter-select" id="filter-carrier">
                            <option value="">Tous les transporteurs</option>
                            <option value="heppner">Heppner</option>
                            <option value="xpo">XPO</option>
                            <option value="kn">Kuehne + Nagel</option>
                        </select>
                        <button class="btn btn-secondary" onclick="loadRates()">
                            🔄 Actualiser
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="rates-table">
                            <thead>
                                <tr>
                                    <th>Transporteur</th>
                                    <th>Département</th>
                                    <th>0-9kg</th>
                                    <th>10-19kg</th>
                                    <th>90-99kg</th>
                                    <th>100-299kg</th>
                                    <th>500-999kg</th>
                                    <th>Délai</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rates-tbody">
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="loading-spinner">Chargement des tarifs...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Options supplémentaires -->
        <div id="tab-options" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>⚙️ Options supplémentaires</h2>
                    <button class="btn btn-primary" onclick="addOption()">
                        ➕ Ajouter une option
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="search-filters">
                        <input type="text" class="form-control search-input" id="search-options" placeholder="🔍 Rechercher une option...">
                        <select class="form-control filter-select" id="filter-option-carrier">
                            <option value="">Tous les transporteurs</option>
                            <option value="heppner">Heppner</option>
                            <option value="xpo">XPO</option>
                            <option value="kn">Kuehne + Nagel</option>
                        </select>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="options-table">
                            <thead>
                                <tr>
                                    <th>Transporteur</th>
                                    <th>Code</th>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                    <th>Unité</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="options-tbody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="loading-spinner">Chargement des options...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Taxes et majorations -->
        <div id="tab-taxes" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>💰 Taxes et majorations par transporteur</h2>
                    <button class="btn btn-primary" onclick="editTaxes()">
                        ✏️ Modifier les taxes
                    </button>
                </div>
                <div class="admin-card-body" id="taxes-content">
                    <div class="loading-spinner">Chargement des taxes...</div>
                </div>
            </div>
        </div>

        <!-- Onglet Import/Export -->
        <div id="tab-import" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📤 Import et Export des données</h2>
                </div>
                <div class="admin-card-body">
                    <div class="import-export-grid">
                        <!-- Import -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📥 Import de fichiers</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="import-form" enctype="multipart/form-data">
                                    <div class="upload-zone" onclick="document.getElementById('file-input').click()">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-text">
                                            <div class="upload-title">Cliquez pour sélectionner un fichier</div>
                                            <div class="upload-subtitle">ou glissez-déposez votre fichier Excel/CSV</div>
                                        </div>
                                        <input type="file" id="file-input" name="import_file" style="display: none;" accept=".xlsx,.xls,.csv">
                                    </div>
                                    <div class="upload-actions">
                                        <button type="button" class="btn btn-primary" onclick="importData()">
                                            📥 Importer les données
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">
                                            📋 Télécharger le modèle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Export -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📤 Export de données</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="export-form">
                                    <div class="form-group">
                                        <label>Type d'export</label>
                                        <select class="form-control" id="export-type" name="export_type">
                                            <option value="all">Toutes les données</option>
                                            <option value="rates">Tarifs uniquement</option>
                                            <option value="options">Options uniquement</option>
                                            <option value="taxes">Taxes uniquement</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Format</label>
                                        <select class="form-control" id="export-format" name="export_format">
                                            <option value="excel">Excel (.xlsx)</option>
                                            <option value="csv">CSV</option>
                                            <option value="json">JSON</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success" onclick="exportData()">
                                        📤 Exporter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour édition de tarif -->
    <div id="edit-rate-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Modifier le tarif</h3>
                <button class="modal-close" onclick="closeModal('edit-rate-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rate-form">
                    <input type="hidden" id="rate-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Transporteur *</label>
                            <select class="form-control" id="rate-carrier" name="carrier" required>
                                <option value="heppner">Heppner</option>
                                <option value="xpo">XPO</option>
                                <option value="kn">Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Département *</label>
                            <input type="text" class="form-control" id="rate-department" name="department" placeholder="Ex: 67" pattern="[0-9]{2}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>0-9 kg</label>
                            <input type="number" class="form-control" id="rate-0-9" name="tarif_0_9" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>10-19 kg</label>
                            <input type="number" class="form-control" id="rate-10-19" name="tarif_10_19" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>90-99 kg</label>
                            <input type="number" class="form-control" id="rate-90-99" name="tarif_90_99" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>100-299 kg</label>
                            <input type="number" class="form-control" id="rate-100-299" name="tarif_100_299" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>500-999 kg</label>
                            <input type="number" class="form-control" id="rate-500-999" name="tarif_500_999" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Délai de livraison</label>
                            <input type="text" class="form-control" id="rate-delay" name="delais" placeholder="Ex: 24h, 48h-72h">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-rate-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveRate()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour édition d'option -->
    <div id="edit-option-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚙️ Modifier l'option</h3>
                <button class="modal-close" onclick="closeModal('edit-option-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="option-form">
                    <input type="hidden" id="option-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Transporteur *</label>
                            <select class="form-control" id="option-carrier" name="transporteur" required>
                                <option value="heppner">Heppner</option>
                                <option value="xpo">XPO</option>
                                <option value="kn">Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Code de l'option *</label>
                            <input type="text" class="form-control" id="option-code" name="code_option" placeholder="Ex: rdv, premium13" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Libellé *</label>
                        <input type="text" class="form-control" id="option-label" name="libelle" placeholder="Ex: Prise de RDV" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Montant *</label>
                            <input type="number" class="form-control" id="option-amount" name="montant" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Unité *</label>
                            <select class="form-control" id="option-unit" name="unite" required>
                                <option value="forfait">Forfait</option>
                                <option value="palette">Par palette</option>
                                <option value="pourcentage">Pourcentage</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="option-active" name="actif" checked> Option active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-option-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveOption()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
                <?php
// public/admin/index.php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

// Vérification simple d'accès admin (à améliorer selon vos besoins)
session_start();

$transport = new Transport($db);

// Récupération des statistiques
try {
    // Compter les transporteurs actifs
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs");
    $totalCarriers = $stmt->fetch()['count'];
    
    // Compter les départements avec tarifs
    $stmt = $db->query("SELECT COUNT(DISTINCT num_departement) as count FROM gul_heppner_rates");
    $totalDepartments = $stmt->fetch()['count'];
    
    // Compter les options actives
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE actif = 1");
    $totalOptions = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $totalCarriers = 0;
    $totalDepartments = 0;
    $totalOptions = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Guldagil Port Calculator</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <h1>
            ⚙️ Administration
            <span class="subtitle">Guldagil Port Calculator</span>
        </h1>
        <nav class="admin-nav">
            <a href="../">🏠 Retour au calculateur</a>
            <a href="export.php">📥 Export</a>
            <a href="#" onclick="showHelp()">❓ Aide</a>
        </nav>
    </header>

    <div class="admin-container">
        <!-- Dashboard stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">🚚</span>
                <div class="stat-value"><?= $totalCarriers ?></div>
                <div class="stat-label">Transporteurs</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📍</span>
                <div class="stat-value"><?= $totalDepartments ?></div>
                <div class="stat-label">Départements</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">⚙️</span>
                <div class="stat-value"><?= $totalOptions ?></div>
                <div class="stat-label">Options actives</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🔄</span>
                <div class="stat-value">Aujourd'hui</div>
                <div class="stat-label">Dernière maj</div>
            </div>
        </div>

        <!-- Navigation par onglets -->
        <div class="tab-navigation">
            <button class="tab-button active" onclick="showTab('rates')">
                📊 Gestion des tarifs
            </button>
            <button class="tab-button" onclick="showTab('options')">
                ⚙️ Options supplémentaires
            </button>
            <button class="tab-button" onclick="showTab('taxes')">
                💰 Taxes et majorations
            </button>
            <button class="tab-button" onclick="showTab('import')">
                📤 Import/Export
            </button>
        </div>

        <!-- Messages d'alerte -->
        <div id="alert-container"></div>

        <!-- Onglet Gestion des tarifs -->
        <div id="tab-rates" class="tab-content active">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📊 Gestion des tarifs par transporteur</h2>
                    <button class="btn btn-primary" onclick="addRate()">
                        ➕ Ajouter un tarif
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="search-filters">
                        <input type="text" class="form-control search-input" id="search-rates" placeholder="🔍 Rechercher par département...">
                        <select class="form-control filter-select" id="filter-carrier">
                            <option value="">Tous les transporteurs</option>
                            <option value="heppner">Heppner</option>
                            <option value="xpo">XPO</option>
                            <option value="kn">Kuehne + Nagel</option>
                        </select>
                        <button class="btn btn-secondary" onclick="loadRates()">
                            🔄 Actualiser
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="rates-table">
                            <thead>
                                <tr>
                                    <th>Transporteur</th>
                                    <th>Département</th>
                                    <th>0-9kg</th>
                                    <th>10-19kg</th>
                                    <th>90-99kg</th>
                                    <th>100-299kg</th>
                                    <th>500-999kg</th>
                                    <th>Délai</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rates-tbody">
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="loading-spinner">Chargement des tarifs...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Options supplémentaires -->
        <div id="tab-options" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>⚙️ Options supplémentaires</h2>
                    <button class="btn btn-primary" onclick="addOption()">
                        ➕ Ajouter une option
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="search-filters">
                        <input type="text" class="form-control search-input" id="search-options" placeholder="🔍 Rechercher une option...">
                        <select class="form-control filter-select" id="filter-option-carrier">
                            <option value="">Tous les transporteurs</option>
                            <option value="heppner">Heppner</option>
                            <option value="xpo">XPO</option>
                            <option value="kn">Kuehne + Nagel</option>
                        </select>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="options-table">
                            <thead>
                                <tr>
                                    <th>Transporteur</th>
                                    <th>Code</th>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                    <th>Unité</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="options-tbody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="loading-spinner">Chargement des options...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Taxes et majorations -->
        <div id="tab-taxes" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>💰 Taxes et majorations par transporteur</h2>
                    <button class="btn btn-primary" onclick="editTaxes()">
                        ✏️ Modifier les taxes
                    </button>
                </div>
                <div class="admin-card-body" id="taxes-content">
                    <div class="loading-spinner">Chargement des taxes...</div>
                </div>
            </div>
        </div>

        <!-- Onglet Import/Export -->
        <div id="tab-import" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📤 Import et Export des données</h2>
                </div>
                <div class="admin-card-body">
                    <div class="import-export-grid">
                        <!-- Import -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📥 Import de fichiers</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="import-form" enctype="multipart/form-data">
                                    <div class="upload-zone" onclick="document.getElementById('file-input').click()">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-text">
                                            <div class="upload-title">Cliquez pour sélectionner un fichier</div>
                                            <div class="upload-subtitle">ou glissez-déposez votre fichier Excel/CSV</div>
                                        </div>
                                        <input type="file" id="file-input" name="import_file" style="display: none;" accept=".xlsx,.xls,.csv">
                                    </div>
                                    <div class="upload-actions">
                                        <button type="button" class="btn btn-primary" onclick="importData()">
                                            📥 Importer les données
                                        </button>
                                        <a href="template.php" class="btn btn-secondary">
                                            📋 Télécharger le modèle
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Export -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📤 Export de données</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="export-form">
                                    <div class="form-group">
                                        <label>Type d'export</label>
                                        <select class="form-control" id="export-type" name="export_type">
                                            <option value="all">Toutes les données</option>
                                            <option value="rates">Tarifs uniquement</option>
                                            <option value="options">Options uniquement</option>
                                            <option value="taxes">Taxes uniquement</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Format</label>
                                        <select class="form-control" id="export-format" name="export_format">
                                            <option value="excel">Excel (.xlsx)</option>
                                            <option value="csv">CSV</option>
                                            <option value="json">JSON</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success" onclick="exportData()">
                                        📤 Exporter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour édition de tarif -->
    <div id="edit-rate-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Modifier le tarif</h3>
                <button class="modal-close" onclick="closeModal('edit-rate-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rate-form">
                    <input type="hidden" id="rate-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Transporteur *</label>
                            <select class="form-control" id="rate-carrier" name="carrier" required>
                                <option value="heppner">Heppner</option>
                                <option value="xpo">XPO</option>
                                <option value="kn">Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Département *</label>
                            <input type="text" class="form-control" id="rate-department" name="department" placeholder="Ex: 67" pattern="[0-9]{2}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>0-9 kg</label>
                            <input type="number" class="form-control" id="rate-0-9" name="tarif_0_9" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>10-19 kg</label>
                            <input type="number" class="form-control" id="rate-10-19" name="tarif_10_19" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>90-99 kg</label>
                            <input type="number" class="form-control" id="rate-90-99" name="tarif_90_99" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>100-299 kg</label>
                            <input type="number" class="form-control" id="rate-100-299" name="tarif_100_299" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>500-999 kg</label>
                            <input type="number" class="form-control" id="rate-500-999" name="tarif_500_999" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Délai de livraison</label>
                            <input type="text" class="form-control" id="rate-delay" name="delais" placeholder="Ex: 24h, 48h-72h">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-rate-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveRate()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour édition d'option -->
    <div id="edit-option-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚙️ Modifier l'option</h3>
                <button class="modal-close" onclick="closeModal('edit-option-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="option-form">
                    <input type="hidden" id="option-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Transporteur *</label>
                            <select class="form-control" id="option-carrier" name="transporteur" required>
                                <option value="heppner">Heppner</option>
                                <option value="xpo">XPO</option>
                                <option value="kn">Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Code de l'option *</label>
                            <input type="text" class="form-control" id="option-code" name="code_option" placeholder="Ex: rdv, premium13" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Libellé *</label>
                        <input type="text" class="form-control" id="option-label" name="libelle" placeholder="Ex: Prise de RDV" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Montant *</label>
                            <input type="number" class="form-control" id="option-amount" name="montant" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Unité *</label>
                            <select class="form-control" id="option-unit" name="unite" required>
                                <option value="forfait">Forfait</option>
                                <option value="palette">Par palette</option>
                                <option value="pourcentage">Pourcentage</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="option-active" name="actif" checked> Option active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-option-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveOption()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
