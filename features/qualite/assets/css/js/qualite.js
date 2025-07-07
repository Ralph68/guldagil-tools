/**
 * Titre: JavaScript Principal - Module Contrôle Qualité
 * Chemin: /features/qualite/assets/js/qualite.js
 * Version: 0.5 beta + build auto
 */

// ========== CONFIGURATION MODULE ==========
const QualiteModule = {
    config: {
        moduleId: 'qualite',
        apiBaseUrl: '/features/qualite/api/',
        refreshInterval: 30000, // 30 secondes
        version: '0.5 beta',
        autoSave: true,
        autoSaveInterval: 30000
    },
    
    // État du module
    state: {
        currentSection: null,
        isLoading: false,
        lastUpdate: null,
        stats: {},
        notifications: [],
        activeModal: null,
        formData: {}
    },
    
    // Cache des données
    cache: {
        equipmentTypes: null,
        equipmentModels: {},
        agencies: null,
        lastCacheUpdate: null
    }
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔬 Initialisation module Contrôle Qualité');
    
    QualiteModule.init();
});

QualiteModule.init = function() {
    this.setupEventListeners();
    this.loadInitialData();
    this.startAutoRefresh();
    this.initAutoSave();
    
    console.log('✅ Module Contrôle Qualité initialisé');
};

// ========== GESTION DES ÉVÉNEMENTS ==========
QualiteModule.setupEventListeners = function() {
    // Gestion des modales
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            this.closeAllModals();
        }
    });
    
    // Échapper pour fermer modales
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.state.activeModal) {
            this.closeAllModals();
        }
    });
    
    // Sauvegarde automatique des formulaires
    document.addEventListener('input', (e) => {
        if (e.target.form && this.config.autoSave) {
            this.scheduleAutoSave(e.target.form);
        }
    });
    
    // Gestion des erreurs globales
    window.addEventListener('error', (e) => {
        this.handleError('Erreur JavaScript', e.error);
    });
};

// ========== CHARGEMENT DES DONNÉES ==========
QualiteModule.loadInitialData = function() {
    this.loadEquipmentTypes();
    this.loadAgencies();
    this.updateStats();
};

QualiteModule.loadEquipmentTypes = function() {
    if (this.cache.equipmentTypes && this.isCacheValid()) {
        return Promise.resolve(this.cache.equipmentTypes);
    }
    
    return this.apiCall('equipment-types')
        .then(data => {
            this.cache.equipmentTypes = data;
            this.cache.lastCacheUpdate = Date.now();
            return data;
        })
        .catch(error => {
            console.error('Erreur chargement types équipements:', error);
            this.showNotification('Erreur chargement des types d\'équipements', 'error');
        });
};

QualiteModule.loadEquipmentModels = function(typeId) {
    if (this.cache.equipmentModels[typeId]) {
        return Promise.resolve(this.cache.equipmentModels[typeId]);
    }
    
    return this.apiCall(`equipment-models/${typeId}`)
        .then(data => {
            this.cache.equipmentModels[typeId] = data;
            return data;
        })
        .catch(error => {
            console.error('Erreur chargement modèles:', error);
        });
};

QualiteModule.loadAgencies = function() {
    if (this.cache.agencies && this.isCacheValid()) {
        return Promise.resolve(this.cache.agencies);
    }
    
    return this.apiCall('agencies')
        .then(data => {
            this.cache.agencies = data;
            return data;
        })
        .catch(error => {
            console.error('Erreur chargement agences:', error);
        });
};

// ========== GESTION DES MODALES ==========
QualiteModule.showModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        this.state.activeModal = modalId;
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            modal.classList.add('modal-show');
        });
    }
};

QualiteModule.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('modal-show');
        setTimeout(() => {
            modal.style.display = 'none';
            if (this.state.activeModal === modalId) {
                this.state.activeModal = null;
                document.body.style.overflow = '';
            }
        }, 200);
    }
};

QualiteModule.closeAllModals = function() {
    if (this.state.activeModal) {
        this.closeModal(this.state.activeModal);
    }
};

// ========== NAVIGATION ==========
QualiteModule.navigateToSection = function(section) {
    this.state.currentSection = section;
    
    // Mettre à jour l'URL sans recharger
    const url = new URL(window.location);
    url.searchParams.set('action', section);
    window.history.pushState({}, '', url);
    
    this.updateNavigation(section);
};

QualiteModule.updateNavigation = function(activeSection) {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeItem = document.querySelector(`.nav-item[href*="${activeSection}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
};

// ========== NOTIFICATIONS ==========
QualiteModule.showNotification = function(message, type = 'info', duration = 5000) {
    const notification = {
        id: Date.now(),
        message,
        type,
        timestamp: new Date()
    };
    
    this.state.notifications.push(notification);
    this.renderNotification(notification);
    
    if (duration > 0) {
        setTimeout(() => {
            this.removeNotification(notification.id);
        }, duration);
    }
};

QualiteModule.renderNotification = function(notification) {
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.className = 'notifications-container';
        document.body.appendChild(container);
    }
    
    const notifElement = document.createElement('div');
    notifElement.className = `notification notification-${notification.type}`;
    notifElement.dataset.id = notification.id;
    notifElement.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${notification.message}</span>
            <button class="notification-close" onclick="QualiteModule.removeNotification(${notification.id})">×</button>
        </div>
    `;
    
    container.appendChild(notifElement);
    
    // Animation d'entrée
    requestAnimationFrame(() => {
        notifElement.classList.add('notification-show');
    });
};

QualiteModule.removeNotification = function(id) {
    const element = document.querySelector(`[data-id="${id}"]`);
    if (element) {
        element.classList.remove('notification-show');
        setTimeout(() => {
            element.remove();
        }, 300);
    }
    
    this.state.notifications = this.state.notifications.filter(n => n.id !== id);
};

// ========== SAUVEGARDE AUTOMATIQUE ==========
QualiteModule.initAutoSave = function() {
    if (!this.config.autoSave) return;
    
    setInterval(() => {
        this.performAutoSave();
    }, this.config.autoSaveInterval);
};

QualiteModule.scheduleAutoSave = function(form) {
    if (!form) return;
    
    clearTimeout(form._autoSaveTimeout);
    form._autoSaveTimeout = setTimeout(() => {
        this.saveFormData(form);
    }, 2000);
};

QualiteModule.saveFormData = function(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const formId = form.id || 'unnamed-form';
    
    try {
        localStorage.setItem(`qualite_form_${formId}`, JSON.stringify({
            data,
            timestamp: Date.now(),
            url: window.location.pathname
        }));
        
        this.showNotification('Données sauvegardées automatiquement', 'success', 2000);
    } catch (error) {
        console.warn('Impossible de sauvegarder automatiquement:', error);
    }
};

QualiteModule.restoreFormData = function(formId) {
    try {
        const saved = localStorage.getItem(`qualite_form_${formId}`);
        if (saved) {
            const { data, timestamp } = JSON.parse(saved);
            
            // Vérifier que les données ne sont pas trop anciennes (24h)
            if (Date.now() - timestamp < 24 * 60 * 60 * 1000) {
                return data;
            } else {
                localStorage.removeItem(`qualite_form_${formId}`);
            }
        }
    } catch (error) {
        console.warn('Erreur restauration données:', error);
    }
    
    return null;
};

// ========== VALIDATION ==========
QualiteModule.validateForm = function(form) {
    const errors = [];
    const warnings = [];
    
    // Validation des champs obligatoires
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            errors.push(`Le champ "${field.labels[0]?.textContent || field.name}" est obligatoire`);
            field.classList.add('field-error');
        } else {
            field.classList.remove('field-error');
        }
    });
    
    // Validations spécifiques
    const specificValidations = this.getSpecificValidations(form);
    errors.push(...specificValidations.errors);
    warnings.push(...specificValidations.warnings);
    
    return { errors, warnings };
};

QualiteModule.getSpecificValidations = function(form) {
    const errors = [];
    const warnings = [];
    const formData = new FormData(form);
    
    // Validations pour adoucisseurs
    if (form.dataset.equipmentType === 'adoucisseur') {
        const rawHardness = parseFloat(formData.get('raw_water_hardness'));
        const targetHardness = parseFloat(formData.get('target_hardness'));
        
        if (rawHardness && targetHardness && targetHardness >= rawHardness) {
            errors.push('Le TH à obtenir doit être inférieur au TH de l\'eau brute');
        }
        
        const resinVolume = parseFloat(formData.get('resin_volume'));
        const exchangeCapacity = parseFloat(formData.get('exchange_capacity'));
        
        if (resinVolume && exchangeCapacity) {
            const expectedCapacity = resinVolume * 6;
            if (Math.abs(exchangeCapacity - expectedCapacity) > expectedCapacity * 0.3) {
                warnings.push('La capacité d\'échange semble incohérente avec le volume de résine');
            }
        }
    }
    
    // Validations pour pompes
    if (form.dataset.equipmentType === 'pompe') {
        const maxFlow = parseFloat(formData.get('debit_max'));
        const realFlow = parseFloat(formData.get('debit_reel'));
        
        if (maxFlow && realFlow && realFlow > maxFlow) {
            errors.push('Le débit réel ne peut pas dépasser le débit maximum');
        }
    }
    
    return { errors, warnings };
};

// ========== CALCULS AUTOMATIQUES ==========
QualiteModule.performAutomaticCalculations = function(form) {
    const formData = new FormData(form);
    const equipmentType = form.dataset.equipmentType;
    
    if (equipmentType === 'adoucisseur') {
        this.calculateAdoucisseurParams(form, formData);
    } else if (equipmentType === 'pompe') {
        this.calculatePompeParams(form, formData);
    }
};

QualiteModule.calculateAdoucisseurParams = function(form, formData) {
    const rawHardness = parseFloat(formData.get('raw_water_hardness'));
    const targetHardness = parseFloat(formData.get('target_hardness'));
    const dailyTotal = parseFloat(formData.get('daily_consumption_total'));
    const exchangeCapacity = parseFloat(formData.get('exchange_capacity'));
    const saltPerRegen = parseFloat(formData.get('salt_consumption_per_regen'));
    
    if (rawHardness && targetHardness && dailyTotal) {
        // Calcul consommation à 0°F
        const daily0F = dailyTotal * ((rawHardness - targetHardness) / rawHardness);
        this.updateFormField(form, 'daily_consumption_0f', daily0F.toFixed(2));
        
        if (exchangeCapacity) {
            // Volume max entre régénérations
            const maxVolume = exchangeCapacity / (rawHardness - targetHardness);
            this.updateCalculationDisplay(form, 'calc-max-volume', maxVolume.toFixed(2));
            
            // Régénérations par an
            const annualRegen = Math.ceil((daily0F * 365) / maxVolume);
            this.updateCalculationDisplay(form, 'calc-annual-regen', annualRegen);
            
            if (saltPerRegen) {
                // Consommation annuelle sel
                const annualSalt = saltPerRegen * annualRegen;
                this.updateCalculationDisplay(form, 'calc-annual-salt', annualSalt.toFixed(0));
            }
        }
    }
};

QualiteModule.calculatePompeParams = function(form, formData) {
    const maxFlow = parseFloat(formData.get('debit_max'));
    const percentage = parseFloat(formData.get('pourcentage_reglage'));
    
    if (maxFlow && percentage) {
        const realFlow = maxFlow * (percentage / 100);
        this.updateFormField(form, 'debit_reel', realFlow.toFixed(2));
        
        const concentration = parseFloat(formData.get('concentration_produit'));
        if (concentration) {
            const consumption = realFlow * concentration;
            this.updateCalculationDisplay(form, 'calc-consumption', consumption.toFixed(3));
        }
    }
};

QualiteModule.updateFormField = function(form, fieldName, value) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (field) {
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }
};

QualiteModule.updateCalculationDisplay = function(form, elementId, value) {
    const element = form.querySelector(`#${elementId}`);
    if (element) {
        element.textContent = value;
        element.parentElement.style.display = 'block';
    }
};

// ========== API ET REQUÊTES ==========
QualiteModule.apiCall = function(endpoint, options = {}) {
    const url = this.config.apiBaseUrl + endpoint;
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    return fetch(url, { ...defaultOptions, ...options })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Erreur API:', error);
            throw error;
        });
};

QualiteModule.updateStats = function() {
    return this.apiCall('stats')
        .then(stats => {
            this.state.stats = stats;
            this.renderStats(stats);
        })
        .catch(error => {
            console.error('Erreur mise à jour statistiques:', error);
        });
};

QualiteModule.renderStats = function(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = stats[key];
        }
    });
};

// ========== UTILITAIRES ==========
QualiteModule.isCacheValid = function() {
    const cacheAge = Date.now() - (this.cache.lastCacheUpdate || 0);
    return cacheAge < 300000; // 5 minutes
};

QualiteModule.clearCache = function() {
    this.cache = {
        equipmentTypes: null,
        equipmentModels: {},
        agencies: null,
        lastCacheUpdate: null
    };
};

QualiteModule.handleError = function(title, error) {
    console.error(title + ':', error);
    this.showNotification(`${title}: ${error.message}`, 'error');
};

QualiteModule.startAutoRefresh = function() {
    setInterval(() => {
        this.updateStats();
    }, this.config.refreshInterval);
};

QualiteModule.formatDate = function(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
};

QualiteModule.formatNumber = function(number, decimals = 2) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
};

// ========== FONCTIONS GLOBALES ==========
window.showQuickActions = function() {
    QualiteModule.showModal('quickActionsModal');
};

window.initQualiteModule = function() {
    QualiteModule.init();
};

window.closeModal = function(modalId) {
    QualiteModule.closeModal(modalId);
};

// Styles CSS pour les notifications (ajouté dynamiquement)
const notificationStyles = `
.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.notification {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 10px;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.notification-show {
    transform: translateX(0);
    opacity: 1;
}

.notification-content {
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-info {
    border-left: 4px solid #3b82f6;
}

.notification-success {
    border-left: 4px solid #22c55e;
}

.notification-warning {
    border-left: 4px solid #f59e0b;
}

.notification-error {
    border-left: 4px solid #ef4444;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
