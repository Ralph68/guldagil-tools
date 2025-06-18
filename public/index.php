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
    
    <!-- Variables manquantes pour compatibilité -->
    <style>
        :root {
            --primary-color: var(--gul-blue-primary);
            --primary-light: var(--gul-blue-light);
            --primary-dark: var(--gul-blue-dark);
            --warning-color: var(--gul-orange);
            --warning-light: var(--gul-orange-light);
            --warning-dark: var(--gul-orange-dark);
            --secondary-color: var(--text-muted);
            --secondary-light: var(--bg-tertiary);
            --secondary-dark: var(--border-dark);
            --border-color: var(--border-light);
            --spacing-lg: var(--space-lg);
            --spacing-xl: var(--space-xl);
            --spacing-md: var(--space-md);
            --spacing-sm: var(--space-sm);
            --spacing-xs: var(--space-xs);
            --radius-md: var(--radius-md);
            --radius-lg: var(--radius-lg);
            --radius-sm: var(--radius-sm);
            --transition: var(--transition-normal);
        }
    </style>
    
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
                <a href="calculateur/" class="nav-link calculateur" style="padding: 0.75rem 1rem; text-decoration: none; color: var(--text-secondary); font-weight: 500; border-radius: var(--radius-md); transition: var(--transition-normal); display: flex; align-items: center; gap: var(--space-sm); border: 1px solid transparent; font-size: 0.9rem;">
                    <span>🚚</span>
                    Calculateur
                </a>
                <a href="adr/" class="nav-link adr" style="padding: 0.75rem 1rem; text-decoration: none; color: var(--text-secondary); font-weight: 500; border-radius: var(--radius-md); transition: var(--transition-normal); display: flex; align-items: center; gap: var(--space-sm); border: 1px solid transparent; font-size: 0.9rem;">
                    <span>⚠️</span>
                    ADR
                </a>
                <a href="admin/" class="nav-link admin" style="padding: 0.75rem 1rem; text-decoration: none; color: var(--text-secondary); font-weight: 500; border-radius: var(--radius-md); transition: var(--transition-normal); display: flex; align-items: center; gap: var(--space-sm); border: 1px solid transparent; font-size: 0.9rem;">
                    <span>⚙️</span>
                    Admin
                </a>
            </nav>
            
            <div class="header-actions">
                <!-- Le bouton de thème sera ajouté automatiquement par theme-switcher.js -->
                <div class="header-account">
                    <div class="account-info" style="display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-sm) var(--space-md); background: var(--bg-tertiary); border-radius: var(--radius-md); font-size: 0.9rem; color: var(--text-primary);">
                        <span class="account-icon">👨‍💻</span>
                        <span class="account-text" style="color: var(--text-primary);">Dev</span>
                    </div>
                    <?php if ($auth_required): ?>
                    <a href="?logout=1" class="logout-btn" onclick="return confirm('Se déconnecter ?')" style="padding: var(--space-sm); background: var(--error-border); color: var(--text-inverse); border-radius: var(--radius-md); text-decoration: none; transition: var(--transition-normal); display: flex; align-items: center; gap: var(--space-xs); font-size: 0.9rem;">
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
    <div class="auth-status" style="background: var(--warning-bg); color: var(--warning-text); padding: var(--space-lg); border-radius: var(--radius-md); margin: var(--space-lg) 0; border-left: 4px solid var(--warning-border);">
        <strong>🔓 Mode développement actif</strong>
        <p style="margin: var(--space-sm) 0 0 0;">L'authentification est désactivée pour faciliter le développement. En production, activez <code style="background: rgba(0,0,0,0.1); padding: var(--space-xs); border-radius: var(--radius-sm);">$auth_required = true</code>.</p>
    </div>
    <?php endif; ?>

    <!-- Container principal -->
    <main class="main-container">
        <!-- Section héro -->
        <section class="hero-section" style="background: linear-gradient(135deg, var(--gul-blue-primary) 0%, var(--gul-blue-light) 100%); color: var(--text-inverse); padding: var(--space-3xl) 0; text-align: center; margin-bottom: var(--space-3xl);">
            <div class="hero-content" style="max-width: 800px; margin: 0 auto; padding: 0 var(--space-lg);">
                <h2 class="hero-title" style="font-size: 2.5rem; font-weight: 700; margin-bottom: var(--space-md); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); color: var(--text-inverse);">Outils logistiques intégrés</h2>
                <p class="hero-subtitle" style="font-size: 1.2rem; opacity: 0.95; line-height: 1.6; font-weight: 400; color: var(--text-inverse);">
                    Calculez vos frais de transport, gérez vos marchandises dangereuses 
                    et administrez votre système en toute simplicité.
                </p>
            </div>
        </section>

        <!-- Grille des applications - Version simplifiée -->
        <section class="apps-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--space-xl); margin-bottom: var(--space-3xl);">
            <!-- Calculateur de frais -->
            <div class="app-card calculateur" style="background: var(--bg-primary); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); overflow: hidden; transition: var(--transition-normal); border: 1px solid var(--border-light); display: flex; flex-direction: column; min-height: 400px; cursor: pointer;" onclick="window.location.href='calculateur/'">
                <div class="app-header" style="padding: var(--space-xl); display: flex; align-items: center; gap: var(--space-md); border-bottom: 1px solid var(--border-light);">
                    <div class="app-icon" style="width: 4rem; height: 4rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; background: var(--gul-blue-bg); color: var(--gul-blue-primary);">🚚</div>
                    <div class="app-info" style="flex: 1;">
                        <h3 class="app-title" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-sm) 0;">Calculateur de frais</h3>
                        <p class="app-description" style="font-size: 1rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">Comparez instantanément les tarifs des transporteurs selon vos critères</p>
                    </div>
                </div>
                
                <div class="app-content" style="padding: var(--space-xl); flex: 1; display: flex; flex-direction: column;">
                    <div class="app-features" style="margin-bottom: var(--space-lg);">
                        <ul style="list-style: none; margin: 0; padding: 0; color: var(--text-secondary);">
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-blue-primary); font-weight: bold;">✓</span>
                                Saisie guidée : poids, dimensions, destination
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-blue-primary); font-weight: bold;">✓</span>
                                Comparaison automatique des 3 transporteurs
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-blue-primary); font-weight: bold;">✓</span>
                                Export PDF et sauvegarde des calculs
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-blue-primary); font-weight: bold;">✓</span>
                                Interface mobile responsive
                            </li>
                        </ul>
                    </div>
                    
                    <div class="app-action" style="margin-top: auto;">
                        <div class="btn btn-primary" style="background: var(--gul-blue-primary); color: var(--text-inverse); border: 1px solid var(--gul-blue-primary); width: 100%; text-align: center; font-size: 1rem; padding: var(--space-lg); display: flex; align-items: center; justify-content: center; gap: var(--space-sm);">
                            <span>🚀</span>
                            Lancer le calculateur
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module ADR -->
            <div class="app-card adr" style="background: var(--bg-primary); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); overflow: hidden; transition: var(--transition-normal); border: 1px solid var(--border-light); display: flex; flex-direction: column; min-height: 400px; cursor: pointer;" onclick="window.location.href='adr/'">
                <div class="app-header" style="padding: var(--space-xl); display: flex; align-items: center; gap: var(--space-md); border-bottom: 1px solid var(--border-light);">
                    <div class="app-icon" style="width: 4rem; height: 4rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; background: var(--gul-orange-bg); color: var(--gul-orange);">⚠️</div>
                    <div class="app-info" style="flex: 1;">
                        <h3 class="app-title" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-sm) 0;">Gestion ADR</h3>
                        <p class="app-description" style="font-size: 1rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">Déclarations et suivi des marchandises dangereuses</p>
                    </div>
                </div>
                
                <div class="app-content" style="padding: var(--space-xl); flex: 1; display: flex; flex-direction: column;">
                    <div class="app-features" style="margin-bottom: var(--space-lg);">
                        <ul style="list-style: none; margin: 0; padding: 0; color: var(--text-secondary);">
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-orange); font-weight: bold;">✓</span>
                                Formulaires de déclaration pré-remplis
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-orange); font-weight: bold;">✓</span>
                                Base de données 250+ produits dangereux
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-orange); font-weight: bold;">✓</span>
                                Validation automatique codes transport
                            </li>
                            <li style="padding: var(--space-sm) 0; display: flex; align-items: center; gap: var(--space-sm);">
                                <span style="color: var(--gul-orange); font-weight: bold;">✓</span>
                                Export conforme réglementation
                            </li>
                        </ul>
                    </div>
                    
                    <div class="app-action" style="margin-top: auto;">
                        <div class="btn btn-warning" style="background: var(--gul-orange); color: var(--text-inverse); border: 1px solid var(--gul-orange); width: 100%; text-align: center; font-size: 1rem; padding: var(--space-lg); display: flex; align-items: center; justify-content: center; gap: var(--space-sm);">
                            <span>⚠️</span>
                            Accéder au module ADR
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section informations rapides -->
        <section class="quick-info" style="margin-bottom: var(--space-3xl);">
            <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-lg);">
                <div class="info-card" style="background: var(--bg-primary); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: var(--space-md); transition: var(--transition-normal); border: 1px solid var(--border-light);">
                    <div class="info-icon" style="font-size: 1.5rem; flex-shrink: 0; opacity: 0.8;">🚛</div>
                    <div class="info-content">
                        <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-xs) 0;">Transporteurs</h4>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">3 transporteurs configurés</p>
                    </div>
                </div>
                
                <div class="info-card" style="background: var(--bg-primary); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: var(--space-md); transition: var(--transition-normal); border: 1px solid var(--border-light);">
                    <div class="info-icon" style="font-size: 1.5rem; flex-shrink: 0; opacity: 0.8;">📍</div>
                    <div class="info-content">
                        <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-xs) 0;">Couverture</h4>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">95 départements français</p>
                    </div>
                </div>
                
                <div class="info-card" style="background: var(--bg-primary); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: var(--space-md); transition: var(--transition-normal); border: 1px solid var(--border-light);">
                    <div class="info-icon" style="font-size: 1.5rem; flex-shrink: 0; opacity: 0.8;">⚠️</div>
                    <div class="info-content">
                        <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-xs) 0;">Produits ADR</h4>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">250+ références actives</p>
                    </div>
                </div>
                
                <div class="info-card" style="background: var(--bg-primary); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: var(--space-md); transition: var(--transition-normal); border: 1px solid var(--border-light);">
                    <div class="info-icon" style="font-size: 1.5rem; flex-shrink: 0; opacity: 0.8;">📈</div>
                    <div class="info-content">
                        <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-xs) 0;">Système</h4>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Opérationnel 24h/24</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer" style="background: var(--bg-primary); border-top: 1px solid var(--border-light); margin-top: var(--space-3xl);">
        <div class="footer-container" style="max-width: var(--container-max); margin: 0 auto; padding: var(--space-xl); text-align: center;">
            <div class="footer-links" style="display: flex; justify-content: center; gap: var(--space-xl); margin-bottom: var(--space-lg); flex-wrap: wrap;">
                <a href="#" class="footer-link" onclick="showHelp()" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; transition: var(--transition-normal);">Documentation</a>
                <a href="admin/export.php?type=all&format=csv" class="footer-link" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; transition: var(--transition-normal);">Export données</a>
                <a href="#" class="footer-link" onclick="showContact()" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; transition: var(--transition-normal);">Support technique</a>
                <a href="#" class="footer-link" onclick="showVersion()" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; transition: var(--transition-normal);">Version système</a>
            </div>
            
            <div class="footer-info" style="color: var(--text-muted); font-size: 0.8rem;">
                <p style="margin: var(--space-xs) 0;">&copy; 2025 Guldagil - Portail logistique beta 0.5</p>
                <p style="margin: var(--space-xs) 0;">Dernière mise à jour : <?= date('d/m/Y H:i', filemtime(__FILE__)) ?></p>
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

        // Fonctions d'aperçu (supprimées pour simplifier)
        function showHelp() {
            showNotification('Documentation en cours de rédaction', 'info');
        }

        function showContact() {
            showNotification('Support: dev@guldagil.com', 'info');
        }

        function showVersion() {
            showNotification('Portail Guldagil beta 0.5 - Build ' + new Date().getFullYear(), 'info');
        }
    </script>
</body>
</html>
