/**
 * Titre: JavaScript pour le module utilisateur
 * Chemin: /public/user/assets/js/user.js
 * Version: 1.0
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
                        showNotification('Préférences sauvegardées avec succès', 'success');
                    } else {
                        showNotification('Erreur lors de la sauvegarde des préférences', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur réseau', 'error');
                });
        });
    }

    // ==============================================
    // ANIMATIONS ET INTERACTIONS
    // ==============================================
    function initAnimations() {
        const animatedElements = document.querySelectorAll('.module-card, .stat-card, .activity-item');
        
        if (!animatedElements.length) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animatedElements.forEach((element, index) => {
            // Configuration initiale
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = `all 0.5s ease ${index * 0.05}s`;
            
            // Observer l'élément
            observer.observe(element);
        });
    }
    
    function initModuleCards() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            // Animation hover
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('restricted')) {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
            
            // Traitement des modules restreints
            if (card.classList.contains('restricted')) {
                const moduleLink = card.querySelector('.module-link');
                if (moduleLink) {
                    moduleLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        showNotification('Accès restreint - Permissions insuffisantes', 'warning');
                    });
                }
            }
            
            // Tracking des clics sur modules accessibles
            const moduleId = card.dataset.module;
            if (moduleId && !card.classList.contains('restricted')) {
                const moduleLink = card.querySelector('.module-link');
                if (moduleLink) {
                    moduleLink.addEventListener('click', function() {
                        trackModuleAccess(moduleId);
                    });
                }
            }
        });
    }
    
    function initStatCounters() {
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach(card => {
            const statValue = card.querySelector('.stat-content h3');
            if (!statValue) return;
            
            const finalValue = parseInt(statValue.textContent);
            if (isNaN(finalValue)) return;
            
            // Animation du compteur
            animateCounter(statValue, 0, finalValue, 1500);
            
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
    // GESTION DE LA TIMELINE D'ACTIVITÉ
    // ==============================================
    function initActivityTimeline() {
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
                    typeIndicator.style.transform = 'translateY(-50%) scale(1.1)';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                const typeIndicator = this.querySelector('.activity-type');
                if (typeIndicator) {
                    typeIndicator.style.transform = '';
                }
            });
        });
    }
    
    // ==============================================
    // INDICATEUR DE STATUT UTILISATEUR
    // ==============================================
    function initUserStatus() {
        const statusIndicator = document.querySelector('.status-indicator.online');
        if (statusIndicator) {
            // Animation pulse
            statusIndicator.style.animation = 'pulse-online 2s infinite';
            
            // Mise à jour de l'heure de dernière activité
            updateLastActivity();
            setInterval(updateLastActivity, 60000); // Toutes les minutes
        }
    }
    
    function updateLastActivity() {
        const sessionTime = document.querySelector('.user-meta span:nth-child(2)');
        if (sessionTime) {
            // Récupérer le temps de début de session ou utiliser le timestamp actuel
            const sessionStart = sessionStorage.getItem('sessionStart') || Date.now();
            if (!sessionStorage.getItem('sessionStart')) {
                sessionStorage.setItem('sessionStart', Date.now());
            }
            
            const elapsed = Math.floor((Date.now() - sessionStart) / 1000 / 60);
            sessionTime.textContent = `⏱️ Session: ${elapsed}min`;
        }
    }
    
    // ==============================================
    // ACTIONS RAPIDES
    // ==============================================
    function initQuickActions() {
        const quickBtns = document.querySelectorAll('.quick-btn');
        
        quickBtns.forEach(btn => {
            // Animation au hover
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
                    
                    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                        window.location.href = this.getAttribute('href');
                    }
                });
            }
        });
    }
    
    // ==============================================
    // NOTIFICATIONS
    // ==============================================
    function showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${getNotificationIcon(type)}</span>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close">×</button>
        `;
        
        // Styles inline pour éviter dépendance CSS
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
            justifyContent: 'space-between',
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
        }, 10);
        
        // Gestionnaire de fermeture
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
        
        // Fermeture automatique
        setTimeout(() => {
            if (notification.parentNode) {
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
            warning: { bg: '#fef3c7', border: '#ed8936', text: '#744210' },
            info: { bg: '#ebf8ff', border: '#3182ce', text: '#2c5282' }
        };
        return colors[type] || colors.info;
    }
    
    // ==============================================
    // RACCOURCIS CLAVIER
    // ==============================================
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ne pas activer les raccourcis dans les champs de saisie
            if (e.target.matches('input, textarea, select, [contenteditable]')) {
                return;
            }
            
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
            
            // Échap = Fermer notifications
            if (e.key === 'Escape') {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(notif => {
                    const closeBtn = notif.querySelector('.notification-close');
                    if (closeBtn) closeBtn.click();
                });
            }
        });
    }
    
    // ==============================================
    // TRACKING ANALYTIQUE
    // ==============================================
    function trackModuleAccess(moduleId) {
        console.log(`📊 Module accédé: ${moduleId}`);
        
        // Stocker localement pour statistiques
        const accessLog = JSON.parse(localStorage.getItem('moduleAccess') || '{}');
        accessLog[moduleId] = (accessLog[moduleId] || 0) + 1;
        localStorage.setItem('moduleAccess', JSON.stringify(accessLog));
        
        // Dans un environnement réel, envoi à un service d'analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'module_access', {
                'module_id': moduleId,
                'user_role': getUserRole()
            });
        }
    }
    
    function getUserRole() {
        const roleBadge = document.querySelector('.role-badge');
        return roleBadge ? roleBadge.textContent.toLowerCase() : 'user';
    }
    
    // ==============================================
    // MONITORING PERFORMANCE
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
    // UTILITAIRES
    // ==============================================
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // ==============================================
    // INITIALISATION
    // ==============================================
    try {
        // Fonctionnalités de base
        initAnimations();
        initModuleCards();
        
        // Fonctionnalités avancées (si éléments présents)
        if (document.querySelector('.stat-card')) {
            initStatCounters();
        }
        
        if (document.querySelector('.activity-item')) {
            initActivityTimeline();
        }
        
        if (document.querySelector('.status-indicator')) {
            initUserStatus();
        }
        
        if (document.querySelector('.quick-btn')) {
            initQuickActions();
        }
        
        // Raccourcis clavier
        initKeyboardShortcuts();
        
        // Monitoring performances
        initPerformanceMonitoring();
        
        console.log('✅ Dashboard utilisateur entièrement initialisé');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation du dashboard:', error);
    }
});

// ==============================================
// API PUBLIQUE
// ==============================================
window.UserDashboard = {
    showNotification: function(message, type = 'info') {
        const event = new CustomEvent('showUserNotification', {
            detail: { message, type }
        });
        document.dispatchEvent(event);
    },
    
    refreshData: function() {
        console.log('🔄 Rafraîchissement des données utilisateur...');
        location.reload();
    },
    
    trackModuleAccess: function(moduleId) {
        console.log(`📊 Module accédé: ${moduleId}`);
        // Dans un vrai système, envoyer à analytics
    },
    
    navigateTo: function(url) {
        window.location.href = url;
    }
};

// Écouteur d'événement pour les notifications
document.addEventListener('showUserNotification', function(e) {
    if (typeof showNotification === 'function') {
        showNotification(e.detail.message, e.detail.type);
    }
});

// Session storage init
if (!sessionStorage.getItem('sessionStart')) {
    sessionStorage.setItem('sessionStart', Date.now());
}
    
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
