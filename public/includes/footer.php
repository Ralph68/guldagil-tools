<?php
/**
 * Titre: Footer global du portail
 * Chemin: /public/includes/footer.php
 * Version: 0.5 beta + build
 */
?>
    </main>

    <!-- Footer principal -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-copyright">
                <p>© <?= date('Y') ?> Guldagil - Solutions transport & logistique</p>
                <p class="footer-author">Développé par <?= APP_AUTHOR ?></p>
            </div>
            <div class="footer-meta">
                <div class="version-info"><?= renderVersionFooter() ?></div>
                <div class="footer-links">
                    <a href="admin/" class="footer-link">Administration</a>
                    <a href="admin/maintenance.php" class="footer-link">Maintenance</a>
                    <?php if (DEBUG): ?>
                    <a href="?debug=1" class="footer-link footer-link--debug">Debug</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Structure MVC -->
    <script src="assets/js/app.min.js"></script>
    <script src="assets/js/portal.js"></script>
    
    <!-- Configuration JS finale -->
    <script>
        window.PortalConfig = {
            version: '<?= APP_VERSION ?>',
            build: '<?= BUILD_NUMBER ?>',
            buildShort: '<?= substr(BUILD_NUMBER, -8) ?>',
            debug: <?= DEBUG ? 'true' : 'false' ?>,
            modules: <?= json_encode(array_keys($modules ?? [])) ?>,
            metrics: <?= json_encode($stats ?? []) ?>
        };
        
        // Log version pour développement
        if (window.PortalConfig.debug) {
            console.info('🏷️ ' + window.PortalConfig.version + ' (Build #' + window.PortalConfig.buildShort + ')');
        }
    </script>
</body>
</html>
