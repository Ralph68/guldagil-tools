/**
 * Titre: Module calculs et communication API - Calculateur
 * Chemin: /public/assets/js/modules/calculateur/calculs.js
 * Version: 0.5 beta + build
 * 
 * Gestion des calculs et communication avec l'API
 * Dépendance: calculateur.js, form-handler.js
 */

// ========================================
// MODULE CALCULS
// ========================================

window.Calculateur = window.Calculateur || {};

Calculateur.Calculs = {
    
    /**
     * Configuration des calculs
     */
    config: {
        timeout: 30000, // 30 secondes
        retryAttempts: 2,
        retryDelay: 1000
    },
    
    /**
     * Cache des résultats
     */
    cache: new Map(),
    
    /**
     * Initialisation du module calculs
     */
    init() {
        this.setupCache();
        
        if (Calculateur.Config.DEBUG) {
            console.log('🧮 Module Calculs initialisé');
        }
    },
    
    /**
     * Configuration du cache
     */
    setupCache() {
        // Nettoyage automatique du cache toutes les 5 minutes
        setInterval(() => {
            this.cleanCache();
        }, 5 * 60 * 1000);
    },
    
    /**
     * Exécution du calcul principal
     */
    async performCalculation(formData) {
        // Vérification cache
        const cacheKey = this.generateCacheKey(formData);
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < 300000) { // Cache valide 5 minutes
                if (Calculateur.Config.DEBUG) {
                    console.log('📋 Résultat depuis le cache');
                }
                return cached.data;
            }
        }
        
        // Validation pré-calcul
        this.validateCalculationData(formData);
        
        // Préparation des données
        const requestData = this.prepareRequestData(formData);
        
        // Exécution avec retry
        const result = await this.executeWithRetry(requestData);
        
        // Mise en cache
        this.cache.set(cacheKey, {
            data: result,
            timestamp: Date.now()
        });
        
        return result;
    },
    
    /**
     * Validation des données avant calcul
     */
    validateCalculationData(formData) {
        if (!formData.departement || !/^\d{2}$/.test(formData.departement)) {
            throw new Error('Département invalide');
        }
        
        if (!formData.poids || formData.poids <= 0) {
            throw new Error('Poids invalide');
        }
        
        if (!['colis', 'palette'].includes(formData.type)) {
            throw new Error('Type d\'envoi invalide');
        }
        
        if (!['oui', 'non'].includes(formData.adr)) {
            throw new Error('Option ADR invalide');
        }
    },
    
    /**
     * Préparation des données pour l'API
     */
    prepareRequestData(formData) {
        const data = new FormData();
        
        // Données obligatoires
        data.append('departement', formData.departement);
        data.append('poids', formData.poids.toString());
        data.append('type', formData.type);
        data.append('adr', formData.adr);
        
        // Données optionnelles
        data.append('option_sup', formData.option_sup || 'standard');
        data.append('enlevement', formData.enlevement || '0');
        data.append('palettes', formData.palettes || '0');
        
        // Métadonnées
        data.append('timestamp', Date.now().toString());
        data.append('version', Calculateur.Config.VERSION);
        
        if (Calculateur.Config.DEBUG) {
            data.append('debug', '1');
        }
        
        return data;
    },
    
    /**
     * Exécution avec tentatives multiples
     */
    async executeWithRetry(requestData, attempt = 1) {
        try {
            return await this.makeApiRequest(requestData);
        } catch (error) {
            if (attempt < this.config.retryAttempts) {
                if (Calculateur.Config.DEBUG) {
                    console.warn(`⚠️ Tentative ${attempt} échouée, retry dans ${this.config.retryDelay}ms`);
                }
                
                await this.delay(this.config.retryDelay);
                return this.executeWithRetry(requestData, attempt + 1);
            } else {
                throw error;
            }
        }
    },
    
    /**
     * Requête API principale
     */
    async makeApiRequest(requestData) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
        
        try {
            const response = await fetch(Calculateur.Config.API_ENDPOINT, {
                method: 'POST',
                body: requestData,
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse API non-JSON reçue');
            }
            
            const result = await response.json();
            
            if (Calculateur.Config.DEBUG) {
                console.log('📊 Réponse API reçue:', result);
            }
            
            return this.processApiResponse(result);
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('Timeout de la requête de calcul');
            }
            
            throw error;
        }
    },
    
    /**
     * Traitement de la réponse API
     */
    processApiResponse(result) {
        // Vérification structure de base
        if (!result || typeof result !== 'object') {
            throw new Error('Réponse API invalide');
        }
        
        // Gestion des erreurs API
        if (result.error || (result.errors && result.errors.length > 0)) {
            const errorMessage = result.error || result.errors.join(', ');
            throw new Error(`Erreur calcul: ${errorMessage}`);
        }
        
        // Vérification présence données
        if (!result.success && !result.affretement) {
            throw new Error('Aucun résultat de calcul disponible');
        }
        
        // Affretement spécial
        if (result.affretement) {
            return {
                type: 'affretement',
                message: result.message || 'Affrètement requis pour cette expédition',
                success: false
            };
        }
        
        // Résultats normaux
        if (!result.formatted || !result.bestCarrier) {
            throw new Error('Données de calcul incomplètes');
        }
        
        return {
            type: 'success',
            success: true,
            bestCarrier: result.bestCarrier,
            best: result.best,
            formatted: result.formatted,
            comparison: this.buildComparison(result.formatted),
            debug: result.debug || null
        };
    },
    
    /**
     * Construction des données de comparaison
     */
    buildComparison(formatted) {
        const carriers = Object.entries(formatted)
            .filter(([key, carrier]) => carrier.price !== null && !isNaN(carrier.price))
            .map(([key, carrier]) => ({
                code: key,
                name: carrier.name,
                price: carrier.price,
                formatted: carrier.formatted
            }))
            .sort((a, b) => a.price - b.price);
        
        return {
            count: carriers.length,
            carriers: carriers,
            range: carriers.length > 1 ? {
                min: carriers[0].price,
                max: carriers[carriers.length - 1].price,
                difference: carriers[carriers.length - 1].price - carriers[0].price
            } : null
        };
    },
    
    /**
     * Génération clé de cache
     */
    generateCacheKey(formData) {
        const key = [
            formData.departement,
            formData.poids,
            formData.type,
            formData.adr,
            formData.option_sup,
            formData.enlevement,
            formData.palettes
        ].join('|');
        
        return btoa(key); // Encodage base64 pour la clé
    },
    
    /**
     * Nettoyage du cache
     */
    cleanCache() {
        const now = Date.now();
        const maxAge = 300000; // 5 minutes
        
        for (const [key, value] of this.cache.entries()) {
            if (now - value.timestamp > maxAge) {
                this.cache.delete(key);
            }
        }
        
        if (Calculateur.Config.DEBUG) {
            console.log(`🧹 Cache nettoyé, ${this.cache.size} entrées restantes`);
        }
    },
    
    /**
     * Utilitaire de délai
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },
    
    /**
     * Statistiques du cache
     */
    getCacheStats() {
        return {
            size: this.cache.size,
            keys: Array.from(this.cache.keys()),
            timestamps: Array.from(this.cache.values()).map(v => v.timestamp)
        };
    },
    
    /**
     * Vider le cache manuellement
     */
    clearCache() {
        this.cache.clear();
        if (Calculateur.Config.DEBUG) {
            console.log('🗑️ Cache vidé manuellement');
        }
    },
    
    /**
     * Test de connectivité API
     */
    async testApiConnectivity() {
        try {
            const testData = new FormData();
            testData.append('test', '1');
            
            const response = await fetch(Calculateur.Config.API_ENDPOINT, {
                method: 'POST',
                body: testData,
                signal: AbortSignal.timeout(5000)
            });
            
            return {
                success: response.ok,
                status: response.status,
                statusText: response.statusText
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }
};
