<?php
/**
 * Titre: Footer standalone pour module EPI
 * Chemin: /features/epi/partials/standalone_footer.php
 * Version: 0.5 beta + build auto
 */
?>

    <!-- Footer -->
    <footer class="standalone-footer" style="background: var(--epi-primary); color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="footer-container" style="max-width: 1400px; margin: 0 auto; padding: 0 2rem;">
            <div class="footer-content" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 2rem;">
                
                <!-- Marque -->
                <div class="footer-brand">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">Portail Guldagil</h3>
                    <p style="margin: 0; opacity: 0.8; font-size: 0.9rem;">Solutions professionnelles</p>
                </div>
                
                <!-- Version -->
                <div class="footer-version" style="text-align: center;">
                    <div style="margin-bottom: 0.25rem;">
                        <span style="opacity: 0.7;">Version</span>
                        <span style="font-weight: 600; margin-left: 0.25rem;"><?= defined('APP_VERSION') ? APP_VERSION : '0.5.0' ?></span>
                    </div>
                    <div style="font-size: 0.8rem; opacity: 0.7;">
                        Build #<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : 'auto' ?>
                    </div>
                </div>
                
                <!-- Copyright -->
                <div class="footer-copyright" style="text-align: right;">
                    <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">
                        © <?= date('Y') ?> Guldagil<br>
                        <span style="font-size: 0.8rem;">Tous droits réservés</span>
                    </p>
                </div>
            </div>
            
            <!-- Navigation footer -->
            <div class="footer-nav" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2); text-align: center;">
                <a href="/" style="color: white; text-decoration: none; margin: 0 1rem; opacity: 0.8; transition: opacity 0.3s ease;">Accueil</a>
                <a href="/features/epi/" style="color: white; text-decoration: none; margin: 0 1rem; opacity: 0.8; transition: opacity 0.3s ease;">EPI</a>
                <a href="/calculateur/" style="color: white; text-decoration: none; margin: 0 1rem; opacity: 0.8; transition: opacity 0.3s ease;">Calculateur</a>
                <a href="/admin/" style="color: white; text-decoration: none; margin: 0 1rem; opacity: 0.8; transition: opacity 0.3s ease;">Admin</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript global -->
    <script src="/public/assets/js/portal.js?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : '1' ?>"></script>
    
    <!-- JavaScript module EPI -->
    <script src="/features/epi/assets/js/epi.js?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : '1' ?>"></script>
    
    <!-- JavaScript de performance -->
    <script>
        // Monitoring des performances
        document.addEventListener('DOMContentLoaded', function() {
            // Log de performance
            if (window.performance && window.performance.timing) {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                if (loadTime > 0) {
                    console.log(`⚡ Module EPI chargé en ${loadTime}ms`);
                }
            }
            
            // Gestion des erreurs spécifiques EPI
            window.addEventListener('error', function(e) {
                if (e.filename && e.filename.includes('epi')) {
                    console.error('Erreur module EPI:', e.filename, e.lineno, e.message);
                    
                    // Notification utilisateur en cas d'erreur critique
                    if (window.PortalManager) {
                        window.PortalManager.showToast('error', 'Erreur module', 'Une erreur est survenue dans le module EPI');
                    }
                }
            });
            
            // Détection de la connectivité
            window.addEventListener('online', function() {
                if (window.PortalManager) {
                    window.PortalManager.showToast('success', 'Connexion', 'Connexion rétablie', 3000);
                }
            });
            
            window.addEventListener('offline', function() {
                if (window.PortalManager) {
                    window.PortalManager.showToast('warning', 'Connexion', 'Connexion perdue - Mode hors ligne');
                }
            });
        });
        
        // Utilitaires globaux EPI
        window.EPIUtils = {
            // Navigation sécurisée
            navigateTo: function(url) {
                if (url && typeof url === 'string') {
                    window.location.href = url;
                }
            },
            
            // Formatage des dates
            formatDate: function(date) {
                return new Date(date).toLocaleDateString('fr-FR');
            },
            
            formatDateTime: function(date) {
                return new Date(date).toLocaleString('fr-FR');
            },
            
            // Validation simple
            validateRequired: function(fields) {
                return fields.every(field => field && field.trim().length > 0);
            },
            
            // Copie dans le presse-papier
            copyToClipboard: async function(text) {
                try {
                    await navigator.clipboard.writeText(text);
                    if (window.PortalManager) {
                        window.PortalManager.showToast('success', 'Copié', 'Texte copié dans le presse-papier', 2000);
                    }
                } catch (err) {
                    console.error('Erreur copie:', err);
                }
            }
        };
        
        // Configuration finale
        console.log('🛡️ Module EPI v<?= defined('APP_VERSION') ? APP_VERSION : '0.5.0' ?> - Prêt');
    </script>

    <style>
        /* Styles footer responsive */
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr !important;
                text-align: center !important;
                gap: 1rem !important;
            }
            
            .footer-brand,
            .footer-copyright {
                text-align: center !important;
            }
            
            .footer-nav a {
                display: inline-block;
                margin: 0.25rem 0.5rem !important;
            }
        }
        
        /* Hover effects */
        .footer-nav a:hover {
            opacity: 1 !important;
            text-decoration: underline !important;
        }
    </style>

</body>
</html>
