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

// Enregistrer visite pour analytics simple
if ($is_admin_or_dev === false) { // N'enregistre pas les visites admin
    try {
        $analytics_data = [
            'page' => $_SERVER['REQUEST_URI'] ?? '',
            'module' => $current_module,
            'user_id' => $_SESSION['user_id'] ?? 0,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_hash' => md5($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Vérifier existence du dossier
        $analytics_dir = ROOT_PATH . '/storage/analytics/';
        if (!file_exists($analytics_dir)) {
            mkdir($analytics_dir, 0755, true);
        }
        
        // Fichier journalier pour limiter la taille
        $log_file = $analytics_dir . 'visits_' . date('Y-m-d') . '.log';
        
        // Enregistrer l'entrée
        file_put_contents(
            $log_file,
            json_encode($analytics_data) . PHP_EOL,
            FILE_APPEND
        );
    } catch (Exception $e) {
        // Silencieux en cas d'erreur - ne pas perturber l'expérience utilisateur
    }
}
?>

    </main> <!-- Fermeture du main ouvert dans header -->

    <!-- Footer principal -->
    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Section Logo et Navigation -->
            <div class="footer-main">
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
                        <div class="footer-brand-tagline">Solutions professionnelles traitement des eaux</div>
                    </div>
                </div>

                <!-- Navigation rapide -->
                <div class="footer-navigation">
                    <div class="footer-links-grid">
                        <?php foreach ($nav_links as $key => $link): ?>
                            <a href="<?= htmlspecialchars($link[2]) ?>" class="footer-link" data-module="<?= htmlspecialchars($key) ?>">
                                <span class="link-icon"><?= $link[0] ?></span>
                                <?= htmlspecialchars($link[1]) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section légale et informations -->
        <div class="footer-legal">
            <div class="footer-container footer-legal-container">
                <!-- Liens légaux -->
                <div class="legal-links">
                    <?php foreach ($legal_links as $legal_link): ?>
                        <a href="<?= htmlspecialchars($legal_link[2]) ?>" class="legal-link">
                            <?= $legal_link[0] ?> <?= htmlspecialchars($legal_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Copyright et version -->
                <div class="footer-bottom">
                    <div class="copyright">
                        &copy; <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - Tous droits réservés
                    </div>
                    <div class="version-info">
                        <span class="version-value"><?= htmlspecialchars($app_version) ?></span> |
                        <span class="build-value"><?= htmlspecialchars($build_number) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Footer modulaire -->
    <script src="/assets/js/footer.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <!-- Analytics script - chargé uniquement pour admin -->
    <?php if ($is_admin_or_dev): ?>
    <script>
        // Initialiser le tracker en mode admin seulement
        window.portalAnalytics = {
            enabled: true,
            isAdmin: true,
            module: '<?= htmlspecialchars($current_module) ?>',
            pageId: '<?= htmlspecialchars(basename($_SERVER['REQUEST_URI'])) ?>'
        };
    </script>
    <?php else: ?>
    <script>
        // Version utilisateur (tracking uniquement)
        window.portalAnalytics = {
            enabled: true,
            isAdmin: false,
            module: '<?= htmlspecialchars($current_module) ?>',
            pageId: '<?= htmlspecialchars(basename($_SERVER['REQUEST_URI'])) ?>'
        };
    </script>
    <?php endif; ?>
    
</body>
</html>
