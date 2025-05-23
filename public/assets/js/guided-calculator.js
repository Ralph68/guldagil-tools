// guided-calculator.js - Interface guidée avec calcul dynamique
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 DOM chargé, initialisation en cours...');
    
    // =============================================================================
    // VARIABLES ET ÉLÉMENTS
    // =============================================================================
    
    // Éléments du formulaire
    const form = document.getElementById('calc-form');
    const steps = document.querySelectorAll('.form-step');
    const progressBar = document.getElementById('progress-bar');
    
    // Champs de saisie
    const departement = document.getElementById('departement');
    const poids = document.getElementById('poids');
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const adrInputs = document.querySelectorAll('input[name="adr"]');
    const optionInputs = document.querySelectorAll('input[name="option_sup"]');
    const enlevement = document.getElementById('enlevement');
    const palettes = document.getElementById('palettes');
    
    // Éléments de résultat et UI
    const loading = document.getElementById('loading');
    const resultContent = document.getElementById('result-content');
    const bestResult = document.getElementById('best-result');
    const errorContainer = document.getElementById('error-container');
    const resetBtn = document.getElementById('btn-reset');
    
    // Sections spéciales
    const paletteSection = document.getElementById('palette-section');
    const paletteButtons = document.querySelectorAll('.palette-btn');
    const paletteInfo = document.getElementById('palette-info');
    
    // État de l'application
    let currentStep = 1;
    let formData = {};
    let calculateTimeout = null;
    let hasFirstCalculation = false;
    
    // =============================================================================
    // MODAL DE COMPARAISON - Définies en premier
    // =============================================================================
    
    function showComparison() {
        console.log('🔍 showComparison appelée');
        
        if (!window.lastCalculationData) {
            console.error('❌ Aucune donnée de calcul disponible');
            alert('Aucune donnée de calcul disponible');
            return;
        }
        
        const data = window.lastCalculationData;
        console.log('✅ Données disponibles:', data);
        
        // Re-chercher les éléments au moment de l'utilisation
        const modal = document.getElementById('comparison-modal');
        const body = document.getElementById('comparison-body');
        
        console.log('🔍 Recherche des éléments modal:');
        console.log('modal trouvée:', modal);
        console.log('body trouvé:', body);
        
        if (!modal) {
            console.error('❌ Modal comparison-modal non trouvée dans le DOM');
            console.log('🔍 Éléments disponibles avec id contenant "modal":');
            document.querySelectorAll('[id*="modal"]').forEach(el => {
                console.log('- ', el.id, el);
            });
            alert('Erreur: Modal non trouvée dans le DOM');
            return;
        }
        
        if (!body) {
            console.error('❌ Element comparison-body non trouvé dans le DOM');
            console.log('🔍 Éléments disponibles avec id contenant "comparison":');
            document.querySelectorAll('[id*="comparison"]').forEach(el => {
                console.log('- ', el.id, el);
            });
            alert('Erreur: Body modal non trouvé dans le DOM');
            return;
        }
        
        let html = '<div class="comparison-grid">';
        
        Object.keys(data.formatted).forEach(key => {
            const carrier = data.formatted[key];
            const isBest = key === data.bestCarrier;
            const isAvailable = carrier.price !== null;
            
            html += `
                <div class="comparison-card ${isBest ? 'best' : ''} ${!isAvailable ? 'unavailable' : ''}" data-carrier="${key}">
                    <h4>${carrier.name}</h4>
                    <div class="comparison-price">${carrier.formatted}</div>
                    ${carrier.debug && isAvailable ? 
                        `<button class="btn-details" data-carrier="${key}">📋 Détails</button>` 
                        : ''}
                    <div class="carrier-details" id="details-${key}" style="display: none;"></div>
                </div>
            `;
        });
        
        html += '</div>';
        body.innerHTML = html;
        
        // Ajouter les event listeners pour les boutons détails
        const detailButtons = body.querySelectorAll('.btn-details');
        detailButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const carrierId = e.target.getAttribute('data-carrier');
                toggleCarrierDetails(carrierId);
            });
        });
        
        modal.classList.add('active');
        
        console.log('✅ Modal de comparaison affichée avec succès');
    }
    
    function closeComparison() {
        const modal = document.getElementById('comparison-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }
    
    function toggleCarrierDetails(carrierId) {
        if (!window.lastCalculationData) return;
        
        const carrier = window.lastCalculationData.formatted[carrierId];
        const debug = carrier.debug;
        const detailsDiv = document.getElementById(`details-${carrierId}`);
        
        if (!debug || !detailsDiv) return;
        
        if (detailsDiv.style.display === 'none') {
            // Afficher les détails
            let detailsHtml = `
                <div style="margin-top: 1rem; padding: 1rem; background: #f9f9f9; border-radius: 6px; text-align: left;">
                    <h6 style="margin: 0 0 0.5rem 0; color: #333;">Détail du calcul</h6>
                    <table style="width: 100%; font-size: 0.8rem;">
            `;
            
            Object.keys(debug).forEach(key => {
                if (key === 'carrier' || key === 'error') return;
                
                let label = key.replace(/_/g, ' ');
                label = label.charAt(0).toUpperCase() + label.slice(1);
                const value = debug[key];
                
                if (typeof value === 'number') {
                    detailsHtml += `<tr><td style="padding: 0.2rem 0;">${label}:</td><td style="text-align: right;">${value.toFixed(2)} €</td></tr>`;
                } else if (typeof value === 'boolean') {
                    detailsHtml += `<tr><td style="padding: 0.2rem 0;">${label}:</td><td style="text-align: right;">${value ? 'Oui' : 'Non'}</td></tr>`;
                } else {
                    detailsHtml += `<tr><td style="padding: 0.2rem 0;">${label}:</td><td style="text-align: right;">${value}</td></tr>`;
                }
            });
            
            detailsHtml += '</table></div>';
            detailsDiv.innerHTML = detailsHtml;
            detailsDiv.style.display = 'block';
        } else {
            // Masquer les détails
            detailsDiv.style.display = 'none';
        }
    }
    
    // =============================================================================
    // GESTION DES ÉTAPES
    // =============================================================================
    
    function showStep(stepNumber, autoScroll = true) {
        steps.forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            
            if (stepNum < stepNumber) {
                // Étapes précédentes : completées
                step.style.display = 'block';
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (stepNum === stepNumber) {
                // Étape actuelle : active
                step.style.display = 'block';
                step.classList.add('active', 'reveal');
                step.classList.remove('completed');
                
                // Scroll vers l'étape seulement si demandé
                if (autoScroll) {
                    setTimeout(() => {
                        step.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }, 100);
                }
            } else {
                // Étapes futures : masquées
                step.style.display = 'none';
                step.classList.remove('active', 'completed', 'reveal');
            }
        });
        
        updateProgressBar(stepNumber);
        currentStep = stepNumber;
    }
    
    function updateProgressBar(stepNumber) {
        const totalSteps = 6;
        const progress = (stepNumber / totalSteps) * 100;
        progressBar.style.width = `${progress}%`;
    }
    
    function nextStep() {
        if (currentStep < 6) {
            showStep(currentStep + 1);
        }
    }
    
    function canProceedToStep(stepNumber) {
        switch (stepNumber) {
            case 2: // Poids
                return validateDepartement();
            case 3: // Type (sauf si > 60kg)
                const poidsValue = parseFloat(poids.value);
                if (poidsValue > 60) {
                    // Si > 60kg, on peut passer directement à l'étape 4 (ADR)
                    return validateDepartement() && validatePoids();
                }
                return validateDepartement() && validatePoids();
            case 4: // ADR
                return validateDepartement() && validatePoids() && validateType();
            case 5: // Options (après premier calcul)
                return hasFirstCalculation;
            case 6: // Compléments
                return hasFirstCalculation;
            default:
                return true;
        }
    }
    
    // =============================================================================
    // VALIDATION DES CHAMPS
    // =============================================================================
    
    function validateDepartement() {
        const value = departement.value.trim();
        const errorEl = document.getElementById('error-departement');
        
        if (!value) {
            showFieldError('departement', 'Le département est requis');
            return false;
        }
        
        if (!/^[0-9]{2}$/.test(value)) {
            showFieldError('departement', 'Le département doit être composé de 2 chiffres');
            return false;
        }
        
        hideFieldError('departement');
        return true;
    }
    
    function validatePoids() {
        const value = parseFloat(poids.value);
        const errorEl = document.getElementById('error-poids');
        
        if (!value || value <= 0) {
            showFieldError('poids', 'Le poids doit être supérieur à 0');
            return false;
        }
        
        if (value > 3500) {
            showFieldError('poids', 'Le poids ne peut pas dépasser 3500 kg');
            return false;
        }
        
        hideFieldError('poids');
        return true;
    }
    
    function validateType() {
        const selectedType = document.querySelector('input[name="type"]:checked');
        
        // Si > 60kg, forcer automatiquement palette (pas d'erreur)
        const poidsValue = parseFloat(poids.value);
        if (poidsValue > 60) {
            const paletteRadio = document.getElementById('type-palette');
            if (!selectedType && paletteRadio) {
                paletteRadio.checked = true;
            }
            hideFieldError('type');
            return true;
        }
        
        // Sinon validation normale
        if (!selectedType) {
            showFieldError('type', 'Veuillez sélectionner un type d\'envoi');
            return false;
        }
        
        hideFieldError('type');
        return true;
    }
    
    function validateADR() {
        const selectedAdr = document.querySelector('input[name="adr"]:checked');
        if (!selectedAdr) {
            showFieldError('adr', 'Veuillez indiquer si la marchandise est dangereuse');
            return false;
        }
        
        hideFieldError('adr');
        return true;
    }
    
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
    
    // =============================================================================
    // GESTION DES SECTIONS SPÉCIALES
    // =============================================================================
    
    function togglePaletteSection() {
        const selectedType = document.querySelector('input[name="type"]:checked');
        if (selectedType && selectedType.value === 'palette') {
            paletteSection.style.display = 'block';
            // Sélectionner 1 palette par défaut
            if (!palettes.value) {
                palettes.value = '1';
                updatePaletteButtons();
            }
        } else {
            paletteSection.style.display = 'none';
            palettes.value = '0';
        }
    }
    
    function updatePaletteButtons() {
        const currentValue = palettes.value;
        paletteButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.palettes === currentValue) {
                btn.classList.add('active');
            }
        });
    }
    
    function handleEnlevementChange() {
        if (enlevement.checked) {
            // Désactiver les options premium quand enlèvement activé
            optionInputs.forEach(input => {
                if (input.value !== 'standard') {
                    input.disabled = true;
                    input.closest('label').style.opacity = '0.5';
                }
            });
            // Forcer option standard
            document.getElementById('opt-standard').checked = true;
        } else {
            // Réactiver toutes les options
            optionInputs.forEach(input => {
                input.disabled = false;
                input.closest('label').style.opacity = '1';
            });
        }
    }
    
    // =============================================================================
    // CALCUL DYNAMIQUE
    // =============================================================================
    
    function shouldCalculate() {
        const poidsValue = parseFloat(poids.value);
        
        // Si > 60kg, palette est automatiquement sélectionnée
        if (poidsValue > 60) {
            return validateDepartement() && 
                   validatePoids() && 
                   validateADR();
        }
        
        // Sinon validation normale
        return validateDepartement() && 
               validatePoids() && 
               validateType() && 
               validateADR();
    }
    
    function calculatePrices() {
        // Annuler le calcul précédent
        if (calculateTimeout) {
            clearTimeout(calculateTimeout);
        }
        
        // Vérifier si on peut calculer
        if (!shouldCalculate()) {
            return;
        }
        
        // Attendre un peu pour éviter trop de requêtes (debounce)
        calculateTimeout = setTimeout(() => {
            performCalculation();
        }, 300);
    }
    
    function performCalculation() {
        // Préparer les données
        const formData = new FormData();
        formData.append('departement', departement.value);
        formData.append('poids', poids.value);
        
        const selectedType = document.querySelector('input[name="type"]:checked');
        const selectedAdr = document.querySelector('input[name="adr"]:checked');
        const selectedOption = document.querySelector('input[name="option_sup"]:checked');
        
        if (selectedType) formData.append('type', selectedType.value);
        if (selectedAdr) formData.append('adr', selectedAdr.value);
        if (selectedOption) formData.append('option_sup', selectedOption.value);
        
        formData.append('enlevement', enlevement.checked ? '1' : '0');
        formData.append('palettes', palettes.value || '0');
        
        // Afficher le loading
        showLoading();
        
        // Faire la requête AJAX
        fetch('ajax-calculate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            handleCalculationResult(data);
        })
        .catch(error => {
            hideLoading();
            console.error('Erreur:', error);
            showError('Erreur lors du calcul des tarifs');
        });
    }
    
    function handleCalculationResult(data) {
        clearErrors();
        
        // 1. Affrètement nécessaire
        if (data.affretement) {
            displayAffretement(data.message);
            return;
        }
        
        // 2. Erreurs de validation
        if (data.errors && data.errors.length > 0) {
            displayErrors(data.errors);
            return;
        }
        
        // 3. Résultat valide
        if (data.success && data.bestCarrier) {
            displayBestResult(data);
            
            // Premier calcul : débloquer les étapes suivantes
            if (!hasFirstCalculation) {
                hasFirstCalculation = true;
                // Révéler l'étape options si pas encore visible
                if (currentStep === 4) {
                    nextStep(); // Aller à l'étape 5 (options)
                }
            }
        } else {
            showError('Aucun tarif disponible pour ces critères');
        }
    }
    
    function displayBestResult(data) {
        const bestCarrier = data.formatted[data.bestCarrier];
        
        // Récupérer les données saisies pour le récapitulatif
        const selectedType = document.querySelector('input[name="type"]:checked');
        const selectedAdr = document.querySelector('input[name="adr"]:checked');
        const selectedOption = document.querySelector('input[name="option_sup"]:checked');
        
        let html = `
            <!-- Récapitulatif de la saisie -->
            <div class="recap-saisie">
                <h3>📋 Récapitulatif</h3>
                <div class="recap-grid">
                    <span><strong>Département:</strong> ${departement.value}</span>
                    <span><strong>Poids:</strong> ${poids.value} kg</span>
                    <span><strong>Type:</strong> ${selectedType ? selectedType.value : 'Non défini'}</span>
                    <span><strong>ADR:</strong> ${selectedAdr ? (selectedAdr.value === 'oui' ? 'Oui' : 'Non') : 'Non défini'}</span>
                    ${selectedOption && selectedOption.value !== 'standard' ? 
                        `<span><strong>Option:</strong> ${getOptionLabel(selectedOption.value)}</span>` : ''}
                    ${enlevement.checked ? '<span><strong>Enlèvement:</strong> Oui</span>' : ''}
                    ${palettes.value && palettes.value !== '0' ? 
                        `<span><strong>Palettes:</strong> ${palettes.value}</span>` : ''}
                </div>
                <button type="button" class="btn-modify" onclick="scrollToFirstStep()">✏️ Modifier</button>
            </div>
            
            <!-- Meilleur résultat -->
            <div class="best-result">
                <div class="carrier-info">
                    <div class="carrier-name">${bestCarrier.name}</div>
                    <div class="carrier-price">${bestCarrier.formatted}</div>
                </div>
                <div class="result-actions">
                    <button type="button" class="btn-details" id="btn-compare">
                        📊 Comparer
                    </button>
                </div>
            </div>
        `;
        
        // Alertes de seuils
        if (data.alerts && data.alerts.length > 0) {
            data.alerts.forEach(alert => {
                if (alert.carrier === data.bestCarrier) {
                    html += `
                        <div class="alert">
                            💡 ${alert.message} - Économie : ${alert.savings.toFixed(2)} €
                        </div>
                    `;
                }
            });
        }
        
        // Message de remise en palette si applicable
        if (data.fallback && data.fallback.hasBetter) {
            html += `
                <div class="alert">
                    ✨ Remise en palette disponible chez ${data.formatted[data.fallback.carrier].name}
                    <br><small>Économie de ${data.fallback.savings.toFixed(2)} € en remettant sur palette</small>
                </div>
            `;
        }
        
        bestResult.innerHTML = html;
        
        // Ajouter l'event listener après avoir créé le bouton
        const compareBtn = document.getElementById('btn-compare');
        if (compareBtn) {
            compareBtn.addEventListener('click', showComparison);
            console.log('Event listener ajouté au bouton Comparer');
        }
        
        // Sauvegarder les données pour comparaison
        window.lastCalculationData = data;
        
        console.log('Données sauvegardées pour comparaison:', data);
    }
    
    // Fonction pour obtenir le libellé des options
    function getOptionLabel(optionValue) {
        const labels = {
            'rdv': 'Prise de RDV',
            'premium13': 'Premium avant 13h',
            'premium18': 'Premium avant 18h',
            'datefixe': 'Date fixe'
        };
        return labels[optionValue] || optionValue;
    }
    
    // Fonction pour revenir à la première étape
    window.scrollToFirstStep = function() {
        showStep(1, true); // avec scroll
        setTimeout(() => departement.focus(), 300);
    };
    
    function displayAffretement(message) {
        bestResult.innerHTML = `
            <div class="affretement-message">
                <h3>🚛 Affrètement nécessaire</h3>
                <p>${message}</p>
                <p><strong>📞 Service achat : 03 89 63 42 42</strong></p>
            </div>
        `;
    }
    
    function displayErrors(errors) {
        let html = '<div class="error"><ul>';
        errors.forEach(error => {
            html += `<li>${error}</li>`;
        });
        html += '</ul></div>';
        errorContainer.innerHTML = html;
    }
    
    function showError(message) {
        errorContainer.innerHTML = `<div class="error">${message}</div>`;
    }
    
    function clearErrors() {
        errorContainer.innerHTML = '';
    }
    
    function showLoading() {
        loading.classList.add('active');
        resultContent.classList.add('loading');
    }
    
    function hideLoading() {
        loading.classList.remove('active');
        resultContent.classList.remove('loading');
    }
    
    // =============================================================================
    // GESTION DES ÉVÉNEMENTS
    // =============================================================================
    
    // Auto-focus et progression des étapes
    let poidsTimeout = null;
    
    departement.addEventListener('input', () => {
        if (departement.value.length === 2 && validateDepartement()) {
            if (canProceedToStep(2)) {
                showStep(2);
                setTimeout(() => poids.focus(), 200);
            }
        }
        calculatePrices();
    });
    
    departement.addEventListener('focus', () => {
        departement.select();
    });
    
    poids.addEventListener('input', () => {
        // Afficher l'étape 3 (type) dès la saisie du premier chiffre, SANS scroll
        const poidsValue = parseFloat(poids.value);
        if (poids.value.length > 0 && poidsValue > 0) {
            // Forcer palette si > 60kg
            if (poidsValue > 60) {
                const paletteRadio = document.getElementById('type-palette');
                if (paletteRadio) {
                    paletteRadio.checked = true;
                    // Masquer l'option colis
                    const colisOption = document.querySelector('.radio-option:has(#type-colis)');
                    if (colisOption) {
                        colisOption.style.display = 'none';
                    }
                    togglePaletteSection();
                    
                    // Passer automatiquement à l'étape ADR si poids validé (SANS scroll)
                    if (validatePoids() && canProceedToStep(4)) {
                        showStep(4, false); // false = pas de scroll
                    }
                }
            } else {
                // Réafficher l'option colis si <= 60kg
                const colisOption = document.querySelector('.radio-option:has(#type-colis)');
                if (colisOption) {
                    colisOption.style.display = 'block';
                }
                
                // Passer à l'étape type dès la saisie (SANS scroll)
                if (canProceedToStep(3)) {
                    showStep(3, false); // false = pas de scroll
                }
            }
        }
        
        calculatePrices();
    });
    
    // Types d'envoi
    typeInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (validateType() && canProceedToStep(4)) {
                showStep(4);
                togglePaletteSection();
            }
            calculatePrices();
        });
    });
    
    // ADR
    adrInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (validateADR()) {
                calculatePrices(); // Premier calcul possible
            }
        });
    });
    
    // Options (recalcul seulement)
    optionInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (hasFirstCalculation) {
                calculatePrices();
            }
        });
    });
    
    // Enlèvement
    enlevement.addEventListener('change', () => {
        handleEnlevementChange();
        if (hasFirstCalculation) {
            calculatePrices();
        }
    });
    
    // Palettes EUR
    paletteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.palettes;
            
            if (value === 'plus') {
                paletteInfo.style.display = 'block';
                palettes.value = '';
                paletteButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            } else {
                paletteInfo.style.display = 'none';
                palettes.value = value;
                updatePaletteButtons();
                if (hasFirstCalculation) {
                    calculatePrices();
                }
            }
        });
    });
    
    // Reset
    resetBtn.addEventListener('click', () => {
        if (confirm('Voulez-vous vraiment recommencer ?')) {
            resetForm();
        }
    });
    
    function resetForm() {
        form.reset();
        currentStep = 1;
        hasFirstCalculation = false;
        formData = {};
        
        // Réinitialiser l'affichage
        showStep(1);
        bestResult.innerHTML = '<p class="invite-message">🚀 Commence par renseigner ton département de livraison</p>';
        clearErrors();
        
        // Réinitialiser les états spéciaux
        paletteSection.style.display = 'none';
        paletteInfo.style.display = 'none';
        palettes.value = '1';
        
        // Réactiver toutes les options
        optionInputs.forEach(input => {
            input.disabled = false;
            input.closest('label').style.opacity = '1';
        });
        
        // Focus sur le premier champ
        setTimeout(() => departement.focus(), 100);
    }
    
    // =============================================================================
    // GESTION DE L'HISTORIQUE (Modal)
    // =============================================================================
    
    const modal = document.getElementById('historique-modal');
    const closeBtn = document.querySelector('.close');
    
    window.showHistorique = function() {
        modal.classList.add('active');
        loadHistorique();
    };
    
    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
    });
    
    // Le gestionnaire global des clics extérieurs est maintenant plus haut dans le code
    
    function loadHistorique() {
        const content = document.getElementById('historique-content');
        content.innerHTML = '<p>Chargement...</p>';
        
        fetch('ajax-historique.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.historique.length > 0) {
                    let html = '<table class="historique-table">';
                    html += '<thead><tr>';
                    html += '<th>Date</th><th>Dép.</th><th>Poids</th><th>Type</th>';
                    html += '<th>Transporteur</th><th>Prix</th>';
                    html += '</tr></thead><tbody>';
                    
                    data.historique.forEach(entry => {
                        html += '<tr>';
                        html += `<td>${new Date(entry.date).toLocaleDateString('fr-FR')}</td>`;
                        html += `<td>${entry.departement}</td>`;
                        html += `<td>${entry.poids} kg</td>`;
                        html += `<td>${entry.type}</td>`;
                        html += `<td><strong>${entry.best_carrier}</strong></td>`;
                        html += `<td><strong>${entry.best_price.toFixed(2)} €</strong></td>`;
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<p>Aucun historique disponible</p>';
                }
            })
            .catch(error => {
                content.innerHTML = '<p style="color: red;">Erreur lors du chargement</p>';
            });
    }
    
    window.clearHistorique = function() {
        if (confirm('Voulez-vous vraiment effacer tout l\'historique ?')) {
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
        }
    };
    
    // =============================================================================
    // COMPARAISON TOUS TRANSPORTEURS
    // =============================================================================
    
    window.showAllCarriers = function() {
        if (!window.lastCalculationData) return;
        
        const data = window.lastCalculationData;
        let html = '<div class="all-carriers-modal">';
        html += '<h3>Comparaison des transporteurs</h3>';
        html += '<div class="carrier-grid">';
        
        Object.keys(data.formatted).forEach(key => {
            const carrier = data.formatted[key];
            const isBest = key === data.bestCarrier;
            const isAvailable = carrier.price !== null;
            
            html += `
                <div class="carrier-card ${isBest ? 'best' : ''} ${!isAvailable ? 'unavailable' : ''}">
                    <h4>${carrier.name} ${isBest ? '⭐' : ''}</h4>
                    <div class="carrier-price">${carrier.formatted}</div>
                    ${carrier.debug ? '<button onclick="showCarrierDetails(\'' + key + '\')">Détails</button>' : ''}
                </div>
            `;
        });
        
        html += '</div></div>';
        
        // Afficher dans un modal ou intégrer dans les résultats
        const existingModal = document.querySelector('.all-carriers-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        bestResult.insertAdjacentHTML('afterend', html);
    };
    
    // =============================================================================
    // INITIALISATION
    // =============================================================================
    
    // Initialiser l'interface
    showStep(1);
    
    // Focus initial
    setTimeout(() => {
        departement.focus();
    }, 500);
    
    // Vérifier que les modals existent
    console.log('Vérification des modals:');
    console.log('comparison-modal:', document.getElementById('comparison-modal'));
    console.log('comparison-body:', document.getElementById('comparison-body'));
    console.log('historique-modal:', document.getElementById('historique-modal'));
    
    // Gérer l'auto-complétion du navigateur
    setTimeout(() => {
        if (departement.value && poids.value) {
            // Si des valeurs sont pré-remplies, valider et avancer
            if (validateDepartement()) showStep(2);
            if (validatePoids()) showStep(3);
            // etc.
        }
    }, 1000);
    
    console.log('✅ Interface guidée initialisée');
});
