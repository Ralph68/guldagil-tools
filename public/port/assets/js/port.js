/**
 * Titre: Module JavaScript calculateur de frais de port - Version corrigée
 * Chemin: /public/port/assets/js/port.js  
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // État du module
    state: {
        isCalculating: false,
        currentStep: 1,
        adrSelected: false,
        history: [],
        lastResults: null
    },

    // Cache DOM
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.activateStep(1);
        console.log('🧮 Calculateur Port initialisé');
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
            palettesGroup: document.getElementById('palettesGroup'),
            paletteEurGroup: document.getElementById('paletteEurGroup'),
            adr: document.getElementById('adr'),
            enlevement: document.getElementById('enlevement'),
            calcStatus: document.getElementById('calcStatus'),
            resultsContent: document.getElementById('resultsContent'),
            debugContainer: document.getElementById('debugContainer')
        };
    },

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Navigation département
        this.dom.departement.addEventListener('input', () => {
            clearTimeout(this.deptTimeout);
            this.dom.departement.classList.remove('valid');
            
            this.deptTimeout = setTimeout(() => {
                if (this.validateDepartement()) {
                    this.dom.departement.classList.add('valid');
                    setTimeout(() => {
                        this.activateStep(2);
                        if (this.dom.poids.offsetParent !== null) {
                            this.dom.poids.focus();
                        }
                    }, 300);
                }
            }, 300);
        });

        // NOUVELLE LOGIQUE POIDS : Si > 60kg = forcément palette
        this.dom.poids.addEventListener('input', () => {
            this.dom.poids.classList.remove('valid');
            const poids = parseFloat(this.dom.poids.value) || 0;
            
            if (poids > 0) {
                this.dom.poids.classList.add('valid');
                
                // LOGIQUE AUTOMATIQUE : > 60kg = palette
                if (poids > 60) {
                    this.dom.type.value = 'palette';
                    this.dom.type.classList.add('valid');
                    this.dom.type.disabled = true;
                    
                    // Calcul automatique nombre de palettes (1 palette = ~300kg max)
                    const nbPalettes = Math.ceil(poids / 300);
                    this.dom.palettes.value = nbPalettes;
                    
                } else {
                    // ≤ 60kg : réactiver le choix type
                    this.dom.type.disabled = false;
                    if (this.dom.type.value === 'palette') {
                        this.dom.type.value = '';
                        this.dom.type.classList.remove('valid');
                    }
                }
                
                this.updatePaletteVisibility();
            }
        });

        // Gestion type 
        this.dom.type.addEventListener('change', () => {
            if (this.dom.type.value) {
                this.dom.type.classList.add('valid');
                this.updatePaletteVisibility();
            }
        });

        // Gestion toggles ADR
        document.querySelectorAll('[data-adr]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.dom.adr.value = e.target.dataset.adr;
                this.state.adrSelected = true;
                
                // Passage automatique aux options si tout est rempli
                const poidsOk = parseFloat(this.dom.poids.value) > 0;
                const typeOk = this.dom.type.value !== '';
                
                if (poidsOk && typeOk) {
                    setTimeout(() => {
                        this.activateStep(3);
                        this.autoCalculateStandard();
                    }, 300);
                }
            });
        });

        // Gestion enlèvement
        document.querySelectorAll('[data-enlevement]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.dom.enlevement.value = e.target.dataset.enlevement;
            });
        });

        // Navigation par étapes
        document.querySelectorAll('.calc-step-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!e.target.classList.contains('disabled')) {
                    const step = parseInt(e.target.dataset.step);
                    this.activateStep(step);
                }
            });
        });

        // Soumission formulaire
        this.dom.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCalculate();
        });
    },

    /**
     * Validation département
     */
    validateDepartement() {
        const value = this.dom.departement.value.trim();
        return value.length >= 2 && /^[0-9]+$/.test(value);
    },

    /**
     * NOUVELLE GESTION palette EUR avec consigne
     */
    updatePaletteVisibility() {
        const type = this.dom.type.value;
        const poids = parseFloat(this.dom.poids.value) || 0;
        
        const isPalette = type === 'palette';
        
        // Affichage groupe palettes
        this.dom.palettesGroup.style.display = isPalette ? 'block' : 'none';
        
        // Affichage groupe palette EUR (consigne) seulement si palette
        if (this.dom.paletteEurGroup) {
            this.dom.paletteEurGroup.style.display = isPalette ? 'block' : 'none';
            
            if (!isPalette && this.dom.paletteEur) {
                this.dom.paletteEur.value = '0';
            }
        }
    },

    /**
     * Navigation séquentielle
     */
    activateStep(step) {
        document.querySelectorAll('.calc-form-step').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.calc-step-btn').forEach(el => {
            el.classList.remove('active');
            el.classList.add('disabled');
        });

        for (let i = 1; i <= step; i++) {
            const stepEl = document.querySelector(`.calc-form-step[data-step="${i}"]`);
            const btnEl = document.querySelector(`.calc-step-btn[data-step="${i}"]`);
            if (!btnEl || !stepEl) continue;

            const indicator = btnEl.querySelector('.calc-step-indicator');
            if (!indicator) continue;

            if (i < step) {
                btnEl.classList.remove('disabled');
                btnEl.classList.add('completed');
                indicator.textContent = '✓';
            } else if (i === step) {
                stepEl.classList.add('active');
                btnEl.classList.add('active');
                btnEl.classList.remove('disabled');
                indicator.textContent = i;
            }
        }
        this.state.currentStep = step;
    },

    /**
     * Calcul automatique standard
     */
    async autoCalculateStandard() {
        const formData = this.getFormData();
        this.dom.calcStatus.textContent = '⏳ Calcul automatique...';
        
        try {
            const data = await this.callAPI(formData);
            this.displayResults(data);
        } catch (error) {
            console.error('Erreur:', error);
            this.dom.calcStatus.textContent = '❌ Erreur de calcul';
        }
    },

    /**
     * Calcul principal
     */
    async handleCalculate() {
        if (this.state.isCalculating) return;
        
        const formData = this.getFormData();
        
        this.state.isCalculating = true;
        this.dom.form.classList.add('loading');
        this.dom.calcStatus.textContent = '⏳ Calcul en cours...';
        
        try {
            const data = await this.callAPI(formData);
            this.displayResults(data);
            this.saveToHistory(formData, data);
        } catch (error) {
            console.error('Erreur:', error);
            this.dom.calcStatus.textContent = '❌ Erreur de calcul';
        } finally {
            this.state.isCalculating = false;
            this.dom.form.classList.remove('loading');
        }
    },

    /**
     * Récupération données formulaire avec palette EUR
     */
    getFormData() {
        const formData = new FormData(this.dom.form);
        const params = Object.fromEntries(formData.entries());
        
        // Ajouter palette_eur si visible
        if (this.dom.paletteEurGroup && this.dom.paletteEurGroup.style.display !== 'none') {
            params.palette_eur = parseInt(this.dom.paletteEur.value) || 0;
        }
        
        return params;
    },

    /**
     * Appel API
     */
    async callAPI(params) {
        const response = await fetch('?ajax=calculate', {
            method: 'POST',
            body: new URLSearchParams(params)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    },

    /**
     * NOUVEAU : Affichage des résultats avec classement et pliage
     */
    displayResults(data) {
        const status = this.dom.calcStatus;
        const content = this.dom.resultsContent;
        
        this.state.lastResults = data;
        
        if (!data.success) {
            status.textContent = '❌ ' + (data.error || 'Erreur de calcul');
            content.innerHTML = `
                <div class="calc-error-state">
                    <div class="calc-error-icon">❌</div>
                    <p><strong>Erreur de calcul</strong></p>
                    <p>${data.error || 'Erreur inconnue'}</p>
                </div>
            `;
            return;
        }
        
        status.textContent = `✅ Calculé en ${data.time_ms}ms`;
        
        // Tri des transporteurs par prix (croissant)
        const carriers = Object.entries(data.carriers)
            .filter(([_, info]) => info.available)
            .sort((a, b) => (a[1].price || 0) - (b[1].price || 0));
        
        if (carriers.length === 0) {
            content.innerHTML = `
                <div class="calc-warning-state">
                    <div class="calc-warning-icon">⚠️</div>
                    <p><strong>Aucun tarif disponible</strong></p>
                    <p>Vérifiez le département ou consultez le debug</p>
                </div>
            `;
            return;
        }
        
        const carrierIcons = {
            'xpo': '🚛',
            'heppner': '🚚', 
            'kn': '📦'
        };
        
        let html = '<div class="calc-results-grid">';
        
        // Premier résultat (moins cher) : toujours visible
        const [bestCarrier, bestInfo] = carriers[0];
        const bestIcon = carrierIcons[bestCarrier] || '🚛';
        
        html += `
            <div class="carrier-card winner">
                <div class="carrier-badge">💰 Meilleur prix</div>
                <div class="carrier-name">
                    ${bestIcon} ${bestInfo.name}
                </div>
                <div class="carrier-price">${bestInfo.formatted}</div>
                <div class="carrier-delay" id="delay-${bestCarrier}">
                    ⏱️ Calcul délai...
                </div>
            </div>
        `;
        
        // Autres résultats : masqués par défaut si > 1 résultat
        if (carriers.length > 1) {
            html += `
                <div class="other-carriers">
                    <button class="show-others-btn" onclick="toggleOtherCarriers()">
                        <span id="othersToggleText">Voir ${carriers.length - 1} autre(s) transporteur(s)</span>
                        <span class="toggle-icon" id="othersToggleIcon">▼</span>
                    </button>
                    <div class="other-carriers-content" id="otherCarriersContent" style="display: none;">
            `;
            
            for (let i = 1; i < carriers.length; i++) {
                const [carrier, info] = carriers[i];
                const icon = carrierIcons[carrier] || '🚛';
                
                html += `
                    <div class="carrier-card secondary">
                        <div class="carrier-name">
                            ${icon} ${info.name}
                        </div>
                        <div class="carrier-price">${info.formatted}</div>
                        <div class="carrier-delay" id="delay-${carrier}">
                            ⏱️ Calcul délai...
                        </div>
                    </div>
                `;
            }
            
            html += `</div></div>`;
        }
        
        html += '</div>';
        
        content.innerHTML = html;
        
        // Récupération des délais pour tous les transporteurs
        carriers.forEach(([carrier, _]) => {
            this.fetchDelay(carrier);
        });
        
        // Affichage historique et debug
        document.getElementById('historySection').style.display = 'block';
        
        // Affichage du debug avec détails de calcul
        if (data.debug) {
            this.displayDebugInfo(data.debug);
        }
    },

    /**
     * NOUVEAU : Affichage debug détaillé
     */
    displayDebugInfo(debugData) {
        if (!this.dom.debugContainer) return;
        
        this.dom.debugContainer.style.display = 'block';
        
        let debugHtml = '<div class="debug-steps">';
        
        // Étapes de calcul par transporteur
        Object.entries(debugData).forEach(([carrier, steps]) => {
            if (typeof steps === 'object' && steps.steps) {
                debugHtml += `
                    <div class="debug-carrier">
                        <h4>🔍 ${carrier.toUpperCase()} - Étapes de calcul</h4>
                        <div class="debug-steps-list">
                `;
                
                steps.steps.forEach((step, index) => {
                    debugHtml += `
                        <div class="debug-step">
                            <strong>Étape ${index + 1}:</strong> ${step}
                        </div>
                    `;
                });
                
                if (steps.finalPrice) {
                    debugHtml += `
                        <div class="debug-final">
                            <strong>Prix final:</strong> ${steps.finalPrice}€
                        </div>
                    `;
                }
                
                debugHtml += '</div></div>';
            }
        });
        
        debugHtml += '</div>';
        
        document.getElementById('debugContent').innerHTML = debugHtml;
    },

    /**
     * Récupération délai transporteur
     */
    async fetchDelay(carrier) {
        try {
            const dept = this.dom.departement.value;
            const option = document.querySelector('input[name="option_sup"]:checked')?.value || 'standard';
            
            const response = await fetch(`?ajax=delay&carrier=${carrier}&dept=${dept}&option=${option}`);
            const data = await response.json();
            
            const delayEl = document.getElementById(`delay-${carrier}`);
            if (delayEl && data.success) {
                delayEl.innerHTML = `⏱️ ${data.delay}`;
            }
        } catch (error) {
            console.error('Erreur délai:', error);
        }
    },

    /**
     * Sauvegarde historique
     */
    saveToHistory(formData, results) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            params: formData,
            results: results,
            id: Date.now()
        };
        
        this.state.history.unshift(historyItem);
        if (this.state.history.length > 10) {
            this.state.history = this.state.history.slice(0, 10);
        }
        
        try {
            localStorage.setItem('calculateur_history', JSON.stringify(this.state.history));
        } catch (error) {
            console.warn('Impossible de sauvegarder l\'historique');
        }
    }
};

// NOUVELLES Fonctions globales pour l'interface
window.resetForm = function() {
    // Reset formulaire
    document.getElementById('calculatorForm').reset();
    document.getElementById('adr').value = 'non';
    document.getElementById('enlevement').value = 'non';
    
    // Reset groupes palette
    document.getElementById('palettesGroup').style.display = 'none';
    if (document.getElementById('paletteEurGroup')) {
        document.getElementById('paletteEurGroup').style.display = 'none';
    }
    
    // Réactiver type
    document.getElementById('type').disabled = false;
    
    // Reset toggles
    document.querySelectorAll('[data-adr]').forEach(btn => btn.classList.remove('active'));
    document.querySelector('[data-adr="non"]').classList.add('active');
    
    document.querySelectorAll('[data-enlevement]').forEach(btn => btn.classList.remove('active'));
    document.querySelector('[data-enlevement="non"]').classList.add('active');
    
    // Reset validation
    document.querySelectorAll('.calc-input').forEach(input => input.classList.remove('valid'));
    
    // Reset résultats
    document.getElementById('resultsContent').innerHTML = `
        <div class="calc-empty-state">
            <div class="calc-empty-icon">🧮</div>
            <p class="calc-empty-text">Complétez le formulaire pour voir les tarifs</p>
        </div>
    `;
    
    document.getElementById('calcStatus').textContent = '⏳ En attente...';
    document.getElementById('historySection').style.display = 'none';
    document.getElementById('debugContainer').style.display = 'none';
    
    // Reset navigation
    CalculateurModule.state.adrSelected = false;
    CalculateurModule.activateStep(1);
    document.getElementById('departement').focus();
};

window.toggleOtherCarriers = function() {
    const content = document.getElementById('otherCarriersContent');
    const text = document.getElementById('othersToggleText');
    const icon = document.getElementById('othersToggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        text.textContent = 'Masquer les autres transporteurs';
        icon.textContent = '▲';
    } else {
        content.style.display = 'none';
        text.textContent = text.textContent.replace('Masquer', 'Voir').replace('autres transporteurs', 'autre(s) transporteur(s)');
        icon.textContent = '▼';
    }
};

window.toggleHistory = function() {
    const content = document.getElementById('historyContent');
    const toggle = document.getElementById('historyToggle');
    
    if (content.style.display === 'block') {
        content.style.display = 'none';
        toggle.textContent = '▼';
    } else {
        content.style.display = 'block';
        toggle.textContent = '▲';
    }
};

window.toggleDebug = function() {
    const content = document.getElementById('debugContent');
    const toggle = document.getElementById('debugToggle');
    
    if (content.style.display === 'block') {
        content.style.display = 'none';
        toggle.textContent = '▼';
    } else {
        content.style.display = 'block';
        toggle.textContent = '▲';
    }
};

// Initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CalculateurModule.init());
} else {
    CalculateurModule.init();
}
