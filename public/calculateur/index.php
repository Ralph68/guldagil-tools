<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur Frais de Port - Amélioré</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        
        /* Amélioration ADR */
        .adr-options { display: flex; gap: 10px; margin-bottom: 10px; }
        .adr-option { display: flex; align-items: center; gap: 5px; padding: 10px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; }
        .adr-option:hover { border-color: #007cba; }
        .adr-option input:checked + label { background: #f0f7ff; }
        
        /* Amélioration Options */
        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 10px; }
        .option-card { padding: 10px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; text-align: center; }
        .option-card:hover { border-color: #007cba; }
        .option-card.selected { border-color: #007cba; background: #f0f7ff; }
        
        /* Amélioration Enlèvement */
        .enlevement-section { padding: 15px; background: #f8f8f8; border-radius: 4px; margin-bottom: 20px; }
        .enlevement-section.disabled { opacity: 0.6; background: #f5f5f5; }
        .checkbox-label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        
        .results { margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px; }
        .best-price { font-size: 24px; color: #28a745; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .carrier-result { display: flex; justify-content: space-between; padding: 10px; margin: 5px 0; background: white; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🚛 Calculateur de Frais de Port</h1>
    
    <form id="calc-form">
        <!-- Champs de base -->
        <div class="form-group">
            <label for="departement">Département de destination</label>
            <input type="number" id="departement" name="departement" placeholder="Ex: 75" required>
        </div>
        
        <div class="form-group">
            <label for="poids">Poids total (kg)</label>
            <input type="number" id="poids" name="poids" step="0.1" placeholder="Ex: 25.5" required>
        </div>
        
        <div class="form-group">
            <label for="type">Type d'envoi</label>
            <select id="type" name="type" required>
                <option value="">Sélectionner...</option>
                <option value="colis">Colis</option>
                <option value="palette">Palette</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="palettes">Nombre de palettes EUR</label>
            <input type="number" id="palettes" name="palettes" min="0" value="0">
        </div>
        
        <!-- Section ADR améliorée -->
        <div class="form-group">
            <label>⚠️ Marchandises dangereuses (ADR)</label>
            <div class="adr-options">
                <div class="adr-option">
                    <input type="radio" id="adr-non" name="adr" value="non" checked>
                    <label for="adr-non">❌ Non</label>
                </div>
                <div class="adr-option">
                    <input type="radio" id="adr-oui" name="adr" value="oui">
                    <label for="adr-oui">⚠️ Oui (+62€)</label>
                </div>
            </div>
        </div>
        
        <!-- Section Options mutuellement exclusives -->
        <div class="form-group">
            <label>🚀 Options de livraison (une seule sélection)</label>
            <div class="options-grid">
                <div class="option-card selected" data-option="standard">
                    <strong>Standard</strong><br>
                    <small>24-48h</small>
                </div>
                <div class="option-card" data-option="premium13">
                    <strong>Premium 13h</strong><br>
                    <small>Calculé</small>
                </div>
                <div class="option-card" data-option="premium18">
                    <strong>Premium 18h</strong><br>
                    <small>Calculé</small>
                </div>
                <div class="option-card" data-option="rdv">
                    <strong>RDV</strong><br>
                    <small>Calculé</small>
                </div>
            </div>
            <input type="hidden" id="service_livraison" name="service_livraison" value="standard">
        </div>
        
        <!-- Section Enlèvement séparée -->
        <div class="enlevement-section" id="enlevement-section">
            <div class="checkbox-label">
                <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                <span>🏭 Enlèvement sur site expéditeur</span>
            </div>
            <small id="enlevement-help">Disponible uniquement avec livraison standard</small>
        </div>
        
        <button type="submit" style="width: 100%; padding: 15px; background: #007cba; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
            🧮 Calculer les frais de port
        </button>
    </form>
    
    <div id="results" class="results" style="display: none;">
        <!-- Résultats affichés ici -->
    </div>
    
    <script>
        // Variables globales
        let selectedOption = 'standard';
        
        // Gestion des options mutuellement exclusives
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('click', () => {
                // Désélectionner toutes
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                
                // Sélectionner cliquée
                card.classList.add('selected');
                selectedOption = card.dataset.option;
                document.getElementById('service_livraison').value = selectedOption;
                
                // Gérer enlèvement
                updateEnlevementState();
                
                // Calculer si formulaire complet
                if (isFormValid()) calculateRates();
            });
        });
        
        // Gestion enlèvement = standard uniquement
        function updateEnlevementState() {
            const enlevementCheckbox = document.getElementById('enlevement');
            const enlevementSection = document.getElementById('enlevement-section');
            
            if (selectedOption === 'standard') {
                enlevementCheckbox.disabled = false;
                enlevementSection.classList.remove('disabled');
            } else {
                enlevementCheckbox.disabled = true;
                enlevementCheckbox.checked = false;
                enlevementSection.classList.add('disabled');
            }
        }
        
        // Calcul automatique lors des changements
        ['departement', 'poids', 'type', 'palettes'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            field.addEventListener('input', () => {
                if (isFormValid()) calculateRates();
            });
            field.addEventListener('change', () => {
                if (isFormValid()) calculateRates();
            });
        });
        
        document.querySelectorAll('input[name="adr"]').forEach(radio => {
            radio.addEventListener('change', () => {
                if (isFormValid()) calculateRates();
            });
        });
        
        document.getElementById('enlevement').addEventListener('change', () => {
            if (isFormValid()) calculateRates();
        });
        
        // Validation formulaire
        function isFormValid() {
            const dept = document.getElementById('departement').value;
            const poids = document.getElementById('poids').value;
            const type = document.getElementById('type').value;
            
            return dept && poids > 0 && type;
        }
        
        // Calcul des tarifs
        async function calculateRates() {
            const formData = new FormData(document.getElementById('calc-form'));
            
            try {
                const response = await fetch('ajax-calculate.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                displayResults(result);
                
            } catch (error) {
                console.error('Erreur calcul:', error);
                document.getElementById('results').innerHTML = 
                    '<div style="color: red;">❌ Erreur de calcul. Veuillez réessayer.</div>';
                document.getElementById('results').style.display = 'block';
            }
        }
        
        // Affichage des résultats
        function displayResults(result) {
            const resultsDiv = document.getElementById('results');
            
            if (result.success && result.best_rate) {
                let html = `<div class="best-price">🏆 Meilleur tarif : ${result.best_rate.formatted}</div>`;
                
                html += '<h3>📊 Comparaison des transporteurs</h3>';
                
                Object.entries(result.carriers).forEach(([carrier, data]) => {
                    const isBest = carrier === result.best_rate.carrier;
                    const style = isBest ? 'style="border-left: 4px solid #28a745; background: #f8fff8;"' : '';
                    
                    html += `
                        <div class="carrier-result" ${style}>
                            <span>${isBest ? '🏆 ' : ''}${data.name}</span>
                            <span><strong>${data.formatted}</strong></span>
                        </div>
                    `;
                });
                
                resultsDiv.innerHTML = html;
            } else {
                resultsDiv.innerHTML = `<div style="color: red;">❌ ${result.message || 'Aucun tarif disponible'}</div>`;
            }
            
            resultsDiv.style.display = 'block';
        }
        
        // Soumission formulaire
        document.getElementById('calc-form').addEventListener('submit', (e) => {
            e.preventDefault();
            calculateRates();
        });
        
        // Initialisation
        updateEnlevementState();
    </script>
</body>
</html>
