<?php
/**
 * Titre: Footer professionnel du portail Guldagil - Version améliorée
 * Chemin: /templates/footer.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Informations de version via config/version.php
$version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : date('YmdHis'),
    'formatted_date' => date('d/m/Y H:i'),
    'short_build' => substr(defined('BUILD_NUMBER') ? BUILD_NUMBER : date('YmdHis'), -8),
    'year' => date('Y')
];

// Variables globales
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$current_module = $current_module ?? 'home';
$show_admin_footer = $show_admin_footer ?? ($current_module === 'home');
$user_authenticated = $user_authenticated ?? false;
$current_user = $current_user ?? null;
$is_admin = $user_authenticated && isset($current_user['role']) && $current_user['role'] === 'admin';
?>
        </div><!-- Fin main-container -->
    </main><!-- Fin portal-main -->

    <!-- Section administration (uniquement sur l'accueil) -->
    <?php if ($show_admin_footer && $user_authenticated): ?>
    <section class="admin-section">
        <div class="admin-container">
            <div class="admin-header">
                <h3 class="admin-title">
                    <span class="admin-icon">⚙️</span>
                    Administration et Outils
                </h3>
                <p class="admin-subtitle">Gestion centralisée du portail et des modules</p>
            </div>
            
            <div class="admin-grid">
                <!-- Configuration générale -->
                <div class="admin-card">
                    <div class="card-header">
                        <div class="card-icon admin-config">🔧</div>
                        <h4 class="card-title">Configuration</h4>
                    </div>
                    <p class="card-description">Paramètres globaux, modules et préférences système</p>
                    <div class="card-actions">
                        <a href="/admin/" class="admin-btn primary">
                            <span>⚙️</span>
                            Configurer
                        </a>
                        <a href="/admin/backup.php" class="admin-btn secondary">
                            <span>💾</span>
                            Sauvegarde
                        </a>
                    </div>
                </div>
                
                <!-- Maintenance et diagnostics -->
                <div class="admin-card">
                    <div class="card-header">
                        <div class="card-icon admin-maintenance">🛠️</div>
                        <h4 class="card-title">Maintenance</h4>
                    </div>
                    <p class="card-description">Optimisation, cache, logs et diagnostics système</p>
                    <div class="card-actions">
                        <a href="/admin/maintenance.php" class="admin-btn primary">
                            <span>🔧</span>
                            Diagnostiquer
                        </a>
                        <a href="/admin/logs.php" class="admin-btn secondary">
                            <span>📋</span>
                            Logs
                        </a>
                    </div>
                </div>
                
                <!-- Statistiques et rapports -->
                <div class="admin-card">
                    <div class="card-header">
                        <div class="card-icon admin-stats">📊</div>
                        <h4 class="card-title">Statistiques</h4>
                    </div>
                    <p class="card-description">Tableaux de bord, rapports d'usage et métriques</p>
                    <div class="card-actions">
                        <a href="/admin/stats.php" class="admin-btn primary">
                            <span>📈</span>
                            Voir stats
                        </a>
                        <a href="/admin/reports.php" class="admin-btn secondary">
                            <span>📄</span>
                            Rapports
                        </a>
                    </div>
                </div>
                
                <!-- Gestion des utilisateurs (admin only) -->
                <?php if ($is_admin): ?>
                <div class="admin-card">
                    <div class="card-header">
                        <div class="card-icon admin-users">👥</div>
                        <h4 class="card-title">Utilisateurs</h4>
                    </div>
                    <p class="card-description">Gestion des comptes, rôles et permissions</p>
                    <div class="card-actions">
                        <a href="/admin/users.php" class="admin-btn primary">
                            <span>👤</span>
                            Gérer
                        </a>
                        <a href="/admin/permissions.php" class="admin-btn secondary">
                            <span>🔐</span>
                            Rôles
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Import/Export données -->
                <div class="admin-card">
                    <div class="card-header">
                        <div class="card-icon admin-data">📂</div>
                        <h4 class="card-title">Données</h4>
                    </div>
                    <p class="card-description">Import/export des tarifs, configurations et données</p>
                    <div class="card-actions">
                        <a href="/admin/import.php" class="admin-btn primary">
                            <span>📥</span>
                            Importer
                        </a>
                        <a href="/admin/export.php" class="admin-btn secondary">
                            <span>📤</span>
                            Exporter
                        </a>
                    </div>
                </div>
                
                <!-- Outils développeur (mode debug uniquement) -->
                <?php if (defined('DEBUG') && DEBUG): ?>
                <div class="admin-card dev-tools">
                    <div class="card-header">
                        <div class="card-icon admin-dev">🧪</div>
                        <h4 class="card-title">Outils dev</h4>
                    </div>
                    <p class="card-description">Diagnostics avancés et outils de débogage</p>
                    <div class="card-actions">
                        <a href="/admin/dev-tools.php" class="admin-btn primary">
                            <span>🔍</span>
                            Debug
                        </a>
                        <a href="/admin/api-test.php" class="admin-btn secondary">
                            <span>🧩</span>
                            API Test
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer principal -->
    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Branding et informations société -->
            <div class="footer-brand">
                <div class="footer-logo-section">
                    <div class="footer-logo">
                        <?php if (file_exists(ROOT_PATH . '/assets/img/logo.svg')): ?>
                            <img src="/assets/img/logo.svg" alt="Guldagil" class="footer-logo-img">
                        <?php else: ?>
                            🌊
                        <?php endif; ?>
                    </div>
                    <div class="footer-brand-info">
                        <div class="footer-brand-name"><?= htmlspecialchars($app_name) ?></div>
                        <div class="footer-brand-tagline">Solutions professionnelles</div>
                        <div class="footer-company">Secteur traitement de l'eau</div>
                    </div>
                </div>
                
                <!-- Liens utiles -->
                <div class="footer-links">
                    <h4 class="footer-links-title">Liens utiles</h4>
                    <div class="footer-links-grid">
                        <a href="/" class="footer-link">
                            <span class="link-icon">🏠</span>
                            Accueil
                        </a>
                        <a href="/calculateur/" class="footer-link">
                            <span class="link-icon">🚛</span>
                            Calculateur
                        </a>
                        <a href="/adr/" class="footer-link">
                            <span class="link-icon">⚠️</span>
                            Module ADR
                        </a>
                        <a href="/qualite/" class="footer-link">
                            <span class="link-icon">✅</span>
                            Contrôle Qualité
                        </a>
                        <?php if ($user_authenticated): ?>
                        <a href="/admin/" class="footer-link">
                            <span class="link-icon">⚙️</span>
                            Administration
                        </a>
                        <a href="/auth/logout.php" class="footer-link logout">
                            <span class="link-icon">🚪</span>
                            Déconnexion
                        </a>
                        <?php else: ?>
                        <a href="/auth/login.php" class="footer-link">
                            <span class="link-icon">🔐</span>
                            Connexion
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Informations techniques et version -->
            <div class="footer-info">
                <div class="version-section">
                    <h4 class="version-title">Informations techniques</h4>
                    <div class="version-details">
                        <div class="version-item">
                            <span class="version-label">Version:</span>
                            <span class="version-value"><?= htmlspecialchars($version_info['version']) ?></span>
                        </div>
                        <div class="version-item">
                            <span class="version-label">Build:</span>
                            <span class="version-value">#<?= htmlspecialchars($version_info['short_build']) ?></span>
                        </div>
                        <div class="version-item">
                            <span class="version-label">Dernière MAJ:</span>
                            <span class="version-value"><?= htmlspecialchars($version_info['formatted_date']) ?></span>
                        </div>
                        <?php if (defined('DEBUG') && DEBUG): ?>
                        <div class="version-item debug-info">
                            <span class="version-label">Mode:</span>
                            <span class="version-value debug-badge">Développement</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Support et contact -->
                <div class="support-section">
                    <h4 class="support-title">Support technique</h4>
                    <div class="support-links">
                        <a href="/admin/maintenance.php" class="support-link">
                            <span class="link-icon">🔧</span>
                            Diagnostics
                        </a>
                        <a href="/admin/logs.php" class="support-link">
                            <span class="link-icon">📋</span>
                            Logs système
                        </a>
                        <a href="mailto:<?= htmlspecialchars($app_author) ?>?subject=Support Portail Guldagil" class="support-link">
                            <span class="link-icon">📧</span>
                            Contact dev
                        </a>
                        <?php if (file_exists(ROOT_PATH . '/documentation/README.md')): ?>
                        <a href="/documentation/" class="support-link">
                            <span class="link-icon">📚</span>
                            Documentation
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Copyright et mentions légales -->
            <div class="footer-legal">
                <div class="copyright-section">
                    <p class="copyright-text">
                        © <?= htmlspecialchars($version_info['year']) ?> 
                        <strong><?= htmlspecialchars($app_author) ?></strong>
                    </p>
                    <p class="copyright-company">
                        Développé pour <strong>Guldagil</strong> - Traitement de l'eau
                    </p>
                </div>
                
                <div class="legal-links">
                    <a href="/legal/privacy.php" class="legal-link">Confidentialité</a>
                    <span class="legal-separator">•</span>
                    <a href="/legal/terms.php" class="legal-link">Conditions d'usage</a>
                    <span class="legal-separator">•</span>
                    <a href="/admin/security.php" class="legal-link">Sécurité</a>
                </div>
                
                <!-- Indicateur de statut système -->
                <div class="system-status">
                    <div class="status-indicator online">
                        <span class="status-dot"></span>
                        <span class="status-text">Système opérationnel</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- CSS intégré pour le footer -->
    <style>
        /* Section administration */
        .admin-section {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            padding: var(--spacing-2xl) 0;
            border-top: 1px solid var(--gray-200);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        .admin-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .admin-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }

        .admin-subtitle {
            color: var(--gray-600);
            font-size: 1.125rem;
            margin: 0;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-xl);
            margin-top: var(--spacing-xl);
        }

        .admin-card {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .admin-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .admin-card.dev-tools {
            border: 2px solid #fbbf24;
            background: linear-gradient(135deg, #fef3c7, white);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-config { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .admin-maintenance { background: linear-gradient(135deg, #fed7aa, #fdba74); }
        .admin-stats { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .admin-users { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
        .admin-data { background: linear-gradient(135deg, #fecaca, #fca5a5); }
        .admin-dev { background: linear-gradient(135deg, #fef08a, #fbbf24); }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .card-description {
            color: var(--gray-600);
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0 0 var(--spacing-lg);
        }

        .card-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .admin-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition-normal);
            border: 1px solid transparent;
        }

        .admin-btn.primary {
            background: var(--primary-blue);
            color: white;
        }

        .admin-btn.primary:hover {
            background: var(--primary-blue-dark);
            transform: translateY(-1px);
        }

        .admin-btn.secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .admin-btn.secondary:hover {
            background: var(--gray-200);
            border-color: var(--gray-400);
        }

        /* Footer principal */
        .portal-footer {
            background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
            color: white;
            padding: var(--spacing-2xl) 0 var(--spacing-xl);
            margin-top: var(--spacing-2xl);
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: var(--spacing-2xl);
            align-items: start;
        }

        /* Section branding */
        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xl);
        }

        .footer-logo-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .footer-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .footer-logo-img {
            width: 32px;
            height: 32px;
        }

        .footer-brand-info {
            display: flex;
            flex-direction: column;
        }

        .footer-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .footer-brand-tagline {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-bottom: 0.125rem;
        }

        .footer-company {
            font-size: 0.75rem;
            opacity: 0.7;
            color: var(--primary-blue-light);
        }

        /* Links section */
        .footer-links-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            color: white;
        }

        .footer-links-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-sm);
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            padding: var(--spacing-xs) 0;
            transition: var(--transition-fast);
        }

        .footer-link:hover {
            color: var(--primary-blue-light);
            transform: translateX(2px);
        }

        .footer-link.logout {
            color: rgba(239, 68, 68, 0.8);
        }

        .footer-link.logout:hover {
            color: #fca5a5;
        }

        .link-icon {
            font-size: 0.875rem;
            width: 16px;
            text-align: center;
        }

        /* Section informations */
        .footer-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xl);
        }

        .version-title,
        .support-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            color: white;
        }

        .version-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .version-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .version-label {
            opacity: 0.7;
        }

        .version-value {
            font-weight: 500;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .debug-badge {
            background: #fbbf24;
            color: #92400e;
            padding: 0.125rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .support-links {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .support-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition-fast);
        }

        .support-link:hover {
            color: var(--primary-blue-light);
        }

        /* Section légale */
        .footer-legal {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
            text-align: right;
        }

        .copyright-text {
            font-size: 0.875rem;
            margin: 0 0 0.25rem;
        }

        .copyright-company {
            font-size: 0.75rem;
            opacity: 0.7;
            margin: 0;
        }

        .legal-links {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: 0.75rem;
        }

        .legal-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .legal-link:hover {
            color: var(--primary-blue-light);
        }

        .legal-separator {
            opacity: 0.5;
        }

        .system-status {
            margin-top: var(--spacing-md);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: 0.75rem;
            justify-content: flex-end;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-text {
            opacity: 0.8;
        }

        /* Responsive footer */
        @media (max-width: 1024px) {
            .footer-container {
                grid-template-columns: 1fr 1fr;
                gap: var(--spacing-xl);
            }
            
            .footer-legal {
                grid-column: 1 / -1;
                text-align: center;
                margin-top: var(--spacing-xl);
                padding-top: var(--spacing-xl);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .legal-links {
                justify-content: center;
            }
            
            .status-indicator {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                gap: var(--spacing-xl);
            }
            
            .footer-links-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .admin-btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .portal-footer {
                padding: var(--spacing-xl) 0;
            }
            
            .footer-logo-section {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-sm);
            }
            
            .version-item {
                flex-direction: column;
                gap: 0.25rem;
                text-align: center;
            }
        }
    </style>

    <!-- JavaScript modulaire -->
    <?php if ($module_js && file_exists(ROOT_PATH . "/assets/js/modules/{$current_module}.js")): ?>
    <script src="/assets/js/modules/<?= $current_module ?>.js?v=<?= $build_number ?>"></script>
    <?php endif; ?>

    <!-- JavaScript global -->
    <script src="/assets/js/core.js?v=<?= $build_number ?>"></script>
    
    <!-- Script de base pour interactions footer -->
    <script>
        // Animation d'apparition au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les cartes admin
        document.querySelectorAll('.admin-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Indicateur de statut système (simulation)
        document.addEventListener('DOMContentLoaded', function() {
            const statusDot = document.querySelector('.status-dot');
            if (statusDot) {
                // Simulation vérification système
                setTimeout(() => {
                    statusDot.style.background = '#10b981'; // Vert = OK
                }, 1000);
            }
        });
    </script>
</body>
</html>
