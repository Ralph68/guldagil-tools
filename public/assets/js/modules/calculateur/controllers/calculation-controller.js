/**
 * Titre: Contrôleur de calcul des frais de port
 * Chemin: /public/assets/js/modules/calculateur/controllers/calculation-controller.js
 * Version: 0.5 beta + build
 */

class CalculationController {
    constructor() {
        this.currentRequest = null;
        this.calculationHistory = [];
        this.lastCalculationTime = null;
        this.bindMethods();
    }

    bindMethods() {
        this.calculate = this.calculate.bind(this);
        this.cancelCalculation = this.cancelCalculation.bind(this);
        this.handleCalculationResult = this.handleCalculationResult.bind(this);
        this.handleCalculationError = this.handleCalculationError.bind(this);
    }

    init() {
        this.setupStateObservers();
        CalculateurConfig.log('info', 'Calculation controller initialisé');
    }

    setupStateObservers() {
        // Observer les changements de données pour calcul automatique
        window.calculateurState?.observe('formData', (newData, oldData) => {
            if (this.shouldTriggerCalculation(newData, oldData)) {
                this.scheduleCalculation();
            }
        });

        // Observer validation globale
        window.calculateurState?.observe('validation.isValid', (isValid) => {
            if (isValid) {
                this.scheduleCalculation();
            } else {
                this.cancelCalculation();
                window.calculateurState?.clearResults();
            }
        });
    }

    shouldTriggerCalculation(newData, oldData) {
        if (!newData || !this.isMinimalDataComplete(newData)) {
            return false;
        }

        // Vérifier si données critiques ont changé
        const criticalFields = ['departement', 'poids', 'type'];
        return criticalFields.some(field => newData[field] !== oldData?.[field]);
    }

    isMinimalDataComplete(data) {
        return data.departement && 
               data.poids && 
               data.type &&
               CalculateurConfig.VALIDATION.DEPT_PATTERN.test(data.departement) &&
               data.poids >= CalculateurConfig.VALIDATION.MIN_POIDS;
    }

    scheduleCalculation() {
        // Annuler calcul précédent
        this.cancelCalculation();

        // Programmer nouveau calcul après délai
        this.calculationTimeout = setTimeout(() => {
            this.calculate();
        }, CalculateurConfig.TIMING.AUTO_CALC_DELAY);
    }

    async calculate(force = false) {
        try {
            const formData = window.calculateurState?.get('formData');
            
            if (!force && !this.isMinimalDataComplete(formData)) {
                CalculateurConfig.log('debug', 'Données insuffisantes pour calcul');
                return;
            }

            // Annuler requête en cours
            this.cancelCalculation();

            // Marquer début de calcul
            window.calculateurState?.setCalculating(true);
            this.lastCalculationTime = Date.now();

            CalculateurConfig.log('info', 'Début calcul:', formData);

            // Envoyer requête
            const response = await window.apiService.calculate(formData);
            
            // Traiter résultat
            this.handleCalculationResult(response, formData);

        } catch (error) {
            this.handleCalculationError(error, formData);
        } finally {
            window.calculateurState?.setCalculating(false);
        }
    }

    handleCalculationResult(response, requestData) {
        // Vérifier que requête toujours valide
        if (!this.isResponseValid(response, requestData)) {
            return;
        }

        // Enrichir réponse
        const enrichedResponse = this.enrichResponse(response, requestData);

        // Mettre à jour état
        window.calculateurState?.updateResults(enrichedResponse);

        // Sauvegarder historique
        this.saveToHistory(requestData, enrichedResponse);

        // Analyser pour suggestions
        this.generateSuggestions(enrichedResponse, requestData);

        CalculateurConfig.log('info', 'Calcul terminé:', enrichedResponse);
    }

    handleCalculationError(error, requestData) {
        CalculateurConfig.log('error', 'Erreur calcul:', error);

        // Analyser type d'erreur
        const errorInfo = this.analyzeError(error);

        // Mettre à jour état avec erreur
        window.calculateurState?.set('results', {
            error: true,
            message: errorInfo.userMessage,
            type: errorInfo.type,
            retryable: errorInfo.retryable,
            timestamp: new Date().toISOString()
        });

        // Afficher notification si nécessaire
        if (errorInfo.showNotification) {
            window.showFooterToast?.(errorInfo.userMessage, 'error');
        }
    }

    isResponseValid(response, requestData) {
        // Vérifier que les données n'ont pas changé pendant le calcul
        const currentData = window.calculateurState?.get('formData');
        
        const criticalFields = ['departement', 'poids', 'type'];
        return criticalFields.every(field => 
            currentData?.[field] === requestData[field]
        );
    }

    enrichResponse(response, requestData) {
        return {
            ...response,
            request: requestData,
            calculationTime: Date.now() - this.lastCalculationTime,
            savings: this.calculateSavings(response.carriers),
            recommendations: this.generateRecommendations(response, requestData)
        };
    }

    calculateSavings(carriers) {
        const prices = Object.values(carriers)
            .filter(result => result && typeof result === 'number')
            .sort((a, b) => a - b);

        if (prices.length < 2) return null;

        return {
            amount: prices[prices.length - 1] - prices[0],
            percentage: ((prices[prices.length - 1] - prices[0]) / prices[prices.length - 1] * 100).toFixed(1)
        };
    }

    generateRecommendations(response, requestData) {
        const recommendations = [];

        // Recommandation poids optimal
        if (requestData.poids && requestData.poids % 100 !== 0) {
            const nextHundred = Math.ceil(requestData.poids / 100) * 100;
            if (nextHundred - requestData.poids <= 50) {
                recommendations.push({
                    type: 'weight_optimization',
                    title: 'Optimisation poids',
                    message: `Atteignez ${nextHundred}kg pour bénéficier du tarif au centaine`,
                    priority: 'medium'
                });
            }
        }

        // Recommandation ADR + Premium
        if (requestData.adr && requestData.service_livraison.includes('star')) {
            recommendations.push({
                type: 'service_compatibility',
                title: 'Service incompatible',
                message: 'Les services Star ne sont pas compatibles avec l\'ADR',
                priority: 'high'
            });
        }

        // Recommandation économique
        const savings = this.calculateSavings(response.carriers);
        if (savings && savings.amount > 20) {
            recommendations.push({
                type: 'cost_optimization',
                title: 'Économie possible',
                message: `Économisez ${savings.amount.toFixed(2)}€ (${savings.percentage}%) en choisissant le meilleur tarif`,
                priority: 'high'
            });
        }

        return recommendations;
    }

    generateSuggestions(response, requestData) {
        const suggestions = [];

        // Suggestion type envoi
        if (requestData.type === 'colis' && requestData.poids > 30) {
            suggestions.push({
                type: 'shipping_type',
                message: 'Avec ce poids, une palette pourrait être plus économique',
                action: () => this.suggestTypeChange('palette')
            });
        }

        // Suggestion palettes
        if (requestData.type === 'palette' && requestData.palettes === 0) {
            suggestions.push({
                type: 'palette_count',
                message: 'Indiquez le nombre de palettes pour un calcul précis',
                action: () => this.focusField('palettes')
            });
        }

        // Mettre à jour suggestions dans l'état
        window.calculateurState?.set('results.suggestions', suggestions);
    }

    suggestTypeChange(newType) {
        if (confirm(`Changer le type d'envoi vers "${newType}" ?`)) {
            window.formController?.setFieldValue('type', newType);
        }
    }

    focusField(fieldName) {
        const field = document.getElementById(fieldName);
        if (field) {
            field.focus();
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    analyzeError(error) {
        const info = {
            type: 'unknown',
            userMessage: 'Erreur de calcul',
            retryable: true,
            showNotification: false
        };

        if (error.name === 'AbortError') {
            info.type = 'cancelled';
            info.userMessage = 'Calcul annulé';
            info.retryable = false;
            info.showNotification = false;
        } else if (error.message?.includes('Timeout')) {
            info.type = 'timeout';
            info.userMessage = 'Délai dépassé - Vérifiez votre connexion';
            info.retryable = true;
            info.showNotification = true;
        } else if (error.message?.includes('Network')) {
            info.type = 'network';
            info.userMessage = 'Problème de connexion';
            info.retryable = true;
            info.showNotification = true;
        } else if (error.message?.includes('HTTP')) {
            info.type = 'server';
            info.userMessage = 'Erreur serveur temporaire';
            info.retryable = true;
            info.showNotification = true;
        } else if (error.userMessage) {
            info.userMessage = error.userMessage;
            info.showNotification = true;
        }

        return info;
    }

    saveToHistory(requestData, response) {
        const historyEntry = {
            timestamp: Date.now(),
            request: { ...requestData },
            response: {
                carriers: response.carriers,
                bestRate: response.bestRate,
                calculationTime: response.calculationTime
            }
        };

        this.calculationHistory.unshift(historyEntry);

        // Limiter historique
        if (this.calculationHistory.length > 50) {
            this.calculationHistory = this.calculationHistory.slice(0, 50);
        }
    }

    cancelCalculation() {
        // Annuler timeout
        if (this.calculationTimeout) {
            clearTimeout(this.calculationTimeout);
            this.calculationTimeout = null;
        }

        // Annuler requête API
        if (window.apiService) {
            window.apiService.cancelPendingRequests();
        }
    }

    retryLastCalculation() {
        const lastRequest = this.calculationHistory[0]?.request;
        if (lastRequest) {
            this.calculate(true);
        }
    }

    getCalculationStats() {
        return {
            totalCalculations: this.calculationHistory.length,
            averageTime: this.getAverageCalculationTime(),
            lastCalculation: this.lastCalculationTime,
            successRate: this.getSuccessRate()
        };
    }

    getAverageCalculationTime() {
        if (this.calculationHistory.length === 0) return 0;
        
        const totalTime = this.calculationHistory.reduce((sum, entry) => 
            sum + (entry.response.calculationTime || 0), 0);
        
        return Math.round(totalTime / this.calculationHistory.length);
    }

    getSuccessRate() {
        if (this.calculationHistory.length === 0) return 100;
        
        const successful = this.calculationHistory.filter(entry => 
            !entry.response.error).length;
        
        return Math.round((successful / this.calculationHistory.length) * 100);
    }

    // Calculs spécialisés
    calculateWeightOptimization(currentWeight, carriers) {
        const optimizations = [];
        const weightThresholds = [100, 300, 500, 1000, 2000];
        
        weightThresholds.forEach(threshold => {
            if (currentWeight < threshold && threshold - currentWeight <= 100) {
                optimizations.push({
                    targetWeight: threshold,
                    difference: threshold - currentWeight,
                    savings: 'À calculer' // Nécessiterait un nouveau calcul
                });
            }
        });
        
        return optimizations;
    }

    calculateSeasonalImpact(requestData) {
        const now = new Date();
        const isHighSeason = [6, 7].includes(now.getMonth()); // Juillet-Août
        const isWinterSeason = [11, 0, 1, 2].includes(now.getMonth()); // Dec-Mar
        
        const impacts = [];
        
        if (isHighSeason) {
            impacts.push({
                type: 'high_season',
                description: 'Période haute saison (été)',
                impact: 'Surcoût possible sur certains transporteurs'
            });
        }
        
        if (isWinterSeason && ['04', '05', '73', '74'].includes(requestData.departement)) {
            impacts.push({
                type: 'winter_restrictions',
                description: 'Restrictions hivernales montagne',
                impact: 'Délais allongés possibles'
            });
        }
        
        return impacts;
    }

    // Analyse comparative
    compareCarriers(carriers) {
        const comparison = {
            available: [],
            unavailable: [],
            priceRange: { min: null, max: null },
            recommendations: []
        };
        
        Object.entries(carriers).forEach(([carrier, result]) => {
            const carrierInfo = CalculateurConfig.getCarrierInfo(carrier);
            
            if (result && typeof result === 'number') {
                comparison.available.push({
                    carrier,
                    price: result,
                    info: carrierInfo
                });
                
                if (comparison.priceRange.min === null || result < comparison.priceRange.min) {
                    comparison.priceRange.min = result;
                }
                if (comparison.priceRange.max === null || result > comparison.priceRange.max) {
                    comparison.priceRange.max = result;
                }
            } else {
                comparison.unavailable.push({
                    carrier,
                    info: carrierInfo,
                    reason: 'Non disponible'
                });
            }
        });
        
        // Trier par prix
        comparison.available.sort((a, b) => a.price - b.price);
        
        // Générer recommandations
        if (comparison.available.length > 1) {
            const cheapest = comparison.available[0];
            const mostExpensive = comparison.available[comparison.available.length - 1];
            const savings = mostExpensive.price - cheapest.price;
            
            if (savings > 10) {
                comparison.recommendations.push({
                    type: 'price_difference',
                    message: `${savings.toFixed(2)}€ d'écart entre le plus cher et le moins cher`
                });
            }
        }
        
        return comparison;
    }

    // Gestion affichage temps réel
    updateCalculationProgress(step, total) {
        const progressElement = document.getElementById('status-progress');
        if (progressElement) {
            const percentage = ((step + 1) / total) * 100;
            progressElement.style.setProperty('--progress', `${percentage}%`);
        }
        
        // Mettre à jour étapes de chargement
        const loadingSteps = document.querySelectorAll('.loading-step');
        loadingSteps.forEach((stepEl, index) => {
            stepEl.classList.remove('active', 'completed');
            if (index < step) {
                stepEl.classList.add('completed');
            } else if (index === step) {
                stepEl.classList.add('active');
            }
        });
    }

    // Export données
    exportCalculationData(format = 'json') {
        const data = {
            request: window.calculateurState?.get('formData'),
            results: window.calculateurState?.get('results'),
            timestamp: new Date().toISOString(),
            version: CalculateurConfig.META.VERSION
        };
        
        switch (format) {
            case 'json':
                return JSON.stringify(data, null, 2);
            case 'csv':
                return this.convertToCSV(data);
            default:
                return data;
        }
    }

    convertToCSV(data) {
        const carriers = data.results?.carriers || {};
        const rows = ['Transporteur,Prix,Disponible,Délai'];
        
        Object.entries(carriers).forEach(([carrier, result]) => {
            const carrierInfo = CalculateurConfig.getCarrierInfo(carrier);
            rows.push([
                carrierInfo.name,
                typeof result === 'number' ? result.toFixed(2) : 'N/A',
                typeof result === 'number' ? 'Oui' : 'Non',
                '24-48h' // Délai par défaut
            ].join(','));
        });
        
        return rows.join('\n');
    }

    cleanup() {
        this.cancelCalculation();
        this.calculationHistory = [];
    }
}

// Export global
window.calcController = new CalculationController();
