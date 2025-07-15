/**
 * Titre: Extension JavaScript Dashboard Home pour PortalManager existant
 * Chemin: /assets/js/home.js
 * Version: 0.5 beta + build auto
 * Usage: Extension du PortalManager pour fonctionnalités dashboard
 */

(function() {
    'use strict';

    // ========================================
    // 🎯 EXTENSION DU PORTALMANAGER EXISTANT
    // ========================================
    
    // Attendre que PortalManager soit disponible
    function waitForPortalManager(callback) {
        if (window.PortalManager && window.PortalManager.initialized) {
            callback();
        } else if (window.PortalManager) {
            window.PortalManager.on('ready', callback);
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => waitForPortalManager(callback), 100);
            });
        }
    }

    // Configuration spécifique au dashboard home
    const HOME_CONFIG = {
        animations: {
            enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            duration: 600,
            stagger: 100
        },
        stats: {
            refreshInterval: 30000,
            animationDuration: 1500
        },
        modules: {
            preloadEnabled: true,
            hoverDelay: 200,
            clickDelay: 150
        }
    };

    let homeInitialized = false;
    let statsRefreshTimer = null;
    let moduleObserver = null;

    // ========================================
    // 🚀 EXTENSION PRINCIPALE
    // ========================================
    
    function extendPortalManager() {
        if (!window.PortalManager || homeInitialized) return;

        console.log('🏠 Extension Dashboard Home du PortalManager');

        // Ajouter méthodes spécifiques au dashboard
        window.PortalManager.home = {
            init: initializeDashboardHome,
            refreshStats: refreshDashboardStats,
            animateModules: animateModuleCards,
            preloadModule: preloadModulePage,
            showModuleTooltip: showModuleTooltip,
            trackModuleInteraction: trackModuleInteraction
        };

        // Étendre les capacités existantes
        extendExistingMethods();
        
        // Initialiser si le DOM est prêt
        if (document.readyState === 'complete') {
            initializeDashboardHome();
        } else {
            document.addEventListener('DOMContentLoaded', initializeDashboardHome);
        }

        homeInitialized = true;
    }

    function extendExistingMethods() {
        const originalRegisterModule = window.PortalManager.registerModule;
        
        // Enrichir l'enregistrement des modules
        window.PortalManager.registerModule = function(moduleId, config) {
            // Appeler la méthode originale
            const result = originalRegisterModule.call(this, moduleId, config);
            
            // Ajouter fonctionnalités dashboard
            if (config.element && config.element.classList.contains('module-card')) {
                enhanceModuleCard(config.element, moduleId, config);
            }
            
            return result;
        };

        // Étendre le système de notifications
        const originalShowToast = window.PortalManager.showToast;
        window.PortalManager.showToast = function(type, title, message, options = {}) {
            // Options spécifiques dashboard
            const dashboardOptions = {
                duration: options.duration || 5000,
                position: options.position || 'top-right',
                ...options
            };
            
            return originalShowToast.call(this, type, title, message, dashboardOptions);
        };
    }

    // ========================================
    // 🏠 INITIALISATION DASHBOARD HOME
    // ========================================
    
    function initializeDashboardHome() {
        if (!document.querySelector('.dashboard-container')) {
            console.log('🏠 Dashboard container non trouvé, skip initialisation home');
            return;
        }

        console.log('🏠 Initialisation Dashboard Home');

        try {
            setupWelcomeSection();
            initializeStatsCards();
            enhanceModuleCards();
            setupRoleInteractions();
            setupKeyboardNavigation();
            startStatsRefresh();
            setupIntersectionObserver();
            
            // Déclencher animations d'entrée
            if (HOME_CONFIG.animations.enabled) {
                setTimeout(animateModuleCards, 300);
            }
            
            console.log('✅ Dashboard Home initialisé');
            
            // Émettre événement
            if (window.PortalManager.emit) {
                window.PortalManager.emit('home:ready', { 
                    timestamp: Date.now(),
                    modules: document.querySelectorAll('.module-card').length
                });
            }
            
        } catch (error) {
            console.error('❌ Erreur initialisation Dashboard Home:', error);
            if (window.PortalManager.log) {
                window.PortalManager.log('error', 'Erreur Dashboard Home', error);
            }
        }
    }

    // ========================================
    // 👋 SECTION BIENVENUE
    // ========================================
    
    function setupWelcomeSection() {
        const welcomeSection = document.querySelector('.welcome-section');
        if (!welcomeSection) return;

        // Animation de typing pour le titre si souhaité
        const title = welcomeSection.querySelector('h1');
        if (title && HOME_CONFIG.animations.enabled) {
            animateWelcomeTitle(title);
        }

        // Mise à jour temps réel de l'heure
        const timeElement = welcomeSection.querySelector('.welcome-meta span:last-child');
        if (timeElement) {
            updateWelcomeTime(timeElement);
            setInterval(() => updateWelcomeTime(timeElement), 60000); // Chaque minute
        }
    }

    function animateWelcomeTitle(titleElement) {
        const text = titleElement.textContent;
        titleElement.style.opacity = '0';
        
        setTimeout(() => {
            titleElement.style.opacity = '1';
            titleElement.style.animation = 'fadeInUp 0.8s ease-out';
        }, 200);
    }

    function updateWelcomeTime(timeElement) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        timeElement.innerHTML = `⏰ Connecté à : ${timeString}`;
    }

    // ========================================
    // 📊 CARTES STATISTIQUES
    // ========================================
    
    function initializeStatsCards() {
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach((card, index) => {
            // Animation d'entrée retardée
            if (HOME_CONFIG.animations.enabled) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * HOME_CONFIG.animations.stagger);
            }

            // Animation des compteurs
            const statNumber = card.querySelector('.stat-number');
            if (statNumber && HOME_CONFIG.animations.enabled) {
                setTimeout(() => {
                    animateStatCounter(statNumber);
                }, index * HOME_CONFIG.animations.stagger + 300);
            }

            // Interaction hover
            card.addEventListener('mouseenter', () => handleStatHover(card));
            card.addEventListener('click', () => handleStatClick(card));
        });
    }

    function animateStatCounter(element) {
        const finalValue = element.textContent.trim();
        const isNumeric = /^\d+$/.test(finalValue);
        
        if (!isNumeric) return;
        
        const targetNumber = parseInt(finalValue);
        const duration = HOME_CONFIG.stats.animationDuration;
        const startTime = performance.now();
        
        element.textContent = '0';
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Fonction d'easing out quadratic
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.floor(targetNumber * easeOut);
            
            element.textContent = currentValue.toLocaleString('fr-FR');
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = finalValue;
            }
        }
        
        requestAnimationFrame(updateCounter);
    }

    function handleStatHover(card) {
        const number = card.querySelector('.stat-number');
        if (number && HOME_CONFIG.animations.enabled) {
            number.style.animation = 'pulse 2s infinite';
        }
    }

    function handleStatClick(card) {
        const label = card.querySelector('.stat-label').textContent;
        
        if (window.PortalManager.showToast) {
            window.PortalManager.showToast('info', 'Statistique', 
                `Détails pour "${label}" : fonctionnalité à venir`);
        }
        
        // Animation de feedback
        if (HOME_CONFIG.animations.enabled) {
            card.style.transform = 'scale(1.05)';
            setTimeout(() => {
                card.style.transform = '';
            }, 200);
        }
    }

    // ========================================
    // 🗂️ CARTES MODULES
    // ========================================
    
    function enhanceModuleCards() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach((card, index) => {
            const moduleData = extractModuleData(card);
            
            // Animation d'entrée
            if (HOME_CONFIG.animations.enabled) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * HOME_CONFIG.animations.stagger + 600);
            }

            enhanceModuleCard(card, `module_${index}`, moduleData);
        });
    }

    function enhanceModuleCard(card, moduleId, moduleData) {
        // Gestion des interactions
        setupModuleInteractions(card, moduleData);
        
        // Ajout d'indicateurs de statut
        addModuleStatusIndicator(card, moduleData);
        
        // Configuration accessibilité
        setupModuleAccessibility(card, moduleData);
        
        // Enregistrer dans PortalManager si disponible
        if (window.PortalManager.registerModule) {
            window.PortalManager.registerModule(moduleId, {
                ...moduleData,
                element: card,
                enhanced: true
            });
        }
    }

    function setupModuleInteractions(card, moduleData) {
        let hoverTimeout;
        
        // Gestion hover
        card.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
            handleModuleHover(card, moduleData);
        });
        
        card.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                handleModuleLeave(card);
            }, 100);
        });
        
        // Gestion click
        card.addEventListener('click', (e) => {
            handleModuleClick(e, card, moduleData);
        });
        
        // Gestion focus/blur pour accessibilité
        card.addEventListener('focus', () => {
            card.style.outline = '2px solid var(--primary-blue)';
            card.style.outlineOffset = '2px';
        });
        
        card.addEventListener('blur', () => {
            card.style.outline = 'none';
        });
    }

    function handleModuleHover(card, moduleData) {
        const icon = card.querySelector('.module-icon');
        
        // Animation icône
        if (icon && HOME_CONFIG.animations.enabled) {
            icon.style.transform = 'scale(1.1) rotate(5deg)';
            icon.style.transition = 'transform 0.3s ease';
        }
        
        // Préchargement si module accessible
        if (moduleData.isAccessible && moduleData.href && HOME_CONFIG.modules.preloadEnabled) {
            preloadModulePage(moduleData.href);
        }
        
        // Tooltip informatif
        if (!card.classList.contains('no-access')) {
            showModuleTooltip(card, moduleData);
        } else {
            showAccessDeniedTooltip(card);
        }
        
        // Analytics
        trackModuleInteraction('hover', moduleData);
    }

    function handleModuleLeave(card) {
        const icon = card.querySelector('.module-icon');
        
        if (icon) {
            icon.style.transform = 'scale(1) rotate(0deg)';
        }
        
        // Masquer tooltips
        hideAllTooltips();
    }

    function handleModuleClick(event, card, moduleData) {
        // Vérifier l'accès
        if (card.classList.contains('no-access')) {
            event.preventDefault();
            showAccessDeniedDialog(moduleData);
            return;
        }
        
        // Animation de clic
        if (HOME_CONFIG.animations.enabled) {
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = '';
            }, HOME_CONFIG.modules.clickDelay);
        }
        
        // Notification
        if (window.PortalManager.showToast && moduleData.name) {
            window.PortalManager.showToast('info', 'Navigation', 
                `Chargement de ${moduleData.name}...`);
        }
        
        // Analytics
        trackModuleInteraction('click', moduleData);
    }

    function extractModuleData(card) {
        return {
            name: card.querySelector('.module-name')?.textContent?.trim() || 'Module',
            description: card.querySelector('.module-description')?.textContent?.trim() || '',
            status: card.querySelector('.module-status-badge')?.textContent?.trim() || 'unknown',
            href: card.querySelector('a')?.href || card.dataset.href || '#',
            icon: card.querySelector('.module-icon')?.textContent?.trim() || '📦',
            isAccessible: !card.classList.contains('no-access'),
            category: card.closest('.category-section')?.querySelector('.category-title')?.textContent?.trim() || 'Général'
        };
    }

    // ========================================
    // 🔧 FONCTIONS UTILITAIRES
    // ========================================
    
    function addModuleStatusIndicator(card, moduleData) {
        const header = card.querySelector('.module-header');
        if (!header) return;
        
        const indicator = document.createElement('div');
        indicator.className = 'module-status-indicator';
        indicator.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: ${moduleData.isAccessible ? '#22c55e' : '#ef4444'};
            box-shadow: 0 0 0 2px white, 0 0 0 3px rgba(0,0,0,0.1);
            z-index: 10;
        `;
        
        if (header.style.position !== 'relative') {
            header.style.position = 'relative';
        }
        
        header.appendChild(indicator);
    }

    function setupModuleAccessibility(card, moduleData) {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 
            `${moduleData.name} - ${moduleData.description}. ${moduleData.isAccessible ? 'Accessible' : 'Accès restreint'}`);
        
        // Navigation clavier
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    }

    function showModuleTooltip(card, moduleData) {
        const tooltip = createTooltip(
            `${moduleData.name}`,
            `${moduleData.description}\nStatut: ${moduleData.status}`
        );
        positionTooltip(tooltip, card);
    }

    function showAccessDeniedTooltip(card) {
        const tooltip = createTooltip(
            '🔒 Accès restreint',
            'Contactez un administrateur pour obtenir l\'accès à ce module'
        );
        positionTooltip(tooltip, card);
    }

    function createTooltip(title, content) {
        const tooltip = document.createElement('div');
        tooltip.className = 'home-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-title">${title}</div>
            <div class="tooltip-content">${content}</div>
        `;
        
        tooltip.style.cssText = `
            position: absolute;
            background: var(--gray-800, #1f2937);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            max-width: 300px;
            z-index: 1000;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        `;
        
        document.body.appendChild(tooltip);
        
        // Animation d'entrée
        setTimeout(() => {
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translateY(0)';
        }, 10);
        
        return tooltip;
    }

    function positionTooltip(tooltip, element) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        let top = rect.top - tooltipRect.height - 10;
        
        // Ajustements si hors écran
        if (left < 10) left = 10;
        if (left + tooltipRect.width > window.innerWidth - 10) {
            left = window.innerWidth - tooltipRect.width - 10;
        }
        if (top < 10) {
            top = rect.bottom + 10;
        }
        
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function hideAllTooltips() {
        const tooltips = document.querySelectorAll('.home-tooltip');
        tooltips.forEach(tooltip => {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(10px)';
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, 200);
        });
    }

    function showAccessDeniedDialog(moduleData) {
        const dialog = document.createElement('div');
        dialog.className = 'access-denied-dialog';
        
        dialog.innerHTML = `
            <div class="dialog-overlay"></div>
            <div class="dialog-content">
                <div class="dialog-icon">🔒</div>
                <h3>Accès restreint</h3>
                <p>Le module "${moduleData.name}" nécessite des permissions supplémentaires.</p>
                <div class="dialog-actions">
                    <button class="btn-primary" onclick="this.closest('.access-denied-dialog').remove()">
                        Compris
                    </button>
                </div>
            </div>
        `;
        
        dialog.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        document.body.appendChild(dialog);
        
        // Animation d'entrée
        setTimeout(() => {
            dialog.querySelector('.dialog-content').style.transform = 'scale(1)';
            dialog.querySelector('.dialog-content').style.opacity = '1';
        }, 10);
    }

    // ========================================
    // 📈 ACTUALISATION DES STATS
    // ========================================
    
    function startStatsRefresh() {
        if (statsRefreshTimer) {
            clearInterval(statsRefreshTimer);
        }
        
        statsRefreshTimer = setInterval(() => {
            refreshDashboardStats();
        }, HOME_CONFIG.stats.refreshInterval);
        
        // Arrêter si la page devient cachée
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && statsRefreshTimer) {
                clearInterval(statsRefreshTimer);
            } else if (!document.hidden && !statsRefreshTimer) {
                startStatsRefresh();
            }
        });
    }

    function refreshDashboardStats() {
        // Simuler mise à jour des calculs du jour
        const calculsTodayCard = document.querySelector('.stat-card:nth-child(3) .stat-number');
        
        if (calculsTodayCard) {
            const currentValue = parseInt(calculsTodayCard.textContent) || 0;
            const increment = Math.floor(Math.random() * 3) + 1;
            const newValue = currentValue + increment;
            
            // Animation de changement
            calculsTodayCard.style.color = 'var(--color-success, #10b981)';
            calculsTodayCard.style.transform = 'scale(1.1)';
            
            setTimeout(() => {
                calculsTodayCard.textContent = newValue;
                calculsTodayCard.style.color = '';
                calculsTodayCard.style.transform = 'scale(1)';
            }, 200);
        }
    }

    // ========================================
    // ⌨️ NAVIGATION CLAVIER
    // ========================================
    
    function setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Navigation entre modules avec flèches
            if (e.target.classList.contains('module-card')) {
                handleModuleKeyboardNavigation(e);
            }
            
            // Raccourcis globaux
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'h':
                        e.preventDefault();
                        focusFirstModule();
                        break;
                    case 'r':
                        e.preventDefault();
                        refreshDashboardStats();
                        break;
                }
            }
        });
    }

    function handleModuleKeyboardNavigation(e) {
        const modules = Array.from(document.querySelectorAll('.module-card'));
        const currentIndex = modules.indexOf(e.target);
        let nextIndex = currentIndex;
        
        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                nextIndex = (currentIndex + 1) % modules.length;
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                nextIndex = (currentIndex - 1 + modules.length) % modules.length;
                break;
            case 'Home':
                nextIndex = 0;
                break;
            case 'End':
                nextIndex = modules.length - 1;
                break;
        }
        
        if (nextIndex !== currentIndex) {
            e.preventDefault();
            modules[nextIndex].focus();
            modules[nextIndex].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }

    function focusFirstModule() {
        const firstModule = document.querySelector('.module-card');
        if (firstModule) {
            firstModule.focus();
            firstModule.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }

    // ========================================
    // 👁️ INTERSECTION OBSERVER
    // ========================================
    
    function setupIntersectionObserver() {
        if (!window.IntersectionObserver) return;

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        moduleObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-viewport');
                    
                    // Animation spéciale pour les éléments animés
                    if (HOME_CONFIG.animations.enabled) {
                        animateElementInView(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        const elementsToObserve = document.querySelectorAll(
            '.module-card, .stat-card, .category-section'
        );
        
        elementsToObserve.forEach(element => {
            moduleObserver.observe(element);
        });
    }

    function animateElementInView(element) {
        if (element.classList.contains('module-card')) {
            element.style.transform = 'translateY(0) scale(1)';
            element.style.opacity = '1';
        }
    }

    // ========================================
    // 🔗 PRÉCHARGEMENT MODULES
    // ========================================
    
    function preloadModulePage(href) {
        if (!href || href === '#' || href === 'javascript:void(0)') return;
        
        // Éviter le préchargement multiple
        if (document.querySelector(`link[rel="prefetch"][href="${href}"]`)) return;
        
        // Préchargement discret
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        document.head.appendChild(link);
        
        // Nettoyer après un certain temps
        setTimeout(() => {
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
        }, 10000);
    }

    // ========================================
    // 📊 ANALYTICS ET TRACKING
    // ========================================
    
    function trackModuleInteraction(action, moduleData) {
        const eventData = {
            action: action,
            module: moduleData.name,
            category: moduleData.category,
            status: moduleData.status,
            accessible: moduleData.isAccessible,
            timestamp: Date.now()
        };
        
        // PortalManager analytics
        if (window.PortalManager.log) {
            window.PortalManager.log('info', `Module ${action}`, eventData);
        }
        
        // Google Analytics si disponible
        if (window.gtag) {
            window.gtag('event', `module_${action}`, {
                event_category: 'dashboard_interaction',
                event_label: moduleData.name,
                custom_map: {
                    module_status: moduleData.status,
                    module_accessible: moduleData.isAccessible
                }
            });
        }
        
        // Matomo si disponible
        if (window._paq) {
            window._paq.push(['trackEvent', 'Dashboard', `Module ${action}`, moduleData.name]);
        }
    }

    // ========================================
    // 👤 INTERACTIONS RÔLE
    // ========================================
    
    function setupRoleInteractions() {
        const roleInfo = document.querySelector('.role-info');
        const roleBadge = document.querySelector('.role-badge');
        
        if (roleInfo) {
            roleInfo.addEventListener('click', showRolePermissions);
        }
        
        if (roleBadge) {
            roleBadge.addEventListener('click', (e) => {
                e.stopPropagation();
                const role = roleBadge.textContent.trim().toLowerCase();
                showRoleDetails(role);
            });
        }
    }

    function showRolePermissions() {
        if (window.PortalManager.showToast) {
            window.PortalManager.showToast('info', 'Permissions utilisateur', 
                'Cliquez sur votre badge de rôle pour plus de détails');
        }
    }

    function showRoleDetails(role) {
        const roleDescriptions = {
            'user': 'Accès aux modules actifs et consultation des données',
            'admin': 'Gestion système et accès modules actifs/beta', 
            'dev': 'Accès développeur complet incluant modules en développement',
            'logistique': 'Accès spécialisé transport et logistique'
        };
        
        const description = roleDescriptions[role] || 'Permissions standard';
        
        if (window.PortalManager.showToast) {
            window.PortalManager.showToast('info', `Rôle ${role.toUpperCase()}`, description);
        }
    }

    // ========================================
    // 🎨 ANIMATIONS AVANCÉES
    // ========================================
    
    function animateModuleCards() {
        const cards = document.querySelectorAll('.module-card');
        
        cards.forEach((card, index) => {
            if (HOME_CONFIG.animations.enabled) {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
                card.style.transition = `opacity ${HOME_CONFIG.animations.duration}ms ease ${index * 50}ms, 
                                       transform ${HOME_CONFIG.animations.duration}ms ease ${index * 50}ms`;
            }
        });
    }

    // ========================================
    // 🧹 NETTOYAGE
    // ========================================
    
    function cleanup() {
        if (statsRefreshTimer) {
            clearInterval(statsRefreshTimer);
            statsRefreshTimer = null;
        }
        
        if (moduleObserver) {
            moduleObserver.disconnect();
            moduleObserver = null;
        }
        
        hideAllTooltips();
    }

    // Nettoyage automatique
    window.addEventListener('beforeunload', cleanup);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hideAllTooltips();
        }
    });

    // ========================================
    // 🚀 INITIALISATION
    // ========================================
    
    // ========================================
    // 🚀 INITIALISATION
    // ========================================
    
    // Attendre PortalManager et initialiser
    waitForPortalManager(extendPortalManager);

    // Export pour tests et debug
    window.DashboardHome = {
        init: initializeDashboardHome,
        refreshStats: refreshDashboardStats,
        animateModules: animateModuleCards,
        cleanup: cleanup,
        config: HOME_CONFIG,
        
        // Méthodes utilitaires
        showTooltip: showModuleTooltip,
        hideTooltips: hideAllTooltips,
        trackInteraction: trackModuleInteraction,
        preloadPage: preloadModulePage,
        
        // État actuel
        get initialized() { return homeInitialized; },
        get statsTimer() { return statsRefreshTimer; },
        get observer() { return moduleObserver; }
    };

    // Méthodes globales pour debug console
    if (window.console && window.console.log) {
        window.DashboardHome.help = function() {
            console.log(`
🏠 Dashboard Home API v${HOME_CONFIG.version || '1.0'}

Commandes disponibles:
- DashboardHome.init() : Réinitialiser dashboard
- DashboardHome.refreshStats() : Actualiser statistiques
- DashboardHome.animateModules() : Relancer animations
- DashboardHome.cleanup() : Nettoyer ressources
- DashboardHome.showTooltip(card, data) : Afficher tooltip
- DashboardHome.hideTooltips() : Masquer tooltips
- DashboardHome.preloadPage(url) : Précharger page

État:
- Initialisé: ${homeInitialized}
- Animations: ${HOME_CONFIG.animations.enabled}
- Timer stats: ${statsRefreshTimer ? 'Actif' : 'Inactif'}
- Observer: ${moduleObserver ? 'Actif' : 'Inactif'}

Raccourcis clavier:
- Ctrl+H : Focus premier module
- Ctrl+R : Actualiser stats
- Flèches : Navigation modules
            `);
        };
    }

})();/**
 * Titre: JavaScript Dashboard Accueil - Interactions et animations
 * Chemin: /assets/js/modules/home.js
 * Version: 0.5 beta + build auto
 * Usage: Page d'accueil dashboard
 */

(function() {
    'use strict';

    // ========================================
    // 🎯 VARIABLES ET CONFIGURATION
    // ========================================
    
    const CONFIG = {
        animations: {
            duration: 300,
            easing: 'ease-out',
            stagger: 100
        },
        stats: {
            refreshInterval: 30000, // 30 secondes
            animationDuration: 1000
        },
        modules: {
            hoverDelay: 150,
            clickDelay: 200
        },
        accessibility: {
            reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
        }
    };

    let initialized = false;
    let statsRefreshTimer = null;
    let animationObserver = null;

    // ========================================
    // 🚀 INITIALISATION PRINCIPALE
    // ========================================
    
    function init() {
        if (initialized) return;
        
        console.log('🏠 Initialisation Dashboard Home');
        
        // Vérifier que le DOM est prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        try {
            setupEventListeners();
            initializeAnimations();
            setupStatsRefresh();
            setupAccessibility();
            setupModuleInteractions();
            setupRoleInfoInteractions();
            
            initialized = true;
            console.log('✅ Dashboard Home initialisé');
            
            // Déclencher événement personnalisé
            document.dispatchEvent(new CustomEvent('dashboardHomeReady', {
                detail: { timestamp: Date.now() }
            }));
            
        } catch (error) {
            console.error('❌ Erreur initialisation Dashboard Home:', error);
        }
    }

    // ========================================
    // 🎬 GESTIONNAIRE D'ÉVÉNEMENTS
    // ========================================
    
    function setupEventListeners() {
        // Interaction cartes modules
        const moduleCards = document.querySelectorAll('.module-card');
        moduleCards.forEach(card => {
            card.addEventListener('mouseenter', handleModuleHover);
            card.addEventListener('mouseleave', handleModuleLeave);
            card.addEventListener('click', handleModuleClick);
            card.addEventListener('keydown', handleModuleKeydown);
        });

        // Interaction statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('click', handleStatClick);
            card.addEventListener('mouseenter', handleStatHover);
        });

        // Interaction badges rôle
        const roleBadges = document.querySelectorAll('.role-badge');
        roleBadges.forEach(badge => {
            badge.addEventListener('click', handleRoleClick);
        });

        // Gestion responsive
        window.addEventListener('resize', debounce(handleResize, 250));
        
        // Gestion changement préférence animation
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        mediaQuery.addEventListener('change', handleMotionPreferenceChange);
    }

    // ========================================
    // ✨ ANIMATIONS ET EFFETS VISUELS
    // ========================================
    
    function initializeAnimations() {
        if (CONFIG.accessibility.reducedMotion) {
            console.log('🔇 Animations réduites activées');
            return;
        }

        // Animation d'entrée des éléments
        setupIntersectionObserver();
        
        // Animation des compteurs
        animateStatCounters();
        
        // Animation de révélation progressive
        staggerAnimateElements();
    }

    function setupIntersectionObserver() {
        if (!window.IntersectionObserver) return;

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Animation spéciale pour les modules
                    if (entry.target.classList.contains('module-card')) {
                        setTimeout(() => {
                            entry.target.style.transform = 'translateY(0)';
                            entry.target.style.opacity = '1';
                        }, parseInt(entry.target.dataset.delay) || 0);
                    }
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        const elementsToAnimate = document.querySelectorAll(
            '.stat-card, .module-card, .category-section, .alert'
        );
        
        elementsToAnimate.forEach((el, index) => {
            el.dataset.delay = index * CONFIG.animations.stagger;
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `all ${CONFIG.animations.duration}ms ${CONFIG.animations.easing}`;
            animationObserver.observe(el);
        });
    }

    function animateStatCounters() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach(element => {
            const finalValue = element.textContent.trim();
            const isNumeric = /^\d+$/.test(finalValue);
            
            if (!isNumeric) return;
            
            const targetNumber = parseInt(finalValue);
            const duration = CONFIG.stats.animationDuration;
            const startTime = performance.now();
            
            element.textContent = '0';
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Fonction d'easing out
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentValue = Math.floor(targetNumber * easeOut);
                
                element.textContent = currentValue.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = finalValue;
                }
            }
            
            // Démarrer l'animation après un délai
            setTimeout(() => {
                requestAnimationFrame(updateCounter);
            }, 500);
        });
    }

    function staggerAnimateElements() {
        const elements = document.querySelectorAll('.modules-grid .module-card');
        
        elements.forEach((element, index) => {
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0) scale(1)';
            }, index * 100);
        });
    }

    // ========================================
    // 🔄 ACTUALISATION DES STATISTIQUES
    // ========================================
    
    function setupStatsRefresh() {
        // Actualisation périodique des stats (simulation)
        statsRefreshTimer = setInterval(() => {
            updateRandomStats();
        }, CONFIG.stats.refreshInterval);
        
        // Arrêter le timer si la page n'est plus visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(statsRefreshTimer);
            } else {
                setupStatsRefresh();
            }
        });
    }

    function updateRandomStats() {
        const calculsTodayElement = document.querySelector('.stat-card:nth-child(3) .stat-number');
        
        if (calculsTodayElement) {
            const currentValue = parseInt(calculsTodayElement.textContent);
            const newValue = currentValue + Math.floor(Math.random() * 3);
            
            // Animation de changement
            calculsTodayElement.style.transform = 'scale(1.1)';
            calculsTodayElement.style.color = 'var(--color-success)';
            
            setTimeout(() => {
                calculsTodayElement.textContent = newValue;
                calculsTodayElement.style.transform = 'scale(1)';
                calculsTodayElement.style.color = 'var(--color-primary)';
            }, 150);
        }
    }

    // ========================================
    // 🎮 INTERACTIONS MODULES
    // ========================================
    
    function setupModuleInteractions() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            // Effet de survol amélioré
            card.addEventListener('mouseenter', function() {
                if (this.classList.contains('no-access')) {
                    showAccessTooltip(this);
                } else {
                    showModulePreview(this);
                }
            });
            
            // Gestion du focus clavier
            card.addEventListener('focus', function() {
                this.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            });
        });
    }

    function handleModuleHover(event) {
        const card = event.currentTarget;
        const icon = card.querySelector('.module-icon');
        
        if (!CONFIG.accessibility.reducedMotion && icon) {
            icon.style.transform = 'scale(1.1) rotate(5deg)';
            icon.style.transition = 'transform 0.3s ease';
        }
        
        // Préchargement de la page si module accessible
        if (!card.classList.contains('no-access')) {
            const moduleLink = card.querySelector('a[href]');
            if (moduleLink) {
                preloadModulePage(moduleLink.href);
            }
        }
    }

    function handleModuleLeave(event) {
        const card = event.currentTarget;
        const icon = card.querySelector('.module-icon');
        
        if (icon) {
            icon.style.transform = 'scale(1) rotate(0deg)';
        }
        
        hideTooltips();
    }

    function handleModuleClick(event) {
        const card = event.currentTarget;
        
        if (card.classList.contains('no-access')) {
            event.preventDefault();
            showAccessDeniedMessage(card);
            return;
        }
        
        // Animation de clic
        if (!CONFIG.accessibility.reducedMotion) {
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = '';
            }, CONFIG.modules.clickDelay);
        }
        
        // Analytics
        trackModuleClick(card.dataset.module);
    }

    function handleModuleKeydown(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            event.currentTarget.click();
        }
    }

    // ========================================
    // 📊 INTERACTIONS STATISTIQUES
    // ========================================
    
    function handleStatClick(event) {
        const card = event.currentTarget;
        const label = card.querySelector('.stat-label').textContent;
        
        showStatDetails(label, card);
        
        // Animation de feedback
        if (!CONFIG.accessibility.reducedMotion) {
            card.style.transform = 'scale(1.05)';
            setTimeout(() => {
                card.style.transform = '';
            }, 200);
        }
    }

    function handleStatHover(event) {
        const card = event.currentTarget;
        const number = card.querySelector('.stat-number');
        
        if (number && !CONFIG.accessibility.reducedMotion) {
            number.style.animation = 'pulse 2s infinite';
        }
    }

    // ========================================
    // 👤 INTERACTIONS RÔLE
    // ========================================
    
    function setupRoleInfoInteractions() {
        const roleInfo = document.querySelector('.role-info');
        
        if (roleInfo) {
            roleInfo.addEventListener('click', () => {
                showRolePermissions();
            });
        }
    }

    function handleRoleClick(event) {
        event.stopPropagation();
        const role = event.currentTarget.textContent.trim().toLowerCase();
        showRoleDetails(role);
    }

    // ========================================
    // 🔧 FONCTIONS UTILITAIRES
    // ========================================
    
    function showAccessTooltip(card) {
        const tooltip = createTooltip('🔒 Accès restreint - Contactez un administrateur');
        positionTooltip(tooltip, card);
    }

    function showModulePreview(card) {
        const moduleName = card.querySelector('.module-name').textContent;
        const description = card.querySelector('.module-description').textContent;
        
        const preview = createTooltip(`📋 ${moduleName}: ${description.substring(0, 100)}...`);
        positionTooltip(preview, card);
    }

    function createTooltip(text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--color-gray-800);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            z-index: 1000;
            max-width: 300px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        `;
        
        document.body.appendChild(tooltip);
        
        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);
        
        return tooltip;
    }

    function positionTooltip(tooltip, element) {
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    }

    function hideTooltips() {
        const tooltips = document.querySelectorAll('.custom-tooltip');
        tooltips.forEach(tooltip => {
            tooltip.style.opacity = '0';
            setTimeout(() => {
                tooltip.remove();
            }, 200);
        });
    }

    function showAccessDeniedMessage(card) {
        const moduleName = card.querySelector('.module-name').textContent;
        
        const message = document.createElement('div');
        message.className = 'access-denied-message';
        message.innerHTML = `
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); text-align: center; z-index: 1001; max-width: 400px;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                <h3 style="margin-bottom: 1rem; color: var(--color-gray-800);">Accès restreint</h3>
                <p style="color: var(--color-gray-600); margin-bottom: 1.5rem;">
                    Le module "${moduleName}" nécessite des permissions supplémentaires.
                </p>
                <button onclick="this.closest('.access-denied-message').remove()" 
                        style="background: var(--primary-blue); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    Compris
                </button>
            </div>
        `;
        
        message.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        `;
        
        document.body.appendChild(message);
    }

    // ========================================
    // 📈 ACTUALISATION DES STATS
    // ========================================
    
    function startStatsRefresh() {
        if (statsRefreshTimer) {
            clearInterval(statsRefreshTimer);
        }
        
        statsRefreshTimer = setInterval(() => {
            refreshDashboardStats();
        }, HOME_CONFIG.stats.refreshInterval);
        
        // Arrêter si la page devient cachée
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && statsRefreshTimer) {
                clearInterval(statsRefreshTimer);
            } else if (!document.hidden && !statsRefreshTimer) {
                startStatsRefresh();
            }
        });
    }

    function refreshDashboardStats() {
        // Simuler mise à jour des calculs du jour
        const calculsTodayCard = document.querySelector('.stat-card:nth-child(3) .stat-number');
        
        if (calculsTodayCard) {
            const currentValue = parseInt(calculsTodayCard.textContent) || 0;
            const increment = Math.floor(Math.random() * 3) + 1;
            const newValue = currentValue + increment;
            
            // Animation de changement
            calculsTodayCard.style.color = 'var(--color-success, #10b981)';
            calculsTodayCard.style.transform = 'scale(1.1)';
            
            setTimeout(() => {
                calculsTodayCard.textContent = newValue;
                calculsTodayCard.style.color = '';
                calculsTodayCard.style.transform = 'scale(1)';
            }, 200);
        }
    }

    // ========================================
    // ⌨️ NAVIGATION CLAVIER
    // ========================================
    
    function setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Navigation entre modules avec flèches
            if (e.target.classList.contains('module-card')) {
                handleModuleKeyboardNavigation(e);
            }
            
            // Raccourcis globaux
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'h':
                        e.preventDefault();
                        focusFirstModule();
                        break;
                    case 'r':
                        e.preventDefault();
                        refreshDashboardStats();
                        break;
                }
            }
        });
    }

    function handleModuleKeyboardNavigation(e) {
        const modules = Array.from(document.querySelectorAll('.module-card'));
        const currentIndex = modules.indexOf(e.target);
        let nextIndex = currentIndex;
        
        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                nextIndex = (currentIndex + 1) % modules.length;
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                nextIndex = (currentIndex - 1 + modules.length) % modules.length;
                break;
            case 'Home':
                nextIndex = 0;
                break;
            case 'End':
                nextIndex = modules.length - 1;
                break;
        }
        
        if (nextIndex !== currentIndex) {
            e.preventDefault();
            modules[nextIndex].focus();
            modules[nextIndex].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }

    function focusFirstModule() {
        const firstModule = document.querySelector('.module-card');
        if (firstModule) {
            firstModule.focus();
            firstModule.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }

    // ========================================
    // 👁️ INTERSECTION OBSERVER
    // ========================================
    
    function setupIntersectionObserver() {
        if (!window.IntersectionObserver) return;

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        moduleObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-viewport');
                    
                    // Animation spéciale pour les éléments animés
                    if (HOME_CONFIG.animations.enabled) {
                        animateElementInView(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        const elementsToObserve = document.querySelectorAll(
            '.module-card, .stat-card, .category-section'
        );
        
        elementsToObserve.forEach(element => {
            moduleObserver.observe(element);
        });
    }

    function animateElementInView(element) {
        if (element.classList.contains('module-card')) {
            element.style.transform = 'translateY(0) scale(1)';
            element.style.opacity = '1';
        }
    }

    // ========================================
    // 🔗 PRÉCHARGEMENT MODULES
    // ========================================
    
    function preloadModulePage(href) {
        if (!href || href === '#' || href === 'javascript:void(0)') return;
        
        // Éviter le préchargement multiple
        if (document.querySelector(`link[rel="prefetch"][href="${href}"]`)) return;
        
        // Préchargement discret
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        document.head.appendChild(link);
        
        // Nettoyer après un certain temps
        setTimeout(() => {
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
        }, 10000);
    }

    // ========================================
    // 📊 ANALYTICS ET TRACKING
    // ========================================
    
    function trackModuleInteraction(action, moduleData) {
        const eventData = {
            action: action,
            module: moduleData.name,
            category: moduleData.category,
            status: moduleData.status,
            accessible: moduleData.isAccessible,
            timestamp: Date.now()
        };
        
        // PortalManager analytics
        if (window.PortalManager && window.PortalManager.log) {
            window.PortalManager.log('info', `Module ${action}`, eventData);
        }
        
        // Google Analytics si disponible
        if (window.gtag) {
            window.gtag('event', `module_${action}`, {
                event_category: 'dashboard_interaction',
                event_label: moduleData.name,
                custom_map: {
                    module_status: moduleData.status,
                    module_accessible: moduleData.isAccessible
                }
            });
        }
        
        // Matomo si disponible
        if (window._paq) {
            window._paq.push(['trackEvent', 'Dashboard', `Module ${action}`, moduleData.name]);
        }
    }

    // ========================================
    // 👤 INTERACTIONS RÔLE
    // ========================================
    
    function setupRoleInteractions() {
        const roleInfo = document.querySelector('.role-info');
        const roleBadge = document.querySelector('.role-badge');
        
        if (roleInfo) {
            roleInfo.addEventListener('click', showRolePermissions);
        }
        
        if (roleBadge) {
            roleBadge.addEventListener('click', (e) => {
                e.stopPropagation();
                const role = roleBadge.textContent.trim().toLowerCase();
                showRoleDetails(role);
            });
        }
    }

    function showRolePermissions() {
        if (window.PortalManager && window.PortalManager.showToast) {
            window.PortalManager.showToast('info', 'Permissions utilisateur', 
                'Cliquez sur votre badge de rôle pour plus de détails');
        }
    }

    function showRoleDetails(role) {
        const roleDescriptions = {
            'user': 'Accès aux modules actifs et consultation des données',
            'admin': 'Gestion système et accès modules actifs/beta', 
            'dev': 'Accès développeur complet incluant modules en développement',
            'logistique': 'Accès spécialisé transport et logistique'
        };
        
        const description = roleDescriptions[role] || 'Permissions standard';
        
        if (window.PortalManager && window.PortalManager.showToast) {
            window.PortalManager.showToast('info', `Rôle ${role.toUpperCase()}`, description);
        }
    }

    // ========================================
    // 🎨 ANIMATIONS AVANCÉES
    // ========================================
    
    function animateModuleCards() {
        const cards = document.querySelectorAll('.module-card');
        
        cards.forEach((card, index) => {
            if (HOME_CONFIG.animations.enabled) {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
                card.style.transition = `opacity ${HOME_CONFIG.animations.duration}ms ease ${index * 50}ms, 
                                       transform ${HOME_CONFIG.animations.duration}ms ease ${index * 50}ms`;
            }
        });
    }

    // ========================================
    // 🧹 NETTOYAGE
    // ========================================
    
    function cleanup() {
        if (statsRefreshTimer) {
            clearInterval(statsRefreshTimer);
            statsRefreshTimer = null;
        }
        
        if (moduleObserver) {
            moduleObserver.disconnect();
            moduleObserver = null;
        }
        
        hideAllTooltips();
    }

    // Nettoyage automatique
    window.addEventListener('beforeunload', cleanup);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hideAllTooltips();
        }
    });

    // ========================================
    // 🚀 INITIALISATION
    // ========================================
    
    // Attendre PortalManager et initialiser
    waitForPortalManager(extendPortalManager);

    // Export pour tests et debug
    window.DashboardHome = {
        init: initializeDashboardHome,
        refreshStats: refreshDashboardStats,
        animateModules: animateModuleCards,
        cleanup: cleanup,
        config: HOME_CONFIG,
        
        // Méthodes utilitaires
        showTooltip: showModuleTooltip,
        hideTooltips: hideAllTooltips,
        trackInteraction: trackModuleInteraction,
        preloadPage: preloadModulePage,
        
        // État actuel
        get initialized() { return homeInitialized; },
        get statsTimer() { return statsRefreshTimer; },
        get observer() { return moduleObserver; }
    };

    // Méthodes globales pour debug console
    if (window.console && window.console.log) {
        window.DashboardHome.help = function() {
            console.log(`
🏠 Dashboard Home API v1.0

Commandes disponibles:
- DashboardHome.init() : Réinitialiser dashboard
- DashboardHome.refreshStats() : Actualiser statistiques
- DashboardHome.animateModules() : Relancer animations
- DashboardHome.cleanup() : Nettoyer ressources
- DashboardHome.showTooltip(card, data) : Afficher tooltip
- DashboardHome.hideTooltips() : Masquer tooltips
- DashboardHome.preloadPage(url) : Précharger page

État:
- Initialisé: ${homeInitialized}
- Animations: ${HOME_CONFIG.animations.enabled}
- Timer stats: ${statsRefreshTimer ? 'Actif' : 'Inactif'}
- Observer: ${moduleObserver ? 'Actif' : 'Inactif'}

Raccourcis clavier:
- Ctrl+H : Focus premier module
- Ctrl+R : Actualiser stats
- Flèches : Navigation modules
            `);
        };
    }

})();
