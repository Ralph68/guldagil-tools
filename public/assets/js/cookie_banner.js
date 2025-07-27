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
    }

    acceptAll() {
        this.setCookie(this.cookieName, 'accepted', this.consentExpiry);
        this.removeBanner();
        this.showNotification('✅ Préférences sauvegardées pour tout le site');
        this.createManageButton();
    }

    acceptMinimal() {
        this.setCookie(this.cookieName, 'minimal', this.consentExpiry);
        this.removeBanner();
        this.showNotification('⚙️ Préférences sauvegardées pour tout le site');
        this.createManageButton();
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
