/**
 * Titre: Gestionnaire de formulaire - Module calculateur
 * Chemin: /public/assets/js/modules/calculateur/form-handler.js
 * Version: 0.5 beta + build
 * 
 * Gestion du formulaire, validation et événements
 * Dépendance: calculateur.js (chargé en premier)
 */

// ========================================
// MODULE GESTION FORMULAIRE
// ========================================

window.Calculateur = window.Calculateur || {};

Calculateur.Form = {
    
    /**
     * Initialisation du gestionnaire de formulaire
     */
    init() {
        this.setupEventListeners();
        this.loadFormState();
        this.updateButtonState();
        
        if (Calculateur.Config.DEBUG) {
            console.log('📝 Module Form initialisé');
        }
    },
    
    /**
     * Configuration des événements
     */
    setupEventListeners() {
        const elements = Calculateur.Elements;
        
        // Département avec auto-progression
        if (elements.departement) {
            elements.departement.addEventListener('input', this.handleDepartementInput.bind(this));
            elements.departement.addEventListener('focus', () => elements.departement.select());
        }
        
        // Poids avec validation
        if (elements.poids) {
            elements.poids.addEventListener('input', this.handlePoidsInput.bind(this));
        }
        
        // Types d'envoi
        elements.typeInputs.forEach(input => {
            input.addEventListener('change', this.handleTypeChange.bind(this));
        });
        
        // ADR
        elements.adrInputs.forEach(input => {
            input.addEventListener('change', this.handleAdrChange.bind(this));
        });
        
        // Options supplémentaires
        if (elements.optionSup) {
            elements.optionSup.addEventListener('change', this.handleOptionChange.bind(this));
        }
        
        // Palettes
        if (elements.palettes) {
            elements.palettes.addEventListener('input', this.handlePalettesChange.bind(this));
        }
        
        // Validation en temps réel sur tous les champs
        this.setupRealtimeValidation();
    },
    
    /**
     * Validation en temps réel
     */
    setupRealtimeValidation() {
        const fields = [
            Calculateur.Elements.departement,
            Calculateur.Elements.poids
        ].filter(Boolean);
        
        fields.forEach(field => {
            field.addEventListener('input', () => {
                this.validateField(field);
                Calculateur.State.updateFormData();
                this.updateButtonState();
                
                // Déclencher calcul auto si formulaire valide
                if (Calculateur.Core.triggerAutoCalculation) {
                    Calculateur.Core.triggerAutoCalculation();
                }
            });
        });
    },
    
    /**
     * Gestion saisie département
     */
    handleDepartementInput() {
        const value = Calculateur.Elements.departement.value.trim();
        
        // Auto-progression si département valide
        if (value.length === 2 && /^\d{2}$/.test(value)) {
            const deptNum = parseInt(value);
            if (deptNum >= 1 && deptNum <= 95) {
                setTimeout(() => {
                    if (Calculateur.Elements.poids) {
                        Calculateur.Elements.poids.focus();
                        Calculateur.Elements.poids.select();
                    }
                }, 200);
            }
        }
    },
    
    /**
     * Gestion saisie poids
     */
    handlePoidsInput() {
        const poids = parseFloat(Calculateur.Elements.poids.value) || 0;
        
        // Suggestion auto palette si poids > seuil
        if (poids >= Calculateur.Config.PALETTE_THRESHOLD) {
            this.showPaletteOptions();
            this.suggestPaletteType();
        } else {
            this.hidePaletteOptions();
        }
        
        // Vérification limites
        if (poids > Calculateur.Config.MAX_POIDS) {
            this.showWarning(`Poids élevé (${poids}kg). Vérifiez les conditions de transport.`);
        }
    },
    
    /**
     * Gestion changement type
     */
    handleTypeChange() {
        const selectedType = this.getSelectedValue('type');
        
        if (selectedType === 'palette') {
            this.showPaletteOptions();
        } else {
            this.hidePaletteOptions();
        }
        
        // Adaptation des options selon le type
        this.updateOptionsAvailability();
    },
    
    /**
     * Gestion changement ADR
     */
    handleAdrChange() {
        const isAdr = this.getSelectedValue('adr') === 'oui';
        
        // Désactiver certaines options si ADR
        if (isAdr) {
            this.disableIncompatibleOptions();
        } else {
            this.enableAllOptions();
        }
    },
    
    /**
     * Gestion changement options
     */
    handleOptionChange() {
        // Mise à jour immédiate des données
        Calculateur.State.updateFormData();
        this.updateButtonState();
    },
    
    /**
     * Gestion changement palettes
     */
    handlePalettesChange() {
        this.updatePaletteButtons();
    },
    
    /**
     * Afficher options palette
     */
    showPaletteOptions() {
        if (Calculateur.Elements.paletteOptions) {
            Calculateur.Elements.paletteOptions.style.display = 'block';
            Calculateur.Elements.paletteOptions.classList.add('fade-in');
        }
    },
    
    /**
     * Masquer options palette
     */
    hidePaletteOptions() {
        if (Calculateur.Elements.paletteOptions) {
            Calculateur.Elements.paletteOptions.style.display = 'none';
            if (Calculateur.Elements.palettes) {
                Calculateur.Elements.palettes.value = '0';
            }
        }
    },
    
    /**
     * Suggestion type palette selon poids
     */
    suggestPaletteType() {
        const poids = parseFloat(Calculateur.Elements.poids.value) || 0;
        
        // Auto-sélection du type palette
        const typeRadio = document.querySelector('input[name="type"][value="palette"]');
        if (typeRadio && poids >= Calculateur.Config.PALETTE_THRESHOLD) {
            typeRadio.checked = true;
            this.handleTypeChange();
        }
    },
    
    /**
     * Mise à jour boutons palette
     */
    updatePaletteButtons() {
        const currentValue = Calculateur.Elements.palettes.value;
        document.querySelectorAll('.palette-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.value === currentValue) {
                btn.classList.add('active');
            }
        });
    },
    
    /**
     * Désactiver options incompatibles avec ADR
     */
    disableIncompatibleOptions() {
        if (Calculateur.Elements.optionSup) {
            const options = Calculateur.Elements.optionSup.querySelectorAll('option');
            options.forEach(option => {
                if (option.dataset.adrIncompatible === 'true') {
                    option.disabled = true;
                    option.textContent = option.textContent.replace(' (⚠️ Non disponible avec ADR)', '') + ' (⚠️ Non disponible avec ADR)';
                }
            });
        }
    },
    
    /**
     * Réactiver toutes les options
     */
    enableAllOptions() {
        if (Calculateur.Elements.optionSup) {
            const options = Calculateur.Elements.optionSup.querySelectorAll('option');
            options.forEach(option => {
                option.disabled = false;
                option.textContent = option.textContent.replace(' (⚠️ Non disponible avec ADR)', '');
            });
        }
    },
    
    /**
     * Mise à jour disponibilité options
     */
    updateOptionsAvailability() {
        const selectedType = this.getSelectedValue('type');
        const isAdr = this.getSelectedValue('adr') === 'oui';
        
        if (isAdr) {
            this.disableIncompatibleOptions();
        } else {
            this.enableAllOptions();
        }
    },
    
    /**
     * Obtenir la valeur sélectionnée d'un groupe radio
     */
    getSelectedValue(name) {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        return selected ? selected.value : '';
    },
    
    /**
     * Récupération des données du formulaire
     */
    getFormData() {
        return {
            departement: Calculateur.Elements.departement?.value?.trim() || '',
            poids: parseFloat(Calculateur.Elements.poids?.value) || 0,
            type: this.getSelectedValue('type') || 'colis',
            adr: this.getSelectedValue('adr') || 'non',
            option_sup: Calculateur.Elements.optionSup?.value || 'standard',
            enlevement: Calculateur.Elements.enlevement?.checked ? '1' : '0',
            palettes: Calculateur.Elements.palettes?.value || '0'
        };
    },
    
    /**
     * Validation complète du formulaire
     */
    validateForm() {
        const data = this.getFormData();
        const errors = [];
        
        // Département
        if (!data.departement || !/^\d{2}$/.test(data.departement)) {
            errors.push('Département invalide (2 chiffres requis)');
        } else {
            const dept = parseInt(data.departement);
            if (dept < 1 || dept > 95) {
                errors.push('Département hors limites (01-95)');
            }
        }
        
        // Poids
        if (!data.poids || data.poids < Calculateur.Config.MIN_POIDS) {
            errors.push(`Poids minimum: ${Calculateur.Config.MIN_POIDS}kg`);
        }
        if (data.poids > Calculateur.Config.MAX_POIDS) {
            errors.push(`Poids maximum: ${Calculateur.Config.MAX_POIDS}kg`);
        }
        
        // Type
        if (!['colis', 'palette'].includes(data.type)) {
            errors.push('Type d\'envoi invalide');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors,
            data: data
        };
    },
    
    /**
     * Validation d'un champ spécifique
     */
    validateField(field) {
        if (!field) return;
        
        field.classList.remove('error', 'valid');
        
        let isValid = true;
        const value = field.value.trim();
        
        if (field === Calculateur.Elements.departement) {
            isValid = /^\d{2}$/.test(value) && parseInt(value) >= 1 && parseInt(value) <= 95;
        } else if (field === Calculateur.Elements.poids) {
            const poids = parseFloat(value);
            isValid = poids >= Calculateur.Config.MIN_POIDS && poids <= Calculateur.Config.MAX_POIDS;
        }
        
        field.classList.add(isValid ? 'valid' : 'error');
        return isValid;
    },
    
    /**
     * Mise à jour état du bouton de calcul
     */
    updateButtonState() {
        if (!Calculateur.Elements.btnCalculate) return;
        
        const isValid = this.validateForm().isValid;
        const isCalculating = Calculateur.State.isCalculating;
        
        Calculateur.Elements.btnCalculate.disabled = !isValid || isCalculating;
        
        if (!isValid) {
            Calculateur.Elements.btnCalculate.innerHTML = '<span>📝</span> Complétez le formulaire';
        } else if (!isCalculating) {
            Calculateur.Elements.btnCalculate.innerHTML = '<span>🚀</span> Calculer les tarifs';
        }
    },
    
    /**
     * Sauvegarde état formulaire
     */
    saveFormState() {
        try {
            const formData = this.getFormData();
            sessionStorage.setItem('calculateur_form_state', JSON.stringify(formData));
        } catch (e) {
            // Ignore les erreurs de stockage
        }
    },
    
    /**
     * Chargement état formulaire
     */
    loadFormState() {
        try {
            const saved = sessionStorage.getItem('calculateur_form_state');
            if (saved) {
                const data = JSON.parse(saved);
                this.populateForm(data);
            }
        } catch (e) {
            // Ignore les erreurs de stockage
        }
    },
    
    /**
     * Remplissage du formulaire
     */
    populateForm(data) {
        Object.entries(data).forEach(([key, value]) => {
            const field = Calculateur.Elements.form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                } else if (field.type === 'radio') {
                    const radioButton = Calculateur.Elements.form.querySelector(`[name="${key}"][value="${value}"]`);
                    if (radioButton) radioButton.checked = true;
                } else {
                    field.value = value;
                }
                
                // Déclencher les événements pour mise à jour UI
                field.dispatchEvent(new Event('change'));
            }
        });
    },
    
    /**
     * Sauvegarde dans l'historique
     */
    saveToHistory(formData, result) {
        try {
            let history = JSON.parse(localStorage.getItem('calculateur_history') || '[]');
            
            const entry = {
                timestamp: Date.now(),
                params: formData,
                best: result.best,
                bestCarrier: result.bestCarrier,
                id: Date.now().toString()
            };
            
            history.unshift(entry);
            history = history.slice(0, 20); // Garder 20 dernières
            
            localStorage.setItem('calculateur_history', JSON.stringify(history));
        } catch (e) {
            // Ignore les erreurs de stockage
        }
    },
    
    /**
     * Récupération de l'historique
     */
    getHistory() {
        try {
            return JSON.parse(localStorage.getItem('calculateur_history') || '[]');
        } catch (e) {
            return [];
        }
    },
    
    /**
     * Suppression d'une entrée de l'historique
     */
    removeFromHistory(id) {
        try {
            let history = this.getHistory();
            history = history.filter(entry => entry.id !== id);
            localStorage.setItem('calculateur_history', JSON.stringify(history));
            return true;
        } catch (e) {
            return false;
        }
    },
    
    /**
     * Nettoyage complet de l'historique
     */
    clearHistory() {
        try {
            localStorage.removeItem('calculateur_history');
            return true;
        } catch (e) {
            return false;
        }
    },
    
    /**
     * Restauration depuis l'historique
     */
    restoreFromHistory(id) {
        const history = this.getHistory();
        const entry = history.find(h => h.id === id);
        
        if (entry && entry.params) {
            this.populateForm(entry.params);
            
            if (Calculateur.UI && Calculateur.UI.showSuccess) {
                Calculateur.UI.showSuccess('Paramètres restaurés depuis l\'historique');
            }
            
            return true;
        }
        
        return false;
    },
    
    /**
     * Création de boutons palette raccourcis
     */
    createPaletteButtons() {
        const paletteContainer = document.getElementById('palette-buttons');
        if (!paletteContainer) return;
        
        const buttons = [
            { value: '1', label: '1 palette' },
            { value: '2', label: '2 palettes' },
            { value: '3', label: '3 palettes' },
            { value: '4', label: '4 palettes' },
            { value: '5', label: '5+ palettes' }
        ];
        
        buttons.forEach(btn => {
            const button = Calculateur.Utils.dom.create('button', {
                type: 'button',
                class: 'palette-btn btn btn-outline-secondary btn-sm',
                'data-value': btn.value
            }, btn.label);
            
            button.addEventListener('click', () => {
                if (Calculateur.Elements.palettes) {
                    Calculateur.Elements.palettes.value = btn.value;
                    this.updatePaletteButtons();
                    this.handlePalettesChange();
                }
            });
            
            paletteContainer.appendChild(button);
        });
    },
    
    /**
     * Validation avancée pour cas spéciaux
     */
    validateSpecialCases(data) {
        const warnings = [];
        
        // Poids élevé sans palette
        if (data.poids > 200 && data.type === 'colis') {
            warnings.push('Poids élevé pour un colis - Envisagez le type "Palette"');
        }
        
        // ADR avec options incompatibles
        if (data.adr === 'oui' && ['premium13', 'premium18'].includes(data.option_sup)) {
            warnings.push('Les options Premium peuvent ne pas être disponibles avec ADR');
        }
        
        // Nombreuses palettes sans enlèvement
        if (parseInt(data.palettes) > 3 && data.enlevement === '0') {
            warnings.push('Nombre élevé de palettes - Envisagez l\'option enlèvement');
        }
        
        return warnings;
    },
    
    /**
     * Auto-complétion département
     */
    setupDepartementAutocomplete() {
        const input = Calculateur.Elements.departement;
        if (!input) return;
        
        // Départements français avec noms
        const departements = {
            '01': 'Ain', '02': 'Aisne', '03': 'Allier', '04': 'Alpes-de-Haute-Provence',
            '05': 'Hautes-Alpes', '06': 'Alpes-Maritimes', '07': 'Ardèche', '08': 'Ardennes',
            '09': 'Ariège', '10': 'Aube', '11': 'Aude', '12': 'Aveyron',
            '13': 'Bouches-du-Rhône', '14': 'Calvados', '15': 'Cantal', '16': 'Charente',
            '17': 'Charente-Maritime', '18': 'Cher', '19': 'Corrèze', '21': 'Côte-d\'Or',
            '22': 'Côtes-d\'Armor', '23': 'Creuse', '24': 'Dordogne', '25': 'Doubs',
            '26': 'Drôme', '27': 'Eure', '28': 'Eure-et-Loir', '29': 'Finistère',
            '30': 'Gard', '31': 'Haute-Garonne', '32': 'Gers', '33': 'Gironde',
            '34': 'Hérault', '35': 'Ille-et-Vilaine', '36': 'Indre', '37': 'Indre-et-Loire',
            '38': 'Isère', '39': 'Jura', '40': 'Landes', '41': 'Loir-et-Cher',
            '42': 'Loire', '43': 'Haute-Loire', '44': 'Loire-Atlantique', '45': 'Loiret',
            '46': 'Lot', '47': 'Lot-et-Garonne', '48': 'Lozère', '49': 'Maine-et-Loire',
            '50': 'Manche', '51': 'Marne', '52': 'Haute-Marne', '53': 'Mayenne',
            '54': 'Meurthe-et-Moselle', '55': 'Meuse', '56': 'Morbihan', '57': 'Moselle',
            '58': 'Nièvre', '59': 'Nord', '60': 'Oise', '61': 'Orne',
            '62': 'Pas-de-Calais', '63': 'Puy-de-Dôme', '64': 'Pyrénées-Atlantiques',
            '65': 'Hautes-Pyrénées', '66': 'Pyrénées-Orientales', '67': 'Bas-Rhin',
            '68': 'Haut-Rhin', '69': 'Rhône', '70': 'Haute-Saône', '71': 'Saône-et-Loire',
            '72': 'Sarthe', '73': 'Savoie', '74': 'Haute-Savoie', '75': 'Paris',
            '76': 'Seine-Maritime', '77': 'Seine-et-Marne', '78': 'Yvelines', '79': 'Deux-Sèvres',
            '80': 'Somme', '81': 'Tarn', '82': 'Tarn-et-Garonne', '83': 'Var',
            '84': 'Vaucluse', '85': 'Vendée', '86': 'Vienne', '87': 'Haute-Vienne',
            '88': 'Vosges', '89': 'Yonne', '90': 'Territoire de Belfort', '91': 'Essonne',
            '92': 'Hauts-de-Seine', '93': 'Seine-Saint-Denis', '94': 'Val-de-Marne', '95': 'Val-d\'Oise'
        };
        
        // Affichage du nom du département
        input.addEventListener('input', () => {
            const value = input.value.trim();
            const departementName = departements[value];
            
            if (departementName) {
                input.title = `${value} - ${departementName}`;
                if (Calculateur.Utils.dom.$('#departement-name')) {
                    Calculateur.Utils.dom.$('#departement-name').textContent = departementName;
                }
            } else {
                input.title = '';
                if (Calculateur.Utils.dom.$('#departement-name')) {
                    Calculateur.Utils.dom.$('#departement-name').textContent = '';
                }
            }
        });
    },
    
    /**
     * Suggestions de poids selon le type
     */
    setupPoidsHelpers() {
        const input = Calculateur.Elements.poids;
        if (!input) return;
        
        const suggestions = {
            'colis': [1, 5, 10, 20, 30, 50],
            'palette': [100, 200, 400, 600, 800, 1000]
        };
        
        // Création des boutons suggestions
        const createSuggestionButtons = (type) => {
            const container = document.getElementById('poids-suggestions');
            if (!container) return;
            
            container.innerHTML = '';
            
            suggestions[type].forEach(weight => {
                const btn = Calculateur.Utils.dom.create('button', {
                    type: 'button',
                    class: 'btn btn-outline-info btn-sm me-1 mb-1',
                    'data-weight': weight
                }, `${weight}kg`);
                
                btn.addEventListener('click', () => {
                    input.value = weight;
                    input.dispatchEvent(new Event('input'));
                    input.focus();
                });
                
                container.appendChild(btn);
            });
        };
        
        // Mise à jour selon le type sélectionné
        Calculateur.Elements.typeInputs.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    createSuggestionButtons(radio.value);
                }
            });
        });
        
        // Initialisation
        const checkedType = document.querySelector('input[name="type"]:checked');
        if (checkedType) {
            createSuggestionButtons(checkedType.value);
        }
    },
    
    /**
     * Afficher avertissement
     */
    showWarning(message) {
        if (Calculateur.UI && Calculateur.UI.showWarning) {
            Calculateur.UI.showWarning(message);
        }
    },
    
    /**
     * Reset du formulaire
     */
    reset() {
        if (Calculateur.Elements.form) {
            Calculateur.Elements.form.reset();
        }
        
        this.hidePaletteOptions();
        this.enableAllOptions();
        this.updateButtonState();
        
        // Nettoyer affichages auxiliaires
        const departementName = document.getElementById('departement-name');
        if (departementName) departementName.textContent = '';
        
        // Sauvegarder état vide
        this.saveFormState();
    },
    
    /**
     * Validation et suggestions en temps réel
     */
    setupSmartValidation() {
        // Délai pour éviter trop de validations
        const validateWithDelay = Calculateur.Utils.debounce(() => {
            const data = this.getFormData();
            const warnings = this.validateSpecialCases(data);
            
            warnings.forEach(warning => {
                this.showWarning(warning);
            });
        }, 1000);
        
        // Attacher aux champs principaux
        [Calculateur.Elements.poids, Calculateur.Elements.optionSup].forEach(element => {
            if (element) {
                element.addEventListener('input', validateWithDelay);
                element.addEventListener('change', validateWithDelay);
            }
        });
    }
};
