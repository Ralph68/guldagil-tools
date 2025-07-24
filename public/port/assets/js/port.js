/**
 * Titre: Module JavaScript calculateur de frais de port - PARTIE 1/2
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 * CORRECTIFS: UX améliorée + Fonctions complètes
 */

const CalculateurModule = {
    // Configuration ajustée pour meilleure UX
    config: {
        apiUrl: '?ajax=calculate',
        debounceDelay: 800,          // Augmenté pour éviter calculs trop fréquents
        progressDelay: 1500,         // Délai avant progression automatique
        validationDelay: 500,        // Délai pour validation visuelle
        maxRetries: 3,
        autoCalculate: false,        // Désactivé par défaut
        waitForAdrDelay: 1500,       // Délai d'attente pour ADR
        poidsSeuilPalette: 150       // Seuil auto palette/colis
    },

    // État du module
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        currentStep: 1,
        stepValidation: {
            1: false,  // Destination
            2: false,  // Colis  
            3: true,   // Options (optionnel)
            4: false   // Résultats
        },
        userInteraction: {
            lastInput: 0,
            hasManuallyNavigated: false,
            fieldsFocused: new Set()
        },
        adrSelected: false,
        typeAutoSelected: false,
        userInteracting: false,
        lastProgressTime: 0
    },

    // Cache DOM
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.setupValidation();
        this.setupSteps();
        this.loadHistory();
        this.updateUI();
        this.createDebugPanel();
        console.log('🧮 Calculateur module initialisé avec flow intelligent');
    },

    /**
     * Cache des éléments DOM
     */
    cacheDOMElements() {
        this.dom = {
            form: document.getElementById('calculatorForm'),
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            type: document.getElementById('type'),
            palettes: document.getElementById('palettes'),
            paletteEur: document.getElementById('palette_eur'),
            adr: document.getElementsByName('adr'),
            enlevement: document.getElementById('enlevement'),
            optionSup: document.getElementById('option_sup'),
            
            // Boutons de navigation
            calculateBtn: document.getElementById('calculateBtn'),
            nextBtn: document.getElementById('nextBtn'),
            prevBtn: document.getElementById('prevBtn'),
            
            // Éléments d'interface
            resultsContent: document.getElementById('resultsContent'),
            resultsContainer: document.getElementById('resultsContainer'),
            calcStatus: document.getElementById('calcStatus'),
            progressFill: document.getElementById('progressFill'),
            
            // Groupes conditionnels
            palettesGroup: document.getElementById('palettesGroup'),
            paletteEurGroup: document.getElementById('paletteEurGroup'),
            
            // Éléments de navigation
            stepBtns: document.querySelectorAll('.calc-step-btn'),
            stepContents: document.querySelectorAll('.calc-step-content'),
            toggleBtns: document.querySelectorAll('.calc-toggle-btn')
        };
    },

    /**
     * Configuration des événements - VERSION AMÉLIORÉE UX
     */
    setupEventListeners() {
        // ========================================
        // VALIDATION DOUCE AVEC DEBOUNCE INTELLIGENT
        // ========================================
        
        if (this.dom.departement) {
            // Validation progressive sans auto-navigation
            this.dom.departement.addEventListener('input', 
                this.debounce(() => {
                    this.state.userInteraction.lastInput = Date.now();
                    this.validateDepartement();
                    this.updateStepValidation();
                    this.smartAutoProgress();
                }, this.config.debounceDelay)
            );
            
            // Validation à la perte de focus avec suggestion
            this.dom.departement.addEventListener('blur', () => {
                this.validateDepartement();
                this.updateStepValidation();
                this.suggestNextStepIfReady();
            });
            
            // Nettoyage des erreurs au focus
            this.dom.departement.addEventListener('focus', () => {
                this.state.userInteraction.fieldsFocused.add('departement');
                this.clearFieldError('departement');
            });
        }

        if (this.dom.poids) {
            this.dom.poids.addEventListener('input', 
                this.debounce(() => {
                    this.state.userInteraction.lastInput = Date.now();
                    this.validatePoids();
                    this.updateStepValidation();
                    this.autoSelectTypeByWeight();
                    this.smartAutoProgress();
                }, this.config.debounceDelay)
            );
            
            this.dom.poids.addEventListener('blur', () => {
                this.validatePoids();
                this.updateStepValidation();
                this.suggestNextStepIfReady();
            });
            
            this.dom.poids.addEventListener('focus', () => {
                this.state.userInteraction.fieldsFocused.add('poids');
                this.clearFieldError('poids');
            });
        }

        // ========================================
        // GESTION DU TYPE AVEC CONDITIONS
        // ========================================
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.handleTypeChange();
                this.updateStepValidation();
            });
        }

        // ========================================
        // NAVIGATION MANUELLE - PRIORITAIRE
        // ========================================
        
        // Boutons de navigation des étapes
        this.dom.stepBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const step = parseInt(e.target.closest('.calc-step-btn').dataset.step);
                this.state.userInteraction.hasManuallyNavigated = true;
                this.goToStep(step);
            });
        });

        // Bouton Suivant
        if (this.dom.nextBtn) {
            this.dom.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.state.userInteraction.hasManuallyNavigated = true;
                this.nextStep();
            });
        }

        // Bouton Précédent
        if (this.dom.prevBtn) {
            this.dom.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.state.userInteraction.hasManuallyNavigated = true;
                this.prevStep();
            });
        }

        // ========================================
        // SOUMISSION ET CALCUL
        // ========================================
        
        if (this.dom.form) {
            this.dom.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // ========================================
        // NAVIGATION CLAVIER INTELLIGENTE
        // ========================================
        document.addEventListener('keydown', (e) => {
            // Entrée pour progression douce
            if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey) {
                this.handleEnterKey(e);
            }
            
            // Navigation par flèches
            if (e.key === 'ArrowRight' && e.ctrlKey) {
                e.preventDefault();
                this.nextStep();
            }
            if (e.key === 'ArrowLeft' && e.ctrlKey) {
                e.preventDefault();
                this.prevStep();
            }
        });
    },

    /**
     * Progression automatique intelligente
     */
    smartAutoProgress() {
        // Ne pas progresser si l'utilisateur navigue manuellement
        if (this.state.userInteraction.hasManuallyNavigated) {
            return;
        }
        
        // Ne pas progresser si l'utilisateur vient de saisir quelque chose
        const timeSinceLastInput = Date.now() - this.state.userInteraction.lastInput;
        if (timeSinceLastInput < this.config.progressDelay) {
            return;
        }
        
        const currentStep = this.state.currentStep;
        
        // Vérifier si l'étape actuelle est valide
        if (this.state.stepValidation[currentStep] && currentStep < 4) {
            // Afficher une indication visuelle douce
            this.showStepSuggestion(currentStep + 1);
            
            // Auto-progression TRÈS douce après délai supplémentaire
            setTimeout(() => {
                if (!this.state.userInteraction.hasManuallyNavigated && 
                    this.state.stepValidation[currentStep]) {
                    this.goToStep(currentStep + 1);
                }
            }, this.config.progressDelay);
        }
    },

    /**
     * Sélection automatique du type selon le poids
     */
    autoSelectTypeByWeight() {
        if (!this.dom.poids || !this.dom.type) return;
        
        const poids = parseFloat(this.dom.poids.value);
        if (isNaN(poids) || this.state.typeAutoSelected) return;
        
        // Auto-sélection intelligente
        if (poids >= this.config.poidsSeuilPalette && this.dom.type.value === 'colis') {
            this.dom.type.value = 'palette';
            this.handleTypeChange();
            this.state.typeAutoSelected = true;
            
            this.showMessage('🏗️ Type "Palette" sélectionné automatiquement', 'info', 2000);
        }
    },

    /**
     * Gestion progressive de la touche Entrée
     */
    handleEnterKey(event) {
        const activeElement = document.activeElement;
        const currentStep = this.state.currentStep;
        
        // Si on est dans un champ de saisie
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'SELECT')) {
            event.preventDefault();
            
            // Validation du champ actuel
            if (activeElement.id === 'departement') {
                this.validateDepartement();
            } else if (activeElement.id === 'poids') {
                this.validatePoids();
            }
            
            // Suggestion douce de passage à l'étape suivante
            setTimeout(() => {
                this.suggestNextStepIfReady();
            }, this.config.validationDelay);
            
        } else if (currentStep === 4) {
            // Dernière étape : lancer le calcul
            this.handleCalculate();
        }
    },

    /**
     * Suggestion douce pour passer à l'étape suivante
     */
    suggestNextStepIfReady() {
        // Ne pas suggérer si l'utilisateur navigue manuellement
        if (this.state.userInteraction.hasManuallyNavigated) {
            return;
        }
        
        // Ne pas suggérer si l'utilisateur vient de saisir quelque chose
        const timeSinceLastInput = Date.now() - this.state.userInteraction.lastInput;
        if (timeSinceLastInput < this.config.progressDelay) {
            return;
        }
        
        const currentStep = this.state.currentStep;
        
        // Vérifier si l'étape actuelle est valide
        if (this.state.stepValidation[currentStep] && currentStep < 4) {
            // Afficher une indication visuelle douce
            this.showStepSuggestion(currentStep + 1);
            
            // Auto-progression TRÈS douce après délai supplémentaire
            setTimeout(() => {
                if (!this.state.userInteraction.hasManuallyNavigated && 
                    this.state.stepValidation[currentStep]) {
                    this.goToStep(currentStep + 1);
                }
            }, this.config.progressDelay);
        }
    },

    /**
     * Affichage d'une suggestion visuelle
     */
    showStepSuggestion(step) {
        const stepBtn = document.querySelector(`[data-step="${step}"]`);
        if (stepBtn) {
            stepBtn.style.animation = 'pulse 0.8s ease-in-out 2';
            setTimeout(() => {
                stepBtn.style.animation = '';
            }, 1600);
        }
    },

    /**
     * Configuration de la validation
     */
    setupValidation() {
        // Validation temps réel avec indicateurs visuels améliorés
        const requiredFields = ['departement', 'poids'];
        
        requiredFields.forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                // Aide contextuelle au focus
                field.addEventListener('focus', () => {
                    this.showFieldHelp(fieldName);
                });
                
                // Validation douce pendant la saisie
                field.addEventListener('input', 
                    this.debounce(() => {
                        this.validateFieldSoftly(fieldName);
                    }, this.config.validationDelay)
                );
            }
        });
    },

    /**
     * Validation douce d'un champ (sans erreurs brutales)
     */
    validateFieldSoftly(fieldName) {
        const field = this.dom[fieldName];
        if (!field) return;

        let isValid = false;
        let hint = '';

        if (fieldName === 'departement') {
            const value = field.value.trim();
            if (value.length === 0) {
                hint = 'Entrez le numéro du département...';
            } else if (value.length < 2) {
                hint = 'Continuez à saisir...';
            } else if (!/^[0-9]{2,3}$/.test(value)) {
                hint = 'Format: 75, 69, 974, etc.';
            } else {
                isValid = true;
                hint = '✓ Département valide';
            }
        } else if (fieldName === 'poids') {
            const value = parseFloat(field.value);
            if (isNaN(value) || value <= 0) {
                hint = 'Entrez le poids en kg...';
            } else if (value > 32000) {
                hint = 'Maximum 32000 kg';
            } else {
                isValid = true;
                hint = `✓ ${value} kg`;
            }
        }

        // Mise à jour de l'interface avec douceur
        this.updateFieldStatus(fieldName, isValid, hint);
        return isValid;
    },

    /**
     * Mise à jour du statut d'un champ
     */
    updateFieldStatus(fieldName, isValid, hint) {
        const field = this.dom[fieldName];
        const helpElement = field.parentElement.querySelector('.calc-field-help');
        
        // Mise à jour de la classe du champ
        field.classList.remove('error', 'valid');
        if (isValid) {
            field.classList.add('valid');
        }
        
        // Mise à jour de l'aide contextuelle
        if (helpElement) {
            helpElement.textContent = hint;
            helpElement.className = 'calc-field-help';
            if (isValid) {
                helpElement.classList.add('success');
            }
        }
    },

    /**
     * Affichage d'aide contextuelle
     */
    showFieldHelp(fieldName) {
        const hints = {
            'departement': 'Saisissez le numéro du département de livraison (2 ou 3 chiffres)',
            'poids': 'Entrez le poids total de votre envoi en kilogrammes'
        };
        
        const field = this.dom[fieldName];
        const helpElement = field.parentElement.querySelector('.calc-field-help');
        
        if (helpElement && hints[fieldName]) {
            helpElement.textContent = hints[fieldName];
            helpElement.style.opacity = '1';
        }
    },

    /**
     * Nettoyage des erreurs de champ
     */
    clearFieldError(fieldName) {
        const field = this.dom[fieldName];
        const errorElement = document.getElementById(fieldName + 'Error');
        
        field.classList.remove('error');
        if (errorElement) {
            errorElement.textContent = '';
        }
    },

    /**
     * Configuration des étapes de navigation
     */
    setupSteps() {
        // Gestion des boutons d'étapes dans la progression
        const stepButtons = document.querySelectorAll('.calc-step[data-step]');
        stepButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const step = parseInt(e.target.closest('.calc-step').dataset.step);
                if (this.canNavigateToStep(step)) {
                    this.state.userInteraction.hasManuallyNavigated = true;
                    this.goToStep(step);
                }
            });
        });

        // Gestion du type de colis pour afficher/masquer les champs palettes
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.handleTypeChange();
            });
        }

        // Validation et progression intelligente
        this.setupIntelligentProgression();
    },

    /**
     * Gestion du changement de type de colis
     */
    handleTypeChange() {
        const selectedType = this.dom.type.value;
        const palettesGroup = this.dom.palettesGroup;
        const paletteEurGroup = this.dom.paletteEurGroup;
        
        if (selectedType === 'palette') {
            if (palettesGroup) {
                palettesGroup.style.display = 'block';
                palettesGroup.classList.add('show');
            }
            if (paletteEurGroup) {
                paletteEurGroup.style.display = 'block';
                paletteEurGroup.classList.add('show');
            }
        } else {
            if (palettesGroup) {
                palettesGroup.style.display = 'none';
                palettesGroup.classList.remove('show');
            }
            if (paletteEurGroup) {
                paletteEurGroup.style.display = 'none';
                paletteEurGroup.classList.remove('show');
            }
        }
    },

    /**
     * Progression intelligente et non intrusive
     */
    setupIntelligentProgression() {
        // Observer les changements dans les champs principaux
        ['departement', 'poids'].forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                field.addEventListener('input', 
                    this.debounce(() => {
                        this.checkAutoProgression();
                    }, 1200) // Délai augmenté pour laisser l'utilisateur finir
                );
            }
        });
    }

    // SUITE DE LA PARTIE 1...

    /**
     * Vérification de progression automatique douce
     */
    checkAutoProgression() {
        // Ne pas progresser si l'utilisateur navigue manuellement
        if (this.state.userInteraction.hasManuallyNavigated) {
            return;
        }

        const currentStep = this.state.currentStep;
        const isCurrentStepValid = this.validateCurrentStep();
        
        if (isCurrentStepValid && currentStep < 3) {
            // Suggérer la prochaine étape avec animation douce
            this.suggestNextStep();
        }
    },

    /**
     * Suggestion de prochaine étape (non forcée)
     */
    suggestNextStep() {
        const nextStep = this.state.currentStep + 1;
        const nextStepElement = document.querySelector(`[data-step="${nextStep}"]`);
        
        if (nextStepElement) {
            // Animation de suggestion
            nextStepElement.style.animation = 'pulse 1s ease-in-out 2';
            
            // Retirer l'animation après
            setTimeout(() => {
                nextStepElement.style.animation = '';
            }, 2000);
        }
    },

    /**
     * Navigation vers une étape
     */
    goToStep(step) {
        if (step < 1 || step > 3) return;
        
        // Masquer toutes les étapes
        this.dom.stepContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Supprimer active de tous les boutons d'étapes
        document.querySelectorAll('.calc-step').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Afficher l'étape demandée
        const targetContent = document.querySelector(`[data-step="${step}"]`);
        if (targetContent) {
            targetContent.classList.add('active');
        }
        
        // Activer le bouton d'étape
        const targetStep = document.querySelector(`.calc-step[data-step="${step}"]`);
        if (targetStep) {
            targetStep.classList.add('active');
        }
        
        // Mettre à jour l'état actuel
        this.state.currentStep = step;
        
        // Mettre à jour la barre de progression
        this.updateProgressBar();
        
        // Mettre à jour la visibilité des boutons
        this.updateNavigationButtons();
        
        // Focus sur le premier champ de l'étape
        this.focusFirstFieldInStep(step);
    },

    /**
     * Étape suivante
     */
    nextStep() {
        const currentStep = this.state.currentStep;
        if (currentStep < 3 && this.validateCurrentStep()) {
            this.goToStep(currentStep + 1);
        }
    },

    /**
     * Étape précédente
     */
    prevStep() {
        const currentStep = this.state.currentStep;
        if (currentStep > 1) {
            this.goToStep(currentStep - 1);
        }
    },

    /**
     * Mise à jour de la barre de progression
     */
    updateProgressBar() {
        const progress = (this.state.currentStep / 3) * 100;
        if (this.dom.progressFill) {
            this.dom.progressFill.style.width = progress + '%';
        }
    },

    /**
     * Mise à jour des boutons de navigation
     */
    updateNavigationButtons() {
        const currentStep = this.state.currentStep;
        
        // Bouton précédent
        if (this.dom.prevBtn) {
            this.dom.prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
        }
        
        // Bouton suivant
        if (this.dom.nextBtn) {
            this.dom.nextBtn.style.display = currentStep < 3 ? 'block' : 'none';
        }
        
        // Bouton calculer
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.style.display = currentStep === 3 ? 'block' : 'none';
        }
    },

    /**
     * Focus sur le premier champ de l'étape
     */
    focusFirstFieldInStep(step) {
        const stepContent = document.querySelector(`[data-step="${step}"].active`);
        if (stepContent) {
            const firstInput = stepContent.querySelector('input, select');
            if (firstInput) {
                setTimeout(() => {
                    firstInput.focus();
                }, 300); // Délai pour la transition
            }
        }
    },

    /**
     * Vérification si on peut naviguer vers une étape
     */
    canNavigateToStep(step) {
        // On peut toujours revenir en arrière
        if (step <= this.state.currentStep) {
            return true;
        }
        
        // Pour aller de l'avant, vérifier les étapes précédentes
        for (let i = 1; i < step; i++) {
            if (!this.state.stepValidation[i]) {
                return false;
            }
        }
        
        return true;
    },

    /**
     * Validation de l'étape actuelle
     */
    validateCurrentStep() {
        const step = this.state.currentStep;
        
        switch (step) {
            case 1:
                return this.validateDepartement();
            case 2:
                return this.validatePoids();
            case 3:
                return true; // Options toujours valides
            default:
                return false;
        }
    },

    /**
     * Mise à jour de la validation des étapes
     */
    updateStepValidation() {
        const step1Valid = this.validateDepartement();
        const step2Valid = this.validatePoids();
        
        this.state.stepValidation[1] = step1Valid;
        this.state.stepValidation[2] = step2Valid;
        this.state.stepValidation[3] = true; // Options toujours valides
        
        // Mettre à jour l'interface
        this.updateStepVisualValidation();
    },

    /**
     * Mise à jour visuelle de la validation des étapes
     */
    updateStepVisualValidation() {
        Object.keys(this.state.stepValidation).forEach(step => {
            const stepElement = document.querySelector(`.calc-step[data-step="${step}"]`);
            if (stepElement) {
                stepElement.classList.remove('valid', 'invalid');
                
                if (this.state.stepValidation[step]) {
                    stepElement.classList.add('valid');
                } else if (parseInt(step) < this.state.currentStep) {
                    stepElement.classList.add('invalid');
                }
            }
        });
    },

    /**
     * Validation du département
     */
    validateDepartement() {
        const field = this.dom.departement;
        if (!field) return false;
        
        const value = field.value.trim();
        const errorElement = document.getElementById('departementError');
        
        // Nettoyage
        field.classList.remove('error', 'valid');
        if (errorElement) errorElement.textContent = '';
        
        if (value === '') {
            return false; // Pas d'erreur si vide, juste pas valide
        }
        
        if (!/^[0-9]{2,3}$/.test(value)) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = 'Format invalide. Utilisez 2 ou 3 chiffres (ex: 75, 974).';
            }
            return false;
        }
        
        // Validation supplémentaire des départements existants
        const deptNum = parseInt(value);
        const validDepts = [
            // Métropole
            ...Array.from({length: 95}, (_, i) => i + 1),
            // DOM-TOM
            971, 972, 973, 974, 975, 976, 984, 986, 987, 988
        ];
        
        if (!validDepts.includes(deptNum)) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = 'Département inconnu. Vérifiez le numéro.';
            }
            return false;
        }
        
        field.classList.add('valid');
        return true;
    },

    /**
     * Validation du poids
     */
    validatePoids() {
        const field = this.dom.poids;
        if (!field) return false;
        
        const value = parseFloat(field.value);
        const errorElement = document.getElementById('poidsError');
        
        // Nettoyage
        field.classList.remove('error', 'valid');
        if (errorElement) errorElement.textContent = '';
        
        if (field.value === '') {
            return false; // Pas d'erreur si vide, juste pas valide
        }
        
        if (isNaN(value) || value <= 0) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = 'Le poids doit être un nombre positif.';
            }
            return false;
        }
        
        if (value > 32000) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = 'Poids maximum : 32000 kg.';
            }
            return false;
        }
        
        if (value < 0.1) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = 'Poids minimum : 0.1 kg.';
            }
            return false;
        }
        
        field.classList.add('valid');
        return true;
    },

    /**
     * Gestion du calcul
     */
    async handleCalculate() {
        if (this.state.isCalculating) {
            return;
        }
        
        // Validation finale
        if (!this.validateCurrentStep()) {
            this.showError('Veuillez corriger les erreurs avant de calculer.');
            return;
        }
        
        this.state.isCalculating = true;
        this.showStatus('Calcul en cours...', 'loading');
        
        try {
            const formData = this.getFormData();
            const results = await this.calculateTransport(formData);
            
            if (results.success) {
                this.displayResults(results);
                this.addToHistory(formData, results);
                this.showStatus('Calcul terminé avec succès', 'success');
            } else {
                throw new Error(results.error || 'Erreur de calcul');
            }
            
        } catch (error) {
            console.error('Erreur calcul:', error);
            this.showError('Erreur lors du calcul: ' + error.message);
        } finally {
            this.state.isCalculating = false;
        }
    },

    /**
     * Récupération des données du formulaire
     */
    getFormData() {
        const adrElements = this.dom.adr;
        let adrValue = 'non';
        
        if (adrElements && adrElements.length > 0) {
            for (let element of adrElements) {
                if (element.checked) {
                    adrValue = element.value;
                    break;
                }
            }
        }
        
        return {
            departement: this.dom.departement?.value.trim() || '',
            poids: parseFloat(this.dom.poids?.value) || 0,
            type: this.dom.type?.value || 'colis',
            adr: adrValue,
            option_sup: this.dom.optionSup?.value || 'standard',
            enlevement: this.dom.enlevement?.checked ? 'oui' : 'non',
            palettes: parseInt(this.dom.palettes?.value) || 1,
            palette_eur: parseInt(this.dom.paletteEur?.value) || 0
        };
    },

    /**
     * Appel API pour le calcul
     */
    async calculateTransport(formData) {
        const response = await fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData)
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        return await response.json();
    },

    /**
     * Affichage des résultats
     */
    displayResults(results) {
        const container = this.dom.resultsContainer;
        const placeholder = this.dom.resultsContent;
        
        if (!container || !results.carriers) return;
        
        // Masquer le placeholder
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Afficher le conteneur de résultats
        container.style.display = 'block';
        container.innerHTML = '';
        
        // Trier les transporteurs par prix
        const sortedCarriers = Object.entries(results.carriers)
            .filter(([_, data]) => data.disponible)
            .sort((a, b) => a[1].total - b[1].total);
        
        if (sortedCarriers.length === 0) {
            container.innerHTML = '<div class="calc-no-results">Aucun transporteur disponible pour cette destination.</div>';
            return;
        }
        
        sortedCarriers.forEach(([carrier, data], index) => {
            const resultCard = this.createResultCard(carrier, data, index === 0);
            container.appendChild(resultCard);
        });
        
        // Scroll vers les résultats
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    /**
     * Création d'une carte de résultat
     */
    createResultCard(carrier, data, isBest) {
        const card = document.createElement('div');
        card.className = `calc-result-card ${isBest ? 'best-price' : ''}`;
        
        const carrierNames = {
            'xpo': 'XPO Logistics',
            'heppner': 'Heppner',
            'kuehne': 'Kuehne+Nagel'
        };
        
        const carrierName = carrierNames[carrier] || carrier.toUpperCase();
        
        card.innerHTML = `
            <div class="calc-result-header">
                <div class="calc-carrier-info">
                    <h3 class="calc-carrier-name">${carrierName}</h3>
                    ${isBest ? '<span class="best-price-badge">Meilleur prix</span>' : ''}
                </div>
                <div class="calc-price-info">
                    <div class="calc-price">${data.total.toFixed(2)} €</div>
                    <div class="calc-price-label">TTC</div>
                </div>
            </div>
            <div class="calc-result-details">
                <div class="calc-detail-item">
                    <span>Service:</span>
                    <span class="calc-detail-value">${data.service}</span>
                </div>
                <div class="calc-detail-item">
                    <span>Délai:</span>
                    <span class="calc-detail-value">${data.delai}</span>
                </div>
                <div class="calc-detail-item">
                    <span>Prix HT:</span>
                    <span class="calc-detail-value">${data.prix.toFixed(2)} €</span>
                </div>
                <div class="calc-detail-item">
                    <span>Taxes:</span>
                    <span class="calc-detail-value">${data.taxes.toFixed(2)} €</span>
                </div>
            </div>
        `;
        
        return card;
    },

    /**
     * Affichage du statut
     */
    showStatus(message, type) {
        const statusElement = this.dom.calcStatus;
        if (!statusElement) return;
        
        statusElement.textContent = message;
        statusElement.className = `calc-status ${type}`;
        statusElement.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => {
                statusElement.style.display = 'none';
            }, 3000);
        }
    },

    /**
     * Affichage d'une erreur
     */
    showError(message) {
        this.showStatus(message, 'error');
    },

    /**
     * Affichage d'un message temporaire
     */
    showMessage(message, type = 'info', duration = 3000) {
        this.showStatus(message, type);
        
        if (duration > 0) {
            setTimeout(() => {
                const statusElement = this.dom.calcStatus;
                if (statusElement) {
                    statusElement.style.display = 'none';
                }
            }, duration);
        }
    },

    /**
     * Mise en évidence des options ADR
     */
    highlightAdrOptions() {
        const adrCards = document.querySelectorAll('.calc-radio-card');
        adrCards.forEach(card => {
            card.style.animation = 'pulse 0.5s ease-in-out 3';
            setTimeout(() => {
                card.style.animation = '';
            }, 1500);
        });
    },

    /**
     * Ajout à l'historique
     */
    addToHistory(formData, results) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            formData,
            results,
            id: Date.now()
        };
        
        this.state.history.unshift(historyItem);
        
        // Limiter l'historique à 10 éléments
        if (this.state.history.length > 10) {
            this.state.history = this.state.history.slice(0, 10);
        }
        
        this.saveHistory();
        this.updateHistoryDisplay();
    },

    /**
     * Sauvegarde de l'historique
     */
    saveHistory() {
        try {
            localStorage.setItem('calc_history', JSON.stringify(this.state.history));
        } catch (e) {
            console.warn('Impossible de sauvegarder l\'historique:', e);
        }
    },

    /**
     * Chargement de l'historique
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
     * Mise à jour de l'affichage de l'historique
     */
    updateHistoryDisplay() {
        const historySection = document.getElementById('historySection');
        const historyContent = document.getElementById('historyContent');
        
        if (!historySection || !historyContent) return;
        
        if (this.state.history.length > 0) {
            historySection.style.display = 'block';
            
            historyContent.innerHTML = this.state.history.map(item => {
                const date = new Date(item.timestamp).toLocaleString('fr-FR');
                return `
                    <div class="calc-history-item">
                        <div class="calc-history-header">
                            <span class="calc-history-date">${date}</span>
                            <span class="calc-history-dest">Dept. ${item.formData.departement}</span>
                        </div>
                        <div class="calc-history-details">
                            ${item.formData.poids}kg - ${item.formData.type}
                        </div>
                    </div>
                `;
            }).join('');
        }
    },

    /**
     * Création du panneau de debug
     */
    createDebugPanel() {
        // Créer le panneau de debug seulement si en mode dev
        if (typeof window !== 'undefined' && window.location.search.includes('debug=1')) {
            const debugPanel = document.createElement('div');
            debugPanel.id = 'calcDebugPanel';
            debugPanel.className = 'calc-debug-panel';
            debugPanel.innerHTML = `
                <div class="calc-debug-header">
                    <h4>🐛 Debug Calculateur</h4>
                    <button onclick="this.parentElement.parentElement.style.display='none'">×</button>
                </div>
                <div class="calc-debug-content">
                    <div>Étape actuelle: <span id="debugCurrentStep">1</span></div>
                    <div>Validations: <span id="debugValidations">{}</span></div>
                    <div>Interactions: <span id="debugInteractions">{}</span></div>
                </div>
            `;
            
            debugPanel.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                background: white;
                border: 1px solid #ddd;
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 9999;
                font-family: monospace;
                font-size: 12px;
                width: 300px;
            `;
            
            document.body.appendChild(debugPanel);
            
            // Mise à jour périodique du debug
            setInterval(() => {
                this.updateDebugPanel();
            }, 1000);
        }
    },

    /**
     * Mise à jour du panneau de debug
     */
    updateDebugPanel() {
        const debugPanel = document.getElementById('calcDebugPanel');
        if (!debugPanel) return;
        
        const currentStepEl = document.getElementById('debugCurrentStep');
        const validationsEl = document.getElementById('debugValidations');
        const interactionsEl = document.getElementById('debugInteractions');
        
        if (currentStepEl) currentStepEl.textContent = this.state.currentStep;
        if (validationsEl) validationsEl.textContent = JSON.stringify(this.state.stepValidation);
        if (interactionsEl) interactionsEl.textContent = JSON.stringify({
            hasManuallyNavigated: this.state.userInteraction.hasManuallyNavigated,
            lastInput: Math.round((Date.now() - this.state.userInteraction.lastInput) / 1000) + 's ago'
        });
    },

    /**
     * Mise à jour de l'interface générale
     */
    updateUI() {
        this.updateStepValidation();
        this.updateProgressBar();
        this.updateNavigationButtons();
        this.updateHistoryDisplay();
    },

    /**
     * Fonction debounce améliorée
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Fonctions globales pour les toggles des sections
window.toggleAbout = function() {
    const content = document.getElementById('aboutContent');
    const toggle = document.getElementById('aboutToggle');
    
    if (content && toggle) {
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            toggle.textContent = '▲';
        } else {
            content.style.display = 'none';
            toggle.textContent = '▼';
        }
    }
};

window.toggleExpress = function() {
    const content = document.getElementById('expressContent');
    const toggle = document.getElementById('expressToggle');
    
    if (content && toggle) {
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            toggle.textContent = '▲';
        } else {
            content.style.display = 'none';
            toggle.textContent = '▼';
        }
    }
};

window.toggleHistory = function() {
    const content = document.getElementById('historyContent');
    const toggle = document.getElementById('historyToggle');
    
    if (content && toggle) {
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            toggle.textContent = '▲';
        } else {
            content.style.display = 'none';
            toggle.textContent = '▼';
        }
    }
};

window.toggleDebug = function() {
    const content = document.getElementById('debugContent');
    const toggle = document.getElementById('debugToggle');
    
    if (content && toggle) {
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            toggle.textContent = '▲';
        } else {
            content.style.display = 'none';
            toggle.textContent = '▼';
        }
    }
};

window.contactExpress = function() {
    alert('Fonction de contact Express en développement.\nContactez le service commercial pour une demande express.');
};

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    CalculateurModule.init();
});

// Export global pour compatibilité
window.CalculateurModule = CalculateurModule;
