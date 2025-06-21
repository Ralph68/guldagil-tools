/**
 * Titre: Contrôleur interactions interface
 * Chemin: /public/assets/js/modules/calculateur/controllers/ui-controller.js
 * Version: 0.5 beta + build
 */

class UIController {
    constructor() {
        this.elements = {};
        this.animations = new Map();
        this.toasts = [];
        this.bindMethods();
    }

    bindMethods() {
        this.showToast = this.showToast.bind(this);
        this.updateResults = this.updateResults.bind(this);
        this.toggleComparison = this.toggleComparison.bind(this);
        this.showDetails = this.showDetails.bind(this);
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.setupStateObservers();
        this.initializeInterface();
        
        CalculateurConfig.log('info', 'UI controller initialisé');
    }

    cacheElements() {
        this.elements = {
            // États résultats
            resultsEmpty: document.getElementById('results-empty'),
            resultsLoading: document.getElementById('results-loading'),
            resultsDisplay: document.getElementById('results-display'),
            resultsError: document.getElementById('results-error'),
            
            // Statut
            resultsStatus: document.getElementById('results-status'),
            statusProgress: document.getElementById('status-progress'),
            
            // Meilleur tarif
            bestRateCard: document.getElementById('best-rate-card'),
            bestCarrierIcon: document.getElementById('best-carrier-icon'),
            bestCarrierName: document.getElementById('best-carrier-name'),
            bestCarrierDelay: document.getElementById('best-carrier-delay'),
            bestPriceValue: document.getElementById('best-price-value'),
            
            // Comparaison
            comparisonList: document.getElementById('comparison-list'),
            btnToggleComparison: document.getElementById('btn-toggle-comparison'),
            
            // Suggestions
            suggestionsSection: document.getElementById('suggestions-section'),
            suggestionsList: document.getElementById('suggestions-list'),
            
            // Actions
            btnSelectBest: document.getElementById('btn-select-best'),
            btnDetailsBest: document.getElementById('btn-details-best'),
            btnRetry: document.getElementById('btn-retry'),
            
            // Détails
            detailsPanel: document.getElementById('details-panel'),
            detailsContent: document.getElementById('details-content'),
            btnCloseDetails: document.getElementById('btn-close-details'),
            
            // Stats
            statCalculations: document.getElementById('stat-calculations'),
            statTime: document.getElementById('stat-time'),
            statSavings: document.getElementById('stat-savings')
        };
    }

    bindEvents() {
        // Actions principales
        this.elements.btnToggleComparison?.addEventListener('click', this.toggleComparison);
        this.elements.btnDetailsBest?.addEventListener('click', () => this.showDetails('best'));
        this.elements.btnSelectBest?.addEventListener('click', this.selectBestRate);
        this.elements.btnRetry?.addEventListener('click', this.retryCalculation);
        this.elements.btnCloseDetails?.addEventListener('click', this.hideDetails);
        
        // Actions rapides
        document.getElementById('btn-export')?.addEventListener('click', this.exportResults);
        document.getElementById('btn-print')?.addEventListener('click', this.printResults);
        document.getElementById('btn-share')?.addEventListener('click', this.shareResults);
        document.getElementById('btn-new-calc')?.addEventListener('click', this.newCalculation);
        
        // Clavier
        document.addEventListener('keydown', this.handleKeyboard.bind(this));
        
        // Gestion responsive
        window.addEventListener('resize', this.handleResize.bind(this));
    }

    setupStateObservers() {
        // Observer état calcul
        window.calculateurState?.observe('ui.isCalculating', (isCalculating) => {
            this.updateCalculatingState(isCalculating);
        });
        
        // Observer résultats
        window.calculateurState?.observe('results', (results) => {
            this.updateResults(results);
        });
        
        // Observer affichage résultats
        window.calculateurState?.observe('ui.showResults', (showResults) => {
            this.toggleResultsDisplay(showResults);
        });
        
        // Observer étape courante
        window.calculateurState?.observe('ui.currentStep', (step) => {
            this.updateStepIndication(step);
        });
    }

    initializeInterface() {
        this.showResultsState('empty');
        this.updateStats();
        this.createToastContainer();
    }

    updateCalculatingState(isCalculating) {
        if (isCalculating) {
            this.showResultsState('loading');
            this.animateProgressSteps();
        }
    }

    showResultsState(state) {
        // Masquer tous les états
        Object.values(this.elements).forEach(el => {
            if (el?.id?.startsWith('results-')) {
                el.style.display = 'none';
            }
        });
        
        // Afficher état demandé
        const targetElement = this.elements[`results${state.charAt(0).toUpperCase() + state.slice(1)}`];
        if (targetElement) {
            targetElement.style.display = 'block';
            targetElement.classList.add('fade-in');
        }
    }

    updateResults(results) {
        if (!results) return;
        
        if (results.error) {
            this.showError(results);
        } else if (results.carriers) {
            this.showSuccess(results);
        }
    }

    showSuccess(results) {
        this.showResultsState('display');
        
        // Meilleur tarif
        if (results.bestRate) {
            this.updateBestRate(results.bestRate);
        }
        
        // Comparaison
        this.updateComparison(results.carriers);
        
        // Suggestions
        if (results.suggestions?.length > 0) {
            this.updateSuggestions(results.suggestions);
        }
        
        // Stats
        this.updateStats(results);
        
        // Animation
        this.animateResults();
    }

    updateBestRate(bestRate) {
        if (!this.elements.bestRateCard) return;
        
        const { carrier, price, info, delay } = bestRate;
        
        if (this.elements.bestCarrierIcon) {
            this.elements.bestCarrierIcon.textContent = info.icon;
        }
        
        if (this.elements.bestCarrierName) {
            this.elements.bestCarrierName.textContent = info.name;
        }
        
        if (this.elements.bestCarrierDelay) {
            this.elements.bestCarrierDelay.textContent = delay;
        }
        
        if (this.elements.bestPriceValue) {
            this.animatePrice(this.elements.bestPriceValue, price);
        }
    }

    updateComparison(carriers) {
        if (!this.elements.comparisonList) return;
        
        this.elements.comparisonList.innerHTML = '';
        
        Object.entries(carriers).forEach(([carrier, result]) => {
            const item = this.createComparisonItem(carrier, result);
            this.elements.comparisonList.appendChild(item);
        });
    }

    createComparisonItem(carrier, result) {
        const item = document.createElement('div');
        const info = CalculateurConfig.getCarrierInfo(carrier);
        const available = result && typeof result === 'number';
        
        item.className = `comparison-item ${available ? '' : 'unavailable'}`;
        item.innerHTML = `
            <div class="comparison-carrier">
                <div class="comparison-icon">${info.icon}</div>
                <div class="comparison-name">${info.name}</div>
            </div>
            <div class="comparison-price">
                ${available ? `${result.toFixed(2)} €` : 'Non disponible'}
            </div>
        `;
        
        if (available) {
            item.addEventListener('click', () => this.selectCarrier(carrier, result));
        }
        
        return item;
    }

    updateSuggestions(suggestions) {
        if (!this.elements.suggestionsList) return;
        
        this.elements.suggestionsSection.style.display = 'block';
        this.elements.suggestionsList.innerHTML = '';
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `<p class="suggestion-text">${suggestion.message}</p>`;
            
            if (suggestion.action) {
                item.addEventListener('click', suggestion.action);
                item.style.cursor = 'pointer';
            }
            
            this.elements.suggestionsList.appendChild(item);
        });
    }

    showError(error) {
        this.showResultsState('error');
        
        const messageEl = document.getElementById('error-message');
        if (messageEl) {
            messageEl.textContent = error.message || 'Erreur inconnue';
        }
        
        this.showToast(error.message, 'error');
    }

    animateResults() {
        const elements = [
            this.elements.bestRateCard,
            this.elements.comparisonList,
            this.elements.suggestionsSection
        ].filter(Boolean);
        
        elements.forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('result-appear');
            }, index * 100);
        });
    }

    animatePrice(element, newPrice) {
        if (!element) return;
        
        const currentPrice = parseFloat(element.textContent) || 0;
        const duration = 600;
        const start = performance.now();
        
        const animate = (timestamp) => {
            const elapsed = timestamp - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const eased = this.easeOutQuart(progress);
            const interpolated = currentPrice + (newPrice - currentPrice) * eased;
            
            element.textContent = interpolated.toFixed(2);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }

    animateProgressSteps() {
        const steps = document.querySelectorAll('.loading-step');
        let current = 0;
        
        const animate = () => {
            if (current < steps.length) {
                steps[current]?.classList.add('active');
                if (current > 0) {
                    steps[current - 1]?.classList.remove('active');
                    steps[current - 1]?.classList.add('completed');
                }
                current++;
                setTimeout(animate, 300);
            }
        };
        
        animate();
    }

    toggleComparison() {
        const list = this.elements.comparisonList;
        const btn = this.elements.btnToggleComparison;
        
        if (!list || !btn) return;
        
        const isExpanded = list.classList.contains('expanded');
        
        list.classList.toggle('expanded');
        btn.classList.toggle('expanded');
        
        const toggleText = btn.querySelector('.toggle-text');
        if (toggleText) {
            toggleText.textContent = isExpanded ? 'Afficher tout' : 'Masquer';
        }
    }

    showDetails(type = 'all') {
        if (!this.elements.detailsPanel) return;
        
        const results = window.calculateurState?.get('results');
        if (!results) return;
        
        this.elements.detailsContent.innerHTML = this.formatDebugInfo(results.debug);
        this.elements.detailsPanel.classList.add('visible');
        this.elements.detailsPanel.style.display = 'flex';
    }

    hideDetails() {
        if (!this.elements.detailsPanel) return;
        
        this.elements.detailsPanel.classList.remove('visible');
        setTimeout(() => {
            this.elements.detailsPanel.style.display = 'none';
        }, 300);
    }

    formatDebugInfo(debug) {
        if (!debug) return '<p>Aucune information de debug disponible</p>';
        
        return Object.entries(debug).map(([carrier, info]) => {
            const carrierInfo = CalculateurConfig.getCarrierInfo(carrier);
            return `
                <div class="debug-carrier">
                    <h5>${carrierInfo.name}</h5>
                    <pre>${JSON.stringify(info, null, 2)}</pre>
                </div>
            `;
        }).join('');
    }

    selectBestRate() {
        const bestRate = window.calculateurState?.get('results.bestRate');
        if (bestRate) {
            this.selectCarrier(bestRate.carrier, bestRate.price);
        }
    }

    selectCarrier(carrier, price) {
        const info = CalculateurConfig.getCarrierInfo(carrier);
        this.showToast(`${info.name} sélectionné - ${price.toFixed(2)}€`, 'success');
        
        // Ici on pourrait déclencher une action (redirection, modal, etc.)
        CalculateurConfig.log('info', `Transporteur sélectionné: ${carrier} - ${price}€`);
    }

    retryCalculation() {
        window.calcController?.retryLastCalculation();
    }

    newCalculation() {
        if (confirm('Recommencer un nouveau calcul ?')) {
            window.calculateurState?.reset();
            this.showResultsState('empty');
        }
    }

    exportResults() {
        const data = window.calcController?.exportCalculationData('json');
        if (data) {
            this.downloadFile('calcul-frais-port.json', data, 'application/json');
            this.showToast('Résultats exportés', 'success');
        }
    }

    printResults() {
        window.print();
    }

    shareResults() {
        const formData = window.calculateurState?.get('formData');
        if (!formData) return;
        
        const url = new URL(window.location);
        url.searchParams.set('dept', formData.departement);
        url.searchParams.set('poids', formData.poids);
        url.searchParams.set('type', formData.type);
        
        if (navigator.share) {
            navigator.share({
                title: 'Calcul frais de port',
                url: url.toString()
            });
        } else {
            navigator.clipboard.writeText(url.toString());
            this.showToast('Lien copié dans le presse-papier', 'success');
        }
    }

    downloadFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        
        URL.revokeObjectURL(url);
    }

    updateStats(results = null) {
        const stats = window.calcController?.getCalculationStats() || {};
        
        if (this.elements.statCalculations) {
            this.elements.statCalculations.textContent = stats.totalCalculations || 0;
        }
        
        if (this.elements.statTime && stats.averageTime) {
            this.elements.statTime.textContent = `${stats.averageTime}ms`;
        }
        
        if (this.elements.statSavings && results?.savings) {
            this.elements.statSavings.textContent = `${results.savings.amount.toFixed(2)}€`;
        }
    }

    createToastContainer() {
        if (document.querySelector('.toast-container')) return;
        
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    showToast(message, type = 'info', duration = 4000) {
        const container = document.querySelector('.toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">×</button>
        `;
        
        container.appendChild(toast);
        
        // Animation entrée
        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });
        
        // Fermeture automatique
        const closeToast = () => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        };
        
        toast.querySelector('.toast-close').addEventListener('click', closeToast);
        setTimeout(closeToast, duration);
        
        this.toasts.push(toast);
    }

    handleKeyboard(event) {
        switch (event.key) {
            case 'Escape':
                if (this.elements.detailsPanel?.classList.contains('visible')) {
                    this.hideDetails();
                    event.preventDefault();
                }
                break;
            case 'r':
                if (event.ctrlKey || event.metaKey) {
                    this.retryCalculation();
                    event.preventDefault();
                }
                break;
        }
    }

    handleResize() {
        // Ajuster interface pour mobile
        const isMobile = window.innerWidth < 768;
        document.body.classList.toggle('mobile-layout', isMobile);
    }

    updateStepIndication(step) {
        const statusText = this.elements.resultsStatus?.querySelector('.status-text');
        if (statusText) {
            const stepName = CalculateurConfig.UI.PROGRESS_STEPS[step]?.label;
            statusText.textContent = `Étape ${step + 1}: ${stepName}`;
        }
    }

    toggleResultsDisplay(show) {
        if (show) {
            this.showResultsState('display');
        } else {
            this.showResultsState('empty');
        }
    }
}

// Export global
window.uiController = new UIController();
