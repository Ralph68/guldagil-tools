/**
 * Titre: JavaScript pour le calculateur de frais de port
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
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
        prevBtns: document.querySelectorAll('.btn-prev'),
        adr: document.getElementsByName('adr'),
        enlevement: document.getElementById('enlevement'),
        optionSup: document.getElementById('option_sup')
    };
    
    // État du calculateur
    let state = {
        currentStep: 1,
        isCalculating: false,
        stepValidation: {
            1: false,
            2: false,
            3: true
        },
        history: []
    };
    
    // Debug helper
    function addDebug(message, data = null) {
        if (!dom.debugContent) return;
        
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
            if (dom.palettesGroup) dom.palettesGroup.style.display = 'block';
            if (dom.paletteEurGroup) dom.paletteEurGroup.style.display = 'block';
        } else {
            if (dom.palettesGroup) dom.palettesGroup.style.display = 'none';
            if (dom.paletteEurGroup) dom.paletteEurGroup.style.display = 'none';
        }
    }
    
    // Navigation avec les boutons suivant/précédent
    if (dom.nextBtns) {
        dom.nextBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const nextStep = parseInt(this.dataset.goto);
                goToStep(nextStep);
            });
        });
    }
    
    if (dom.prevBtns) {
        dom.prevBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const prevStep = parseInt(this.dataset.goto);
                goToStep(prevStep);
            });
        });
    }
    
    // Événements pour le poids et le type
    if (dom.poids) {
        dom.poids.addEventListener('change', autoSelectType);
    }
    
    if (dom.type) {
        dom.type.addEventListener('change', handleTypeChange);
    }
    
    // Gestion du debug panel
    const debugHeader = document.querySelector('.debug-header');
    if (debugHeader) {
        debugHeader.addEventListener('click', function() {
            const debugPanel = document.getElementById('debugPanel');
            if (debugPanel) {
                debugPanel.classList.toggle('expanded');
            }
        });
    }
    
    // Gestion du formulaire
    if (dom.form) {
        dom.form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (state.isCalculating) {
                addDebug('Calcul déjà en cours - Ignoré');
                return;
            }
            
            state.isCalculating = true;
            if (dom.calcStatus) {
                dom.calcStatus.textContent = '⏳ Calcul en cours...';
            }
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
                    if (dom.calcStatus) {
                        dom.calcStatus.textContent = '✅ Calcul terminé';
                    }
                    showTempMessage('✅ Calcul terminé avec succès', 'success', 2000);
                } else {
                    throw new Error(data.error || 'Erreur inconnue');
                }
            })
            .catch(error => {
                console.error('Erreur calcul:', error);
                if (dom.calcStatus) {
                    dom.calcStatus.textContent = '❌ Erreur: ' + error.message;
                }
                showTempMessage('❌ Erreur: ' + error.message, 'warning', 5000);
            })
            .finally(() => {
                state.isCalculating = false;
            });
        });
    }
    
    // Affichage des résultats
    function displayResults(data) {
        if (!dom.resultsContent) return;
        
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
    
    // Validation du département
    function validateDepartement() {
        const field = dom.departement;
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
    }
    
    // Validation du poids
    function validatePoids() {
        const field = dom.poids;
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
    }
    
    // Mise à jour de la validation des étapes
    function updateStepValidation() {
        state.stepValidation[1] = validateDepartement();
        state.stepValidation[2] = validatePoids();
        state.stepValidation[3] = true; // Options toujours valides
        
        // Mettre à jour l'interface
        updateStepVisualValidation();
    }
    
    function updateStepVisualValidation() {
        Object.keys(state.stepValidation).forEach(step => {
            const stepElement = document.querySelector(`.calc-step[data-step="${step}"]`);
            if (stepElement) {
                stepElement.classList.remove('valid', 'invalid');
                
                if (state.stepValidation[step]) {
                    stepElement.classList.add('valid');
                } else if (parseInt(step) < state.currentStep) {
                    stepElement.classList.add('invalid');
                }
            }
        });
    }
    
    // Historique
    function addToHistory(formData, results) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            formData,
            results,
            id: Date.now()
        };
        
        state.history.unshift(historyItem);
        
        // Limiter l'historique à 10 éléments
        if (state.history.length > 10) {
            state.history = state.history.slice(0, 10);
        }
        
        saveHistory();
        updateHistoryDisplay();
    }
    
    function saveHistory() {
        try {
            localStorage.setItem('calc_history', JSON.stringify(state.history));
        } catch (e) {
            console.warn('Impossible de sauvegarder l\'historique:', e);
        }
    }
    
    function loadHistory() {
        try {
            const saved = localStorage.getItem('calc_history');
            state.history = saved ? JSON.parse(saved) : [];
        } catch (e) {
            console.warn('Erreur chargement historique:', e);
            state.history = [];
        }
    }
    
    function updateHistoryDisplay() {
        const historySection = document.getElementById('historySection');
        const historyContent = document.getElementById('historyContent');
        
        if (!historySection || !historyContent) return;
        
        if (state.history.length > 0) {
            historySection.style.display = 'block';
            
            historyContent.innerHTML = state.history.map(item => {
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
    }
    
    // Initialisation
    loadHistory();
    updateHistoryDisplay();
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
    },
    
    // Force le calcul
    calculate: function() {
        const calculateBtn = document.querySelector('.btn-calculate');
        if (calculateBtn) calculateBtn.click();
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