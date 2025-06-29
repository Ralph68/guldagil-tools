<?php
/**
 * Titre: Footer modulaire du portail Guldagil - CSS critique intégré
 * Chemin: /templates/footer.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Variables par défaut avec fallbacks
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$build_date = defined('BUILD_DATE') ? BUILD_DATE : date('d/m/Y H:i');
$current_module = $current_module ?? 'home';
$show_admin_footer = $show_admin_footer ?? false;
$current_user = $current_user ?? null;

// Version info formatée
$version_info = [
    'version' => $app_version,
    'build' => $build_number,
    'short_build' => substr($build_number, -8),
    'date' => $build_date,
    'year' => date('Y')
];

// Déterminer si afficher section admin
$is_admin = ($current_user['role'] ?? '') === 'admin' || ($current_user['role'] ?? '') === 'dev';
$show_admin = $show_admin_footer && $is_admin;
?>
        </div><!-- Fin main-container -->
    </main><!-- Fin portal-main -->

    <!-- Section administration (conditionnelle) -->
    <?php if ($show_admin): ?>
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
                    <div class="admin-icon">🔧</div>
                    <h4>Maintenance</h4>
                    <p>Optimisation et diagnostics</p>
                    <a href="/admin/maintenance.php" class="admin-btn">Accéder</a>
                </div>
                
                <div class="admin-card">
                    <div class="admin-icon">📊</div>
                    <h4>Statistiques</h4>
                    <p>Tableaux de bord et rapports</p>
                    <a href="/admin/stats.php" class="admin-btn">Accéder</a>
                </div>
                
                <?php if (($current_user['role'] ?? '') === 'dev'): ?>
                <div class="admin-card">
                    <div class="admin-icon">🛠️</div>
                    <h4>Outils dev</h4>
                    <p>Diagnostics et débogage</p>
                    <a href="/admin/dev-tools.php" class="admin-btn">Accéder</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer principal -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-title"><?= htmlspecialchars($app_name) ?></div>
                <div class="footer-subtitle">Solutions professionnelles</div>
            </div>
            
            <div class="footer-info">
                <div class="version-info">
                    <span class="version-label">Version:</span>
                    <span class="version-number"><?= htmlspecialchars($version_info['version']) ?></span>
                    <span class="build-separator">|</span>
                    <span class="build-label">Build:</span>
                    <span class="build-number">#<?= htmlspecialchars($version_info['short_build']) ?></span>
                </div>
                <div class="build-info">
                    <span class="build-date"><?= htmlspecialchars($version_info['date']) ?></span>
                </div>
            </div>
            
            <div class="footer-copyright">
                <p>&copy; <?= htmlspecialchars($version_info['year']) ?> <?= htmlspecialchars($app_author) ?></p>
            </div>
        </div>
    </footer>

    <!-- CSS critique footer intégré -->
    <style>
        /* CSS critique footer - Performance */
        .admin-section {
            background: var(--gray-50, #f9fafb);
            padding: var(--spacing-xl, 2rem) 0;
            border-top: 1px solid var(--gray-200, #e5e7eb);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg, 1.5rem);
            margin-top: var(--spacing-lg, 1.5rem);
        }

        .admin-card {
            background: white;
            padding: var(--spacing-lg, 1.5rem);
            border-radius: var(--radius-lg, 0.75rem);
            box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid var(--gray-200, #e5e7eb);
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
        }

        .admin-icon {
            font-size: 2rem;
            margin-bottom: var(--spacing-md, 1rem);
            display: block;
        }

        .admin-card h4 {
            margin: 0 0 var(--spacing-sm, 0.5rem);
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900, #111827);
        }

        .admin-card p {
            margin: 0 0 var(--spacing-md, 1rem);
            color: var(--gray-600, #4b5563);
            font-size: 0.875rem;
        }

        .admin-btn {
            display: inline-block;
            padding: var(--spacing-sm, 0.5rem) var(--spacing-md, 1rem);
            background: var(--primary-blue, #3182ce);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md, 0.5rem);
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .admin-btn:hover {
            background: var(--primary-blue-dark, #2c5282);
        }

        /* Footer principal */
        .portal-footer {
            background: linear-gradient(135deg, var(--gray-800, #1f2937), var(--gray-900, #111827));
            color: white;
            padding: var(--spacing-xl, 2rem) 0;
            margin-top: var(--spacing-xl, 2rem);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg, 1.5rem);
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: var(--spacing-lg, 1.5rem);
        }

        .footer-brand {
            text-align: left;
        }

        .footer-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .footer-subtitle {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .footer-info {
            text-align: center;
            font-size: 0.875rem;
        }

        .version-info {
            margin-bottom: 0.25rem;
        }

        .version-label, .build-label {
            opacity: 0.7;
        }

        .version-number, .build-number {
            font-weight: 600;
            margin-left: 0.25rem;
        }

        .build-separator {
            margin: 0 var(--spacing-sm, 0.5rem);
            opacity: 0.5;
        }

        .build-info {
            opacity: 0.7;
            font-size: 0.8rem;
        }

        .footer-copyright {
            text-align: right;
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .footer-copyright p {
            margin: 0;
        }

        /* Responsive footer */
        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: var(--spacing-md, 1rem);
            }

            .footer-brand,
            .footer-copyright {
                text-align: center;
            }

            .admin-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-md, 1rem);
            }
        }

        @media (max-width: 480px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
            
            .portal-footer {
                padding: var(--spacing-lg, 1.5rem) 0;
            }
        }
    </style>

    <!-- JavaScript modulaire -->
    <?php if ($module_js && file_exists(ROOT_PATH . "/assets/js/modules/{$current_module}.js")): ?>
        <script defer src="/assets/js/modules/<?= htmlspecialchars($current_module) ?>.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    <?php endif; ?>

    <!-- JavaScript global -->
    <script>
        // Fonctions globales footer
        document.addEventListener('DOMContentLoaded', function() {
            // Version console info
            console.log('🏠 <?= htmlspecialchars($app_name) ?> v<?= htmlspecialchars($version_info['version']) ?>');
            console.log('🔧 Build #<?= htmlspecialchars($version_info['short_build']) ?> - <?= htmlspecialchars($version_info['date']) ?>');
            
            // Performance monitoring
            if (window.performance && window.performance.timing) {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                if (loadTime > 0) {
                    console.log('⚡ Page chargée en ' + loadTime + 'ms');
                }
            }
            
            // Module actuel
            console.log('📍 Module: <?= htmlspecialchars($current_module) ?>');
        });

        // Gestion erreurs globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JS:', e.filename, e.lineno, e.message);
        });

        // Navigation sécurisée
        function goHome() {
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                window.location.href = '/';
            }
        }

        // Utilitaire AJAX sécurisé
        function secureAjax(url, options = {}) {
            const defaults = {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            };
            
            return fetch(url, Object.assign(defaults, options));
        }
    </script>
</body>
</html>
