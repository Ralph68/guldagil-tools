/**
 * Titre: Module JavaScript calculateur de frais de port - Version CORRIGÉE
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // Configuration
    config: {
        apiUrl: '?ajax=calculate',
        debounceDelay: 500,
        maxRetries: 3,
        autoProgressDelay: 800, // Délai pour progression automatique
        waitForAdrDelay: 1500,  // Délai d'attente pour ADR
        poidsSeuilPalette: 150  // Seuil auto palette/colis
    },

    // État du module
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        currentStep: 1,
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
        this.loadHistory();
        this.setupValidation();
        this.setupSteps();
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
            adr: document.getElementById('adr'),
            enlevement: document.getElementById('enlevement'),
            optionSup: document.getElementById('option_sup'),
            calculateBtn: document.getElementById('calculateBtn'),
            resultsContent: document.getElementById('resultsContent'),
            calcStatus: document.getElementById('calcStatus'),
            palettesGroup: document.getElementById('palettesGroup'),
            paletteEurGroup: document.getElementById('paletteEurGroup'),
            
            // Éléments pour les étapes
            stepBtns: document.querySelectorAll('.calc-step-btn'),
            stepContents: document.querySelectorAll('.calc-step-content'),
            toggleBtns: document.querySelectorAll('.calc-toggle-btn')
        };
    },

    /**
     * Configuration des événements - VERSION CORRIGÉE
     */
    setupEventListeners() {
        // Validation temps réel avec progression INTELLIGENTE
        if (this.dom.departement) {
            this.dom.departement.addEventListener('input', 
                this.debounce(() => {
                    this.validateDepartement();
                    this.smartAutoProgress();
                }, this.config.debounceDelay)
            );
            
            this.dom.departement.addEventListener('blur', () => {
                this.state.userInteracting = false;
                this.validateDepartement();
                this.smartAutoProgress();
            });
        }

        if (this.dom.poids) {
            this.dom.poids.addEventListener('input', 
                this.debounce(() => {
                    this.validatePoids();
                    this.autoSelectTypeByWeight();
                    this.smartAutoProgress();
                }, this.config.debounceDelay)
            );
            
            this.dom.poids.addEventListener('blur', () => {
                this.state.userInteracting = false;
                this.validatePoids();
                this.autoSelectTypeByWeight();
                this.smartAutoProgress();
            });
        }

        // Gestion du type - ÉVITER LE CONFLIT AUTO/MANUEL
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.state.typeAutoSelected = false; // L'utilisateur a changé manuellement
                this.handleTypeChange();
                this.smartAutoProgress();
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

        // Gestion focus/blur pour détecter interaction utilisateur
        ['departement', 'poids', 'type'].forEach(field => {
            if (this.dom[field]) {
                this.dom[field].addEventListener('focus', () => {
                    this.state.userInteracting = true;
                });
            }
        });
    },

    /**
     * AUTO-PROGRESSION INTELLIGENTE - VERSION CORRIGÉE
     */
    smartAutoProgress() {
        // Éviter progression trop rapide pendant que l'utilisateur tape
        if (this.state.userInteracting) {
            console.log('🤖 Progression suspendue : utilisateur en interaction');
            return;
        }

        const now = Date.now();
        if (now - this.state.lastProgressTime < this.config.autoProgressDelay) {
            console.log('🤖 Progression suspendue : délai trop court');
            return;
        }

        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        
        console.log(`🤖 Auto-progression: dept=${deptValid}, poids=${poidsValid}, step=${this.state.currentStep}, typeAuto=${this.state.typeAutoSelected}`);
        
        // Étape 1 → 2 : Département valide
        if (deptValid && this.state.currentStep === 1) {
            console.log('🚀 Progression automatique : Étape 1 → 2');
            this.state.lastProgressTime = now;
            setTimeout(() => {
                this.activateStep(2);
                this.showMessage('⚖️ Maintenant, indiquez le poids de votre envoi', 'info', 3000);
                this.focusFirstFieldInStep(2);
            }, this.config.autoProgressDelay);
        }
        
        // Étape 2 → 3 : Département + Poids valides + Type auto-sélectionné
        else if (deptValid && poidsValid && this.state.currentStep === 2 && this.state.typeAutoSelected) {
            console.log('🚀 Progression automatique : Étape 2 → 3');
            this.state.lastProgressTime = now;
            setTimeout(() => {
                this.activateStep(3);
                this.showMessage('⚙️ Type suggéré selon le poids. ADR requis ?', 'info', 4000);
                this.highlightAdrOptions();
            }, this.config.autoProgressDelay);
        }
        
        // Pas de calcul automatique - Attendre explicitement la réponse ADR
    },

    /**
     * NOUVELLE FONCTION : Auto-sélection type selon poids
     */
    autoSelectTypeByWeight() {
        if (!this.dom.poids || !this.dom.type) return;
        
        const poids = parseFloat(this.dom.poids.value);
        if (isNaN(poids) || poids <= 0) return;
        
        // Auto-sélection intelligente
        let suggestedType = '';
        let reason = '';
        
        if (poids <= this.config.poidsSeuilPalette) {
            suggestedType = 'colis';
            reason = `Poids ${poids}kg → Suggéré: COLIS (≤ ${this.config.poidsSeuilPalette}kg)`;
        } else {
            suggestedType = 'palette';
            reason = `Poids ${poids}kg → Suggéré: PALETTE (> ${this.config.poidsSeuilPalette}kg)`;
        }
        
        // Appliquer uniquement si pas déjà défini manuellement par l'utilisateur
        if (this.dom.type.value === '' || this.state.typeAutoSelected) {
            this.dom.type.value = suggestedType;
            this.state.typeAutoSelected = true;
            console.log('🎯 ' + reason);
            this.showMessage(reason, 'success', 2500);
            this.handleTypeChange();
        }
    },

    /**
     * NOUVELLE FONCTION : Mise en évidence options ADR
     */
    highlightAdrOptions() {
        const adrButtons = document.querySelectorAll('[data-adr]');
        adrButtons.forEach(btn => {
            btn.style.animation = 'pulse 1s ease-in-out 3';
            btn.style.border = '2px solid #007bff';
        });
        
        setTimeout(() => {
            adrButtons.forEach(btn => {
                btn.style.animation = '';
                btn.style.border = '';
            });
        }, 3000);
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

        // Gestion des toggles ADR - LOGIQUE CORRIGÉE
        document.querySelectorAll('[data-adr]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleAdrSelection(e.target);
            });
        });

        // Gestion enlèvement
        document.querySelectorAll('[data-enlevement]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleEnlevementSelection(e.target);
            });
        });

        // Gestion type palette/colis
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.handleTypeChange();
            });
        }
    },

    /**
     * NOUVELLE FONCTION : Gestion sélection ADR
     */
    handleAdrSelection(btn) {
        // Désactiver tous les boutons ADR
        document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        this.dom.adr.value = btn.dataset.adr;
        this.state.adrSelected = true;
        
        const isAdr = btn.dataset.adr === 'oui';
        this.showMessage(
            isAdr ? '⚠️ ADR activé - Majoration appliquée' : '✅ Transport standard sélectionné',
            isAdr ? 'warning' : 'success',
            2000
        );
        
        // ATTENDRE avant de lancer le calcul automatique
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        const typeValid = this.dom.type.value !== '';
        
        if (deptValid && poidsValid && typeValid) {
            console.log('🎯 Toutes les données disponibles - Calcul dans ' + this.config.waitForAdrDelay + 'ms');
            this.showMessage('🧮 Calcul automatique dans quelques instants...', 'info', this.config.waitForAdrDelay);
            
            setTimeout(() => {
                this.handleCalculate();
            }, this.config.waitForAdrDelay);
        }
    },

    /**
     * NOUVELLE FONCTION : Gestion sélection enlèvement
     */
    handleEnlevementSelection(btn) {
        document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this.dom.enlevement.value = btn.dataset.enlevement;
        
        const isEnlevement = btn.dataset.enlevement === 'oui';
        this.showMessage(
            isEnlevement ? '🚚 Enlèvement activé' : '📮 Livraison standard',
            'info',
            1500
        );
    },

    /**
     * Gestion type palette/colis - VERSION CORRIGÉE
     */
    handleTypeChange() {
        const type = this.dom.type.value;
        
        if (this.dom.palettesGroup) {
            this.dom.palettesGroup.style.display = (type === 'palette') ? 'block' : 'none';
        }
        if (this.dom.paletteEurGroup) {
            this.dom.paletteEurGroup.style.display = (type === 'palette') ? 'block' : 'none';
        }
        
        // Réinitialiser les valeurs si changement de type
        if (type === 'colis') {
            if (this.dom.palettes) this.dom.palettes.value = '1';
            if (this.dom.paletteEur) this.dom.paletteEur.value = '0';
        }
    },

    /**
     * Calcul principal - VERSION CORRIGÉE
     */
    async handleCalculate() {
        if (this.state.isCalculating) {
            console.log('⏳ Calcul déjà en cours');
            return;
        }

        // Validation complète avant calcul
        if (!this.validateForm()) {
            this.showMessage('❌ Veuillez compléter tous les champs requis', 'error', 3000);
            return;
        }

        this.state.isCalculating = true;
        this.dom.form.classList.add('loading');
        
        if (this.dom.calcStatus) {
            this.dom.calcStatus.textContent = '⏳ Calcul en cours...';
        }

        const formData = new FormData(this.dom.form);
        const params = Object.fromEntries(formData.entries());
        
        // Log pour débogage
        this.addDebugInfo('Paramètres envoyés', params);

        try {
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                body: new URLSearchParams(params)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            this.addDebugInfo('Réponse serveur', data);
            
            if (data.success) {
                this.displayResults(data);
                this.addToHistory(params, data);
                this.showMessage('✅ Calcul terminé avec succès', 'success', 2000);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }

        } catch (error) {
            console.error('Erreur calcul:', error);
            this.addDebugInfo('Erreur', error.message);
            this.showMessage('❌ Erreur: ' + error.message, 'error', 5000);
            
            if (this.dom.calcStatus) {
                this.dom.calcStatus.textContent = '❌ Erreur de calcul';
            }
        } finally {
            this.state.isCalculating = false;
            this.dom.form.classList.remove('loading');
        }
    },

    /**
     * Affichage des résultats - VERSION CORRIGÉE
     */
    displayResults(data) {
        if (!this.dom.resultsContent) return;

        let html = '<div class="calc-results-wrapper">';
        
        // En-tête avec temps de calcul
        html += `<div class="calc-results-header">`;
        html += `<h3>🚛 Résultats de calcul</h3>`;
        html += `<small>Calculé en ${data.time_ms || 0}ms</small>`;
        html += `</div>`;

        // Résultats transporteurs
        if (data.carriers && Object.keys(data.carriers).length > 0) {
            html += '<div class="calc-results-grid">';
            
            Object.entries(data.carriers).forEach(([carrier, result]) => {
                const carrierName = this.getCarrierDisplayName(carrier);
                const price = result.prix_ttc || result.prix_ht || 0;
                const delay = result.delai || 'N/A';
                
                html += `<div class="calc-result-card">`;
                html += `<div class="calc-result-header">`;
                html += `<strong>${carrierName}</strong>`;
                html += `<span class="calc-result-delay">${delay}</span>`;
                html += `</div>`;
                html += `<div class="calc-result-price">`;
                html += `${this.formatPrice(price)} TTC`;
                html += `</div>`;
                if (result.prix_ht && result.prix_ht !== price) {
                    html += `<div class="calc-result-price-ht">`;
                    html += `HT: ${this.formatPrice(result.prix_ht)}`;
                    html += `</div>`;
                }
                html += `</div>`;
            });
            
            html += '</div>';
        } else {
            html += `<div class="calc-no-results">`;
            html += `<p>⚠️ Aucun transporteur disponible pour ces critères</p>`;
            html += `</div>`;
        }

        html += '</div>';
        
        this.dom.resultsContent.innerHTML = html;
        
        if (this.dom.calcStatus) {
            this.dom.calcStatus.textContent = '✅ Calcul terminé';
        }
    },

    /**
     * NOUVELLE FONCTION : Création panel de débogage
     */
    createDebugPanel() {
        const debugPanel = document.createElement('div');
        debugPanel.id = 'calc-debug-panel';
        debugPanel.innerHTML = `
            <div class="calc-debug-header" onclick="this.parentElement.classList.toggle('expanded')">
                <span>🔧 Debug</span>
                <span class="calc-debug-toggle">▼</span>
            </div>
            <div class="calc-debug-content">
                <div id="calc-debug-log"></div>
                <button type="button" onclick="document.getElementById('calc-debug-log').innerHTML = ''" 
                        style="margin-top: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px;">
                    Vider le log
                </button>
            </div>
        `;
        
        // Styles intégrés
        debugPanel.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow: hidden;
        `;
        
        const style = document.createElement('style');
        style.textContent = `
            #calc-debug-panel .calc-debug-header {
                background: #007bff;
                color: white;
                padding: 8px 12px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            #calc-debug-panel .calc-debug-content {
                display: none;
                padding: 10px;
                max-height: 250px;
                overflow-y: auto;
            }
            #calc-debug-panel.expanded .calc-debug-content {
                display: block;
            }
            #calc-debug-panel.expanded .calc-debug-toggle {
                transform: rotate(180deg);
            }
            #calc-debug-log div {
                margin-bottom: 8px;
                padding: 4px;
                background: white;
                border-radius: 3px;
                border-left: 3px solid #007bff;
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(debugPanel);
    },

    /**
     * NOUVELLE FONCTION : Ajouter info debug
     */
    addDebugInfo(label, data) {
        const debugLog = document.getElementById('calc-debug-log');
        if (!debugLog) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.innerHTML = `
            <strong>${timestamp} - ${label}:</strong><br>
            <pre style="margin: 4px 0; white-space: pre-wrap; font-size: 11px;">${
                typeof data === 'object' ? JSON.stringify(data, null, 2) : data
            }</pre>
        `;
        
        debugLog.appendChild(entry);
        debugLog.scrollTop = debugLog.scrollHeight;
    },

    /**
     * Validation formulaire complète
     */
    validateForm() {
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        const typeValid = this.dom.type && this.dom.type.value !== '';
        
        return deptValid && poidsValid && typeValid;
    },

    /**
     * Validation département
     */
    validateDepartement() {
        if (!this.dom.departement) return false;
        
        const value = this.dom.departement.value.trim();
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/i.test(value);
        
        this.updateFieldValidation('departement', isValid, 
            isValid ? '' : 'Département invalide (ex: 75, 69, 13, 2A)');
        
        return isValid;
    },

    /**
     * Validation poids
     */
    validatePoids() {
        if (!this.dom.poids) return false;
        
        const value = parseFloat(this.dom.poids.value);
        const isValid = value >= 1 && value <= 3000 && !isNaN(value);
        
        this.updateFieldValidation('poids', isValid, 
            isValid ? '' : 'Poids entre 1 et 3000 kg requis');
        
        return isValid;
    },

    /**
     * Mise à jour validation champ
     */
    updateFieldValidation(fieldName, isValid, errorMessage) {
        const field = this.dom[fieldName];
        if (!field) return;
        
        const wrapper = field.closest('.calc-form-group');
        if (!wrapper) return;
        
        // Supprime les anciens messages d'erreur
        const existingError = wrapper.querySelector('.calc-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Met à jour les classes
        field.classList.toggle('error', !isValid);
        wrapper.classList.toggle('has-error', !isValid);
        
        // Ajoute le message d'erreur si nécessaire
        if (!isValid && errorMessage) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'calc-error-message';
            errorDiv.textContent = errorMessage;
            errorDiv.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 4px;';
            wrapper.appendChild(errorDiv);
        }
    },

    /**
     * Activation d'une étape
     */
    activateStep(stepNumber) {
        const previousStep = this.state.currentStep;
        this.state.currentStep = stepNumber;
        
        // Mise à jour des boutons d'étapes
        this.dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.toggle('active', btnStep === stepNumber);
            btn.classList.toggle('completed', btnStep < stepNumber);
        });
        
        // Mise à jour du contenu des étapes
        this.dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            const isActive = contentStep === stepNumber;
            
            if (isActive) {
                content.style.display = 'block';
                setTimeout(() => {
                    content.classList.add('active');
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
        
        this.focusFirstFieldInStep(stepNumber);
        this.showStepNotification(stepNumber, previousStep);
    },

    /**
     * Focus sur le premier champ de l'étape
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
     * Notification de changement d'étape
     */
    showStepNotification(newStep, previousStep) {
        const messages = {
            1: '📍 Saisissez le département de destination',
            2: '⚖️ Indiquez le poids de votre envoi',
            3: '⚙️ Choisissez vos options d\'expédition'
        };
        
        if (newStep > previousStep && messages[newStep]) {
            this.showMessage(messages[newStep], 'info', 2500);
        }
    },

    /**
     * Gestion de la touche Entrée
     */
    handleEnterKey(e) {
        if (e.target.matches('input, select, textarea')) {
            e.preventDefault();
            
            const currentStep = this.state.currentStep;
            const deptValid = this.validateDepartement();
            const poidsValid = this.validatePoids();
            
            if (currentStep === 1 && deptValid) {
                this.activateStep(2);
            } else if (currentStep === 2 && deptValid && poidsValid) {
                this.activateStep(3);
            } else if (currentStep >= 3 && this.validateForm()) {
                this.handleCalculate();
            }
        }
    },

    /**
     * Affichage de messages
     */
    showMessage(message, type = 'info', duration = 3000) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `calc-message calc-message-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 9999;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        // Couleurs selon le type
        const colors = {
            success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
            error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
            warning: { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
            info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
        };
        
        const color = colors[type] || colors.info;
        messageDiv.style.backgroundColor = color.bg;
        messageDiv.style.borderLeft = `4px solid ${color.border}`;
        messageDiv.style.color = color.text;
        
        document.body.appendChild(messageDiv);
        
        // Animation d'entrée
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(0)';
        }, 100);
        
        // Suppression automatique
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, duration);
    },

    /**
     * Formatage prix
     */
    formatPrice(price) {
        if (!price || isNaN(price)) return '0,00 €';
        return parseFloat(price).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    },

    /**
     * Nom d'affichage transporteur
     */
    getCarrierDisplayName(carrier) {
        const names = {
            'xpo': 'XPO Logistics',
            'heppner': 'Heppner',
            'kn': 'Kuehne + Nagel'
        };
        return names[carrier] || carrier.toUpperCase();
    },

    /**
     * Ajout à l'historique
     */
    addToHistory(params, result) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            params: { ...params },
            result: { ...result },
            id: Date.now()
        };
        
        this.state.history.unshift(historyItem);
        
        // Limiter l'historique à 10 éléments
        if (this.state.history.length > 10) {
            this.state.history = this.state.history.slice(0, 10);
        }
        
        this.saveHistory();
    },

    /**
     * Sauvegarde historique
     */
    saveHistory() {
        try {
            // Pas de localStorage dans Claude.ai - stockage en mémoire uniquement
            console.log('📝 Historique mis à jour:', this.state.history.length, 'éléments');
        } catch (e) {
            console.warn('Impossible de sauvegarder l\'historique:', e);
        }
    },

    /**
     * Chargement historique
     */
    loadHistory() {
        try {
            // Pas de localStorage dans Claude.ai - initialisation vide
            this.state.history = [];
            console.log('📖 Historique initialisé');
        } catch (e) {
            console.warn('Impossible de charger l\'historique:', e);
            this.state.history = [];
        }
    },

    /**
     * Configuration validation
     */
    setupValidation() {
        // CSS pour la validation
        const style = document.createElement('style');
        style.textContent = `
            .calc-form-group.has-error input,
            .calc-form-group.has-error select {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }
            
            .calc-form-group input.error,
            .calc-form-group select.error {
                border-color: #dc3545 !important;
            }
            
            .calc-error-message {
                color: #dc3545 !important;
                font-size: 12px !important;
                margin-top: 4px !important;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .calc-form.loading {
                opacity: 0.7;
                pointer-events: none;
            }
            
            .calc-form.loading .calc-step-content {
                position: relative;
            }
            
            .calc-form.loading .calc-step-content::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }
        `;
        document.head.appendChild(style);
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
    }
};

// Auto-initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    CalculateurModule.init();
});

// Export pour compatibilité
window.CalculateurModule = CalculateurModule;
