<?php
/**
 * Titre: Footer du portail Guldagil - Version modulaire optimisée
 * Chemin: /templates/footer.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Variables avec fallbacks sécurisés
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$current_module = $current_module ?? 'home';

// Récupérer rôle utilisateur pour personnalisation
$user_role = 'user'; // Défaut
if (isset($current_user['role'])) {
    $user_role = $current_user['role'];
} elseif (isset($_SESSION['user']['role'])) {
    $user_role = $_SESSION['user']['role'];
}

$is_admin_or_dev = in_array($user_role, ['admin', 'dev']);

// Liens de navigation selon le module actuel
$nav_links = [
    'home' => ['🏠', 'Accueil', '/'],
    'calculateur' => ['🚛', 'Calculateur', '/calculateur/'],
    'adr' => ['⚠️', 'Gestion ADR', '/adr/'],
    'qualite' => ['✅', 'Contrôle Qualité', '/qualite/'],
    'user' => ['👤', 'Mon Espace', '/user/']
];

// Retirer le module actuel des liens
unset($nav_links[$current_module]);

// Documentation et liens légaux
$legal_links = [];
if (file_exists(ROOT_PATH . '/legal/privacy.php')) {
    $legal_links[] = ['🔒', 'Confidentialité', '/legal/privacy.php'];
}
if (file_exists(ROOT_PATH . '/legal/terms.php')) {
    $legal_links[] = ['📋', 'CGU', '/legal/terms.php'];
}
if (file_exists(ROOT_PATH . '/help/')) {
    $legal_links[] = ['❓', 'Aide', '/help/'];
}

// Si pas de documentation légale, liens alternatifs
if (empty($legal_links)) {
    $legal_links = [
        ['📞', 'Support', 'mailto:support@guldagil.fr'],
        ['📖', 'Documentation', '#'],
        ['ℹ️', 'À propos', '#']
    ];
}
?>

    </main> <!-- Fermeture du main ouvert dans header -->

    <!-- Footer principal -->
    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Section Branding -->
            <div class="footer-brand">
                <div class="footer-logo-section">
                    <div class="footer-logo">
                        <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                            <img src="/assets/img/logo.png" alt="Logo" class="footer-logo-img">
                        <?php else: ?>
                            <span class="logo-placeholder">🌊</span>
                        <?php endif; ?>
                    </div>
                    <div class="footer-brand-info">
                        <div class="footer-brand-name"><?= htmlspecialchars($app_name) ?></div>
                        <div class="footer-brand-tagline">Solutions professionnelles</div>
                        <div class="footer-company">Secteur traitement des eaux</div>
                    </div>
                </div>
            </div>

            <!-- Navigation rapide -->
            <div class="footer-navigation">
                <h4 class="footer-links-title">Navigation</h4>
                <div class="footer-links-grid">
                    <?php foreach ($nav_links as $key => $link): ?>
                        <a href="<?= htmlspecialchars($link[2]) ?>" class="footer-link">
                            <span class="link-icon"><?= $link[0] ?></span>
                            <?= htmlspecialchars($link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section Admin/Dev ou Support utilisateur -->
            <?php if ($is_admin_or_dev): ?>
                <div class="footer-admin">
                    <h4 class="admin-title">Administration</h4>
                    <div class="admin-grid">
                        <div class="admin-card">
                            <div class="card-header">
                                <span class="card-icon">⚙️</span>
                                <h5 class="card-title">Gestion Système</h5>
                            </div>
                            <p class="card-description">
                                Configuration globale, utilisateurs et modules du portail.
                            </p>
                            <div class="card-actions">
                                <a href="/admin/" class="admin-btn primary">
                                    <span>🔧</span>
                                    Administration
                                </a>
                                <a href="/admin/logs.php" class="admin-btn secondary">
                                    <span>📊</span>
                                    Logs
                                </a>
                            </div>
                        </div>
                        
                        <?php if ($user_role === 'dev'): ?>
                        <div class="admin-card">
                            <div class="card-header">
                                <span class="card-icon">💻</span>
                                <h5 class="card-title">Développement</h5>
                            </div>
                            <p class="card-description">
                                Outils de développement et maintenance technique.
                            </p>
                            <div class="card-actions">
                                <a href="/dev/debug.php" class="admin-btn primary">
                                    <span>🐛</span>
                                    Debug
                                </a>
                                <a href="/admin/scanner.php" class="admin-btn secondary">
                                    <span>🔍</span>
                                    Scanner
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="footer-support">
                    <h4 class="support-title">Besoin d'aide ?</h4>
                    <div class="support-card">
                        <p class="support-text">
                            Notre équipe est là pour vous accompagner dans l'utilisation du portail.
                        </p>
                        <a href="mailto:support@guldagil.fr" class="contact-admin">
                            <span>💬</span>
                            Contacter le support
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section légale et informations -->
        <div class="footer-legal">
            <div class="footer-container footer-legal-container">
                <!-- Informations système -->
                <div class="footer-info">
                    <div class="version-section">
                        <div class="version-details">
                            <div class="version-item">
                                <span class="version-label">Version</span>
                                <span class="version-value" data-type="version"><?= htmlspecialchars($app_version) ?></span>
                            </div>
                            <div class="version-item">
                                <span class="version-label">Build</span>
                                <span class="version-value" data-type="build"><?= htmlspecialchars($build_number) ?></span>
                            </div>
                            <div class="version-item">
                                <span class="version-badge <?= defined('SYSTEM_STATUS') && SYSTEM_STATUS === 'maintenance' ? 'status-maintenance' : 'status-operational' ?>">
                                    <?= defined('SYSTEM_STATUS') && SYSTEM_STATUS === 'maintenance' ? '🔧 Maintenance' : '✅ Opérationnel' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liens légaux -->
                <div class="legal-links">
                    <?php foreach ($legal_links as $legal_link): ?>
                        <a href="<?= htmlspecialchars($legal_link[2]) ?>" class="legal-link">
                            <?= $legal_link[0] ?> <?= htmlspecialchars($legal_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Copyright et statut -->
                <div class="footer-bottom">
                    <div class="copyright">
                        &copy; <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - Tous droits réservés
                    </div>

                    <!-- Indicateur de statut système -->
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span class="status-text">Système opérationnel</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Footer modulaire -->
    <script src="/assets/js/footer.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <!-- JavaScript spécifique au module (chargé depuis header si activé) -->
    
</body>
</html>
