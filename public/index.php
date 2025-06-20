<?php
/**
 * public/index.php - Portail principal Guldagil
 * Version 0.5 beta - Architecture MVC modulaire
 */

// Chargement de la configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// Authentification simplifiée (développement)
session_start();
$auth_enabled = false; // Désactivé pour le développement
$user_info = [
    'username' => 'Développeur',
    'role' => 'admin',
    'authenticated' => true
];

// Récupération des statistiques rapides
try {
    $stats = [
        'calculations_today' => $db->query("SELECT COUNT(*) FROM gul_adr_expeditions WHERE DATE(date_creation) = CURDATE()")->fetchColumn() ?: 0,
        'modules_available' => count(array_filter(MODULES, fn($m) => $m['enabled'])),
        'system_status' => 'operational'
    ];
} catch (Exception $e) {
    $stats = ['calculations_today' => 0, 'modules_available' => 4, 'system_status' => 'partial'];
    logError('Stats retrieval failed', ['error' => $e->getMessage()]);
}

// Version et build
$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Guldagil - Calculateur et Gestion Transport</title>
    
    <!-- Preconnect optimisations -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS consolidé -->
    <link rel="stylesheet" href="assets/css/app.min.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Portail Guldagil - Calculateur de frais de port, gestion ADR et suivi des expéditions">
    <meta name="keywords" content="transport, frais de port, ADR, expédition, Guldagil">
    <meta name="author" content="Guldagil">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body>
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand">
                <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="portal-logo">
                <div class="brand-info">
                    <h1 class="portal-title">Portail Guldagil</h1>
                    <p class="portal-subtitle">Solutions transport & logistique</p>
                </div>
            </div>
            
            <div class="header-status">
                <div class="status-indicator <?= $stats['system_status'] ?>">
                    <span class="status-dot"></span>
                    <span class="status-text">
                        <?= $stats['system_status'] === 'operational' ? 'Système opérationnel' : 'Fonctionnement partiel' ?>
                    </span>
                </div>
                
                <?php if ($auth_enabled): ?>
                <div class="user-profile">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= htmlspecialchars($user_info['username']) ?></span>
                </div>
                <?php else: ?>
                <div class="dev-mode">
                    <span class="dev-indicator">👨‍💻</span>
                    <span class="dev-text">Mode développement</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Navigation principale -->
    <nav class="portal-navigation">
        <div class="nav-container">
            <div class="nav-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['calculations_today'] ?></span>
                    <span class="stat-label">calculs aujourd'hui</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['modules_available'] ?></span>
                    <span class="stat-label">modules actifs</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="portal-container">
            
            <!-- Section modules principaux -->
            <section class="modules-section">
                <h2 class="section-title">Modules disponibles</h2>
                
                <div class="modules-grid">
                    
                    <!-- Module Frais de port -->
                    <div class="module-card primary-module">
                        <div class="module-header">
                            <div class="module-icon">🧮</div>
                            <div class="module-info">
                                <h3 class="module-title">Frais de port</h3>
                                <p class="module-description">Calcul et comparaison des tarifs de transport</p>
                            </div>
                        </div>
                        
                        <div class="module-features">
                            <span class="feature-tag">✓ Comparaison transporteurs</span>
                            <span class="feature-tag">✓ Calcul instantané</span>
                            <span class="feature-tag">✓ Options avancées</span>
                        </div>
                        
                        <div class="module-actions">
                            <a href="calculateur/" class="btn btn-primary">
                                <span>🚀</span>
                                Accéder au calculateur
                            </a>
                            <a href="calculateur/?demo=1" class="btn btn-secondary">
                                <span>🎮</span>
                                Mode démo
                            </a>
                        </div>
                        
                        <div class="module-stats">
                            <div class="stat">
                                <span class="stat-number"><?= $stats['calculations_today'] ?></span>
                                <span class="stat-text">calculs aujourd'hui</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module ADR -->
                    <div class="module-card">
                        <div class="module-header">
                            <div class="module-icon">⚠️</div>
                            <div class="module-info">
                                <h3 class="module-title">Gestion ADR</h3>
                                <p class="module-description">Transport de marchandises dangereuses</p>
                            </div>
                        </div>
                        
                        <div class="module-features">
                            <span class="feature-tag">✓ Déclarations ADR</span>
                            <span class="feature-tag">✓ Gestion produits</span>
                            <span class="feature-tag">✓ Quotas transport</span>
                        </div>
                        
                        <div class="module-actions">
                            <a href="adr/" class="btn btn-primary">
                                <span>⚠️</span>
                                Accéder à l'ADR
                            </a>
                        </div>
                        
                        <div class="module-status">
                            <span class="status-badge operational">Opérationnel</span>
                        </div>
                    </div>
                    
                </div>
            </section>
            
            <!-- Section suivi/tracking -->
            <section class="tracking-section">
                <h2 class="section-title">Suivi des expéditions</h2>
                
                <div class="tracking-grid">
                    
                    <!-- Liens transporteurs -->
                    <div class="tracking-card">
                        <h3 class="tracking-title">
                            <span class="tracking-icon">📦</span>
                            Portails transporteurs
                        </h3>
                        
                        <div class="transporters-links">
                            <?php foreach (TRACKING_LINKS as $key => $transporter): ?>
                                <?php if ($transporter['active']): ?>
                                <a href="<?= $transporter['url'] ?>" 
                                   target="_blank" 
                                   class="transporter-link"
                                   title="Accéder au portail <?= $transporter['name'] ?>">
                                    <div class="transporter-info">
                                        <span class="transporter-icon">🚚</span>
                                        <span class="transporter-name"><?= $transporter['name'] ?></span>
                                    </div>
                                    <span class="external-link">↗</span>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="tracking-note">
                            <span class="note-icon">💡</span>
                            <span class="note-text">Accès direct aux portails de suivi des transporteurs partenaires</span>
                        </div>
                    </div>
                    
                    <!-- Recherche rapide -->
                    <div class="tracking-card">
                        <h3 class="tracking-title">
                            <span class="tracking-icon">🔍</span>
                            Recherche rapide
                        </h3>
                        
                        <form class="quick-search-form" onsubmit="handleQuickSearch(event)">
                            <div class="search-input-group">
                                <input type="text" 
                                       placeholder="N° d'expédition, référence..." 
                                       class="search-input"
                                       id="quickSearchInput">
                                <button type="submit" class="search-btn">
                                    <span>🔍</span>
                                </button>
                            </div>
                        </form>
                        
                        <div class="search-suggestions">
                            <span class="suggestion-label">Recherches fréquentes :</span>
                            <div class="suggestion-tags">
                                <button class="suggestion-tag" onclick="setQuickSearch('EXP2025')">EXP2025*</button>
                                <button class="suggestion-tag" onclick="setQuickSearch('urgent')">Urgent</button>
                                <button class="suggestion-tag" onclick="setQuickSearch('ADR')">ADR</button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </section>
            
        </div>
    </main>

    <!-- Footer avec admin discret -->
    <footer class="portal-footer">
        <div class="footer-container">
            
            <!-- Informations version -->
            <div class="footer-version">
                <span class="version">Portail v<?= $version_info['version'] ?> beta</span>
                <span class="build">Build #<?= $version_info['build'] ?></span>
                <span class="date"><?= $version_info['formatted_date'] ?></span>
            </div>
            
            <!-- Copyright -->
            <div class="footer-copyright">
                <span>&copy; <?= COPYRIGHT_YEAR ?> Guldagil. Tous droits réservés.</span>
            </div>
            
            <!-- Accès admin discret -->
            <div class="footer-admin">
                <a href="admin/" class="admin-link" title="Administration">
                    <span class="admin-icon">⚙️</span>
                </a>
            </div>
            
        </div>
    </footer>

    <!-- JavaScript consolidé -->
    <script src="assets/js/app.min.js"></script>
    
    <!-- Configuration du portail -->
    <script>
        // Variables globales
        window.PORTAL_CONFIG = {
            version: '<?= $version_info['version'] ?>',
            build: '<?= $version_info['build'] ?>',
            debug: <?= DEBUG ? 'true' : 'false' ?>,
            modules: <?= json_encode(MODULES) ?>,
            stats: <?= json_encode($stats) ?>
        };
        
        // Initialisation du portail
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Portail Guldagil initialisé', window.PORTAL_CONFIG);
            
            // Initialiser les modules du portail
            if (typeof Portal !== 'undefined') {
                Portal.init();
            }
        });
    </script>
    
    <?php if (DEBUG): ?>
    <!-- Debug panel en développement -->
    <div id="debug-panel" class="debug-panel">
        <h4>🐛 Debug Panel</h4>
        <div class="debug-info">
            <strong>Config:</strong> <?= json_encode($version_info) ?><br>
            <strong>Modules:</strong> <?= count(MODULES) ?> configurés<br>
            <strong>DB:</strong> <?= $db ? 'Connectée' : 'Erreur' ?><br>
            <strong>Session:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?>
        </div>
    </div>
    <?php endif; ?>
    
</body>
</html>
