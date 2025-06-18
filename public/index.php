<?php
// public/index.php - Accueil épuré Portail Guldagil V2 - VERSION MODULAIRE
require __DIR__ . '/../config.php';

// Authentification simple (désactivée en dev)
$auth_required = false; // Passer à true en production
$auth_password = 'GulPort';

if ($auth_required) {
    session_start();
    
    if (!isset($_SESSION['authenticated'])) {
        if ($_POST['password'] ?? '' === $auth_password) {
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Afficher la page de connexion
        include 'auth-login.php';
        exit;
    }
}

// Gestion de la déconnexion
if (isset($_GET['logout']) && $auth_required) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Guldagil - Outils logistiques</title>
    
    <!-- Nouvelle structure CSS modulaire -->
    <link rel="stylesheet" href="assets/css/globals.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/modules/portail.css">
    
    <!-- Preconnect pour optimiser les performances -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <meta name="description" content="Portail logistique Guldagil - Calculateur de frais, gestion ADR et administration">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <!-- Header avec navigation et compte -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-brand">
                <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
                <h1 class="header-title">Portail Guldagil</h1>
            </div>
            
            <nav class="header-nav">
                <a href="calculateur/" class="nav-link calculateur">
                    <span>🚚</span>
                    Calculateur
                </a>
                <a href="adr/" class="nav-link adr">
                    <span>⚠️</span>
                    ADR
                </a>
                <a href="admin/" class="nav-link admin">
                    <span>⚙️</span>
                    Admin
                </a>
            </nav>
            
            <div class="header-actions">
                <!-- Le bouton de thème sera ajouté automatiquement par theme-switcher.js -->
                <div class="header-account">
                    <div class="account-info">
                        <span class="account-icon">👨‍💻</span>
                        <span class="account-text">Dev</span>
                    </div>
                    <?php if ($auth_required): ?>
                    <a href="?logout=1" class="logout-btn" onclick="return confirm('Se déconnecter ?')">
                        <span>🚪</span>
                        Déconnexion
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Zone d'état d'authentification -->
    <?php if (!$auth_required): ?>
    <div class="auth-status">
        <strong>🔓 Mode développement actif</strong>
        <p>L'authentification est désactivée pour faciliter le développement. En production, activez <code>$auth_required = true</code>.</p>
    </div>
    <?php endif; ?>

    <!-- Container principal -->
    <main class="main-container">
        <!-- Section héro -->
        <section class="hero-section">
            <div class="hero-content">
                <h2 class="hero-title">Outils logistiques intégrés</h2>
                <p class="hero-subtitle">
                    Calculez vos frais de transport, gérez vos marchandises dangereuses 
                    et administrez votre système en toute simplicité.
                </p>
            </div>
        </section>

        <!-- Grille des applications -->
        <section class="apps-grid">
            <!-- Calculateur de frais -->
            <div class="app-card calculateur">
                <div class="app-header">
                    <div class="app-icon">🚚</div>
                    <div class="app-info">
                        <h3 class="app-title">Calculateur de frais</h3>
                        <p class="app-description">Comparez les tarifs de transport instantanément</p>
                    </div>
                </div>
                
                <div class="app-features">
                    <span class="feature-tag">Comparaison transporteurs</span>
                    <span class="feature-tag">Calculs instantanés</span>
                    <span class="feature-tag">Export résultats</span>
                </div>
                
                <div class="app-actions">
                    <a href="calculateur/" class="btn btn-primary">
                        <span>🚀</span>
                        Lancer le calculateur
                    </a>
                    <button class="btn btn-outline" onclick="showCalculatorPreview()">
                        <span>👁️</span>
                        Aperçu
                    </button>
                </div>
            </div>

            <!-- Module ADR -->
            <div class="app-card adr">
                <div class="app-header">
                    <div class="app-icon">⚠️</div>
                    <div class="app-info">
                        <h3 class="app-title">Gestion ADR</h3>
                        <p class="app-description">Déclarations et suivi des marchandises dangereuses</p>
                    </div>
                </div>
                
                <div class="app-features">
                    <span class="feature-tag">Déclarations ADR</span>
                    <span class="feature-tag">Base produits</span>
                    <span class="feature-tag">Conformité réglementaire</span>
                </div>
                
                <div class="app-actions">
                    <a href="adr/" class="btn btn-warning">
                        <span>⚠️</span>
                        Accéder à ADR
                    </a>
                    <button class="btn btn-outline" onclick="showADRPreview()">
                        <span>📋</span>
                        Déclarations
                    </button>
                </div>
            </div>

            <!-- Administration -->
            <div class="app-card admin">
                <div class="app-header">
                    <div class="app-icon">⚙️</div>
                    <div class="app-info">
                        <h3 class="app-title">Administration</h3>
                        <p class="app-description">Configuration et gestion du système</p>
                    </div>
                </div>
                
                <div class="app-features">
                    <span class="feature-tag">Gestion tarifs</span>
                    <span class="feature-tag">Maintenance</span>
                    <span class="feature-tag">Statistiques</span>
                </div>
                
                <div class="app-actions">
                    <a href="admin/" class="btn btn-secondary">
                        <span>⚙️</span>
                        Administrer
                    </a>
                    <button class="btn btn-outline" onclick="showAdminStats()">
                        <span>📊</span>
                        Statistiques
                    </button>
                </div>
            </div>
        </section>

        <!-- Section informations rapides -->
        <section class="quick-info">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">🚛</div>
                    <div class="info-content">
                        <h4>Transporteurs</h4>
                        <p>3 transporteurs configurés</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h4>Couverture</h4>
                        <p>95 départements français</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">⚠️</div>
                    <div class="info-content">
                        <h4>Produits ADR</h4>
                        <p>250+ références actives</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📈</div>
                    <div class="info-content">
                        <h4>Système</h4>
                        <p>Opérationnel 24h/24</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links">
                <a href="#" class="footer-link" onclick="showHelp()">Documentation</a>
                <a href="admin/export.php?type=all&format=csv" class="footer-link">Export données</a>
                <a href="#" class="footer-link" onclick="showContact()">Support technique</a>
                <a href="#" class="footer-link" onclick="showVersion()">Version système</a>
            </div>
            
            <div class="footer-info">
                <p>&copy; 2025 Guldagil - Portail logistique v2.0</p>
                <p>Dernière mise à jour : <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
    </footer>

    <!-- Scripts modulaires (nouvelle structure) -->
    <script src="assets/js/globals.js"></script>
    <script src="assets/js/theme-switcher.js"></script>
    <script src="assets/js/modules/portail.js"></script>
    
    <!-- Scripts d'initialisation -->
    <script>
        // Initialisation du portail
        document.addEventListener('DOMContentLoaded', function() {
            // Le theme-switcher s'initialise automatiquement
            console.log('🚀 Portail Guldagil v2.0 - Mode modulaire actif');
            
            // Ajouter le bouton de thème si absent
            if (!document.querySelector('.theme-toggle')) {
                initializeThemeToggle();
            }
        });

        // Fonctions d'aperçu (placeholder)
        function showCalculatorPreview() {
            showNotification('Aperçu calculateur - Fonctionnalité à venir', 'info');
        }

        function showADRPreview() {
            showNotification('Aperçu ADR - Fonctionnalité à venir', 'info');
        }

        function showAdminStats() {
            showNotification('Statistiques admin - Fonctionnalité à venir', 'info');
        }

        function showHelp() {
            showNotification('Documentation en cours de rédaction', 'info');
        }

        function showContact() {
            showNotification('Support: dev@guldagil.com', 'info');
        }

        function showVersion() {
            showNotification('Portail Guldagil v2.0 - Build ' + new Date().getFullYear(), 'info');
        }
    </script>
</body>
</html>
