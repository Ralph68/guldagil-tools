// public/adr/assets/js/adr-destinataire.js - Gestion destinataire optimisée
console.log('📫 Chargement module destinataire ADR optimisé...');

// Variables globales
let villesCache = new Map();
let destinatairesCache = new Map();
let searchTimeout = null;

// Configuration
const CONFIG = {
    searchDelay: 200,
    minSearchChars: 2,
    maxSuggestions: 8,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes
};

// État du destinataire
let currentDestinataire = {
    id: null,
    nom: '',
    adresse_complete: '',
    code_postal: '',
    ville: '',
    pays: 'France',
    telephone: '',
    email: ''
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module destinataire initialisé');
    initializeDestinataireModule();
});

function initializeDestinataireModule() {
    setupDestinataireEventListeners();
    preloadCommonData();
    initializeFormValidation();
}

function setupDestinataireEventListeners() {
    // Recherche globale destinataire
    const searchInput = document.getElementById('search-destinataire');
    if (searchInput) {
        searchInput.addEventListener('input', handleDestinataireSearch);
        searchInput.addEventListener('focus', handleSearchFocus);
        searchInput.addEventListener('blur', hideDestinatairesSuggestions);
        searchInput.addEventListener('keydown', handleSearchKeyNavigation);
    }

    // Code postal -> recherche villes
    const cpInput = document.getElementById('destinataire-cp');
    if (cpInput) {
        cpInput.addEventListener('input', handleCodePostalInput);
        cpInput.addEventListener('blur', hideVillesSuggestions);
        cpInput.addEventListener('keydown', handleVilleKeyNavigation);
    }

    // Ville -> recherche CP/ville
    const villeInput = document.getElementById('destinataire-ville');
    if (villeInput) {
        villeInput.addEventListener('input', handleVilleInput);
        villeInput.addEventListener('focus', handleVilleFocus);
        villeInput.addEventListener('blur', hideVillesSuggestions);
        villeInput.addEventListener('keydown', handleVilleKeyNavigation);
    }

    // Validation en temps réel
    const requiredFields = ['destinataire-nom', 'destinataire-cp', 'destinataire-ville'];
    requiredFields.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', validateDestinataireRealTime);
            input.addEventListener('blur', updateCurrentDestinataire);
        }
    });

    // Autres champs
    const optionalFields = ['destinataire-adresse', 'destinataire-telephone', 'destinataire-email'];
    optionalFields.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('blur', updateCurrentDestinataire);
        }
    });
}

// ========== RECHERCHE DESTINATAIRES ==========

function handleDestinataireSearch() {
    const searchInput = document.getElementById('search-destinataire');
    const query = searchInput.value.trim();
    
    if (query.length < CONFIG.minSearchChars) {
        hideDestinatairesSuggestions();
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchDestinataires(query);
    }, CONFIG.searchDelay);
}

function handleSearchFocus() {
    const searchInput = document.getElementById('search-destinataire');
    const query = searchInput.value.trim();
    
    if (query.length >= CONFIG.minSearchChars) {
        searchDestinataires(query);
    }
}

function handleSearchKeyNavigation(e) {
    const suggestions = document.querySelectorAll('#destinataires-suggestions .suggestion-item');
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            navigateSuggestions(suggestions, 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            navigateSuggestions(suggestions, -1);
            break;
        case 'Enter':
            e.preventDefault();
            selectHighlightedSuggestion(suggestions);
            break;
        case 'Escape':
            hideDestinatairesSuggestions();
            break;
    }
}

function searchDestinataires(query) {
    console.log('🔍 Recherche destinataires:', query);
    
    // Vérifier cache
    const cacheKey = `dest_${query.toLowerCase()}`;
    if (destinatairesCache.has(cacheKey)) {
        const cached = destinatairesCache.get(cacheKey);
        if (Date.now() - cached.timestamp < CONFIG.cacheTimeout) {
            displayDestinatairesSuggestions(cached.data);
            return;
        }
    }
    
    // Recherche API avec gestion d'erreur améliorée
    const formData = new FormData();
    formData.append('action', 'search_destinataires');
    formData.append('query', query);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Mettre en cache
            destinatairesCache.set(cacheKey, {
                data: data.destinataires,
                timestamp: Date.now()
            });
            
            displayDestinatairesSuggestions(data.destinataires);
        } else {
            console.warn('Aucun destinataire trouvé:', data.error);
            displayDestinatairesSuggestions([]);
        }
    })
    .catch(error => {
        console.error('Erreur recherche destinataires:', error);
        hideDestinatairesSuggestions();
        showNotification('❌ Erreur lors de la recherche', 'error');
    });
}

function displayDestinatairesSuggestions(destinataires) {
    const container = document.getElementById('destinataires-suggestions');
    if (!container) return;
    
    let html = '';
    
    if (destinataires.length === 0) {
        html = `
            <div class="suggestion-item create-new" onclick="showCreateDestinataireForm()" data-action="create">
                <div class="suggestion-header">
                    <span class="suggestion-icon">➕</span>
                    <span class="suggestion-title">Créer ce destinataire</span>
                </div>
                <div class="suggestion-details">Enregistrer pour les prochaines expéditions</div>
            </div>
        `;
    } else {
        destinataires.forEach((dest, index) => {
            html += `
                <div class="suggestion-item" onclick="selectDestinataire(${escapeJson(dest)})" data-index="${index}">
                    <div class="suggestion-header">
                        <span class="suggestion-icon">📍</span>
                        <span class="suggestion-title">${escapeHtml(dest.nom)}</span>
                        <span class="suggestion-frequency">×${dest.frequence_utilisation || 1}</span>
                    </div>
                    <div class="suggestion-details">
                        ${escapeHtml(dest.adresse_complete || 'Adresse non renseignée')}<br>
                        <strong>${escapeHtml(dest.code_postal)} ${escapeHtml(dest.ville)}</strong>
                    </div>
                </div>
            `;
        });
        
        html += `
            <div class="suggestion-item create-new" onclick="showCreateDestinataireForm()" data-action="create">
                <div class="suggestion-header">
                    <span class="suggestion-icon">➕</span>
                    <span class="suggestion-title">Nouveau destinataire</span>
                </div>
                <div class="suggestion-details">Créer un destinataire différent</div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

function hideDestinatairesSuggestions() {
    setTimeout(() => {
        const container = document.getElementById('destinataires-suggestions');
        if (container) {
            container.style.display = 'none';
        }
    }, 150);
}

function selectDestinataire(dest) {
    console.log('📍 Destinataire sélectionné:', dest);
    
    // Remplir le formulaire
    setInputValue('search-destinataire', dest.nom);
    setInputValue('destinataire-nom', dest.nom);
    setInputValue('destinataire-adresse', dest.adresse_complete || '');
    setInputValue('destinataire-cp', dest.code_postal);
    setInputValue('destinataire-ville', dest.ville);
    setInputValue('destinataire-telephone', dest.telephone || '');
    setInputValue('destinataire-email', dest.email || '');
    
    // Mettre à jour l'état
    currentDestinataire = { ...dest };
    updateCurrentDestinataire();
    
    // Afficher les informations sélectionnées
    showSelectedDestinataire(dest);
    
    // Masquer suggestions
    hideDestinatairesSuggestions();
    
    // Incrémenter fréquence d'utilisation
    if (dest.id) {
        incrementDestinataireUsage(dest.id);
    }
    
    // Valider et activer l'étape suivante
    validateDestinataireRealTime();
    
    showNotification('✅ Destinataire sélectionné', 'success');
}

function showSelectedDestinataire(dest) {
    const selectedDiv = document.getElementById('selected-destinataire');
    const selectedInfo = document.getElementById('selected-destinataire-info');
    const newForm = document.getElementById('new-destinataire-form');
    
    if (selectedInfo) {
        selectedInfo.innerHTML = `
            <div><strong>${escapeHtml(dest.nom)}</strong></div>
            <div>${escapeHtml(dest.adresse_complete || 'Adresse non renseignée')}</div>
            <div><strong>${escapeHtml(dest.code_postal)} ${escapeHtml(dest.ville)}</strong> (${escapeHtml(dest.pays || 'France')})</div>
            ${dest.telephone ? `<div>📞 ${escapeHtml(dest.telephone)}</div>` : ''}
            ${dest.email ? `<div>📧 ${escapeHtml(dest.email)}</div>` : ''}
        `;
    }
    
    if (selectedDiv) selectedDiv.style.display = 'block';
    // Ne plus masquer le formulaire - il reste visible pour modification
}

function incrementDestinataireUsage(destId) {
    const formData = new FormData();
    formData.append('action', 'increment_destinataire_usage');
    formData.append('id', destId);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .catch(error => {
        console.log('Info: erreur incrémentation usage:', error.message);
    });
}

// ========== AUTOCOMPLÉTION VILLES ==========

function handleCodePostalInput() {
    const cpInput = document.getElementById('destinataire-cp');
    const cp = cpInput.value.trim();
    
    // Validation format code postal
    if (cp.length > 0 && !/^\d{0,5}$/.test(cp)) {
        cpInput.value = cp.replace(/\D/g, '').substring(0, 5);
        return;
    }
    
    if (cp.length >= 2) {
        searchVillesByCP(cp);
    } else {
        hideVillesSuggestions();
    }
    
    validateDestinataireRealTime();
}

function handleVilleInput() {
    const villeInput = document.getElementById('destinataire-ville');
    const ville = villeInput.value.trim();
    const cp = getInputValue('destinataire-cp');
    
    if (ville.length >= 2) {
        if (cp.length >= 2) {
            searchVillesByCPAndName(cp, ville);
        } else {
            searchVillesByName(ville);
        }
    } else if (cp.length >= 2) {
        searchVillesByCP(cp);
    } else {
        hideVillesSuggestions();
    }
    
    validateDestinataireRealTime();
}

function handleVilleFocus() {
    const cp = getInputValue('destinataire-cp');
    if (cp.length >= 2) {
        searchVillesByCP(cp);
    }
}

function handleVilleKeyNavigation(e) {
    const suggestions = document.querySelectorAll('#villes-suggestions .ville-suggestion');
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            navigateVilleSuggestions(suggestions, 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            navigateVilleSuggestions(suggestions, -1);
            break;
        case 'Enter':
            e.preventDefault();
            selectHighlightedVille(suggestions);
            break;
        case 'Escape':
            hideVillesSuggestions();
            break;
    }
}

function searchVillesByCP(cp) {
    console.log('🏘️ Recherche villes par CP:', cp);
    
    const cacheKey = `cp_${cp}`;
    if (villesCache.has(cacheKey)) {
        const cached = villesCache.get(cacheKey);
        if (Date.now() - cached.timestamp < CONFIG.cacheTimeout) {
            displayVillesSuggestions(cached.data);
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', 'search_villes_by_cp');
    formData.append('cp', cp);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            villesCache.set(cacheKey, {
                data: data.villes,
                timestamp: Date.now()
            });
            
            displayVillesSuggestions(data.villes);
        } else {
            hideVillesSuggestions();
        }
    })
    .catch(error => {
        console.error('Erreur recherche villes:', error);
        hideVillesSuggestions();
    });
}

function searchVillesByName(ville) {
    console.log('🏘️ Recherche villes par nom:', ville);
    
    const cacheKey = `ville_${ville.toLowerCase()}`;
    if (villesCache.has(cacheKey)) {
        const cached = villesCache.get(cacheKey);
        if (Date.now() - cached.timestamp < CONFIG.cacheTimeout) {
            displayVillesSuggestions(cached.data);
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', 'search_villes_by_name');
    formData.append('ville', ville);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            villesCache.set(cacheKey, {
                data: data.villes,
                timestamp: Date.now()
            });
            
            displayVillesSuggestions(data.villes);
        } else {
            hideVillesSuggestions();
        }
    })
    .catch(error => {
        console.error('Erreur recherche villes:', error);
        hideVillesSuggestions();
    });
}

function searchVillesByCPAndName(cp, ville) {
    console.log('🏘️ Recherche combinée CP + ville:', cp, ville);
    
    const cacheKey = `cp_ville_${cp}_${ville.toLowerCase()}`;
    if (villesCache.has(cacheKey)) {
        const cached = villesCache.get(cacheKey);
        if (Date.now() - cached.timestamp < CONFIG.cacheTimeout) {
            displayVillesSuggestions(cached.data);
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', 'search_villes_by_cp_and_name');
    formData.append('cp', cp);
    formData.append('ville', ville);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            villesCache.set(cacheKey, {
                data: data.villes,
                timestamp: Date.now()
            });
            
            displayVillesSuggestions(data.villes);
        } else {
            hideVillesSuggestions();
        }
    })
    .catch(error => {
        console.error('Erreur recherche villes:', error);
        hideVillesSuggestions();
    });
}

function displayVillesSuggestions(villes) {
    const container = document.getElementById('villes-suggestions');
    if (!container || !villes || villes.length === 0) {
        hideVillesSuggestions();
        return;
    }
    
    let html = '';
    
    // Limiter le nombre de suggestions
    const limitedVilles = villes.slice(0, CONFIG.maxSuggestions);
    
    limitedVilles.forEach((ville, index) => {
        html += `
            <div class="ville-suggestion" onclick="selectVille('${escapeJs(ville.code_postal)}', '${escapeJs(ville.ville)}')" data-index="${index}">
                <div class="ville-cp">${escapeHtml(ville.code_postal)}</div>
                <div class="ville-nom">${escapeHtml(ville.ville)}</div>
                ${ville.departement ? `<div class="ville-dept">(${escapeHtml(ville.departement)})</div>` : ''}
            </div>
        `;
    });
    
    container.innerHTML = html;
    container.style.display = 'block';
}

function hideVillesSuggestions() {
    setTimeout(() => {
        const container = document.getElementById('villes-suggestions');
        if (container) {
            container.style.display = 'none';
        }
    }, 150);
}

function selectVille(cp, ville) {
    console.log('🏘️ Ville sélectionnée:', cp, ville);
    
    setInputValue('destinataire-cp', cp);
    setInputValue('destinataire-ville', ville);
    
    hideVillesSuggestions();
    updateCurrentDestinataire();
    validateDestinataireRealTime();
    
    // Focus sur le champ suivant logique
    const adresseInput = document.getElementById('destinataire-adresse');
    if (adresseInput && !adresseInput.value.trim()) {
        adresseInput.focus();
    }
}

// ========== CRÉATION DESTINATAIRE ==========

function showCreateDestinataireForm() {
    console.log('➕ Le formulaire destinataire est déjà visible');
    
    hideDestinatairesSuggestions();
    
    const searchInput = document.getElementById('search-destinataire');
    const nomInput = document.getElementById('destinataire-nom');
    
    const nomValue = searchInput ? searchInput.value.trim() : '';
    
    // Pré-remplir le nom si une recherche a été effectuée
    if (nomValue && nomInput && !nomInput.value.trim()) {
        nomInput.value = nomValue;
    }
    
    // Focus sur le premier champ vide
    const cpInput = document.getElementById('destinataire-cp');
    const villeInput = document.getElementById('destinataire-ville');
    
    if (nomInput && !nomInput.value.trim()) {
        nomInput.focus();
    } else if (cpInput && !cpInput.value.trim()) {
        cpInput.focus();
    } else if (villeInput && !villeInput.value.trim()) {
        villeInput.focus();
    }
    
    showNotification('📝 Remplissez les informations du destinataire', 'info');
}

function saveNewDestinataire() {
    console.log('💾 Sauvegarde nouveau destinataire');
    
    updateCurrentDestinataire();
    
    if (!validateDestinataire(true)) {
        return false;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_destinataire');
    Object.keys(currentDestinataire).forEach(key => {
        if (key !== 'id') { // Exclure l'ID pour la création
            formData.append(key, currentDestinataire[key] || '');
        }
    });
    
    // Afficher état de chargement
    const saveBtn = document.querySelector('button[onclick="saveNewDestinataire()"]');
    const originalText = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.innerHTML = '<span class="loading-spinner"></span> Sauvegarde...';
        saveBtn.disabled = true;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Destinataire enregistré avec succès', 'success');
            
            // Mettre à jour avec l'ID retourné
            currentDestinataire.id = data.destinataire.id;
            
            // Nettoyer le cache pour forcer le rechargement
            destinatairesCache.clear();
            
            // Afficher comme sélectionné
            showSelectedDestinataire(data.destinataire);
            
            // Valider
            validateDestinataireRealTime();
            
            return true;
        } else {
            showNotification('❌ Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
            return false;
        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde:', error);
        showNotification('❌ Erreur lors de la sauvegarde', 'error');
        return false;
    })
    .finally(() => {
        // Restaurer le bouton
        if (saveBtn) {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
}

function cancelNewDestinataire() {
    const newForm = document.getElementById('new-destinataire-form');
    const searchInput = document.getElementById('search-destinataire');
    
    if (newForm) newForm.style.display = 'none';
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    
    // Réinitialiser les champs
    clearDestinataireForm();
}

function changeDestinataire() {
    currentDestinataire = {
        id: null,
        nom: '',
        adresse_complete: '',
        code_postal: '',
        ville: '',
        pays: 'France',
        telephone: '',
        email: ''
    };
    
    const selectedDiv = document.getElementById('selected-destinataire');
    const searchInput = document.getElementById('search-destinataire');
    const newForm = document.getElementById('new-destinataire-form');
    
    if (selectedDiv) selectedDiv.style.display = 'none';
    if (newForm) newForm.style.display = 'none';
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    
    clearDestinataireForm();
    validateDestinataireRealTime();
}

// ========== VALIDATION ==========

function initializeFormValidation() {
    // Validation en temps réel pour tous les champs requis
    const requiredFields = ['search-destinataire', 'destinataire-nom', 'destinataire-cp', 'destinataire-ville'];
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', validateDestinataireRealTime);
            field.addEventListener('blur', validateDestinataireRealTime);
        }
    });
}

function validateDestinataireRealTime() {
    updateCurrentDestinataire();
    
    const isValid = validateDestinataire(false);
    
    // Mettre à jour l'UI
    const nextButton = document.getElementById('btn-next-to-products');
    if (nextButton) {
        nextButton.disabled = !isValid;
    }
    
    // Indicateurs visuels
    updateDestinataireValidationUI(isValid);
    
    // Mettre à jour la progression
    if (typeof updateProgressInfo === 'function') {
        updateProgressInfo();
    }
    
    return isValid;
}

function validateDestinataire(showErrors = true) {
    const errors = [];
    
    if (!currentDestinataire.nom.trim()) {
        errors.push('Nom du destinataire obligatoire');
    }
    
    if (!currentDestinataire.code_postal.trim()) {
        errors.push('Code postal obligatoire');
    } else if (!/^\d{5}$/.test(currentDestinataire.code_postal.trim())) {
        errors.push('Code postal invalide (5 chiffres requis)');
    }
    
    if (!currentDestinataire.ville.trim()) {
        errors.push('Ville obligatoire');
    }
    
    // Validation email si renseigné
    if (currentDestinataire.email && !isValidEmail(currentDestinataire.email)) {
        errors.push('Format email invalide');
    }
    
    if (showErrors && errors.length > 0) {
        showNotification('❌ Erreurs de validation:\n• ' + errors.join('\n• '), 'error');
    }
    
    return errors.length === 0;
}

function updateDestinataireValidationUI(isValid) {
    // Validation visuelle par champ
    const fieldValidations = {
        'search-destinataire': () => currentDestinataire.nom.trim().length > 0,
        'destinataire-nom': () => currentDestinataire.nom.trim().length > 0,
        'destinataire-cp': () => /^\d{5}$/.test(currentDestinataire.code_postal.trim()),
        'destinataire-ville': () => currentDestinataire.ville.trim().length > 0,
        'destinataire-email': () => !currentDestinataire.email || isValidEmail(currentDestinataire.email)
    };
    
    Object.entries(fieldValidations).forEach(([inputId, validator]) => {
        const input = document.getElementById(inputId);
        if (input) {
            const isFieldValid = validator();
            input.classList.toggle('valid', isFieldValid && input.value.trim().length > 0);
            input.classList.toggle('invalid', !isFieldValid && input.value.trim().length > 0);
        }
    });
    
    // Indicateur global
    const indicator = document.getElementById('destinataire-status');
    if (indicator) {
        if (isValid) {
            indicator.textContent = '✅ Destinataire complet';
            indicator.className = 'status-success';
        } else {
            indicator.textContent = '📝 Destinataire incomplet';
            indicator.className = 'status-pending';
        }
    }
}

function updateCurrentDestinataire() {
    currentDestinataire = {
        ...currentDestinataire,
        nom: getInputValue('destinataire-nom') || getInputValue('search-destinataire'),
        adresse_complete: getInputValue('destinataire-adresse'),
        code_postal: getInputValue('destinataire-cp'),
        ville: getInputValue('destinataire-ville'),
        pays: getInputValue('destinataire-pays') || 'France',
        telephone: getInputValue('destinataire-telephone'),
        email: getInputValue('destinataire-email')
    };
}

// ========== NAVIGATION ET SÉLECTION ==========

let selectedSuggestionIndex = -1;
let selectedVilleIndex = -1;

function navigateSuggestions(suggestions, direction) {
    if (suggestions.length === 0) return;
    
    // Retirer la sélection actuelle
    suggestions.forEach(s => s.classList.remove('highlighted'));
    
    // Calculer le nouvel index
    selectedSuggestionIndex += direction;
    
    if (selectedSuggestionIndex < 0) {
        selectedSuggestionIndex = suggestions.length - 1;
    } else if (selectedSuggestionIndex >= suggestions.length) {
        selectedSuggestionIndex = 0;
    }
    
    // Appliquer la nouvelle sélection
    if (suggestions[selectedSuggestionIndex]) {
        suggestions[selectedSuggestionIndex].classList.add('highlighted');
        suggestions[selectedSuggestionIndex].scrollIntoView({
            block: 'nearest'
        });
    }
}

function navigateVilleSuggestions(suggestions, direction) {
    if (suggestions.length === 0) return;
    
    suggestions.forEach(s => s.classList.remove('highlighted'));
    
    selectedVilleIndex += direction;
    
    if (selectedVilleIndex < 0) {
        selectedVilleIndex = suggestions.length - 1;
    } else if (selectedVilleIndex >= suggestions.length) {
        selectedVilleIndex = 0;
    }
    
    if (suggestions[selectedVilleIndex]) {
        suggestions[selectedVilleIndex].classList.add('highlighted');
        suggestions[selectedVilleIndex].scrollIntoView({
            block: 'nearest'
        });
    }
}

function selectHighlightedSuggestion(suggestions) {
    if (selectedSuggestionIndex >= 0 && suggestions[selectedSuggestionIndex]) {
        const suggestion = suggestions[selectedSuggestionIndex];
        const action = suggestion.dataset.action;
        
        if (action === 'create') {
            showCreateDestinataireForm();
        } else {
            suggestion.click();
        }
    }
}

function selectHighlightedVille(suggestions) {
    if (selectedVilleIndex >= 0 && suggestions[selectedVilleIndex]) {
        suggestions[selectedVilleIndex].click();
    }
}

// ========== FONCTIONS UTILITAIRES ==========

function preloadCommonData() {
    // Charger les villes les plus fréquentes en cache
    const commonCPs = ['67000', '68000', '68100', '68170', '75001', '69000', '13000'];
    
    commonCPs.forEach((cp, index) => {
        setTimeout(() => {
            searchVillesByCP(cp);
        }, index * 100);
    });
}

function clearDestinataireForm() {
    const fields = [
        'search-destinataire', 'destinataire-nom', 'destinataire-adresse', 
        'destinataire-cp', 'destinataire-ville', 'destinataire-telephone', 
        'destinataire-email'
    ];
    
    fields.forEach(id => {
        setInputValue(id, '');
    });
    
    currentDestinataire = {
        id: null,
        nom: '',
        adresse_complete: '',
        code_postal: '',
        ville: '',
        pays: 'France',
        telephone: '',
        email: ''
    };
    
    updateDestinataireValidationUI(false);
    hideDestinatairesSuggestions();
    hideVillesSuggestions();
}

function getInputValue(id) {
    const element = document.getElementById(id);
    return element ? element.value.trim() : '';
}

function setInputValue(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.value = value || '';
        element.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n');
}

function escapeJson(obj) {
    return JSON.stringify(obj).replace(/"/g, '&quot;');
}

function showNotification(message, type = 'info') {
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Utiliser le système existant si disponible
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    
    // Fallback simple
    createNotification(message, type);
}

function createNotification(message, type) {
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
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
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        pointer-events: all;
        animation: slideIn 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        font-size: 0.9rem;
        line-height: 1.4;
    `;
    
    notification.innerHTML = `
        ${getNotificationIcon(type)}
        <span style="flex: 1; white-space: pre-line;">${message}</span>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 0.25rem;">
            ×
        </button>
    `;
    
    container.appendChild(notification);
    
    // Auto-suppression
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, type === 'error' ? 6000 : 4000);
}

function getNotificationColor(type) {
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
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

// ========== API PUBLIQUE ==========

// Exposer les fonctions nécessaires globalement
window.selectDestinataire = selectDestinataire;
window.showCreateDestinataireForm = showCreateDestinataireForm;
window.saveNewDestinataire = saveNewDestinataire;
window.cancelNewDestinataire = cancelNewDestinataire;
window.changeDestinataire = changeDestinataire;
window.selectVille = selectVille;

// Fonction pour récupérer l'état du destinataire (pour les autres modules)
window.getDestinataireData = function() {
    updateCurrentDestinataire();
    return {
        isValid: validateDestinataire(false),
        data: currentDestinataire
    };
};

// Fonction pour nettoyer le formulaire
window.clearDestinataireForm = clearDestinataireForm;

// Fonction pour valider manuellement
window.validateDestinataire = validateDestinataireRealTime;

// Ajouter les styles CSS si nécessaire
if (!document.getElementById('destinataire-styles')) {
    const style = document.createElement('style');
    style.id = 'destinataire-styles';
    style.textContent = `
        /* Styles spécifiques destinataire */
        .suggestion-item.highlighted,
        .ville-suggestion.highlighted {
            background: var(--adr-primary) !important;
            color: white !important;
            transform: translateX(4px);
        }
        
        .suggestion-item.highlighted .suggestion-title,
        .suggestion-item.highlighted .suggestion-details {
            color: white !important;
        }
        
        .form-control.valid {
            border-color: var(--adr-success);
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1);
        }
        
        .form-control.invalid {
            border-color: var(--adr-danger);
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }
        
        .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff40;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .status-success {
            color: var(--adr-success);
            font-weight: 600;
        }
        
        .status-pending {
            color: var(--adr-warning);
            font-weight: 600;
        }
        
        .status-error {
            color: var(--adr-danger);
            font-weight: 600;
        }
        
        /* Amélioration mobile */
        @media (max-width: 768px) {
            .suggestions-container {
                max-height: 250px;
            }
            
            .suggestion-item {
                padding: 0.75rem;
            }
            
            .ville-suggestion {
                padding: 0.5rem 0.75rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
            
            .ville-cp {
                min-width: auto;
            }
        }
        
        /* Mode sombre */
        @media (prefers-color-scheme: dark) {
            .suggestions-container {
                background: #2d3748;
                border-color: #4a5568;
            }
            
            .suggestion-item,
            .ville-suggestion {
                border-bottom-color: #4a5568;
                color: #f7fafc;
            }
            
            .suggestion-item:hover,
            .ville-suggestion:hover {
                background: #4a5568;
            }
        }
    `;
    document.head.appendChild(style);
}

// Debug et analytics
console.log('✅ Module destinataire ADR chargé avec succès');
console.log('🎯 Fonctionnalités: recherche, autocomplétion, validation, cache');

// Statistiques de session
window.addEventListener('beforeunload', function() {
    if (destinatairesCache.size > 0 || villesCache.size > 0) {
        console.log('📊 Stats cache destinataire:', {
            destinataires_cached: destinatairesCache.size,
            villes_cached: villesCache.size,
            current_destinataire: currentDestinataire,
            session_duration: Date.now() - window.adrStartTime
        });
    }
});

// Marquer le début de la session
window.adrStartTime = Date.now();

// Gestion des erreurs globales pour le module
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('adr-destinataire')) {
        console.error('🚨 Erreur module destinataire:', e.error);
        showNotification('❌ Erreur technique dans le module destinataire', 'error');
    }
});

// Auto-nettoyage du cache
setInterval(() => {
    const now = Date.now();
    
    // Nettoyer cache destinataires
    for (const [key, value] of destinatairesCache) {
        if (now - value.timestamp > CONFIG.cacheTimeout) {
            destinatairesCache.delete(key);
        }
    }
    
    // Nettoyer cache villes
    for (const [key, value] of villesCache) {
        if (now - value.timestamp > CONFIG.cacheTimeout) {
            villesCache.delete(key);
        }
    }
}, CONFIG.cacheTimeout);

// Export des fonctions pour tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateDestinataire,
        updateCurrentDestinataire,
        searchDestinataires,
        searchVillesByCP,
        isValidEmail,
        escapeHtml,
        escapeJs
    };
}
