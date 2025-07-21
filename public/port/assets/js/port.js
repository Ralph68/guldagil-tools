/**
 * Titre: Module JavaScript calculateur de frais de port - Version corrigée
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // Configuration
    config: {
        apiUrl: '?ajax=calculate',
        debounceDelay: 300,
        maxRetries: 3
    },

    // État du module
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        currentStep: 1,
        adrSelected: false
    },

    // Cache DOM
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadHistory();
        this.setupValidation();
        this.setupSteps();
        console.log('🧮 Calculateur module initialisé');
    },

    /**
     * Cache des éléments DOM avec classes CSS modernisées
     */
    cacheDOMElements() {
        this.dom = {
            form: document.getElementById('calculatorForm'),
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            type: document.getElementById('type'),
            palettes: document.getElementById('palettes'),
            paletteEur: document.getElementById('palette_eur'),
            adr: document.getElementById('adr'),
            enlevement: document.getElementById('enlevement'),
            optionSup: document.getElementById('option_sup'),
            calculateBtn: document.getElementById('calculateBtn'),
            resultsContent: document.getElementById('resultsContent'),
            calcStatus: document.getElementById('calcStatus'),
            palettesGroup: document.getElementById('palettesGroup'),
            paletteEurGroup: document.getElementById('paletteEurGroup'),
            
            // Nouveaux éléments pour les étapes
            stepBtns: document.querySelectorAll('.calc-step-btn'),
            stepContents: document.querySelectorAll('.calc-step-content'),
            toggleBtns: document.querySelectorAll('.calc-toggle-btn')
        };
    },

    /**
     * Configuration des événements - VERSION CORRIGÉE
     */
    setupEventListeners() {
        // Validation temps réel avec progression automatique
        if (this.dom.departement) {
            this.dom.departement.addEventListener('input', 
                this.debounce(() => {
                    this.validateDepartement();
                    this.autoProgressIfValid();
                }, this.config.debounceDelay)
            );
            
            // Validation à la perte de focus
            this.dom.departement.addEventListener('blur', () => {
                this.validateDepartement();
                this.autoProgressIfValid();
            });
        }

        if (this.dom.poids) {
            this.dom.poids.addEventListener('input', 
                this.debounce(() => {
                    this.validatePoids();
                    this.autoProgressIfValid();
                }, this.config.debounceDelay)
            );
            
            this.dom.poids.addEventListener('blur', () => {
                this.validatePoids();
                this.autoProgressIfValid();
            });
        }

        // Soumission formulaire
        if (this.dom.form) {
            this.dom.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // Bouton calcul manuel
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                this.handleEnterKey(e);
            }
        });
    },

    /**
     * Configuration des étapes - VERSION CORRIGÉE
     */
    setupSteps() {
        // Gestion des boutons d'étapes
        this.dom.stepBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const step = parseInt(e.target.dataset.step);
                this.activateStep(step);
            });
        });

        // Gestion des toggles (ADR, enlèvement)
        this.dom.toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleToggle(e.target);
            });
        });

        // Gestion type palette/colis
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.handleTypeChange();
                this.autoProgressIfValid();
            });
        }
    },

    /**
     * NOUVELLE FONCTION : Auto-progression intelligente des étapes
     */
    autoProgressIfValid() {
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        
        console.log(`Auto-progression: dept=${deptValid}, poids=${poidsValid}, step=${this.state.currentStep}`);
        
        // Étape 1 → 2 : Département valide
        if (deptValid && this.state.currentStep === 1) {
            console.log('🚀 Progression automatique : Étape 1 → 2');
            setTimeout(() => {
                this.activateStep(2);
                // Focus sur le champ poids
                if (this.dom.poids) {
                    this.dom.poids.focus();
                }
            }, 600);
        }
        
        // Étape 2 → 3 : Département + Poids valides
        else if (deptValid && poidsValid && this.state.currentStep === 2) {
            console.log('🚀 Progression automatique : Étape 2 → 3');
            setTimeout(() => {
                this.activateStep(3);
                // Focus sur le type d'envoi
                if (this.dom.type) {
                    this.dom.type.focus();
                }
            }, 600);
        }
        
        // Étape 3 : Calcul automatique si tout est valide
        else if (deptValid && poidsValid && this.state.currentStep >= 3 && !this.state.isCalculating) {
            console.log('🚀 Lancement calcul automatique');
            setTimeout(() => this.handleCalculate(), 1000);
        }
    },

    /**
     * Gestion de la touche Entrée - NOUVELLE FONCTION
     */
    handleEnterKey(e) {
        const activeElement = document.activeElement;
        
        // Si on est sur le champ département et qu'il est valide
        if (activeElement === this.dom.departement && this.validateDepartement()) {
            e.preventDefault();
            this.activateStep(2);
            setTimeout(() => this.dom.poids.focus(), 200);
        }
        
        // Si on est sur le champ poids et qu'il est valide
        else if (activeElement === this.dom.poids && this.validatePoids()) {
            e.preventDefault();
            this.activateStep(3);
            setTimeout(() => this.dom.type.focus(), 200);
        }
        
        // Si on est sur l'étape 3 et que tout est valide
        else if (this.state.currentStep >= 3 && this.isFormValid()) {
            e.preventDefault();
            this.handleCalculate();
        }
    },

    /**
     * Activer une étape avec animations - VERSION AMÉLIORÉE
     */
    activateStep(stepNumber) {
        console.log(`🎯 Activation étape ${stepNumber}`);
        
        const previousStep = this.state.currentStep;
        this.state.currentStep = stepNumber;
        
        // Mettre à jour les boutons d'étapes
        this.dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.remove('active', 'completed');
            
            if (btnStep === stepNumber) {
                btn.classList.add('active');
            } else if (btnStep < stepNumber) {
                btn.classList.add('completed');
            }
        });

        // Mettre à jour le contenu des étapes avec animation
        this.dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            
            if (contentStep === stepNumber) {
                content.style.display = 'block';
                content.classList.add('active');
                // Animation d'entrée
                setTimeout(() => {
                    content.style.opacity = '1';
                    content.style.transform = 'translateY(0)';
                }, 50);
            } else {
                content.classList.remove('active');
                content.style.opacity = '0';
                content.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    content.style.display = 'none';
                }, 200);
            }
        });

        // Focus sur le premier champ de l'étape active
        this.focusFirstFieldInStep(stepNumber);
        
        // Notification visuelle
        this.showStepNotification(stepNumber, previousStep);
    },

    /**
     * Focus sur le premier champ de l'étape - NOUVELLE FONCTION
     */
    focusFirstFieldInStep(stepNumber) {
        setTimeout(() => {
            switch(stepNumber) {
                case 1:
                    if (this.dom.departement) this.dom.departement.focus();
                    break;
                case 2:
                    if (this.dom.poids) this.dom.poids.focus();
                    break;
                case 3:
                    if (this.dom.type) this.dom.type.focus();
                    break;
            }
        }, 300);
    },

    /**
     * Notification de changement d'étape - NOUVELLE FONCTION
     */
    showStepNotification(newStep, previousStep) {
        const messages = {
            1: '📍 Saisissez le département de destination',
            2: '⚖️ Indiquez le poids de votre envoi',
            3: '⚙️ Choisissez vos options d\'expédition'
        };
        
        if (newStep > previousStep) {
            this.showMessage(messages[newStep], 'success', 2000);
        }
    },

    /**
     * Validation département - VERSION CORRIGÉE
     */
    validateDepartement() {
        if (!this.dom.departement) return false;
        
        const value = this.dom.departement.value.trim();
        // Regex améliorée pour départements français (01-95 + 2A, 2B)
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/i.test(value);
        
        console.log(`Validation département: "${value}" → ${isValid}`);
        
        this.updateFieldValidation('departement', isValid, 
            isValid ? '' : 'Département invalide (ex: 75, 69, 13, 2A)');
        
        return isValid;
    },

    /**
     * Validation poids - VERSION CORRIGÉE
     */
    validatePoids() {
        if (!this.dom.poids) return false;
        
        const value = parseFloat(this.dom.poids.value);
        const isValid = value >= 1 && value <= 3000 && !isNaN(value);
        
        console.log(`Validation poids: ${value} → ${isValid}`);
        
        this.updateFieldValidation('poids', isValid, 
            isValid ? '' : 'Poids entre 1 et 3000 kg');
        
        return isValid;
    },

    /**
     * Mise à jour validation champ - VERSION AMÉLIORÉE
     */
    updateFieldValidation(fieldName, isValid, errorMessage) {
        const field = this.dom[fieldName];
        const errorElement = document.getElementById(fieldName + 'Error');
        
        if (!field) return;
        
        // Mise à jour des classes CSS
        field.classList.remove('error', 'valid');
        if (isValid) {
            field.classList.add('valid');
        } else if (field.value.trim() !== '') {
            field.classList.add('error');
        }
        
        // Affichage message d'erreur
        if (errorElement) {
            errorElement.textContent = errorMessage;
        }
        
        // Mise à jour état interne
        this.state.validationErrors[fieldName] = !isValid;
    },

    /**
     * Gestion des toggles (ADR, enlèvement) - VERSION CORRIGÉE
     */
    handleToggle(clickedBtn) {
        const group = clickedBtn.closest('.calc-toggle-group');
        if (!group) return;
        
        // Retirer active de tous les boutons du groupe
        group.querySelectorAll('.calc-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activer le bouton cliqué
        clickedBtn.classList.add('active');
        
        // Mettre à jour l'état si c'est ADR
        if (clickedBtn.dataset.adr !== undefined) {
            this.state.adrSelected = clickedBtn.dataset.adr === 'oui';
        }
        
        // Progression automatique
        this.autoProgressIfValid();
    },

    /**
     * Gestion changement de type - VERSION CORRIGÉE
     */
    handleTypeChange() {
        if (!this.dom.type) return;
        
        const isPalette = this.dom.type.value === 'palette';
        
        // Afficher/masquer les champs palettes
        if (this.dom.palettesGroup) {
            this.dom.palettesGroup.style.display = isPalette ? 'block' : 'none';
        }
        if (this.dom.paletteEurGroup) {
            this.dom.paletteEurGroup.style.display = isPalette ? 'block' : 'none';
        }
        
        // Réinitialiser les valeurs si ce n'est pas palette
        if (!isPalette && this.dom.palettes) {
            this.dom.palettes.value = '1';
        }
        if (!isPalette && this.dom.paletteEur) {
            this.dom.paletteEur.value = '0';
        }
    },

    /**
     * Vérification validité formulaire complet
     */
    isFormValid() {
        return this.validateDepartement() && this.validatePoids();
    },

    /**
     * Afficher un message à l'utilisateur - NOUVELLE FONCTION
     */
    showMessage(message, type = 'info', duration = 3000) {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `calc-notification calc-notification-${type}`;
        notification.textContent = message;
        
        // Styles inline pour assurer l'affichage
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#3182ce'};
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Suppression automatique
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    },

    /**
     * Gestion du calcul
     */
    async handleCalculate() {
        if (this.state.isCalculating) return;
        
        // Validation finale
        if (!this.isFormValid()) {
            this.showMessage('⚠️ Veuillez corriger les erreurs du formulaire', 'error');
            return;
        }

        const formData = this.getFormData();
        
        this.state.isCalculating = true;
        this.showLoading();
        this.disableForm();

        try {
            console.log('🧮 Lancement calcul avec:', formData);
            const results = await this.callAPI(formData);
            this.displayResults(results, formData);
            this.saveToHistory(formData, results);
            this.showMessage('✅ Calcul terminé avec succès', 'success');
        } catch (error) {
            console.error('❌ Erreur calcul:', error);
            this.showMessage('❌ Erreur lors du calcul. Veuillez réessayer.', 'error');
        } finally {
            this.state.isCalculating = false;
            this.enableForm();
        }
    },

    /**
     * Récupération données formulaire
     */
    getFormData() {
        return {
            departement: this.dom.departement?.value.trim().padStart(2, '0') || '',
            poids: parseFloat(this.dom.poids?.value) || 0,
            type: this.dom.type?.value || 'colis',
            palettes: parseInt(this.dom.palettes?.value) || 1,
            palette_eur: parseInt(this.dom.paletteEur?.value) || 0,
            adr: this.state.adrSelected ? 'oui' : 'non',
            enlevement: 'non', // À implémenter si nécessaire
            option_sup: this.dom.optionSup?.value || 'standard'
        };
    },

    /**
     * Appel API
     */
    async callAPI(formData) {
    const params = new URLSearchParams(formData);
    const response = await fetch(this.config.apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString()
    });

    const text = await response.text();
    console.log('[DEBUG] Réponse brute du serveur :', text);
    // Ensuite, essaye de parser le JSON
    try {
        const result = JSON.parse(text);
        if (!result.success) {
            throw new Error(result.error || 'Erreur inconnue');
        }
        return result;
    } catch (err) {
        throw new Error('Réponse non-JSON reçue: ' + text.substring(0, 100));
    }
},

    /**
     * Affichage des résultats
     */
    displayResults(results, formData) {
        if (!this.dom.resultsContent) return;
        
        let html = '<div class="calc-results">';
        
        if (results.carriers) {
            Object.entries(results.carriers).forEach(([carrier, data]) => {
                html += `
                    <div class="calc-result-card">
                        <h4>${carrier.toUpperCase()}</h4>
                        <div class="calc-result-price">
                            <span class="calc-price-ht">${data.prix_ht}€ HT</span>
                            <span class="calc-price-ttc">${data.prix_ttc}€ TTC</span>
                        </div>
                        <div class="calc-result-delay">Délai: ${data.delai}</div>
                    </div>
                `;
            });
        }
        
        html += '</div>';
        this.dom.resultsContent.innerHTML = html;
        
        // Afficher la section résultats
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'block';
        }
    },

    /**
     * Affichage du loading
     */
    showLoading() {
        if (this.dom.calcStatus) {
            this.dom.calcStatus.innerHTML = '🧮 Calcul en cours...';
            this.dom.calcStatus.style.display = 'block';
        }
    },

    /**
     * Désactiver formulaire
     */
    disableForm() {
        const fields = ['departement', 'poids', 'type', 'palettes', 'paletteEur'];
        fields.forEach(field => {
            if (this.dom[field]) {
                this.dom[field].disabled = true;
            }
        });
        
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.disabled = true;
        }
    },

    /**
     * Réactiver formulaire
     */
    enableForm() {
        const fields = ['departement', 'poids', 'type', 'palettes', 'paletteEur'];
        fields.forEach(field => {
            if (this.dom[field]) {
                this.dom[field].disabled = false;
            }
        });
        
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.disabled = false;
        }
        
        if (this.dom.calcStatus) {
            this.dom.calcStatus.style.display = 'none';
        }
    },

    /**
     * Sauvegarde dans l'historique
     */
    saveToHistory(params, results) {
        const entry = {
            timestamp: Date.now(),
            params: { ...params },
            results: { ...results },
            id: 'calc_' + Date.now()
        };
        
        this.state.history.unshift(entry);
        this.state.history = this.state.history.slice(0, 10);
        
        try {
            localStorage.setItem('calc_history', JSON.stringify(this.state.history));
        } catch (e) {
            console.warn('Erreur sauvegarde historique:', e);
        }
    },

    /**
     * Chargement historique
     */
    loadHistory() {
        try {
            const saved = localStorage.getItem('calc_history');
            this.state.history = saved ? JSON.parse(saved) : [];
        } catch (e) {
            console.warn('Erreur chargement historique:', e);
            this.state.history = [];
        }
    },

    /**
     * Fonction debounce
     */
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
    },

    /**
     * Configuration validation - NOUVELLE FONCTION
     */
    setupValidation() {
        // Validation en temps réel avec indicateurs visuels
        const requiredFields = ['departement', 'poids'];
        
        requiredFields.forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                // Validation à la perte de focus
                field.addEventListener('blur', () => {
                    this[`validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`]();
                });
                
                // Nettoyage des erreurs à la saisie
                field.addEventListener('focus', () => {
                    field.classList.remove('error');
                    const errorElement = document.getElementById(fieldName + 'Error');
                    if (errorElement) {
                        errorElement.textContent = '';
                    }
                });
            }
        });
    }
};

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    CalculateurModule.init();
});

// Export global pour compatibilité
window.CalculateurModule = CalculateurModule;
