<?php
// public/index.php - Accueil épuré Portail Guldagil V2
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
    <link rel="stylesheet" href="assets/css/portail-accueil.css">
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
            
            <div class="header-account">
                <div class="account-info">
                    <span class="account-icon">👨‍💻</span>
                    <span class="account-text">Dev</span>
                </div>
                <?php if ($auth_required): ?>
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Se déconnecter ?')" title="Déconnexion">
                    <span>🚪</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero section -->
    <section class="hero">
        <div class="hero-container">
            <h1>Vos outils logistiques</h1>
            <p class="hero-subtitle">
                Accédez rapidement à vos interfaces de travail spécialisées
            </p>
        </div>
    </section>

    <!-- Cartes outils principales -->
    <main class="main-container">
        <div class="tools-grid">
            <!-- Calculateur de frais -->
            <div class="tool-card calculateur-card" onclick="location.href='calculateur/'">
                <div class="tool-header">
                    <div class="tool-icon">🚚</div>
                    <div class="tool-info">
                        <h2>Calculateur de frais</h2>
                        <p class="tool-status">Interface complète</p>
                    </div>
                    <div class="tool-arrow">→</div>
                </div>
                
                <div class="tool-content">
                    <p class="tool-description">
                        Comparez les tarifs Heppner, XPO et Kuehne+Nagel avec toutes les options avancées
                    </p>
                    
                    <ul class="tool-features">
                        <li>Calculs en temps réel</li>
                        <li>Alertes "payant pour"</li>
                        <li>Options premium et enlèvement</li>
                        <li>Historique des calculs</li>
                        <li>Comparaison détaillée</li>
                    </ul>
                </div>
                
                <div class="tool-footer">
                    <span class="tool-cta">Accéder à l'interface →</span>
                </div>
            </div>

            <!-- Module ADR -->
            <div class="tool-card adr-card" onclick="location.href='adr/'">
                <div class="tool-header">
                    <div class="tool-icon">⚠️</div>
                    <div class="tool-info">
                        <h2>Module ADR</h2>
                        <p class="tool-status">Accès sécurisé</p>
                    </div>
                    <div class="tool-arrow">→</div>
                </div>
                
                <div class="tool-content">
                    <p class="tool-description">
                        Gestion complète des déclarations de marchandises dangereuses
                    </p>
                    
                    <ul class="tool-features">
                        <li>Déclarations individuelles</li>
                        <li>Récapitulatifs quotidiens</li>
                        <li>Export PDF réglementaire</li>
                        <li>Gestion des quotas</li>
                        <li>Base produits ADR</li>
                    </ul>
                </div>
                
                <div class="tool-footer">
                    <span class="tool-cta">🔐 Accéder au module →</span>
                </div>
            </div>

            <!-- Administration -->
            <div class="tool-card admin-card" onclick="location.href='admin/'">
                <div class="tool-header">
                    <div class="tool-icon">⚙️</div>
                    <div class="tool-info">
                        <h2>Administration</h2>
                        <p class="tool-status">Administrateurs</p>
                    </div>
                    <div class="tool-arrow">→</div>
                </div>
                
                <div class="tool-content">
                    <p class="tool-description">
                        Configuration système et gestion des données
                    </p>
                    
                    <ul class="tool-features">
                        <li>Gestion des tarifs</li>
                        <li>Options supplémentaires</li>
                        <li>Taxes et majorations</li>
                        <li>Export / Import</li>
                        <li>Maintenance système</li>
                    </ul>
                </div>
                
                <div class="tool-footer">
                    <span class="tool-cta">🔧 Interface admin →</span>
                </div>
            </div>
        </div>

        <!-- Liens rapides transporteurs -->
        <section class="quick-access">
            <h3>🔗 Suivi des expéditions</h3>
            <p class="quick-access-desc">Accès direct aux portails transporteurs</p>
            
            <div class="transporteur-links">
                <a href="https://myportal.heppner-group.com/home" target="_blank" class="transporteur-link heppner">
                    <span class="transporteur-icon">🚛</span>
                    <div class="transporteur-info">
                        <strong>Portal Heppner</strong>
                        <small>Suivi colis et palettes</small>
                    </div>
                    <span class="external-icon">↗</span>
                </a>
                
                <a href="https://xpoconnecteu.xpo.com/customer/orders/list" target="_blank" class="transporteur-link xpo">
                    <span class="transporteur-icon">📦</span>
                    <div class="transporteur-info">
                        <strong>XPO Connect</strong>
                        <small>Gestion des commandes</small>
                    </div>
                    <span class="external-icon">↗</span>
                </a>
                
                <a href="#" target="_blank" class="transporteur-link kn">
                    <span class="transporteur-icon">🌐</span>
                    <div class="transporteur-info">
                        <strong>Kuehne+Nagel</strong>
                        <small>Portal client</small>
                    </div>
                    <span class="external-icon">↗</span>
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>Contact & Support</h4>
                <p><strong>Logistique :</strong> achats@guldagil.com</p>
                <p><strong>Support technique :</strong> runser.jean.thomas@guldagil.com</p>
                <p><strong>Standard :</strong> 03 89 63 42 42</p>
            </div>
            
            <div class="footer-section">
                <h4>Outils disponibles</h4>
                <p><a href="calculateur/">Calculateur de frais</a></p>
                <p><a href="adr/">Module ADR</a></p>
                <p><a href="admin/">Administration</a></p>
            </div>
            
            <div class="footer-section">
                <h4>Informations</h4>
                <p>© 2025 Guldagil</p>
                <p>Portail v2.0 - Usage interne</p>
                <p>Développé par l'équipe technique</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/portail-accueil.js"></script>
</body>
</html>
