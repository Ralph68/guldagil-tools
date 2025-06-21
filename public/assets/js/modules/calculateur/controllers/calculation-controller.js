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
