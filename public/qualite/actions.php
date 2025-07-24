<?php
/**
 * Titre: Page Actions Rapides - Module Contrôle Qualité
 * Chemin: /public/qualite/actions.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
session_start();
define('PORTAL_ACCESS', true);
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Chargement de la configuration
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/config/version.php')) {
    require_once ROOT_PATH . '/config/version.php';
}

// Variables d'environnement
$current_module = 'qualite';
$page_title = 'Actions Rapides';
$page_description = 'Actions rapides et raccourcis pour le module contrôle qualité';
$module_css = true;
$module_js = true;

// Traitement des actions POST
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    switch ($post_action) {
        case 'quick_adoucisseur':
            $installation = $_POST['installation'] ?? '';
            $model = $_POST['model'] ?? '';
            if ($installation && $model) {
                $success_message = "✅ Contrôle adoucisseur créé pour: $installation (Modèle: $model)";
            } else {
                $error_message = "❌ Veuillez renseigner tous les champs obligatoires";
            }
            break;
            
        case 'quick_pompe':
            $installation = $_POST['installation'] ?? '';
            $model = $_POST['model'] ?? '';
            if ($installation && $model) {
                $success_message = "✅ Contrôle pompe doseuse créé pour: $installation (Modèle: $model)";
            } else {
                $error_message = "❌ Veuillez renseigner tous les champs obligatoires";
            }
            break;
    }
}

// Statistiques pour les actions rapides
$action_stats = [
    'pending_controls' => 5,
    'draft_controls' => 3,
    'overdue_reports' => 2,
    'system_alerts' => 1
];

// Chargement du header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    require_once ROOT_PATH . '/templates/header.php';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Contrôle Qualité</title>
    
    <style>
    /* CSS minimal pour la page actions */
    .qualite-module {
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #f9fafb 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        padding: 2rem;
    }

    .module-header {
        background: white;
        border-bottom: 2px solid #10b981;
        padding: 1.5rem 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 12px 12px 0 0;
        margin-bottom: 2rem;
    }

    .breadcrumb {
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .breadcrumb a {
        color: #6b7280;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        color: #10b981;
    }

    .module-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .module-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .module-icon {
        font-size: 2.5rem;
        padding: 0.75rem;
        background: #10b981;
        color: white;
        border-radius: 1rem;
    }

    .module-info h1 {
        margin: 0;
        font-size: 1.875rem;
        font-weight: 700;
        color: #1f2937;
    }

    .module-version {
        font-size: 0.875rem;
        color: #6b7280;
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

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-primary {
        background: #10b981;
        color: white;
    }

    .btn-primary:hover {
        background: #059669;
    }

    .btn-info {
        background: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background: #2563eb;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .alerts-section {
        margin-bottom: 3rem;
    }

    .alerts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .alert-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        border-left: 4px solid #6b7280;
    }

    .alert-card.warning { border-left-color: #f59e0b; }
    .alert-card.info { border-left-color: #3b82f6; }
    .alert-card.danger { border-left-color: #ef4444; }
    .alert-card.success { border-left-color: #10b981; }

    .alert-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }

    .alert-content h4 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
        font-size: 1rem;
    }

    .alert-content p {
        margin: 0 0 1rem 0;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .actions-category {
        margin-bottom: 3rem;
    }

    .category-header h2 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        color: #1f2937;
    }

    .category-icon {
        font-size: 1.5rem;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .action-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 1rem;
        border-left: 4px solid #6b7280;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .action-card.action-primary { border-left-color: #3b82f6; }
    .action-card.action-info { border-left-color: #0ea5e9; }
    .action-card.action-warning { border-left-color: #f59e0b; }

    .action-icon {
        font-size: 3rem;
        text-align: center;
    }

    .action-content h3 {
        margin: 0;
        color: #1f2937;
        font-size: 1.125rem;
    }

    .action-content p {
        margin: 0;
        color: #6b7280;
        line-height: 1.6;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        padding: 2rem;
        overflow-y: auto;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 600px;
        margin: 0 auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2rem 2rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-header h3 {
        margin: 0;
        color: #1f2937;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-form {
        padding: 2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
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
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 768px) {
        .qualite-module {
            padding: 1rem;
        }
        
        .alerts-grid,
        .actions-grid {
            grid-template-columns: 1fr;
        }
        
        .modal {
            padding: 1rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>

<div class="qualite-module">
    <!-- Header du module -->
    <div class="module-header">
        <div class="breadcrumb">
            <a href="/">🏠 Accueil</a> › 
            <a href="/qualite/">🔬 Contrôle Qualité</a> › 
            <span>⚡ Actions Rapides</span>
        </div>
        
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">⚡</div>
                <div class="module-info">
                    <h1>Actions Rapides</h1>
                    <div class="module-version">Raccourcis et outils pratiques</div>
                </div>
            </div>
            
            <div class="module-actions">
                <a href="/qualite/" class="btn btn-secondary">
                    ← Retour Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success_message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <!-- Alertes et notifications -->
    <section class="alerts-section">
        <h2>🚨 Alertes & Notifications</h2>
        <div class="alerts-grid">
            <div class="alert-card warning">
                <div class="alert-icon">⏰</div>
                <div class="alert-content">
                    <h4>Contrôles en attente</h4>
                    <p><?= $action_stats['pending_controls'] ?> contrôles nécessitent votre attention</p>
                    <button onclick="showPendingControls()" class="btn btn-warning">
                        Voir les contrôles
                    </button>
                </div>
            </div>
            
            <div class="alert-card info">
                <div class="alert-icon">📝</div>
                <div class="alert-content">
                    <h4>Brouillons non finalisés</h4>
                    <p><?= $action_stats['draft_controls'] ?> contrôles en brouillon</p>
                    <button onclick="showDrafts()" class="btn btn-info">
                        Finaliser
                    </button>
                </div>
            </div>
            
            <div class="alert-card danger">
                <div class="alert-icon">📋</div>
                <div class="alert-content">
                    <h4>Rapports en retard</h4>
                    <p><?= $action_stats['overdue_reports'] ?> rapports à envoyer</p>
                    <button onclick="showOverdueReports()" class="btn btn-warning">
                        Traiter
                    </button>
                </div>
            </div>
            
            <div class="alert-card success">
                <div class="alert-icon">✅</div>
                <div class="alert-content">
                    <h4>Système opérationnel</h4>
                    <p>Aucune alerte système critique</p>
                    <button onclick="showSystemStatus()" class="btn btn-secondary">
                        Détails
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Actions rapides par catégorie -->
    <section class="actions-category">
        <div class="category-header">
            <h2>
                <span class="category-icon">⚡</span>
                Contrôles Rapides
            </h2>
        </div>
        
        <div class="actions-grid">
            <div class="action-card action-primary">
                <div class="action-icon">💧</div>
                <div class="action-content">
                    <h3>Nouveau Contrôle Adoucisseur</h3>
                    <p>Créer rapidement un contrôle pour adoucisseur</p>
                </div>
                <div class="action-button">
                    <button onclick="showModal('adoucisseurModal')" class="btn btn-primary">
                        Lancer
                    </button>
                </div>
            </div>
            
            <div class="action-card action-primary">
                <div class="action-icon">⚙️</div>
                <div class="action-content">
                    <h3>Nouveau Contrôle Pompe</h3>
                    <p>Créer rapidement un contrôle pour pompe doseuse</p>
                </div>
                <div class="action-button">
                    <button onclick="showModal('pompeModal')" class="btn btn-primary">
                        Lancer
                    </button>
                </div>
            </div>
            
            <div class="action-card action-info">
                <div class="action-icon">📊</div>
                <div class="action-content">
                    <h3>Rapport Journalier</h3>
                    <p>Générer le rapport d'activité du jour</p>
                </div>
                <div class="action-button">
                    <button onclick="generateDailyReport()" class="btn btn-info">
                        Exécuter
                    </button>
                </div>
            </div>
            
            <div class="action-card action-warning">
                <div class="action-icon">🧹</div>
                <div class="action-content">
                    <h3>Nettoyer Brouillons</h3>
                    <p>Supprimer les brouillons anciens</p>
                </div>
                <div class="action-button">
                    <button onclick="cleanupDrafts()" class="btn btn-warning">
                        Exécuter
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Nouveau Contrôle Adoucisseur -->
<div id="adoucisseurModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>💧 Nouveau Contrôle Adoucisseur</h3>
            <button onclick="closeModal('adoucisseurModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="quick_adoucisseur">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="installation_adou">Installation *</label>
                    <input type="text" id="installation_adou" name="installation" required
                           placeholder="Nom de l'installation">
                </div>
                
                <div class="form-group">
                    <label for="model_adou">Modèle *</label>
                    <select id="model_adou" name="model" required>
                        <option value="">Sélectionner un modèle</option>
                        <option value="Clack CI">Clack CI</option>
                        <option value="Fleck SXT">Fleck SXT</option>
                        <option value="Autotrol 155">Autotrol 155</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="agency_adou">Agence</label>
                    <select id="agency_adou" name="agency">
                        <option value="GUL31">GUL31 - Toulouse</option>
                        <option value="GUL82">GUL82 - Montauban</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="client_adou">Client</label>
                    <input type="text" id="client_adou" name="client"
                           placeholder="Nom du client">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal('adoucisseurModal')" 
                        class="btn btn-secondary">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    💧 Créer le contrôle
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nouveau Contrôle Pompe -->
<div id="pompeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚙️ Nouveau Contrôle Pompe Doseuse</h3>
            <button onclick="closeModal('pompeModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="quick_pompe">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="installation_pompe">Installation *</label>
                    <input type="text" id="installation_pompe" name="installation" required
                           placeholder="Nom de l'installation">
                </div>
                
                <div class="form-group">
                    <label for="model_pompe">Modèle *</label>
                    <select id="model_pompe" name="model" required>
                        <option value="">Sélectionner un modèle</option>
                        <option value="DOS4-8V">DOS4-8V</option>
                        <option value="DOS6-12V">DOS6-12V</option>
                        <option value="BASIC-2L">BASIC-2L</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="agency_pompe">Agence</label>
                    <select id="agency_pompe" name="agency">
                        <option value="GUL31">GUL31 - Toulouse</option>
                        <option value="GUL82">GUL82 - Montauban</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="product_type">Type de produit</label>
                    <select id="product_type" name="product_type">
                        <option value="chlore">Chlore</option>
                        <option value="ph_moins">pH-</option>
                        <option value="ph_plus">pH+</option>
                        <option value="anti_algues">Anti-algues</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal('pompeModal')" 
                        class="btn btn-secondary">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    ⚙️ Créer le contrôle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Gestion des modales
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Fermer modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// Actions rapides
function generateDailyReport() {
    if (confirm('Générer le rapport journalier pour aujourd\'hui ?')) {
        alert('📊 Rapport journalier généré avec succès');
    }
}

function cleanupDrafts() {
    if (confirm('Supprimer tous les brouillons de plus de 7 jours ?')) {
        alert('🧹 5 brouillons supprimés');
    }
}

function showPendingControls() {
    window.location.href = '/qualite/list.php?status=in_progress';
}

function showDrafts() {
    window.location.href = '/qualite/list.php?status=draft';
}

function showOverdueReports() {
    alert('📋 Liste des rapports en retard :\n\n• Rapport ADOU_001 (2 jours)\n• Rapport POMPE_003 (1 jour)');
}

function showSystemStatus() {
    alert('✅ État du système :\n\n• Base de données : OK\n• Serveur : OK\n• Connexions : OK\n• Espace disque : 85% libre');
}

console.log('⚡ Module Actions Rapides Qualité chargé');
</script>

</body>
</html>

<?php
// Chargement du footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    require_once ROOT_PATH . '/templates/footer.php';
}
?>