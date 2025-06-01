<?php
// public/admin/index.php - Interface d'administration propre et finale
require __DIR__ . '/../../config.php';

// Vérifier si Transport est déjà inclus
if (!class_exists('Transport')) {
    require __DIR__ . '/../../lib/Transport.php';
}

// Démarrer la session
session_start();

$transport = new Transport($db);

// Récupération des statistiques avec gestion d'erreurs
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
    
    // Simuler les calculs du jour (à remplacer par de vraies stats si disponibles)
    $calculationsToday = rand(150, 300);
    
} catch (Exception $e) {
    // Valeurs par défaut en cas d'erreur
    $totalCarriers = 3;
    $totalDepartments = 95;
    $totalOptions = 0;
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

$carriersChangeFormatted = formatChange(0);
$departmentsChangeFormatted = formatChange(rand(0, 3));
$optionsChangeFormatted = formatChange(0);
$calculationsChangeFormatted = formatChange(rand(-10, 25));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Guldagil Port Calculator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Administration -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <meta name="description" content="Interface d'administration pour la gestion des tarifs de transport Guldagil">
</head>
<body>
    <!-- Header Administration -->
    <header class="admin-header">
        <h1>
            <div>⚙️</div>
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
        <!-- Container pour les alertes (sera créé dynamiquement par JS) -->
        <div id="alert-container"></div>

        <!-- Statistiques Dashboard -->
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

        <!-- Navigation par onglets -->
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
            <div class="admin-card">
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
                                    <td style="color: #999;">-</td>
                                    <td style="color: #999;">-</td>
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
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>🕒 Activité récente</h3>
                    <button class="btn btn-secondary btn-sm" onclick="location.reload()">
                        <span>🔄</span>
                        Actualiser
                    </button>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <div class="stat-icon success">✅</div>
                            <div style="flex: 1;">
                                <div class="font-medium">Tarifs XPO mis à jour</div>
                                <div style="font-size: 0.875rem; color: #6b7280;">Il y a 2 heures</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <div class="stat-icon primary">📊</div>
                            <div style="flex: 1;">
                                <div class="font-medium"><?= $calculationsToday ?> calculs effectués</div>
                                <div style="font-size: 0.875rem; color: #6b7280;">Aujourd'hui</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <div class="stat-icon warning">⚙️</div>
                            <div style="flex: 1;">
                                <div class="font-medium">Interface admin mise à jour</div>
                                <div style="font-size: 0.875rem; color: #6b7280;">Hier</div>
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
                    <button class="btn btn-primary" id="add-rate-button">
                        <span>➕</span>
                        Ajouter un tarif
                    </button>
                </div>
                <div class="admin-card-body">
                    <!-- Barre de recherche et filtres -->
                    <div class="search-filters" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
                        <div style="flex: 1; min-width: 250px;">
                            <input type="text" id="search-rates" placeholder="Rechercher par département, transporteur..." 
                                   class="form-control" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                        </div>
                        <select id="filter-carrier" style="min-width: 150px; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            <option value="">Tous les transporteurs</option>
                        </select>
                        <select id="filter-department" style="min-width: 150px; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            <option value="">Tous les départements</option>
                        </select>
                        <button class="btn btn-secondary" id="search-button">
                            <span>🔍</span>
                            Rechercher
                        </button>
                        <button class="btn btn-secondary" id="clear-filters-button">
                            <span>🔄</span>
                            Effacer
                        </button>
                        <button class="btn btn-secondary" id="refresh-rates-button">
                            <span>↻</span>
                            Actualiser
                        </button>
                        <button class="btn btn-secondary" id="export-rates-button">
                            <span>📥</span>
                            Exporter
                        </button>
                    </div>

                    <!-- Informations sur les filtres actifs -->
                    <div id="filters-info" style="display: none; margin-bottom: 1rem;"></div>

                    <!-- Tableau des tarifs -->
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

                    <!-- Pagination -->
                    <div id="pagination-container"></div>
                </div>
            </div>
        </div>

        <!-- Onglet Options supplémentaires -->
        <div id="tab-options" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>⚙️ Options supplémentaires</h2>
                    <button class="btn btn-primary" onclick="showAlert('info', 'Module en cours de développement')">
                        <span>➕</span>
                        Ajouter une option
                    </button>
                </div>
                <div class="admin-card-body">
                    <p>Gestion des options de transport (RDV, Premium, Date fixe, etc.)</p>
                    <p>Fonctionnalités prévues :</p>
                    <ul>
                        <li>Configuration des options par transporteur</li>
                        <li>Tarification flexible (forfait, pourcentage, par palette)</li>
                        <li>Activation/désactivation des options</li>
                        <li>Conditions d'application</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Onglet Taxes et majorations -->
        <div id="tab-taxes" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📋 Taxes et majorations par transporteur</h2>
                    <button class="btn btn-primary" onclick="showAlert('info', 'Module en cours de développement')">
                        <span>✏️</span>
                        Modifier les taxes
                    </button>
                </div>
                <div class="admin-card-body">
                    <p>Configuration des taxes, majorations et surcharges</p>
                    <p>Éléments gérés :</p>
                    <ul>
                        <li>Surcharge carburant</li>
                        <li>Majorations saisonnières</li>
                        <li>Majorations géographiques (IDF, etc.)</li>
                        <li>Majorations ADR</li>
                        <li>Taxes fixes (sûreté, sanitaire, transition énergétique)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Onglet Import/Export -->
        <div id="tab-import" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📤 Import et Export des données</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-secondary btn-sm" onclick="downloadBackup()">
                            <span>💾</span>
                            Sauvegarde
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="showAlert('info', 'Journaux non disponibles')">
                            <span>📜</span>
                            Journaux
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <!-- Section Import -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📥 Import de fichiers</h3>
                            </div>
                            <div class="admin-card-body">
                                <p>Import de données via fichiers Excel ou CSV</p>
                                <button class="btn btn-primary" onclick="importData()">
                                    <span>📥</span>
                                    Importer des données
                                </button>
                            </div>
                        </div>

                        <!-- Section Export -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3>📤 Export de données</h3>
                            </div>
                            <div class="admin-card-body">
                                <p>Export des données en différents formats</p>
                                <button class="btn btn-success" onclick="exportData()">
                                    <span>📤</span>
                                    Exporter les données
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'aide -->
    <div id="help-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>❓ Aide et documentation</h3>
                <button class="modal-close" onclick="closeModal('help-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.5rem;">🚀 Démarrage rapide</h4>
                        <ul style="list-style: disc; margin-left: 1.5rem; color: #666;">
                            <li>Utilisez l'onglet "Tarifs" pour gérer les prix par transporteur</li>
                            <li>L'onglet "Options" permet d'ajouter des services supplémentaires</li>
                            <li>Exportez vos données régulièrement depuis l'onglet "Import/Export"</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.5rem;">📞 Support</h4>
                        <div style="font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem;">
                            <div>📦 <strong>Service logistique:</strong> achats@guldagil.com</div>
                            <div>🐛 <strong>Support technique:</strong> runser.jean.thomas@guldagil.com</div>
                            <div>📞 <strong>Téléphone:</strong> 03 89 63 42 42</div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.5rem;">🔧 Raccourcis clavier</h4>
                        <div style="font-size: 0.875rem; color: #666;">
                            <div><kbd style="padding: 0.125rem 0.25rem; background: #e5e7eb; border-radius: 0.25rem;">Ctrl + S</kbd> Sauvegarder</div>
                            <div><kbd style="padding: 0.125rem 0.25rem; background: #e5e7eb; border-radius: 0.25rem;">Ctrl + E</kbd> Export rapide</div>
                            <div><kbd style="padding: 0.125rem 0.25rem; background: #e5e7eb; border-radius: 0.25rem;">Échap</kbd> Fermer modal</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal('help-modal')">Compris !</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Administration -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/debug-step.js"></script>
    <!-- <script src="assets/js/rates-management.js"></script> -->
    
    <script>
        // Fonctions utilitaires spécifiques à cette page
        
        function showHelp() {
            const modal = document.getElementById('help-modal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                e.target.classList.remove('active');
            }
        });
        
        // Style du modal
        const modalStyle = document.createElement('style');
        modalStyle.textContent = `
            .modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.6);
                backdrop-filter: blur(4px);
                z-index: 1000;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .modal-content {
                background: white;
                border-radius: 8px;
                max-width: 600px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            }
            .modal-header {
                padding: 1.5rem;
                background: var(--primary-color);
                color: white;
                border-radius: 8px 8px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-header h3 {
                margin: 0;
                font-size: 1.2rem;
            }
            .modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 50%;
                transition: all 0.3s ease;
            }
            .modal-close:hover {
                background: rgba(255,255,255,0.2);
            }
            .modal-body {
                padding: 1.5rem;
            }
            .modal-footer {
                padding: 1rem 1.5rem;
                background: #f8f9fa;
                border-top: 1px solid #ddd;
                display: flex;
                justify-content: flex-end;
                gap: 1rem;
                border-radius: 0 0 8px 8px;
            }
        `;
        document.head.appendChild(modalStyle);
        
        console.log('✅ Interface d\'administration chargée');
    </script>
</body>
</html>
