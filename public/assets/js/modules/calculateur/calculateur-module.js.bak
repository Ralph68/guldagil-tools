// public/assets/js/calculateur-module.js - Module calculateur complet

console.log('🚚 Chargement Module Calculateur V2...');

// ========== CONFIGURATION ==========
const CONFIG = {
    AUTO_CALC_DELAY: 300,
    SEUILS_ALERTES: [100, 1000, 2000, 3000],
    MAX_POIDS: 3500,
    MIN_POIDS: 0.1,
    PALETTE_THRESHOLD: 60,
    API_ENDPOINT: '../ajax-calculate.php',
    HISTORIQUE_ENDPOINT: '../ajax-historique.php'
};

// ========== ÉTAT GLOBAL ==========
const CalculatorState = {
    isCalculating: false,
    currentResults: null,
    formData: {},
    calculationTimeout: null,
    
    // Méthodes
    setCalculating(state) {
        this.isCalculating = state;
        updateCalculatingUI(state);
    },
    
    setResults(results) {
        this.currentResults = results;
    },
    
    updateFormData() {
        this.formData = getFormData();
    },
    
    isFormValid() {
        return validateForm().isValid;
    }
};

// ========== ÉLÉMENTS DOM ==========
const elements = {
    // Formulaire
    form: document.getElementById('calculator-form'),
    departement: document.getElementById('departement'),
    poids: document.getElementById('poids'),
    typeInputs: document.querySelectorAll('input[name="type"]'),
    adrInputs: document.querySelectorAll('input[name="adr"]'),
    optionSup: document.getElementById('option_sup'),
    enlevement: document.getElementById('enlevement'),
    palettes: document.getElementById('palettes'),
    paletteOptions: document.getElementById('palette-options'),
    
    // Actions
    btnCalculate: document.getElementById('btn-calculate'),
    
    // Résultats
    loadingZone: document.getElementById('loading-zone'),
    resultMain: document.getElementById('result-main'),
    resultStatus: document.getElementById('result-status'),
    resultContent: document.getElementById('result-content'),
    alertsZone: document.getElementById('alerts-zone'),
    alertsContent: document.getElementById('alerts-content'),
    comparisonZone: document.getElementById('comparison-zone'),
    comparisonContent: document.getElementById('comparison-content'),
    quickActions: document.getElementById('quick-actions'),
    
    // Erreurs
    errorContainer: document.getElementById('error-container')
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Initialisation Module Calculateur');
    
    setupEventListeners();
    initializeForm();
    preloadHistorique();
});

function initializeForm() {
    // Focus automatique sur département
    setTimeout(() => {
        if (elements.departement) {
            elements.departement.focus();
        }
    }, 300);
    
    // État initial
    CalculatorState.updateFormData();
    updateButtonState();
}

function setupEventListeners() {
    // Auto-progression et validation
    setupFormProgression();
    
    // Calcul automatique et manuel
    setupCalculationTriggers();
    
    // Gestion des types et options
    setupTypeAndOptionsHandling();
    
    // Actions utilisateur
    setupUserActions();
    
    // Navigation et modal
    setupModalHandlers();
}

// ========== PROGRESSION FORMULAIRE ==========
function setupFormProgression() {
    // Département avec auto-progression
    if (elements.departement) {
        elements.departement.addEventListener('input', handleDepartementInput);
        elements.departement.addEventListener('focus', () => elements.departement.select());
    }
    
    // Poids avec suggestions
    if (elements.poids) {
        elements.poids.addEventListener('input', handlePoidsInput);
    }
    
    // Validation en temps réel
    [elements.departement, elements.poids].forEach(element => {
        if (element) {
            element.addEventListener('input', () => {
                validateField(element);
                CalculatorState.updateFormData();
                updateButtonState();
                triggerAutoCalculation();
            });
        }
    });
}

function handleDepartementInput() {
    const value = elements.departement.value.trim();
    
    // Auto-progression si valide
    if (value.length === 2 && /^\d{2}$/.test(value)) {
        const deptNum = parseInt(value);
        if (deptNum >= 1 && deptNum <= 95) {
            // Petit délai pour que l'utilisateur voie la validation
            setTimeout(() => {
                if (elements.poids) {
                    elements.poids.focus();
                }
            }, 200);
        }
    }
}

function handlePoidsInput() {
    const poids = parseFloat(elements.poids.value) || 0;
    
    // Suggestion palette si > seuil
    if (poids >= CONFIG.PALETTE_THRESHOLD) {
        suggestPaletteMode(poids);
    } else {
        resetTypeRestrictions();
    }
    
    // Vérification limites
    if (poids > CONFIG.MAX_POIDS) {
        showFieldError('poids', `Poids maximum: ${CONFIG.MAX_POIDS} kg`);
    } else {
        hideFieldError('poids');
    }
}

function suggestPaletteMode(poids) {
    const paletteRadio = document.getElementById('type-palette');
    const colisRadio = document.getElementById('type-colis');
    
    if (paletteRadio && !paletteRadio.checked) {
        showNotification(
            `⚠️ Poids ${poids}kg → Palette recommandée (optimisation tarifaire)`,
            'warning'
        );
        
        // Auto-sélection après 2 secondes si pas d'action utilisateur
        setTimeout(() => {
            if (!paletteRadio.checked && elements.poids.value == poids) {
                paletteRadio.checked = true;
                handleTypeChange();
                showNotification('✅ Palette sélectionnée automatiquement', 'info');
            }
        }, 2000);
    }
}

function resetTypeRestrictions() {
    const colisRadio = document.getElementById('type-colis');
    if (colisRadio) {
        colisRadio.disabled = false;
    }
}

// ========== GESTION TYPES ET OPTIONS ==========
function setupTypeAndOptionsHandling() {
    // Types d'envoi
    elements.typeInputs.forEach(input => {
        input.addEventListener('change', handleTypeChange);
    });
    
    // ADR
    elements.adrInputs.forEach(input => {
        input.addEventListener('change', () => {
            CalculatorState.updateFormData();
            triggerAutoCalculation();
        });
    });
    
    // Options
    if (elements.optionSup) {
        elements.optionSup.addEventListener('change', () => {
            CalculatorState.updateFormData();
            triggerAutoCalculation();
        });
    }
    
    // Enlèvement
    if (elements.enlevement) {
        elements.enlevement.addEventListener('change', handleEnlevementChange);
    }
    
    // Boutons palettes
    setupPaletteButtons();
}

function handleTypeChange() {
    const selectedType = document.querySelector('input[name="type"]:checked');
    
    if (selectedType && selectedType.value === 'palette') {
        showPaletteOptions();
    } else {
        hidePaletteOptions();
    }
    
    CalculatorState.updateFormData();
    triggerAutoCalculation();
}

function showPaletteOptions() {
    if (elements.paletteOptions) {
        elements.paletteOptions.style.display = 'block';
        // Animation d'apparition
        elements.paletteOptions.classList.add('fade-in');
        
        // Valeur par défaut si vide
        if (!elements.palettes.value) {
            elements.palettes.value = '1';
            updatePaletteButtons();
        }
    }
}

function hidePaletteOptions() {
    if (elements.paletteOptions) {
        elements.paletteOptions.style.display = 'none';
        elements.palettes.value = '0';
    }
}

function handleEnlevementChange() {
    const isChecked = elements.enlevement.checked;
    
    if (isChecked) {
        // Forcer option standard
        if (elements.optionSup) {
            elements.optionSup.value = 'standard';
            elements.optionSup.disabled = true;
        }
        
        showNotification(
            'ℹ️ Enlèvement activé → Options de livraison désactivées',
            'info'
        );
    } else {
        // Réactiver options
        if (elements.optionSup) {
            elements.optionSup.disabled = false;
        }
    }
    
    CalculatorState.updateFormData();
    triggerAutoCalculation();
}

function setupPaletteButtons() {
    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const value = btn.dataset.value;
            
            if (value === 'contact') {
                showNotification(
                    '📞 Pour 4+ palettes, contactez le service achat : 03 89 63 42 42',
                    'warning'
                );
                return;
            }
            
            elements.palettes.value = value;
            updatePaletteButtons();
            CalculatorState.updateFormData();
            triggerAutoCalculation();
        });
    });
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

// ========== CALCULS ==========
function setupCalculationTriggers() {
    // Bouton calcul manuel
    if (elements.btnCalculate) {
        elements.btnCalculate.addEventListener('click', (e) => {
            e.preventDefault();
            performCalculation();
        });
    }
    
    // Reset formulaire
    if (elements.form) {
        elements.form.addEventListener('reset', handleFormReset);
    }
}

function triggerAutoCalculation() {
    // Annuler calcul précédent
    if (CalculatorState.calculationTimeout) {
        clearTimeout(CalculatorState.calculationTimeout);
    }
    
    // Programmer nouveau calcul si formulaire valide
    if (CalculatorState.isFormValid()) {
        CalculatorState.calculationTimeout = setTimeout(() => {
            performCalculation();
        }, CONFIG.AUTO_CALC_DELAY);
    }
}

function performCalculation() {
    if (CalculatorState.isCalculating) {
        console.log('⏳ Calcul déjà en cours...');
        return;
    }
    
    // Validation finale
    const validation = validateForm();
    if (!validation.isValid) {
        showValidationErrors(validation.errors);
        return;
    }
    
    CalculatorState.setCalculating(true);
    clearErrors();
    CalculatorState.updateFormData();
    
    console.log('🚀 Lancement calcul:', CalculatorState.formData);
    
    // Préparer FormData pour l'API
    const formData = new FormData();
    Object.keys(CalculatorState.formData).forEach(key => {
        formData.append(key, CalculatorState.formData[key]);
    });
    
    // Requête API
    fetch(CONFIG.API_ENDPOINT, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('📊 Résultats reçus:', data);
        handleCalculationResults(data);
    })
    .catch(error => {
        console.error('❌ Erreur calcul:', error);
        showError('Erreur lors du calcul. Veuillez réessayer.');
    })
    .finally(() => {
        CalculatorState.setCalculating(false);
    });
}

// ========== TRAITEMENT RÉSULTATS ==========
function handleCalculationResults(data) {
    CalculatorState.setResults(data);
    
    if (data.affretement) {
        displayAffretement(data.message);
        return;
    }
    
    if (data.errors && data.errors.length > 0) {
        showError(data.errors.join('<br>'));
        return;
    }
    
    if (data.success && data.bestCarrier) {
        displaySuccessResults(data);
        checkAndDisplayAlerts(data);
        showQuickActions();
    } else {
        displayNoResults();
    }
}

function displaySuccessResults(data) {
    const bestCarrier = data.formatted[data.bestCarrier];
    const bestPrice = data.best;
    
    // Mise à jour du statut
    elements.resultStatus.textContent = `Meilleur tarif: ${bestCarrier.formatted}`;
    
    // Contenu principal
    let html = `
        <div class="result-success fade-in">
            <div class="result-best-price">
                🏆 ${bestCarrier.name}
            </div>
            <div class="result-best-price">
                ${bestCarrier.formatted}
            </div>
            <div class="result-details">
                ${CalculatorState.formData.departement} • ${CalculatorState.formData.poids}kg • 
                ${CalculatorState.formData.type} • ADR: ${CalculatorState.formData.adr === 'oui' ? 'Oui' : 'Non'}
            </div>
        </div>
    `;
    
    // Comparaison mini
    html += generateMiniComparison(data);
    
    elements.resultContent.innerHTML = html;
    
    // Animation
    elements.resultMain.classList.add('slide-up');
    setTimeout(() => {
        elements.resultMain.classList.remove('slide-up');
    }, 500);
}

function generateMiniComparison(data) {
    const carriers = Object.entries(data.formatted)
        .filter(([key, carrier]) => carrier.price !== null)
        .sort(([,a], [,b]) => a.price - b.price);
    
    if (carriers.length <= 1) return '';
    
    let html = '<div class="result-comparison-mini"><h5>Autres transporteurs:</h5>';
    
    carriers.slice(1, 4).forEach(([key, carrier]) => {
        const diff = carrier.price - carriers[0][1].price;
        html += `
            <div class="comparison-mini-item">
                <span>${carrier.name}</span>
                <span>${carrier.formatted} (+${formatPrice(diff)})</span>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

function displayAffretement(message) {
    elements.resultStatus.textContent = 'Affrètement requis';
    elements.resultContent.innerHTML = `
        <div style="text-align: center; padding: 2rem; background: #fffbeb; border: 2px solid var(--gul-warning); border-radius: var(--radius);">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🚛</div>
            <h4 style="color: var(--gul-warning); margin-bottom: 1rem;">Affrètement nécessaire</h4>
            <p style="margin-bottom: 1rem;">${message}</p>
            <strong style="color: var(--gul-blue-primary);">📞 Service achat : 03 89 63 42 42</strong>
        </div>
    `;
}

function displayNoResults() {
    elements.resultStatus.textContent = 'Aucun tarif disponible';
    elements.resultContent.innerHTML = `
        <div class="result-placeholder">
            <div class="placeholder-icon">❌</div>
            <h4>Aucun tarif disponible</h4>
            <p>Aucun transporteur ne peut prendre en charge cette expédition</p>
        </div>
    `;
}

// ========== ALERTES SEUILS ==========
function checkAndDisplayAlerts(data) {
    if (!data.results) return;
    
    const poids = parseFloat(CalculatorState.formData.poids);
    const bestPrice = data.best;
    const alerts = [];
    
    // Vérifier chaque seuil
    CONFIG.SEUILS_ALERTES.forEach(seuil => {
        if (poids >= seuil * 0.8 && poids < seuil) {
            const unitRate = bestPrice / poids;
            const seuilPrice = unitRate * seuil;
            
            if (seuilPrice < bestPrice) {
                const economies = bestPrice - seuilPrice;
                alerts.push({
                    seuil,
                    economies: formatPrice(economies),
                    message: `Payant pour déclarer ${seuil}kg → Économie de ${formatPrice(economies)}`
                });
            }
        }
    });
    
    // Afficher les alertes
    if (alerts.length > 0) {
        let html = '';
        alerts.forEach(alert => {
            html += `<div class="alert-item">💡 ${alert.message}</div>`;
        });
        
        elements.alertsContent.innerHTML = html;
        elements.alertsZone.style.display = 'block';
        elements.alertsZone.classList.add('fade-in');
    } else {
        elements.alertsZone.style.display = 'none';
    }
}

// ========== ACTIONS UTILISATEUR ==========
function setupUserActions() {
    // Actions rapides
    window.showDetailedComparison = showDetailedComparison;
    window.exportCalculation = exportCalculation;
    window.showHistorique = showHistorique;
    window.resetCalculator = resetCalculator;
}

function showDetailedComparison() {
    if (!CalculatorState.currentResults || !CalculatorState.currentResults.formatted) {
        showNotification('❌ Aucune donnée de comparaison disponible', 'error');
        return;
    }
    
    const data = CalculatorState.currentResults;
    
    // Trier par prix croissant
    const sortedResults = Object.entries(data.formatted)
        .filter(([key, carrier]) => carrier.price !== null)
        .sort(([,a], [,b]) => a.price - b.price);
    
    let html = '';
    
    sortedResults.forEach(([key, carrier], index) => {
        const isBest = index === 0;
        const priceDiff = index === 0 ? 0 : carrier.price - sortedResults[0][1].price;
        
        html += `
            <div class="comparison-item ${isBest ? 'best' : ''}">
                <div class="carrier-name">
                    ${carrier.name}
                    ${isBest ? '<span style="color: var(--gul-success); font-size: 0.8rem; margin-left: 0.5rem;">⭐ OPTIMAL</span>' : ''}
                </div>
                <div>
                    <div class="carrier-price">${carrier.formatted}</div>
                    ${priceDiff > 0 ? `<div style="font-size: 0.8rem; color: var(--gul-gray-500);">+${formatPrice(priceDiff)}</div>` : ''}
                </div>
            </div>
        `;
    });
    
    elements.comparisonContent.innerHTML = html;
    elements.comparisonZone.style.display = 'block';
    elements.comparisonZone.classList.add('slide-up');
    
    // Scroll vers la comparaison
    elements.comparisonZone.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}

function exportCalculation() {
    if (!CalculatorState.currentResults) {
        showNotification('❌ Aucun calcul à exporter', 'error');
        return;
    }
    
    // Préparer les données d'export
    const data = CalculatorState.currentResults;
    const exportData = {
        date: new Date().toLocaleString('fr-FR'),
        parametres: CalculatorState.formData,
        resultat_optimal: {
            transporteur: data.bestCarrier,
            prix: data.best
        },
        comparaison: data.formatted
    };
    
    // Créer et télécharger le fichier JSON
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `calcul-transport-${new Date().toISOString().slice(0,10)}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showNotification('📄 Calcul exporté avec succès', 'success');
}

function resetCalculator() {
    if (!confirm('Voulez-vous vraiment recommencer le calcul ?')) return;
    
    // Reset formulaire
    elements.form.reset();
    
    // Reset état
    CalculatorState.currentResults = null;
    CalculatorState.formData = {};
    CalculatorState.setCalculating(false);
    
    // Reset UI
    hidePaletteOptions();
    elements.alertsZone.style.display = 'none';
    elements.comparisonZone.style.display = 'none';
    elements.quickActions.style.display = 'none';
    
    // Reset résultat
    elements.resultStatus.textContent = 'En attente';
    elements.resultContent.innerHTML = `
        <div class="result-placeholder">
            <div class="placeholder-icon">🚀</div>
            <h4>Prêt à calculer</h4>
            <p>Renseignez le formulaire pour voir les tarifs</p>
        </div>
    `;
    
    // Réinitialiser les restrictions
    resetTypeRestrictions();
    if (elements.optionSup) {
        elements.optionSup.disabled = false;
    }
    
    clearErrors();
    updateButtonState();
    
    // Focus
    setTimeout(() => {
        if (elements.departement) {
            elements.departement.focus();
        }
    }, 100);
    
    showNotification('🔄 Calculateur réinitialisé', 'info');
}

function showQuickActions() {
    if (elements.quickActions) {
        elements.quickActions.style.display = 'grid';
        elements.quickActions.classList.add('fade-in');
    }
}

// ========== HISTORIQUE ==========
function preloadHistorique() {
    // Précharger l'historique en arrière-plan
    fetch(CONFIG.HISTORIQUE_ENDPOINT + '?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`📋 Historique préchargé: ${data.historique.length} entrées`);
            }
        })
        .catch(error => {
            console.log('Info: Historique non disponible');
        });
}

function showHistorique() {
    const modal = document.getElementById('historique-modal');
    if (!modal) return;
    
    modal.classList.add('active');
    loadHistoriqueContent();
}

function loadHistoriqueContent() {
    const content = document.getElementById('historique-content');
    if (!content) return;
    
    content.innerHTML = '<div class="loading-placeholder">Chargement de l\'historique...</div>';
    
    fetch(CONFIG.HISTORIQUE_ENDPOINT + '?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historique.length > 0) {
                displayHistoriqueTable(data.historique, content);
            } else {
                content.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--gul-gray-500);">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
                        <h4>Aucun historique</h4>
                        <p>Vos calculs apparaîtront ici automatiquement</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--gul-error);">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">❌</div>
                    <h4>Erreur de chargement</h4>
                    <p>Impossible de charger l'historique</p>
                </div>
            `;
        });
}

function displayHistoriqueTable(historique, container) {
    let html = `
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: var(--gul-gray-100);">
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--gul-gray-300);">Date</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--gul-gray-300);">Critères</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--gul-gray-300);">Transporteur</th>
                        <th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid var(--gul-gray-300);">Prix</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid var(--gul-gray-300);">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    historique.forEach((entry, index) => {
        const date = new Date(entry.date);
        html += `
            <tr style="border-bottom: 1px solid var(--gul-gray-200); ${index % 2 === 0 ? 'background: var(--gul-gray-50);' : ''}">
                <td style="padding: 0.75rem; font-size: 0.85rem; color: var(--gul-gray-600);">
                    <div>${date.toLocaleDateString('fr-FR')}</div>
                    <div style="font-size: 0.75rem; opacity: 0.8;">${date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
                </td>
                <td style="padding: 0.75rem;">
                    <div><strong>${entry.departement}</strong> • <strong>${entry.poids}kg</strong></div>
                    <div style="font-size: 0.8rem; color: var(--gul-gray-500);">${entry.type} • ADR: ${entry.adr}</div>
                </td>
                <td style="padding: 0.75rem; font-weight: 600; color: var(--gul-blue-primary);">
                    ${entry.best_carrier}
                </td>
                <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: var(--gul-success);">
                    ${formatPrice(entry.best_price)}
                </td>
                <td style="padding: 0.75rem; text-align: center;">
                    <button class="btn btn-outline btn-sm" onclick="replayCalculation(${index})" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                        🔄 Rejouer
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    if (historique.length >= 10) {
        html += `
            <div style="margin-top: 1rem; padding: 0.75rem; background: var(--gul-blue-bg); border-radius: var(--radius); font-size: 0.85rem; color: var(--gul-blue-primary);">
                ℹ️ Seuls les 10 derniers calculs sont affichés
            </div>
        `;
    }
    
    container.innerHTML = html;
}

window.replayCalculation = function(index) {
    // Cette fonction sera implémentée pour rejouer un calcul
    showNotification('🔄 Fonction "Rejouer" en développement', 'info');
    closeModal('historique-modal');
};

window.clearHistorique = function() {
    if (!confirm('Voulez-vous vraiment effacer tout l\'historique ?')) return;
    
    fetch(CONFIG.HISTORIQUE_ENDPOINT + '?action=clear')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadHistoriqueContent();
                showNotification('🗑️ Historique effacé', 'success');
            }
        })
        .catch(error => {
            showNotification('❌ Erreur lors de la suppression', 'error');
        });
};

// ========== MODAL ==========
function setupModalHandlers() {
    // Fermer avec X
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Fermer en cliquant à l'extérieur
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Échap pour fermer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
};

// ========== VALIDATION ==========
function validateForm() {
    const errors = [];
    let isValid = true;
    
    // Département
    const dept = elements.departement.value.trim();
    if (!dept) {
        errors.push({ field: 'departement', message: 'Département obligatoire' });
        isValid = false;
    } else if (!/^\d{2}$/.test(dept)) {
        errors.push({ field: 'departement', message: 'Format: 2 chiffres (ex: 67)' });
        isValid = false;
    } else {
        const num = parseInt(dept);
        if (num < 1 || num > 95) {
            errors.push({ field: 'departement', message: 'Département invalide (01-95)' });
            isValid = false;
        }
    }
    
    // Poids
    const poids = parseFloat(elements.poids.value);
    if (!poids || poids <= 0) {
        errors.push({ field: 'poids', message: 'Poids obligatoire et > 0' });
        isValid = false;
    } else if (poids > CONFIG.MAX_POIDS) {
        errors.push({ field: 'poids', message: `Poids maximum: ${CONFIG.MAX_POIDS} kg` });
        isValid = false;
    }
    
    // Type
    const type = document.querySelector('input[name="type"]:checked');
    if (!type) {
        errors.push({ field: 'type', message: 'Sélectionnez un type d\'envoi' });
        isValid = false;
    }
    
    // ADR
    const adr = document.querySelector('input[name="adr"]:checked');
    if (!adr) {
        errors.push({ field: 'adr', message: 'Précisez si marchandise dangereuse' });
        isValid = false;
    }
    
    return { isValid, errors };
}

function validateField(element) {
    const fieldName = element.id;
    const value = element.value.trim();
    
    hideFieldError(fieldName);
    
    switch (fieldName) {
        case 'departement':
            if (value && (!/^\d{2}$/.test(value) || parseInt(value) < 1 || parseInt(value) > 95)) {
                showFieldError(fieldName, 'Format: 2 chiffres (01-95)');
                return false;
            }
            break;
            
        case 'poids':
            const poids = parseFloat(value);
            if (value && (poids <= 0 || poids > CONFIG.MAX_POIDS)) {
                showFieldError(fieldName, `Entre 0.1 et ${CONFIG.MAX_POIDS} kg`);
                return false;
            }
            break;
    }
    
    return true;
}

function showValidationErrors(errors) {
    errors.forEach(error => {
        showFieldError(error.field, error.message);
    });
}

function showFieldError(fieldName, message) {
    const errorElement = document.getElementById(`error-${fieldName}`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

function hideFieldError(fieldName) {
    const errorElement = document.getElementById(`error-${fieldName}`);
    if (errorElement) {
        errorElement.classList.remove('show');
    }
}

function showError(message) {
    if (elements.errorContainer) {
        elements.errorContainer.innerHTML = message;
        elements.errorContainer.style.display = 'block';
    }
}

function clearErrors() {
    if (elements.errorContainer) {
        elements.errorContainer.style.display = 'none';
    }
    
    document.querySelectorAll('.field-error').forEach(error => {
        error.classList.remove('show');
    });
}

// ========== UTILITAIRES ==========
function getFormData() {
    const typeInput = document.querySelector('input[name="type"]:checked');
    const adrInput = document.querySelector('input[name="adr"]:checked');
    
    return {
        departement: elements.departement.value.trim(),
        poids: elements.poids.value.trim(),
        type: typeInput ? typeInput.value : '',
        adr: adrInput ? adrInput.value : '',
        option_sup: elements.optionSup ? elements.optionSup.value : 'standard',
        enlevement: elements.enlevement ? (elements.enlevement.checked ? '1' : '0') : '0',
        palettes: elements.palettes ? elements.palettes.value : '0'
    };
}

function updateCalculatingUI(isCalculating) {
    if (elements.loadingZone) {
        elements.loadingZone.style.display = isCalculating ? 'block' : 'none';
    }
    
    if (elements.btnCalculate) {
        elements.btnCalculate.disabled = isCalculating;
        elements.btnCalculate.innerHTML = isCalculating ? 
            '<span>⏳</span> Calcul...' : 
            '<span>🚀</span> Calculer les tarifs';
    }
}

function updateButtonState() {
    if (elements.btnCalculate) {
        const isValid = CalculatorState.isFormValid();
        elements.btnCalculate.disabled = !isValid || CalculatorState.isCalculating;
        
        if (!isValid) {
            elements.btnCalculate.innerHTML = '<span>📝</span> Complétez le formulaire';
        } else if (!CalculatorState.isCalculating) {
            elements.btnCalculate.innerHTML = '<span>🚀</span> Calculer les tarifs';
        }
    }
}

function handleFormReset() {
    setTimeout(() => {
        CalculatorState.currentResults = null;
        CalculatorState.formData = {};
        hidePaletteOptions();
        clearErrors();
        updateButtonState();
        
        // Reset UI
        elements.alertsZone.style.display = 'none';
        elements.comparisonZone.style.display = 'none';
        elements.quickActions.style.display = 'none';
        
        elements.resultStatus.textContent = 'En attente';
        elements.resultContent.innerHTML = `
            <div class="result-placeholder">
                <div class="placeholder-icon">🚀</div>
                <h4>Prêt à calculer</h4>
                <p>Renseignez le formulaire pour voir les tarifs</p>
            </div>
        `;
    }, 10);
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

function showNotification(message, type = 'info', duration = 4000) {
    // Créer ou utiliser le container de notifications
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10001;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        pointer-events: all;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    `;
    
    notification.innerHTML = `
        ${getNotificationIcon(type)}
        <span style="flex: 1;">${message}</span>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 0.25rem;">
            ×
        </button>
    `;
    
    container.appendChild(notification);
    
    // Auto-suppression
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, type === 'error' ? 8000 : duration);
}

function getNotificationColor(type) {
    const colors = {
        'success': 'var(--gul-success)',
        'error': 'var(--gul-error)',
        'warning': 'var(--gul-warning)',
        'info': 'var(--gul-blue-primary)'
    };
    return colors[type] || colors.info;
}

function getNotificationIcon(type) {
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    return icons[type] || icons.info;
}

// ========== ANIMATIONS CSS ==========
if (!document.getElementById('module-animations')) {
    const style = document.createElement('style');
    style.id = 'module-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// ========== NETTOYAGE ==========
window.addEventListener('beforeunload', function() {
    if (CalculatorState.calculationTimeout) {
        clearTimeout(CalculatorState.calculationTimeout);
    }
});

console.log('✅ Module Calculateur V2 chargé avec succès');
console.log('🎯 Fonctionnalités: calcul auto/manuel, validation temps réel, historique, export');
