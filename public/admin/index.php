<?php
// public/admin/index.php - Interface d'administration moderne
require __DIR__ . '/../../config.php';

// Vérifier si Transport est déjà inclus
if (!class_exists('Transport')) {
    require __DIR__ . '/../../lib/Transport.php';
}

// Vérification simple d'accès admin (à améliorer selon vos besoins)
session_start();

$transport = new Transport($db);

// Récupération des statistiques avec gestion d'erreurs améliorée
try {
    // Compter les transporteurs actifs
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs");
    $totalCarriers = $stmt->fetch()['count'] ?? 0;
    
    // Compter les départements avec tarifs (union des tables)
    $sql = "SELECT COUNT(DISTINCT num_departement) as count FROM (
                SELECT num_departement FROM gul_heppner_rates WHERE num_departement IS NOT NULL
                UNION 
                SELECT num_departement FROM gul_xpo_rates WHERE num_departement IS NOT NULL
                UNION 
                SELECT num_departement FROM gul_kn_rates WHERE num_departement IS NOT NULL
            ) as all_departments";
    $stmt = $db->query($sql);
    $totalDepartments = $stmt->fetch()['count'] ?? 0;
    
    // Compter les options actives
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE actif = 1");
    $totalOptions = $stmt->fetch()['count'] ?? 0;
    
    // Calculer les statistiques de tendance (simulation)
    $carriersChange = 0; // Aucun nouveau transporteur ce mois
    $departmentsChange = rand(0, 3); // Simulation
    $optionsChange = rand(0, 2); // Simulation
    $calculationsToday = rand(150, 300); // Simulation
    
} catch (Exception $e) {
    // Valeurs par défaut en cas d'erreur
    $totalCarriers = 3;
    $totalDepartments = 95;
    $totalOptions = 0;
    $carriersChange = 0;
    $departmentsChange = 0;
    $optionsChange = 0;
    $calculationsToday = 0;
    error_log("Erreur statistiques admin: " . $e->getMessage());
}

// Fonction pour formater les changements
function formatChange($value) {
    if ($value > 0) {
        return ['text' => "+{$value}", 'class' => 'positive', 'icon' => '↗️'];
    } elseif ($value < 0) {
        return ['text' => "{$value}", 'class' => 'negative', 'icon' => '↘️'];
    } else {
        return ['text' => "0", 'class' => 'neutral', 'icon' => '→'];
    }
}

$carriersChangeFormatted = formatChange($carriersChange);
$departmentsChangeFormatted = formatChange($departmentsChange);
$optionsChangeFormatted = formatChange($optionsChange);
$calculationsChangeFormatted = formatChange(rand(-10, 25)); // Simulation variation journalière
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Guldagil Port Calculator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        /* CSS de secours intégré pour vérifier si le fichier externe fonctionne */
        .test-css { display: none; }
    </style>
    <meta name="description" content="Interface d'administration pour la gestion des tarifs de transport">
</head>
<body>
    <!-- Header moderne -->
    <header class="admin-header">
        <h1>
            <div class="logo-icon">⚙️</div>
            <div>
                Administration
                <div class="subtitle">Guldagil Port Calculator v1.2.0</div>
            </div>
        </h1>
        <nav class="admin-nav">
            <a href="../" title="Retour au calculateur">
                <span>🏠</span>
                Calculateur
            </a>
            <a href="export.php" title="Exporter les données">
                <span>📥</span>
                Export
            </a>
            <a href="#" onclick="showHelp()" title="Aide et documentation">
                <span>❓</span>
                Aide
            </a>
            <div class="user-info">
                <span>👤</span>
                Admin
            </div>
        </nav>
    </header>

    <div class="admin-container">
        <!-- Messages d'alerte -->
        <div id="alert-container"></div>

        <!-- Statistiques dashboard modernes -->
        <div class="stats-grid">
            <div class="stat-card slide-in-up">
                <div class="stat-header">
                    <div class="stat-title">Transporteurs actifs</div>
                    <div class="stat-icon primary">🚚</div>
                </div>
                <div class="stat-value"><?= $totalCarriers ?></div>
                <div class="stat-trend <?= $carriersChangeFormatted['class'] ?>">
                    <span><?= $carriersChangeFormatted['icon'] ?></span>
                    <?= $carriersChangeFormatted['text'] ?> ce mois
                </div>
            </div>

            <div class="stat-card slide-in-up" style="animation-delay: 0.1s">
                <div class="stat-header">
                    <div class="stat-title">Départements couverts</div>
                    <div class="stat-icon success">📍</div>
                </div>
                <div class="stat-value"><?= $totalDepartments ?></div>
                <div class="stat-trend <?= $departmentsChangeFormatted['class'] ?>">
                    <span><?= $departmentsChangeFormatted['icon'] ?></span>
                    <?= $departmentsChangeFormatted['text'] ?> ce mois
                </div>
            </div>

            <div class="stat-card slide-in-up" style="animation-delay: 0.2s">
                <div class="stat-header">
                    <div class="stat-title">Options disponibles</div>
                    <div class="stat-icon warning">⚙️</div>
                </div>
                <div class="stat-value"><?= $totalOptions ?></div>
                <div class="stat-trend <?= $optionsChangeFormatted['class'] ?>">
                    <span><?= $optionsChangeFormatted['icon'] ?></span>
                    <?= $optionsChangeFormatted['text'] ?> ce mois
                </div>
            </div>

            <div class="stat-card slide-in-up" style="animation-delay: 0.3s">
                <div class="stat-header">
                    <div class="stat-title">Calculs aujourd'hui</div>
                    <div class="stat-icon primary">📊</div>
                </div>
                <div class="stat-value"><?= $calculationsToday ?></div>
                <div class="stat-trend <?= $calculationsChangeFormatted['class'] ?>">
                    <span><?= $calculationsChangeFormatted['icon'] ?></span>
                    <?= $calculationsChangeFormatted['text'] ?>% vs hier
                </div>
            </div>
        </div>

        <!-- Navigation par onglets moderne -->
        <div class="tab-navigation">
            <button class="tab-button active" onclick="showTab('dashboard')" data-tab="dashboard">
                <span>📊</span>
                Tableau de bord
            </button>
            <button class="tab-button" onclick="showTab('rates')" data-tab="rates">
                <span>💰</span>
                Gestion des tarifs
            </button>
            <button class="tab-button" onclick="showTab('options')" data-tab="options">
                <span>⚙️</span>
                Options supplémentaires
            </button>
            <button class="tab-button" onclick="showTab('taxes')" data-tab="taxes">
                <span>📋</span>
                Taxes & Majorations
            </button>
            <button class="tab-button" onclick="showTab('import')" data-tab="import">
                <span>📤</span>
                Import/Export
            </button>
        </div>

        <!-- Onglet Tableau de bord -->
        <div id="tab-dashboard" class="tab-content active">
            <div class="admin-card fade-in">
                <div class="admin-card-header">
                    <h2>📊 Aperçu des données récentes</h2>
                    <button class="btn btn-secondary btn-sm" onclick="showTab('rates')">
                        <span>👁️</span>
                        Voir tous les tarifs
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Transporteur</th>
                                    <th>Département</th>
                                    <th>Tarif 0-9kg</th>
                                    <th>Tarif 100-299kg</th>
                                    <th>Délai</th>
                                    <th>Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-semibold text-primary">Heppner</td>
                                    <td>67 - Bas-Rhin</td>
                                    <td class="font-medium">12,68 €</td>
                                    <td class="font-medium">22,97 €</td>
                                    <td><span class="badge badge-success">24h</span></td>
                                    <td><span class="badge badge-success">Actif</span></td>
                                    <td class="text-center">
                                        <div class="actions">
                                            <button class="btn btn-secondary btn-sm" onclick="editRate('heppner', '67')" title="Modifier">
                                                ✏️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-primary">XPO</td>
                                    <td>68 - Haut-Rhin</td>
                                    <td class="font-medium">35,17 €</td>
                                    <td class="font-medium">16,22 €</td>
                                    <td><span class="badge badge-success">24h-48h</span></td>
                                    <td><span class="badge badge-success">Actif</span></td>
                                    <td class="text-center">
                                        <div class="actions">
                                            <button class="btn btn-secondary btn-sm" onclick="editRate('xpo', '68')" title="Modifier">
                                                ✏️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-primary">Kuehne + Nagel</td>
                                    <td>75 - Paris</td>
                                    <td class="text-gray-400">-</td>
                                    <td class="text-gray-400">-</td>
                                    <td><span class="badge badge-info">24h-48h</span></td>
                                    <td><span class="badge badge-warning">En attente</span></td>
                                    <td class="text-center">
                                        <div class="actions">
                                            <button class="btn btn-primary btn-sm" onclick="addRate('kn', '75')" title="Ajouter tarif">
                                                ➕
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="admin-card fade-in" style="animation-delay: 0.2s">
                <div class="admin-card-header">
                    <h3>🕒 Activité récente</h3>
                    <button class="btn btn-secondary btn-sm">
                        <span>🔄</span>
                        Actualiser
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="stat-icon success">✅</div>
                            <div class="flex-1">
                                <div class="font-medium">Tarifs XPO mis à jour</div>
                                <div class="text-sm text-gray-500">Il y a 2 heures</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="stat-icon primary">📊</div>
                            <div class="flex-1">
                                <div class="font-medium">247 calculs effectués</div>
                                <div class="text-sm text-gray-500">Aujourd'hui</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="stat-icon warning">⚙️</div>
                            <div class="flex-1">
                                <div class="font-medium">Nouvelle option ajoutée</div>
                                <div class="text-sm text-gray-500">Hier</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Gestion des tarifs -->
        <div id="tab-rates" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>💰 Gestion des tarifs par transporteur</h2>
                    <button class="btn btn-primary" onclick="openModal('add-rate-modal')">
                        <span>➕</span>
                        Ajouter un tarif
                    </button>
                </div>
                <div class="admin-card-body">
                    <!-- Barre de recherche et filtres modernes -->
                    <div class="search-filters">
                        <div class="search-input">
                            <input type="text" id="search-rates" placeholder="Rechercher par département, transporteur..." class="form-control">
                        </div>
                        <select class="form-control filter-select" id="filter-carrier">
                            <option value="">Tous les transporteurs</option>
                            <option value="heppner">Heppner</option>
                            <option value="xpo">XPO</option>
                            <option value="kn">Kuehne + Nagel</option>
                        </select>
                        <button class="btn btn-secondary" onclick="loadRates()">
                            <span>🔄</span>
                            Actualiser
                        </button>
                        <button class="btn btn-secondary" onclick="exportRates()">
                            <span>📥</span>
                            Exporter
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
                                    <th class="text-center">Actions</th>
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
                    <button class="btn btn-primary" onclick="openModal('add-option-modal')">
                        <span>➕</span>
                        Ajouter une option
                    </button>
                </div>
                <div class="admin-card-body">
                    <div class="search-filters">
                        <div class="search-input">
                            <input type="text" id="search-options" placeholder="Rechercher une option..." class="form-control">
                        </div>
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
                                    <th class="text-center">Actions</th>
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
                    <h2>📋 Taxes et majorations par transporteur</h2>
                    <button class="btn btn-primary" onclick="editTaxes()">
                        <span>✏️</span>
                        Modifier les taxes
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
                    <div class="flex gap-2">
                        <button class="btn btn-secondary btn-sm" onclick="downloadBackup()">
                            <span>💾</span>
                            Sauvegarde
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="showLogs()">
                            <span>📜</span>
                            Journaux
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="import-export-grid">
                        <!-- Section Import -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📥 Import de fichiers</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="import-form" enctype="multipart/form-data">
                                    <div class="upload-zone" onclick="document.getElementById('file-input').click()" 
                                         ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-title">Cliquez pour sélectionner un fichier</div>
                                        <div class="upload-subtitle">ou glissez-déposez votre fichier Excel/CSV ici</div>
                                        <div class="upload-subtitle text-xs mt-2">Formats acceptés: .xlsx, .xls, .csv (max 10Mo)</div>
                                        <input type="file" id="file-input" name="import_file" style="display: none;" 
                                               accept=".xlsx,.xls,.csv" onchange="handleFileSelect(event)">
                                    </div>
                                    
                                    <div id="file-preview" class="hidden mt-4 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl">📄</span>
                                            <div class="flex-1">
                                                <div id="file-name" class="font-medium"></div>
                                                <div id="file-size" class="text-sm text-gray-500"></div>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeFile()">❌</button>
                                        </div>
                                    </div>

                                    <div class="upload-actions">
                                        <button type="button" class="btn btn-primary" onclick="importData()" id="import-btn" disabled>
                                            <span>📥</span>
                                            Importer les données
                                        </button>
                                        <a href="template.php" class="btn btn-secondary">
                                            <span>📋</span>
                                            Télécharger le modèle
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Section Export -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📤 Export de données</h3>
                            </div>
                            <div class="admin-card-body">
                                <form id="export-form">
                                    <div class="form-group">
                                        <label class="form-label">Type d'export</label>
                                        <select class="form-control" id="export-type" name="export_type">
                                            <option value="all">🗃️ Toutes les données</option>
                                            <option value="rates">💰 Tarifs uniquement</option>
                                            <option value="options">⚙️ Options uniquement</option>
                                            <option value="taxes">📋 Taxes uniquement</option>
                                            <option value="stats">📊 Statistiques</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Format d'export</label>
                                        <select class="form-control" id="export-format" name="export_format">
                                            <option value="excel">📊 Excel (.xlsx)</option>
                                            <option value="csv">📝 CSV</option>
                                            <option value="json">🔧 JSON</option>
                                            <option value="pdf">📄 PDF (rapport)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Transporteurs à inclure</label>
                                        <div class="flex flex-col gap-2">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="carriers[]" value="heppner" checked> Heppner
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="carriers[]" value="xpo" checked> XPO
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="carriers[]" value="kn" checked> Kuehne + Nagel
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-success w-full" onclick="exportData()">
                                        <span>📤</span>
                                        Exporter les données
                                    </button>
                                </form>

                                <!-- Exports récents -->
                                <div class="mt-6">
                                    <h4 class="font-semibold mb-3">📋 Exports récents</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center gap-2">
                                                <span>📊</span>
                                                <span class="text-sm">export_tarifs_2025-01-20.xlsx</span>
                                            </div>
                                            <div class="flex gap-1">
                                                <button class="btn btn-secondary btn-sm">📥</button>
                                                <button class="btn btn-danger btn-sm">🗑️</button>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center gap-2">
                                                <span>📋</span>
                                                <span class="text-sm">rapport_mensuel_janvier.pdf</span>
                                            </div>
                                            <div class="flex gap-1">
                                                <button class="btn btn-secondary btn-sm">📥</button>
                                                <button class="btn btn-danger btn-sm">🗑️</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modaux modernes -->
    
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
                            <label class="form-label">Transporteur *</label>
                            <select class="form-control" id="rate-carrier" name="carrier" required>
                                <option value="">Sélectionner...</option>
                                <option value="heppner">🚚 Heppner</option>
                                <option value="xpo">🚛 XPO</option>
                                <option value="kn">🚐 Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Département *</label>
                            <input type="text" class="form-control" id="rate-department" name="department" 
                                   placeholder="Ex: 67" pattern="[0-9]{2}" maxlength="2" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">0-9 kg</label>
                            <input type="number" class="form-control" id="rate-0-9" name="tarif_0_9" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">10-19 kg</label>
                            <input type="number" class="form-control" id="rate-10-19" name="tarif_10_19" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">90-99 kg</label>
                            <input type="number" class="form-control" id="rate-90-99" name="tarif_90_99" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">100-299 kg</label>
                            <input type="number" class="form-control" id="rate-100-299" name="tarif_100_299" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">500-999 kg</label>
                            <input type="number" class="form-control" id="rate-500-999" name="tarif_500_999" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Délai de livraison</label>
                            <input type="text" class="form-control" id="rate-delay" name="delais" 
                                   placeholder="Ex: 24h, 48h-72h">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-rate-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveRate()">
                    <span>💾</span>
                    Enregistrer
                </button>
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
                            <label class="form-label">Transporteur *</label>
                            <select class="form-control" id="option-carrier" name="transporteur" required>
                                <option value="">Sélectionner...</option>
                                <option value="heppner">🚚 Heppner</option>
                                <option value="xpo">🚛 XPO</option>
                                <option value="kn">🚐 Kuehne + Nagel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Code de l'option *</label>
                            <input type="text" class="form-control" id="option-code" name="code_option" 
                                   placeholder="Ex: rdv, premium13" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Libellé *</label>
                        <input type="text" class="form-control" id="option-label" name="libelle" 
                               placeholder="Ex: Prise de RDV" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Montant *</label>
                            <input type="number" class="form-control" id="option-amount" name="montant" 
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unité *</label>
                            <select class="form-control" id="option-unit" name="unite" required>
                                <option value="forfait">💰 Forfait</option>
                                <option value="palette">📦 Par palette</option>
                                <option value="pourcentage">📊 Pourcentage</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="option-active" name="actif" checked> 
                            Option active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('edit-option-modal')">Annuler</button>
                <button class="btn btn-primary" onclick="saveOption()">
                    <span>💾</span>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal d'aide -->
    <div id="help-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>❓ Aide et documentation</h3>
                <button class="modal-close" onclick="closeModal('help-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold mb-2">🚀 Démarrage rapide</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600">
                            <li>Utilisez l'onglet "Tarifs" pour gérer les prix par transporteur</li>
                            <li>L'onglet "Options" permet d'ajouter des services supplémentaires</li>
                            <li>Exportez vos données régulièrement depuis l'onglet "Import/Export"</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-2">📞 Support</h4>
                        <div class="space-y-2 text-sm">
                            <div>📦 <strong>Service logistique:</strong> achats@guldagil.com</div>
                            <div>🐛 <strong>Support technique:</strong> runser.jean.thomas@guldagil.com</div>
                            <div>📞 <strong>Téléphone:</strong> 03 89 63 42 42</div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-2">🔧 Raccourcis clavier</h4>
                        <div class="space-y-1 text-sm text-gray-600">
                            <div><kbd class="px-1 bg-gray-200 rounded">Ctrl + S</kbd> Sauvegarder</div>
                            <div><kbd class="px-1 bg-gray-200 rounded">Ctrl + E</kbd> Export rapide</div>
                            <div><kbd class="px-1 bg-gray-200 rounded">Échap</kbd> Fermer modal</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal('help-modal')">Compris !</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Scripts spécifiques à cette page
        
        // Gestion des raccourcis clavier
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        saveCurrentForm();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportData();
                        break;
                }
            }
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
        
        // Gestion du drag & drop pour l'upload
        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add('dragover');
        }
        
        function handleDragLeave(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('dragover');
        }
        
        function handleDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('file-input').files = files;
                handleFileSelect({ target: { files: files } });
            }
        }
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const preview = document.getElementById('file-preview');
                const fileName = document.getElementById('file-name');
                const fileSize = document.getElementById('file-size');
                const importBtn = document.getElementById('import-btn');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                preview.classList.remove('hidden');
                importBtn.disabled = false;
            }
        }
        
        function removeFile() {
            document.getElementById('file-input').value = '';
            document.getElementById('file-preview').classList.add('hidden');
            document.getElementById('import-btn').disabled = true;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Fonctions utilitaires
        function showHelp() {
            openModal('help-modal');
        }
        
        function saveCurrentForm() {
            // Logique de sauvegarde selon l'onglet actif
            const activeTab = document.querySelector('.tab-content.active').id;
            switch(activeTab) {
                case 'tab-rates':
                    console.log('Sauvegarde des tarifs...');
                    break;
                case 'tab-options':
                    console.log('Sauvegarde des options...');
                    break;
                default:
                    console.log('Rien à sauvegarder');
            }
        }
        
        function closeAllModals() {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
        
        // Animation d'apparition des statistiques
        function animateStats() {
            const stats = document.querySelectorAll('.stat-value');
            stats.forEach((stat, index) => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 30;
                
                const counter = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        stat.textContent = finalValue;
                        clearInterval(counter);
                    } else {
                        stat.textContent = Math.floor(currentValue);
                    }
                }, 50 + (index * 10));
            });
        }
        
        // Lancer l'animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(animateStats, 500);
        });
    </script>
</body>
</html>
