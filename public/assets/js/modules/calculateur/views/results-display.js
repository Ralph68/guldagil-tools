/**
 * Titre: Vue affichage résultats
 * Chemin: /public/assets/js/modules/calculateur/views/results-display.js
 * Version: 0.5 beta + build
 */

class ResultsDisplayView {
    constructor() {
        this.elements = {};
        this.currentState = 'empty';
        this.animationTimeout = null;
    }

    init() {
        this.cacheElements();
        this.setupObservers();
        this.bindEvents();
        CalculateurConfig.log('info', 'Results display view initialisée');
    }

    cacheElements() {
        this.elements = {
            // États
            empty: document.getElementById('results-empty'),
            loading: document.getElementById('results-loading'),
            display: document.getElementById('results-display'),
            error: document.getElementById('results-error'),
            
            // Meilleur tarif
            bestCard: document.getElementById('best-rate-card'),
            bestIcon: document.getElementById('best-carrier-icon'),
            bestName: document.getElementById('best-carrier-name'),
            bestDelay: document.getElementById('best-carrier-delay'),
            bestPrice: document.getElementById('best-price-value'),
            
            // Comparaison
            comparisonList: document.getElementById('comparison-list'),
            comparisonToggle: document.getElementById('btn-toggle-comparison'),
            
            // Suggestions
            suggestionsSection: document.getElementById('suggestions-section'),
            suggestionsList: document.getElementById('suggestions-list'),
            
            // Loading steps
            loadingSteps: document.querySelectorAll('.loading-step')
        };
    }

    setupObservers() {
        window.calculateurState?.observe('ui.isCalculating', (calculating) => {
            calculating ? this.showLoading() : this.hideLoading();
        });

        window.calculateurState?.observe('results', (results) => {
            this.updateResults(results);
        });
    }

    bindEvents() {
        this.elements.comparisonToggle?.addEventListener('click', this.toggleComparison.bind(this));
    }

    showEmpty() {
        this.switchState('empty');
    }

    showLoading() {
        this.switchState('loading');
        this.animateLoadingSteps();
    }

    hideLoading() {
        this.stopLoadingAnimation();
    }

    showResults(results) {
        this.switchState('display');
        this.renderResults(results);
    }

    showError(error) {
        this.switchState('error');
        this.renderError(error);
    }

    switchState(newState) {
        if (this.currentState === newState) return;

        // Masquer état actuel
        Object.values(this.elements).forEach(el => {
            if (el?.id?.startsWith('results-')) {
                el.style.display = 'none';
                el.classList.remove('visible');
            }
        });

        // Afficher nouvel état
        const targetEl = this.elements[newState];
        if (targetEl) {
            targetEl.style.display = 'block';
            requestAnimationFrame(() => targetEl.classList.add('visible'));
        }

        this.currentState = newState;
    }

    animateLoadingSteps() {
        let currentStep = 0;
        const steps = this.elements.loadingSteps;
        
        const animate = () => {
            if (currentStep < steps.length) {
                // Activer étape courante
                steps[currentStep]?.classList.add('active');
                
                // Compléter étape précédente
                if (currentStep > 0) {
                    steps[currentStep - 1]?.classList.remove('active');
                    steps[currentStep - 1]?.classList.add('completed');
                }
                
                currentStep++;
                this.animationTimeout = setTimeout(animate, 400);
            }
        };
        
        animate();
    }

    stopLoadingAnimation() {
        if (this.animationTimeout) {
            clearTimeout(this.animationTimeout);
            this.animationTimeout = null;
        }
        
        // Reset steps
        this.elements.loadingSteps.forEach(step => {
            step.classList.remove('active', 'completed');
        });
    }

    updateResults(results) {
        if (!results) {
            this.showEmpty();
            return;
        }

        if (results.error) {
            this.showError(results);
        } else if (results.carriers) {
            this.showResults(results);
        }
    }

    renderResults(results) {
        this.renderBestRate(results.bestRate);
        this.renderComparison(results.carriers);
        this.renderSuggestions(results.suggestions);
    }

    renderBestRate(bestRate) {
        if (!bestRate || !this.elements.bestCard) return;

        const { carrier, price, info, delay } = bestRate;
        
        if (this.elements.bestIcon) {
            this.elements.bestIcon.textContent = info.icon;
        }
        
        if (this.elements.bestName) {
            this.elements.bestName.textContent = info.name;
        }
        
        if (this.elements.bestDelay) {
            this.elements.bestDelay.textContent = delay;
        }
        
        if (this.elements.bestPrice) {
            this.animatePrice(this.elements.bestPrice, price);
        }

        // Animation carte
        this.elements.bestCard.classList.add('result-appear');
    }

    renderComparison(carriers) {
        if (!this.elements.comparisonList || !carriers) return;

        this.elements.comparisonList.innerHTML = '';

        Object.entries(carriers).forEach(([carrier, result], index) => {
            const item = this.createComparisonItem(carrier, result);
            this.elements.comparisonList.appendChild(item);
            
            // Animation échelonnée
            setTimeout(() => item.classList.add('fade-in'), index * 100);
        });
    }

    createComparisonItem(carrier, result) {
        const info = CalculateurConfig.getCarrierInfo(carrier);
        const available = result && typeof result === 'number';
        
        const item = document.createElement('div');
        item.className = `comparison-item ${available ? '' : 'unavailable'}`;
        
        item.innerHTML = `
            <div class="comparison-carrier">
                <div class="comparison-icon">${info.icon}</div>
                <div class="comparison-name">${info.name}</div>
            </div>
            <div class="comparison-price">
                ${available ? 
                    `<span class="price">${result.toFixed(2)} €</span>` : 
                    '<span class="unavailable-text">Non disponible</span>'
                }
            </div>
        `;

        if (available) {
            item.addEventListener('click', () => this.selectCarrier(carrier, result));
            item.style.cursor = 'pointer';
        }

        return item;
    }

    renderSuggestions(suggestions) {
        if (!suggestions?.length) {
            this.elements.suggestionsSection.style.display = 'none';
            return;
        }

        this.elements.suggestionsSection.style.display = 'block';
        this.elements.suggestionsList.innerHTML = '';

        suggestions.forEach((suggestion, index) => {
            const item = this.createSuggestionItem(suggestion);
            this.elements.suggestionsList.appendChild(item);
            
            setTimeout(() => item.classList.add('slide-in'), index * 150);
        });
    }

    createSuggestionItem(suggestion) {
        const item = document.createElement('div');
        item.className = `suggestion-item ${suggestion.priority || 'medium'}`;
        
        item.innerHTML = `
            <div class="suggestion-icon">${this.getSuggestionIcon(suggestion.type)}</div>
            <div class="suggestion-content">
                <div class="suggestion-title">${suggestion.title || 'Suggestion'}</div>
                <div class="suggestion-text">${suggestion.message}</div>
            </div>
        `;

        if (suggestion.action) {
            item.addEventListener('click', suggestion.action);
            item.style.cursor = 'pointer';
            item.classList.add('clickable');
        }

        return item;
    }

    getSuggestionIcon(type) {
        const icons = {
            weight_optimization: '⚖️',
            service_compatibility: '⚠️',
            cost_optimization: '💰',
            shipping_type: '📦',
            palette_count: '🏗️'
        };
        return icons[type] || '💡';
    }

    renderError(error) {
        const messageEl = document.getElementById('error-message');
        if (messageEl) {
            messageEl.textContent = error.message || 'Une erreur est survenue';
        }
    }

    animatePrice(element, targetPrice) {
        if (!element) return;

        const startPrice = parseFloat(element.textContent) || 0;
        const duration = 800;
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const easedProgress = this.easeOutCubic(progress);
            const currentPrice = startPrice + (targetPrice - startPrice) * easedProgress;
            
            element.textContent = currentPrice.toFixed(2);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    toggleComparison() {
        const list = this.elements.comparisonList;
        const toggle = this.elements.comparisonToggle;
        
        if (!list || !toggle) return;

        const isExpanded = list.classList.contains('expanded');
        
        list.classList.toggle('expanded');
        toggle.classList.toggle('expanded');
        
        const toggleText = toggle.querySelector('.toggle-text');
        const toggleIcon = toggle.querySelector('.toggle-icon');
        
        if (toggleText) {
            toggleText.textContent = isExpanded ? 'Afficher tout' : 'Masquer';
        }
        
        if (toggleIcon) {
            toggleIcon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }

    selectCarrier(carrier, price) {
        const info = CalculateurConfig.getCarrierInfo(carrier);
        
        // Animation sélection
        const items = this.elements.comparisonList.querySelectorAll('.comparison-item');
        items.forEach(item => {
            const carrierName = item.querySelector('.comparison-name').textContent;
            if (carrierName === info.name) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });

        // Notification
        window.uiController?.showToast(
            `${info.name} sélectionné - ${price.toFixed(2)}€`, 
            'success'
        );

        CalculateurConfig.log('info', `Carrier selected: ${carrier} - ${price}€`);
    }

    reset() {
        this.showEmpty();
        this.stopLoadingAnimation();
        
        // Reset animations
        document.querySelectorAll('.result-appear, .fade-in, .slide-in').forEach(el => {
            el.classList.remove('result-appear', 'fade-in', 'slide-in');
        });
    }
}

window.resultsDisplayView = new ResultsDisplayView();
