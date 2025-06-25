// public/assets/js/modules/calculateur/ux-enlevement.js
// Logique UX pour enlèvement = standard uniquement

document.addEventListener('DOMContentLoaded', function() {
    setupEnlevementLogic();
});

function setupEnlevementLogic() {
    const optionCards = document.querySelectorAll('.option-card');
    const enlevementCheckbox = document.getElementById('enlevement-checkbox');
    const enlevementSection = document.getElementById('enlevement-section');
    const serviceField = document.querySelector('input[name="service_livraison"]');
    
    // Gestion clic sur options
    optionCards.forEach(card => {
        card.addEventListener('click', function() {
            // Désélectionner toutes
            optionCards.forEach(c => c.classList.remove('selected'));
            
            // Sélectionner cliquée
            this.classList.add('selected');
            const selectedOption = this.dataset.option;
            
            // Mettre à jour champ caché
            if (serviceField) {
                serviceField.value = selectedOption;
            }
            
            // Gérer enlèvement
            updateEnlevementState(selectedOption);
            
            // Déclencher calcul si auto-calc disponible
            triggerAutoCalculation();
        });
    });
    
    // Gestion changement enlèvement
    if (enlevementCheckbox) {
        enlevementCheckbox.addEventListener('change', function() {
            if (enlevementSection) {
                enlevementSection.classList.toggle('enabled', this.checked);
            }
            triggerAutoCalculation();
        });
    }
    
    // Initialiser état
    const selectedCard = document.querySelector('.option-card.selected');
    if (selectedCard) {
        updateEnlevementState(selectedCard.dataset.option);
    }
}

function updateEnlevementState(selectedOption) {
    const enlevementCheckbox = document.getElementById('enlevement-checkbox');
    const enlevementSection = document.getElementById('enlevement-section');
    const helpText = document.getElementById('enlevement-help');
    
    if (!enlevementCheckbox || !enlevementSection) return;
    
    if (selectedOption === 'standard') {
        // Activer enlèvement pour standard
        enlevementCheckbox.disabled = false;
        enlevementSection.classList.remove('disabled');
        
        if (helpText) {
            helpText.textContent = 'Cochez pour ajouter l\'enlèvement sur site expéditeur';
        }
    } else {
        // Désactiver enlèvement pour autres options
        enlevementCheckbox.disabled = true;
        enlevementCheckbox.checked = false;
        enlevementSection.classList.add('disabled');
        enlevementSection.classList.remove('enabled');
        
        if (helpText) {
            helpText.textContent = '🚫 Enlèvement disponible uniquement avec livraison standard';
        }
        
        // Déclencher événement pour calcul auto
        enlevementCheckbox.dispatchEvent(new Event('change'));
    }
}

function triggerAutoCalculation() {
    // Si module avancé disponible
    if (window.calcController && typeof window.calcController.scheduleCalculation === 'function') {
        window.calcController.scheduleCalculation();
    }
    // Sinon déclencher événement pour mode fallback
    else {
        const form = document.getElementById('calc-form');
        if (form) {
            form.dispatchEvent(new Event('change'));
        }
    }
}
