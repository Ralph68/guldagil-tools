/**
 * Titre: Service API pour calculs de frais de port
 * Chemin: /public/assets/js/modules/calculateur/core/api-service.js
 * Version: 0.5 beta + build
 * 
 * Service centralisé pour communication avec ajax-calculate.php
 */

class ApiService {
    constructor() {
        this.baseUrl = '';
        this.abortController = null;
        this.requestQueue = new Map();
        this.retryAttempts = new Map();
        
        // Statistiques
        this.stats = {
            totalRequests: 0,
            successfulRequests: 0,
            failedRequests: 0,
            averageResponseTime: 0
        };
        
        CalculateurConfig.log('info', 'Service API initialisé');
    }

    init() {
        console.log('API Service initialisé');
    }
    /**
     * Calculer les frais de port
     * @param {object} formData - Données du formulaire
     * @returns {Promise<object>} Résultats du calcul
     */
    async calculate(formData) {
        const requestId = this.generateRequestId();
        
        try {
            // Annuler les requêtes en cours
            this.cancelPendingRequests();
            
            // Valider les données
            this.validateFormData(formData);
            
            // Normaliser les données
            const normalizedData = this.normalizeFormData(formData);
            
            CalculateurConfig.log('info', 'Envoi calcul:', normalizedData);
            
            // Envoyer la requête
            const response = await this.sendRequest(requestId, normalizedData);
            
            // Traiter la réponse
            const processedResponse = this.processResponse(response);
            
            this.updateStats(true, response.metadata?.processing_time);
            
            CalculateurConfig.log('info', 'Calcul réussi:', processedResponse);
            
            return processedResponse;
            
        } catch (error) {
            this.updateStats(false);
            this.handleError(error, requestId);
            throw error;
        } finally {
            this.requestQueue.delete(requestId);
            this.retryAttempts.delete(requestId);
        }
    }
    
    /**
     * Générer un ID unique pour la requête
     * @returns {string}
     */
    generateRequestId() {
        return `calc_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    
    /**
     * Valider les données du formulaire
     * @param {object} formData - Données à valider
     */
    validateFormData(formData) {
        const errors = [];
        
        if (!formData.departement) {
            errors.push('Département requis');
        }
        
        if (!formData.poids || formData.poids <= 0) {
            errors.push('Poids requis et supérieur à 0');
        }
        
        if (!formData.type) {
            errors.push('Type d\'envoi requis');
        }
        
        if (errors.length > 0) {
            throw new Error(`Données invalides: ${errors.join(', ')}`);
        }
    }
    
    /**
     * Normaliser les données du formulaire
     * @param {object} formData - Données brutes
     * @returns {object} Données normalisées
     */
    normalizeFormData(formData) {
        return {
            departement: String(formData.departement).padStart(2, '0'),
            poids: parseFloat(formData.poids),
            type: String(formData.type).toLowerCase(),
            adr: formData.adr === true || formData.adr === 'oui' ? 'oui' : 'non',
            service_livraison: formData.service_livraison || 'standard',
            enlevement: Boolean(formData.enlevement),
            palettes: parseInt(formData.palettes) || 0
        };
    }
    
    /**
     * Envoyer la requête HTTP
     * @param {string} requestId - ID de la requête
     * @param {object} data - Données à envoyer
     * @returns {Promise<object>}
     */
    async sendRequest(requestId, data) {
        const startTime = performance.now();
        
        // Créer AbortController pour cette requête
        this.abortController = new AbortController();
        
        // Ajouter à la queue
        this.requestQueue.set(requestId, this.abortController);
        
        // Configuration de la requête
        const requestConfig = {
            method: CalculateurConfig.API.METHODS.CALCULATE,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Request-ID': requestId
            },
            body: JSON.stringify(data),
            signal: this.abortController.signal
        };
        
        // Timeout personnalisé
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => {
                reject(new Error('Timeout de la requête'));
            }, CalculateurConfig.API.TIMEOUT);
        });
        
        // Envoyer la requête avec retry
        return await this.executeWithRetry(requestId, async () => {
            const response = await Promise.race([
                fetch(CalculateurConfig.API.ENDPOINT, requestConfig),
                timeoutPromise
            ]);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
            }
            
            const responseData = await response.json();
            
            // Ajouter métadonnées de performance
            responseData.metadata = {
                ...responseData.metadata,
                request_id: requestId,
                client_processing_time: performance.now() - startTime
            };
            
            return responseData;
        });
    }
    
    /**
     * Exécuter une requête avec retry automatique
     * @param {string} requestId - ID de la requête
     * @param {function} requestFn - Fonction de requête
     * @returns {Promise<object>}
     */
    async executeWithRetry(requestId, requestFn) {
        const maxAttempts = CalculateurConfig.API.RETRY_ATTEMPTS;
        let attempt = this.retryAttempts.get(requestId) || 0;
        
        while (attempt < maxAttempts) {
            try {
                return await requestFn();
            } catch (error) {
                attempt++;
                this.retryAttempts.set(requestId, attempt);
                
                if (attempt >= maxAttempts) {
                    throw error;
                }
                
                // Attendre avant retry
                await this.delay(CalculateurConfig.API.RETRY_DELAY * attempt);
                
                CalculateurConfig.log('warn', `Retry ${attempt}/${maxAttempts} pour requête ${requestId}`);
            }
        }
    }
    
    /**
     * Traiter la réponse de l'API
     * @param {object} response - Réponse brute
     * @returns {object} Réponse traitée
     */
    processResponse(response) {
        if (!response.success) {
            throw new Error(response.message || 'Erreur de calcul inconnue');
        }
        
        // Structurer les résultats
        const processedResponse = {
            success: true,
            carriers: this.processCarrierResults(response.carriers || {}),
            bestRate: this.findBestRate(response.carriers || {}),
            suggestions: response.suggestions || [],
            affretement: response.affretement || false,
            debug: response.debug || null,
            metadata: response.metadata || {}
        };
        
        // Valider les résultats
        this.validateResponse(processedResponse);
        
        return processedResponse;
    }
    
    /**
     * Traiter les résultats par transporteur
     * @param {object} carriers - Résultats bruts par transporteur
     * @returns {object} Résultats traités
     */
    processCarrierResults(carriers) {
        const processed = {};
        
        Object.entries(carriers).forEach(([carrier, result]) => {
            if (result !== null && typeof result === 'number') {
                processed[carrier] = {
                    price: parseFloat(result.toFixed(2)),
                    available: true,
                    carrier: carrier,
                    delay: this.getCarrierDelay(carrier),
                    info: CalculateurConfig.getCarrierInfo(carrier)
                };
            } else {
                processed[carrier] = {
                    price: null,
                    available: false,
                    carrier: carrier,
                    delay: null,
                    info: CalculateurConfig.getCarrierInfo(carrier),
                    reason: 'Non disponible'
                };
            }
        });
        
        return processed;
    }
    
    /**
     * Trouver le meilleur tarif
     * @param {object} carriers - Résultats par transporteur
     * @returns {object|null}
     */
    findBestRate(carriers) {
        let bestRate = null;
        let bestPrice = Infinity;
        
        Object.entries(carriers).forEach(([carrier, result]) => {
            if (result !== null && typeof result === 'number') {
                if (result < bestPrice) {
                    bestPrice = result;
                    bestRate = {
                        carrier: carrier,
                        price: parseFloat(result.toFixed(2)),
                        info: CalculateurConfig.getCarrierInfo(carrier),
                        delay: this.getCarrierDelay(carrier)
                    };
                }
            }
        });
        
        return bestRate;
    }
    
    /**
     * Obtenir le délai d'un transporteur
     * @param {string} carrier - Code transporteur
     * @returns {string}
     */
    getCarrierDelay(carrier) {
        const delays = {
            'heppner': '24-48h',
            'xpo': '24-48h',
            'kn': '48-72h'
        };
        
        return delays[carrier] || 'Délai à confirmer';
    }
    
    /**
     * Valider la réponse de l'API
     * @param {object} response - Réponse à valider
     */
    validateResponse(response) {
        if (!response.carriers || typeof response.carriers !== 'object') {
            throw new Error('Format de réponse invalide: carriers manquant');
        }
        
        if (Object.keys(response.carriers).length === 0) {
            throw new Error('Aucun transporteur dans la réponse');
        }
    }
    
    /**
     * Annuler les requêtes en cours
     */
    cancelPendingRequests() {
        this.requestQueue.forEach((controller, requestId) => {
            try {
                controller.abort();
                CalculateurConfig.log('info', `Requête ${requestId} annulée`);
            } catch (error) {
                // Ignorer les erreurs d'annulation
            }
        });
        
        this.requestQueue.clear();
    }
    
    /**
     * Gérer les erreurs
     * @param {Error} error - Erreur à traiter
     * @param {string} requestId - ID de la requête
     */
    handleError(error, requestId) {
        let userMessage = 'Erreur lors du calcul';
        
        if (error.name === 'AbortError') {
            userMessage = 'Calcul annulé';
        } else if (error.message.includes('Timeout')) {
            userMessage = 'Délai d\'attente dépassé';
        } else if (error.message.includes('Network')) {
            userMessage = 'Erreur de connexion';
        } else if (error.message.includes('HTTP')) {
            userMessage = 'Erreur du serveur';
        }
        
        CalculateurConfig.log('error', `Erreur API (${requestId}):`, {
            message: error.message,
            stack: error.stack,
            userMessage
        });
        
        // Créer une erreur enrichie
        const enrichedError = new Error(userMessage);
        enrichedError.originalError = error;
        enrichedError.requestId = requestId;
        enrichedError.userMessage = userMessage;
        
        throw enrichedError;
    }
    
    /**
     * Mettre à jour les statistiques
     * @param {boolean} success - Succès de la requête
     * @param {number} responseTime - Temps de réponse
     */
    updateStats(success, responseTime = null) {
        this.stats.totalRequests++;
        
        if (success) {
            this.stats.successfulRequests++;
            
            if (responseTime) {
                const count = this.stats.successfulRequests;
                this.stats.averageResponseTime = 
                    ((this.stats.averageResponseTime * (count - 1)) + responseTime) / count;
            }
        } else {
            this.stats.failedRequests++;
        }
    }
    
    /**
     * Obtenir les statistiques
     * @returns {object}
     */
    getStats() {
        return {
            ...this.stats,
            successRate: this.stats.totalRequests > 0 
                ? (this.stats.successfulRequests / this.stats.totalRequests * 100).toFixed(1)
                : '0.0'
        };
    }
    
    /**
     * Utilitaire pour délai
     * @param {number} ms - Millisecondes
     * @returns {Promise}
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Mock response pour tests
     * @param {object} formData - Données du formulaire
     * @returns {Promise<object>}
     */
    async mockCalculate(formData) {
        await this.delay(CalculateurConfig.DEBUG.MOCK_RESPONSES.DELAY);
        
        // Simuler échec aléatoire
        if (Math.random() > CalculateurConfig.DEBUG.MOCK_RESPONSES.SUCCESS_RATE) {
            throw new Error('Erreur simulée pour test');
        }
        
        return {
            success: true,
            carriers: {
                heppner: 89.50,
                xpo: 95.20,
                kn: null
            },
            suggestions: [
                {
                    type: 'weight_optimization',
                    message: 'En passant à 200kg, économisez 12€ avec Heppner'
                }
            ],
            debug: {
                heppner: { tarif_base: 75.00, surcharge_gasoil: 14.50 },
                xpo: { tarif_base: 82.00, surcharge_gasoil: 13.20 }
            },
            metadata: {
                processing_time: Math.random() * 500 + 200,
                version: CalculateurConfig.META.VERSION
            }
        };
    }
}

// =========================================================================
// INSTANCE GLOBALE ET EXPORT
// =========================================================================

// Créer instance globale
window.apiService = new ApiService();

// En mode debug, permettre d'utiliser les mocks
if (CalculateurConfig.DEBUG.ENABLED && CalculateurConfig.DEBUG.MOCK_RESPONSES.ENABLED) {
    const originalCalculate = window.apiService.calculate.bind(window.apiService);
    
    window.apiService.calculate = function(formData) {
        CalculateurConfig.log('info', 'Utilisation des mocks pour le calcul');
        return this.mockCalculate(formData);
    };
    
    window.apiService.useRealAPI = function() {
        this.calculate = originalCalculate;
        CalculateurConfig.log('info', 'Retour à l\'API réelle');
    };
}
