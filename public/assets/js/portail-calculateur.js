// public/assets/js/portail-calculateur.js - Calculateur Portail V2

console.log('🚀 Chargement Calculateur Portail V2...');

// ========== CONFIGURATION ========== 
const CONFIG = {
    AUTO_CALC_DELAY: 500,
    SEUILS_ALERTES: [100, 1000, 2000, 3000],
    MAX_POIDS: 3500,
    MIN_POIDS: 1
};

// ========== VARIABLES GLOBALES ==========
let calculationTimeout = null;
let currentResults = null;
let isCalculating = false;

// ========== ÉLÉMENTS DOM ==========
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
    paletteField: document.getElementById('palette-field'),
    
    // Résultat
    loading: document.getElementById('loading'),
    resultZone: document.getElementById('result-zone'),
    alertsContainer: document.getElementById('alerts-container'),
    
    // Actions
    formActions: document.getElementById('form-actions'),
    btnCompare: document.getElementById('btn-compare'),
    btnHistorique: document.getElementById('btn-historique'),
    btnReset: document.getElementById('btn-reset'),
    
    // Erreurs
    errorContainer: document.getElementById('error-container')
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Initialisation Calculateur Portail V2');
    setupEventListeners();
    initializeForm();
});

function initializeForm() {
    // Focus automatique sur département
    setTimeout(() => {
        if (elements.departement) {
            elements.departement.focus();
        }
    }, 500);
    
    // Charger l'historique en arrière-plan
    loadHistoriqueInBackground();
}

function setupEventListeners() {
    // Auto-progression département -> poids
    if (elements.departement) {
        elements.departement.addEventListener('input', handleDepartementInput);
        elements.departement.addEventListener('focus', () => elements.departement.select());
    }
    
    // Calcul automatique
    if (elements.poids) {
        elements.poids.addEventListener('input', handlePoidsInput);
    }
    
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
    if (elements.optionSup) {
        elements.optionSup.addEventListener('change', triggerCalculation);
    }
    
    if (elements.enlevement) {
        elements.enlevement.addEventListener('change', handleEnlevementChange);
    }
    
    // Boutons palettes
    setupPaletteButtons();
    
    // Actions
    if (elements.btnCompare) {
        elements.btnCompare.addEventListener('click', showDetailedComparison);
    }
    
    if (elements.btnHistorique) {
        elements.btnHistorique.addEventListener('click', showHistorique);
    }
    
    if (elements.btnReset) {
        elements.btnReset.addEventListener('click', resetForm);
    }
    
    // Modal historique
    setupHistoriqueModal();
}

// ========== GESTION SAISIE ==========
function handleDepartementInput() {
    const value = elements.departement.value;
    
    // Auto-progression si 2 chiffres valides
    if (value.length === 2 && /^\d{2}$/.test(value)) {
        const deptNum = parseInt(value);
        if (deptNum >= 1 && deptNum <= 95) {
            elements.poids.focus();
        }
    }
    
    triggerCalculation();
}

function handlePoidsInput() {
    const poids = parseFloat(elements.poids.value) || 0;
    
    // Auto-sélection palette si > 60kg
    if (poids >= 60) {
        const paletteRadio = document.getElementById('type-palette');
        const colisRadio = document.getElementById('type-colis');
        
        if (paletteRadio && !paletteRadio.checked) {
            paletteRadio.checked = true;
            colisRadio.disabled = true;
            togglePaletteField();
            
            showAlert('⚠️ Poids élevé → Palette automatiquement sélectionnée', 'warning');
        }
    } else {
        // Réactiver l'option colis si < 60kg
        const colisRadio = document.getElementById('type-colis');
        if (colisRadio) {
            colisRadio.disabled = false;
        }
    }
    
    triggerCalculation();
}

function handleEnlevementChange() {
    // Si enlèvement activé, forcer option standard
    if (elements.enlevement.checked && elements.optionSup) {
        elements.optionSup.value = 'standard';
        elements.optionSup.disabled = true;
        showAlert('ℹ️ Enlèvement → Options livraison désactivées', 'info');
    } else if (elements.optionSup) {
        elements.optionSup.disabled = false;
    }
    
    triggerCalculation();
}

function togglePaletteField() {
    const selectedType = document.querySelector('input[name="type"]:checked');
    
    if (selectedType && selectedType.value === 'palette') {
        elements.paletteField.style.display = 'block';
        if (!elements.palettes.value) {
            elements.palettes.value = '1';
            updatePaletteButtons();
        }
        // Afficher options avancées
        document.getElementById('advanced-options').style.display = 'block';
    } else {
        elements.paletteField.style.display = 'none';
        elements.palettes.value = '0';
    }
}

// ========== BOUTONS PALETTES ==========
function setupPaletteButtons() {
    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const value = btn.dataset.value;
            
            if (value === 'plus') {
                showAlert('⚠️ Pour plus de 3 palettes, contactez notre service achat : 📞 03 89 63 42 42', 'warning');
                elements.palettes.value = '';
            } else {
                elements.palettes.value = value;
                updatePaletteButtons();
                triggerCalculation();
            }
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

// ========== CALCUL PRINCIPAL ==========
function triggerCalculation() {
    if (calculationTimeout) {
        clearTimeout(calculationTimeout);
    }
    
    calculationTimeout = setTimeout(() => {
        if (isFormValid() && !isCalculating) {
            performCalculation();
        }
    }, CONFIG.AUTO_CALC_DELAY);
}

function isFormValid() {
    const dept = elements.departement.value.trim();
    const poids = elements.poids.value.trim();
    const type = document.querySelector('input[name="type"]:checked');
    const adr = document.querySelector('input[name="adr"]:checked');
    
    return dept && /^\d{2}$/.test(dept) && 
           poids && parseFloat(poids) > 0 && 
           type && adr;
}

function performCalculation() {
    if (isCalculating) return;
    
    isCalculating = true;
    clearErrors();
    showLoading(true);
    
    // Préparer les données
    const formData = new FormData();
    formData.append('departement', elements.departement.value);
    formData.append('poids', elements.poids.value);
    
    const selectedType = document.querySelector('input[name="type"]:checked');
    const selectedAdr = document.querySelector('input[name="adr"]:checked');
    
    if (selectedType) formData.append('type', selectedType.value);
    if (selectedAdr) formData.append('adr', selectedAdr.value);
    
    formData.append('option_sup', elements.optionSup?.value || 'standard');
    formData.append('enlevement', elements.enlevement?.checked ? '1' : '0');
    formData.append('palettes', elements.palettes?.value || '0');
    
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
        showError('❌ Erreur lors du calcul. Veuillez réessayer.');
    })
    .finally(() => {
        isCalculating = false;
        showLoading(false);
    });
}

// ========== TRAITEMENT RÉSULTATS ==========
function handleCalculationResult(data) {
    currentResults = data;
    
    if (data.affretement) {
        displayAffretement(data.message);
        return;
    }
    
    if (data.errors && data.errors.length > 0) {
        showError('❌ ' + data.errors.join('<br>'));
        return;
    }
    
    if (data.success && data.bestCarrier) {
        displayResults(data);
        showAdvancedActions();
        
        // Alertes de seuils
        checkAndDisplayAlerts(data);
    } else {
        displayNoResults();
    }
}

function displayResults(data) {
    const bestCarrier = data.formatted[data.bestCarrier];
    const bestPrice = data.best;
    
    // Récap des paramètres
    const selectedType = document.querySelector('input[name="type"]:checked');
    const selectedAdr = document.querySelector('input[name="adr"]:checked');
    
    let html = `
        <div class="result-success">
            <div class="result-best-price">
                🏆 ${bestCarrier.name} : ${bestCarrier.formatted}
            </div>
            <div class="result-details">
                ${elements.departement.value} • ${elements.poids.value}kg • 
                ${selectedType?.value || '?'} • ADR:${selectedAdr?.value === 'oui' ? 'Oui' : 'Non'}
            </div>
        </div>
    `;
    
    // Message de remise en palette si applicable
    if (data.fallback && data.fallback.hasBetter) {
        html += `
            <div class="alert alert-info" style="margin-top: var(--spacing-md);">
                ✨ <strong>Remise en palette disponible</strong><br>
                Économie de ${formatPrice(data.fallback.savings)} avec ${data.fallback.carrier}
            </div>
        `;
    }
    
    elements.resultZone.innerHTML = html;
}

function displayAffretement(message) {
    elements.resultZone.innerHTML = `
        <div style="text-align: center; padding: var(--spacing-xl); background: #fffbeb; border: 2px solid var(--gul-warning); border-radius: var(--radius); color: var(--gul-warning);">
            <div style="font-size: 2rem; margin-bottom: var(--spacing-md);">🚛</div>
            <h4 style="margin-bottom: var(--spacing-md);">Affrètement nécessaire</h4>
            <p style="margin-bottom: var(--spacing-md);">${message}</p>
            <strong style="color: var(--gul-blue-primary);">📞 Service achat : 03 89 63 42 42</strong>
        </div>
    `;
}

function displayNoResults() {
    elements.resultZone.innerHTML = `
        <div class="result-placeholder">
            <div class="placeholder-icon">❌</div>
            <p>Aucun tarif disponible pour ces critères</p>
        </div>
    `;
}

// ========== ALERTES SEUILS ==========
function checkAndDisplayAlerts(data) {
    if (!data.results || !elements.alertsContainer) return;
    
    const poids = parseFloat(elements.poids.value);
    const bestPrice = data.best;
    let alerts = [];
    
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
                    message: `💡 Payant pour déclarer ${seuil}kg → Économie de ${formatPrice(economies)}`
                });
            }
        }
    });
    
    // Afficher les alertes
    if (alerts.length > 0) {
        let html = '';
        alerts.forEach(alert => {
            html += `<div class="alert alert-warning">${alert.message}</div>`;
        });
        elements.alertsContainer.innerHTML = html;
    } else {
        elements.alertsContainer.innerHTML = '';
    }
}

// ========== COMPARAISON DÉTAILLÉE ==========
function showDetailedComparison() {
    if (!currentResults || !currentResults.formatted) {
        showAlert('❌ Aucune donnée de comparaison disponible', 'error');
        return;
    }
    
    // Créer HTML de comparaison
    let html = '<div class="result-comparison">';
    html += '<div class="comparison-header">📊 Comparaison détaillée</div>';
    
    // Trier par prix croissant
    const sortedResults = Object.entries(currentResults.formatted)
        .filter(([key, carrier]) => carrier.price !== null)
        .sort(([,a], [,b]) => a.price - b.price);
    
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
    
    html += '</div>';
    
    // Injecter après le résultat principal
    const existingComparison = elements.resultZone.querySelector('.result-comparison');
    if (existingComparison) {
        existingComparison.remove();
    }
    
    elements.resultZone.insertAdjacentHTML('beforeend', html);
    
    // Scroll vers la comparaison
    elements.resultZone.querySelector('.result-comparison').scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}

// ========== HISTORIQUE ==========
function loadHistoriqueInBackground() {
    // Charger l'historique en arrière-plan pour l'avoir prêt
    fetch('ajax-historique.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`📋 Historique chargé: ${data.historique.length} entrées`);
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
    
    content.innerHTML = '<div style="text-align: center; padding: 2rem;">Chargement...</div>';
    
    fetch('ajax-historique.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historique.length > 0) {
                displayHistoriqueTable(data.historique, content);
            } else {
                content.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--gul-gray-500);">Aucun historique disponible</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--gul-error);">Erreur lors du chargement</div>';
        });
}

function displayHistoriqueTable(historique, container) {
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
    
    historique.forEach(entry => {
        const date = new Date(entry.date);
        html += `
            <tr style="border-bottom: 1px solid var(--gul-gray-200);">
                <td style="padding: 0.5rem; font-size: 0.8rem; color: var(--gul-gray-600);">
                    ${date.toLocaleDateString('fr-FR')}<br>
                    ${date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}
                </td>
                <td style="padding: 0.5rem;">
                    ${entry.departement} • ${entry.poids}kg<br>
                    <small style="color: var(--gul-gray-500);">${entry.type} • ADR:${entry.adr}</small>
                </td>
                <td style="padding: 0.5rem; font-weight: 600; color: var(--gul-blue-primary);">
                    ${entry.best_carrier}
                </td>
                <td style="padding: 0.5rem; text-align: right; font-weight: bold; color: var(--gul-success);">
                    ${formatPrice(entry.best_price)}
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

function setupHistoriqueModal() {
    // Fermer modal avec X
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
        });
    });
    
    // Fermer modal en cliquant à l'extérieur
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

window.clearHistorique = function() {
    if (!confirm('Voulez-vous vraiment effacer tout l\'historique ?')) return;
    
    fetch('ajax-historique.php?action=clear')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadHistoriqueContent();
                showAlert('🗑️ Historique effacé', 'info');
            }
        })
        .catch(error => {
            showAlert('❌ Erreur lors de la suppression', 'error');
        });
};

// ========== RESET FORMULAIRE ==========
function resetForm() {
    if (!confirm('Voulez-vous vraiment recommencer le calcul ?')) return;
    
    // Reset du formulaire
    elements.form.reset();
    
    // Reset des états
    clearErrors();
    currentResults = null;
    isCalculating = false;
    
    // Reset de l'interface
    document.getElementById('advanced-options').style.display = 'none';
    elements.paletteField.style.display = 'none';
    elements.formActions.style.display = 'none';
    elements.alertsContainer.innerHTML = '';
    
    // Reset du résultat
    elements.resultZone.innerHTML = `
        <div class="result-placeholder">
            <div class="placeholder-icon">🚀</div>
            <p>Renseignez vos informations pour voir les tarifs</p>
        </div>
    `;
    
    // Réactiver tous les champs
    document.getElementById('type-colis').disabled = false;
    if (elements.optionSup) {
        elements.optionSup.disabled = false;
    }
    
    // Focus sur le premier champ
    setTimeout(() => elements.departement.focus(), 100);
    
    showAlert('🔄 Formulaire réinitialisé', 'info');
}

// ========== ACTIONS AVANCÉES ==========
function showAdvancedActions() {
    if (elements.formActions) {
        elements.formActions.style.display = 'flex';
    }
    
    // Afficher options avancées si pas encore visible
    const advancedOptions = document.getElementById('advanced-options');
    if (advancedOptions && advancedOptions.style.display === 'none') {
        advancedOptions.style.display = 'block';
    }
}

// ========== GESTION ERREURS ==========
function showError(message) {
    if (elements.errorContainer) {
        elements.errorContainer.innerHTML = message;
        elements.errorContainer.style.display = 'block';
    }
}

function clearErrors() {
    if (elements.errorContainer) {
        elements.errorContainer.style.display = 'none';
        elements.errorContainer.innerHTML = '';
    }
    
    // Supprimer erreurs de champs
    document.querySelectorAll('.field-error').forEach(error => {
        error.classList.remove('show');
    });
}

function showLoading(show) {
    if (elements.loading) {
        elements.loading.style.display = show ? 'block' : 'none';
    }
}

// ========== ALERTES SYSTÈME ==========
function showAlert(message, type = 'info', duration = 4000) {
    // Créer ou utiliser le container d'alertes système
    let container = document.getElementById('system-alerts');
    if (!container) {
        container = document.createElement('div');
        container.id = 'system-alerts';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    const alert = document.createElement('div');
    alert.style.cssText = `
        background: ${getAlertColor(type)};
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
    
    alert.innerHTML = `
        ${getAlertIcon(type)}
        <span style="flex: 1;">${message}</span>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 0.25rem;">
            ×
        </button>
    `;
    
    container.appendChild(alert);
    
    // Auto-suppression
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 300);
        }
    }, type === 'error' ? 6000 : duration);
}

function getAlertColor(type) {
    const colors = {
        'success': 'var(--gul-success)',
        'error': 'var(--gul-error)',
        'warning': 'var(--gul-warning)',
        'info': 'var(--gul-blue-primary)'
    };
    return colors[type] || colors.info;
}

function getAlertIcon(type) {
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    return icons[type] || icons.info;
}

// ========== UTILITAIRES ==========
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

// ========== ANIMATIONS CSS ==========
if (!document.getElementById('portail-animations')) {
    const style = document.createElement('style');
    style.id = 'portail-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .loading-state {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .result-success {
            animation: slideUpFade 0.5s ease;
        }
        
        @keyframes slideUpFade {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}

// ========== NAVIGATION SMOOTH ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ========== ANALYTICS ==========
document.querySelectorAll('a[target="_blank"]').forEach(link => {
    link.addEventListener('click', function() {
        console.log('🔗 Lien externe:', this.href);
        // Ici vous pouvez ajouter du tracking analytics
    });
});

// ========== NETTOYAGE ==========
window.addEventListener('beforeunload', function() {
    if (calculationTimeout) {
        clearTimeout(calculationTimeout);
    }
});

console.log('✅ Calculateur Portail V2 chargé avec succès');
console.log('🎯 Fonctionnalités: calcul auto, alertes seuils, historique, comparaison');
