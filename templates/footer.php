<?php
/**
 * Titre: Footer du portail Guldagil - Architecture complète
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
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('ymdHis');
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$current_module = $current_module ?? 'home';
$module_js = $module_js ?? true;

// Récupérer rôle utilisateur
$user_role = 'user';
if (isset($current_user['role'])) {
    $user_role = $current_user['role'];
} elseif (isset($_SESSION['user']['role'])) {
    $user_role = $_SESSION['user']['role'];
}
$is_admin_or_dev = in_array($user_role, ['admin', 'dev']);

// Navigation footer
$nav_links = [
    'home' => ['🏠', 'Accueil', '/'],
    'port' => ['📦', 'Frais de port', '/port/'],
    'adr' => ['⚠️', 'Gestion ADR', '/adr/'],
    'epi' => ['🦺', 'EPI', '/epi/'],
    'qualite' => ['✅', 'Qualité', '/qualite/'],
    'materiel' => ['🔧', 'Matériels', '/materiel/'],
    'user' => ['👤', 'Mon Espace', '/user/'],
];

if ($is_admin_or_dev) {
    $nav_links['admin'] = ['⚙️', 'Administration', '/admin/'];
}

// Supprimer module courant
unset($nav_links[$current_module]);

// Liens légaux
$legal_links = [
    ['⚖️', 'Mentions légales', '/legal/mentions.php'],
    ['🔒', 'Confidentialité', '/legal/privacy.php'],
    ['📋', 'CGU', '/legal/terms.php'],
    ['🍪', 'Cookies', '/legal/cookies.php'],
    ['✉️', 'Contact', '/contact.php'],
    ['ℹ️', 'À propos', '/about.php'],
    ['📝', 'Évolutions', '/channellog.php'],
];
?>

    </main>

    <footer class="portal-footer">
        <div class="footer-container">
            <!-- Navigation rapide -->
            <nav class="footer-navigation" aria-label="Navigation rapide">
                <div class="footer-links-grid">
                    <?php foreach ($nav_links as $key => $link): ?>
                        <a href="<?= htmlspecialchars($link[2]) ?>" class="footer-link">
                            <span class="link-icon"><?= $link[0] ?></span>
                            <?= htmlspecialchars($link[1]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>

        <!-- Section légale -->
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
                            (<?= date('d/m/Y H:i') ?>)
                        </p>
                    </div>
                </div>

                <!-- Status dev -->
                <?php if ($is_admin_or_dev): ?>
                <div class="footer-dev-info">
                    🔧 Mode <?= htmlspecialchars($user_role) ?> | Module : <?= htmlspecialchars($current_module) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/js/header.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        $module_js_paths = [
            "{$current_module}/assets/js/{$current_module}.js",
            "/assets/js/modules/{$current_module}.js"
        ];
        
        foreach ($module_js_paths as $js_path):
            if (file_exists(ROOT_PATH . "/public/" . ltrim($js_path, '/'))): ?>
                <script src="<?= htmlspecialchars($js_path) ?>?v=<?= htmlspecialchars($build_number) ?>"></script>
                <?php break; ?>
            <?php endif;
        endforeach; ?>
    <?php endif; ?>

    <script src="/assets/js/cookie_banner.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    
    <!-- CSS footer intégré -->
    <style>
    .portal-footer {
        margin-top: 2rem;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 2rem 0 1rem;
    }
    
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    .footer-navigation {
        margin-bottom: 2rem;
    }
    
    .footer-links-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
    }
    
    .footer-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
    }
    
    .footer-link:hover {
        background: #e9ecef;
        color: #007bff;
        text-decoration: none;
    }
    
    .link-icon {
        font-size: 1.2rem;
    }
    
    .footer-legal {
        background: #e9ecef;
        padding: 1.5rem 0;
        margin-top: 2rem;
    }
    
    .footer-legal-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        align-items: center;
        text-align: center;
    }
    
    .legal-links {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
    }
    
    .legal-link {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: #495057;
        text-decoration: none;
        font-size: 0.875rem;
    }
    
    .legal-link:hover {
        color: #007bff;
        text-decoration: underline;
    }
    
    .footer-info {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 2rem;
        align-items: center;
        width: 100%;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .company-info {
        line-height: 1.5;
    }
    
    .company-info strong {
        color: #007bff;
    }
    
    .contact-email {
        color: #007bff;
        text-decoration: none;
    }
    
    .contact-email:hover {
        text-decoration: underline;
    }
    
    .copyright-info {
        text-align: right;
    }
    
    .version-info {
        font-size: 0.75rem;
        font-family: monospace;
        margin-top: 0.25rem;
    }
    
    .footer-dev-info {
        margin-top: 1rem;
        font-size: 0.75rem;
        color: #6c757d;
        background: #dee2e6;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    
    @media (max-width: 768px) {
        .footer-links-grid {
            justify-content: flex-start;
        }
        
        .legal-links {
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        
        .footer-info {
            grid-template-columns: 1fr;
            gap: 1rem;
            text-align: center;
        }
        
        .copyright-info {
            text-align: center;
        }
    }
    </style>
    
</body>
</html>