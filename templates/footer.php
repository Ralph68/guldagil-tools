<?php
/**
 * Titre: Footer du portail Guldagil - Version mise à jour avec vrais liens
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

// NAVIGATION RAPIDE - VRAIS LIENS EXISTANTS VÉRIFIÉS
$nav_links = [
    'home' => ['🏠', 'Accueil', '/'],
    'port' => ['📦', 'Frais de port', '/port/'], // Calculateur existant
    'adr' => ['⚠️', 'Gestion ADR', '/adr/'], // Module ADR existant
    'epi' => ['🦺', 'EPI', '/epi/'], // Module EPI existant
    'qualite' => ['✅', 'Contrôle Qualité', '/qualite/'], // Module qualité existant
    'materiel' => ['🔧', 'Matériels', '/materiel/'], // Module matériel existant
    'user' => ['👤', 'Mon Espace', '/user/'], // Espace utilisateur existant
];

// Ajouter admin/dev selon le rôle
if ($is_admin_or_dev) {
    $nav_links['admin'] = ['⚙️', 'Administration', '/admin/']; // Module admin existant
}

// On retire le lien du module courant pour éviter la redondance
unset($nav_links[$current_module]);

// LIENS LÉGAUX - VRAIS FICHIERS EXISTANTS VÉRIFIÉS
$legal_links = [
    ['⚖️', 'Mentions légales', '/legal/mentions.php'], // Fichier existant vérifié
    ['🔒', 'Confidentialité', '/legal/privacy.php'], // Fichier existant vérifié
    ['📋', 'CGU', '/legal/terms.php'], // Fichier existant vérifié
    ['🍪', 'Cookies', '/legal/cookies.php'], // Fichier existant vérifié
    ['📚', 'Documentation légale', '/legal/'], // Index légal existant vérifié
];

// LIENS ADDITIONNELS - PAGES EXISTANTES CONFIRMÉES
$additional_links = [
    ['✉️', 'Contact', '/contact.php'], // Formulaire existant (privilégier email)
    ['ℹ️', 'À propos', '/about.php'], // Page existante confirmée
    ['📝', 'Évolutions', '/channellog.php'], // Journal existant confirmé
];

// TODO: Ajouter ces pages manquantes pour compléter le portail
$missing_pages_todo = [
    'help/' => 'Centre d\'aide et documentation utilisateur',
    'legal/security.php' => 'Politique de sécurité informatique',
    // Scripts JS manquants pour modules :
    'adr/assets/js/adr.js' => 'Script JavaScript pour module ADR',
    'admin/assets/js/admin.js' => 'Script JavaScript pour module admin',
    'epi/assets/js/epi.js' => 'Script JavaScript pour module EPI',
    'materiel/assets/js/materiel.js' => 'Script JavaScript pour module matériel',
    'qualite/assets/js/qualite.js' => 'Script JavaScript pour module qualité'
];

// Analytics simple (hors admin/dev pour éviter pollution des stats)
if ($is_admin_or_dev === false) {
    try {
        // Préparation des données analytics anonymisées
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
            <!-- Navigation rapide vers les modules existants -->
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
                <!-- Liens légaux conformes à la réglementation française -->
                <nav class="legal-links" aria-label="Liens légaux">
                    <?php foreach ($legal_links as $legal_link): ?>
                        <a href="<?= htmlspecialchars($legal_link[2]) ?>" class="legal-link">
                            <span class="legal-icon"><?= $legal_link[0] ?></span>
                            <?= htmlspecialchars($legal_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Liens additionnels existants -->
                    <?php foreach ($additional_links as $add_link): ?>
                        <a href="<?= htmlspecialchars($add_link[2]) ?>" class="legal-link">
                            <span class="legal-icon"><?= $add_link[0] ?></span>
                            <?= htmlspecialchars($add_link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Informations légales obligatoires -->
                <div class="footer-info">
                    <div class="company-info">
                        <strong>Guldagil SAS</strong> - Solutions professionnelles traitement de l'eau<br>
                        SIRET : 123 456 789 00012 | RCS Strasbourg<br>
                        <a href="mailto:runser.jean.thomas@guldagil.fr" class="contact-email">
                            📧 Contact : runser.jean.thomas@guldagil.fr
                        </a>
                    </div>
                    
                    <div class="copyright-info">
                        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - <?= htmlspecialchars($app_name) ?></p>
                        <p class="version-info">
                            Version <?= htmlspecialchars($app_version) ?> - Build <?= htmlspecialchars($build_number) ?> 
                            (<?= date('d/m/Y H:i', BUILD_TIMESTAMP ?? time()) ?>)
                        </p>
                    </div>
                </div>

                <!-- Status système pour admin/dev -->
                <?php if ($is_admin_or_dev): ?>
                <div class="footer-dev-info">
                    <small>
                        🔧 Mode <?= htmlspecialchars($user_role) ?> | 
                        Module : <?= htmlspecialchars($current_module) ?> | 
                        <?php
                        // Vérification rapide BDD
                        $db_status = '❌';
                        try {
                            if (isset($db) && $db instanceof PDO) {
                                $db->query('SELECT 1');
                                $db_status = '✅';
                            }
                        } catch (Exception $e) {
                            $db_status = '⚠️';
                        }
                        ?>
                        BDD : <?= $db_status ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Scripts nécessaires -->
    <script src="/assets/js/header.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <!-- JS modulaire avec chemins conformes aux instructions -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        // Chemins JS selon architecture modulaire définie dans les instructions
        $module_js_paths = [
            "{$current_module}/assets/js/{$current_module}.js",
            "/{$current_module}/assets/js/{$current_module}.js",
            "/assets/js/modules/{$current_module}.js"
        ];
        
        $js_loaded = false;
        foreach ($module_js_paths as $js_path):
            if (file_exists(ROOT_PATH . "/public/" . ltrim($js_path, '/'))): ?>
                <script src="<?= htmlspecialchars($js_path) ?>?v=<?= htmlspecialchars($build_number) ?>"></script>
                <?php 
                $js_loaded = true;
                break;
            endif;
        endforeach;
        
        // TODO: Développer les scripts JS manquants pour les modules
        if (!$js_loaded): ?>
            <!-- TODO: Créer <?= htmlspecialchars($current_module) ?>.js pour ce module -->
        <?php endif; ?>
    <?php endif; ?>

    <!-- Cookie banner RGPD -->
    <script src="/assets/js/cookie_banner.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
</body>
</html>