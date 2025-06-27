// public/assets/js/adr-destinataire.js - Gestion destinataire simplifiée
console.log('📫 Chargement module destinataire ADR...');

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
    nom: '',
    adresse: '',
    code_postal: '',
    ville: '',
    pays: 'France',
    telephone: '',
    email: ''
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module destinataire initialisé');
    initializeDestinataireForm();
});

function initializeDestinataireForm() {
    setupDestinataireEventListeners();
    preloadCommonData();
}

function setupDestinataireEventListeners() {
    // Recherche globale destinataire
    const nomInput = document.getElementById('destinataire-nom');
    if (nomInput) {
        nomInput.addEventListener('input', handleDestinataireSearch);
        nomInput.addEventListener('focus', () => {
            if (nomInput.value.length >= CONFIG.minSearchChars) {
                handleDestinataireSearch();
            }
        });
        nomInput.addEventListener('blur', () => {
            setTimeout(hideDestinatairesSuggestions, 150);
        });
    }

    // Code postal -> recherche villes
    const cpInput = document.getElementById('destinataire-cp');
    if (cpInput) {
        cpInput.addEventListener('input', handleCodePostalInput);
        cpInput.addEventListener('blur', () => {
            setTimeout(hideVillesSuggestions, 150);
        });
    }

    // Ville -> recherche CP/ville
    const villeInput = document.getElementById('destinataire-ville');
    if (villeInput) {
        villeInput.addEventListener('input', handleVilleInput);
        villeInput.addEventListener('focus', () => {
            const cp = getInputValue('destinataire-cp');
            if (cp.length >= 2) {
                searchVillesByCP(cp);
            }
        });
        villeInput.addEventListener('blur', () => {
            setTimeout(hideVillesSuggestions, 150);
        });
    }

    // Validation en temps réel
    ['destinataire-nom', 'destinataire-cp', 'destinataire-ville'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', validateDestinataireRealTime);
            input.addEventListener('blur', updateCurrentDestinataire);
        }
    });

    // Autres champs
    ['destinataire-adresse', 'destinataire-telephone', 'destinataire-email'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('blur', updateCurrentDestinataire);
        }
    });
}

// ========== RECHERCHE DESTINATAIRES ==========

function handleDestinataireSearch() {
    const nomInput = document.getElementById('destinataire-nom');
    const query = nomInput.value.trim();
    
    if (query.length < CONFIG.minSearchChars) {
        hideDestinatairesSuggestions();
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchDestinataires(query);
    }, CONFIG.searchDelay);
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
    
    // Recherche API
    fetch('create.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=search_destinataires&query=${encodeURIComponent(query)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre en cache
            destinatairesCache.set(cacheKey, {
                data: data.destinataires,
                timestamp: Date.now()
            });
            
            displayDestinatairesSuggestions(data.destinataires);
        } else {
            hideDestinatairesSuggestions();
        }
    })
    .catch(error => {
        console.error('Erreur recherche destinataires:', error);
        hideDestinatairesSuggestions();
    });
}

function displayDestinatairesSuggestions(destinataires) {
    const container = document.getElementById('destinataires-suggestions');
    if (!container) return;
    
    let html = '';
    
    if (destinataires.length === 0) {
        html = `
            <div class="suggestion-item create-new" onclick="showCreateDestinataireForm()">
                <div class="suggestion-header">
                    <span class="suggestion-icon">➕</span>
                    <span class="suggestion-title">Créer ce destinataire</span>
                </div>
                <div class="suggestion-details">Enregistrer pour les prochaines expéditions</div>
            </div>
        `;
    } else {
        destinataires.forEach(dest => {
            html += `
                <div class="suggestion-item" onclick="selectDestinataire(${escapeJson(dest)})">
                    <div class="suggestion-header">
                        <span class="suggestion-icon">📍</span>
                        <span class="suggestion-title">${escapeHtml(dest.nom)}</span>
                        <span class="suggestion-frequency">×${dest.frequence_utilisation}</span>
                    </div>
                    <div class="suggestion-details">
                        ${escapeHtml(dest.adresse_complete || 'Adresse non renseignée')}<br>
                        <strong>${escapeHtml(dest.code_postal)} ${escapeHtml(dest.ville)}</strong>
                    </div>
                </div>
            `;
        });
        
        html += `
            <div class="suggestion-item create-new" onclick="showCreateDestinataireForm()">
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
    const container = document.getElementById('destinataires-suggestions');
    if (container) {
        container.style.display = 'none';
    }
}

function selectDestinataire(dest) {
    console.log('📍 Destinataire sélectionné:', dest);
    
    // Remplir le formulaire
    setInputValue('destinataire-nom', dest.nom);
    setInputValue('destinataire-adresse', dest.adresse_complete || '');
    setInputValue('destinataire-cp', dest.code_postal);
    setInputValue('destinataire-ville', dest.ville);
    setInputValue('destinataire-telephone', dest.telephone || '');
    setInputValue('destinataire-email', dest.email || '');
    
    // Mettre à jour l'état
    updateCurrentDestinataire();
    
    // Masquer suggestions
    hideDestinatairesSuggestions();
    
    // Incrémenter fréquence d'utilisation
    incrementDestinataireUsage(dest.id);
    
    // Valider le formulaire
    validateDestinataireRealTime();
    
    showNotification('✅ Destinataire sélectionné', 'success');
}

function incrementDestinataireUsage(destId) {
    fetch('create.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=increment_destinataire_usage&id=${destId}`
    })
    .catch(error => {
        console.log('Info: erreur incrémentation usage:', error.message);
    });
}

// ========== AUTOCOMPLÉTION VILLES ==========

function handleCodePostalInput() {
    const cpInput = document.getElementById('destinataire-cp');
    const cp = cpInput.value.trim();
    
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
            // Recherche combinée CP + ville
            searchVillesByCPAndName(cp, ville);
        } else {
            // Recherche par ville seule
            searchVillesByName(ville);
        }
    } else if (cp.length >= 2) {
        // CP saisi mais ville vide -> suggestions par CP
        searchVillesByCP(cp);
    } else {
        hideVillesSuggestions();
    }
    
    validateDestinataireRealTime();
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
    
    fetch('create.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=search_villes_by_cp&cp=${encodeURIComponent(cp)}`
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
    
    fetch('create.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=search_villes_by_name&ville=${encodeURIComponent(ville)}`
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
    console.log('🏘️ Recherche villes par CP + nom:', cp, ville);
    
    const cacheKey = `cp_ville_${cp}_${ville.toLowerCase()}`;
    if (villesCache.has(cacheKey)) {
        const cached = villesCache.get(cacheKey);
        if (Date.now() - cached.timestamp < CONFIG.cacheTimeout) {
            displayVillesSuggestions(cached.data);
            return;
        }
    }
    
    fetch('create.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=search_villes_by_cp_and_name&cp=${encodeURIComponent(cp)}&ville=${encodeURIComponent(ville)}`
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
    
    limitedVilles.forEach(ville => {
        html += `
            <div class="ville-suggestion" onclick="selectVille('${escapeJs(ville.code_postal)}', '${escapeJs(ville.ville)}')">
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
    const container = document.getElementById('villes-suggestions');
    if (container) {
        container.style.display = 'none';
    }
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
    console.log('➕ Affichage formulaire nouveau destinataire');
    
    hideDestinatairesSuggestions();
    
    // Pré-remplir avec la recherche actuelle
    const nomInput = document.getElementById('destinataire-nom');
    const nomValue = nomInput.value.trim();
    
    if (!nomValue) {
        showNotification('❌ Saisissez d\'abord un nom pour le destinataire', 'warning');
        nomInput.focus();
        return;
    }
    
    // Activer le mode "création"
    document.body.classList.add('creating-destinataire');
    
    // Focus sur le premier champ vide obligatoire
    const cpInput = document.getElementById('destinataire-cp');
    const villeInput = document.getElementById('destinataire-ville');
    
    if (!cpInput.value.trim()) {
        cpInput.focus();
    } else if (!villeInput.value.trim()) {
        villeInput.focus();
    }
    
    showNotification('📝 Complétez le formulaire pour enregistrer ce destinataire', 'info');
}

function saveDestinataire() {
    console.log('💾 Sauvegarde destinataire');
    
    updateCurrentDestinataire();
    
    if (!validateDestinataire()) {
        return false;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_destinataire');
    Object.keys(currentDestinataire).forEach(key => {
        formData.append(key, currentDestinataire[key]);
    });
    
    fetch('create.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Destinataire enregistré avec succès', 'success');
            
            // Nettoyer le cache pour forcer le rechargement
            destinatairesCache.clear();
            
            // Désactiver le mode création
            document.body.classList.remove('creating-destinataire');
            
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
    });
}

// ========== VALIDATION ==========

function validateDestinataireRealTime() {
    updateCurrentDestinataire();
    
    const isValid = validateDestinataire(false);
    
    // Mettre à jour l'UI
    const nextButton = document.getElementById('btn-next-to-products');
    if (nextButton) {
        nextButton.disabled = !isValid;
    }
    
    // Indicateur visuel
    updateDestinataireValidationUI(isValid);
    
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
        errors.push('Code postal invalide (5 chiffres)');
    }
    
    if (!currentDestinataire.ville.trim()) {
        errors.push('Ville obligatoire');
    }
    
    if (showErrors && errors.length > 0) {
        showNotification('❌ Erreurs:\n• ' + errors.join('\n• '), 'error');
    }
    
    return errors.length === 0;
}

function updateDestinataireValidationUI(isValid) {
    const formGroups = document.querySelectorAll('.destinataire-form-group');
    
    // Validation visuelle par champ
    ['destinataire-nom', 'destinataire-cp', 'destinataire-ville'].forEach(inputId => {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        const value = input.value.trim();
        const isFieldValid = value.length > 0;
        
        // Validation spécifique CP
        if (inputId === 'destinataire-cp' && value) {
            const isValidCP = /^\d{5}$/.test(value);
            input.classList.toggle('valid', isValidCP);
            input.classList.toggle('invalid', !isValidCP);
        } else {
            input.classList.toggle('valid', isFieldValid);
            input.classList.toggle('invalid', !isFieldValid);
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
        nom: getInputValue('destinataire-nom'),
        adresse: getInputValue('destinataire-adresse'),
        code_postal: getInputValue('destinataire-cp'),
        ville: getInputValue('destinataire-ville'),
        pays: getInputValue('destinataire-pays') || 'France',
        telephone: getInputValue('destinataire-telephone'),
        email: getInputValue('destinataire-email')
    };
}

// ========== FONCTIONS UTILITAIRES ==========

function preloadCommonData() {
    // Charger les villes les plus fréquentes en cache
    const commonCPs = ['67000', '68000', '68100', '75001', '69000', '13000'];
    
    commonCPs.forEach(cp => {
        setTimeout(() => {
            searchVillesByCP(cp);
        }, Math.random() * 1000);
    });
}

function getInputValue(id) {
    const element = document.getElementById(id);
    return element ? element.value.trim() : '';
}

function setInputValue(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.value = value || '';
        
        // Déclencher l'événement input pour les validations
        element.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function escapeJson(obj) {
    return JSON.stringify(obj).replace(/"/g, '&quot;');
}

function showNotification(message, type = 'info') {
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Utiliser le système de notifications existant si disponible
    if (typeof window.showAlert === 'function') {
        window.showAlert(type, message);
        return;
    }
    
    // Fallback notification simple
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
    `;
    
    notification.innerHTML = `
        ${getNotificationIcon(type)}
        <span style="flex: 1;">${message}</span>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 0.25rem;">
            ×
        </button>
    `;
    
    container.appendChild(notification);
    
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
window.selectVille = selectVille;
window.saveDestinataire = saveDestinataire;
window.validateDestinataireRealTime = validateDestinataireRealTime;
window.updateCurrentDestinataire = updateCurrentDestinataire;

// Fonction pour récupérer l'état du destinataire (pour les autres modules)
window.getDestinataireData = function() {
    updateCurrentDestinataire();
    return {
        isValid: validateDestinataire(false),
        data: currentDestinataire
    };
};

// Fonction pour nettoyer le formulaire
window.clearDestinataireForm = function() {
    ['destinataire-nom', 'destinataire-adresse', 'destinataire-cp', 
     'destinataire-ville', 'destinataire-telephone', 'destinataire-email'].forEach(id => {
        setInputValue(id, '');
    });
    
    currentDestinataire = {
        nom: '', adresse: '', code_postal: '', ville: '', 
        pays: 'France', telephone: '', email: ''
    };
    
    updateDestinataireValidationUI(false);
    hideDestinatairesSuggestions();
    hideVillesSuggestions();
    
    document.body.classList.remove('creating-destinataire');
};

// Ajouter les styles CSS nécessaires
if (!document.getElementById('destinataire-styles')) {
    const style = document.createElement('style');
    style.id = 'destinataire-styles';
    style.textContent = `
        /* Styles pour autocomplétion destinataire */
        .destinataire-search-container {
            position: relative;
        }

        #destinataires-suggestions,
        #villes-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            display: none;
        }

        .suggestion-item {
            padding: 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
        }

        .suggestion-item:hover {
            background: #f8f9fa;
            transform: translateX(4px);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item.create-new {
            background: #e3f2fd;
            border-top: 2px solid #2196f3;
        }

        .suggestion-item.create-new:hover {
            background: #bbdefb;
        }

        .suggestion-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .suggestion-icon {
            font-size: 1.2rem;
        }

        .suggestion-title {
            font-weight: 600;
            color: #ff6b35;
            flex: 1;
        }

        .suggestion-frequency {
            background: #ff6b35;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .suggestion-details {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.3;
        }

        /* Suggestions villes */
        .ville-suggestion {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .ville-suggestion:hover {
            background: #f8f9fa;
            transform: translateX(4px);
        }

        .ville-cp {
            font-weight: 600;
            color: #ff6b35;
            min-width: 60px;
        }

        .ville-nom {
            font-weight: 500;
            flex: 1;
        }

        .ville-dept {
            font-size: 0.8rem;
            color: #666;
        }

        /* États de validation */
        .form-control.valid {
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1);
        }

        .form-control.invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }

        /* Indicateur de statut */
        .status-success {
            color: #28a745;
            font-weight: 600;
        }

        .status-pending {
            color: #ffc107;
            font-weight: 600;
        }

        /* Mode création */
        .creating-destinataire .destinataire-form-group {
            animation: highlight 0.5s ease;
        }

        @keyframes highlight {
            0% { background: transparent; }
            50% { background: rgba(255, 107, 53, 0.1); }
            100% { background: transparent; }
        }

        /* Animations */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            #destinataires-suggestions,
            #villes-suggestions {
                max-height: 200px;
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
    `;
    document.head.appendChild(style);
}

console.log('✅ Module destinataire ADR chargé avec succès');
console.log('🎯 Fonctions disponibles: recherche, autocomplétion, validation');

// Debug et analytics
window.addEventListener('beforeunload', function() {
    console.log('📊 Cache stats:', {
        destinataires_cached: destinatairesCache.size,
        villes_cached: villesCache.size,
        current_destinataire: currentDestinataire
    });
});
