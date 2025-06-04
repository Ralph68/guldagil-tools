<?php
// public/adr/index.php - Page d'accueil module ADR
session_start();

// Configuration temporaire en attendant l'auth complète
$authEnabled = false; // Mettre à true quand l'auth sera implémentée
$debugMode = true;    // Pour permettre l'accès direct en développement

// Vérification d'authentification (temporaire)
if ($authEnabled) {
    // Vérifier si l'utilisateur est authentifié ADR
    if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
        // Rediriger vers la page de login ADR
        header('Location: auth/login.php');
        exit;
    }
    
    // Si authentifié, rediriger vers le dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Mode développement : accès direct autorisé
    if (isset($_GET['access']) && $_GET['access'] === 'dev') {
        // Simuler une session authentifiée pour le développement
        $_SESSION['adr_logged_in'] = true;
        $_SESSION['adr_user'] = 'dev.user';
        $_SESSION['adr_login_time'] = time();
        // Donner aussi la permission "dev" pour accéder aux outils internes
        $_SESSION['adr_permissions'] = ['read', 'write', 'admin', 'dev'];
        
        header('Location: dashboard.php');
        exit;
    }
}

// Charger la configuration
require __DIR__ . '/../../config.php';

// Informations du module ADR
$moduleInfo = [
    'name' => 'Module ADR Guldagil',
    'version' => '1.0.0',
    'description' => 'Gestion des marchandises dangereuses selon la réglementation ADR',
    'features' => [
        'Déclarations d\'expédition individuelles',
        'Récapitulatifs quotidiens par transporteur',
        'Base de données produits ADR actualisée',
        'Export PDF conformes à la réglementation',
        'Historique et recherche avancée',
        'Gestion des quotas de transport'
    ]
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module ADR - Guldagil Portal</title>
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-warning: #ffc107;
            --adr-success: #28a745;
            --adr-dark: #343a40;
            --adr-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .adr-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .adr-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
        }

        .adr-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 2rem;
            box-shadow: var(--shadow-hover);
        }

        .adr-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--adr-primary);
            margin-bottom: 0.5rem;
        }

        .adr-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .adr-description {
            font-size: 1rem;
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
            text-align: left;
        }

        .feature-card {
            background: var(--adr-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--adr-primary);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-left-color: var(--adr-secondary);
        }

        .feature-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .actions-section {
            margin-top: 3rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-width: 200px;
        }

        .btn-primary {
            background: var(--adr-primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-secondary {
            background: var(--adr-light);
            color: var(--adr-dark);
            border: 2px solid var(--adr-primary);
        }

        .btn-secondary:hover {
            background: var(--adr-primary);
            color: white;
        }

        .btn-outline {
            background: transparent;
            color: var(--adr-primary);
            border: 2px solid var(--adr-primary);
        }

        .btn-outline:hover {
            background: var(--adr-primary);
            color: white;
        }

        .auth-status {
            background: var(--adr-warning);
            color: #856404;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
            border-left: 4px solid #ffc107;
        }

        .dev-access {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1rem 0;
            border-left: 4px solid var(--adr-info);
        }

        .footer-links {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-link {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--adr-primary);
        }

        /* Animation de chargement */
        .loading-dots {
            display: inline-block;
        }

        .loading-dots::after {
            content: '';
            animation: dots 1.5s infinite;
        }

        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .adr-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .adr-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .actions-section {
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="adr-container">
        <!-- Logo ADR -->
        <div class="adr-logo">⚠️</div>
        
        <!-- Titre et description -->
        <h1 class="adr-title"><?= htmlspecialchars($moduleInfo['name']) ?></h1>
        <p class="adr-subtitle">Transport de marchandises dangereuses</p>
        <p class="adr-description">
            <?= htmlspecialchars($moduleInfo['description']) ?>
        </p>

        <!-- Statut d'authentification -->
        <?php if (!$authEnabled && $debugMode): ?>
        <div class="auth-status">
            <strong>⚠️ Mode développement</strong><br>
            L'authentification n'est pas encore implémentée. Accès direct autorisé pour les tests.
        </div>
        <?php endif; ?>

        <!-- Fonctionnalités -->
        <div class="features-grid">
            <?php foreach ($moduleInfo['features'] as $feature): ?>
            <div class="feature-card">
                <span class="feature-icon">✅</span>
                <?= htmlspecialchars($feature) ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Actions principales -->
        <div class="actions-section">
            <?php if ($authEnabled): ?>
                <!-- Mode production avec authentification -->
                <a href="auth/login.php" class="btn btn-primary">
                    <span>🔐</span>
                    Se connecter au module ADR
                </a>
                <a href="../" class="btn btn-outline">
                    <span>🏠</span>
                    Retour au Portal Guldagil
                </a>
                
            <?php else: ?>
                <!-- Mode développement -->
                <div class="dev-access">
                    <strong>🔧 Accès développement</strong><br>
                    Cliquez ci-dessous pour accéder directement au module (session temporaire)
                </div>
                
                <a href="?access=dev" class="btn btn-primary">
                    <span>🚀</span>
                    Accéder au Dashboard ADR
                </a>
                
                <a href="dashboard.php" class="btn btn-secondary">
                    <span>📊</span>
                    Dashboard direct
                </a>
                
                <a href="declaration/create.php" class="btn btn-outline">
                    <span>📝</span>
                    Créer une déclaration
                </a>
                
            <?php endif; ?>
            
            <a href="../" class="btn btn-outline">
                <span>🏠</span>
                Retour au Portal
            </a>
        </div>

        <!-- Liens footer -->
        <div class="footer-links">
            <a href="../" class="footer-link">🏠 Accueil Portal</a>
            <a href="../admin/" class="footer-link">⚙️ Administration</a>
            <a href="mailto:runser.jean.thomas@guldagil.com" class="footer-link">📧 Support technique</a>
            <a href="https://www.guldagil.com" target="_blank" class="footer-link">🌐 Site Guldagil</a>
        </div>

        <!-- Version et informations -->
        <div style="margin-top: 2rem; font-size: 0.8rem; color: #999;">
            Version <?= htmlspecialchars($moduleInfo['version']) ?> | 
            <?= $authEnabled ? 'Mode production' : 'Mode développement' ?> | 
            © <?= date('Y') ?> Guldagil
        </div>
    </div>

    <script>
        // Configuration
        const ADR_CONFIG = {
            authEnabled: <?= json_encode($authEnabled) ?>,
            debugMode: <?= json_encode($debugMode) ?>,
            moduleInfo: <?= json_encode($moduleInfo) ?>
        };

        // Fonctions utilitaires
        function showLoading(button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="loading-dots">Chargement</span>';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }

        // Gestion des clics sur les boutons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                // Ajouter un effet de chargement pour les liens externes
                if (this.href && !this.href.includes('#')) {
                    showLoading(this);
                }
            });
        });

        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.adr-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);

            // Animation des cartes
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, 200 + (index * 100));
            });
        });

        // Gestion des raccourcis clavier
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'Enter':
                    // Entrée pour accès rapide
                    if (ADR_CONFIG.authEnabled) {
                        window.location.href = 'auth/login.php';
                    } else {
                        window.location.href = '?access=dev';
                    }
                    break;
                    
                case 'Escape':
                    // Echap pour retour
                    window.location.href = '../';
                    break;
                    
                case 'd':
                    // 'd' pour dashboard direct (dev only)
                    if (ADR_CONFIG.debugMode && !ADR_CONFIG.authEnabled) {
                        window.location.href = 'dashboard.php';
                    }
                    break;
            }
        });

        // Analytics et logging
        function logAccess(type) {
            console.log('ADR_ACCESS:', {
                type: type,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                authEnabled: ADR_CONFIG.authEnabled
            });
            
            // Ici vous pourriez envoyer des données analytics
        }

        // Log de l'accès à la page
        logAccess('page_view');

        console.log('🔰 Module ADR initialisé');
        console.log('📋 Fonctionnalités:', ADR_CONFIG.moduleInfo.features);
        
        <?php if (!$authEnabled): ?>
        console.log('🚨 Mode développement actif - Authentification désactivée');
        console.log('💡 Raccourcis: Entrée (accès rapide), d (dashboard), Échap (retour)');
        <?php endif; ?>
    </script>
</body>
</html>
