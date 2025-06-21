// =============================================================================
// FICHIER 3: /public/assets/js/modules/calculateur/services/api-service.js
// =============================================================================

/**
 * Service API pour les calculs
 */
class ApiService {
    constructor() {
        this.baseUrl = CalculateurConfig.API.ENDPOINT;
        this.timeout = CalculateurConfig.API.TIMEOUT;
    }
    
    /**
     * Calcul des tarifs
     */
    async calculateRates(formData) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData),
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success && result.errors) {
                throw new Error(result.errors.join(', '));
            }
            
            return result;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('Délai d\'attente dépassé');
            }
            
            throw error;
        }
    }
    
    /**
     * Validation des données
     */
    async validateData(formData) {
        // Validation côté client d'abord
        const clientValidation = this.validateClient(formData);
        if (!clientValidation.isValid) {
            return clientValidation;
        }
        
        // Puis validation serveur si nécessaire
        try {
            const response = await fetch(this.baseUrl + '?validate=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            return await response.json();
        } catch (error) {
            return { isValid: true }; // Fallback sur validation client
        }
    }
    
    /**
     * Validation côté client
     */
    validateClient(data) {
        const errors = {};
        
        // Département
        if (!data.departement || !CalculateurConfig.VALIDATION.DEPT_PATTERN.test(data.departement)) {
            errors.departement = 'Département invalide (01-95)';
        }
        
        // Poids
        if (!data.poids || data.poids < CalculateurConfig.VALIDATION.MIN_POIDS) {
            errors.poids = `Poids minimum: ${CalculateurConfig.VALIDATION.MIN_POIDS}kg`;
        }
        if (data.poids > CalculateurConfig.VALIDATION.MAX_POIDS) {
            errors.poids = `Poids maximum: ${CalculateurConfig.VALIDATION.MAX_POIDS}kg`;
        }
        
        // Type
        if (!['colis', 'palette'].includes(data.type)) {
            errors.type = 'Type d\'envoi invalide';
        }
        
        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }
}

window.apiService = new ApiService();
