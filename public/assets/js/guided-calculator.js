// =============================================================================
// CONFIGURATION CENTRALISÉE
// =============================================================================

const CONFIG = {
    WEIGHT_THRESHOLDS: {
        PALETTE_SUGGESTION: 60,
        PALETTE_MANDATORY: 70,
        VOLUME_DISCOUNT: 500,
        MAX_WEIGHT: 3500
    },
    PRICE_ALERT_THRESHOLD: 30,
    AUTO_SAVE_DELAY: 500,
    MAX_HISTORY_ITEMS: 50
};

// =============================================================================
// UTILITAIRES
// =============================================================================

const utils = {
    formatPrice: (price) => new Intl.NumberFormat('fr-FR', { 
        style: 'currency', 
        currency: 'EUR' 
    }).format(price),
    
    formatWeight: (weight) => `${weight} kg`,
    
    showAlert: (message, type = 'info', container = 'alerts-container') => {
        const alertsContainer = document.getElementById(container);
        if (!alertsContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = message;
        alertsContainer.appendChild(alert);
        
        // Auto-remove après 8 secondes pour les infos, permanent pour warnings
        if (type === 'info') {
            setTimeout(() => alert.remove(), 8000);
        }
    },
    
    clearAlerts: (container = 'alerts-container') => {
        const alertsContainer = document.getElementById(container);
        if (alertsContainer) {
            alertsContainer.innerHTML = '';
        }
    },
    
    debounce: (func, wait) => {
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

// =============================================================================
// VARIABLES GLOBALES
// =============================================================================

let currentData = null;
let isCalculating = false;

// Éléments DOM
const elements = {
    // Formulaire
    form: document.getElementById('calc-form'),
    departement: document.getElementById('departement'),
    poids: document.getElementById('poids'),
    typeInputs: document.querySelectorAll('input[name="type"]'),
    adrInputs: document.querySelectorAll('input[name="adr"]'),
    optionSup: document.getElementById('option_sup'),
    enlevement: document.getElementById('enlevement'),
    palettes: document.getElementById('palettes'),
    
    // Sections
    advancedOptions: document.getElementById('advanced-options'),
    paletteField: document.getElementById('palette-field'),
    
    // Résultat
    loading: document.getElementById('loading'),
    resultContent: document.getElementById('result-content'),
    resultStatus: document.getElementById('result-status'),
    resultActions: document.getElementById('result-actions'),
    
    // Boutons
    btnReset: document.getElementById('btn-reset'),
    btnCompare: document.getElementById('btn-compare'),
    
    // Erreurs
    errorContainer: document.getElementById('error-container')
};

// =============================================================================
// VALIDATION
// =============================================================================

const validators = {
    departement: (value) => {
        if (!value || !value.trim()) return { valid: false, message: "Département requis" };
        if (!/^\d{2}$/.test(value)) return { valid: false, message: "Format: 2 chiffres (ex: 67)" };
        const num = parseInt(value);
        if (num < 1 || num > 95) return { valid: false, message: "Département invalide (01-95)" };
        return { valid: true };
    },
    
    poids: (value) => {
        if (!value || value.trim() === '') return { valid: false, message: "Poids requis" };
        const poids = parseInt(value);
        if (isNaN(poids) || poids < 1) return { valid: false, message: "Poids minimum: 1 kg" };
        if (poids > CONFIG.WEIGHT_THRESHOLDS.MAX_WEIGHT) {
            return { valid: false, message: `Poids maximum: ${CONFIG.WEIGHT_THRESHOLDS.MAX_WEIGHT} kg` };
        }
        return { valid: true };
    },
    
    type: () => {
        const selected = document.querySelector('input[name="type"]:checked');
        if (!selected) return { valid: false, message: "Sélectionnez un type d'envoi" };
        return { valid: true };
    },
    
    adr: () => {
        const selected = document.querySelector('input[name="adr"]:checked');
        if (!selected) return { valid: false, message: "Indiquez si marchandise dangereuse" };
        return { valid: true };
    }
};

// =============================================================================
// GESTION DES ERREURS
// =============================================================================

function showFieldError(fieldName, message) {
    const errorEl = document.getElementById(`error-${fieldName}`);
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
}

function hideFieldError(fieldName) {
    const errorEl = document.getElementById(`error-${fieldName}`);
    if (errorEl) {
        errorEl.classList.remove('show');
    }
}

function clearAllErrors() {
    document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));
    elements.errorContainer.classList.remove('show');
    utils.clearAlerts();
}

function showGlobalError(message) {
    elements.errorContainer.innerHTML = message;
    elements.errorContainer.classList.add('show');
}

// =============================================================================
// VALIDATION FORMULAIRE
// =============================================================================

function validateForm() {
    let isValid = true;
    const errors = [];
    
    // Validation champ par champ
    ['departement', 'poids', 'type', 'adr'].forEach(field => {
        const result = validators[field](elements[field]?.value);
        if (!result.valid) {
            showFieldError(field, result.message);
            errors.push(result.message);
            isValid = false;
        } else {
            hideFieldError(field);
        }
    });
    
    return { valid: isValid, errors };
}

function canCalculate() {
    return validateForm().valid;
}

// =============================================================================
// GESTION DU POIDS ET SUGGESTIONS
// =============================================================================

function handleWeightChange(weight) {
    const poids = parseInt(weight);
    if (isNaN(poids)) return;
    
    // Auto-sélection palette si > 60kg
    if (poids >= CONFIG.WEIGHT_THRESHOLDS.PALETTE_SUGGESTION) {
        const paletteRadio = document.getElementById('type-palette');
        const colisRadio = document.getElementById('type-colis');
        
        if (paletteRadio && !paletteRadio.checked) {
            paletteRadio.checked = true;
            colisRadio.disabled = true;
            togglePaletteField();
            
            utils.showAlert(
                `⚠️ Poids ${poids}kg → Palette automatiquement sélectionnée (recommandé > ${CONFIG.WEIGHT_THRESHOLDS.PALETTE_SUGGESTION}kg)`,
                'warning'
            );
        }
    } else {
        // Réactiver l'option colis si < 60kg
        const colisRadio = document.getElementById('type-colis');
        if (colisRadio) {
            colisRadio.disabled = false;
        }
    }
    
    // Alertes seuils économiques
    checkWeightThresholds(poids);
}

function checkWeightThresholds(poids) {
    utils.clearAlerts();
    
    if (poids >= CONFIG.WEIGHT_THRESHOLDS.VOLUME_DISCOUNT) {
        utils.showAlert(
            `💡 CONSEIL: Poids ${poids}kg éligible aux tarifs dégressifs. Contactez le service achat pour négociation spéciale.`,
            'info'
        );
    }
    
    if (poids >= CONFIG.WEIGHT_THRESHOLDS.PALETTE_SUGGESTION && poids < CONFIG.WEIGHT_THRESHOLDS.PALETTE_MANDATORY) {
        utils.showAlert(
            `⚠️ ATTENTION: Poids ${poids}kg proche du seuil palette (${CONFIG.WEIGHT_THRESHOLDS.PALETTE_MANDATORY}kg). Vérifiez si palettisation possible pour économies.`,
            'warning'
        );
    }
}

// =============================================================================
// GESTION INTERFACE
// =============================================================================

function togglePaletteField() {
    const selectedType = document.querySelector('input[name="type"]:checked');
    const paletteField = elements.paletteField;
    
    if (selectedType && selectedType.value === 'palette') {
        paletteField.style.display = 'block';
        if (!elements.palettes.value) {
            elements.palettes.value = '1';
            updatePaletteButtons();
        }
    } else {
        paletteField.style.display = 'none';
        elements.palettes.value = '0';
    }
}

function updatePaletteButtons() {
    const currentValue = elements.palettes.value;
    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.value === currentValue) {
            btn.classList.add('active');
        }
    });
}

function showAdvancedOptions() {
    elements.advancedOptions.style.display = 'block';
    elements.resultActions.style.display = 'block';
}

function updateResultStatus(message) {
    elements.resultStatus.textContent = message;
}

// =============================================================================
// CALCUL PRINCIPAL
// =============================================================================

const debouncedCalculate = utils.debounce(performCalculation, CONFIG.AUTO_SAVE_DELAY);

function triggerCalculation() {
    if (canCalculate() && !isCalculating) {
        debouncedCalculate();
    }
}

function performCalculation() {
    if (isCalculating) return;
    
    clearAllErrors();
    isCalculating = true;
    
    // Afficher loading
    elements.loading.style.display = 'block';
    elements.resultContent.innerHTML = '';
    updateResultStatus('Calcul en cours...');
    
    // Préparer les données
    const formData = new FormData();
    formData.append('departement', elements.departement.value);
    formData.append('poids', elements.poids.value);
    
    const selectedType = document.querySelector('input[name="type"]:checked');
    const selectedAdr = document.querySelector('input[name="adr"]:checked');
    
    if (selectedType) formData.append('type', selectedType.value);
    if (selectedAdr) formData.append('adr', selectedAdr.value);
    
    formData.append('option_sup', elements.optionSup.value);
    formData.append('enlevement', elements.enlevement.checked ? '1' : '0');
    formData.append('palettes', elements.palettes.value || '0');
    
    // Requête AJAX
    fetch('ajax-calculate.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        handleCalculationResult(data);
    })
    .catch(error => {
        console.error('Erreur calcul:', error);
        showGlobalError('❌ Erreur lors du calcul. Veuillez réessayer.');
        updateResultStatus('Erreur');
    })
    .finally(() => {
        isCalculating = false;
        elements.loading.style.display = 'none';
    });
}

// =============================================================================
// TRAITEMENT RÉSULTATS
// =============================================================================

function handleCalculationResult(data) {
    currentData = data;
    
    if (data.affretement) {
        displayAffretement(data.message);
        return;
    }
    
    if (data.errors && data.errors.length > 0) {
        showGlobalError('❌ ' + data.errors.join('<br>'));
        updateResultStatus('Erreur de validation');
        return;
    }
    
    if (data.success && data.bestCarrier) {
        displayResults(data);
        showAdvancedOptions();
        
        // Alertes d'écart de prix
        checkPriceAlerts(data);
    } else {
        elements.resultContent.innerHTML = '<div class="result-placeholder"><p>❌ Aucun tarif disponible pour ces critères</p></div>';
        updateResultStatus('Aucun tarif disponible');
    }
}

function displayResults(data) {
    const bestCarrier = data.formatted[data.bestCarrier];
    const bestPrice = data.best;
    
    // Récap des paramètres
    const selectedType = document.querySelector('input[name="type"]:checked');
    const selectedAdr = document.querySelector('input[name="adr"]:checked');
    
    let html = `
        <div class="result-best">
            <div class="result-recap">
                <small style="color: var(--gul-gray-500);">
                    ${elements.departement.value} • ${elements.poids.value}kg • 
                    ${selectedType?.value || '?'} • ADR:${selectedAdr?.value === 'oui' ? 'Oui' : 'Non'}
                </small>
            </div>
            
            <div class="best-carrier" style="margin-top: 1rem;">
                <div style="font-size: 1.1rem; font-weight: 600; color: var(--gul-blue-primary); margin-bottom: 0.5rem;">
                    🏆 ${bestCarrier.name}
                </div>
                <div style="font-size: 2rem; font-weight: bold; color: var(--gul-success);">
                    ${utils.formatPrice(bestPrice)}
                </div>
            </div>
        </div>
    `;
    
    // Message de remise en palette si applicable
    if (data.fallback && data.fallback.hasBetter) {
        html += `
            <div style="margin-top: 1rem; padding: 0.75rem; background: #e7f3ff; border-radius: 0.5rem; border-left: 4px solid var(--gul-blue-primary);">
                <strong>✨ Remise en palette disponible</strong><br>
                <small>Économie de ${utils.formatPrice(data.fallback.savings)} en passant sur palette</small>
            </div>
        `;
    }
    
    elements.resultContent.innerHTML = html;
    updateResultStatus(`Meilleur tarif: ${utils.formatPrice(bestPrice)}`);
}

function displayAffretement(message) {
    elements.resultContent.innerHTML = `
        <div style="text-align: center; padding: 2rem 1rem; background: #fffbeb; border-radius: 0.5rem; border: 2px solid var(--gul-warning);">
            <div style="font-size: 1.5rem; margin-bottom: 1rem;">🚛</div>
            <h4 style="color: var(--gul-warning); margin-bottom: 1rem;">Affrètement nécessaire</h4>
            <p style="margin-bottom: 1rem;">${message}</p>
            <strong style="color: var(--gul-blue-primary);">📞 Service achat : 03 89 63 42 42</strong>
        </div>
    `;
    updateResultStatus('Affrètement requis');
}

function checkPriceAlerts(data) {
    if (!data.results) return;
    
    const prices = Object.values(data.results).filter(p => p !== null).sort((a, b) => a - b);
    if (prices.length < 2) return;
    
    const minPrice = prices[0];
    const maxPrice = prices[prices.length - 1];
    const ecart = ((maxPrice - minPrice) / minPrice) * 100;
    
    if (ecart > CONFIG.PRICE_ALERT_THRESHOLD) {
        utils.showAlert(
            `💰 ÉCART IMPORTANT: ${ecart.toFixed(0)}% entre le moins cher (${utils.formatPrice(minPrice)}) et le plus cher (${utils.formatPrice(maxPrice)}). Justification requise si choix non-optimal.`,
            'critical'
        );
    }
}

// =============================================================================
// COMPARAISON TRANSPORTEURS
// =============================================================================

function showComparison() {
    if (!currentData || !currentData.formatted) {
        alert('Aucune donnée de comparaison disponible');
        return;
    }
    
    let html = '<div style="margin-top: 1rem;"><h4>📊 Comparaison détaillée</h4><div style="display: grid; gap: 0.75rem; margin-top: 1rem;">';
    
    // Trier par prix croissant
    const sortedResults = Object.entries(currentData.formatted)
        .filter(([key, carrier]) => carrier.price !== null)
        .sort(([,a], [,b]) => a.price - b.price);
    
    sortedResults.forEach(([key, carrier], index) => {
        const isBest = index === 0;
        const priceDiff = index === 0 ? 0 : carrier.price - sortedResults[0][1].price;
        
        html += `
            <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: center; padding: 0.75rem; border-radius: 0.5rem; ${isBest ? 'background: #e7f9e7; border: 2px solid var(--gul-success);' : 'background: var(--gul-gray-50); border: 1px solid var(--gul-gray-200);'}">
                <div>
                    <strong>${carrier.name}</strong>
                    ${isBest ? '<span style="color: var(--gul-success); font-size: 0.8rem; margin-left: 0.5rem;">⭐ OPTIMAL</span>' : ''}
                </div>
                <div style="font-weight: bold; color: ${isBest ? 'var(--gul-success)' : 'var(--gul-gray-700)'};">
                    ${carrier.formatted}
                </div>
                <div style="font-size: 0.9rem; color: var(--gul-gray-500);">
                    ${priceDiff > 0 ? '+' + utils.formatPrice(priceDiff) : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div></div>';
    
    elements.resultContent.innerHTML += html;
}

// =============================================================================
// RESET FORMULAIRE
// =============================================================================

function resetForm() {
    if (!confirm('Voulez-vous vraiment recommencer ?')) return;
    
    // Reset du formulaire
    elements.form.reset();
    
    // Reset des états
    clearAllErrors();
    currentData = null;
    isCalculating = false;
    
    // Reset de l'interface
    elements.advancedOptions.style.display = 'none';
    elements.paletteField.style.display = 'none';
    elements.resultActions.style.display = 'none';
    
    // Reset du résultat
    elements.resultContent.innerHTML = `
        <div class="result-placeholder">
            <div class="placeholder-icon">🚀</div>
            <p>Renseignez vos informations pour voir les tarifs</p>
        </div>
    `;
    updateResultStatus('En attente...');
    
    // Réactiver tous les champs
    document.getElementById('type-colis').disabled = false;
    
    // Focus sur le premier champ
    setTimeout(() => elements.departement.focus(), 100);
}

// =============================================================================
// GESTION HISTORIQUE
// =============================================================================

window.showHistorique = function() {
    const modal = document.getElementById('historique-modal');
    modal.classList.add('active');
    loadHistorique();
};

function loadHistorique() {
    const content = document.getElementById('historique-content');
    content.innerHTML = '<p>Chargement...</p>';
    
    fetch('ajax-historique.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historique.length > 0) {
                let html = `
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: var(--gul-gray-100);">
                                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--gul-gray-300);">Date</th>
                                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--gul-gray-300);">Critères</th>
                                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--gul-gray-300);">Transporteur</th>
                                <th style="padding: 0.5rem; text-align: right; border-bottom: 1px solid var(--gul-gray-300);">Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.historique.forEach(entry => {
                    html += `
                        <tr style="border-bottom: 1px solid var(--gul-gray-200);">
                            <td style="padding: 0.5rem; font-size: 0.8rem; color: var(--gul-gray-600);">
                                ${new Date(entry.date).toLocaleDateString('fr-FR')}<br>
                                ${new Date(entry.date).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}
                            </td>
                            <td style="padding: 0.5rem;">
                                ${entry.departement} • ${entry.poids}kg<br>
                                <small style="color: var(--gul-gray-500);">${entry.type} • ADR:${entry.adr}</small>
                            </td>
                            <td style="padding: 0.5rem; font-weight: 600; color: var(--gul-blue-primary);">
                                ${entry.best_carrier}
                            </td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: bold; color: var(--gul-success);">
                                ${utils.formatPrice(entry.best_price)}
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<p style="text-align: center; color: var(--gul-gray-500); padding: 2rem;">Aucun historique disponible</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p style="color: var(--gul-error); text-align: center; padding: 2rem;">Erreur lors du chargement</p>';
        });
}

window.clearHistorique = function() {
    if (!confirm('Voulez-vous vraiment effacer tout l\'historique ?')) return;
    
    fetch('ajax-historique.php?action=clear')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadHistorique();
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression');
        });
};

// =============================================================================
// EVENT LISTENERS
// =============================================================================

function setupEventListeners() {
    // Auto-progression et validation
    elements.departement.addEventListener('input', (e) => {
        if (e.target.value.length === 2 && validators.departement(e.target.value).valid) {
            elements.poids.focus();
        }
        triggerCalculation();
    });
    
    elements.departement.addEventListener('focus', () => elements.departement.select());
    
    elements.poids.addEventListener('input', (e) => {
        handleWeightChange(e.target.value);
        triggerCalculation();
    });
    
    // Radio buttons
    elements.typeInputs.forEach(input => {
        input.addEventListener('change', () => {
            togglePaletteField();
            triggerCalculation();
        });
    });
    
    elements.adrInputs.forEach(input => {
        input.addEventListener('change', triggerCalculation);
    });
    
    // Options avancées
    elements.optionSup.addEventListener('change', triggerCalculation);
    elements.enlevement.addEventListener('change', triggerCalculation);
    
    // Palettes
    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const value = e.target.dataset.value;
            
            if (value === 'plus') {
                utils.showAlert('⚠️ Pour plus de 3 palettes, contactez notre service achat : 📞 03 89 63 42 42', 'warning');
                elements.palettes.value = '';
            } else {
                elements.palettes.value = value;
                updatePaletteButtons();
                triggerCalculation();
            }
        });
    });
    
    // Boutons
    elements.btnReset.addEventListener('click', resetForm);
    elements.btnCompare.addEventListener('click', showComparison);
    
    // Modal
    document.querySelector('.modal-close').addEventListener('click', () => {
        document.getElementById('historique-modal').classList.remove('active');
    });
    
    window.addEventListener('click', (e) => {
        const modal = document.getElementById('historique-modal');
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
}

// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Calculateur Guldagil - Initialisation...');
    
    setupEventListeners();
    
    // Focus initial
    setTimeout(() => {
        elements.departement.focus();
    }, 500);
    
    console.log('✅ Calculateur initialisé avec succès');
});
