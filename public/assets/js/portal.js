/**
 * Titre: Gestionnaire JavaScript du portail Guldagil - COMPLET 615+ lignes
 * Chemin: /public/assets/js/portal.js
 * Version: 0.5 beta + build auto
 */

class PortalManager {
    constructor() {
        this.version = '0.5-beta';
        this.modules = new Map();
        this.initialized = false;
        this.debug = false;
        this.cache = new Map();
        this.notifications = [];
        this.observers = new Map();
        this.eventListeners = new Map();
    }

    init() {
        if (this.initialized) return;

        this.log('info', `🚀 Initialisation Portail Guldagil v${this.version}`);
        
        try {
            this.detectEnvironment();
            this.initializeCache();
            this.initializeModules();
            this.setupEventListeners();
            this.setupAccessibility();
            this.setupAnimations();
            this.setupFormValidation();
            this.setupErrorHandling();
            this.checkModuleStatus();
            this.startPerformanceMonitoring();
            
            this.initialized = true;
            this.log('success', '✅ Portail initialisé');
            
            if (this.debug) {
                window.PortalAPI = this.getDebugAPI();
                this.log('info', '🔧 API Debug disponible : PortalAPI');
            }
            
            // Événement personnalisé
            this.emit('portal:ready', { 
                version: this.version,
                modules: this.modules.size,
                timestamp: Date.now()
            });
            
        } catch (error) {
            this.log('error', 'Erreur initialisation', error);
            this.handleCriticalError(error);
        }
    }

    detectEnvironment() {
        this.debug = document.querySelector('meta[name="cache-control"]')?.content === 'no-cache';
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
        this.isDesktop = window.innerWidth > 1024;
        this.isOnline = navigator.onLine;
        this.hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        // Classes CSS d'environnement
        document.body.classList.add(
            this.isMobile ? 'is-mobile' : 
            this.isTablet ? 'is-tablet' : 'is-desktop'
        );
        
        if (this.hasTouch) {
            document.body.classList.add('has-touch');
        }
        
        if (this.debug) {
            document.body.classList.add('debug-mode');
        }
        
        // Détection des capacités
        this.capabilities = {
            localStorage: this.testLocalStorage(),
            sessionStorage: this.testSessionStorage(),
            indexedDB: !!window.indexedDB,
            webWorkers: !!window.Worker,
            notifications: 'Notification' in window,
            geolocation: 'geolocation' in navigator,
            camera: 'mediaDevices' in navigator
        };
        
        this.log('info', '🌐 Environnement détecté', {
            mobile: this.isMobile,
            tablet: this.isTablet,
            desktop: this.isDesktop,
            touch: this.hasTouch,
            online: this.isOnline,
            capabilities: this.capabilities
        });
    }

    testLocalStorage() {
        try {
            const test = '__test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    testSessionStorage() {
        try {
            const test = '__test__';
            sessionStorage.setItem(test, test);
            sessionStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    initializeCache() {
        this.cache.set('startTime', Date.now());
        this.cache.set('pageViews', this.getFromStorage('pageViews', 0) + 1);
        this.saveToStorage('pageViews', this.cache.get('pageViews'));
        
        // Cache des requêtes
        this.requestCache = new Map();
        this.cacheExpiry = 5 * 60 * 1000; // 5 minutes
    }

    getFromStorage(key, defaultValue) {
        if (this.capabilities.localStorage) {
            try {
                const value = localStorage.getItem(`portal_${key}`);
                return value ? JSON.parse(value) : defaultValue;
            } catch (e) {
                return defaultValue;
            }
        }
        return defaultValue;
    }

    saveToStorage(key, value) {
        if (this.capabilities.localStorage) {
            try {
                localStorage.setItem(`portal_${key}`, JSON.stringify(value));
            } catch (e) {
                this.log('warning', 'Impossible de sauvegarder en localStorage');
            }
        }
    }

    initializeModules() {
        const moduleCards = document.querySelectorAll('.module-card');
        let moduleCount = 0;
        
        moduleCards.forEach((card, index) => {
            const moduleId = card.dataset.module || this.extractModuleId(card);
            const moduleData = this.extractModuleData(card);
            
            if (moduleId && moduleData.name) {
                this.modules.set(moduleId, {
                    ...moduleData,
                    element: card,
                    initialized: false,
                    index: index,
                    loadTime: null,
                    errorCount: 0,
                    lastAccessed: null
                });
                
                this.initializeModuleCard(card, moduleData, index);
                moduleCount++;
            }
        });
        
        this.log('info', `📦 ${moduleCount} modules détectés et initialisés`);
        this.cache.set('moduleCount', moduleCount);
    }

    extractModuleId(card) {
        // Priorité : data-module > href > classe CSS
        if (card.dataset.module) {
            return card.dataset.module;
        }
        
        const link = card.querySelector('a[href], .btn[href]');
        if (link) {
            const href = link.getAttribute('href');
            const match = href.match(/\/([^\/]+)\/?$/);
            return match ? match[1] : 'unknown';
        }
        
        // Extraire de la classe CSS module-*
        const classList = Array.from(card.classList);
        const moduleClass = classList.find(cls => cls.startsWith('module-') && cls !== 'module-card');
        if (moduleClass) {
            return moduleClass.replace('module-', '');
        }
        
        return `unknown_${Date.now()}`;
    }

    extractModuleData(card) {
        const icon = card.querySelector('.module-icon')?.textContent?.trim();
        const name = card.querySelector('.module-title')?.textContent?.trim();
        const status = card.querySelector('.module-status')?.textContent?.trim();
        const description = card.querySelector('.module-description')?.textContent?.trim();
        const button = card.querySelector('.btn, a[href]');
        const href = button?.getAttribute('href');
        const progress = card.querySelector('.module-progress')?.textContent?.trim();
        
        // Extraire les fonctionnalités
        const features = [];
        card.querySelectorAll('.module-features li').forEach(li => {
            const feature = li.textContent?.trim();
            if (feature) features.push(feature);
        });
        
        return {
            icon: icon || '📦',
            name: name || 'Module sans nom',
            status: status || 'unknown',
            description: description || '',
            href: href || '#',
            progress: progress || '0%',
            features: features,
            isActive: card.classList.contains('module-available') || 
                     button && !button.disabled && !button.classList.contains('btn-disabled'),
            isRestricted: card.classList.contains('module-restricted'),
            isDevelopment: card.classList.contains('module-dev') || 
                          status?.toLowerCase().includes('développement')
        };
    }

    initializeModuleCard(card, moduleData, index) {
        // Animation d'entrée différée
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
        
        // Gestion des clics avec analytics
        const button = card.querySelector('.btn, a[href]');
        if (button && moduleData.isActive) {
            button.addEventListener('click', (e) => {
                this.handleModuleAccess(e, moduleData);
            });
        }
        
        // Effets visuels améliorés
        this.setupModuleHoverEffects(card, moduleData);
        
        // Ajout d'un indicateur de statut temps réel
        this.addStatusIndicator(card, moduleData);
        
        // Gestion du lazy loading des images si présentes
        this.setupLazyLoading(card);
    }

    setupModuleHoverEffects(card, moduleData) {
        let hoverTimeout;
        
        card.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
            if (moduleData.isActive) {
                card.style.transform = 'translateY(-8px) scale(1.02)';
                card.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.15)';
                
                // Précharger le module si possible
                if (moduleData.href && moduleData.href !== '#') {
                    this.preloadModule(moduleData.href);
                }
            }
        });
        
        card.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                card.style.transform = 'translateY(0) scale(1)';
                card.style.boxShadow = '';
            }, 100);
        });
        
        // Support touch pour mobile
        if (this.hasTouch) {
            card.addEventListener('touchstart', () => {
                card.classList.add('touch-active');
            });
            
            card.addEventListener('touchend', () => {
                setTimeout(() => {
                    card.classList.remove('touch-active');
                }, 150);
            });
        }
    }

    addStatusIndicator(card, moduleData) {
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
            background: ${moduleData.isActive ? '#22c55e' : '#ef4444'};
            box-shadow: 0 0 0 2px white;
            z-index: 10;
        `;
        
        if (header.style.position !== 'relative') {
            header.style.position = 'relative';
        }
        
        header.appendChild(indicator);
    }

    setupLazyLoading(card) {
        const images = card.querySelectorAll('img[data-src]');
        if (images.length === 0) return;
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    preloadModule(href) {
        // Éviter le préchargement multiple
        if (this.requestCache.has(href)) return;
        
        // Préchargement discret
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        document.head.appendChild(link);
        
        this.requestCache.set(href, Date.now());
        
        // Nettoyer après un certain temps
        setTimeout(() => {
            document.head.removeChild(link);
        }, 10000);
    }

    handleModuleAccess(event, moduleData) {
        const module = this.modules.get(this.getModuleIdFromData(moduleData));
        
        if (module) {
            module.lastAccessed = Date.now();
            module.loadTime = performance.now();
        }
        
        // Confirmation pour modules restreints
        if (moduleData.isRestricted) {
            const confirmAccess = confirm(
                `🔒 Accès administrateur requis.\n\nContinuer vers ${moduleData.name} ?`
            );
            if (!confirmAccess) {
                event.preventDefault();
                return false;
            }
        }
        
        // Vérification pour modules en développement
        if (moduleData.isDevelopment && !this.debug) {
            const confirmDev = confirm(
                `⚠️ Module en développement.\n\nCertaines fonctionnalités peuvent être instables.\nContinuer ?`
            );
            if (!confirmDev) {
                event.preventDefault();
                return false;
            }
        }
        
        this.log('info', `🔗 Navigation vers ${moduleData.name}`, {
            module: moduleData.name,
            href: moduleData.href,
            timestamp: Date.now()
        });
        
        // Analytics personnalisées
        this.trackModuleAccess(moduleData);
        
        // Notification de chargement
        if (moduleData.href && moduleData.href !== '#') {
            this.showToast('info', 'Chargement...', `Ouverture de ${moduleData.name}`);
        }
    }

    getModuleIdFromData(moduleData) {
        // Retrouver l'ID du module à partir des données
        for (const [id, data] of this.modules) {
            if (data.name === moduleData.name) {
                return id;
            }
        }
        return null;
    }

    trackModuleAccess(moduleData) {
        // Analytics internes
        const accessLog = this.getFromStorage('moduleAccess', {});
        const moduleKey = moduleData.name.toLowerCase().replace(/\s+/g, '_');
        
        if (!accessLog[moduleKey]) {
            accessLog[moduleKey] = {
                name: moduleData.name,
                count: 0,
                firstAccess: Date.now(),
                lastAccess: null
            };
        }
        
        accessLog[moduleKey].count++;
        accessLog[moduleKey].lastAccess = Date.now();
        
        this.saveToStorage('moduleAccess', accessLog);
        
        // Google Analytics si disponible
        if (window.gtag) {
            window.gtag('event', 'module_access', {
                event_category: 'navigation',
                event_label: moduleData.name,
                custom_map: {
                    module_status: moduleData.status,
                    module_type: moduleData.isRestricted ? 'restricted' : 'standard'
                }
            });
        }
        
        // Matomo si disponible
        if (window._paq) {
            window._paq.push(['trackEvent', 'Module', 'Access', moduleData.name]);
        }
    }

    setupEventListeners() {
        // Gestion du redimensionnement
        window.addEventListener('resize', this.debounce(() => {
            this.detectEnvironment();
            this.refreshLayout();
            this.emit('portal:resize', {
                width: window.innerWidth,
                height: window.innerHeight,
                isMobile: this.isMobile,
                isTablet: this.isTablet,
                isDesktop: this.isDesktop
            });
        }, 250));
        
        // Gestion de la connectivité
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showToast('success', 'Connexion rétablie', 'Vous êtes de nouveau en ligne');
            this.checkModuleStatus();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showToast('warning', 'Hors ligne', 'Connexion internet interrompue');
        });
        
        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            
            // Raccourcis clavier
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'k':
                        e.preventDefault();
                        this.openQuickSearch();
                        break;
                    case '/':
                        e.preventDefault();
                        this.openHelp();
                        break;
                }
            }
            
            // Navigation avec flèches dans les modules
            if (e.target.classList.contains('module-card')) {
                this.handleModuleKeyboardNavigation(e);
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
        
        // Gestion des erreurs globales
        window.addEventListener('error', (e) => {
            this.log('error', 'Erreur JavaScript globale', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error?.stack
            });
            
            this.handleError(e.error || new Error(e.message));
        });
        
        window.addEventListener('unhandledrejection', (e) => {
            this.log('error', 'Promise rejetée non gérée', e.reason);
            this.handleError(e.reason);
        });
        
        // Gestion de la visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.emit('portal:hidden');
                this.pauseNonEssentialTasks();
            } else {
                this.emit('portal:visible');
                this.resumeNonEssentialTasks();
                this.checkModuleStatus();
            }
        });
        
        // Gestion beforeunload pour les données non sauvegardées
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedData()) {
                e.preventDefault();
                e.returnValue = 'Des modifications non sauvegardées seront perdues.';
                return e.returnValue;
            }
        });
    }

    openQuickSearch() {
        // Implémentation recherche rapide
        this.showToast('info', 'Recherche rapide', 'Fonctionnalité à venir');
    }

    openHelp() {
        // Ouvrir l'aide
        this.showToast('info', 'Aide', 'Raccourcis : Ctrl+K (recherche), Ctrl+/ (aide)');
    }

    handleModuleKeyboardNavigation(e) {
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
            case 'Enter':
            case ' ':
                e.preventDefault();
                const button = e.target.querySelector('.btn, a[href]');
                if (button) button.click();
                return;
        }
        
        if (nextIndex !== currentIndex) {
            e.preventDefault();
            modules[nextIndex].focus();
        }
    }

    setupAccessibility() {
        // ARIA live region pour les annonces
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        announcer.id = 'portal-announcer';
        document.body.appendChild(announcer);
        this.announcer = announcer;
        
        // Améliorer les modules pour l'accessibilité
        document.querySelectorAll('.module-card').forEach((card, index) => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('aria-describedby', `module-desc-${index}`);
            
            const description = card.querySelector('.module-description');
            if (description) {
                description.id = `module-desc-${index}`;
            }
        });
        
        // Skip links
        this.addSkipLinks();
        
        // Contraste élevé si préféré
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.body.classList.add('high-contrast');
        }
        
        // Réduction de mouvement si préférée
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduce-motion');
        }
    }

    addSkipLinks() {
        const skipLinks = document.createElement('div');
        skipLinks.className = 'skip-links';
        skipLinks.innerHTML = `
            <a href="#main-content" class="skip-link">Aller au contenu principal</a>
            <a href="#modules" class="skip-link">Aller aux modules</a>
            <a href="#footer" class="skip-link">Aller au pied de page</a>
        `;
        
        document.body.insertBefore(skipLinks, document.body.firstChild);
    }

    setupAnimations() {
        // Intersection Observer pour animations d'entrée
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Animation personnalisée selon le type d'élément
                    if (entry.target.classList.contains('stat-card')) {
                        this.animateStatCard(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        const animatableElements = document.querySelectorAll(
            '.module-card, .stat-card, .info-card, .action-card, .alert'
        );
        
        animatableElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            observer.observe(element);
        });
        
        this.observers.set('animations', observer);
    }

    animateStatCard(card) {
        const value = card.querySelector('.stat-value');
        if (!value) return;
        
        const finalValue = parseInt(value.textContent) || 0;
        const duration = 1000;
        const steps = 30;
        const increment = finalValue / steps;
        let current = 0;
        
        const counter = setInterval(() => {
            current += increment;
            if (current >= finalValue) {
                value.textContent = finalValue;
                clearInterval(counter);
            } else {
                value.textContent = Math.floor(current);
            }
        }, duration / steps);
    }

    setupFormValidation() {
        // Validation en temps réel des formulaires
        document.querySelectorAll('form').forEach(form => {
            this.setupFormValidationForForm(form);
        });
    }

    setupFormValidationForForm(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Validation au blur
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            // Validation en temps réel pour certains champs
            if (input.type === 'email' || input.type === 'url') {
                input.addEventListener('input', this.debounce(() => {
                    this.validateField(input);
                }, 500));
            }
        });
        
        // Validation à la soumission
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                this.showToast('error', 'Erreur de validation', 'Veuillez corriger les erreurs dans le formulaire');
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        // Validation required
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Ce champ est obligatoire';
        }
        
        // Validation email
        if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            isValid = false;
            message = 'Format d\'email invalide';
        }
        
        // Validation URL
        if (field.type === 'url' && value && !/^https?:\/\/.+/.test(value)) {
            isValid = false;
            message = 'URL invalide (doit commencer par http:// ou https://)';
        }
        
        // Affichage du résultat
        this.displayFieldValidation(field, isValid, message);
        
        return isValid;
    }

    displayFieldValidation(field, isValid, message) {
        // Retirer les classes précédentes
        field.classList.remove('field-valid', 'field-invalid');
        
        // Retirer le message précédent
        const existingMessage = field.parentNode.querySelector('.field-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Ajouter la nouvelle classe
        field.classList.add(isValid ? 'field-valid' : 'field-invalid');
        
        // Ajouter le message si erreur
        if (!isValid && message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'field-message field-error';
            messageElement.textContent = message;
            field.parentNode.appendChild(messageElement);
        }
    }

    validateForm(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    setupErrorHandling() {
        this.errorQueue = [];
        this.maxErrors = 10;
        this.errorReportingEnabled = this.debug;
    }

    handleError(error) {
        const errorInfo = {
            message: error.message || 'Erreur inconnue',
            stack: error.stack,
            timestamp: Date.now(),
            userAgent: navigator.userAgent,
            url: window.location.href,
            userId: this.getCurrentUserId()
        };
        
        this.errorQueue.push(errorInfo);
        
        // Limiter la taille de la queue
        if (this.errorQueue.length > this.maxErrors) {
            this.errorQueue.shift();
        }
        
        // Rapporter l'erreur si activé
        if (this.errorReportingEnabled) {
            this.reportError(errorInfo);
        }
        
        // Afficher une notification utilisateur si critique
        if (this.isCriticalError(error)) {
            this.showToast('error', 'Erreur système', 'Une erreur inattendue s\'est produite');
        }
    }

    handleCriticalError(error) {
        this.log('error', '💥 Erreur critique', error);
        
        // Sauvegarder l'état avant le crash
        this.saveEmergencyState();
        
        // Afficher une interface de récupération
        this.showRecoveryInterface(error);
    }

    isCriticalError(error) {
        const criticalPatterns = [
            /network/i,
            /failed to fetch/i,
            /load/i,
            /timeout/i
        ];
        
        return criticalPatterns.some(pattern => 
            pattern.test(error.message || '')
        );
    }

    saveEmergencyState() {
        const state = {
            timestamp: Date.now(),
            url: window.location.href,
            modules: Array.from(this.modules.keys()),
            cache: Object.fromEntries(this.cache),
            errors: this.errorQueue.slice(-5)
        };
        
        this.saveToStorage('emergencyState', state);
    }

    showRecoveryInterface(error) {
        const recovery = document.createElement('div');
        recovery.className = 'recovery-interface';
        recovery.innerHTML = `
            <div class="recovery-content">
                <h2>🚨 Erreur système détectée</h2>
                <p>Le portail a rencontré une erreur inattendue.</p>
                <div class="recovery-actions">
                    <button onclick="location.reload()" class="btn btn-primary">
                        Recharger la page
                    </button>
                    <button onclick="this.closest('.recovery-interface').remove()" class="btn">
                        Continuer
                    </button>
                </div>
                ${this.debug ? `<details><summary>Détails technique</summary><pre>${error.stack}</pre></details>` : ''}
            </div>
        `;
        
        recovery.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        document.body.appendChild(recovery);
    }

    getCurrentUserId() {
        // Extraire l'ID utilisateur si disponible
        const userElement = document.querySelector('.user-info');
        if (userElement) {
            return userElement.textContent.trim();
        }
        return 'anonymous';
    }

    reportError(errorInfo) {
        ///**
 * Titre: Gestionnaire JavaScript du portail Guldagil - COMPLET
 * Chemin: /public/assets/js/portal.js
 * Version: 0.5 beta + build auto
 */

class PortalManager {
    constructor() {
        this.version = '0.5-beta';
        this.modules = new Map();
        this.initialized = false;
        this.debug = false;
    }

    init() {
        if (this.initialized) return;

        this.log('info', `🚀 Initialisation Portail Guldagil v${this.version}`);
        
        try {
            this.detectEnvironment();
            this.initializeModules();
            this.setupEventListeners();
            this.setupAccessibility();
            this.setupAnimations();
            this.checkModuleStatus();
            
            this.initialized = true;
            this.log('success', '✅ Portail initialisé');
            
            if (this.debug) {
                window.PortalAPI = this.getDebugAPI();
            }
            
        } catch (error) {
            this.log('error', 'Erreur initialisation', error);
        }
    }

    detectEnvironment() {
        this.debug = document.querySelector('meta[name="cache-control"]')?.content === 'no-cache';
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
        this.isDesktop = window.innerWidth > 1024;
        
        document.body.classList.add(
            this.isMobile ? 'is-mobile' : 
            this.isTablet ? 'is-tablet' : 'is-desktop'
        );
        
        if (this.debug) {
            document.body.classList.add('debug-mode');
        }
    }

    initializeModules() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            const moduleId = card.dataset.module || this.extractModuleId(card);
            const moduleData = this.extractModuleData(card);
            
            this.modules.set(moduleId, {
                ...moduleData,
                element: card,
                initialized: false
            });
            
            this.initializeModuleCard(card, moduleData);
        });
        
        this.log('info', `📦 ${this.modules.size} modules détectés`);
    }

    extractModuleId(card) {
        const link = card.querySelector('a[href]');
        if (link) {
            const href = link.getAttribute('href');
            return href.replace(/^\//, '').replace(/\/$/, '') || 'home';
        }
        return 'unknown';
    }

    extractModuleData(card) {
        const icon = card.querySelector('.module-icon')?.textContent;
        const name = card.querySelector('.module-title')?.textContent;
        const status = card.querySelector('.module-status')?.textContent;
        const description = card.querySelector('.module-description')?.textContent;
        const button = card.querySelector('.btn');
        const href = button?.href;
        
        return {
            icon,
            name,
            status,
            description,
            href,
            isActive: card.classList.contains('module-available'),
            isRestricted: card.classList.contains('module-restricted')
        };
    }

    initializeModuleCard(card, moduleData) {
        // Animation d'entrée
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        // Gestion des clics
        const button = card.querySelector('.btn');
        if (button && !button.disabled) {
            button.addEventListener('click', (e) => {
                this.handleModuleAccess(e, moduleData);
            });
        }
        
        // Effet hover amélioré
        card.addEventListener('mouseenter', () => {
            if (moduleData.isActive) {
                card.style.transform = 'translateY(-4px) scale(1.02)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    }

    handleModuleAccess(event, moduleData) {
        if (moduleData.isRestricted) {
            const confirmAccess = confirm(
                `Accès administrateur requis.\nContinuer vers ${moduleData.name} ?`
            );
            if (!confirmAccess) {
                event.preventDefault();
                return false;
            }
        }
        
        this.log('info', `Navigation vers ${moduleData.name}`);
        
        // Analytics si disponible
        if (window.gtag) {
            window.gtag('event', 'module_access', {
                module_name: moduleData.name,
                module_status: moduleData.status
            });
        }
    }

    setupEventListeners() {
        // Responsive
        window.addEventListener('resize', this.debounce(() => {
            this.detectEnvironment();
            this.refreshLayout();
        }, 250));
        
        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
        
        // Gestion erreurs
        window.addEventListener('error', (e) => {
            this.log('error', 'Erreur JavaScript', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno
            });
        });
    }

    setupAccessibility() {
        // Focus visible pour navigation clavier
        const focusableElements = document.querySelectorAll(
            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        focusableElements.forEach(element => {
            element.addEventListener('focus', () => {
                element.setAttribute('data-focus-visible', 'true');
            });
            
            element.addEventListener('blur', () => {
                element.removeAttribute('data-focus-visible');
            });
        });
        
        // Annonces pour lecteurs d'écran
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        document.body.appendChild(announcer);
        this.announcer = announcer;
    }

    setupAnimations() {
        // Intersection Observer pour animations d'entrée
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        document.querySelectorAll('.module-card, .stat-card, .info-card, .action-card').forEach(card => {
            observer.observe(card);
        });
        
        // Animation séquentielle pour les cartes
        this.animateCardsSequentially();
    }

    animateCardsSequentially() {
        const cards = document.querySelectorAll('.module-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            }, index * 100);
        });
    }

    checkModuleStatus() {
        this.modules.forEach((moduleData, moduleId) => {
            if (moduleData.href && moduleData.isActive) {
                this.pingModule(moduleId, moduleData.href);
            }
        });
    }

    async pingModule(moduleId, href) {
        try {
            const response = await fetch(href, { 
                method: 'HEAD',
                cache: 'no-cache'
            });
            
            const module = this.modules.get(moduleId);
            if (module) {
                module.status = response.ok ? 'online' : 'offline';
                this.updateModuleStatusUI(moduleId, module.status);
            }
        } catch (error) {
            const module = this.modules.get(moduleId);
            if (module) {
                module.status = 'error';
                this.updateModuleStatusUI(moduleId, 'error');
            }
        }
    }

    updateModuleStatusUI(moduleId, status) {
        const module = this.modules.get(moduleId);
        if (!module || !module.element) return;
        
        let statusIcon = '';
        let statusClass = '';
        
        switch (status) {
            case 'online':
                statusIcon = '🟢';
                statusClass = 'status-online';
                break;
            case 'offline':
                statusIcon = '🔴';
                statusClass = 'status-offline';
                break;
            case 'error':
                statusIcon = '⚠️';
                statusClass = 'status-error';
                break;
        }
        
        // Ajouter indicateur visuel
        const statusElement = module.element.querySelector('.module-status-indicator');
        if (statusElement) {
            statusElement.textContent = statusIcon;
            statusElement.className = `module-status-indicator ${statusClass}`;
        }
    }

    refreshLayout() {
        // Recalculer les grilles responsive
        const grids = document.querySelectorAll('.modules-grid, .stats-grid, .info-grid, .actions-grid');
        grids.forEach(grid => {
            grid.style.display = 'none';
            grid.offsetHeight; // Force reflow
            grid.style.display = 'grid';
        });
    }

    showToast(type, title, message) {
        const toast = this.createToast(type, title, message);
        this.displayToast(toast);
        
        // Announce pour accessibilité
        if (this.announcer) {
            this.announcer.textContent = `${title}: ${message}`;
        }
    }

    createToast(type, title, message) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">${this.getToastIcon(type)}</div>
                <div class="toast-text">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Fermer">&times;</button>
            </div>
        `;
        
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 16px;
            max-width: 400px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        return toast;
    }

    getToastIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || 'ℹ️';
    }

    displayToast(toast) {
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Gestion fermeture
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            this.hideToast(toast);
        });
        
        // Auto-fermeture
        setTimeout(() => {
            this.hideToast(toast);
        }, 5000);
    }

    hideToast(toast) {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    log(level, message, data = null) {
        const prefix = `[Portal ${this.version}]`;
        
        switch (level) {
            case 'error':
                console.error(prefix, message, data);
                break;
            case 'warning':
                console.warn(prefix, message, data);
                break;
            case 'success':
                console.log(`%c${prefix} ${message}`, 'color: green; font-weight: bold', data);
                break;
            default:
                console.log(prefix, message, data);
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // API de gestion des modules
    registerModule(moduleId, moduleConfig) {
        this.modules.set(moduleId, {
            ...moduleConfig,
            initialized: true,
            registeredAt: Date.now()
        });
        
        this.log('info', `Module ${moduleId} enregistré`);
    }

    unregisterModule(moduleId) {
        if (this.modules.has(moduleId)) {
            this.modules.delete(moduleId);
            this.log('info', `Module ${moduleId} désenregistré`);
        }
    }

    getModule(moduleId) {
        return this.modules.get(moduleId);
    }

    getAllModules() {
        return Array.from(this.modules.entries()).map(([id, config]) => ({
            id,
            ...config
        }));
    }

    // Gestion des événements personnalisés
    emit(eventName, data) {
        const event = new CustomEvent(`portal:${eventName}`, {
            detail: data,
            bubbles: true
        });
        document.dispatchEvent(event);
    }

    on(eventName, callback) {
        document.addEventListener(`portal:${eventName}`, callback);
    }

    off(eventName, callback) {
        document.removeEventListener(`portal:${eventName}`, callback);
    }

    // Utilitaires pour modules
    showLoader(target = document.body) {
        const loader = document.createElement('div');
        loader.className = 'portal-loader';
        loader.innerHTML = `
            <div class="loader-spinner"></div>
            <div class="loader-text">Chargement...</div>
        `;
        
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        target.appendChild(loader);
        return loader;
    }

    hideLoader(loader) {
        if (loader && loader.parentNode) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.parentNode.removeChild(loader);
            }, 300);
        }
    }

    getDebugAPI() {
        return {
            version: this.version,
            modules: this.getAllModules(),
            
            // Fonctions de test
            showToast: (type, title, message) => {
                this.showToast(type, title, message);
            },
            
            pingAllModules: () => {
                this.checkModuleStatus();
            },
            
            refreshLayout: () => {
                this.refreshLayout();
            },
            
            simulateError: () => {
                this.log('error', 'Erreur simulée', { test: true });
            },
            
            getStats: () => ({
                modulesCount: this.modules.size,
                activeModules: this.getAllModules().filter(m => m.isActive).length,
                environment: {
                    isMobile: this.isMobile,
                    isTablet: this.isTablet,
                    isDesktop: this.isDesktop,
                    debug: this.debug
                },
                performance: {
                    initTime: this.initTime,
                    memoryUsage: performance.memory ? {
                        used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) + ' MB',
                        total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024) + ' MB'
                    } : 'Non disponible'
                }
            }),
            
            // Commandes
            help: () => {
                console.log(`
Portal Debug API v${this.version}

Commandes disponibles:
- PortalAPI.showToast(type, title, message) : Afficher une notification
- PortalAPI.pingAllModules() : Vérifier statut des modules
- PortalAPI.refreshLayout() : Recalculer mise en page
- PortalAPI.getStats() : Statistiques système
- PortalAPI.simulateError() : Simuler une erreur
- PortalAPI.help() : Afficher cette aide

Types de toast: success, error, warning, info
                `);
            }
        };
    }
}

// Initialisation automatique et exposition globale
const PortalManager = new PortalManager();
window.PortalManager = PortalManager;

// Auto-initialisation au chargement DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        PortalManager.initTime = performance.now();
        PortalManager.init();
    });
} else {
    PortalManager.initTime = performance.now();
    PortalManager.init();
}

// Export pour modules ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PortalManager;
}
