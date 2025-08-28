/**
 * Titre: Gestionnaire de bannière cookie RGPD pour Portail Guldagil
 * Chemin: /assets/js/cookie_banner.js
 * Version: 1.0 - Optimisé pour persistance permanente
 * RGPD 2025 compliant - Cookies techniques uniquement
 */

class CookieBannerManager {
    constructor() {
        this.cookieName = 'guldagil_cookie_consent';
        this.consentExpiry = 730; // 2 ans - très long pour éviter les réapparitions
        this.localStorageKey = 'guldagil_cookie_consent_permanent';
        this.init();
    }

    init() {
        // Vérifier toutes les sources de consentement possibles
        if (this.hasAnyConsent()) {
            // Juste créer le bouton de gestion discret, sans bannière
            this.createManageButton();
        } else {
            // Premier passage - afficher la bannière une seule fois
            this.createBanner();
        }
    }

    hasAnyConsent() {
        // Vérifier dans l'ordre: localStorage (plus permanent), puis cookie
        return this.hasLocalStorageConsent() || this.hasCookieConsent();
    }

    hasLocalStorageConsent() {
        try {
            return localStorage.getItem(this.localStorageKey) === 'accepted' || 
                   localStorage.getItem(this.localStorageKey) === 'minimal';
        } catch (e) {
            return false;
        }
    }

    hasCookieConsent() {
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
        // Définir pour TOUT le domaine principal
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
        
        // Stocker aussi dans localStorage pour persistance maximale
        try {
            localStorage.setItem(this.localStorageKey, value);
        } catch (e) {
            console.log('LocalStorage non disponible, utilisation des cookies uniquement');
        }
        
        // Si utilisateur connecté, envoyer aussi à la base de données
        this.saveToDatabase(value);
    }

    saveToDatabase(value) {
        // Vérifier si l'utilisateur est connecté
        const userLoggedIn = document.body.classList.contains('authenticated');
        
        if (userLoggedIn) {
            // Envoyer le choix à un endpoint spécial pour enregistrement BDD
            fetch('/api/save_cookie_preference.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    preference: value
                })
            }).catch(err => console.log('Impossible de sauvegarder en BDD, utilisation locale uniquement'));
        }
    }

    createBanner() {
        // Vérifier si la bannière existe déjà
        if (document.getElementById('cookie-banner')) return;
        
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
                        📋 Plus de détails
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
        // Vérifier si le bouton existe déjà
        if (document.getElementById('cookie-manage-btn')) return;
        
        const button = document.createElement('button');
        button.id = 'cookie-manage-btn';
        button.className = 'cookie-manage-btn';
        button.innerHTML = '🍪';
        button.title = 'Gérer mes cookies';
        button.onclick = () => this.showManageModal();
        
        // Rendre le bouton très discret
        button.style.opacity = '0.6';
        button.style.transform = 'scale(0.8)';
        
        document.body.appendChild(button);
        
        // Animation d'apparition discrète
        setTimeout(() => {
            button.style.opacity = '0.8';
            button.style.transform = 'scale(1)';
        }, 1000);
        
        // Survol pour visibilité
        button.addEventListener('mouseenter', () => {
            button.style.opacity = '1';
            button.style.transform = 'scale(1.1)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.opacity = '0.8';
            button.style.transform = 'scale(1)';
        });
    }

    acceptAll() {
        this.setCookie(this.cookieName, 'accepted', this.consentExpiry);
        this.hideBanner();
        this.showNotification('✅ Tous les cookies acceptés - Merci !');
        
        // Event personnalisé pour modules
        window.dispatchEvent(new CustomEvent('cookiesAccepted', { 
            detail: { level: 'all' } 
        }));
    }

    acceptMinimal() {
        this.setCookie(this.cookieName, 'minimal', this.consentExpiry);
        this.hideBanner();
        this.showNotification('⚙️ Cookies techniques acceptés');
        
        // Event personnalisé pour modules
        window.dispatchEvent(new CustomEvent('cookiesAccepted', { 
            detail: { level: 'minimal' } 
        }));
    }

    hideBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.classList.remove('cookie-banner-visible');
            setTimeout(() => {
                banner.remove();
            }, 300);
        }
        
        // Créer le bouton de gestion après avoir masqué la bannière
        setTimeout(() => {
            this.createManageButton();
        }, 500);
    }

    showDetails() {
        this.showManageModal();
    }

    showManageModal() {
        // Supprimer modal existant
        const existingModal = document.getElementById('cookie-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'cookie-modal';
        modal.className = 'cookie-modal';
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h2>🍪 Gestion des cookies</h2>
                    <button class="cookie-modal-close" onclick="this.closest('.cookie-modal').remove()">✕</button>
                </div>
                
                <div class="cookie-modal-body">
                    <div class="current-status">
                        <h3>📊 Statut actuel</h3>
                        <p class="status-text">
                            ${this.hasAnyConsent() ? 
                                (this.getCookie(this.cookieName) === 'accepted' ? '✅ Tous cookies acceptés' : '⚙️ Cookies techniques uniquement') 
                                : '❌ Aucun consentement'}
                        </p>
                    </div>
                    
                    <div class="cookie-categories">
                        <div class="cookie-category">
                            <h3>🔧 Cookies techniques (obligatoires)</h3>
                            <p>Nécessaires au fonctionnement du portail :</p>
                            <ul>
                                <li><code>PHPSESSID</code> - Session utilisateur</li>
                                <li><code>guldagil_cookie_consent</code> - Vos préférences cookies</li>
                                <li><code>portal_theme</code> - Thème d'affichage</li>
                            </ul>
                            <p><strong>Ces cookies ne peuvent pas être désactivés.</strong></p>
                        </div>
                        
                        <div class="cookie-category">
                            <h3>📊 Cookies fonctionnels (optionnels)</h3>
                            <p>Améliorent votre expérience :</p>
                            <ul>
                                <li><code>user_preferences</code> - Vos paramètres d'interface</li>
                                <li><code>module_settings</code> - Configuration des modules</li>
                                <li><code>last_calculations</code> - Historique des calculs</li>
                            </ul>
                            <p><em>Aucun tracking externe ni publicité.</em></p>
                        </div>
                    </div>
                    
                    <div class="cookie-actions-modal">
                        <div class="cookie-action-card">
                            <h4>⚙️ Cookies techniques uniquement</h4>
                            <p>Fonctionnalités de base uniquement</p>
                            <button class="cookie-btn cookie-btn-minimal" onclick="cookieBanner.acceptMinimal(); this.closest('.cookie-modal').remove();">
                                Choisir cette option
                            </button>
                        </div>
                        
                        <div class="cookie-action-card">
                            <h4>✅ Accepter tous les cookies</h4>
                            <p>Expérience complète et optimisée</p>
                            <button class="cookie-btn cookie-btn-accept" onclick="cookieBanner.acceptAll(); this.closest('.cookie-modal').remove();">
                                Choisir cette option
                            </button>
                        </div>
                    </div>
                    
                    <div class="cookie-danger-zone">
                        <h4>🗑️ Zone de réinitialisation</h4>
                        <button class="cookie-btn cookie-btn-danger" onclick="cookieBanner.resetConsent(); this.closest('.cookie-modal').remove();">
                            Supprimer tous mes cookies
                        </button>
                    </div>
                </div>
                
                <div class="cookie-modal-footer">
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
        
        // Supprimer localStorage
        try {
            localStorage.removeItem(this.localStorageKey);
        } catch (e) {}

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

        // Event personnalisé
        window.dispatchEvent(new CustomEvent('cookiesReset'));

        // Recréer la bannière après 1 seconde
        setTimeout(() => {
            // Supprimer le bouton de gestion
            const manageBtn = document.getElementById('cookie-manage-btn');
            if (manageBtn) manageBtn.remove();
            
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

// Initialisation automatique mais avec un délai et uniquement si nécessaire
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si le consentement existe déjà dans localStorage ou cookie
    const hasLocalConsent = localStorage.getItem('guldagil_cookie_consent_permanent') === 'accepted' || 
                          localStorage.getItem('guldagil_cookie_consent_permanent') === 'minimal';
    
    const hasCookieConsent = document.cookie
        .split('; ')
        .some(row => row.startsWith('guldagil_cookie_consent=accepted') || 
                      row.startsWith('guldagil_cookie_consent=minimal'));
    
    // Initialiser uniquement si aucun consentement n'est trouvé
    if (!hasLocalConsent && !hasCookieConsent) {
        // Délai avant d'afficher pour ne pas interrompre immédiatement l'expérience utilisateur
        setTimeout(() => {
            window.cookieBanner = new CookieBannerManager();
        }, 2000);
    } else {
        // Consentement déjà donné, juste initialiser sans bannière
        window.cookieBanner = new CookieBannerManager();
    }
});

// Fonction globale simplifiée
window.hasCookieConsent = function(level = 'minimal') {
    // Vérifier localStorage en priorité (plus persistant)
    try {
        const localConsent = localStorage.getItem('guldagil_cookie_consent_permanent');
        if (localConsent) {
            if (level === 'minimal') {
                return localConsent === 'accepted' || localConsent === 'minimal';
            }
            return localConsent === 'accepted';
        }
    } catch (e) {
        // Fallback sur cookie si localStorage non disponible
    }
    
    // Vérifier cookie
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