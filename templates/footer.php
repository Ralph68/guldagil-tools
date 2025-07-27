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
$user_role = 'user';
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
    'user' => ['👤', 'Mon Espace', '/user/'],
    // Ajout des liens Contact et Journal des évolutions
    'contact' => ['✉️', 'Contact', '/contact.php'],
    'channellog' => ['📝', 'Journal des évolutions', '/channellog.php']
];
// On retire le lien du module courant pour éviter la redondance dans le footer
unset($nav_links[$current_module]);

// Liens légaux/documentation
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
if (empty($legal_links)) {
    // TODO: Mettre à jour les liens alternatifs si besoin
    $legal_links = [
        ['📞', 'Support', 'mailto:runser.jean.thomas@guldagil.fr'],
        ['📖', 'Documentation', '#'],
        ['ℹ️', 'À propos', 'about.php']
    ];
}

// Analytics simple (hors admin/dev)
if ($is_admin_or_dev === false) {
    try {
        // Préparation des données analytics
        $analytics_data = [
            'page' => $_SERVER['REQUEST_URI'] ?? '',
            'module' => $current_module,
            'user_id' => $_SESSION['user_id'] ?? 0,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_hash' => md5($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $analytics_dir = ROOT_PATH . '/storage/analytics/';
        // Vérifier existence du dossier analytics
        if (!file_exists($analytics_dir)) {
            mkdir($analytics_dir, 0755, true);
        }
        // Fichier journalier pour limiter la taille
        $log_file = $analytics_dir . 'visits_' . date('Y-m-d') . '.log';
        // Enregistrer l'entrée analytics (une ligne JSON par visite)
        file_put_contents(
            $log_file,
            json_encode($analytics_data) . PHP_EOL,
            FILE_APPEND
        );
    } catch (Exception $e) {
        // Silencieux : ne pas perturber l'expérience utilisateur en cas d'erreur d'analytics
        // TODO: Ajouter un système de log d'erreur si besoin
    }
}
?>

    </main> <!-- Fermeture du main ouvert dans header -->

    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Navigation rapide -->
            <nav class="footer-navigation" aria-label="Navigation rapide">
                <div class="footer-links-grid">
                    <?php foreach ($nav_links as $key => $link): ?>
                        <a href="<?= htmlspecialchars($link[2]) ?>" class="footer-link" data-module="<?= htmlspecialchars($key) ?>">
                            <span class="link-icon"><?= $link[0] ?></span>
                            <?= htmlspecialchars($link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>

        <!-- Section légale et informations -->
        <div class="footer-legal">
            <div class="footer-container footer-legal-container">
                <!-- Liens légaux -->
                <nav class="legal-links" aria-label="Liens légaux">
                    <?php foreach ($legal_links as $legal_link): ?>
                        <a href="<?= htmlspecialchars($legal_link[2]) ?>" class="legal-link">
                            <?= $legal_link[0] ?> <?= htmlspecialchars($legal_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
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
