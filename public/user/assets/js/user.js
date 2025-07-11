/**
 * Titre: JavaScript pour dashboard utilisateur
 * Chemin: /public/user/assets/js/user.js
 * Version: 0.5 beta + build auto
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('👤 Dashboard utilisateur JavaScript initialisé');
    
    // ==============================================
    // ANIMATIONS D'ENTRÉE
    // ==============================================
    
    function initAnimations() {
        const animatedElements = document.querySelectorAll(
            '.action-card, .stat-card, .module-item, .activity-item, .security-item, .link-card'
        );
        
        // Observer pour animations au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 50);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        animatedElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.5s ease';
            observer.observe(element);
        });
    }
    
    // ==============================================
    // INTERACTIONS CARTES D'ACTION
    // ==============================================
    
    function initActionCards() {
        const actionCards = document.querySelectorAll('.action-card');
        
        actionCards.forEach(card => {
            // Effet hover avec parallax léger
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
            
            // Effet de clic
            card.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(-2px) scale(0.98)';
            });
            
            card.addEventListener('mouseup', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
        });
    }
    
    // ==============================================
    // STATISTIQUES ANIMÉES
    // ==============================================
    
    function initStatCounters() {
        const statValues = document.querySelectorAll('.stat-value');
        
        statValues.forEach(stat => {
            const finalValue = stat.textContent;
            
            // Si c'est un nombre, animer le compteur
            if (!isNaN(finalValue) && finalValue !== '') {
                animateCounter(stat, 0, parseInt(finalValue), 1000);
            }
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
