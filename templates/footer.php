<?php
/**
 * Titre: Footer modulaire du portail Guldagil
 * Chemin: /templates/footer.php
 * Version: 0.5 beta + build auto
 */

// Informations de version via config/version.php
$version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : date('YmdHis'),
    'formatted_date' => date('d/m/Y H:i'),
    'short_build' => substr(defined('BUILD_NUMBER') ? BUILD_NUMBER : date('YmdHis'), -8)
];

$current_module = $current_module ?? 'home';
$show_admin_footer = $show_admin_footer ?? ($current_module === 'home');
?>
        </div><!-- Fin main-container -->
    </main><!-- Fin portal-main -->

    <!-- Section administration (uniquement sur l'accueil) -->
    <?php if ($show_admin_footer): ?>
    <section class="admin-section">
        <div class="main-container">
            <h3 class="section-title">Administration</h3>
            
            <div class="admin-grid">
                <div class="admin-card">
                    <div class="admin-icon">⚙️</div>
                    <h4>Configuration</h4>
                    <p>Paramètres globaux et modules</p>
                    <a href="/admin/" class="admin-btn">Accéder</a>
                </div>
                
                <div class="admin-card">
                    <div class="admin-icon">📊</div>
                    <h4>Maintenance</h4>
                    <p>Optimisation et diagnostics</p>
                    <a href="/admin/maintenance.php" class="admin-btn">Accéder</a>
                </div>
                
                <div class="admin-card">
                    <div class="admin-icon">📈</div>
                    <h4>Statistiques</h4>
                    <p>Tableau de bord et rapports</p>
                    <a href="/admin/stats.php" class="admin-btn">Accéder</a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer principal -->
    <footer class="portal-footer" role="contentinfo">
        <div class="footer-container">
            <!-- Informations de version -->
            <div class="footer-info">
                <p class="footer-version" title="Informations de version du portail">
                    <span class="version-label">Version:</span>
                    <span class="version-number"><?= htmlspecialchars($version_info['version']) ?></span>
                    <span class="build-label">Build:</span>
                    <span class="build-number">#<?= htmlspecialchars($version_info['short_build']) ?></span>
                    <span class="build-date"><?= htmlspecialchars($version_info['formatted_date']) ?></span>
                </p>
                
                <!-- Informations techniques en mode debug -->
                <?php if (defined('DEBUG') && DEBUG): ?>
                <p class="footer-debug">
                    <span class="debug-label">🐛 Debug:</span>
                    <span class="debug-info">Module: <?= $current_module ?></span>
                    <span class="debug-info">PHP: <?= PHP_VERSION ?></span>
                    <?php if (isset($db)): ?>
                    <span class="debug-info">DB: ✅</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- Copyright et liens -->
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> Jean-Thomas RUNSER - Guldagil</p>
                
                <!-- Liens rapides -->
                <div class="footer-links">
                    <?php if ($current_module !== 'home'): ?>
                    <a href="/" class="footer-link">🏠 Accueil</a>
                    <?php endif; ?>
                    
                    <a href="/admin/" class="footer-link">⚙️ Administration</a>
                    
                    <?php if (defined('DEBUG') && DEBUG): ?>
                    <a href="/diagnostic.php" class="footer-link">🔍 Diagnostic</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bouton retour en haut -->
        <button id="scroll-top" class="scroll-top-btn" onclick="scrollToTop()" 
                title="Retour en haut" aria-label="Retour en haut de page">
            ↑
        </button>
    </footer>

    <!-- Scripts JavaScript -->
    
    <!-- Script principal du portail -->
    <script>
        // Variables globales
        window.PortalConfig = {
            currentModule: '<?= $current_module ?>',
            version: '<?= $version_info['version'] ?>',
            debug: <?= defined('DEBUG') && DEBUG ? 'true' : 'false' ?>,
            userAuthenticated: <?= isset($user_authenticated) && $user_authenticated ? 'true' : 'false' ?>
        };
        
        // Fonction de retour à l'accueil
        function goHome() {
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                window.location.href = '/';
            } else {
                scrollToTop();
            }
        }
        
        // Fonction de scroll vers le haut
        function scrollToTop() {
            window.scrollTo({ 
                top: 0, 
                behavior: 'smooth' 
            });
        }
        
        // Gestion du menu utilisateur
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            } else if (!window.PortalConfig.userAuthenticated) {
                // Redirection vers la page de connexion
                window.location.href = '/auth/login.php';
            }
        }
        
        // Gestion des raccourcis clavier globaux
        document.addEventListener('keydown', function(e) {
            // Raccourcis Alt + touche
            if (e.altKey) {
                switch (e.key) {
                    case 'h':
                        e.preventDefault();
                        goHome();
                        break;
                    case 'a':
                        e.preventDefault();
                        window.location.href = '/admin/';
                        break;
                }
            }
            
            // Échap pour fermer les menus ou revenir à l'accueil
            if (e.key === 'Escape') {
                const userMenu = document.getElementById('user-menu');
                if (userMenu && userMenu.style.display !== 'none') {
                    userMenu.style.display = 'none';
                } else {
                    goHome();
                }
            }
        });
        
        // Initialisation au chargement DOM
        document.addEventListener('DOMContentLoaded', function() {
            console.log(`🚀 Module ${window.PortalConfig.currentModule} initialisé`);
            
            // Gestion du bouton scroll-top
            const scrollTopBtn = document.getElementById('scroll-top');
            if (scrollTopBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollTopBtn.style.display = 'block';
                    } else {
                        scrollTopBtn.style.display = 'none';
                    }
                });
            }
            
            // Fermer le menu utilisateur en cliquant ailleurs
            document.addEventListener('click', function(e) {
                const userMenu = document.getElementById('user-menu');
                const userArea = document.querySelector('.user-area');
                
                if (userMenu && userArea && 
                    !userArea.contains(e.target) && 
                    !userMenu.contains(e.target)) {
                    userMenu.style.display = 'none';
                }
            });
            
            // Auto-hide des messages flash après 5 secondes
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(function(msg) {
                setTimeout(function() {
                    if (msg.parentElement) {
                        msg.style.opacity = '0';
                        setTimeout(function() {
                            msg.remove();
                        }, 300);
                    }
                }, 5000);
            });
            
            // Support tactile amélioré
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }
            
            // Amélioration accessibilité - navigation clavier
            const interactiveElements = document.querySelectorAll('[role="button"][tabindex="0"]');
            interactiveElements.forEach(function(element) {
                element.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        element.click();
                    }
                });
            });
        });
        
        // Fonctions utilitaires globales
        window.PortalUtils = {
            // Fonction de navigation sécurisée
            navigateTo: function(url, confirm_message = null) {
                if (confirm_message && !confirm(confirm_message)) {
                    return false;
                }
                window.location.href = url;
            },
            
            // Affichage de notifications
            showNotification: function(message, type = 'info', duration = 3000) {
                const notification = document.createElement('div');
                notification.className = `flash-message flash-${type}`;
                notification.innerHTML = `
                    <span class="flash-icon">${type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️')}</span>
                    <span class="flash-text">${message}</span>
                    <button class="flash-close" onclick="this.parentElement.remove()">×</button>
                `;
                
                let container = document.querySelector('.flash-messages');
                if (!container) {
                    container = document.createElement('div');
                    container.className = 'flash-messages';
                    document.querySelector('.portal-main').prepend(container);
                }
                
                container.appendChild(notification);
                
                if (duration > 0) {
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, duration);
                }
            }
        };
        
        // Mode debug
        <?php if (defined('DEBUG') && DEBUG): ?>
        window.PortalDebug = {
            config: window.PortalConfig,
            version: <?= json_encode($version_info) ?>,
            module: '<?= $current_module ?>',
            logInfo: function() {
                console.table(this);
            }
        };
        console.log('🔧 Mode debug activé - window.PortalDebug disponible');
        <?php endif; ?>
    </script>
    
    <!-- Script spécifique au module -->
    <?php if (isset($module_js) && $module_js): ?>
    <script src="/assets/js/modules/<?= $current_module ?>.js?v=<?= $version_info['version'] ?>"></script>
    <?php endif; ?>
    
    <!-- Scripts additionnels -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>?v=<?= $version_info['version'] ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
