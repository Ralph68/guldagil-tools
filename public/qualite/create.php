<?php
/**
 * Titre: Création nouveau contrôle qualité - Formulaire par étapes
 * Chemin: /public/qualite/create.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// 🔧 CONFIGURATION & SÉCURITÉ
// =====================================

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement configuration
$config_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables template
$page_title = 'Nouveau Contrôle Qualité';
$page_subtitle = 'Création d\'un contrôle équipement';
$page_description = 'Formulaire de création de contrôle qualité par étapes';
$current_module = 'qualite';
$module_css = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '✅', 'text' => 'Contrôle Qualité', 'url' => '/qualite/', 'active' => false],
    ['icon' => '➕', 'text' => 'Nouveau Contrôle', 'url' => '/qualite/create.php', 'active' => true]
];

// =====================================
// 🔐 AUTHENTIFICATION
// =====================================

$user_authenticated = true;
$current_user = [
    'id' => 1,
    'username' => 'TestUser',
    'role' => 'logistique',
    'name' => 'Contrôleur Qualité'
];

$allowed_roles = ['admin', 'dev', 'logistique', 'resp_materiel'];
if (!in_array($current_user['role'], $allowed_roles)) {
    header('Location: /qualite/');
    exit;
}

// =====================================
// 📊 DONNÉES FORMULAIRE
// =====================================

// Types d'équipements (simulation)
$equipment_types = [
    'pompe_doseuse' => [
        'id' => 1,
        'name' => 'Pompe Doseuse',
        'category' => 'dosage',
        'icon' => '💉',
        'models' => [
            'DOSATRON_8L' => 'Dosatron 8L/h',
            'DOSATRON_24L' => 'Dosatron 24L/h',
            'PERISTALTIQUE_5L' => 'Péristaltique 5L/h',
            'MEMBRANE_10L' => 'Membrane 10L/h'
        ]
    ],
    'adoucisseur' => [
        'id' => 2,
        'name' => 'Adoucisseur',
        'category' => 'traitement',
        'icon' => '💧',
        'models' => [
            'SIMPLEX_15L' => 'Simplex 15L',
            'SIMPLEX_25L' => 'Simplex 25L',
            'DUPLEX_25L' => 'Duplex 25L',
            'DUPLEX_50L' => 'Duplex 50L'
        ]
    ]
];

// Agences (simulation)
$agencies = [
    'AG001' => 'Agence Principale',
    'AG002' => 'Agence Nord',
    'AG003' => 'Agence Sud',
    'AG004' => 'Agence Est'
];

// Étapes du formulaire
$steps = [
    1 => [
        'title' => 'Informations générales',
        'description' => 'Type équipement et identification',
        'icon' => '📋'
    ],
    2 => [
        'title' => 'Données techniques',
        'description' => 'Paramètres et spécifications',
        'icon' => '⚙️'
    ],
    3 => [
        'title' => 'Contrôles qualité',
        'description' => 'Tests et vérifications',
        'icon' => '🔬'
    ],
    4 => [
        'title' => 'Validation finale',
        'description' => 'Observations et approbation',
        'icon' => '✅'
    ]
];

// Génération numéro de contrôle
$control_number = 'CQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// =====================================
// 📄 TEMPLATE HEADER
// =====================================
require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <!-- Header Module -->
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">➕</div>
                <div class="module-info">
                    <h1>Nouveau Contrôle Qualité</h1>
                    <p class="module-version">N° <?= $control_number ?></p>
                </div>
            </div>
            <div class="module-actions">
                <button class="btn btn-outline" onclick="saveDraft()">
                    <span class="icon">💾</span>
                    Sauvegarder brouillon
                </button>
                <a href="/qualite/" class="btn btn-outline">
                    <span class="icon">←</span>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Indicateur d'étapes -->
        <div class="steps-indicator">
            <?php foreach ($steps as $step_num => $step): ?>
            <div class="step-item <?= $step_num === 1 ? 'active' : '' ?>" data-step="<?= $step_num ?>">
                <div class="step-circle">
                    <span class="step-icon"><?= $step['icon'] ?></span>
                    <span class="step-number"><?= $step_num ?></span>
                </div>
                <div class="step-content">
                    <div class="step-title"><?= $step['title'] ?></div>
                    <div class="step-description"><?= $step['description'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire principal -->
        <form id="control-form" class="control-form" method="POST" action="/qualite/api/create.php">
            <input type="hidden" name="control_number" value="<?= $control_number ?>">
            <input type="hidden" name="created_by" value="<?= $current_user['id'] ?>">
            
            <!-- Étape 1: Informations générales -->
            <div class="form-step active" id="step-1">
                <div class="form-card">
                    <div class="card-header">
                        <h2>📋 Informations générales</h2>
                        <p>Sélectionnez le type d'équipement et renseignez les informations de base</p>
                    </div>
                    
                    <div class="card-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="equipment_type" class="required">Type d'équipement</label>
                                <select id="equipment_type" name="equipment_type" class="form-control" required onchange="updateModels()">
                                    <option value="">Sélectionnez un type</option>
                                    <?php foreach ($equipment_types as $type_key => $type): ?>
                                    <option value="<?= $type_key ?>" data-category="<?= $type['category'] ?>">
                                        <?= $type['icon'] ?> <?= $type['name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="equipment_model">Modèle</label>
                                <select id="equipment_model" name="equipment_model" class="form-control" disabled>
                                    <option value="">Sélectionnez d'abord un type</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="agency_code" class="required">Agence</label>
                                <select id="agency_code" name="agency_code" class="form-control" required>
                                    <option value="">Sélectionnez une agence</option>
                                    <?php foreach ($agencies as $code => $name): ?>
                                    <option value="<?= $code ?>"><?= $code ?> - <?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dossier_number">N° Dossier</label>
                                <input type="text" id="dossier_number" name="dossier_number" class="form-control" 
                                       placeholder="Ex: DOS-2025-001">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="arc_number">N° ARC</label>
                                <input type="text" id="arc_number" name="arc_number" class="form-control" 
                                       placeholder="Ex: ARC-2025-001">
                            </div>
                            
                            <div class="form-group">
                                <label for="serial_number">N° Série</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" 
                                       placeholder="N° de série du matériel">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="installation_name">Nom installation</label>
                            <input type="text" id="installation_name" name="installation_name" class="form-control" 
                                   placeholder="Nom du site ou de l'installation">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 2: Données techniques -->
            <div class="form-step" id="step-2">
                <div class="form-card">
                    <div class="card-header">
                        <h2>⚙️ Données techniques</h2>
                        <p>Paramètres techniques et spécifications du matériel</p>
                    </div>
                    
                    <div class="card-content">
                        <div id="technical-fields">
                            <!-- Champs dynamiques selon type équipement -->
                            <div class="info-message">
                                <span class="icon">ℹ️</span>
                                Sélectionnez d'abord un type d'équipement à l'étape 1
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 3: Contrôles qualité -->
            <div class="form-step" id="step-3">
                <div class="form-card">
                    <div class="card-header">
                        <h2>🔬 Contrôles qualité</h2>
                        <p>Tests et vérifications de conformité</p>
                    </div>
                    
                    <div class="card-content">
                        <div id="quality-checks">
                            <!-- Contrôles dynamiques selon type équipement -->
                            <div class="info-message">
                                <span class="icon">ℹ️</span>
                                Les contrôles s'affichent selon le type d'équipement sélectionné
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 4: Validation finale -->
            <div class="form-step" id="step-4">
                <div class="form-card">
                    <div class="card-header">
                        <h2>✅ Validation finale</h2>
                        <p>Observations et validation du contrôle</p>
                    </div>
                    
                    <div class="card-content">
                        <div class="form-group">
                            <label for="observations">Observations</label>
                            <textarea id="observations" name="observations" class="form-control" rows="4"
                                    placeholder="Remarques, observations particulières..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prepared_by" class="required">Contrôlé par</label>
                                <input type="text" id="prepared_by" name="prepared_by" class="form-control" 
                                       value="<?= htmlspecialchars($current_user['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="prepared_date" class="required">Date</label>
                                <input type="date" id="prepared_date" name="prepared_date" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        
                        <div class="validation-section">
                            <h3>Décision finale</h3>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="final_status" value="conforme" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-text">✅ Conforme - Matériel prêt à expédier</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="final_status" value="non_conforme" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-text">❌ Non conforme - Action corrective nécessaire</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="final_status" value="en_attente" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-text">⏳ En attente - Contrôle à finaliser</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation entre étapes -->
            <div class="form-navigation">
                <button type="button" class="btn btn-outline" id="prev-step" style="display: none;" onclick="previousStep()">
                    <span class="icon">←</span>
                    Précédent
                </button>
                
                <div class="nav-center">
                    <span class="step-indicator">Étape <span id="current-step">1</span> sur <?= count($steps) ?></span>
                </div>
                
                <button type="button" class="btn btn-primary" id="next-step" onclick="nextStep()">
                    Suivant
                    <span class="icon">→</span>
                </button>
                
                <button type="submit" class="btn btn-primary" id="submit-form" style="display: none;">
                    <span class="icon">✅</span>
                    Créer le contrôle
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================== -->
<!-- 🔧 JAVASCRIPT FORMULAIRE -->
<!-- ===================================== -->

<script>
// Configuration
const CreateConfig = {
    currentStep: 1,
    maxSteps: <?= count($steps) ?>,
    equipmentTypes: <?= json_encode($equipment_types) ?>,
    controlNumber: '<?= $control_number ?>',
    autoSave: true
};

// Données techniques par type d'équipement
const TechnicalFields = {
    pompe_doseuse: [
        {name: 'debit_nominal', label: 'Débit nominal (L/h)', type: 'number', required: true},
        {name: 'pression_service', label: 'Pression de service (bar)', type: 'number', required: true},
        {name: 'concentration_max', label: 'Concentration max (%)', type: 'number', required: true},
        {name: 'temperature_max', label: 'Température max (°C)', type: 'number', required: true}
    ],
    adoucisseur: [
        {name: 'th_eau_brute', label: 'TH eau brute (°f)', type: 'number', required: true},
        {name: 'th_obtenir', label: 'TH à obtenir (°f)', type: 'number', required: true},
        {name: 'debit_nominal', label: 'Débit nominal (m³/h)', type: 'number', required: true},
        {name: 'volume_resine', label: 'Volume résine (L)', type: 'number', required: true},
        {name: 'consommation_sel', label: 'Consommation sel (kg/régén)', type: 'number', required: true}
    ]
};

// Contrôles qualité par type
const QualityChecks = {
    pompe_doseuse: [
        {name: 'test_etancheite', label: 'Test d\'étanchéité', type: 'checkbox'},
        {name: 'controle_debit', label: 'Contrôle débit', type: 'checkbox'},
        {name: 'test_pression', label: 'Test pression', type: 'checkbox'},
        {name: 'verification_dosage', label: 'Vérification précision dosage', type: 'checkbox'}
    ],
    adoucisseur: [
        {name: 'test_etancheite', label: 'Test d\'étanchéité', type: 'checkbox'},
        {name: 'controle_regeneration', label: 'Contrôle régénération', type: 'checkbox'},
        {name: 'test_th_sortie', label: 'Test TH eau traitée', type: 'checkbox'},
        {name: 'verification_programmation', label: 'Vérification programmation', type: 'checkbox'}
    ]
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    if (CreateConfig.autoSave) {
        setInterval(autoSave, 30000); // Auto-save toutes les 30s
    }
});

// Gestion des étapes
function nextStep() {
    if (validateCurrentStep()) {
        if (CreateConfig.currentStep < CreateConfig.maxSteps) {
            CreateConfig.currentStep++;
            updateStepDisplay();
        }
    }
}

function previousStep() {
    if (CreateConfig.currentStep > 1) {
        CreateConfig.currentStep--;
        updateStepDisplay();
    }
}

function updateStepDisplay() {
    // Masquer toutes les étapes
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Afficher l'étape courante
    document.getElementById(`step-${CreateConfig.currentStep}`).classList.add('active');
    
    // Mettre à jour les indicateurs d'étapes
    document.querySelectorAll('.step-item').forEach((item, index) => {
        item.classList.remove('active', 'completed');
        if (index + 1 === CreateConfig.currentStep) {
            item.classList.add('active');
        } else if (index + 1 < CreateConfig.currentStep) {
            item.classList.add('completed');
        }
    });
    
    // Mettre à jour la navigation
    document.getElementById('prev-step').style.display = CreateConfig.currentStep > 1 ? 'block' : 'none';
    document.getElementById('next-step').style.display = CreateConfig.currentStep < CreateConfig.maxSteps ? 'block' : 'none';
    document.getElementById('submit-form').style.display = CreateConfig.currentStep === CreateConfig.maxSteps ? 'block' : 'none';
    document.getElementById('current-step').textContent = CreateConfig.currentStep;
}

function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step-${CreateConfig.currentStep}`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    let isValid = true;
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    if (!isValid) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'error');
    }
    
    return isValid;
}

// Gestion des types d'équipements
function updateModels() {
    const typeSelect = document.getElementById('equipment_type');
    const modelSelect = document.getElementById('equipment_model');
    const selectedType = typeSelect.value;
    
    // Réinitialiser les modèles
    modelSelect.innerHTML = '<option value="">Sélectionnez un modèle</option>';
    
    if (selectedType && CreateConfig.equipmentTypes[selectedType]) {
        const models = CreateConfig.equipmentTypes[selectedType].models;
        modelSelect.disabled = false;
        
        Object.entries(models).forEach(([key, value]) => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = value;
            modelSelect.appendChild(option);
        });
        
        // Mettre à jour les champs techniques
        updateTechnicalFields(selectedType);
        updateQualityChecks(selectedType);
    } else {
        modelSelect.disabled = true;
        clearTechnicalFields();
        clearQualityChecks();
    }
}

function updateTechnicalFields(equipmentType) {
    const container = document.getElementById('technical-fields');
    
    if (TechnicalFields[equipmentType]) {
        const fields = TechnicalFields[equipmentType];
        let html = '<div class="form-row">';
        
        fields.forEach((field, index) => {
            if (index > 0 && index % 2 === 0) {
                html += '</div><div class="form-row">';
            }
            
            html += `
                <div class="form-group">
                    <label for="${field.name}" ${field.required ? 'class="required"' : ''}>${field.label}</label>
                    <input type="${field.type}" 
                           id="${field.name}" 
                           name="technical[${field.name}]" 
                           class="form-control"
                           ${field.required ? 'required' : ''}>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
}

function updateQualityChecks(equipmentType) {
    const container = document.getElementById('quality-checks');
    
    if (QualityChecks[equipmentType]) {
        const checks = QualityChecks[equipmentType];
        let html = '<div class="checks-grid">';
        
        checks.forEach(check => {
            html += `
                <div class="check-item">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="quality_checks[${check.name}]" 
                               value="1"
                               class="form-checkbox">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">${check.label}</span>
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Ajouter section résultats
        html += `
            <div class="results-section">
                <h3>Résultats des contrôles</h3>
                <div class="form-group">
                    <label for="control_results">Commentaires techniques</label>
                    <textarea id="control_results" 
                              name="control_results" 
                              class="form-control" 
                              rows="3"
                              placeholder="Détails des contrôles effectués, mesures relevées..."></textarea>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }
}

function clearTechnicalFields() {
    document.getElementById('technical-fields').innerHTML = `
        <div class="info-message">
            <span class="icon">ℹ️</span>
            Sélectionnez d'abord un type d'équipement à l'étape 1
        </div>
    `;
}

function clearQualityChecks() {
    document.getElementById('quality-checks').innerHTML = `
        <div class="info-message">
            <span class="icon">ℹ️</span>
            Les contrôles s'affichent selon le type d'équipement sélectionné
        </div>
    `;
}

// Sauvegarde automatique
function autoSave() {
    const formData = new FormData(document.getElementById('control-form'));
    formData.append('action', 'autosave');
    
    fetch('/qualite/api/autosave.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Brouillon sauvegardé automatiquement', 'success', 2000);
        }
    })
    .catch(error => {
        console.error('Erreur auto-save:', error);
    });
}

function saveDraft() {
    const formData = new FormData(document.getElementById('control-form'));
    formData.append('action', 'save_draft');
    
    fetch('/qualite/api/save-draft.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Brouillon sauvegardé avec succès', 'success');
        } else {
            showNotification('Erreur lors de la sauvegarde', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// Soumission du formulaire
document.getElementById('control-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    // Confirmation avant soumission
    if (confirm('Confirmer la création de ce contrôle qualité ?')) {
        const formData = new FormData(this);
        
        // Afficher loading
        const submitBtn = document.getElementById('submit-form');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Création en cours...';
        submitBtn.disabled = true;
        
        fetch('/qualite/api/create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Contrôle créé avec succès !', 'success');
                setTimeout(() => {
                    window.location.href = `/qualite/view.php?id=${data.control_id}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Erreur lors de la création');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification(error.message || 'Erreur lors de la création', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
});

// Utilitaires
function initializeForm() {
    updateStepDisplay();
    
    // Écouteurs d'événements
    document.getElementById('equipment_type').addEventListener('change', updateModels);
    
    // Validation en temps réel
    document.querySelectorAll('input[required], select[required]').forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span class="notification-text">${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Suppression automatique
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

console.log('🔬 Formulaire création contrôle qualité initialisé');
</script>

<!-- ===================================== -->
<!-- 🎨 CSS SPÉCIFIQUE FORMULAIRE -->
<!-- ===================================== -->

<style>
/* Indicateur d'étapes */
.steps-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    position: relative;
    padding: 1rem;
    opacity: 0.5;
    transition: var(--transition);
}

.step-item.active {
    opacity: 1;
}

.step-item.completed {
    opacity: 0.8;
}

.step-item.completed .step-circle {
    background: var(--qualite-success);
    color: white;
}

.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 2rem;
    right: -50%;
    width: 100%;
    height: 2px;
    background: var(--gray-300);
    z-index: 0;
}

.step-item.completed:not(:last-child)::after {
    background: var(--qualite-success);
}

.step-circle {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: var(--gray-200);
    color: var(--gray-500);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    transition: var(--transition);
}

.step-item.active .step-circle {
    background: var(--qualite-primary);
    color: white;
    transform: scale(1.1);
}

.step-icon {
    display: block;
}

.step-number {
    display: none;
}

.step-content {
    max-width: 120px;
}

.step-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.step-description {
    font-size: 0.75rem;
    color: var(--gray-600);
    line-height: 1.3;
}

/* Formulaire par étapes */
.control-form {
    margin-bottom: 2rem;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: fadeInUp 0.5s ease forwards;
}

.form-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.card-header {
    background: var(--gray-50);
    padding: 2rem;
    border-bottom: 1px solid var(--gray-200);
}

.card-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-800);
}

.card-header p {
    margin: 0;
    color: var(--gray-600);
}

.card-content {
    padding: 2rem;
}

/* Champs de formulaire */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.form-group label.required::after {
    content: ' *';
    color: var(--qualite-danger);
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--gray-300);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: var(--transition);
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: var(--qualite-primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-control.error {
    border-color: var(--qualite-danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Messages d'information */
.info-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
    color: var(--gray-600);
    font-style: italic;
}

.info-message .icon {
    font-size: 1.5rem;
}

/* Contrôles qualité */
.checks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.check-item {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
    border: 2px solid transparent;
    transition: var(--transition);
}

.check-item:hover {
    border-color: var(--qualite-primary);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
}

.form-checkbox {
    display: none;
}

.checkbox-custom {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--gray-400);
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.form-checkbox:checked + .checkbox-custom {
    background: var(--qualite-primary);
    border-color: var(--qualite-primary);
}

.form-checkbox:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 0.875rem;
}

/* Section validation */
.validation-section {
    margin-top: 2rem;
    padding: 2rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.validation-section h3 {
    margin: 0 0 1.5rem 0;
    color: var(--gray-800);
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    border: 2px solid transparent;
    transition: var(--transition);
}

.radio-label:hover {
    border-color: var(--qualite-primary);
}

input[type="radio"] {
    display: none;
}

.radio-custom {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--gray-400);
    border-radius: 50%;
    transition: var(--transition);
}

input[type="radio"]:checked + .radio-custom {
    border-color: var(--qualite-primary);
    background: var(--qualite-primary);
    box-shadow: inset 0 0 0 3px white;
}

.radio-text {
    font-weight: 500;
}

/* Navigation */
.form-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-top: 2rem;
}

.nav-center {
    text-align: center;
    font-weight: 500;
    color: var(--gray-600);
}

/* Notifications */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: var(--box-shadow-lg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 400px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid var(--qualite-success);
}

.notification-error {
    border-left: 4px solid var(--qualite-danger);
}

.notification-info {
    border-left: 4px solid var(--qualite-info);
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: var(--gray-400);
    margin-left: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .steps-indicator {
        padding: 1rem;
    }
    
    .step-item {
        padding: 0.5rem;
    }
    
    .step-circle {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }
    
    .step-content {
        max-width: 100px;
    }
    
    .step-title,
    .step-description {
        font-size: 0.75rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .card-header,
    .card-content {
        padding: 1.5rem;
    }
    
    .checks-grid {
        grid-template-columns: 1fr;
    }
    
    .form-navigation {
        flex-direction: column;
        gap: 1rem;
        padding: 1.5rem;
    }
    
    .nav-center {
        order: -1;
    }
}
</style>

<?php
// =====================================
// 📄 TEMPLATE FOOTER
// =====================================
require_once ROOT_PATH . '/templates/footer.php';
?>
