<?php
/**
 * Titre: Footer du portail Guldagil - Version simplifiée
 * Chemin: /templates/footer.php
 * Version: 1.0
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Variables avec fallbacks sécurisés
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$current_module = $current_module ?? 'home';
$module_js = $module_js ?? true; // Définition de la variable manquante avec valeur par défaut

// Récupérer rôle utilisateur pour personnalisation
$user_role = 'user';
if (isset($current_user['role'])) {
    $user_role = $current_user['role'];
} elseif (isset($_SESSION['user']['role'])) {
    $user_role = $_SESSION['user']['role'];
}
$is_admin_or_dev = in_array($user_role, ['admin', 'dev']);

// NAVIGATION RAPIDE - MODULES EXISTANTS
$nav_links = [
    'home' => ['🏠', 'Accueil', '/'],
    'port' => ['📦', 'Frais de port', '/port/'],
    'adr' => ['⚠️', 'Gestion ADR', '/adr/'],
    'qualite' => ['🔬', 'Contrôle Qualité', '/qualite/'],
    'materiel' => ['🔨', 'Gestion du matériel', '/materiel/'],
    'epi' => ['🥽', 'Équipements de protection', '/epi/'],
    'user' => ['👤', 'Mon compte', '/user/']
];

// Ajouter admin si l'utilisateur est admin ou dev
if ($is_admin_or_dev) {
    $nav_links['admin'] = ['⚙️', 'Administration', '/admin/'];
}

// On retire le lien du module courant
unset($nav_links[$current_module]);

// LIENS LÉGAUX
$legal_links = [
    ['⚖️', 'Mentions légales', '/legal/mentions.php'],
    ['🔒', 'Confidentialité', '/legal/privacy.php'],
    ['📋', 'CGU', '/legal/terms.php']
];
?>

    </main> <!-- Fermeture du main ouvert dans header -->

    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Navigation rapide vers les modules -->
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
                            <span class="legal-icon"><?= $legal_link[0] ?></span>
                            <?= htmlspecialchars($legal_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Informations légales -->
                <div class="footer-info">
                    <p>&copy; <?= date('Y') ?> Guldagil - <?= htmlspecialchars($app_name) ?></p>
                    <p class="version-info">
                        Version <?= htmlspecialchars($app_version) ?> - Build <?= htmlspecialchars($build_number) ?>
                    </p>
                </div>

                <!-- Status système pour admin/dev -->
                <?php if ($is_admin_or_dev): ?>
                <div class="footer-dev-info">
                    <small>🔧 Mode <?= htmlspecialchars($user_role) ?> | Module : <?= htmlspecialchars($current_module) ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Scripts nécessaires -->
    <script src="/assets/js/header.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <!-- JS modulaire -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        $module_js_path = "/{$current_module}/assets/js/{$current_module}.js";
        if (file_exists(ROOT_PATH . "/public" . $module_js_path)): ?>
            <script src="<?= htmlspecialchars($module_js_path) ?>?v=<?= htmlspecialchars($build_number) ?>"></script>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>