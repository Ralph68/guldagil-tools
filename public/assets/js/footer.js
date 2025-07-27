/**
 * Titre: JavaScript Footer - Version modulaire optimisée
 * Chemin: /templates/assets/js/footer.js
 * Version: 0.5 beta + build auto
 */

// Vérifier si FooterManager est déjà défini
if (!window.FooterManager) {
    class FooterManager {
        constructor() {
            this.statusUpdateInterval = null;
            this.init();
        }

        init() {
            this.setupVersionDisplay();
            this.setupStatusIndicator();
            this.setupFooterInteractions();
            this.startStatusUpdates();
        }

        setupVersionDisplay() {
            const versionElements = document.querySelectorAll('.version-value');
            versionElements.forEach(element => {
                if (element.dataset.type === 'build') {
                    // Formater le numéro de build
                    const buildNumber = element.textContent;
                    if (buildNumber.length === 8) {
                        const date = buildNumber.substring(0, 8);
                        const year = '20' + date.substring(0, 2);
                        const month = date.substring(2, 4);
                        const day = date.substring(4, 6);
                        const time = date.substring(6, 8);
                        
                        element.title = `Build du ${day}/${month}/${year} - ${time}h`;
                    }
                }
            });
        }

        setupStatusIndicator() {
            const statusDot = document.querySelector('.status-dot');
            const statusText = document.querySelector('.status-text');
            
            if (statusDot && statusText) {
                // Vérifier le statut initial
                this.updateSystemStatus();
                
                // Ajouter interaction au clic
                statusDot.addEventListener('click', () => {
                    this.showDetailedStatus();
                });
                
                statusDot.style.cursor = 'pointer';
                statusDot.title = 'Cliquer pour plus de détails';
            }
        }

        setupFooterInteractions() {
            // Animation des liens admin
            const adminBtns = document.querySelectorAll('.admin-btn');
            adminBtns.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    btn.style.transform = 'translateY(-2px)';
                });
                
                btn.addEventListener('mouseleave', () => {
                    btn.style.transform = '';
                });
            });

            // Contact admin pour les utilisateurs
            const contactBtn = document.querySelector('.contact-admin');
            if (contactBtn) {
                contactBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openContactModal();
                });
            }

            // Liens footer avec analytics
            const footerLinks = document.querySelectorAll('.footer-link, .legal-link');
            footerLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    this.trackFooterClick(link.textContent.trim(), link.href);
                });
            });
        }

        updateSystemStatus() {
            const statusDot = document.querySelector('.status-dot');
            const statusText = document.querySelector('.status-text');
            
            if (!statusDot || !statusText) return;

            // Vérifier connectivité
            if (navigator.onLine) {
                statusDot.style.backgroundColor = '#10b981';
                statusText.textContent = 'Système opérationnel';
                
                // Vérifier API si disponible
                this.checkApiStatus().then(apiStatus => {
                    if (!apiStatus) {
                        statusDot.style.backgroundColor = '#f59e0b';
                        statusText.textContent = 'Services partiels';
                    }
                });
            } else {
                statusDot.style.backgroundColor = '#ef4444';
                statusText.textContent = 'Hors ligne';
            }
        }

        async checkApiStatus() {
            try {
                const response = await fetch('/api/health', {
                    method: 'GET',
                    timeout: 3000
                });
                return response.ok;
            } catch (error) {
                return false;
            }
        }

        showDetailedStatus() {
            const details = {
                timestamp: new Date().toLocaleString('fr-FR'),
                online: navigator.onLine,
                browser: navigator.userAgent.split(' ').pop(),
                session: sessionStorage.length > 0 ? 'Active' : 'Inactive',
                cookies: document.cookie ? 'Activés' : 'Désactivés'
            };

            const message = `
État système - ${details.timestamp}
• Connexion: ${details.online ? 'En ligne' : 'Hors ligne'}
• Session: ${details.session}
• Cookies: ${details.cookies}
• Navigateur: ${details.browser}
        `.trim();

            // Utiliser notification si disponible, sinon alert
            if (window.HeaderNotifications) {
                window.HeaderNotifications.show(message, 'info', 8000);
            } else {
                alert(message);
            }
        }

        openContactModal() {
            // Créer modal de contact simple
            const modal = document.createElement('div');
            modal.className = 'contact-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 2000;
            `;

            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    max-width: 400px;
                    width: 90%;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                ">
                    <h3 style="margin: 0 0 1rem; color: #1f2937;">Contacter l'administration</h3>
                    <p style="color: #6b7280; margin: 0 0 1.5rem; line-height: 1.5;">
                        Pour toute question ou assistance, vous pouvez contacter l'équipe d'administration via les moyens suivants :
                    </p>
                    <div style="margin: 1rem 0;">
                        <strong>📧 Email :</strong> admin@guldagil.fr<br>
                        <strong>📞 Téléphone :</strong> +33 (0)3 XX XX XX XX<br>
                        <strong>💬 Support :</strong> Disponible 9h-17h
                    </div>
                    <button onclick="this.parentNode.parentNode.remove()" style="
                        background: #3182ce;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 6px;
                        cursor: pointer;
                        float: right;
                    ">Fermer</button>
                </div>
            `;

            document.body.appendChild(modal);

            // Fermer en cliquant dehors
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        trackFooterClick(linkText, href) {
            // Analytics simple
            try {
                const event = {
                    type: 'footer_click',
                    link: linkText,
                    url: href,
                    timestamp: Date.now(),
                    user_agent: navigator.userAgent
                };
                
                // Envoyer à analytics ou logger localement
                console.log('Footer Analytics:', event);
                
                // Si service analytics disponible
                if (window.analytics) {
                    window.analytics.track('Footer Link Click', event);
                }
            } catch (error) {
                console.warn('Analytics error:', error);
            }
        }

        startStatusUpdates() {
            // Mettre à jour le statut toutes les 30 secondes
            this.statusUpdateInterval = setInterval(() => {
                this.updateSystemStatus();
            }, 30000);

            // Écouter les changements de connectivité
            window.addEventListener('online', () => {
                this.updateSystemStatus();
                if (window.HeaderNotifications) {
                    window.HeaderNotifications.show('Connexion rétablie', 'success');
                }
            });

            window.addEventListener('offline', () => {
                this.updateSystemStatus();
                if (window.HeaderNotifications) {
                    window.HeaderNotifications.show('Connexion perdue', 'warning', 0);
                }
            });
        }

        destroy() {
            if (this.statusUpdateInterval) {
                clearInterval(this.statusUpdateInterval);
            }
        }
    }

    // Ajouter FooterManager à l'objet global
    window.FooterManager = FooterManager;

    class FooterUtils {
        static updateVersion(version, build) {
            const versionElement = document.querySelector('[data-type="version"]');
            const buildElement = document.querySelector('[data-type="build"]');
            
            if (versionElement) versionElement.textContent = version;
            if (buildElement) buildElement.textContent = build;
        }

        static setSystemStatus(status, message) {
            const statusDot = document.querySelector('.status-dot');
            const statusText = document.querySelector('.status-text');
            
            if (!statusDot || !statusText) return;

            const colors = {
                online: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                maintenance: '#6b7280'
            };

            statusDot.style.backgroundColor = colors[status] || colors.online;
            statusText.textContent = message || 'Statut inconnu';
        }

        static addFooterNotification(message, type = 'info') {
            const footer = document.querySelector('.portal-footer');
            if (!footer) return;

            const notification = document.createElement('div');
            notification.className = 'footer-notification';
            notification.style.cssText = `
                background: ${type === 'warning' ? '#fef3c7' : '#dbeafe'};
                color: ${type === 'warning' ? '#92400e' : '#1e40af'};
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border-radius: 8px;
                font-size: 0.875rem;
                text-align: center;
                animation: slideDown 0.3s ease-out;
            `;
            
            notification.textContent = message;
            footer.prepend(notification);

            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideUp 0.3s ease-out';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
    }

    // Styles CSS pour les animations
    const footerStyles = `
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-20px); opacity: 0; }
        }
        
        .contact-modal button:hover {
            background: #2563eb !important;
        }
    `;

    // Injection des styles
    const styleSheet = document.createElement('style');
    styleSheet.textContent = footerStyles;
    document.head.appendChild(styleSheet);

    // Initialisation automatique
    document.addEventListener('DOMContentLoaded', () => {
        new FooterManager();
        
        // Exposer utilitaires globalement
        window.FooterUtils = FooterUtils;
    });

    // Nettoyage à la fermeture
    window.addEventListener('beforeunload', () => {
        if (window.footerManager) {
            window.footerManager.destroy();
        }
    });
}
