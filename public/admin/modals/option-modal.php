<?php
// public/admin/modals/option-modal.php
// Modal pour la création et édition d'options supplémentaires

// Récupérer les transporteurs disponibles
$carriers = [
    'heppner' => 'Heppner',
    'xpo' => 'XPO',
    'kn' => 'Kuehne + Nagel'
];

// Options prédéfinies courantes
$predefinedOptions = [
    'rdv' => 'Prise de RDV',
    'premium13' => 'Premium avant 13h',
    'premium18' => 'Premium avant 18h',
    'datefixe' => 'Date fixe',
    'enlevement' => 'Enlèvement sur site',
    'palette' => 'Frais par palette EUR',
    'assurance' => 'Assurance renforcée',
    'livraison_etage' => 'Livraison étage',
    'retour_document' => 'Retour de documents',
    'contre_remboursement' => 'Contre-remboursement',
    'gardiennage' => 'Gardiennage',
    'manutention' => 'Manutention spéciale'
];
?>

<!-- Modal d'édition/création d'option -->
<div id="option-modal" class="modal" style="display: none;">
    <div class="modal-content option-modal-content">
        <div class="modal-header">
            <h3 id="option-modal-title">➕ Nouvelle option supplémentaire</h3>
            <button class="modal-close" onclick="closeOptionModal()" aria-label="Fermer">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Indicateur de sauvegarde -->
            <div id="option-save-indicator" class="save-indicator" style="display: none;">
                <div class="save-spinner"></div>
                <span>Sauvegarde en cours...</span>
            </div>
            
            <form id="option-form" autocomplete="off">
                <!-- Section Informations générales -->
                <div class="form-section">
                    <h4>📋 Informations générales</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="option-transporteur">
                                Transporteur
                                <span class="required">*</span>
                            </label>
                            <select id="option-transporteur" name="transporteur" class="form-control" required>
                                <option value="">Sélectionner un transporteur...</option>
                                <?php foreach ($carriers as $code => $name): ?>
                                    <option value="<?= $code ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-help">
                                <small>Transporteur auquel cette option s'applique</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="option-code">
                                Code de l'option
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-suggestions">
                                <input type="text" 
                                       id="option-code" 
                                       name="code_option" 
                                       class="form-control"
                                       placeholder="Ex: rdv, premium13..."
                                       maxlength="50"
                                       pattern="[a-z0-9_]+"
                                       title="Utiliser seulement des lettres minuscules, chiffres et underscore"
                                       required>
                                <div class="suggestions-dropdown" id="code-suggestions" style="display: none;">
                                    <?php foreach ($predefinedOptions as $code => $label): ?>
                                        <div class="suggestion-item" data-code="<?= $code ?>" data-label="<?= $label ?>">
                                            <strong><?= $code ?></strong> - <?= $label ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="field-help">
                                <small>Identifiant unique (lettres minuscules, chiffres, underscore)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="option-libelle">
                            Libellé de l'option
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="option-libelle" 
                               name="libelle" 
                               class="form-control"
                               placeholder="Ex: Prise de rendez-vous avec le destinataire"
                               maxlength="255"
                               required>
                        <div class="field-help">
                            <small>Nom affiché à l'utilisateur final</small>
                        </div>
                    </div>
                </div>

                <!-- Section Tarification -->
                <div class="form-section">
                    <h4>💰 Configuration tarifaire</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="option-montant">
                                Montant
                                <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       id="option-montant" 
                                       name="montant" 
                                       class="form-control"
                                       step="0.01" 
                                       min="0" 
                                       max="999.99"
                                       placeholder="0.00"
                                       required>
                                <span class="input-suffix">€</span>
                            </div>
                            <div class="field-help">
                                <small>Coût de l'option (utiliser 0 pour gratuit)</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="option-unite">
                                Unité de facturation
                                <span class="required">*</span>
                            </label>
                            <select id="option-unite" name="unite" class="form-control" required>
                                <option value="">Choisir l'unité...</option>
                                <option value="forfait">Forfait (montant fixe)</option>
                                <option value="palette">Par palette</option>
                                <option value="pourcentage">Pourcentage du tarif de base</option>
                                <option value="poids">Par kg</option>
                            </select>
                            <div class="field-help">
                                <small>Comment le montant est appliqué</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Explication dynamique du calcul -->
                    <div class="tarification-explanation" id="tarification-explanation">
                        <div class="explanation-content">
                            <h5>💡 Comment sera calculée cette option :</h5>
                            <p id="calculation-explanation">Sélectionnez une unité pour voir l'explication du calcul</p>
                            
                            <div class="calculation-examples" id="calculation-examples" style="display: none;">
                                <h6>Exemples de calcul :</h6>
                                <div class="examples-list" id="examples-list">
                                    <!-- Exemples générés dynamiquement -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Conditions d'application -->
                <div class="form-section">
                    <h4>⚙️ Conditions d'application</h4>
                    
                    <div class="form-group">
                        <label for="option-actif">
                            <input type="checkbox" id="option-actif" name="actif" checked>
                            Option active
                        </label>
                        <div class="field-help">
                            <small>Décochez pour désactiver temporairement cette option</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="option-conditions">Conditions particulières (optionnel)</label>
                        <textarea id="option-conditions" 
                                  name="conditions" 
                                  class="form-control"
                                  rows="3"
                                  placeholder="Ex: Disponible uniquement pour les envois de plus de 50kg, non compatible avec l'option premium..."
                                  maxlength="500"></textarea>
                        <div class="field-help">
                            <small>Restrictions ou conditions spéciales (500 caractères max)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="option-ordre">Ordre d'affichage</label>
                        <input type="number" 
                               id="option-ordre" 
                               name="ordre" 
                               class="form-control"
                               min="1" 
                               max="100"
                               value="10"
                               placeholder="10">
                        <div class="field-help">
                            <small>Position dans la liste (1 = en premier, 100 = en dernier)</small>
                        </div>
                    </div>
                </div>

                <!-- Section Aperçu -->
                <div class="form-section">
                    <h4>👁️ Aperçu de l'option</h4>
                    
                    <div class="option-preview" id="option-preview">
                        <div class="preview-card">
                            <div class="preview-header">
                                <span class="preview-transporteur" id="preview-transporteur">Transporteur</span>
                                <span class="preview-status" id="preview-status">
                                    <span class="badge badge-success">Actif</span>
                                </span>
                            </div>
                            <div class="preview-content">
                                <h5 class="preview-libelle" id="preview-libelle">Libellé de l'option</h5>
                                <div class="preview-code" id="preview-code">code_option</div>
                                <div class="preview-tarification" id="preview-tarification">
                                    <span class="preview-montant" id="preview-montant">0.00 €</span>
                                    <span class="preview-unite" id="preview-unite">forfait</span>
                                </div>
                            </div>
                            <div class="preview-footer">
                                <small class="preview-conditions" id="preview-conditions"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Champs cachés -->
                <input type="hidden" id="option-id" name="id">
            </form>
        </div>
        
        <div class="modal-footer">
            <div class="footer-info">
                <small class="text-muted">
                    💾 Les modifications sont sauvegardées automatiquement
                </small>
            </div>
            <div class="footer-actions">
                <button type="button" class="btn btn-secondary" onclick="closeOptionModal()">
                    <span>❌</span> Annuler
                </button>
                <button type="button" class="btn btn-warning" onclick="resetOptionForm()" id="reset-option-btn" style="display: none;">
                    <span>🔄</span> Réinitialiser
                </button>
                <button type="button" class="btn btn-info" onclick="testOption()" id="test-option-btn">
                    <span>🧪</span> Tester
                </button>
                <button type="button" class="btn btn-primary" onclick="saveOption()" id="save-option-btn">
                    <span>💾</span> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la modal d'options */
.option-modal-content {
    max-width: 800px;
    width: 90%;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

.form-section h4 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-with-suggestions {
    position: relative;
}

.suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.suggestion-item {
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.suggestion-item:hover {
    background: #f0f8ff;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.input-group {
    display: flex;
    align-items: center;
}

.input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-suffix {
    background: #e9ecef;
    border: 2px solid var(--border-color);
    border-left: none;
    border-radius: 0 6px 6px 0;
    padding: 0.75rem;
    font-weight: 600;
    color: #495057;
}

.tarification-explanation {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
}

.explanation-content h5 {
    margin: 0 0 0.5rem 0;
    color: #1565c0;
    font-size: 0.95rem;
}

.explanation-content p {
    margin: 0;
    color: #1976d2;
    font-size: 0.9rem;
}

.calculation-examples {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #bbdefb;
}

.calculation-examples h6 {
    margin: 0 0 0.5rem 0;
    color: #1565c0;
    font-size: 0.85rem;
}

.examples-list {
    font-size: 0.8rem;
    color: #1976d2;
}

.example-item {
    padding: 0.25rem 0;
    display: flex;
    justify-content: space-between;
}

.option-preview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 1rem;
}

.preview-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.preview-transporteur {
    font-weight: 600;
    color: var(--primary-color);
}

.preview-content {
    padding: 1rem;
}

.preview-libelle {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1rem;
}

.preview-code {
    font-family: 'Courier New', monospace;
    background: #f0f0f0;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    color: #666;
    display: inline-block;
    margin-bottom: 0.75rem;
}

.preview-tarification {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.preview-montant {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--success-color);
}

.preview-unite {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.preview-footer {
    padding: 0.75rem 1rem;
    background: #f9f9f9;
    border-top: 1px solid #e0e0e0;
}

.preview-conditions {
    color: #666;
    font-style: italic;
}

.required {
    color: var(--error-color);
    font-weight: bold;
}

.field-help small {
    color: #666;
    font-style: italic;
}

.save-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 10;
}

.save-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .option-modal-content {
        width: 95%;
        margin: 2.5% auto;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .footer-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .footer-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// JavaScript pour la gestion de la modal d'options
document.addEventListener('DOMContentLoaded', function() {
    initializeOptionModal();
});

function initializeOptionModal() {
    console.log('🔧 Initialisation modal options');
    
    // Gestionnaires d'événements pour les suggestions de code
    const codeInput = document.getElementById('option-code');
    const suggestionsDropdown = document.getElementById('code-suggestions');
    
    if (codeInput && suggestionsDropdown) {
        codeInput.addEventListener('input', handleCodeInput);
        codeInput.addEventListener('focus', showCodeSuggestions);
        codeInput.addEventListener('blur', hideCodeSuggestions);
        
        // Gestionnaires pour les suggestions
        suggestionsDropdown.addEventListener('mousedown', function(e) {
            e.preventDefault(); // Empêcher la perte de focus
        });
        
        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                selectSuggestion(this.dataset.code, this.dataset.label);
            });
        });
    }
    
    // Gestionnaires pour la mise à jour de l'aperçu
    const formInputs = document.querySelectorAll('#option-form input, #option-form select, #option-form textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', updateOptionPreview);
        input.addEventListener('change', updateOptionPreview);
    });
    
    // Gestionnaire pour l'explication du calcul
    const uniteSelect = document.getElementById('option-unite');
    if (uniteSelect) {
        uniteSelect.addEventListener('change', updateCalculationExplanation);
    }
    
    console.log('✅ Modal options initialisée');
}

function handleCodeInput() {
    const input = document.getElementById('option-code');
    const value = input.value.toLowerCase();
    
    // Filtrer les suggestions
    filterSuggestions(value);
    
    if (value.length > 0) {
        showCodeSuggestions();
    } else {
        hideCodeSuggestions();
    }
    
    updateOptionPreview();
}

function filterSuggestions(searchTerm) {
    const suggestions = document.querySelectorAll('.suggestion-item');
    let hasVisibleSuggestion = false;
    
    suggestions.forEach(suggestion => {
        const code = suggestion.dataset.code;
        const label = suggestion.dataset.label.toLowerCase();
        
        if (code.includes(searchTerm) || label.includes(searchTerm)) {
            suggestion.style.display = 'block';
            hasVisibleSuggestion = true;
        } else {
            suggestion.style.display = 'none';
        }
    });
    
    const dropdown = document.getElementById('code-suggestions');
    if (hasVisibleSuggestion && searchTerm.length > 0) {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

function showCodeSuggestions() {
    const dropdown = document.getElementById('code-suggestions');
    dropdown.style.display = 'block';
}

function hideCodeSuggestions() {
    setTimeout(() => {
        const dropdown = document.getElementById('code-suggestions');
        dropdown.style.display = 'none';
    }, 200);
}

function selectSuggestion(code, label) {
    document.getElementById('option-code').value = code;
    document.getElementById('option-libelle').value = label;
    
    hideCodeSuggestions();
    updateOptionPreview();
    
    // Focus sur le champ suivant
    document.getElementById('option-montant').focus();
}

function updateCalculationExplanation() {
    const unite = document.getElementById('option-unite').value;
    const montant = document.getElementById('option-montant').value || '0';
    const explanationEl = document.getElementById('calculation-explanation');
    const examplesEl = document.getElementById('calculation-examples');
    const examplesListEl = document.getElementById('examples-list');
    
    let explanation = '';
    let examples = [];
    
    switch (unite) {
        case 'forfait':
            explanation = `Un montant fixe de ${montant}€ sera ajouté au tarif de base, quel que soit le poids ou le nombre de palettes.`;
            examples = [
                { case: 'Envoi 50kg', calculation: `Tarif base: 25.00€ + Option: ${montant}€ = ${(25 + parseFloat(montant || 0)).toFixed(2)}€` },
                { case: 'Envoi 200kg', calculation: `Tarif base: 45.00€ + Option: ${montant}€ = ${(45 + parseFloat(montant || 0)).toFixed(2)}€` }
            ];
            break;
            
        case 'palette':
            explanation = `${montant}€ sera multiplié par le nombre de palettes de l'envoi.`;
            examples = [
                { case: '1 palette', calculation: `${montant}€ × 1 = ${montant}€` },
                { case: '3 palettes', calculation: `${montant}€ × 3 = ${(parseFloat(montant || 0) * 3).toFixed(2)}€` }
            ];
            break;
            
        case 'pourcentage':
            explanation = `${montant}% du tarif de base sera ajouté comme surcharge.`;
            examples = [
                { case: 'Tarif base 30€', calculation: `30.00€ × ${montant}% = ${(30 * (parseFloat(montant || 0) / 100)).toFixed(2)}€` },
                { case: 'Tarif base 50€', calculation: `50.00€ × ${montant}% = ${(50 * (parseFloat(montant || 0) / 100)).toFixed(2)}€` }
            ];
            break;
            
        case 'poids':
            explanation = `${montant}€ sera multiplié par le poids total de l'envoi en kg.`;
            examples = [
                { case: 'Envoi 25kg', calculation: `${montant}€ × 25kg = ${(parseFloat(montant || 0) * 25).toFixed(2)}€` },
                { case: 'Envoi 100kg', calculation: `${montant}€ × 100kg = ${(parseFloat(montant || 0) * 100).toFixed(2)}€` }
            ];
            break;
            
        default:
            explanation = 'Sélectionnez une unité pour voir l\'explication du calcul';
            examples = [];
    }
    
    explanationEl.textContent = explanation;
    
    if (examples.length > 0) {
        examplesListEl.innerHTML = examples.map(ex => 
            `<div class="example-item">
                <span>${ex.case}:</span>
                <strong>${ex.calculation}</strong>
            </div>`
        ).join('');
        examplesEl.style.display = 'block';
    } else {
        examplesEl.style.display = 'none';
    }
}

function updateOptionPreview() {
    const transporteur = document.getElementById('option-transporteur').value;
    const code = document.getElementById('option-code').value;
    const libelle = document.getElementById('option-libelle').value;
    const montant = document.getElementById('option-montant').value || '0';
    const unite = document.getElementById('option-unite').value;
    const actif = document.getElementById('option-actif').checked;
    const conditions = document.getElementById('option-conditions').value;
    
    // Noms des transporteurs
    const transporteurNames = {
        'heppner': 'Heppner',
        'xpo': 'XPO',
        'kn': 'Kuehne + Nagel'
    };
    
    // Noms des unités
    const uniteNames = {
        'forfait': 'forfait',
        'palette': 'par palette',
        'pourcentage': '% du tarif',
        'poids': 'par kg'
    };
    
    // Mettre à jour l'aperçu
    document.getElementById('preview-transporteur').textContent = transporteurNames[transporteur] || 'Transporteur';
    document.getElementById('preview-libelle').textContent = libelle || 'Libellé de l\'option';
    document.getElementById('preview-code').textContent = code || 'code_option';
    document.getElementById('preview-montant').textContent = parseFloat(montant).toFixed(2) + ' €';
    document.getElementById('preview-unite').textContent = uniteNames[unite] || 'unité';
    document.getElementById('preview-conditions').textContent = conditions || '';
    
    // Mettre à jour le statut
    const statusBadge = document.getElementById('preview-status');
    statusBadge.innerHTML = actif ? 
        '<span class="badge badge-success">Actif</span>' : 
        '<span class="badge badge-warning">Inactif</span>';
}

function resetOptionForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
        document.getElementById('option-form').reset();
        document.getElementById('option-id').value = '';
        
        // Réinitialiser l'aperçu
        updateOptionPreview();
        updateCalculationExplanation();
        
        // Cacher le bouton reset
        document.getElementById('reset-option-btn').style.display = 'none';
        
        showAlert('info', 'Formulaire réinitialisé');
    }
}

function testOption() {
    const formData = getOptionFormData();
    
    if (!formData.transporteur || !formData.code_option || !formData.libelle) {
        showAlert('warning', 'Veuillez remplir les champs obligatoires avant de tester');
        return;
    }
    
    // Simuler un test de calcul
    const testCases = [
        { poids: 25, palettes: 1, tarifBase: 30 },
        { poids: 100, palettes: 2, tarifBase: 50 },
        { poids: 500, palettes: 1, tarifBase: 80 }
    ];
    
    let testResults = 'Résultats du test :\n\n';
    
    testCases.forEach((testCase, index) => {
        let optionCost = 0;
        
        switch (formData.unite) {
            case 'forfait':
                optionCost = parseFloat(formData.montant);
                break;
            case 'palette':
                optionCost = parseFloat(formData.montant) * testCase.palettes;
                break;
            case 'pourcentage':
                optionCost = testCase.tarifBase * (parseFloat(formData.montant) / 100);
                break;
            case 'poids':
                optionCost = parseFloat(formData.montant) * testCase.poids;
                break;
        }
        
        testResults += `Test ${index + 1}: ${testCase.poids}kg, ${testCase.palettes} palette(s)\n`;
        testResults += `Tarif base: ${testCase.tarifBase.toFixed(2)}€ + Option: ${optionCost.toFixed(2)}€ = ${(testCase.tarifBase + optionCost).toFixed(2)}€\n\n`;
    });
    
    alert(testResults);
}

function saveOption() {
    const formData = getOptionFormData();
    
    // Validation
    if (!formData.transporteur || !formData.code_option || !formData.libelle) {
        showAlert('error', 'Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    if (isNaN(formData.montant) || formData.montant < 0) {
        showAlert('error', 'Le montant doit être un nombre positif');
        return;
    }
    
    // Afficher l'indicateur de sauvegarde
    document.getElementById('option-save-indicator').style.display = 'flex';
    
    // Simuler la sauvegarde (remplacer par un appel AJAX réel)
    setTimeout(() => {
        document.getElementById('option-save-indicator').style.display = 'none';
        showAlert('success', 'Option sauvegardée avec succès');
        closeOptionModal();
        
        // Recharger la liste des options si elle existe
        if (typeof loadOptions === 'function') {
            loadOptions();
        }
    }, 1500);
    
    console.log('💾 Sauvegarde option:', formData);
}

function getOptionFormData() {
    return {
        id: document.getElementById('option-id').value,
        transporteur: document.getElementById('option-transporteur').value,
        code_option: document.getElementById('option-code').value,
        libelle: document.getElementById('option-libelle').value,
        montant: parseFloat(document.getElementById('option-montant').value || 0),
        unite: document.getElementById('option-unite').value,
        actif: document.getElementById('option-actif').checked ? 1 : 0,
        conditions: document.getElementById('option-conditions').value,
        ordre: parseInt(document.getElementById('option-ordre').value || 10)
    };
}

function closeOptionModal() {
    const modal = document.getElementById('option-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    
    // Réinitialiser le formulaire
    document.getElementById('option-form').reset();
    document.getElementById('option-id').value = '';
    document.getElementById('reset-option-btn').style.display = 'none';
    
    hideCodeSuggestions();
}

function openCreateOptionModal() {
    document.getElementById('option-modal-title').textContent = '➕ Nouvelle option supplémentaire';
    document.getElementById('option-form').reset();
    document.getElementById('option-id').value = '';
    document.getElementById('reset-option-btn').style.display = 'none';
    
    updateOptionPreview();
    updateCalculationExplanation();
    
    const modal = document.getElementById('option-modal');
    modal.style.display = 'flex';
    modal.classList.add('active');
    
    // Focus sur le premier champ
    setTimeout(() => {
        document.getElementById('option-transporteur').focus();
    }, 100);
}

function openEditOptionModal(optionData) {
    document.getElementById('option-modal-title').textContent = '✏️ Modifier l\'option';
    
    // Remplir le formulaire avec les données existantes
    document.getElementById('option-id').value = optionData.id || '';
    document.getElementById('option-transporteur').value = optionData.transporteur || '';
    document.getElementById('option-code').value = optionData.code_option || '';
    document.getElementById('option-libelle').value = optionData.libelle || '';
    document.getElementById('option-montant').value = optionData.montant || '';
    document.getElementById('option-unite').value = optionData.unite || '';
    document.getElementById('option-actif').checked = optionData.actif === 1 || optionData.actif === true;
    document.getElementById('option-conditions').value = optionData.conditions || '';
    document.getElementById('option-ordre').value = optionData.ordre || 10;
    
    // Afficher le bouton reset
    document.getElementById('reset-option-btn').style.display = 'inline-flex';
    
    updateOptionPreview();
    updateCalculationExplanation();
    
    const modal = document.getElementById('option-modal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

// Gestionnaire global pour fermer la modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('option-modal');
        if (modal && modal.style.display === 'flex') {
            closeOptionModal();
        }
    }
});

// Gestionnaire pour fermer en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.id === 'option-modal') {
        closeOptionModal();
    }
});

// Exposer les fonctions globalement
window.openCreateOptionModal = openCreateOptionModal;
window.openEditOptionModal = openEditOptionModal;
window.closeOptionModal = closeOptionModal;
window.saveOption = saveOption;
window.testOption = testOption;
window.resetOptionForm = resetOptionForm;
</script>
