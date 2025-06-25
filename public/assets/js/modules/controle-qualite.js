// /public/assets/js/modules/controle-qualite.js

/**
 * Module JavaScript Contrôle Qualité
 * Gestion des formulaires et interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initControleQualite();
});

function initControleQualite() {
    // Auto-complétion champs
    initAutoComplete();
    
    // Validation formulaire
    initFormValidation();
    
    // Sauvegarde automatique
    initAutoSave();
    
    // Compteurs progression
    initProgressCounters();
}

/**
 * Auto-complétion des champs fréquents
 */
function initAutoComplete() {
    // Email opérateur avec domaine Guldagil
    const emailField = document.getElementById('operateur_email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            const email = this.value;
            if (email && !email.includes('@')) {
                this.value = email + '@guldagil.fr';
            }
        });
    }
    
    // Ref Gul auto selon marque/modèle
    const marqueField = document.getElementById('marque');
    const modeleField = document.getElementById('modele');
    const refGulField = document.getElementById('ref_gul');
    
    if (marqueField && modeleField && refGulField) {
        function updateRefGul() {
            const marque = marqueField.value;
            const modele = modeleField.value;
            
            if (marque === 'TEKNA' && modele.includes('APG603')) {
                refGulField.value = 'DOS4-8V';
            } else if (marque === 'GRUNDFOS' && modele.includes('DDE')) {
                refGulField.value = 'DOS6DDE';
            }
        }
        
        marqueField.addEventListener('change', updateRefGul);
        modeleField.addEventListener('blur', updateRefGul);
    }
}

/**
 * Validation temps réel du formulaire
 */
function initFormValidation() {
    const form = document.querySelector('.cq-form');
    if (!form) return;
    
    // Validation email
    const emailField = document.getElementById('operateur_email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            validateEmail(this);
        });
    }
    
    // Validation N° ARC (format attendu)
    const arcField = document.getElementById('numero_arc');
    if (arcField) {
        arcField.addEventListener('blur', function() {
            validateARC(this);
        });
    }
    
    // Validation date expédition (pas dans le passé)
    const dateField = document.getElementById('date_expedition');
    if (dateField) {
        dateField.addEventListener('change', function() {
            validateDate(this);
        });
    }
    
    // Validation avant soumission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            showAlert('Veuillez corriger les erreurs avant de continuer', 'error');
        }
    });
}

function validateEmail(field) {
    const email = field.value;
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    
    toggleFieldError(field, !isValid, 'Format email invalide');
    return isValid;
}

function validateARC(field) {
    const arc = field.value;
    const isValid = arc.length >= 3; // Validation basique
    
    toggleFieldError(field, !isValid, 'N° ARC trop court');
    return isValid;
}

function validateDate(field) {
    const date = new Date(field.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const isValid = date >= today;
    toggleFieldError(field, !isValid, 'Date ne peut pas être dans le passé');
    return isValid;
}

function toggleFieldError(field, hasError, message) {
    const group = field.closest('.form-group');
    if (!group) return;
    
    // Supprimer ancienne erreur
    const oldError = group.querySelector('.field-error');
    if (oldError) oldError.remove();
    
    if (hasError) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = 'var(--cq-error)';
        errorDiv.style.fontSize = '0.8rem';
        errorDiv.style.marginTop = '0.25rem';
        group.appendChild(errorDiv);
    } else {
        field.classList.remove('error');
    }
}

function validateForm() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            toggleFieldError(field, true, 'Champ obligatoire');
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Sauvegarde automatique en session
 */
function initAutoSave() {
    const form = document.querySelector('.cq-form');
    if (!form) return;
    
    // Charger données sauvegardées
    loadFormData();
    
    // Sauvegarder à chaque modification
    const fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
        field.addEventListener('change', saveFormData);
        if (field.type === 'text' || field.type === 'email') {
            field.addEventListener('blur', saveFormData);
        }
    });
    
    // Effacer sauvegarde après soumission réussie
    form.addEventListener('submit', function() {
        setTimeout(() => {
            localStorage.removeItem('cq_form_data');
        }, 1000);
    });
}

function saveFormData() {
    const form = document.querySelector('.cq-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            // Gérer les checkboxes multiples
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    localStorage.setItem('cq_form_data', JSON.stringify(data));
    
    // Indicateur visuel de sauvegarde
    showSaveIndicator();
}

function loadFormData() {
    const savedData = localStorage.getItem('cq_form_data');
    if (!savedData) return;
    
    try {
        const data = JSON.parse(savedData);
        
        Object.entries(data).forEach(([key, value]) => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = true;
                } else {
                    field.value = value;
                }
            }
        });
        
        showAlert('Données restaurées depuis la dernière session', 'info');
    } catch (e) {
        console.warn('Erreur chargement données sauvegardées:', e);
    }
}

function showSaveIndicator() {
    // Créer ou mettre à jour indicateur
    let indicator = document.getElementById('save-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'save-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--cq-success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        `;
        document.body.appendChild(indicator);
    }
    
    indicator.textContent = '💾 Sauvegardé';
    indicator.style.opacity = '1';
    
    setTimeout(() => {
        indicator.style.opacity = '0';
    }, 2000);
}

/**
 * Compteurs de progression checklist
 */
function initProgressCounters() {
    const checklists = document.querySelectorAll('.cq-checklist');
    
    checklists.forEach(checklist => {
        const checkboxes = checklist.querySelectorAll('input[type="checkbox"]');
        if (checkboxes.length === 0) return;
        
        // Créer compteur
        const counter = document.createElement('div');
        counter.className = 'progress-counter';
        counter.style.cssText = `
            text-align: center;
            margin-top: 1rem;
            font-weight: 500;
            color: var(--cq-primary);
        `;
        
        checklist.parentNode.appendChild(counter);
        
        // Mettre à jour compteur
        function updateCounter() {
            const checked = checklist.querySelectorAll('input[type="checkbox"]:checked').length;
            const total = checkboxes.length;
            const percentage = Math.round((checked / total) * 100);
            
            counter.innerHTML = `
                <div>✅ ${checked}/${total} éléments validés (${percentage}%)</div>
                <div style="background: #eee; height: 6px; border-radius: 3px; margin-top: 0.5rem;">
                    <div style="background: var(--cq-success); height: 100%; width: ${percentage}%; border-radius: 3px; transition: width 0.3s;"></div>
                </div>
            `;
        }
        
        // Événements
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateCounter);
        });
        
        // Initial
        updateCounter();
    });
}

/**
 * Utilitaires
 */
function showAlert(message, type = 'info') {
    // Créer alerte
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        min-width: 300px;
        max-width: 500px;
    `;
    
    document.body.appendChild(alert);
    
    // Auto-suppression
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Fonction pour toggle compteur
function toggleCompteur() {
    const checkbox = document.getElementById('compteur_present');
    const details = document.getElementById('compteur_details');
    
    if (checkbox && details) {
        details.style.display = checkbox.checked ? 'block' : 'none';
        
        // Rendre champs requis si compteur présent
        const compteurFields = details.querySelectorAll('input, select');
        compteurFields.forEach(field => {
            field.required = checkbox.checked;
        });
    }
}
