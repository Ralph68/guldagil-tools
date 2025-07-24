<?php
/**
 * Titre: Page Liste des Contrôles - Module Contrôle Qualité
 * Chemin: /public/qualite/list.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
session_start();
define('PORTAL_ACCESS', true);
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Variables
$current_module = 'qualite';
$page_title = 'Liste des Contrôles';

// Paramètres
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Données simulées
$mock_controls = [
    [
        'id' => 1,
        'control_number' => 'ADOU_20250123_001',
        'equipment_type' => 'Adoucisseur',
        'equipment_model' => 'Clack CI',
        'installation_name' => 'Installation Test 1',
        'agency_code' => 'GUL31',
        'status' => 'completed',
        'created_at' => '2025-01-23 14:30:00',
        'prepared_by' => 'J. Technicien'
    ],
    [
        'id' => 2,
        'control_number' => 'POMPE_20250123_002',
        'equipment_type' => 'Pompe Doseuse',
        'equipment_model' => 'DOS4-8V',
        'installation_name' => 'Installation Test 2',
        'agency_code' => 'GUL82',
        'status' => 'in_progress',
        'created_at' => '2025-01-23 10:15:00',
        'prepared_by' => 'M. Contrôleur'
    ]
];

// Filtrage
$filtered_controls = $mock_controls;
if ($search) {
    $filtered_controls = array_filter($filtered_controls, function($control) use ($search) {
        return stripos($control['control_number'], $search) !== false ||
               stripos($control['installation_name'], $search) !== false;
    });
}
if ($status_filter) {
    $filtered_controls = array_filter($filtered_controls, function($control) use ($status_filter) {
        return $control['status'] === $status_filter;
    });
}

$total_controls = count($filtered_controls);
$current_controls = array_slice($filtered_controls, ($page - 1) * $per_page, $per_page);

$status_labels = [
    'draft' => 'Brouillon',
    'in_progress' => 'En cours',
    'completed' => 'Terminé',
    'validated' => 'Validé',
    'sent' => 'Envoyé'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Contrôle Qualité</title>
    
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        padding: 2rem;
        background: linear-gradient(135deg, #f0fdf4 0%, #f9fafb 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .header {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        border-left: 4px solid #10b981;
    }

    .header h1 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
        font-size: 1.875rem;
    }

    .breadcrumb {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .breadcrumb a {
        color: #10b981;
        text-decoration: none;
    }

    .filters {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
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
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: #10b981;
        color: white;
    }

    .btn-primary:hover {
        background: #059669;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }

    .table-section {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .table tr:hover {
        background: #f9fafb;
    }

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

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .debug {
        margin-top: 2rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #6b7280;
        border-left: 4px solid #3b82f6;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .table-section {
            overflow-x: auto;
        }
    }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="breadcrumb">
            <a href="/">🏠 Accueil</a> › 
            <a href="/qualite/">🔬 Contrôle Qualité</a> › 
            <span>📋 Liste des Contrôles</span>
        </div>
        <h1>📋 Liste des Contrôles</h1>
        <p><?= $total_controls ?> contrôle(s) trouvé(s)</p>
        <div>
            <a href="/qualite/" class="btn btn-secondary">← Retour Dashboard</a>
            <button onclick="nouveauControle()" class="btn btn-primary">➕ Nouveau contrôle</button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters">
        <form method="GET" class="filters-form">
            <div class="filters-grid">
                <div class="form-group">
                    <label for="search">🔍 Recherche</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="N° contrôle, installation...">
                </div>
                
                <div class="form-group">
                    <label for="status">📊 Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <?php foreach ($status_labels as $status => $label): ?>
                        <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                </div>
            </div>
        </form>
    </div>
    <!-- Tableau des résultats -->
    <div class="table-section">
        <?php if (!empty($current_controls)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>N° Contrôle</th>
                    <th>Type / Modèle</th>
                    <th>Installation</th>
                    <th>Agence</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Technicien</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($current_controls as $control): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($control['control_number']) ?></strong>
                    </td>
                    <td>
                        <div>
                            <?= htmlspecialchars($control['equipment_type']) ?>
                            <br>
                            <small style="color: #6b7280;">
                                <?= htmlspecialchars($control['equipment_model']) ?>
                            </small>
                        </div>
                    </td>
                    <td>
                        <?= htmlspecialchars($control['installation_name']) ?>
                    </td>
                    <td>
                        <span class="agency-badge">
                            <?= htmlspecialchars($control['agency_code']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $control['status'] ?>">
                            <?= $status_labels[$control['status']] ?? htmlspecialchars($control['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($control['created_at'])) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($control['prepared_by']) ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-small btn-secondary" 
                                    onclick="viewControl(<?= $control['id'] ?>)"
                                    title="Voir le détail">
                                👁️
                            </button>
                            <?php if (in_array($control['status'], ['draft', 'in_progress'])): ?>
                            <button class="btn btn-small btn-primary"
                                    onclick="editControl(<?= $control['id'] ?>)"
                                    title="Modifier">
                                ✏️
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>Aucun contrôle trouvé</h3>
            <p>Aucun contrôle ne correspond à vos critères de recherche.</p>
            <button onclick="nouveauControle()" class="btn btn-primary">
                ➕ Créer un nouveau contrôle
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Information de debug -->
    <div class="debug">
        <strong>Debug Info:</strong><br>
        ROOT_PATH: <?= ROOT_PATH ?><br>
        Fichier: <?= __FILE__ ?><br>
        Total contrôles: <?= $total_controls ?><br>
        Filtres: search=<?= htmlspecialchars($search) ?>, status=<?= htmlspecialchars($status_filter) ?><br>
        PHP Version: <?= PHP_VERSION ?><br>
        Mémoire: <?= round(memory_get_usage() / 1024 / 1024, 2) ?> MB<br>
        Heure: <?= date('Y-m-d H:i:s') ?>
    </div>
</div>

<script>
function nouveauControle() {
    const type = prompt('Type de contrôle :\n1 - Adoucisseur\n2 - Pompe Doseuse\n\nEntrez 1 ou 2 :');
    
    if (type === '1') {
        window.location.href = 'components/adoucisseurs.php';
    } else if (type === '2') {
        alert('🚧 Module contrôle pompes en développement');
    } else if (type !== null) {
        alert('⚠️ Veuillez entrer 1 ou 2');
    }
}

function viewControl(id) {
    alert('👁️ Affichage du contrôle #' + id + '\n(Fonctionnalité en développement)');
}

function editControl(id) {
    alert('✏️ Modification du contrôle #' + id + '\n(Fonctionnalité en développement)');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Page Liste des Contrôles chargée');
    console.log('Statistiques:', {
        total: <?= $total_controls ?>,
        search: '<?= addslashes($search) ?>',
        status: '<?= addslashes($status_filter) ?>'
    });
});

console.log('✅ Module Liste Contrôles Qualité chargé avec succès');
</script>

</body>
</html>

<?php
// Debug final
if (defined('DEBUG') && DEBUG) {
    echo "<!-- DEBUG: Fin de list.php -->\n";
}
?>