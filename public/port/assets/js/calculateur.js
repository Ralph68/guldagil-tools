/**
 * Titre: Module JavaScript calculateur complet
 * Chemin: /public/port/assets/js/calculateur.js  
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // État du module
    state: {
        isCalculating: false,
        currentStep: 1,
        adrSelected: false,
        history: []
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
        console.log('🧮 Calculateur initialisé');
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
            resultsContent: document.getElementById('resultsContent')
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
                        // Ajoute le focus SEULEMENT si visible
                        setTimeout(() => {
                            if (this.dom.poids.offsetParent !== null) {
                                this.dom.poids.focus();
                            }
                        }, 50);
                    }, 300);
                }
            }, 300);
        });

        // Validation poids + mise à jour palette EUR
        this.dom.poids.addEventListener('input', () => {
            this.dom.poids.classList.remove('valid');
            if (parseFloat(this.dom.poids.value) > 0) {
                this.dom.poids.classList.add('valid');
                this.updatePaletteVisibility();
            }
        });

        // Gestion type + palette EUR
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
     * Gestion visibilité palette EUR
     */
    updatePaletteVisibility() {
        const type = this.dom.type.value;
        const poids = parseFloat(this.dom.poids.value) || 0;
        
        const isPalette = type === 'palette';
        const showEurOption = isPalette && poids > 60;
        
        this.dom.palettesGroup.style.display = isPalette ? 'block' : 'none';
        if (this.dom.paletteEurGroup) {
            this.dom.paletteEurGroup.style.display = showEurOption ? 'block' : 'none';
            
            if (!showEurOption && this.dom.paletteEur) {
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
            if (!btnEl || !stepEl) continue; // Protection anti-null

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
     * Affichage des résultats
     */
    displayResults(data) {
        const status = this.dom.calcStatus;
        const content = this.dom.resultsContent;
        
        if (!data.success) {
            status.textContent = '❌ ' + (data.error || 'Erreur de calcul');
            content.innerHTML = `
                <div style="text-align: center; padding: 30px; color: var(--error);">
                    <div style="font-size: 48px; margin-bottom: 10px;">❌</div>
                    <p><strong>Erreur de calcul</strong></p>
                    <p>${data.error || 'Erreur inconnue'}</p>
                </div>
            `;
            return;
        }
        
        status.textContent = `✅ Calculé en ${data.time_ms}ms`;
        
        let html = '<div class="results-grid">';
        let hasResults = false;
        
        const carrierIcons = {
            'xpo': '🚛',
            'heppner': '🚚', 
            'kn': '📦'
        };
        
        Object.entries(data.carriers).forEach(([carrier, info]) => {
            const cardClass = info.available ? 'available' : 'unavailable';
            const icon = carrierIcons[carrier] || '🚛';
            
            if (info.available) hasResults = true;
            
            html += `
                <div class="carrier-card ${cardClass}">
                    <div class="carrier-name">
                        ${icon} ${info.name}
                    </div>
                    <div class="carrier-price">${info.formatted}</div>
                    <div class="carrier-delay" id="delay-${carrier}">
                        ${info.available ? '⏱️ Calcul délai...' : '❌ Non disponible'}
                    </div>
                </div>
            `;
            
            if (info.available) {
                this.fetchDelay(carrier);
            }
        });
        
        html += '</div>';
        
        if (!hasResults) {
            html += `
                <div style="text-align: center; padding: 20px; color: var(--warning); background: rgba(245, 158, 11, 0.05); border-radius: 8px; margin-top: 15px;">
                    <div style="font-size: 24px; margin-bottom: 8px;">⚠️</div>
                    <p><strong>Aucun tarif disponible</strong></p>
                    <p>Vérifiez le département ou consultez le debug pour plus d'informations</p>
                </div>
            `;
        }
        
        content.innerHTML = html;
        document.getElementById('historySection').style.display = 'block';
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

// Fonctions globales pour l'interface
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
    
    // Reset toggles
    document.querySelectorAll('[data-adr]').forEach(btn => btn.classList.remove('active'));
    document.querySelector('[data-adr="non"]').classList.add('active');
    
    document.querySelectorAll('[data-enlevement]').forEach(btn => btn.classList.remove('active'));
    document.querySelector('[data-enlevement="non"]').classList.add('active');
    
    // Reset validation
    document.querySelectorAll('.form-input').forEach(input => input.classList.remove('valid'));
    
    // Reset résultats
    document.getElementById('resultsContent').innerHTML = `
        <div style="text-align: center; padding: 40px; color: var(--gray-500);">
            <div style="font-size: 48px; margin-bottom: 10px;">🧮</div>
            <p>Complétez le formulaire pour voir les tarifs</p>
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
