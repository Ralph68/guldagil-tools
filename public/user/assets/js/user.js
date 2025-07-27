/**
 * Titre: JavaScript pour dashboard utilisateur
 * Chemin: /public/user/assets/js/user.js
 * Version: 0.6
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('👤 Dashboard utilisateur initialisé');

    // ==============================================
    // GESTION DES PRÉFÉRENCES UTILISATEUR
    // ==============================================
    const savePreferencesButton = document.getElementById('save-preferences-btn');
    const preferencesForm = document.getElementById('preferences-form');

    if (savePreferencesButton && preferencesForm) {
        savePreferencesButton.addEventListener('click', (event) => {
            event.preventDefault();
            const formData = new FormData(preferencesForm);
            const preferences = {};
            formData.forEach((value, key) => {
                preferences[key] = value;
            });

            fetch('/user/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ preferences })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Préférences sauvegardées avec succès.');
                    } else {
                        alert('Erreur lors de la sauvegarde des préférences.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur réseau.');
                });
        });
    }

    // ==============================================
    // ANIMATIONS ET INTERACTIONS
    // ==============================================
    function initAnimations() {
        const animatedElements = document.querySelectorAll('.action-card, .stat-card, .module-item, .activity-item');
        animatedElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.5s ease';
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        });

        animatedElements.forEach(element => observer.observe(element));
    }

    function initActionCards() {
        const actionCards = document.querySelectorAll('.action-card');
        actionCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px) scale(1.02)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    // ==============================================
    // INITIALISATION
    // ==============================================
    initAnimations();
    initActionCards();

    console.log('✅ Dashboard utilisateur entièrement initialisé');
});
    }
    
    function animateCounter(element, start, end, duration) {
        const range = end - start;
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function pour une animation plus naturelle
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.round(start + (range * easeOutQuart));
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = end;
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    // ==============================================
    // MISE À JOUR TEMPS RÉEL
    // ==============================================
    
    function initRealTimeUpdates() {
        // Mise à jour de l'heure de dernière connexion
        const lastLoginElement = document.querySelector('.last-login');
        if (lastLoginElement) {
            setInterval(() => {
                const now = new Date();
                const timeString = now.toLocaleString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                // Note: En production, cette valeur viendrait du serveur
            }, 60000); // Mise à jour chaque minute
        }
        
        // Mise à jour des temps relatifs dans l'activité
        updateRelativeTimes();
        setInterval(updateRelativeTimes, 60000);
    }
    
    function updateRelativeTimes() {
        const timeElements = document.querySelectorAll('.activity-time');
        timeElements.forEach(element => {
            const text = element.textContent;
            
            // Mise à jour basique des temps relatifs
            if (text.includes('Il y a')) {
                // Logique de mise à jour des temps relatifs
                // En production, cela serait plus sophistiqué
            }
        });
    }
    
    // ==============================================
    // NOTIFICATIONS ET ALERTES
    // ==============================================
    
    function initNotifications() {
        // Vérifier les notifications en attente
        checkPendingNotifications();
        
        // Vérifier périodiquement
        setInterval(checkPendingNotifications, 30000); // Toutes les 30 secondes
    }
    
    function checkPendingNotifications() {
        // Simulation de vérification de notifications
        // En production, cela ferait un appel AJAX au serveur
        
        const notifications = getStoredNotifications();
        notifications.forEach(notification => {
            showNotification(notification.message, notification.type);
        });
        
        clearStoredNotifications();
    }
    
    function getStoredNotifications() {
        // Simulation - en production, récupérer depuis sessionStorage ou serveur
        return [];
    }
    
    function clearStoredNotifications() {
        // Nettoyer les notifications affichées
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${getNotificationIcon(type)}</span>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close">×</button>
        `;
        
        // Styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999',
            padding: '12px 16px',
            borderRadius: '8px',
            backgroundColor: getNotificationColor(type).bg,
            borderLeft: `4px solid ${getNotificationColor(type).border}`,
            color: getNotificationColor(type).text,
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            maxWidth: '400px',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Gestionnaire de fermeture
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        });
        
        // Fermeture automatique
        setTimeout(() => {
            if (notification.parentElement) {
                closeBtn.click();
            }
        }, 5000);
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
    
    function getNotificationColor(type) {
        const colors = {
            success: { bg: '#f0fff4', border: '#48bb78', text: '#22543d' },
            error: { bg: '#fed7d7', border: '#e53e3e', text: '#742a2a' },
            warning: { bg: '#ffeaa7', border: '#ed8936', text: '#744210' },
            info: { bg: '#ebf8ff', border: '#3182ce', text: '#2c5282' }
        };
        return colors[type] || colors.info;
    }
    
    // ==============================================
    // RACCOURCIS CLAVIER
    // ==============================================
    
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Alt + H = Accueil
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = '/';
            }
            
            // Alt + P = Profil
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = '/user/profile.php';
            }
            
            // Alt + S = Paramètres
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                window.location.href = '/user/settings.php';
            }
            
            // Alt + C = Calculateur
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                window.location.href = '/port/';
            }
            
            // Échap = Fermer notifications
            if (e.key === 'Escape') {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(notif => {
                    notif.querySelector('.notification-close').click();
                });
            }
        });
    }
    
    // ==============================================
    // GESTION RESPONSIVE
    // ==============================================
    
    function initResponsiveFeatures() {
        // Détection de la taille d'écran
        function handleResize() {
            const isMobile = window.innerWidth <= 768;
            const isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
            
            // Ajustements pour mobile
            if (isMobile) {
                // Réduire les animations sur mobile pour les performances
                document.documentElement.style.setProperty('--transition-duration', '0.2s');
            } else {
                document.documentElement.style.setProperty('--transition-duration', '0.3s');
            }
            
            // Ajustements pour tablette
            if (isTablet) {
                // Ajustements spécifiques tablette
            }
        }
        
        window.addEventListener('resize', debounce(handleResize, 250));
        handleResize(); // Appel initial
    }
    
    // ==============================================
    // UTILITAIRES
    // ==============================================
    
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    // ==============================================
    // MONITORING DES PERFORMANCES
    // ==============================================
    
    function initPerformanceMonitoring() {
        // Mesurer le temps de chargement
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`📊 Dashboard chargé en ${Math.round(loadTime)}ms`);
            
            // En production, envoyer ces métriques au serveur
            if (loadTime > 3000) {
                console.warn('⚠️ Temps de chargement élevé détecté');
            }
        });
        
        // Surveiller les erreurs JavaScript
        window.addEventListener('error', function(e) {
            console.error('❌ Erreur JavaScript:', e.error);
            // En production, reporter l'erreur
        });
    }
    
    // ==============================================
    // INITIALISATION
    // ==============================================
    
    // Initialiser tous les modules
    initAnimations();
    initActionCards();
    initStatCounters();
    initRealTimeUpdates();
    initNotifications();
    initKeyboardShortcuts();
    initResponsiveFeatures();
    initPerformanceMonitoring();
    
    // Message de confirmation du chargement
    console.log('✅ Dashboard utilisateur entièrement initialisé');
    
    // Notification de bienvenue (optionnelle)
    setTimeout(() => {
        const user = document.querySelector('.user-info h1')?.textContent || 'Utilisateur';
        // showNotification(`Bienvenue ${user} !`, 'success');
    }, 1000);
});

// ==============================================
// API PUBLIQUE
// ==============================================

// Fonctions exposées globalement
window.UserDashboard = {
    showNotification: function(message, type) {
        showNotification(message, type);
    },
    
    refreshStats: function() {
        // Rafraîchir les statistiques
        console.log('🔄 Rafraîchissement des statistiques...');
        // En production, faire un appel AJAX
    },
    
    navigateTo: function(url) {
        window.location.href = url;
    }
};

console.log('👤 Module UserDashboard prêt');

// ========================================
// 🔄 AJOUTS POUR VERSION COMPLÈTE
// Ajouter après le code existant
// ========================================

// Variables globales
let dashboardInitialized = false;

// Initialisation enrichie
document.addEventListener('DOMContentLoaded', function() {
    if (dashboardInitialized) return; // Éviter double init
    
    console.log('🚀 Dashboard User v2 - Initialisation...');
    
    // Fonctionnalités existantes préservées
    initializeExistingFeatures();
    
    // Nouvelles fonctionnalités
    initializeModuleCards();
    initializeActivityTimeline();
    initializeUserStatus();
    initializePortalStats();
    initializeQuickActions();
    
    dashboardInitialized = true;
    console.log('✅ Dashboard User complet initialisé');
});

// ========================================
// 🃏 CARTES MODULES INTERACTIVES
// ========================================
function initializeModuleCards() {
    const moduleCards = document.querySelectorAll('.module-card');
    
    moduleCards.forEach(card => {
        const moduleId = card.dataset.module;
        
        // Animation au survol
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('restricted')) {
                this.style.transform = 'translateY(-4px) scale(1.02)';
                this.style.boxShadow = '0 12px 24px rgba(0,0,0,0.15)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Click sur module restreint
        if (card.classList.contains('restricted')) {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                showRestrictedModuleMessage(moduleId);
            });
        }
        
        // Tracking des clics
        const moduleLink = card.querySelector('.module-link');
        if (moduleLink) {
            moduleLink.addEventListener('click', function() {
                trackModuleAccess(moduleId);
            });
        }
    });
}

function showRestrictedModuleMessage(moduleId) {
    // Notification élégante sans alert()
    const notification = document.createElement('div');
    notification.className = 'notification notification-warning';
    notification.innerHTML = `
        <span class="notification-icon">🔒</span>
        <span>Module "${moduleId}" : Permissions insuffisantes</span>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto-fermeture
    setTimeout(() => {
        removeNotification(notification);
    }, 4000);
    
    // Fermeture manuelle
    notification.querySelector('.notification-close').onclick = () => {
        removeNotification(notification);
    };
}

function removeNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// ========================================
// 📊 STATISTIQUES PORTAIL ANIMÉES
// ========================================
function initializePortalStats() {
    const statCards = document.querySelectorAll('.portal-stats .stat-card');
    
    // Observer pour animation au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStatCard(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    
    statCards.forEach(card => {
        observer.observe(card);
        
        // Hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

function animateStatCard(card) {
    const numberElement = card.querySelector('.stat-content h3');
    if (!numberElement) return;
    
    const finalValue = parseInt(numberElement.textContent) || 0;
    const duration = 1500;
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const currentValue = Math.floor(finalValue * easeOut);
        
        numberElement.textContent = currentValue;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        } else {
            numberElement.textContent = finalValue;
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// ========================================
// 📋 TIMELINE ACTIVITÉ
// ========================================
function initializeActivityTimeline() {
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach((item, index) => {
        // Animation d'entrée échelonnée
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease-out';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 100);
        
        // Interaction hover
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
            const typeIndicator = this.querySelector('.activity-type');
            if (typeIndicator) {
                typeIndicator.style.transform = 'translateY(-50%) scale(1.5)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            const typeIndicator = this.querySelector('.activity-type');
            if (typeIndicator) {
                typeIndicator.style.transform = 'translateY(-50%) scale(1)';
            }
        });
    });
}

// ========================================
// 👤 STATUT UTILISATEUR
// ========================================
function initializeUserStatus() {
    const statusIndicator = document.querySelector('.status-indicator.online');
    if (statusIndicator) {
        // Animation pulse
        statusIndicator.style.animation = 'pulse-online 2s infinite';
        
        // Simulation changement de statut (pour démo)
        setInterval(() => {
            updateUserActivity();
        }, 30000); // Toutes les 30s
    }
    
    // Mise à jour de l'heure de dernière activité
    updateLastActivity();
    setInterval(updateLastActivity, 60000); // Toutes les minutes
}

function updateUserActivity() {
    // Simulation d'activité - dans un vrai système, 
    // ceci serait géré côté serveur
    const lastActivity = document.querySelector('.user-meta span:nth-child(1)');
    if (lastActivity) {
        const now = new Date();
        lastActivity.textContent = `📅 Dernière activité: ${now.toLocaleTimeString('fr-FR')}`;
    }
}

function updateLastActivity() {
    const sessionTime = document.querySelector('.user-meta span:nth-child(2)');
    if (sessionTime) {
        const sessionStart = sessionStorage.getItem('sessionStart') || Date.now();
        const elapsed = Math.floor((Date.now() - sessionStart) / 1000 / 60);
        sessionTime.textContent = `⏱️ Session: ${elapsed}min`;
    }
}

// ========================================
// ⚡ ACTIONS RAPIDES
// ========================================
function initializeQuickActions() {
    const quickBtns = document.querySelectorAll('.quick-btn');
    
    quickBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.25)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.backgroundColor = '';
        });
        
        // Confirmation pour déconnexion
        if (btn.classList.contains('danger')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                confirmLogout(this.href);
            });
        }
    });
}

function confirmLogout(logoutUrl) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-confirm';
    notification.innerHTML = `
        <span class="notification-icon">🔓</span>
        <span>Confirmer la déconnexion ?</span>
        <div class="notification-actions">
            <button class="btn-confirm" onclick="window.location='${logoutUrl}'">Oui</button>
            <button class="btn-cancel">Annuler</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    notification.querySelector('.btn-cancel').onclick = () => {
        removeNotification(notification);
    };
}

// ========================================
// 📊 TRACKING ET ANALYTICS
// ========================================
function trackModuleAccess(moduleId) {
    console.log(`📊 Module accédé: ${moduleId}`);
    
    // Dans un vrai système, envoyer à analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'module_access', {
            'module_id': moduleId,
            'user_role': getUserRole()
        });
    }
    
    // Stocker localement pour statistiques
    const accessLog = JSON.parse(localStorage.getItem('moduleAccess') || '{}');
    accessLog[moduleId] = (accessLog[moduleId] || 0) + 1;
    localStorage.setItem('moduleAccess', JSON.stringify(accessLog));
}

function getUserRole() {
    const roleBadge = document.querySelector('.role-badge');
    return roleBadge ? roleBadge.textContent.toLowerCase() : 'user';
}

// ========================================
// 🎨 FONCTIONS UTILITAIRES
// ========================================
function initializeExistingFeatures() {
    // Préserver les fonctionnalités existantes
    const existingCards = document.querySelectorAll('.action-card');
    existingCards.forEach(card => {
        if (!card.hasAttribute('data-enhanced')) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
            
            card.setAttribute('data-enhanced', 'true');
        }
    });
}

// Gestion erreurs globale
window.addEventListener('error', function(e) {
    console.error('Erreur Dashboard User:', e.error);
});

// Performance monitoring
window.addEventListener('load', function() {
    const loadTime = performance.now();
    console.log(`🚀 Dashboard chargé en ${Math.round(loadTime)}ms`);
    
    // Envoyer métrique si analytics disponible
    if (typeof gtag !== 'undefined') {
        gtag('event', 'timing_complete', {
            'name': 'dashboard_load',
            'value': Math.round(loadTime)
        });
    }
});

// Session storage init
if (!sessionStorage.getItem('sessionStart')) {
    sessionStorage.setItem('sessionStart', Date.now());
}
