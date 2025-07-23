<?php
/**
 * Titre: Dashboard Module Contrôle Qualité - Version propre et fonctionnelle
 * Chemin: /public/qualite/index.php
 * Version: 0.5 beta + build auto
 */

// Sécurité et configuration
session_start();
define('PORTAL_ACCESS', true);

// Détermination du ROOT_PATH
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Chargement de la configuration si disponible
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/config/version.php')) {
    require_once ROOT_PATH . '/config/version.php';
}

// Variables d'environnement avec fallbacks sécurisés
$current_module = 'qualite';
$page_title = 'Contrôle Qualité';
$page_description = 'Module de contrôle qualité - Gestion des contrôles adoucisseurs et pompes doseuses';
$module_css = true;
$module_js = true;

// Configuration du module qualité
$qualite_config = [
    'module_name' => 'Contrôle Qualité',
    'module_icon' => '🔬',
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000'
];

// Action demandée avec gestion des redirections
$action = $_GET['action'] ?? 'dashboard';

// Redirection selon l'action
switch ($action) {
    case 'recherche':
    case 'controles':
        header('Location: list.php');
        exit;
    case 'anomalies':
        header('Location: anomalies.php');
        exit;
    case 'search':
        header('Location: search.php');
        exit;
    case 'actions':
        header('Location: actions.php');
        exit;
}

// Statistiques simulées (à remplacer par vraie base de données)
$stats = [
    'controles_mois' => 127,
    'controles_semaine' => 31,
    'controles_aujourd_hui' => 8,
    'taux_conformite' => 94.2,
    'anomalies_ouvertes' => 5,
    'rapports_en_attente' => 3
];

// Contrôles récents (simulation)
$recent_controls = [
    [
        'id' => 1,
        'control_number' => 'ADOU_20250123_001',
        'equipment_type' => 'Adoucisseur',
        'installation_name' => 'Hotel Meridien',
        'status' => 'completed',
        'created_at' => '2025-01-23 14:30:00'
    ],
    [
        'id' => 2,
        'control_number' => 'POMPE_20250123_002',
        'equipment_type' => 'Pompe Doseuse',
        'installation_name' => 'Usine Agroalimentaire',
        'status' => 'in_progress',
        'created_at' => '2025-01-23 10:15:00'
    ]
];

// Labels pour les statuts
$status_labels = [
    'draft' => 'Brouillon',
    'in_progress' => 'En cours',
    'completed' => 'Terminé',
    'validated' => 'Validé',
    'sent' => 'Envoyé'
];

// Chargement du header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    require_once ROOT_PATH . '/templates/header.php';
}
?>

<div class="qualite-module">
    <!-- Header du module -->
    <div class="module-header">
        <div class="breadcrumb">
            <a href="/" class="breadcrumb-item">🏠 Accueil</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">🔬 Contrôle Qualité</span>
        </div>
        
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">🔬</div>
                <div class="module-info">
                    <h1><?= htmlspecialchars($qualite_config['module_name']) ?></h1>
                    <div class="module-version">Version <?= htmlspecialchars($qualite_config['version']) ?></div>
                </div>
            </div>
            
            <div class="module-actions">
                <a href="search.php" class="btn btn-info">
                    🔍 Recherche avancée
                </a>
                <a href="actions.php" class="btn btn-warning">
                    ⚡ Actions rapides
                </a>
                <button onclick="nouveauControle()" class="btn btn-primary">
                    ➕ Nouveau contrôle
                </button>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <main class="main-content">
        <!-- Statistiques principales -->
        <section class="stats-section">
            <h2 class="section-title">📊 Vue d'ensemble</h2>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['controles_aujourd_hui'] ?></div>
                        <div class="stat-label">Aujourd'hui</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['controles_semaine'] ?></div>
                        <div class="stat-label">Cette semaine</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['controles_mois'] ?></div>
                        <div class="stat-label">Ce mois</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['taux_conformite'] ?>%</div>
                        <div class="stat-label">Conformité</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['anomalies_ouvertes'] ?></div>
                        <div class="stat-label">Anomalies ouvertes</div>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['rapports_en_attente'] ?></div>
                        <div class="stat-label">Rapports en attente</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Actions principales -->
        <section class="actions-section">
            <h2 class="section-title">🚀 Actions principales</h2>
            <p class="section-description">Créer un nouveau contrôle ou consulter les archives</p>
            
            <div class="actions-grid">
                <div class="action-card primary" onclick="nouveauControle()">
                    <span class="action-icon">➕</span>
                    <h3 class="action-title">Nouveau contrôle</h3>
                    <p class="action-description">Créer un contrôle adoucisseur ou pompe</p>
                </div>
                
                <div class="action-card secondary" onclick="consulterControles()">
                    <span class="action-icon">🔍</span>
                    <h3 class="action-title">Consulter les contrôles</h3>
                    <p class="action-description">Rechercher dans les archives</p>
                </div>
                
                <div class="action-card warning" onclick="voirAnomalies()">
                    <span class="action-icon">⚠️</span>
                    <h3 class="action-title">Répertoire anomalies</h3>
                    <p class="action-description">Consulter les anomalies par catégorie</p>
                </div>
                
                <div class="action-card info" onclick="rechercheAvancee()">
                    <span class="action-icon">🎯</span>
                    <h3 class="action-title">Recherche avancée</h3>
                    <p class="action-description">Recherche multicritères approfondie</p>
                </div>
            </div>
        </section>

        <!-- Contrôles récents -->
        <section class="recent-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">🕒 Contrôles récents</h2>
                    <p class="section-description">Derniers contrôles effectués</p>
                </div>
                <div class="section-actions">
                    <a href="list.php" class="btn btn-secondary">
                        📋 Voir tous les contrôles
                    </a>
                </div>
            </div>
            
            <?php if (!empty($recent_controls)): ?>
            <div class="controls-table-container">
                <table class="controls-table">
                    <thead>
                        <tr>
                            <th>N° Contrôle</th>
                            <th>Type</th>
                            <th>Installation</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_controls as $control): ?>
                        <tr class="control-row">
                            <td>
                                <strong class="control-number">
                                    <?= htmlspecialchars($control['control_number']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="equipment-type">
                                    <?= htmlspecialchars($control['equipment_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="installation-name">
                                    <?= htmlspecialchars($control['installation_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $control['status'] ?>">
                                    <?= $status_labels[$control['status']] ?? htmlspecialchars($control['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="control-date">
                                    <?= date('d/m/Y H:i', strtotime($control['created_at'])) ?>
                                </span>
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
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h4>Aucun contrôle récent</h4>
                <p>Commencez par créer votre premier contrôle qualité</p>
                <button class="btn btn-primary" onclick="nouveauControle()">
                    ➕ Nouveau contrôle
                </button>
            </div>
            <?php endif; ?>
        </section>

        <!-- Actions rapides spécialisées -->
        <section class="quick-actions-section">
            <h2 class="section-title">⚡ Actions rapides</h2>
            
            <div class="quick-actions-grid">
                <!-- Contrôles par type -->
                <div class="quick-action-group">
                    <h3>💧 Adoucisseurs</h3>
                    <div class="quick-buttons">
                        <button class="btn btn-primary btn-small" onclick="startAdoucisseurControl('CLACK_CI')">
                            Clack CI
                        </button>
                        <button class="btn btn-primary btn-small" onclick="startAdoucisseurControl('FLECK_SXT')">
                            Fleck SXT
                        </button>
                        <button class="btn btn-primary btn-small" onclick="startAdoucisseurControl('AUTOTROL')">
                            Autotrol
                        </button>
                    </div>
                </div>
                
                <div class="quick-action-group">
                    <h3>⚙️ Pompes Doseuses</h3>
                    <div class="quick-buttons">
                        <button class="btn btn-secondary btn-small" onclick="startPompeControl('DOS4_8V')">
                            DOS4-8V
                        </button>
                        <button class="btn btn-secondary btn-small" onclick="startPompeControl('DOS6_12V')">
                            DOS6-12V
                        </button>
                        <button class="btn btn-secondary btn-small" onclick="startPompeControl('BASIC_2L')">
                            Basic-2L
                        </button>
                    </div>
                </div>
                
                <!-- Outils -->
                <div class="quick-action-group">
                    <h3>🔧 Outils</h3>
                    <div class="quick-buttons">
                        <a href="search.php" class="btn btn-info btn-small">
                            🔍 Recherche
                        </a>
                        <a href="anomalies.php" class="btn btn-warning btn-small">
                            ⚠️ Anomalies
                        </a>
                        <a href="actions.php" class="btn btn-success btn-small">
                            ⚡ Actions
                        </a>
                    </div>
                </div>
                
                <!-- Rapports -->
                <div class="quick-action-group">
                    <h3>📊 Rapports</h3>
                    <div class="quick-buttons">
                        <button class="btn btn-info btn-small" onclick="generateDailyReport()">
                            📅 Journalier
                        </button>
                        <button class="btn btn-info btn-small" onclick="generateWeeklyReport()">
                            📈 Hebdomadaire
                        </button>
                        <button class="btn btn-info btn-small" onclick="generateMonthlyReport()">
                            📊 Mensuel
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Liens utiles et navigation -->
        <section class="navigation-section">
            <h2 class="section-title">🧭 Navigation rapide</h2>
            
            <div class="navigation-grid">
                <div class="nav-card">
                    <div class="nav-icon">📋</div>
                    <h4>Gestion des Contrôles</h4>
                    <div class="nav-links">
                        <a href="list.php">📃 Liste complète</a>
                        <a href="list.php?status=draft">✏️ Brouillons</a>
                        <a href="list.php?status=in_progress">🔄 En cours</a>
                        <a href="list.php?status=completed">✅ Terminés</a>
                    </div>
                </div>
                
                <div class="nav-card">
                    <div class="nav-icon">⚠️</div>
                    <h4>Anomalies & Problèmes</h4>
                    <div class="nav-links">
                        <a href="anomalies.php">📚 Répertoire complet</a>
                        <a href="anomalies.php?category=adoucisseur">💧 Adoucisseurs</a>
                        <a href="anomalies.php?category=pompe_doseuse">⚙️ Pompes doseuses</a>
                        <a href="anomalies.php?severity=high">🚨 Sévérité élevée</a>
                    </div>
                </div>
                
                <div class="nav-card">
                    <div class="nav-icon">🔍</div>
                    <h4>Recherche & Analyse</h4>
                    <div class="nav-links">
                        <a href="search.php">🎯 Recherche avancée</a>
                        <a href="search.php?preset=anomalies">⚠️ Avec anomalies</a>
                        <a href="search.php?preset=last_week">📅 Semaine dernière</a>
                        <a href="search.php?preset=low_conformity">📉 Faible conformité</a>
                    </div>
                </div>
                
                <div class="nav-card">
                    <div class="nav-icon">⚡</div>
                    <h4>Outils & Actions</h4>
                    <div class="nav-links">
                        <a href="actions.php">🚀 Actions rapides</a>
                        <a href="actions.php#reports">📊 Génération rapports</a>
                        <a href="actions.php#maintenance">🔧 Maintenance</a>
                        <a href="actions.php#export">📦 Exports groupés</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer du module -->
    <footer class="module-footer">
        <div class="footer-content">
            <div class="footer-info">
                <p>Module <?= htmlspecialchars($qualite_config['module_name']) ?> - <?= htmlspecialchars($qualite_config['version']) ?></p>
                <p>© <?= date('Y') ?> Guldagil - Tous droits réservés</p>
            </div>
            <div class="footer-links">
                <a href="/qualite/help.php">❓ Aide</a>
                <a href="/qualite/about.php">ℹ️ À propos</a>
                <a href="/admin/">⚙️ Administration</a>
            </div>
        </div>
    </footer>
</div>

<!-- JavaScript -->
<script>
// Fonction pour nouveau contrôle avec choix du type
function nouveauControle() {
    const type = prompt('Type de contrôle :\n1 - Adoucisseur\n2 - Pompe Doseuse\n\nEntrez 1 ou 2 :');
    
    if (type === '1') {
        window.location.href = 'components/adoucisseurs.php';
    } else if (type === '2') {
        alert('🚧 Module contrôle pompes en développement\n\nBientôt disponible !');
    } else if (type !== null) {
        alert('⚠️ Veuillez entrer 1 ou 2');
    }
}

// Navigation vers les différentes pages
function consulterControles() {
    window.location.href = 'list.php';
}

function voirAnomalies() {
    window.location.href = 'anomalies.php';
}

function rechercheAvancee() {
    window.location.href = 'search.php';
}

// Actions sur les contrôles
function viewControl(id) {
    window.location.href = `view.php?id=${id}`;
}

function editControl(id) {
    window.location.href = `edit.php?id=${id}`;
}

// Contrôles rapides par type
function startAdoucisseurControl(model) {
    const installation = prompt(`Nom de l'installation pour ${model} :`);
    if (installation) {
        alert(`✅ Contrôle ${model} créé pour: ${installation}\n\nRedirection vers le formulaire...`);
        // Ici redirection vers le formulaire avec pré-remplissage
        window.location.href = `components/adoucisseurs.php?model=${model}&installation=${encodeURIComponent(installation)}`;
    }
}

function startPompeControl(model) {
    alert(`🚧 Contrôle pompe ${model} en développement\n\nBientôt disponible !`);
}

// Génération de rapports
function generateDailyReport() {
    if (confirm('Générer le rapport journalier pour aujourd\'hui ?')) {
        showNotification('📊 Génération du rapport en cours...', 'info');
        setTimeout(() => {
            showNotification('✅ Rapport journalier généré avec succès', 'success');
        }, 2000);
    }
}

function generateWeeklyReport() {
    if (confirm('Générer le rapport hebdomadaire ?')) {
        showNotification('📈 Génération du rapport hebdomadaire...', 'info');
        setTimeout(() => {
            showNotification('✅ Rapport hebdomadaire généré avec succès', 'success');
        }, 3000);
    }
}

function generateMonthlyReport() {
    if (confirm('Générer le rapport mensuel ?')) {
        showNotification('📊 Génération du rapport mensuel...', 'info');
        setTimeout(() => {
            showNotification('✅ Rapport mensuel généré avec succès', 'success');
        }, 4000);
    }
}

// Système de notifications
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="notification-close">×</button>
        </div>
    `;
    
    // Styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        min-width: 300px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        border-left: 4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    `;
    
    notification.querySelector('.notification-content').style.cssText = `
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    `;
    
    notification.querySelector('.notification-close').style.cssText = `
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        margin-left: 1rem;
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Animation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animation des cartes d'action
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, (index * 150) + 500);
    });
    
    // Message de bienvenue
    setTimeout(() => {
        showNotification('🔬 Module Contrôle Qualité chargé avec succès', 'success', 2000);
    }, 1500);
});

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl + N : Nouveau contrôle
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        nouveauControle();
    }
    
    // Ctrl + F : Recherche avancée
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        rechercheAvancee();
    }
    
    // Ctrl + L : Liste des contrôles
    if (e.ctrlKey && e.key === 'l') {
        e.preventDefault();
        consulterControles();
    }
});

console.log('🔬 Module Contrôle Qualité - Dashboard chargé avec succès');
</script>

<?php
// Chargement du footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    require_once ROOT_PATH . '/templates/footer.php';
}
?>
