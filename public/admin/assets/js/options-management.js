// public/admin/assets/js/options-management.js - Gestion des options supplémentaires
console.log('⚙️ Chargement du module de gestion des options...');

// Variables globales
let optionsData = [];
let currentFilters = {
    carrier: '',
    status: ''
};

// Codes d'options disponibles avec leurs descriptions
const OPTION_CODES = {
    'rdv': 'Prise de RDV',
    'premium13': 'Premium avant 13h',
    'premium18': 'Premium avant 18h',
    'datefixe': 'Date fixe',
    'enlevement': 'Enlèvement sur site',
    'palette': 'Frais par palette EUR',
    'assurance': 'Assurance renforcée',
    'livraison_etage': 'Livraison étage',
    'retour_document': 'Retour de documents',
    'contre_remboursement': 'Contre-remboursement'
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module options initialisé');
    initializeOptionsInterface();
});

function initializeOptionsInterface() {
    console.log('🔧 Initialisation interface options');
    setupOptionsEventListeners();
}

function setupOptionsEventListeners() {
    // Boutons d'action
    const addButton = document.getElementById('add-option-button');
    const refreshButton = document.getElementById('refresh-options-button');
    const filterCarrier = document.getElementById('filter-options-carrier');
    const filterStatus = document.getElementById('filter-options-status');
    
    if (addButton) {
        addButton.addEventListener('click', showCreateOptionModal);
    }
    
    if (refreshButton) {
        refreshButton.addEventListener('click', () => loadOptions(true));
    }
    
    if (filterCarrier) {
        filterCarrier.addEventListener('change', handleOptionsFilter);
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', handleOptionsFilter);
    }
}

/**
 * Charge la liste des options
 */
function loadOptions(force = false) {
    console.log('⚙️ Chargement des options...', { filters: currentFilters, force });
    
    showOptionsLoading(true);
    
    const params = new URLSearchParams({
        action: 'list',
        carrier: currentFilters.carrier || '',
        status: currentFilters.status || ''
    });
    
    const url = `api-options.php?${params.toString()}`;
    console.log('📡 Requête API options:', url);
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Options reçues:', data);
            if (data.success) {
                optionsData = data.data.options || [];
                displayOptions(optionsData);
                displayOptionsStats(data.data.stats);
                console.log('✅ Options affichées:', optionsData.length);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('❌ Erreur chargement options:', error);
            showError('Erreur lors du chargement des options: ' + error.message);
            displayOptions([]);
        })
        .finally(() => {
            showOptionsLoading(false);
        });
}

/**
 * Affiche le loading pour les options
 */
function showOptionsLoading(show) {
    const tbody = document.getElementById('options-tbody');
    if (!tbody) return;
    
    if (show) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                        <div class="spinner" style="width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #007acc; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        Chargement des options...
                    </div>
                </td>
            </tr>
        `;
    }
}

/**
 * Affiche les options dans le tableau
 */
function displayOptions(options) {
    const tbody = document.getElementById('options-tbody');
    if (!tbody) return;
    
    if (!options || options.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center" style="padding: 2rem; color: #666;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem;">⚙️</div>
                        <div>Aucune option trouvée</div>
                        <button class="btn btn-primary btn-sm" onclick="showCreateOptionModal()">
                            ➕ Ajouter une option
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    options.forEach(option => {
        const statusBadge = option.actif 
            ? '<span class="badge badge-success">Actif</span>'
            : '<span class="badge badge-warning">Inactif</span>';
            
        const carrierId = getCarrierId(option.transporteur);
        
        html += `
            <tr style="transition: background-color 0.2s ease;">
                <td>
                    <div style="font-weight: 600; color: var(--primary-color);">${option.transporteur}</div>
                    <div style="font-size: 0.8rem; color: #666;">${carrierId}</div>
                </td>
                <td>
                    <div style="font-weight: 500;">${option.code_option}</div>
                </td>
                <td>
                    <div style="max-width: 200px; word-wrap: break-word;">${option.libelle}</div>
                </td>
                <td style="font-weight: 600;">
                    ${parseFloat(option.montant).toFixed(2)} €
                </td>
                <td>
                    <span class="badge badge-info">${getUniteLabel(option.unite)}</span>
                </td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <div class="actions" style="display: flex; gap: 0.5rem; justify-content: center;">
                        <button class="btn btn-secondary btn-sm" 
                                onclick="editOptionModal(${option.id})" 
                                title="Modifier">
                            ✏️
                        </button>
                        <button class="btn ${option.actif ? 'btn-warning' : 'btn-success'} btn-sm" 
                                onclick="toggleOption(${option.id})" 
                                title="${option.actif ? 'Désactiver' : 'Activer'}">
                            ${option.actif ? '⏸️' : '▶️'}
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="confirmDeleteOption(${option.id}, '${option.libelle}')" 
                                title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Affiche les statistiques des options
 */
function displayOptionsStats(stats) {
    if (!stats) return;
    
    // Mettre à jour les cartes de statistiques
    const totalEl = document.getElementById('options-total');
    const activeEl = document.getElementById('options-active');
    const inactiveEl = document.getElementById('options-inactive');
    
    if (totalEl) totalEl.textContent = stats.total;
    if (activeEl) activeEl.textContent = stats.active;
    if (inactiveEl) inactiveEl.textContent = stats.inactive;
    
    // Afficher la répartition par transporteur
    const distributionEl = document.getElementById('options-distribution');
    if (distributionEl && stats.by_carrier) {
        let html = '';
        Object.entries(stats.by_carrier).forEach(([carrier, count]) => {
            const carrierName = getCarrierName(carrier);
            html += `
                <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; margin-bottom: 0.5rem;">
                    <span>${carrierName}</span>
                    <strong>${count} options</strong>
                </div>
            `;
        });
        distributionEl.innerHTML = html;
    }
}

/**
 * Gère les filtres
 */
function handleOptionsFilter() {
    const carrierFilter = document.getElementById('filter-options-carrier');
    const statusFilter = document.getElementById('filter-options-status');
    
    currentFilters.carrier = carrierFilter?.value || '';
    currentFilters.status = statusFilter?.value || '';
    
    console.log('🔍 Filtres options mis à jour:', currentFilters);
    loadOptions();
}

/**
 * Affiche la modal de création d'option
 */
function showCreateOptionModal() {
    console.log('➕ Création nouvelle option');
    
    // Réinitialiser le formulaire
    const form = document.getElementById('option-form');
    if (form) {
        form.reset();
        document.getElementById('option-id').value = '';
        document.getElementById('option-transporteur').disabled = false;
        document.getElementById('option-code').disabled = false;
        
        // Pré-remplir avec des codes d'options
        populateOptionCodeSelect();
        
        // Changer le titre
        document.querySelector('#option-modal .modal-header h3').textContent = '➕ Nouvelle option';
    }
    
    // Afficher la modal
    const modal = document.getElementById('option-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}

/**
 * Affiche la modal d'édition d'option
 */
function editOptionModal(id) {
    console.log('✏️ Édition option:', id);
    
    const option = optionsData.find(o => o.id === id);
    if (!option) {
        showError('Option non trouvée');
        return;
    }
    
    // Remplir le formulaire
    document.getElementById('option-id').value = option.id;
    document.getElementById('option-transporteur').value = getCarrierId(option.transporteur);
    document.getElementById('option-transporteur').disabled = true;
    document.getElementById('option-code').value = option.code_option;
    document.getElementById('option-code').disabled = true;
    document.getElementById('option-libelle').value = option.libelle;
    document.getElementById('option-montant').value = option.montant;
    document.getElementById('option-unite').value = option.unite;
    document.getElementById('option-actif').checked = option.actif;
    
    // Changer le titre
    document.querySelector('#option-modal .modal-header h3').textContent = '✏️ Modifier l\'option';
    
    // Afficher la modal
    const modal = document.getElementById('option-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}

/**
 * Ferme la modal d'option
 */
function closeOptionModal() {
    const modal = document.getElementById('option-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
}

/**
 * Sauvegarde une option (création ou modification)
 */
function saveOption() {
    console.log('💾 Sauvegarde option...');
    
    const formData = {
        id: document.getElementById('option-id').value,
        transporteur: getCarrierName(document.getElementById('option-transporteur').value),
        code_option: document.getElementById('option-code').value,
        libelle: document.getElementById('option-libelle').value,
        montant: parseFloat(document.getElementById('option-montant').value),
        unite: document.getElementById('option-unite').value,
        actif: document.getElementById('option-actif').checked ? 1 : 0
    };
    
    // Validation
    if (!formData.transporteur || !formData.code_option || !formData.libelle || isNaN(formData.montant)) {
        showError('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    console.log('📊 Données option à sauvegarder:', formData);
    
    const isEdit = formData.id !== '';
    const url = 'api-options.php' + (isEdit ? '' : '?action=create');
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || (isEdit ? 'Option modifiée' : 'Option créée') + ' avec succès');
            closeOptionModal();
            loadOptions(); // Recharger la liste
        } else {
            throw new Error(data.error || 'Erreur lors de la sauvegarde');
        }
    })
    .catch(error => {
        console.error('❌ Erreur sauvegarde option:', error);
        showError('Erreur lors de la sauvegarde: ' + error.message);
    });
}

/**
 * Confirme la suppression d'une option
 */
function confirmDeleteOption(id, libelle) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'option "${libelle}" ?`)) {
        deleteOption(id);
    }
}

/**
 * Supprime une option
 */
function deleteOption(id) {
    console.log('🗑️ Suppression option:', id);
    
    fetch(`api-options.php?action=delete&id=${id}`, { method: 'GET' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess('Option supprimée avec succès');
                loadOptions();
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('❌ Erreur suppression option:', error);
            showError('Erreur lors de la suppression: ' + error.message);
        });
}

/**
 * Active/désactive une option
 */
function toggleOption(id) {
    console.log('🔄 Toggle option:', id);
    
    fetch(`api-options.php?action=toggle&id=${id}`, { method: 'GET' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                loadOptions();
            } else {
                throw new Error(data.error || 'Erreur lors du changement de statut');
            }
        })
        .catch(error => {
            console.error('❌ Erreur toggle option:', error);
            showError('Erreur lors du changement de statut: ' + error.message);
        });
}

/**
 * Remplit la liste des codes d'options
 */
function populateOptionCodeSelect() {
    const select = document.getElementById('option-code');
    if (!select) return;
    
    let html = '<option value="">Choisir un code...</option>';
    Object.entries(OPTION_CODES).forEach(([code, label]) => {
        html += `<option value="${code}">${code} - ${label}</option>`;
    });
    
    select.innerHTML = html;
}

/**
 * Met à jour le libellé automatiquement selon le code
 */
function updateOptionLabel() {
    const codeSelect = document.getElementById('option-code');
    const libelleInput = document.getElementById('option-libelle');
    
    if (codeSelect && libelleInput) {
        const selectedCode = codeSelect.value;
        if (selectedCode && OPTION_CODES[selectedCode] && !libelleInput.value) {
            libelleInput.value = OPTION_CODES[selectedCode];
        }
    }
}

/**
 * Fonctions utilitaires
 */
function getCarrierId(carrierName) {
    const mapping = {
        'Heppner': 'heppner',
        'XPO': 'xpo',
        'Kuehne + Nagel': 'kn'
    };
    return mapping[carrierName] || carrierName.toLowerCase();
}

function getCarrierName(carrierId) {
    const mapping = {
        'heppner': 'Heppner',
        'xpo': 'XPO',
        'kn': 'Kuehne + Nagel'
    };
    return mapping[carrierId] || carrierId;
}

function getUniteLabel(unite) {
    const labels = {
        'forfait': 'Forfait',
        'palette': 'Par palette',
        'pourcentage': 'Pourcentage'
    };
    return labels[unite] || unite;
}

/**
 * Messages
 */
function showSuccess(message) {
    if (typeof showAlert === 'function') {
        showAlert('success', message);
    } else {
        console.log('✅ Succès:', message);
        alert(message);
    }
}

function showError(message) {
    if (typeof showAlert === 'function') {
        showAlert('error', message);
    } else {
        console.error('❌ Erreur:', message);
        alert('Erreur: ' + message);
    }
}

// Exposer les fonctions globalement
window.loadOptions = loadOptions;
window.showCreateOptionModal = showCreateOptionModal;
window.editOptionModal = editOptionModal;
window.closeOptionModal = closeOptionModal;
window.saveOption = saveOption;
window.confirmDeleteOption = confirmDeleteOption;
window.deleteOption = deleteOption;
window.toggleOption = toggleOption;
window.updateOptionLabel = updateOptionLabel;
window.handleOptionsFilter = handleOptionsFilter;

// Override de la fonction showTab pour charger les options quand on affiche l'onglet
const originalShowTab = window.showTab;
if (originalShowTab) {
    window.showTab = function(tabName) {
        originalShowTab(tabName);
        
        // Si on affiche l'onglet options, charger les données
        if (tabName === 'options') {
            console.log('⚙️ Chargement onglet options');
            setTimeout(() => {
                if (!optionsData || optionsData.length === 0) {
                    loadOptions();
                }
            }, 100);
        }
    };
}

// Initialiser les event listeners spécifiques aux options
document.addEventListener('DOMContentLoaded', function() {
    // Event listener pour la mise à jour automatique du libellé
    const codeSelect = document.getElementById('option-code');
    if (codeSelect) {
        codeSelect.addEventListener('change', updateOptionLabel);
    }
    
    // Event listener pour fermer la modal en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (e.target.id === 'option-modal') {
            closeOptionModal();
        }
    });
    
    // Event listener pour la touche Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('option-modal');
            if (modal && modal.style.display === 'flex') {
                closeOptionModal();
            }
        }
    });
});

console.log('✅ Module de gestion des options chargé avec succès');
