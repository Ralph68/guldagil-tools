/**
 * Titre: Gestionnaire de bannière cookie RGPD pour Portail Guldagil
 * Chemin: /assets/js/cookie_banner.js
 * Version: 0.5 beta + build auto
 * RGPD 2025 compliant - Cookies techniques uniquement
 */

class CookieBannerManager {
    constructor() {
        this.cookieName = 'guldagil_cookie_consent';
        this.consentExpiry = 365; // 1 an
        this.init();
    }

    init() {
        // Vérifier si le consentement existe déjà
        if (!this.hasConsent()) {
            this.createBanner();
        }
        
        // Créer le bouton "Gérer mes cookies" fixe
        this.createManageButton();
    }

    hasConsent() {
        const consent = this.getCookie(this.cookieName);
        return consent === 'accepted' || consent === 'minimal';
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Strict;Secure`;
    }

    createBanner() {
        const banner = document.createElement('div');
        banner.id = 'cookie-banner';
        banner.className = 'cookie-banner';
        banner.innerHTML = `
            <div class="cookie-banner-content">
                <div class="cookie-banner-text">
                    <div class="cookie-icon">🍪</div>
                    <div class="cookie-message">
                        <h3>Respect de votre vie privée</h3>
                        <p>
                            Ce portail utilise uniquement des <strong>cookies techniques nécessaires</strong> 
                            au fonctionnement (session, préférences). Aucun tracking publicitaire.
                        </p>
                        <p class="cookie-legal-link">
                            <a href="/legal/privacy.php" target="_blank">📋 Politique de confidentialité complète</a>
                        </p>
                    </div>
                </div>
                <div class="cookie-banner-actions">
                    <button class="cookie-btn cookie-btn-accept" onclick="cookieBanner.acceptAll()">
                        ✅ Accepter
                    </button>
                    <button class="cookie-btn cookie-btn-minimal" onclick="cookieBanner.acceptMinimal()">
                        ⚙️ Cookies techniques uniquement
                    </button>
                    <button class="cookie-btn cookie-btn-details" onclick="cookieBanner.showDetails()">
                        ℹ️ Détails
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);
        
        // Animation d'entrée
        setTimeout(() => {
            banner.classList.add('cookie-banner-visible');
        }, 500);
    }

    createManageButton() {
        const button = document.createElement('button');
        button.id = 'cookie-manage-btn';
        button.className = 'cookie-manage-btn';
        button.innerHTML = '🍪';
        button.title = 'Gérer mes cookies';
        button.onclick = () => this.showManageModal();
        
        document.body.appendChild(button);
    }

    acceptAll() {
        this.setCookie(this.cookieName, 'accepted', this.consentExpiry);
        this.removeBanner();
        this.showNotification('✅ Cookies acceptés - Fonctionnalités complètes activées');
    }

    acceptMinimal() {
        this.setCookie(this.cookieName, 'minimal', this.consentExpiry);
        this.removeBanner();
        this.showNotification('⚙️ Cookies techniques uniquement - Fonctionnalités de base');
    }

    removeBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.classList.add('cookie-banner-hidden');
            setTimeout(() => banner.remove(), 300);
        }
    }

    showDetails() {
        const modal = document.createElement('div');
        modal.id = 'cookie-details-modal';
        modal.className = 'cookie-modal';
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h2>🍪 Détail des cookies utilisés</h2>
                    <button class="cookie-modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="cookie-modal-body">
                    <div class="cookie-category">
                        <h3>🔧 Cookies techniques (obligatoires)</h3>
                        <p>Ces cookies sont indispensables au fonctionnement du portail :</p>
                        <ul>
                            <li><code>PHPSESSID</code> - Session utilisateur (temporaire)</li>
                            <li><code>guldagil_preferences</code> - Préférences d'affichage (1 an)</li>
                            <li><code>guldagil_cookie_consent</code> - Mémorisation de vos choix (1 an)</li>
                        </ul>
                        <p><strong>Durée :</strong> Session ou 1 an maximum</p>
                        <p><strong>Finalité :</strong> Fonctionnement du portail</p>
                    </div>
                    
                    <div class="cookie-category">
                        <h3>📊 Cookies de mesure d'audience (optionnels)</h3>
                        <p>
                            ❌ <strong>AUCUN cookie de tracking n'est utilisé</strong><br>
                            ❌ Pas de Google Analytics<br>
                            ❌ Pas de cookies publicitaires<br>
                            ✅ Uniquement des statistiques anonymisées côté serveur
                        </p>
                    </div>

                    <div class="cookie-legal">
                        <h3>⚖️ Vos droits</h3>
                        <p>
                            Conformément au RGPD 2025, vous pouvez à tout moment modifier vos choix 
                            via le bouton 🍪 en bas à droite de votre écran.
                        </p>
                        <p>
                            <a href="/legal/privacy.php" target="_blank">📋 Consulter notre politique complète</a>
                        </p>
                    </div>
                </div>
                <div class="cookie-modal-actions">
                    <button class="cookie-btn cookie-btn-accept" onclick="cookieBanner.acceptAll(); this.parentElement.parentElement.parentElement.remove();">
                        ✅ Accepter tous
                    </button>
                    <button class="cookie-btn cookie-btn-minimal" onclick="cookieBanner.acceptMinimal(); this.parentElement.parentElement.parentElement.remove();">
                        ⚙️ Techniques uniquement
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Fermeture en cliquant à l'extérieur
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    showManageModal() {
        const currentConsent = this.getCookie(this.cookieName);
        
        const modal = document.createElement('div');
        modal.id = 'cookie-manage-modal';
        modal.className = 'cookie-modal';
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h2>⚙️ Gestion des cookies</h2>
                    <button class="cookie-modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="cookie-modal-body">
                    <div class="current-status">
                        <h3>📊 Statut actuel</h3>
                        <p class="status-badge ${currentConsent}">
                            ${currentConsent === 'accepted' ? '✅ Tous les cookies acceptés' : 
                              currentConsent === 'minimal' ? '⚙️ Cookies techniques uniquement' : 
                              '❌ Aucun consentement'}
                        </p>
                        <p><small>Dernière mise à jour : ${new Date().toLocaleDateString('fr-FR')}</small></p>
                    </div>

                    <div class="cookie-actions-grid">
                        <div class="cookie-action-card">
                            <h4>✅ Accepter tous</h4>
                            <p>Cookies techniques + fonctionnalités avancées</p>
                            <button class="cookie-btn cookie-btn-accept" onclick="cookieBanner.acceptAll(); this.parentElement.parentElement.parentElement.parentElement.remove();">
                                Activer
                            </button>
                        </div>
                        
                        <div class="cookie-action-card">
                            <h4>⚙️ Techniques uniquement</h4>
                            <p>Fonctionnement de base du portail</p>
                            <button class="cookie-btn cookie-btn-minimal" onclick="cookieBanner.acceptMinimal(); this.parentElement.parentElement.parentElement.parentElement.remove();">
                                Activer
                            </button>
                        </div>
                        
                        <div class="cookie-action-card">
                            <h4>🗑️ Supprimer tous</h4>
                            <p>Réinitialiser vos préférences</p>
                            <button class="cookie-btn cookie-btn-delete" onclick="cookieBanner.resetConsent(); this.parentElement.parentElement.parentElement.parentElement.remove();">
                                Supprimer
                            </button>
                        </div>
                    </div>

                    <div class="cookie-info">
                        <p>
                            <a href="/legal/privacy.php" target="_blank">📋 Politique de confidentialité</a> | 
                            <a href="/legal/terms.php" target="_blank">📜 Conditions d'utilisation</a>
                        </p>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    resetConsent() {
        // Supprimer le cookie de consentement
        document.cookie = `${this.cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        
        // Supprimer autres cookies non-techniques
        const cookies = document.cookie.split(';');
        cookies.forEach(cookie => {
            const [name] = cookie.split('=');
            const cleanName = name.trim();
            if (cleanName !== 'PHPSESSID' && !cleanName.startsWith('guldagil_')) {
                document.cookie = `${cleanName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            }
        });
        
        this.showNotification('🗑️ Cookies supprimés - Préférences réinitialisées');
        
        // Recréer la bannière après 1 seconde
        setTimeout(() => {
            this.createBanner();
        }, 1000);
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'cookie-notification';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('cookie-notification-visible');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('cookie-notification-visible');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialisation automatique quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.cookieBanner = new CookieBannerManager();
});

// Fonction globale pour vérifier le consentement (utilisable par d'autres scripts)
window.hasCookieConsent = function(level = 'minimal') {
    const consent = document.cookie
        .split('; ')
        .find(row => row.startsWith('guldagil_cookie_consent='));
    
    if (!consent) return false;
    
    const value = consent.split('=')[1];
    
    if (level === 'minimal') {
        return value === 'accepted' || value === 'minimal';
    }
    
    return value === 'accepted';
};
