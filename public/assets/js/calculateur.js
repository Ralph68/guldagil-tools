/**
 * Calculator.js - VERSION CORRIGÉE
 * Gestion du calculateur de frais de port avec logique Excel
 */

class PortCalculator {
    constructor(options = {}) {
        this.formSelector = options.formSelector || '#calculator-form';
        this.resultsSelector = options.resultsSelector || '#results-container';
        this.debugMode = options.debug || false;
        
        this.form = document.querySelector(this.formSelector);
        this.resultsContainer = document.querySelector(this.resultsSelector);
        
        this.apiEndpoint = '/api/calculate.php';
        this.isCalculating = false;
        
        this.init();
    }

    init() {
        if (!this.form || !this.resultsContainer) {
            console.error('Éléments requis non trouvés');
            return;
        }

        this.bindEvents();
        this.loadFormState();
        this.setupFormValidation();
    }

    bindEvents() {
        // Soumission du formulaire
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Validation en temps réel
        this.form.addEventListener('input', (e) => this.handleInput(e));
        this.form.addEventListener('change', (e) => this.handleChange(e));
        
        // Sauvegarde automatique
        this.form.addEventListener('input', () => this.saveFormState());
        this.form.addEventListener('change', () => this.saveFormState());
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isCalculating) return;
        
        const formData = this.getFormData();
        const validation = this.validateForm(formData);
        
        if (!validation.valid) {
            this.showErrors(validation.errors);
            return;
        }

        await this.performCalculation(formData);
    }

    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        // Conversion en objet avec types appropriés
        data.departement = formData.get('departement')?.trim();
        data.poids = parseFloat(formData.get('poids')) || 0;
        data.type = formData.get('type');
        data.adr = formData.has('adr');
        data.option_sup = formData.get('option_sup') || 'standard';
        data.enlevement = formData.has('enlevement');
        data.palettes = parseInt(formData.get('palettes')) || 0;
        
        return data;
    }

    validateForm(data) {
        const errors = [];
        
        // Validation département
        if (!data.departement || !/^\d{1,2}$/.test(data.departement)) {
            errors.push('Veuillez saisir un département valide (01-95)');
        } else {
            const dept = parseInt(data.departement);
            if (dept < 1 || dept > 95) {
                errors.push('Le département doit être entre 01 et 95');
            }
        }
        
        // Validation poids
        if (!data.poids || data.poids <= 0) {
            errors.push('Veuillez saisir un poids supérieur à 0 kg');
        } else if (data.poids > 10000) {
            errors.push('Le poids maximum est de 10 000 kg');
        }
        
        // Validation type
        if (!data.type || !['colis', 'palette'].includes(data.type)) {
            errors.push('Veuillez sélectionner un type d\'envoi');
        }
        
        // Validation palettes
        if (data.type === 'palette' && data.palettes < 0) {
            errors.push('Le nombre de palettes ne peut pas être négatif');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    async performCalculation(data) {
        this.isCalculating = true;
        this.showLoading();
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            if (result.success) {
                this.displayResults(result);
                this.saveToHistory(data, result);
            } else {
                this.showErrors(result.errors || ['Erreur inconnue']);
            }
            
        } catch (error) {
            console.error('Erreur de calcul:', error);
            this.showErrors(['Erreur de connexion. Veuillez réessayer.']);
        } finally {
            this.isCalculating = false;
        }
    }

    displayResults(result) {
        const html = this.buildResultsHTML(result);
        this.resultsContainer.innerHTML = html;
        
        // Animation d'apparition
        this.resultsContainer.style.opacity = '0';
        this.resultsContainer.style.transform = 'translateY(20px)';
        
        requestAnimationFrame(() => {
            this.resultsContainer.style.transition = 'all 0.3s ease';
            this.resultsContainer.style.opacity = '1';
            this.resultsContainer.style.transform = 'translateY(0)';
        });
    }

    buildResultsHTML(result) {
        let html = '';
        
        // Affichage des suggestions importantes en premier
        if (result.suggestions && result.suggestions.length > 0) {
            html += '<div class="suggestions-container">';
            result.suggestions.forEach(suggestion => {
                html += this.buildSuggestionHTML(suggestion);
            });
            html += '</div>';
        }
        
        // Meilleur tarif
        if (result.best && result.best.price) {
            html += this.buildBestResultHTML(result);
        }
        
        // Tableau de comparaison
        html += this.buildComparisonTableHTML(result);
        
        // Debug si activé
        if (this.debugMode && result.debug) {
            html += this.buildDebugHTML(result.debug);
        }
        
        return html;
    }

    buildSuggestionHTML(suggestion) {
        const typeClass = suggestion.type || 'info';
        return `
            <div class="alert alert-${typeClass}">
                <h4>${suggestion.title || 'Information'}</h4>
                <p>${suggestion.message}</p>
                ${suggestion.alternative ? `<p><strong>Alternative :</strong> ${suggestion.alternative}</p>` : ''}
            </div>
        `;
    }

    buildBestResultHTML(result) {
        const best = result.best;
        const carrier = result.formatted[best.carrier];
        
        return `
            <div class="best-result">
                <div class="best-result-header">
                    <h3>🏆 Meilleur tarif</h3>
                    <div class="best-result-badge">Recommandé</div>
                </div>
                <div class="best-result-content">
                    <div class="carrier-info">
                        <div class="carrier-name">${carrier.name}</div>
                        <div class="carrier-details">
                            ${this.getCarrierDetails(best.carrier, result.params)}
                        </div>
                    </div>
                    <div class="price-info">
                        <div class="price-amount">${carrier.formatted}</div>
                        <div class="price-details">TTC, frais inclus</div>
                    </div>
                </div>
                ${this.buildOptimizationTips(result, best.carrier)}
            </div>
        `;
    }

    buildComparisonTableHTML(result) {
        const carriers = ['heppner', 'xpo', 'kn'];
        let tableRows = '';
        
        carriers.forEach(carrier => {
            if (result.formatted[carrier]) {
                const info = result.formatted[carrier];
                const isBest = result.best && result.best.carrier === carrier;
                const isAvailable = info.available;
                
                const statusBadge = isBest ? 
                    '<span class="badge badge-success">Meilleur choix</span>' :
                    isAvailable ? 
                        '<span class="badge badge-available">Disponible</span>' :
                        '<span class="badge badge-unavailable">Non disponible</span>';
                
                const reasonText = !isAvailable && info.debug && info.debug.error ? 
                    `<small class="text-muted">${info.debug.error}</small>` : '';
                
                tableRows += `
                    <tr class="carrier-row ${isBest ? 'best-row' : ''} ${!isAvailable ? 'unavailable-row' : ''}">
                        <td>
                            <div class="carrier-cell">
                                <strong>${info.name}</strong>
                                ${this.getCarrierDetails(carrier, result.params)}
                                ${reasonText}
                            </div>
                        </td>
                        <td class="price-cell">
                            <div class="price-display">${info.formatted}</div>
                        </td>
                        <td class="status-cell">
                            ${statusBadge}
                        </td>
                    </tr>
                `;
            }
        });
        
        return `
            <div class="comparison-section">
                <h3>📊 Comparaison détaillée</h3>
                <div class="table-responsive">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Transporteur</th>
                                <th>Tarif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    getCarrierDetails(carrier, params) {
        const details = [];
        
        // Délai de livraison
        const delays = {
            'heppner': params.option_sup === 'standard' ? '24-48h' : '24h',
            'xpo': params.option_sup === 'standard' ? '24-48h' : '24h',
            'kn': '48-72h'
        };
        
        if (delays[carrier]) {
            details.push(`⏱️ ${delays[carrier]}`);
        }
        
        // Type de service
        if (params.option_sup !== 'standard') {
            const serviceNames = {
                'rdv': 'Prise de RDV',
                'star18': 'Star avant 18h',
                'star13': 'Star avant 13h',
                'premium18': 'Premium avant 18h',
                'premium13': 'Premium avant 13h',
                'datefixe18': 'Date fixe avant 18h',
                'datefixe13': 'Date fixe avant 13h'
            };
            
            if (serviceNames[params.option_sup]) {
                details.push(`⭐ ${serviceNames[params.option_sup]}`);
            }
        }
        
        // ADR
        if (params.adr) {
            details.push('☣️ ADR');
        }
        
        // Enlèvement
        if (params.enlevement) {
            details.push('📦 Enlèvement');
        }
        
        // Palettes
        if (params.palettes > 0) {
            details.push(`🏭 ${params.palettes} palette${params.palettes > 1 ? 's' : ''} EUR`);
        }
        
        return details.length > 0 ? `<div class="carrier-details">${details.join(' • ')}</div>` : '';
    }

    buildOptimizationTips(result, bestCarrier) {
        const tips = [];
        
        // Vérifier si une suggestion "Payant pour 100kg" existe
        if (result.debug[bestCarrier] && result.debug[bestCarrier].suggestion) {
            tips.push(`💡 ${result.debug[bestCarrier].suggestion}`);
        }
        
        // Autres optimisations possibles
        if (result.params.poids < 100 && result.params.type === 'colis') {
            tips.push('💡 Considérez grouper vos envois pour optimiser les coûts');
        }
        
        if (tips.length === 0) return '';
        
        return `
            <div class="optimization-tips">
                <h4>💡 Conseils d'optimisation</h4>
                <ul>
                    ${tips.map(tip => `<li>${tip}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    buildDebugHTML(debug) {
        return `
            <div class="debug-section">
                <h3>🔧 Informations de débogage</h3>
                <pre class="debug-content">${JSON.stringify(debug, null, 2)}</pre>
            </div>
        `;
    }

    showLoading() {
        this.resultsContainer.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div class="loading-text">Calcul en cours...</div>
                <div class="loading-subtext">Comparaison des transporteurs</div>
            </div>
        `;
    }

    showErrors(errors) {
        const errorsHTML = errors.map(error => `<li>${error}</li>`).join('');
        this.resultsContainer.innerHTML = `
            <div class="alert alert-error">
                <h4>⚠️ Erreur de validation</h4>
                <ul>${errorsHTML}</ul>
            </div>
        `;
    }

    handleInput(e) {
        this.validateField(e.target);
        this.updateFormState(e.target);
    }

    handleChange(e) {
        this.updateFormState(e.target);
        
        // Logique spécifique selon le champ
        if (e.target.name === 'type') {
            this.togglePalettesField(e.target.value);
        }
        
        if (e.target.name === 'enlevement') {
            this.toggleEnlevementOptions(e.target.checked);
        }
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        switch (field.name) {
            case 'departement':
                isValid = /^\d{1,2}$/.test(value) && parseInt(value) >= 1 && parseInt(value) <= 95;
                message = isValid ? '' : 'Département invalide (01-95)';
                break;
                
            case 'poids':
                isValid = value && parseFloat(value) > 0 && parseFloat(value) <= 10000;
                message = isValid ? '' : 'Poids invalide (1-10000 kg)';
                break;
        }
        
        this.setFieldValidation(field, isValid, message);
        return isValid;
    }

    setFieldValidation(field, isValid, message) {
        const container = field.closest('.form-group');
        const feedback = container?.querySelector('.field-feedback');
        
        field.classList.toggle('is-invalid', !isValid);
        field.classList.toggle('is-valid', isValid && field.value.trim() !== '');
        
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = message ? 'block' : 'none';
        }
    }

    togglePalettesField(type) {
        const palettesGroup = document.querySelector('.palettes-group');
        if (palettesGroup) {
            palettesGroup.style.display = type === 'palette' ? 'block' : 'none';
        }
    }

    toggleEnlevementOptions(isEnlevement) {
        const optionSelects = document.querySelectorAll('[name="option_sup"] option');
        const premiumOptions = ['rdv', 'star18', 'star13', 'premium18', 'premium13', 'datefixe18', 'datefixe13'];
        
        optionSelects.forEach(option => {
            if (premiumOptions.includes(option.value)) {
                option.disabled = isEnlevement;
                if (isEnlevement && option.selected) {
                    // Basculer vers standard si option premium sélectionnée
                    document.querySelector('[name="option_sup"]').value = 'standard';
                }
            }
        });
    }

    updateFormState(field) {
        // Mise à jour temps réel de l'interface
        if (field.name === 'adr') {
            this.updateADRWarnings(field.checked);
        }
    }

    updateADRWarnings(hasADR) {
        const optionSelect = document.querySelector('[name="option_sup"]');
        const starOptions = optionSelect?.querySelectorAll('option[value^="star"], option[value^="datefixe"]');
        
        starOptions?.forEach(option => {
            if (hasADR) {
                option.textContent = option.textContent.replace(' (⚠️ Non disponible avec ADR)', '') + ' (⚠️ Non disponible avec ADR)';
            } else {
                option.textContent = option.textContent.replace(' (⚠️ Non disponible avec ADR)', '');
            }
        });
    }

    setupFormValidation() {
        // Validation en temps réel
        const fields = this.form.querySelectorAll('input[required], select[required]');
        fields.forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
        });
    }

    saveFormState() {
        try {
            const formData = this.getFormData();
            sessionStorage.setItem('calculator_form_state', JSON.stringify(formData));
        } catch (e) {
            // Ignore les erreurs de storage
        }
    }

    loadFormState() {
        try {
            const saved = sessionStorage.getItem('calculator_form_state');
            if (saved) {
                const data = JSON.parse(saved);
                this.populateForm(data);
            }
        } catch (e) {
            // Ignore les erreurs de storage
        }
    }

    populateForm(data) {
        Object.entries(data).forEach(([key, value]) => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                } else {
                    field.value = value;
                }
                
                // Déclencher les événements pour la mise à jour de l'interface
                field.dispatchEvent(new Event('change'));
            }
        });
    }

    saveToHistory(formData, result) {
        try {
            let history = JSON.parse(localStorage.getItem('calculator_history') || '[]');
            
            const entry = {
                timestamp: Date.now(),
                params: formData,
                best: result.best,
                id: Date.now().toString()
            };
            
            history.unshift(entry);
            history = history.slice(0, 20); // Garder 20 dernières
            
            localStorage.setItem('calculator_history', JSON.stringify(history));
        } catch (e) {
            // Ignore les erreurs de storage
        }
    }
}

// Auto-initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si on est sur la page du calculateur
    if (document.querySelector('#calculator-form')) {
        const calculator = new PortCalculator({
            debug: window.location.search.includes('debug=1')
        });
        
        // Exposer globalement pour debugging
        window.calculator = calculator;
    }
});

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PortCalculator;
}
