<?php
// public/index.php - Accueil Portail Guldagil beta 0.5 - VERSION PROPRE
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
    
    <!-- Structure CSS modulaire -->
    <link rel="stylesheet" href="assets/css/globals.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/modules/portail.css">
    
    <!-- Optimisation performances -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <meta name="description" content="Portail logistique Guldagil beta 0.5 - Calculateur de frais, gestion ADR et administration">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <!-- Header avec navigation -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-brand">
                <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
                <h1 class="header-title">Portail Guldagil</h1>
            </div>
            
            <nav class="header-nav">
                <a href="calculateur/" class="nav-link calculateur">
                    <span class="nav-icon">🚚</span>
                    <span class="nav-text">Calculateur</span>
                </a>
                <a href="adr/" class="nav-link adr">
                    <span class="nav-icon">⚠️</span>
                    <span class="nav-text">ADR</span>
                </a>
                <a href="admin/" class="nav-link admin">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Admin</span>
                </a>
            </nav>
            
            <div class="header-actions">
                <div class="header-account">
                    <div class="account-info">
                        <span class="account-icon">👨‍💻</span>
                        <span class="account-text">Dev</span>
                    </div>
                    <?php if ($auth_required): ?>
                    <a href="?logout=1" class="logout-btn" onclick="return confirm('Se déconnecter ?')">
                        <span class="logout-icon">🚪</span>
                        <span class="logout-text">Déconnexion</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Zone d'état développement -->
    <?php if (!$auth_required): ?>
    <div class="dev-notice">
        <div class="dev-notice-content">
            <span class="dev-notice-icon">🔓</span>
            <div class="dev-notice-text">
                <strong>Mode développement actif</strong>
                <p>L'authentification est désactivée. En production, activez <code>$auth_required = true</code>.</p>
            </div>
        </div>
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

        <!-- Grille des applications principales -->
        <section class="apps-grid">
            <!-- Calculateur de frais -->
            <article class="app-card calculateur" onclick="window.location.href='calculateur/'">
                <div class="app-header">
                    <div class="app-icon">🚚</div>
                    <div class="app-info">
                        <h3 class="app-title">Calculateur de frais</h3>
                        <p class="app-description">Comparez instantanément les tarifs des transporteurs selon vos critères</p>
                    </div>
                </div>
                
                <div class="app-content">
                    <ul class="app-features">
                        <li>Saisie guidée : poids, dimensions, destination</li>
                        <li>Comparaison automatique des 3 transporteurs</li>
                        <li>Export PDF et sauvegarde des calculs</li>
                        <li>Interface mobile responsive</li>
                    </ul>
                    
                    <div class="app-action">
                        <div class="btn btn-primary btn-full">
                            <span class="btn-icon">🚀</span>
                            <span class="btn-text">Lancer le calculateur</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Module ADR -->
            <article class="app-card adr" onclick="window.location.href='adr/'">
                <div class="app-header">
                    <div class="app-icon">⚠️</div>
                    <div class="app-info">
                        <h3 class="app-title">Gestion ADR</h3>
                        <p class="app-description">Déclarations et suivi des marchandises dangereuses</p>
                    </div>
                </div>
                
                <div class="app-content">
                    <ul class="app-features">
                        <li>Formulaires de déclaration pré-remplis</li>
                        <li>Base de données 250+ produits dangereux</li>
                        <li>Validation automatique codes transport</li>
                        <li>Export conforme réglementation</li>
                    </ul>
                    
                    <div class="app-action">
                        <div class="btn btn-warning btn-full">
                            <span class="btn-icon">⚠️</span>
                            <span class="btn-text">Accéder au module ADR</span>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <!-- Informations système -->
        <section class="system-info">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">🚛</div>
                    <div class="info-content">
                        <h4 class="info-title">Transporteurs</h4>
                        <p class="info-text">3 transporteurs configurés</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h4 class="info-title">Couverture</h4>
                        <p class="info-text">95 départements français</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">⚠️</div>
                    <div class="info-content">
                        <h4 class="info-title">Produits ADR</h4>
                        <p class="info-text">250+ références actives</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📈</div>
                    <div class="info-content">
                        <h4 class="info-title">Système</h4>
                        <p class="info-text">Opérationnel 24h/24</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <nav class="footer-links">
                <a href="#" class="footer-link" data-action="help">Documentation</a>
                <a href="admin/export.php?type=all&format=csv" class="footer-link">Export données</a>
                <a href="#" class="footer-link" data-action="contact">Support technique</a>
                <a href="#" class="footer-link" data-action="version">Version système</a>
            </nav>
            
            <div class="footer-info">
                <p class="footer-copyright">&copy; 2025 Guldagil - Portail logistique beta 0.5</p>
                <p class="footer-timestamp">Dernière mise à jour : <?= date('d/m/Y H:i', filemtime(__FILE__)) ?></p>
            </div>
        </div>
    </footer>

    <!-- Scripts modulaires -->
    <script src="assets/js/globals.js"></script>
    <script src="assets/js/theme-switcher.js"></script>
    <script src="assets/js/modules/portail.js"></script>
</body>
</html>
