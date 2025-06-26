/**
 * assets/js/portal.js - JavaScript spécifique au portail principal
 * Chemin: /public/assets/js/portal.js
 * Dépendances: app.min.js
 */

// =============================================================================
// NAMESPACE PORTAL
// =============================================================================

const Portal = {
    // Configuration
    config: {
        searchMinLength: 2,
        searchDelay: 300,
        statsUpdateInterval: 30000, // 30 secondes
        healthCheckInterval: 60000,  // 1 minute
        animationDuration: 300
    },
    
    // État
    state: {
        initialized: false,
        searchTimeout: null,
        lastSearch: '',
        statsTimer: null,
        healthTimer: null
    },
    
    /**
     * Initialisation du portail
     */
    init() {
        if (this.state.initialized) return;
        
        console.log('🏠 Initialisation du portail Guldagil...');
        
        // Initialiser les composants
        this.initSearch();
        this.initModuleCards();
        this.initQuickActions();
        this.initKeyboardShortcuts();
        this.initStatsUpdates();
        this.initHealthChecks();
        this.initAnimations();
        
        this.state.initialized = true;
        console.log('✅ Portail initialisé avec succès');
        
        // Notifier l'initialisation
        this.showWelcomeMessage();
    },
    
    /**
     * Afficher un message de bienvenue
     */
    showWelcomeMessage() {
        if (window.Notifications) {
            setTimeout(() => {
                Notifications.success('Portail Guldagil chargé avec succès!', 2000);
            }, 1000);
        }
    },
    
    /**
     * Initialiser la recherche
     */
    initSearch() {
        const searchInput = document.getElementById('quickSearchInput');
        if (!searchInput) return;
        
        // Événements de recherche
        searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch(e.target.value);
            }
        });
        
        // Placeholder dynamique
        this.initSearchPlaceholder(searchInput);
    },
    
    /**
     * Placeholder dynamique pour la recherche
     */
    initSearchPlaceholder(input) {
        const placeholders = [
            'Rechercher un module...',
            'Trouver une fonctionnalité...',
            'Recherche rapide...',
            'Que cherchez-vous ?'
        ];
        
        let currentIndex = 0;
        
        setInterval(() => {
            if (input !== document.activeElement) {
                input.placeholder = placeholders[currentIndex];
                currentIndex = (currentIndex + 1) % placeholders.length;
            }
        }, 3000);
    },
    
    /**
     * Gérer la saisie de recherche
     */
    handleSearchInput(value) {
        // Annuler la recherche précédente
        if (this.state.searchTimeout) {
            clearTimeout(this.state.searchTimeout);
        }
        
        // Nouvelle recherche après délai
        this.state.searchTimeout = setTimeout(() => {
            if (value.length >= this.config.searchMinLength) {
                this.performAutoSearch(value);
            }
        }, this.config.searchDelay);
    },
    
    /**
     * Effectuer une recherche automatique
     */
    performAutoSearch(query) {
        if (query === this.state.lastSearch) return;
        this.state.lastSearch = query;
        
        console.log('🔍 Recherche automatique:', query);
        
        // Mettre en évidence les modules correspondants
        this.highlightMatchingModules(query);
    },
    
    /**
     * Effectuer une recherche complète
     */
    performSearch(query) {
        if (!query || query.length < this.config.searchMinLength) {
            this.showSearchError('Veuillez saisir au moins 2 caractères');
            return;
        }
        
        console.log('🔍 Recherche complète:', query);
        
        // Simuler une recherche
        this.showSearchResults(query);
    },
    
    /**
     * Mettre en évidence les modules correspondants
     */
    highlightMatchingModules(query) {
        const moduleCards = document.querySelectorAll('.module-card');
        const queryLower = query.toLowerCase();
        
        moduleCards.forEach(card => {
            const title = card.querySelector('.module-title')?.textContent.toLowerCase() || '';
            const description = card.querySelector('.module-description')?.textContent.toLowerCase() || '';
            const features = Array.from(card.querySelectorAll('.feature-tag'))
                .map(tag => tag.textContent.toLowerCase()).join(' ');
            
            const matches = title.includes(queryLower) || 
                          description.includes(queryLower) || 
                          features.includes(queryLower);
            
            if (matches) {
                card.style.transform = 'scale(1.02)';
                card.style.boxShadow = '0 8px 25px rgba(37, 99, 235, 0.15)';
                card.style.borderColor = 'var(--primary-color)';
            } else {
                card.style.opacity = '0.6';
            }
        });
        
        // Restaurer après 3 secondes
        setTimeout(() => {
            this.resetModuleHighlight();
        }, 3000);
    },
    
    /**
     * Restaurer l'apparence des modules
     */
    resetModuleHighlight() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            card.style.transform = '';
            card.style.boxShadow = '';
            card.style.borderColor = '';
            card.style.opacity = '';
        });
    },
    
    /**
     * Afficher les résultats de recherche
     */
    showSearchResults(query) {
        // Simuler des résultats
        const results = this.getSearchResults(query);
        
        if (results.length === 0) {
            this.showSearchError(`Aucun résultat pour "${query}"`);
            return;
        }
        
        // Afficher dans une modal ou rediriger
        const firstResult = results[0];
        if (firstResult.url) {
            if (confirm(`Aller vers "${firstResult.title}" ?`)) {
                window.location.href = firstResult.url;
            }
        }
    },
    
    /**
     * Obtenir les résultats de recherche simulés
     */
    getSearchResults(query) {
        const searchData = [
            { title: 'Calculateur frais de port', url: 'calculateur/', keywords: ['calcul', 'tarif', 'transport', 'prix'] },
            { title: 'Gestion ADR', url: 'adr/', keywords: ['adr', 'dangereuses', 'marchandises', 'déclaration'] },
            { title: 'Contrôle qualité', url: 'controle-qualite/', keywords: ['contrôle', 'qualité', 'pompe', 'équipement'] },
            { title: 'Administration', url: 'admin/', keywords: ['admin', 'configuration', 'gestion', 'paramètres'] },
            { title: 'Nouveau contrôle pompe', url: 'controle-qualite/?controller=pompe-doseuse&action=nouveau', keywords: ['nouveau', 'contrôle', 'pompe', 'doseuse'] },
            { title: 'Gestion des tarifs', url: 'admin/rates.php', keywords: ['tarifs', 'prix', 'transporteur'] },
            { title: 'Import/Export', url: 'admin/import-export.php', keywords: ['import', 'export', 'données'] }
        ];
        
        const queryLower = query.toLowerCase();
        
        return searchData.filter(item => 
            item.title.toLowerCase().includes(queryLower) ||
            item.keywords.some(keyword => keyword.includes(queryLower))
        );
    },
    
    /**
     * Afficher une erreur de recherche
     */
    showSearchError(message) {
        if (window.Notifications) {
            Notifications.warning(message, 2000);
        } else {
            alert(message);
        }
    },
    
    /**
     * Initialiser les cartes de modules
     */
    initModuleCards() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            // Animation au survol
            card.addEventListener('mouseenter', () => {
                this.animateModuleCard(card, 'enter');
            });
            
            card.addEventListener('mouseleave', () => {
                this.animateModuleCard(card, 'leave');
            });
            
            // Clic sur la carte
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.btn')) {
                    const link = card.querySelector('.btn');
                    if (link) {
                        link.click();
                    }
                }
            });
        });
    },
    
    /**
     * Animer une carte de module
     */
    animateModuleCard(card, action) {
        const icon = card.querySelector('.module-icon');
        
        if (action === 'enter') {
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                icon.style.transition = 'transform 0.3s ease';
            }
        } else {
            if (icon) {
                icon.style.transform = '';
            }
        }
    },
    
    /**
     * Initialiser les actions rapides
     */
    initQuickActions() {
        const quickLinks = document.querySelectorAll('.quick-link');
        
        quickLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Animation de clic
                const icon = link.querySelector('.quick-link-icon');
                if (icon) {
                    icon.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        icon.style.transform = '';
                    }, 150);
                }
            });
        });
    },
    
    /**
     * Initialiser les raccourcis clavier
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+K ou Cmd+K pour la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('quickSearchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
                return;
            }
            
            // Raccourcis numériques pour les modules (Alt+1, Alt+2, etc.)
            if (e.altKey && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const moduleIndex = parseInt(e.key) - 1;
                const moduleCards = document.querySelectorAll('.module-card');
                if (moduleCards[moduleIndex]) {
                    const link = moduleCards[moduleIndex].querySelector('.btn');
                    if (link) {
                        link.click();
                    }
                }
                return;
            }
            
            // Escape pour réinitialiser
            if (e.key === 'Escape') {
                this.resetSearchAndHighlights();
            }
        });
    },
    
    /**
     * Réinitialiser la recherche et les surbrillances
     */
    resetSearchAndHighlights() {
        const searchInput = document.getElementById('quickSearchInput');
        if (searchInput && searchInput === document.activeElement) {
            searchInput.value = '';
            searchInput.blur();
        }
        
        this.resetModuleHighlight();
        this.state.lastSearch = '';
    },
    
    /**
     * Initialiser les mises à jour des statistiques
     */
    initStatsUpdates() {
        if (!window.PortalConfig?.debug) {
            this.state.statsTimer = setInterval(() => {
                this.updateStats();
            }, this.config.statsUpdateInterval);
        }
    },
    
    /**
     * Mettre à jour les statistiques
     */
    async updateStats() {
        try {
            console.log('📊 Mise à jour des statistiques...');
            
            // Simuler une mise à jour (remplacer par un appel API réel)
            const statElements = document.querySelectorAll('.stat-value, .stat-number, .stat-big');
            
            statElements.forEach(element => {
                const currentValue = parseInt(element.textContent) || 0;
                const variation = Math.floor(Math.random() * 3) - 1; // -1, 0, ou 1
                const newValue = Math.max(0, currentValue + variation);
                
                if (newValue !== currentValue) {
                    this.animateStatUpdate(element, newValue);
                }
            });
            
        } catch (error) {
            console.warn('⚠️ Erreur mise à jour stats:', error);
        }
    },
    
    /**
     * Animer la mise à jour d'une statistique
     */
    animateStatUpdate(element, newValue) {
        element.style.transition = 'all 0.3s ease';
        element.style.transform = 'scale(1.1)';
        element.style.color = 'var(--success-color)';
        
        setTimeout(() => {
            element.textContent = newValue;
            
            setTimeout(() => {
                element.style.transform = '';
                element.style.color = '';
            }, 150);
        }, 150);
    },
    
    /**
     * Initialiser les vérifications de santé
     */
    initHealthChecks() {
        if (!window.PortalConfig?.debug) {
            this.state.healthTimer = setInterval(() => {
                this.checkSystemHealth();
            }, this.config.healthCheckInterval);
        }
    },
    
    /**
     * Vérifier la santé du système
     */
    async checkSystemHealth() {
        try {
            console.log('🩺 Vérification santé système...');
            
            // Vérifier la connectivité
            const isOnline = navigator.onLine;
            
            // Vérifier la performance
            const performanceData = this.getPerformanceMetrics();
            
            // Mettre à jour l'indicateur de statut
            this.updateStatusIndicator(isOnline, performanceData);
            
        } catch (error) {
            console.warn('⚠️ Erreur vérification santé:', error);
        }
    },
    
    /**
     * Obtenir les métriques de performance
     */
    getPerformanceMetrics() {
        if (!window.performance) return {};
        
        const navigation = performance.getEntriesByType('navigation')[0];
        
        return {
            loadTime: navigation ? navigation.loadEventEnd - navigation.loadEventStart : 0,
            domContentLoaded: navigation ? navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart : 0,
            memory: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            } : null
        };
    },
    
    /**
     * Mettre à jour l'indicateur de statut
     */
    updateStatusIndicator(isOnline, performanceData) {
        const statusIndicator = document.querySelector('.status-indicator');
        const statusDot = document.querySelector('.status-dot');
        const statusText = document.querySelector('.status-text');
        
        if (!statusIndicator || !statusDot || !statusText) return;
        
        // Déterminer le statut
        let status = 'operational';
        let statusMessage = 'Système opérationnel';
        
        if (!isOnline) {
            status = 'error';
            statusMessage = 'Hors ligne';
        } else if (performanceData.loadTime > 3000) {
            status = 'partial';
            statusMessage = 'Performance dégradée';
        }
        
        // Mettre à jour l'interface
        statusIndicator.className = `status-indicator ${status}`;
        statusText.textContent = statusMessage;
        
        // Animation du point de statut
        statusDot.style.animation = 'none';
        setTimeout(() => {
            statusDot.style.animation = 'pulse 2s infinite';
        }, 10);
    },
    
    /**
     * Initialiser les animations
     */
    initAnimations() {
        // Observer pour les animations au scroll
        if ('IntersectionObserver' in window) {
            this.initScrollAnimations();
        }
        
        // Animations de chargement
        this.initLoadingAnimations();
    },
    
    /**
     * Initialiser les animations au scroll
     */
    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        // Observer les sections
        const sections = document.querySelectorAll('.modules-section, .activity-section, .quick-links-section');
        sections.forEach(section => {
            section.classList.add('animate-ready');
            observer.observe(section);
        });
    },
    
    /**
     * Initialiser les animations de chargement
     */
    initLoadingAnimations() {
        // Animation des cartes de modules
        const moduleCards = document.querySelectorAll('.module-card');
        moduleCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 + (index * 100));
        });
        
        // Animation des statistiques
        setTimeout(() => {
            this.animateStatsCountUp();
        }, 500);
    },
    
    /**
     * Animation de comptage des statistiques
     */
    animateStatsCountUp() {
        const statNumbers = document.querySelectorAll('.stat-value, .stat-number, .stat-big');
        
        statNumbers.forEach(element => {
            const finalValue = parseInt(element.textContent) || 0;
            if (finalValue === 0) return;
            
            let currentValue = 0;
            const increment = Math.ceil(finalValue / 30);
            const duration = 1000;
            const stepTime = duration / (finalValue / increment);
            
            element.textContent = '0';
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                element.textContent = currentValue;
            }, stepTime);
        });
    },
    
    /**
     * Recherche globale dans le portail
     */
    performGlobalSearch(query) {
        console.log('🔍 Recherche globale:', query);
        
        // Ici on pourrait implémenter une recherche réelle
        // Pour l'instant, on simule
        const results = this.getSearchResults(query);
        
        if (results.length > 0) {
            this.displaySearchModal(query, results);
        } else {
            this.showSearchError(`Aucun résultat trouvé pour "${query}"`);
        }
    },
    
    /**
     * Afficher la modal de résultats de recherche
     */
    displaySearchModal(query, results) {
        // Créer la modal si elle n'existe pas
        let modal = document.getElementById('search-results-modal');
        if (!modal) {
            modal = this.createSearchModal();
        }
        
        // Remplir avec les résultats
        const resultsContainer = modal.querySelector('.search-results');
        resultsContainer.innerHTML = '';
        
        results.forEach(result => {
            const resultElement = document.createElement('div');
            resultElement.className = 'search-result-item';
            resultElement.innerHTML = `
                <div class="result-title">${result.title}</div>
                <div class="result-url">${result.url}</div>
            `;
            
            resultElement.addEventListener('click', () => {
                window.location.href = result.url;
            });
            
            resultsContainer.appendChild(resultElement);
        });
        
        // Afficher la modal
        modal.style.display = 'flex';
        modal.classList.add('active');
    },
    
    /**
     * Créer la modal de recherche
     */
    createSearchModal() {
        const modal = document.createElement('div');
        modal.id = 'search-results-modal';
        modal.className = 'search-modal';
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Résultats de recherche</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="search-results"></div>
            </div>
        `;
        
        // Événements de fermeture
        modal.querySelector('.modal-close').addEventListener('click', () => {
            this.closeSearchModal(modal);
        });
        
        modal.querySelector('.modal-backdrop').addEventListener('click', () => {
            this.closeSearchModal(modal);
        });
        
        document.body.appendChild(modal);
        
        // Styles inline pour la modal
        const style = document.createElement('style');
        style.textContent = `
            .search-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1000;
                display: none;
                align-items: center;
                justify-content: center;
            }
            
            .modal-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .modal-content {
                position: relative;
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow: hidden;
            }
            
            .modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid var(--border-color);
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: var(--text-secondary);
            }
            
            .search-results {
                max-height: 400px;
                overflow-y: auto;
                padding: 1rem;
            }
            
            .search-result-item {
                padding: 1rem;
                border-radius: 8px;
                cursor: pointer;
                transition: background-color 0.2s ease;
                border-bottom: 1px solid var(--border-color);
            }
            
            .search-result-item:hover {
                background: var(--bg-secondary);
            }
            
            .result-title {
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.25rem;
            }
            
            .result-url {
                font-size: 0.875rem;
                color: var(--text-secondary);
            }
        `;
        
        document.head.appendChild(style);
        
        return modal;
    },
    
    /**
     * Fermer la modal de recherche
     */
    closeSearchModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    },
    
    /**
     * Vérifier la santé des modules
     */
    async checkModulesHealth() {
        const modules = window.PortalConfig?.modules || [];
        
        for (const module of modules) {
            try {
                // Simuler une vérification de santé
                const isHealthy = Math.random() > 0.1; // 90% de chance d'être OK
                
                console.log(`${isHealthy ? '✅' : '❌'} Module ${module}: ${isHealthy ? 'OK' : 'Erreur'}`);
                
                // Mettre à jour l'interface si nécessaire
                if (!isHealthy) {
                    this.markModuleAsUnhealthy(module);
                }
                
            } catch (error) {
                console.warn(`⚠️ Erreur vérification module ${module}:`, error);
            }
        }
    },
    
    /**
     * Marquer un module comme non opérationnel
     */
    markModuleAsUnhealthy(moduleName) {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            const title = card.querySelector('.module-title')?.textContent.toLowerCase();
            if (title && title.includes(moduleName.toLowerCase())) {
                // Ajouter un indicateur d'erreur
                let errorIndicator = card.querySelector('.module-error');
                if (!errorIndicator) {
                    errorIndicator = document.createElement('div');
                    errorIndicator.className = 'module-error';
                    errorIndicator.innerHTML = '⚠️ Service temporairement indisponible';
                    errorIndicator.style.cssText = `
                        background: rgba(239, 68, 68, 0.1);
                        color: var(--error-color);
                        padding: 0.5rem;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        margin-top: 1rem;
                        text-align: center;
                    `;
                    card.appendChild(errorIndicator);
                }
            }
        });
    },
    
    /**
     * Nettoyer les ressources
     */
    destroy() {
        if (this.state.statsTimer) {
            clearInterval(this.state.statsTimer);
        }
        
        if (this.state.healthTimer) {
            clearInterval(this.state.healthTimer);
        }
        
        if (this.state.searchTimeout) {
            clearTimeout(this.state.searchTimeout);
        }
        
        this.state.initialized = false;
        console.log('🏠 Portail nettoyé');
    }
};

// =============================================================================
// FONCTIONS GLOBALES
// =============================================================================

/**
 * Fonction globale pour la recherche rapide
 */
window.handleQuickSearch = function(event) {
    if (event) event.preventDefault();
    
    const input = document.getElementById('quickSearchInput');
    if (!input) return false;
    
    const query = input.value.trim();
    
    if (query.length < Portal.config.searchMinLength) {
        Portal.showSearchError(`Veuillez saisir au moins ${Portal.config.searchMinLength} caractères`);
        return false;
    }
    
    Portal.performSearch(query);
    return false;
};

/**
 * Définir une recherche prédéfinie
 */
window.setQuickSearch = function(value) {
    const searchInput = document.getElementById('quickSearchInput');
    if (searchInput) {
        searchInput.value = value;
        searchInput.focus();
        
        // Animation de mise en évidence
        searchInput.style.background = '#e0f2fe';
        setTimeout(() => {
            searchInput.style.background = '';
        }, 500);
    }
};

/**
 * Afficher les résultats de recherche
 */
window.showSearchResults = function(query) {
    Portal.performGlobalSearch(query);
};

// =============================================================================
// ENREGISTREMENT DU MODULE
// =============================================================================

// Enregistrer le module Portal dans le gestionnaire de modules
if (window.ModuleManager) {
    ModuleManager.register('portal', Portal);
}

// Auto-initialisation si le DOM est déjà chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Portal.init());
} else {
    Portal.init();
}

// Nettoyage avant déchargement
window.addEventListener('beforeunload', () => Portal.destroy());

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Portal;
}
