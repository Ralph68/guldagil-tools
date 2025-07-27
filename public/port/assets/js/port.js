/**
 * Titre: JavaScript pour le calculateur de frais de port
 * Chemin: /public/port/assets/js/port.js
 * Version: 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧮 Calculateur initialisé - Version par étapes');
    
    // Cache DOM
    const dom = {
        form: document.getElementById('calculatorForm'),
        departement: document.getElementById('departement'),
        poids: document.getElementById('poids'),
        type: document.getElementById('type'),
        palettes: document.getElementById('palettes'),
        paletteEur: document.getElementById('palette_eur'),
        calculateBtn: document.querySelector('.btn-calculate'),
        resultsContent: document.getElementById('resultsContent'),
        calcStatus: document.getElementById('calcStatus'),
        stepBtns: document.querySelectorAll('.calc-step-btn'),
        stepContents: document.querySelectorAll('.calc-step-content'),
        debugContent: document.getElementById('debugContent'),
        palettesGroup: document.getElementById('palettesGroup'),
        paletteEurGroup: document.getElementById('paletteEurGroup'),
        nextBtns: document.querySelectorAll('.btn-next'),
        prevBtns: document.querySelectorAll('.btn-prev')
    };
    
    // État du calculateur
    let state = {
        currentStep: 1,
        isCalculating: false
    };
    
    // Debug helper
    function addDebug(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const debugEntry = document.createElement('div');
        debugEntry.className = 'debug-entry';
        
        let content = `<strong>${timestamp}:</strong> ${message}`;
        if (data) {
            content += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        }
        
        debugEntry.innerHTML = content;
        dom.debugContent.appendChild(debugEntry);
        dom.debugContent.scrollTop = dom.debugContent.scrollHeight;
        
        // Limiter à 20 entrées
        const entries = dom.debugContent.querySelectorAll('.debug-entry');
        if (entries.length > 20) {
            entries[0].remove();
        }
    }
    
    // Message temporaire
    function showTempMessage(message, type = 'info', duration = 3000) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `calc-temp-message ${type}`;
        msgDiv.textContent = message;
        document.body.appendChild(msgDiv);
        
        setTimeout(() => msgDiv.classList.add('show'), 100);
        setTimeout(() => {
            msgDiv.classList.remove('show');
            setTimeout(() => msgDiv.remove(), 300);
        }, duration);
    }
    
    addDebug('Module initialisé - Mode par étapes');
    
    // Gestion des étapes
    function goToStep(step) {
        if (step < 1 || step > 3) return;
        
        state.currentStep = step;
        addDebug(`Navigation vers étape ${step}`);
        
        // Mise à jour des boutons d'étape
        dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.toggle('active', btnStep === step);
            btn.classList.toggle('completed', btnStep < step);
        });
        
        // Affichage du contenu approprié
        dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            content.style.display = contentStep === step ? 'block' : 'none';
        });
    }
    
    // Auto-sélection type par poids
    function autoSelectType() {
        if (!dom.poids || !dom.type) return;
        
        const poids = parseFloat(dom.poids.value);
        if (isNaN(poids) || poids <= 0) return;
        
        let suggestedType = '';
        
        if (poids > 3000) {
            suggestedType = 'palette';
            showTempMessage('⚠️ Poids > 3000kg - Affrètement requis', 'warning', 4000);
        } else if (poids <= 150) {
            suggestedType = 'colis';
        } else {
            suggestedType = 'palette';
        }
        
        if (dom.type.value === '' || dom.type.value !== suggestedType) {
            dom.type.value = suggestedType;
            handleTypeChange();
        }
    }
    
    // Gestion type palette/colis
    function handleTypeChange() {
        const type = dom.type.value;
        
        if (type === 'palette') {
            dom.palettesGroup.style.display = 'block';
            dom.paletteEurGroup.style.display = 'block';
        } else {
            dom.palettesGroup.style.display = 'none';
            dom.paletteEurGroup.style.display = 'none';
        }
    }
    
    // Navigation avec les boutons suivant/précédent
    dom.nextBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const nextStep = parseInt(this.dataset.goto);
            goToStep(nextStep);
        });
    });
    
    dom.prevBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const prevStep = parseInt(this.dataset.goto);
            goToStep(prevStep);
        });
    });
    
    // Événements pour le poids et le type
    dom.poids.addEventListener('change', autoSelectType);
    dom.type.addEventListener('change', handleTypeChange);
    
    // Gestion du debug panel
    document.querySelector('.debug-header').addEventListener('click', function() {
        document.getElementById('debugPanel').classList.toggle('expanded');
    });
    
    // Gestion du formulaire
    dom.form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (state.isCalculating) {
            addDebug('Calcul déjà en cours - Ignoré');
            return;
        }
        
        state.isCalculating = true;
        dom.calcStatus.textContent = '⏳ Calcul en cours...';
        addDebug('Début calcul');
        
        const formData = new FormData(dom.form);
        
        fetch('?ajax=calculate', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayResults(data);
                dom.calcStatus.textContent = '✅ Calcul terminé';
                showTempMessage('✅ Calcul terminé avec succès', 'success', 2000);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('Erreur calcul:', error);
            dom.calcStatus.textContent = '❌ Erreur: ' + error.message;
            showTempMessage('❌ Erreur: ' + error.message, 'warning', 5000);
        })
        .finally(() => {
            state.isCalculating = false;
        });
    });
    
    // Affichage des résultats
    function displayResults(data) {
        let html = '<div class="calc-results-wrapper">';
        
        // En-tête avec métadonnées
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--port-border);">';
        html += '<h3 style="margin: 0; color: var(--port-primary); font-size: 1.3rem;">';
        
        if (data.affretement) {
            html += '🚛 Affrètement requis';
        } else {
            html += '🚛 Résultats de calcul';
        }
        
        html += '</h3>';
        html += '<small style="color: var(--port-text); font-weight: 500;">Calculé en ' + (data.time_ms || 0) + 'ms</small>';
        html += '</div>';
        
        if (data.carriers && Object.keys(data.carriers).length > 0) {
            html += '<div class="calc-results-grid">';
            
            Object.entries(data.carriers).forEach(([carrier, result]) => {
                const carrierNames = {
                    'xpo': 'XPO Logistics',
                    'heppner': 'Heppner',
                    'kn': 'Kuehne + Nagel',
                    'affretement': 'Affrètement',
                    'info': 'Information'
                };
                
                const name = carrierNames[carrier] || carrier.toUpperCase();
                const prixTTC = result.prix_ttc || 0;
                const prixHT = result.prix_ht || 0;
                const delai = result.delai || 'N/A';
                const service = result.service || 'Standard';
                
                html += '<div class="calc-result-card">';
                html += '<div class="calc-result-header">';
                html += '<strong>' + name + '</strong>';
                if (delai !== 'N/A') {
                    html += '<span class="calc-result-delay">' + delai + '</span>';
                }
                html += '</div>';
                
                if (prixTTC > 0) {
                    html += '<div class="calc-result-price">' + prixTTC.toFixed(2) + ' € TTC</div>';
                    if (prixHT > 0 && prixHT !== prixTTC) {
                        html += '<div class="calc-result-price-ht">HT: ' + prixHT.toFixed(2) + ' €</div>';
                    }
                } else {
                    html += '<div style="text-align: center; color: var(--port-text); font-style: italic; padding: 1rem;">';
                    html += result.message || 'Contactez-nous pour un devis';
                    html += '</div>';
                }
                
                // Détails du service
                if (result.details || service !== 'Standard') {
                    html += '<div class="calc-result-details">';
                    html += '<div><span>Service:</span><span>' + service + '</span></div>';
                    if (result.details) {
                        Object.entries(result.details).forEach(([key, value]) => {
                            html += '<div><span>' + key + ':</span><span>' + value + '</span></div>';
                        });
                    }
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            html += '<div style="text-align: center; padding: 3rem 2rem; color: var(--port-text);">';
            html += '<p style="font-size: 1.1rem; margin: 0;">⚠️ Aucun résultat disponible</p>';
            html += '</div>';
        }
        
        html += '</div>';
        
        dom.resultsContent.innerHTML = html;
        addDebug('Résultats affichés', { carriers: Object.keys(data.carriers || {}) });
    }
    
    // Initialisation
    addDebug('Tous les événements configurés');
});

// API Publique pour le calculateur
window.PortCalculator = {
    // Réinitialiser le formulaire
    reset: function() {
        const form = document.getElementById('calculatorForm');
        if (form) form.reset();
        
        // Réinitialiser l'étape
        const stepBtns = document.querySelectorAll('.calc-step-btn');
        stepBtns.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.step === '1');
            btn.classList.remove('completed');
        });
        
        // Afficher la première étape
        const stepContents = document.querySelectorAll('.calc-step-content');
        stepContents.forEach(content => {
            content.style.display = content.dataset.step === '1' ? 'block' : 'none';
            content.classList.toggle('active', content.dataset.step === '1');
        });
        
        // Réinitialiser les résultats
        const resultsContent = document.getElementById('resultsContent');
        if (resultsContent) {
            resultsContent.innerHTML = `
                <div class="calc-welcome">
                    <div class="calc-welcome-icon">🚛</div>
                    <h3>Calculateur Intelligent</h3>
                    <p>Navigation étape par étape pour une comparaison précise des tarifs</p>
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(37, 99, 235, 0.05); border-radius: 0.5rem;">
                        <strong>Étapes :</strong><br>
                        1️⃣ Saisissez le département<br>
                        2️⃣ Indiquez le poids et le type d'envoi<br>
                        3️⃣ Configurez les options de livraison<br>
                        4️⃣ Lancez le calcul pour comparer les tarifs
                    </div>
                </div>
            `;
        }
        
        // Réinitialiser le statut
        const calcStatus = document.getElementById('calcStatus');
        if (calcStatus) {
            calcStatus.textContent = '⏳ En attente de vos paramètres...';
        }
        
        console.log('Formulaire réinitialisé');
    }
};

// Fonctions globales pour les toggles des sections
window.toggleDebug = function() {
    const content = document.getElementById('debugContent');
    const panel = document.getElementById('debugPanel');
    
    if (panel) {
        panel.classList.toggle('expanded');
    }
};
    /**
     * Met le focus sur le premier champ de l'étape donnée
     */
    function focusFirstFieldInStep(step) {
        const stepContent = document.querySelector(`[data-step="${step}"].active`);
        if (stepContent) {
            const firstInput = stepContent.querySelector('input, select');
            if (firstInput) {
                setTimeout(() => {
                    firstInput.focus();
                }, 300); // Délai pour la transition
            }
        }
    }

    /**
     * Vérification si on peut naviguer vers une étape
     */
    function canNavigateToStep(step) {
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
    }

    /**
     * Validation de l'étape actuelle
     */
    function validateCurrentStep() {
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
    }

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
