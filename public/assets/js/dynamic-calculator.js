// dynamic-calculator.js
document.addEventListener('DOMContentLoaded', () => {
    // Éléments du formulaire
    const form = document.getElementById('calc-form');
    const departement = document.getElementById('departement');
    const poids = document.getElementById('poids');
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const adrInputs = document.querySelectorAll('input[name="adr"]');
    const optionInputs = document.querySelectorAll('input[name="option_sup"]');
    const enlevement = document.getElementById('enlevement');
    const palettes = document.getElementById('palettes');
    const resetBtn = document.getElementById('reset-btn');
    
    // Éléments de résultat
    const loading = document.getElementById('loading');
    const resultContent = document.getElementById('result-content');
    const bestResult = document.getElementById('best-result');
    const errorContainer = document.getElementById('error-container');
    
    // Délai pour éviter trop de requêtes
    let calculateTimeout = null;
    // pour les seuils et poids renvoyés par l’API
    let thresholds = [];
    let inputPoids = 0;

    
    // Fonction de calcul dynamique
    function calculatePrices() {
        // Annuler la requête précédente
        if (calculateTimeout) {
            clearTimeout(calculateTimeout);
        }
        
        // Attendre un peu avant de lancer le calcul (debounce)
        calculateTimeout = setTimeout(() => {
            // Récupérer les valeurs
            const formData = new FormData();
            formData.append('departement', departement.value);
            formData.append('poids', poids.value);
            
            const selectedType = document.querySelector('input[name="type"]:checked');
            if (selectedType) formData.append('type', selectedType.value);
            
            const selectedAdr = document.querySelector('input[name="adr"]:checked');
            if (selectedAdr) formData.append('adr', selectedAdr.value);
            
            const selectedOption = document.querySelector('input[name="option_sup"]:checked');
            if (selectedOption) formData.append('option_sup', selectedOption.value);
            
            formData.append('enlevement', enlevement.checked ? '1' : '0');
            formData.append('palettes', palettes.value);
            
            // Vérifier si on a les champs requis
            if (!departement.value || !poids.value || !selectedType || !selectedAdr) {
                bestResult.innerHTML = '<p><em>Remplissez tous les champs obligatoires</em></p>';
                return;
            }
            
            // Afficher le chargement
            loading.classList.add('active');
            resultContent.classList.add('loading');
            
            // Faire la requête AJAX
            fetch('ajax-calculate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.classList.remove('active');
                resultContent.classList.remove('loading');
                
                // 1) Affrètement si besoin
                if (data.affretement) {
                    displayAffretement(data.message);
                // 2) Succès et meilleur transporteur
                } 
                else if (data.success && data.bestCarrier) {
                    // stocker thresholds + poids pour les alertes
                    thresholds = data.thresholds;
                    inputPoids = data.poids;

                     // fallback palette si on est en “colis”
                const typeValue = document.querySelector('input[name="type"]:checked').value;
                if (typeValue === 'colis') {
                    const palForm = new FormData();
                    palForm.append('departement', departement.value);
                    palForm.append('poids',      inputPoids);
                    palForm.append('type',       'palette');
                    palForm.append('adr',        document.querySelector('input[name="adr"]:checked').value);
                    const selOpt = document.querySelector('input[name="option_sup"]:checked');
                    if (selOpt) palForm.append('option_sup', selOpt.value);
                    palForm.append('enlevement', enlevement.checked ? '1' : '0');
                    palForm.append('palettes',   palettes.value);
        
                    fetch('ajax-calculate.php', { method: 'POST', body: palForm })
                        .then(res => res.json())
                        .then(palData => renderResultsWithFallback(data, palData));
                } else {
                    displayResults(data);
                }
                // 3) Erreurs retournées
                } 
                else if (data.errors && data.errors.length > 0) {
                    displayErrors(data.errors);
                } 
                // 4) Aucun tarif dispo
                else {
                    bestResult.innerHTML = '<p><em>Aucun tarif disponible pour ces critères</em></p>';
                }
            })
            .catch(error => {
                loading.classList.remove('active');
                resultContent.classList.remove('loading');
                console.error('Erreur:', error);
                bestResult.innerHTML = '<p style="color: red;">Erreur lors du calcul</p>';
            });
        }, 300);
    }
    
    // Afficher message d'affrètement
    function displayAffretement(message) {
        bestResult.innerHTML = `
            <div class="affrètement-message">
                <h3>🚛 Affrètement nécessaire</h3>
                <p>${message}</p>
                <p><strong>☎️ Service achat : 03 89 63 42 42</strong></p>
            </div>
        `;
    }
    
    // Afficher les résultats
    function displayResults(data) {
        const bestCarrier = data.formatted[data.bestCarrier];
        let html = `
            <p><strong>${bestCarrier.name}</strong> : <span style="font-size: 1.3em; color: #4CAF50;">${bestCarrier.formatted}</span></p>
        `;
    // alertes de seuils sous le meilleur tarif
    thresholds.forEach(t => {
        if (inputPoids >= t * 0.8 && inputPoids < t) {
            const unitRate       = data.results[data.bestCarrier] / inputPoids;
            const thresholdPrice = unitRate * t;
            if (thresholdPrice < data.results[data.bestCarrier]) {
                html += `<p class="alert">Payant pour → déclarer ${t} kg</p>`;
            }
        }
    });
        
        // Détails du calcul
        if (bestCarrier.debug) {
            html += `
                <a class="toggle-link" onclick="toggleDetails('calc-details')">📊 Voir le détail du calcul</a>
                <div class="details-box" id="calc-details">
                    ${formatCalculationDetails(bestCarrier.debug)}
                </div>
            `;
        }
        
        // Lien vers tous les transporteurs
        html += `
            <a class="toggle-link" onclick="toggleDetails('all-carriers')">🚚 Comparer tous les transporteurs</a>
            <div class="all-carriers" id="all-carriers">
                <h3>Comparaison des tarifs</h3>
                <div class="carrier-grid">
                    ${formatAllCarriers(data.formatted, data.bestCarrier)}
                </div>
            </div>
        `;
        
        // Frais supplémentaires
        html += `
            <a class="toggle-link" onclick="toggleDetails('frais-sup')">💶 Frais supplémentaires possibles</a>
            <div class="details-box" id="frais-sup">
                <table class="details-table">
                    <tr>
                        <td>Représentation en douane</td>
                        <td>Selon CGV du transporteur (~15€)</td>
                    </tr>
                    <tr>
                        <td>Gardiennage</td>
                        <td>25€/jour si livraison impossible</td>
                    </tr>
                    <tr>
                        <td>Contre-remboursement</td>
                        <td>Minimum 15€ ou % du montant</td>
                    </tr>
                </table>
            </div>
        `;
        
        bestResult.innerHTML = html;
        errorContainer.innerHTML = '';
    }
// ─────────────────────────────────────────────────────────────────────
// Si colis, on compare avec le tarif palette XPO/K+N et on override si besoin
function renderResultsWithFallback(data, palData) {
    const colisHeppner = data.results['heppner'];
    let override = null;

    ['xpo', 'kn'].forEach(carrier => {
        const palPrice = palData.results[carrier];
        if (palPrice !== null && (colisHeppner === null || palPrice < colisHeppner)) {
            override = { carrier, price: palPrice };
        }
    });

    if (override) {
        data.bestCarrier = override.carrier;
        data.best         = override.price;
        data.overridePalette = true;
        data.formatted[override.carrier].price = override.price;
        data.formatted[override.carrier].formatted =
            override.price.toFixed(2).replace('.', ',') + ' €';
    }

    // réaffiche avec éventuel override
    displayResults(data);

    // message “remise en palette”
    if (data.overridePalette) {
        const msg = `<p class="alert">
                        Remise en palette disponible 
                        (${data.formatted[data.bestCarrier].name})
                     </p>`;
        bestResult.insertAdjacentHTML('beforeend', msg);
    }
}
// ─────────────────────────────────────────────────────────────────────

    
    // Formater les détails du calcul
    function formatCalculationDetails(debug) {
        let html = '<table class="details-table">';
        html += `<tr><td>Tarif de base (${debug.poids}kg)</td><td>${debug.tarif_base}€</td></tr>`;
        
        if (debug.majoration_palette) {
            html += `<tr><td>Majoration palette</td><td>+${debug.majoration_palette}</td></tr>`;
        }
        
        if (debug.majoration_adr) {
            html += `<tr><td>Majoration ADR</td><td>+${debug.majoration_adr}</td></tr>`;
        }
        
        // Options
        Object.keys(debug).forEach(key => {
            if (key.startsWith('option_') && debug[key]) {
                const optionName = key.replace('option_', '').replace('_', ' ');
                html += `<tr><td>Option ${optionName}</td><td>+${debug[key]}</td></tr>`;
            }
        });
        
        if (debug.surcharge_gasoil) {
            html += `<tr><td>Surcharge carburant</td><td>+${debug.surcharge_gasoil}</td></tr>`;
        }
        
        if (debug.autres_taxes) {
            html += `<tr><td>Autres taxes</td><td>+${debug.autres_taxes}</td></tr>`;
        }
        
        html += `<tr style="font-weight: bold; border-top: 2px solid #333;">
                    <td>TOTAL</td><td>${debug.tarif_final}€</td>
                 </tr>`;
        html += '</table>';
        
        return html;
    }
    
    // Formater tous les transporteurs
    function formatAllCarriers(carriers, bestCarrier) {
        let html = '';
        
        Object.keys(carriers).forEach(key => {
            const carrier = carriers[key];
            const isBest = key === bestCarrier;
            const isAvailable = carrier.price !== null;
            
            html += `
                <div class="carrier-card ${isBest ? 'best' : ''} ${!isAvailable ? 'unavailable' : ''}">
                    <h4>${carrier.name} ${isBest ? '⭐' : ''}</h4>
                    <div class="carrier-price">${carrier.formatted}</div>
                </div>
            `;
        });
        
        return html;
    }
    
    // Afficher les erreurs
    function displayErrors(errors) {
        let html = '<div class="error"><ul>';
        errors.forEach(error => {
            html += `<li>${error}</li>`;
        });
        html += '</ul></div>';
        errorContainer.innerHTML = html;
        bestResult.innerHTML = '<p><em>Corrigez les erreurs pour voir les tarifs</em></p>';
    }
    
    // Toggle affichage des détails
    window.toggleDetails = function(id) {
        const element = document.getElementById(id);
        if (element) {
            element.classList.toggle('active');
        }
    };
    
    // Gestion de l'option enlèvement
    function handleEnlevement() {
        const isChecked = enlevement.checked;
        optionInputs.forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (isChecked) {
                input.disabled = true;
                if (input.value !== 'standard') {
                    input.checked = false;
                } else {
                    input.checked = true;
                }
                label.classList.add('disabled-option');
            } else {
                input.disabled = false;
                label.classList.remove('disabled-option');
            }
        });
        calculatePrices();
    }
    
    // Réinitialiser le formulaire
    function resetForm() {
        form.reset();
        // Réinitialiser l'affichage
        bestResult.innerHTML = '<p><em>Remplissez le formulaire pour voir les tarifs</em></p>';
        errorContainer.innerHTML = '';
        // Réinitialiser les options
        optionInputs.forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            input.disabled = false;
            label.classList.remove('disabled-option');
        });
    }
    
    // Auto-focus après 2 chiffres dans département
    departement.addEventListener('input', () => {
        if (departement.value.length === 2 && /^\d{2}$/.test(departement.value)) {
            poids.focus();
        }
        calculatePrices();
    });
    
    // Event listeners
    departement.addEventListener('focus', () => departement.select());
    poids.addEventListener('input', calculatePrices);
    palettes.addEventListener('input', calculatePrices);
    enlevement.addEventListener('change', handleEnlevement);
    
    // Radio buttons
    typeInputs.forEach(input => input.addEventListener('change', calculatePrices));
    adrInputs.forEach(input => input.addEventListener('change', calculatePrices));
    optionInputs.forEach(input => input.addEventListener('change', calculatePrices));
    
    // Bouton reset
    resetBtn.addEventListener('click', resetForm);
    
    // Gestion des boutons palettes
    const paletteButtons = document.querySelectorAll('.palette-btn');
    const paletteInfo = document.getElementById('palette-info');
    
    paletteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.palettes;
            
            if (value === 'plus') {
                // Afficher le message d'affrètement
                paletteInfo.style.display = 'block';
                palettes.value = '';
                // Désactiver tous les boutons
                paletteButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            } else {
                // Sélectionner le nombre de palettes
                palettes.value = value;
                paletteInfo.style.display = 'none';
                // Mettre à jour l'état actif
                paletteButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                calculatePrices();
            }
        });
    });
    
    // Gestion du modal historique
    const modal = document.getElementById('historique-modal');
    const closeBtn = document.querySelector('.close');
    
    window.showHistorique = function() {
        modal.classList.add('active');
        loadHistorique();
    };
    
    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
    
    // Charger l'historique
    function loadHistorique() {
        const content = document.getElementById('historique-content');
        content.innerHTML = '<p>Chargement...</p>';
        
        fetch('ajax-historique.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.historique.length > 0) {
                    let html = '<table class="historique-table">';
                    html += '<thead><tr>';
                    html += '<th>Date/Heure</th>';
                    html += '<th>Dép.</th>';
                    html += '<th>Poids</th>';
                    html += '<th>Type</th>';
                    html += '<th>ADR</th>';
                    html += '<th>Option</th>';
                    html += '<th>Transporteur</th>';
                    html += '<th>Prix</th>';
                    html += '</tr></thead><tbody>';
                    
                    data.historique.forEach(entry => {
                        html += '<tr>';
                        html += `<td>${new Date(entry.date).toLocaleString('fr-FR')}</td>`;
                        html += `<td>${entry.departement}</td>`;
                        html += `<td>${entry.poids} kg</td>`;
                        html += `<td>${entry.type}</td>`;
                        html += `<td>${entry.adr}</td>`;
                        html += `<td>${entry.option}</td>`;
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
    
    // Effacer l'historique
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
    
    // Calcul initial si des valeurs sont présentes
    if (departement.value && poids.value) {
        calculatePrices();
    }
});
