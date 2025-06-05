// public/adr/assets/js/adr-create-expedition.js - Gestion création expédition ADR
console.log('🚚 Chargement module création expédition ADR...');

// Variables globales
let currentStep = 'destinataire';
let expeditionProducts = [];
let quotasData = null;
let availableProducts = [];

// Configuration
const CONFIG = {
    searchDelay: 300,
    minSearchChars: 2,
    maxSuggestions: 10,
    quotaMaxDefault: 1000,
    autoSaveInterval: 30000, // 30 secondes
    maxProductsPerExpedition: 50
};

// État de l'expédition
let expeditionData = {
    destinataire: null,
    transporteur: '',
    date_expedition: '',
    observations: '',
    products: [],
    total_points_adr: 0,
    numero_expedition: null,
    created_at: null
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Initialisation création expédition ADR');
    initializeExpeditionForm();
});

function initializeExpeditionForm() {
    setupEventListeners();
    loadAvailableProducts();
    updateStepDisplay();
    initializeAutoSave();
    loadDraftIfExists();
}

function setupEventListeners() {
    // Transporteur et date
    const transporteurSelect = document.getElementById('expedition-transporteur');
    const dateInput = document.getElementById('expedition-date');
    
    if (transporteurSelect) {
        transporteurSelect.addEventListener('change', handleTransporteurChange);
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', handleDateChange);
    }

    // Produits
    const produitCodeInput = document.getElementById('produit-code');
    const produitQuantiteInput = document.getElementById('produit-quantite');
    
    if (produitCodeInput) {
        produitCodeInput.addEventListener('input', handleProductCodeInput);
        produitCodeInput.addEventListener('change', loadProductInfo);
    }
    
    if (produitQuantiteInput) {
        produitQuantiteInput.addEventListener('input', updatePointsCalculation);
    }

    // Navigation étapes
    setupStepNavigation();
    
    // Raccourcis clavier
    setupKeyboardShortcuts();
}

function setupStepNavigation() {
    // Clics sur les étapes
    document.querySelectorAll('.step').forEach(step => {
        step.addEventListener('click', function() {
            const stepName = this.getAttribute('data-step');
            if (stepName && !this.classList.contains('disabled')) {
                showStep(stepName);
            }
        });
    });
    
    // Boutons de navigation
    const nextToProductsBtn = document.getElementById('btn-next-to-products');
    const nextToValidationBtn = document.getElementById('btn-next-to-validation');
    
    if (nextToProductsBtn) {
        nextToProductsBtn.addEventListener('click', nextToProducts);
    }
    
    if (nextToValidationBtn) {
        nextToValidationBtn.addEventListener('click', nextToValidation);
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S pour sauvegarder brouillon
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveAsDraft();
        }
        
        // Ctrl/Cmd + Enter pour créer expédition (si à l'étape validation)
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && currentStep === 'validation') {
            e.preventDefault();
            createExpedition();
        }
        
        // Navigation avec Tab améliorée
        if (e.key === 'Tab') {
            handleTabNavigation(e);
        }
    });
}

// ========== GESTION DES ÉTAPES ==========

function showStep(stepName) {
    console.log('📍 Navigation vers étape:', stepName);
    
    // Validation avant changement d'étape
    if (!canNavigateToStep(stepName)) {
        showNotification('❌ Veuillez compléter l\'étape actuelle avant de continuer', 'warning');
        return;
    }
    
    currentStep = stepName;
    
    // Masquer tous les contenus
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Afficher le contenu demandé
    const targetContent = document.getElementById(`step-${stepName}`);
    if (targetContent) {
        targetContent.classList.add('active');
        targetContent.classList.add('fade-in');
    }
    
    // Actions spécifiques par étape
    switch (stepName) {
        case 'destinataire':
            focusDestinataireSearch();
            break;
        case 'products':
            focusProductInput();
            updateQuotas();
            break;
        case 'validation':
            generateExpeditionSummary();
            break;
    }
    
    updateStepDisplay();
    updateProgressInfo();
}

function canNavigateToStep(stepName) {
    switch (stepName) {
        case 'destinataire':
            return true;
        case 'products':
            return isDestinataireValid();
        case 'validation':
            return isDestinataireValid() && 
                   expeditionProducts.length > 0 && 
                   getInputValue('expedition-transporteur') && 
                   getInputValue('expedition-date');
        default:
            return false;
    }
}

function updateStepDisplay() {
    document.querySelectorAll('.step').forEach(step => {
        const stepData = step.getAttribute('data-step');
        
        step.classList.remove('active', 'completed', 'disabled');
        
        if (stepData === currentStep) {
            step.classList.add('active');
        } else if (isStepCompleted(stepData)) {
            step.classList.add('completed');
        } else if (!isStepAccessible(stepData)) {
            step.classList.add('disabled');
        }
    });
}

function isStepCompleted(stepName) {
    switch (stepName) {
        case 'destinataire':
            return isDestinataireValid();
        case 'products':
            return expeditionProducts.length > 0 && 
                   getInputValue('expedition-transporteur') && 
                   getInputValue('expedition-date');
        case 'validation':
            return false; // Jamais complètement terminée tant qu'on n'a pas créé
        default:
            return false;
    }
}

function isStepAccessible(stepName) {
    switch (stepName) {
        case 'destinataire':
            return true;
        case 'products':
            return isDestinataireValid();
        case 'validation':
            return isDestinataireValid() && 
                   expeditionProducts.length > 0 &&
                   getInputValue('expedition-transporteur') &&
                   getInputValue('expedition-date');
        default:
            return false;
    }
}

function isDestinataireValid() {
    if (typeof window.getDestinataireData === 'function') {
        const destData = window.getDestinataireData();
        return destData.isValid;
    }
    return false;
}

// ========== NAVIGATION ÉTAPES ==========

function nextToProducts() {
    if (!isDestinataireValid()) {
        showNotification('❌ Veuillez sélectionner ou créer un destinataire', 'error');
        return;
    }
    
    showStep('products');
}

function nextToValidation() {
    if (expeditionProducts.length === 0) {
        showNotification('❌ Veuillez ajouter au moins un produit', 'error');
        return;
    }
    
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    
    if (!transporteur || !date) {
        showNotification('❌ Veuillez sélectionner un transporteur et une date', 'error');
        return;
    }
    
    showStep('validation');
}

function backToDestinataire() {
    showStep('destinataire');
}

function backToProducts() {
    showStep('products');
}

// ========== GESTION PRODUITS ==========

function loadAvailableProducts() {
    console.log('📦 Chargement produits disponibles...');
    
    const formData = new FormData();
    formData.append('action', 'search_products');
    formData.append('query', '');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            availableProducts = data.products || [];
            populateProductsList();
            console.log('✅ Produits chargés:', availableProducts.length);
        } else {
            console.error('Erreur chargement produits:', data.error);
            loadDemoProducts();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        loadDemoProducts();
    });
}

function loadDemoProducts() {
    console.log('📦 Chargement produits de démonstration');
    
    availableProducts = [
        { 
            code_produit: 'GUL-001', 
            designation: 'GULTRAT pH+', 
            numero_onu: 'UN1823', 
            points_adr_par_unite: 1,
            categorie_transport: '8'
        },
        { 
            code_produit: 'GUL-002', 
            designation: 'PERFORMAX', 
            numero_onu: 'UN3265', 
            points_adr_par_unite: 2,
            categorie_transport: '3'
        },
        { 
            code_produit: 'GUL-003', 
            designation: 'ALKADOSE', 
            numero_onu: 'UN1824', 
            points_adr_par_unite: 1,
            categorie_transport: '8'
        },
        { 
            code_produit: 'GUL-004', 
            designation: 'CHLORE LIQUIDE', 
            numero_onu: 'UN1791', 
            points_adr_par_unite: 3,
            categorie_transport: '2'
        }
    ];
    
    populateProductsList();
}

function populateProductsList() {
    const datalist = document.getElementById('produits-list');
    if (!datalist) return;
    
    datalist.innerHTML = '';
    
    availableProducts.forEach(product => {
        const option = document.createElement('option');
        option.value = product.code_produit;
        option.textContent = `${product.code_produit} - ${product.designation || 'Sans nom'}`;
        datalist.appendChild(option);
    });
}

function handleProductCodeInput() {
    const codeInput = document.getElementById('produit-code');
    if (!codeInput) return;
    
    const code = codeInput.value.trim();
    if (code.length >= CONFIG.minSearchChars) {
        debounce(() => loadProductInfo(), CONFIG.searchDelay);
    }
}

function loadProductInfo() {
    const codeInput = document.getElementById('produit-code');
    if (!codeInput) return;
    
    const code = codeInput.value.trim();
    if (!code) {
        clearProductFields();
        return;
    }
    
    // Rechercher dans les produits chargés
    const product = availableProducts.find(p => 
        p.code_produit.toLowerCase() === code.toLowerCase()
    );
    
    if (product) {
        updateProductFields(product);
        return;
    }
    
    // Appel API pour un produit spécifique
    const formData = new FormData();
    formData.append('action', 'get_product_info');
    formData.append('code', code);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProductFields(data.product);
        } else {
            clearProductFields();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        clearProductFields();
    });
}

function updateProductFields(product) {
    setInputValue('produit-designation', product.designation || '');
    setInputValue('produit-numero-onu', product.numero_onu || '');
    
    window.currentProductPoints = parseFloat(product.points_adr_par_unite) || 1;
    window.currentProductCategory = product.categorie_transport || '0';
    
    updatePointsCalculation();
    
    // Focus sur quantité si les champs sont remplis
    const quantiteInput = document.getElementById('produit-quantite');
    if (quantiteInput && product.designation) {
        quantiteInput.focus();
    }
}

function clearProductFields() {
    setInputValue('produit-designation', '');
    setInputValue('produit-numero-onu', '');
    
    window.currentProductPoints = 0;
    window.currentProductCategory = '0';
    
    updatePointsCalculation();
}

function updatePointsCalculation() {
    const quantiteInput = document.getElementById('produit-quantite');
    if (!quantiteInput) return;
    
    const quantite = parseFloat(quantiteInput.value) || 0;
    const points = quantite * (window.currentProductPoints || 0);
    
    // Afficher le calcul en temps réel (optionnel)
    const calcDisplay = document.getElementById('points-calculation-display');
    if (calcDisplay) {
        calcDisplay.textContent = `${quantite} × ${window.currentProductPoints || 0} = ${points.toFixed(1)} points`;
    }
}

function addProductToExpedition() {
    console.log('➕ Ajout produit à l\'expédition');
    
    if (expeditionProducts.length >= CONFIG.maxProductsPerExpedition) {
        showNotification(`❌ Maximum ${CONFIG.maxProductsPerExpedition} produits par expédition`, 'error');
        return;
    }
    
    const code = getInputValue('produit-code');
    const designation = getInputValue('produit-designation');
    const numeroOnu = getInputValue('produit-numero-onu');
    const quantite = parseFloat(getInputValue('produit-quantite')) || 0;
    
    // Validation complète
    const validation = validateProductInput(code, designation, numeroOnu, quantite);
    if (!validation.isValid) {
        showNotification('❌ ' + validation.errors.join('\n'), 'error');
        return;
    }
    
    // Vérifier doublons
    const existingProduct = expeditionProducts.find(p => p.code.toLowerCase() === code.toLowerCase());
    if (existingProduct) {
        if (confirm(`⚠️ Le produit ${code} est déjà dans l'expédition.\n\nVoulez-vous additionner les quantités ?`)) {
            existingProduct.quantite += quantite;
            existingProduct.points = existingProduct.quantite * existingProduct.points_par_unite;
            updateProductsTable();
            clearProductForm();
            showNotification('✅ Quantité mise à jour', 'success');
            return;
        } else {
            return;
        }
    }
    
    const points = quantite * (window.currentProductPoints || 0);
    
    const product = {
        id: Date.now() + Math.random(), // ID unique
        code,
        designation,
        numero_onu: numeroOnu,
        quantite,
        points,
        points_par_unite: window.currentProductPoints || 0,
        categorie_transport: window.currentProductCategory || '0',
        added_at: new Date().toISOString()
    };
    
    expeditionProducts.push(product);
    updateProductsTable();
    clearProductForm();
    updateProgressInfo();
    updateQuotasWithCurrentProducts();
    
    showNotification('✅ Produit ajouté à l\'expédition', 'success');
    
    // Focus sur le prochain produit
    focusProductInput();
}

function validateProductInput(code, designation, numeroOnu, quantite) {
    const errors = [];
    
    if (!code || code.length < 3) {
        errors.push('Code produit requis (minimum 3 caractères)');
    }
    
    if (!designation) {
        errors.push('Désignation du produit manquante');
    }
    
    if (!numeroOnu) {
        errors.push('Numéro ONU manquant - Produit non reconnu');
    }
    
    if (!quantite || quantite <= 0) {
        errors.push('Quantité invalide (doit être > 0)');
    }
    
    if (quantite > 10000) {
        errors.push('Quantité excessive (maximum 10 000)');
    }
    
    return {
        isValid: errors.length === 0,
        errors
    };
}

function updateProductsTable() {
    const emptyDiv = document.getElementById('products-empty');
    const tableContainer = document.getElementById('products-table-container');
    const tbody = document.getElementById('products-table-body');
    const nextButton = document.getElementById('btn-next-to-validation');
    
    if (expeditionProducts.length === 0) {
        if (emptyDiv) emptyDiv.style.display = 'block';
        if (tableContainer) tableContainer.style.display = 'none';
        if (nextButton) nextButton.disabled = true;
        return;
    }
    
    if (emptyDiv) emptyDiv.style.display = 'none';
    if (tableContainer) tableContainer.style.display = 'block';
    if (nextButton) nextButton.disabled = false;
    
    if (!tbody) return;
    
    let html = '';
    let totalPoints = 0;
    
    expeditionProducts.forEach((product, index) => {
        totalPoints += product.points;
        html += `
            <tr data-product-id="${product.id}">
                <td>
                    <input type="text" class="inline-edit" value="${escapeHtml(product.code)}" 
                           onchange="updateProduct(${product.id}, 'code', this.value)"
                           readonly>
                </td>
                <td>
                    <input type="text" class="inline-edit" value="${escapeHtml(product.designation)}" 
                           onchange="updateProduct(${product.id}, 'designation', this.value)">
                </td>
                <td>${escapeHtml(product.numero_onu)}</td>
                <td>
                    <input type="number" class="inline-edit" value="${product.quantite}" 
                           step="0.1" min="0.1" max="10000"
                           onchange="updateProductQuantite(${product.id}, this.value)">
                </td>
                <td><strong>${product.points.toFixed(1)}</strong></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeProduct(${product.id})" 
                            title="Supprimer ce produit">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Mettre à jour le total
    const totalElement = document.getElementById('total-points-adr');
    if (totalElement) {
        totalElement.textContent = `${totalPoints.toFixed(1)} points`;
        
        // Alerte si dépassement
        if (quotasData && totalPoints > quotasData.points_restants) {
            totalElement.style.color = 'var(--adr-danger)';
            totalElement.title = 'Attention: dépassement de quota !';
        } else {
            totalElement.style.color = '';
            totalElement.title = '';
        }
    }
    
    // Mettre à jour l'état de l'expédition
    expeditionData.products = expeditionProducts;
    expeditionData.total_points_adr = totalPoints;
}

function updateProduct(id, field, value) {
    const product = expeditionProducts.find(p => p.id === id);
    if (product) {
        product[field] = value;
        updateProgressInfo();
        autoSave();
    }
}

function updateProductQuantite(id, quantite) {
    const product = expeditionProducts.find(p => p.id === id);
    if (product) {
        const newQuantite = parseFloat(quantite) || 0;
        
        if (newQuantite <= 0) {
            showNotification('❌ La quantité doit être supérieure à 0', 'error');
            updateProductsTable(); // Restaurer la valeur précédente
            return;
        }
        
        if (newQuantite > 10000) {
            showNotification('❌ Quantité excessive (maximum 10 000)', 'error');
            updateProductsTable();
            return;
        }
        
        product.quantite = newQuantite;
        product.points = product.quantite * (product.points_par_unite || 1);
        updateProductsTable();
        updateProgressInfo();
        updateQuotasWithCurrentProducts();
        autoSave();
    }
}

function removeProduct(id) {
    const product = expeditionProducts.find(p => p.id === id);
    if (!product) return;
    
    if (confirm(`🗑️ Supprimer "${product.code}" de l'expédition ?`)) {
        expeditionProducts = expeditionProducts.filter(p => p.id !== id);
        updateProductsTable();
        updateProgressInfo();
        updateQuotasWithCurrentProducts();
        autoSave();
        showNotification('🗑️ Produit supprimé', 'info');
    }
}

function clearProductForm() {
    setInputValue('produit-code', '');
    setInputValue('produit-designation', '');
    setInputValue('produit-numero-onu', '');
    setInputValue('produit-quantite', '');
    window.currentProductPoints = 0;
    window.currentProductCategory = '0';
    
    // Nettoyer l'affichage du calcul
    const calcDisplay = document.getElementById('points-calculation-display');
    if (calcDisplay) {
        calcDisplay.textContent = '';
    }
}

// ========== GESTION TRANSPORT ET QUOTAS ==========

function handleTransporteurChange() {
    updateQuotas();
    updateProgressInfo();
    autoSave();
}

function handleDateChange() {
    const dateInput = document.getElementById('expedition-date');
    if (!dateInput) return;
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showNotification('⚠️ Attention: date d\'expédition dans le passé', 'warning');
    }
    
    updateQuotas();
    updateProgressInfo();
    autoSave();
}

function updateQuotas() {
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    
    if (!transporteur || !date) {
        hideQuotaInfo();
        return;
    }
    
    console.log('📊 Mise à jour quotas:', { transporteur, date });
    
    const formData = new FormData();
    formData.append('action', 'get_quotas_jour');
    formData.append('transporteur', transporteur);
    formData.append('date', date);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quotasData = data;
            displayQuotaInfo(data, transporteur, date);
            updateQuotasWithCurrentProducts();
        } else {
            console.error('Erreur quotas:', data.error);
            hideQuotaInfo();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        hideQuotaInfo();
    });
}

function displayQuotaInfo(data, transporteur, date) {
    const quotaInfo = document.getElementById('quota-info');
    const quotaPlaceholder = document.getElementById('quota-placeholder');
    const quotaFill = document.getElementById('quota-fill');
    const quotaAlert = document.getElementById('quota-alert');
    
    if (quotaInfo) quotaInfo.style.display = 'block';
    if (quotaPlaceholder) quotaPlaceholder.style.display = 'none';
    
    // Nom transporteur
    const transporteurName = document.getElementById('quota-transporteur-name');
    if (transporteurName) {
        const names = { 
            heppner: 'Heppner', 
            xpo: 'XPO Logistics', 
            kn: 'Kuehne + Nagel' 
        };
        transporteurName.textContent = names[transporteur] || transporteur.toUpperCase();
    }
    
    // Date formatée
    const quotaDate = document.getElementById('quota-date');
    if (quotaDate) {
        const formattedDate = new Date(date).toLocaleDateString('fr-FR', {
            weekday: 'short',
            day: 'numeric',
            month: 'short'
        });
        quotaDate.textContent = formattedDate;
    }
    
    // Barre de progression
    if (quotaFill) {
        const percentage = Math.min(100, Math.max(0, data.pourcentage_utilise || 0));
        quotaFill.style.width = percentage + '%';
        
        // Couleur selon le pourcentage
        if (percentage >= 90) {
            quotaFill.style.background = 'var(--adr-danger)';
        } else if (percentage >= 70) {
            quotaFill.style.background = 'var(--adr-warning)';
        } else {
            quotaFill.style.background = 'var(--adr-success)';
        }
    }
    
    // Valeurs
    const quotaUtilise = document.getElementById('quota-utilise');
    const quotaRestant = document.getElementById('quota-restant');
    
    if (quotaUtilise) {
        quotaUtilise.textContent = `${(data.points_utilises || 0).toFixed(1)} points`;
    }
    
    if (quotaRestant) {
        const restant = data.points_restants || 0;
        quotaRestant.textContent = `${restant.toFixed(1)} points`;
        quotaRestant.style.color = restant < 100 ? 'var(--adr-danger)' : '';
    }
    
    // Alerte dépassement
    if (quotaAlert) {
        if (data.alerte_depassement || data.points_restants < 50) {
            quotaAlert.style.display = 'block';
            quotaAlert.textContent = data.points_restants < 0 ? 
                '🚨 Quota dépassé !' : 
                '⚠️ Quota bientôt dépassé !';
        } else {
            quotaAlert.style.display = 'none';
        }
    }
}

function hideQuotaInfo() {
    const quotaInfo = document.getElementById('quota-info');
    const quotaPlaceholder = document.getElementById('quota-placeholder');
    
    if (quotaInfo) quotaInfo.style.display = 'none';
    if (quotaPlaceholder) quotaPlaceholder.style.display = 'block';
}

function updateQuotasWithCurrentProducts() {
    if (!quotasData) return;
    
    const currentExpeditionPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    const newPointsUtilises = quotasData.points_utilises + currentExpeditionPoints;
    const newPointsRestants = quotasData.quota_max - newPointsUtilises;
    const newPourcentage = (newPointsUtilises / quotasData.quota_max) * 100;
    
    const updatedData = {
        ...quotasData,
        points_utilises: newPointsUtilises,
        points_restants: newPointsRestants,
        pourcentage_utilise: newPourcentage,
        alerte_depassement: newPointsRestants < 0
    };
    
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    
    if (transporteur && date) {
        displayQuotaInfo(updatedData, transporteur, date);
    }
}

// ========== VALIDATION ET CRÉATION ==========

function generateExpeditionSummary() {
    console.log('📋 Génération récapitulatif expédition');
    
    const summaryDiv = document.getElementById('expedition-summary');
    if (!summaryDiv) return;
    
    const destinataireData = window.getDestinataireData ? window.getDestinataireData().data : null;
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    const observations = getInputValue('expedition-observations');
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    
    if (!destinataireData || !transporteur || !date) {
        summaryDiv.innerHTML = '<div class="alert alert-warning">❌ Données incomplètes pour générer le récapitulatif</div>';
        return;
    }
    
    const transporteurNames = {
        'heppner': 'Heppner',
        'xpo': 'XPO Logistics',
        'kn': 'Kuehne + Nagel'
    };
    
    let html = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📤 Expéditeur</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>GULDAGIL</strong></div>
                    <div>4 Rue Robert Schuman<br>68170 RIXHEIM</div>
                    <div>📞 03 89 44 13 17</div>
                    <div>📧 guldagil@guldagil.com</div>
                </div>
            </div>
            
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📥 Destinataire</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>${escapeHtml(destinataireData.nom)}</strong></div>
                    ${destinataireData.adresse_complete ? `<div>${escapeHtml(destinataireData.adresse_complete)}</div>` : ''}
                    <div><strong>${escapeHtml(destinataireData.code_postal)} ${escapeHtml(destinataireData.ville)}</strong></div>
                    <div>${escapeHtml(destinataireData.pays || 'France')}</div>
                    ${destinataireData.telephone ? `<div>📞 ${escapeHtml(destinataireData.telephone)}</div>` : ''}
                    ${destinataireData.email ? `<div>📧 ${escapeHtml(destinataireData.email)}</div>` : ''}
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">🚚 Transport</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>Transporteur:</strong> ${transporteurNames[transporteur] || transporteur}</div>
                    <div><strong>Date d'expédition:</strong> ${new Date(date).toLocaleDateString('fr-FR', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    })}</div>
                    ${observations ? `<div><strong>Observations:</strong> ${escapeHtml(observations)}</div>` : ''}
                </div>
            </div>
            
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">⚠️ Quotas ADR</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>Points cette expédition:</strong> ${totalPoints.toFixed(1)}</div>
                    ${quotasData ? `
                        <div><strong>Points déjà utilisés:</strong> ${quotasData.points_utilises.toFixed(1)}</div>
                        <div><strong>Points restants après:</strong> ${(quotasData.points_restants - totalPoints).toFixed(1)}</div>
                        <div><strong>Quota maximum:</strong> ${quotasData.quota_max}</div>
                    ` : ''}
                    ${quotasData && (quotasData.points_restants - totalPoints) < 0 ? 
                        '<div style="color: var(--adr-danger); font-weight: bold;">⚠️ DÉPASSEMENT DE QUOTA</div>' : ''}
                </div>
            </div>
        </div>
        
        <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Produits ADR (${expeditionProducts.length})</h4>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Code produit</th>
                    <th>Désignation</th>
                    <th>N° ONU</th>
                    <th>Catégorie</th>
                    <th>Quantité</th>
                    <th>Points ADR</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    expeditionProducts.forEach(product => {
        html += `
            <tr>
                <td><strong>${escapeHtml(product.code)}</strong></td>
                <td>${escapeHtml(product.designation)}</td>
                <td>${escapeHtml(product.numero_onu)}</td>
                <td class="text-center">
                    ${product.categorie_transport ? `Cat. ${product.categorie_transport}` : '-'}
                </td>
                <td>${product.quantite} kg/L</td>
                <td><strong>${product.points.toFixed(1)}</strong></td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr style="background: var(--adr-light); font-weight: bold;">
                    <td colspan="5">TOTAL</td>
                    <td><strong>${totalPoints.toFixed(1)} points</strong></td>
                </tr>
            </tfoot>
        </table>
    `;
    
    // Ajouter des alertes si nécessaire
    if (quotasData && (quotasData.points_restants - totalPoints) < 0) {
        html += `
            <div class="alert alert-danger" style="margin-top: 1rem;">
                <strong>🚨 ATTENTION - DÉPASSEMENT DE QUOTA</strong><br>
                Cette expédition dépasse le quota ADR autorisé pour ce transporteur aujourd'hui.
                Veuillez vérifier avec le service logistique avant de confirmer.
            </div>
        `;
    } else if (quotasData && (quotasData.points_restants - totalPoints) < 50) {
        html += `
            <div class="alert alert-warning" style="margin-top: 1rem;">
                <strong>⚠️ ATTENTION - QUOTA BIENTÔT ATTEINT</strong><br>
                Il ne restera que ${(quotasData.points_restants - totalPoints).toFixed(1)} points après cette expédition.
            </div>
        `;
    }
    
    summaryDiv.innerHTML = html;
}

function saveAsDraft() {
    console.log('💾 Sauvegarde brouillon');
    
    updateExpeditionData();
    
    const draftData = {
        ...expeditionData,
        draft: true,
        saved_at: new Date().toISOString()
    };
    
    try {
        localStorage.setItem('adr_expedition_draft', JSON.stringify(draftData));
        showNotification('💾 Brouillon sauvegardé', 'success');
    } catch (error) {
        console.error('Erreur sauvegarde brouillon:', error);
        showNotification('❌ Erreur lors de la sauvegarde du brouillon', 'error');
    }
}

function createExpedition() {
    console.log('🚀 Création expédition finale');
    
    if (!validateExpedition()) {
        return;
    }
    
    updateExpeditionData();
    
    const confirmMessage = buildConfirmationMessage();
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Afficher l'état de création
    const createBtn = document.querySelector('button[onclick="createExpedition()"]');
    const originalText = createBtn ? createBtn.innerHTML : '';
    if (createBtn) {
        createBtn.innerHTML = '<span class="loading-spinner"></span> Création en cours...';
        createBtn.disabled = true;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_expedition');
    
    // Ajouter toutes les données de l'expédition
    Object.keys(expeditionData).forEach(key => {
        if (key === 'products') {
            formData.append('produits', JSON.stringify(expeditionData.products));
        } else if (expeditionData[key] !== null && expeditionData[key] !== undefined) {
            formData.append(key, expeditionData[key]);
        }
    });
    
    // Ajouter les données du destinataire
    const destinataireData = window.getDestinataireData ? window.getDestinataireData().data : null;
    if (destinataireData) {
        Object.keys(destinataireData).forEach(key => {
            formData.append(`destinataire_${key}`, destinataireData[key] || '');
        });
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Expédition créée avec succès !', 'success');
            
            // Nettoyer le brouillon
            try {
                localStorage.removeItem('adr_expedition_draft');
            } catch (e) {}
            
            setTimeout(() => {
                if (confirm(`✅ Expédition ${data.numero_expedition} créée !\n\nVoulez-vous être redirigé vers la liste des expéditions ?`)) {
                    window.location.href = '../dashboard.php';
                } else {
                    // Réinitialiser le formulaire pour une nouvelle expédition
                    resetForm();
                }
            }, 2000);
        } else {
            showNotification('❌ Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
        }
    })
    .catch(error => {
        console.error('Erreur création:', error);
        showNotification('❌ Erreur lors de la création de l\'expédition', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        if (createBtn) {
            createBtn.innerHTML = originalText;
            createBtn.disabled = false;
        }
    });
}

function validateExpedition() {
    const errors = [];
    
    // Vérifier destinataire
    if (!isDestinataireValid()) {
        errors.push('Destinataire invalide ou incomplet');
    }
    
    // Vérifier produits
    if (expeditionProducts.length === 0) {
        errors.push('Aucun produit dans l\'expédition');
    }
    
    // Vérifier transport
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    
    if (!transporteur) {
        errors.push('Transporteur non sélectionné');
    }
    
    if (!date) {
        errors.push('Date d\'expédition non renseignée');
    }
    
    // Vérifier quotas (warning seulement)
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    if (quotasData && quotasData.points_restants < totalPoints) {
        console.warn('Dépassement de quota détecté');
    }
    
    if (errors.length > 0) {
        showNotification('❌ Erreurs de validation:\n• ' + errors.join('\n• '), 'error');
        return false;
    }
    
    return true;
}

function buildConfirmationMessage() {
    const destinataireData = window.getDestinataireData ? window.getDestinataireData().data : null;
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    
    const transporteurNames = {
        'heppner': 'Heppner',
        'xpo': 'XPO Logistics',
        'kn': 'Kuehne + Nagel'
    };
    
    let message = `🚀 CONFIRMATION CRÉATION EXPÉDITION ADR\n\n`;
    message += `📥 Destinataire: ${destinataireData?.nom || 'Non défini'}\n`;
    message += `🚚 Transporteur: ${transporteurNames[transporteur] || transporteur}\n`;
    message += `📅 Date: ${new Date(date).toLocaleDateString('fr-FR')}\n`;
    message += `📦 Produits: ${expeditionProducts.length}\n`;
    message += `⚠️ Points ADR: ${totalPoints.toFixed(1)}\n\n`;
    
    if (quotasData && quotasData.points_restants < totalPoints) {
        message += `🚨 ATTENTION: Cette expédition dépasse le quota ADR du jour !\n\n`;
    }
    
    message += `Confirmer la création ?`;
    
    return message;
}

function updateExpeditionData() {
    const destinataireData = window.getDestinataireData ? window.getDestinataireData().data : null;
    
    expeditionData = {
        destinataire: destinataireData,
        transporteur: getInputValue('expedition-transporteur'),
        date_expedition: getInputValue('expedition-date'),
        observations: getInputValue('expedition-observations'),
        products: expeditionProducts,
        total_points_adr: expeditionProducts.reduce((sum, p) => sum + p.points, 0),
        numero_expedition: null,
        created_at: null
    };
}

// ========== PROGRESSION ET RÉSUMÉ ==========

function updateProgressInfo() {
    console.log('📊 Mise à jour informations de progression');
    
    const progressDiv = document.getElementById('expedition-progress');
    const progressDestinataire = document.getElementById('progress-destinataire');
    const progressProducts = document.getElementById('progress-products');
    const progressPoints = document.getElementById('progress-points');
    
    if (!progressDiv) return;
    
    const destinataireData = window.getDestinataireData ? window.getDestinataireData().data : null;
    const hasValidDestinataire = isDestinataireValid();
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    
    if (hasValidDestinataire || expeditionProducts.length > 0) {
        progressDiv.style.display = 'block';
        
        if (progressDestinataire) {
            progressDestinataire.textContent = hasValidDestinataire ? 
                `👤 ${destinataireData?.nom || 'Destinataire sélectionné'}` : 
                '👤 Aucun destinataire';
            progressDestinataire.style.color = hasValidDestinataire ? 'var(--adr-success)' : '';
        }
        
        if (progressProducts) {
            progressProducts.textContent = `📦 ${expeditionProducts.length} produit(s)`;
            progressProducts.style.color = expeditionProducts.length > 0 ? 'var(--adr-success)' : '';
        }
        
        if (progressPoints) {
            progressPoints.textContent = `⚠️ ${totalPoints.toFixed(1)} points ADR`;
            
            // Couleur selon les quotas
            if (quotasData) {
                if (totalPoints > quotasData.points_restants) {
                    progressPoints.style.color = 'var(--adr-danger)';
                } else if (totalPoints > quotasData.points_restants * 0.8) {
                    progressPoints.style.color = 'var(--adr-warning)';
                } else {
                    progressPoints.style.color = 'var(--adr-success)';
                }
            }
        }
    } else {
        progressDiv.style.display = 'none';
    }
}

// ========== AUTO-SAUVEGARDE ==========

let autoSaveTimeout = null;

function initializeAutoSave() {
    // Auto-sauvegarde toutes les 30 secondes
    setInterval(() => {
        if (hasUnsavedChanges()) {
            autoSave();
        }
    }, CONFIG.autoSaveInterval);
}

function autoSave() {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    
    autoSaveTimeout = setTimeout(() => {
        saveAsDraft();
    }, 2000); // 2 secondes après la dernière modification
}

function hasUnsavedChanges() {
    return isDestinataireValid() || 
           expeditionProducts.length > 0 || 
           getInputValue('expedition-transporteur') || 
           getInputValue('expedition-date');
}

function loadDraftIfExists() {
    try {
        const draftData = localStorage.getItem('adr_expedition_draft');
        if (draftData) {
            const draft = JSON.parse(draftData);
            
            if (confirm('📋 Un brouillon d\'expédition a été trouvé.\n\nVoulez-vous le restaurer ?')) {
                restoreDraft(draft);
            } else {
                localStorage.removeItem('adr_expedition_draft');
            }
        }
    } catch (error) {
        console.error('Erreur chargement brouillon:', error);
        localStorage.removeItem('adr_expedition_draft');
    }
}

function restoreDraft(draft) {
    console.log('📋 Restauration du brouillon');
    
    // Restaurer transporteur et date
    if (draft.transporteur) {
        setInputValue('expedition-transporteur', draft.transporteur);
    }
    
    if (draft.date_expedition) {
        setInputValue('expedition-date', draft.date_expedition);
    }
    
    if (draft.observations) {
        setInputValue('expedition-observations', draft.observations);
    }
    
    // Restaurer produits
    if (draft.products && Array.isArray(draft.products)) {
        expeditionProducts = draft.products.map(p => ({
            ...p,
            id: Date.now() + Math.random() // Nouveaux IDs
        }));
        updateProductsTable();
    }
    
    // Mettre à jour l'affichage
    updateProgressInfo();
    updateQuotas();
    
    showNotification('📋 Brouillon restauré', 'success');
}

// ========== FOCUS ET NAVIGATION ==========

function focusDestinataireSearch() {
    const searchInput = document.getElementById('search-destinataire');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}

function focusProductInput() {
    const codeInput = document.getElementById('produit-code');
    if (codeInput) {
        codeInput.focus();
        codeInput.select();
    }
}

function handleTabNavigation(e) {
    // Navigation améliorée avec Tab selon l'étape
    if (currentStep === 'products') {
        const produitQuantite = document.getElementById('produit-quantite');
        const addBtn = document.querySelector('button[onclick="addProductToExpedition()"]');
        
        if (e.target === produitQuantite && !e.shiftKey) {
            e.preventDefault();
            if (addBtn) addBtn.focus();
        }
    }
}

// ========== FONCTIONS UTILITAIRES ==========

function resetForm() {
    // Réinitialiser toutes les données
    expeditionProducts = [];
    quotasData = null;
    expeditionData = {
        destinataire: null,
        transporteur: '',
        date_expedition: '',
        observations: '',
        products: [],
        total_points_adr: 0,
        numero_expedition: null,
        created_at: null
    };
    
    // Nettoyer les champs
    setInputValue('expedition-transporteur', '');
    setInputValue('expedition-date', new Date().toISOString().split('T')[0]);
    setInputValue('expedition-observations', '');
    
    // Nettoyer les produits
    clearProductForm();
    updateProductsTable();
    
    // Nettoyer le destinataire
    if (typeof window.clearDestinataireForm === 'function') {
        window.clearDestinataireForm();
    }
    
    // Retour à la première étape
    showStep('destinataire');
    
    // Nettoyer le brouillon
    try {
        localStorage.removeItem('adr_expedition_draft');
    } catch (e) {}
    
    showNotification('🔄 Formulaire réinitialisé', 'info');
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
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'info') {
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Utiliser le système existant si disponible
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    
    // Utiliser celui du module destinataire
    if (typeof window.createNotification === 'function') {
        window.createNotification(message, type);
        return;
    }
    
    // Fallback simple
    alert(`${type.toUpperCase()}: ${message}`);
}

// ========== API PUBLIQUE ==========

// Exposer les fonctions nécessaires globalement
window.addProductToExpedition = addProductToExpedition;
window.updateProduct = updateProduct;
window.updateProductQuantite = updateProductQuantite;
window.removeProduct = removeProduct;
window.nextToProducts = nextToProducts;
window.nextToValidation = nextToValidation;
window.backToDestinataire = backToDestinataire;
window.backToProducts = backToProducts;
window.saveAsDraft = saveAsDraft;
window.createExpedition = createExpedition;
window.showStep = showStep;

// Fonctions pour l'intégration avec d'autres modules
window.getExpeditionData = function() {
    updateExpeditionData();
    return {
        isValid: validateExpedition(),
        data: expeditionData,
        currentStep: currentStep,
        productsCount: expeditionProducts.length,
        totalPoints: expeditionProducts.reduce((sum, p) => sum + p.points, 0)
    };
};

window.getExpeditionProducts = function() {
    return [...expeditionProducts];
};

window.getCurrentStep = function() {
    return currentStep;
};

window.resetExpeditionForm = resetForm;

// ========== STYLES ET ANIMATIONS ==========

// Ajouter les styles CSS nécessaires
if (!document.getElementById('expedition-styles')) {
    const style = document.createElement('style');
    style.id = 'expedition-styles';
    style.textContent = `
        /* Styles spécifiques à la création d'expédition */
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
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .inline-edit:focus {
            background: white !important;
            border: 2px solid var(--adr-primary) !important;
            border-radius: 4px !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .btn:disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
            transform: none !important;
        }
        
        .step.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }
        
        .step.disabled:hover {
            transform: none !important;
            background: transparent !important;
        }
        
        .quota-fill {
            transition: width 0.5s ease, background-color 0.3s ease;
        }
        
        .products-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .products-table tbody tr:hover {
            background: rgba(255, 107, 53, 0.05) !important;
        }
        
        /* Validation visuelle en temps réel */
        .form-control.valid-input {
            border-color: var(--adr-success);
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1);
        }
        
        .form-control.invalid-input {
            border-color: var(--adr-danger);
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }
        
        /* Indicateurs de progression */
        .progress-indicator {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .progress-indicator.complete {
            color: var(--adr-success);
        }
        
        .progress-indicator.incomplete {
            color: var(--adr-warning);
        }
        
        .progress-indicator.error {
            color: var(--adr-danger);
        }
        
        /* Améliorations mobile */
        @media (max-width: 768px) {
            .inline-edit {
                font-size: 0.9rem;
                padding: 0.3rem;
            }
            
            .products-table {
                font-size: 0.85rem;
            }
            
            .quota-bar {
                height: 16px;
            }
            
            .expedition-summary {
                padding: 1rem;
            }
            
            .expedition-summary > div[style*="grid"] {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
        }
        
        /* Mode impression */
        @media print {
            .step-navigation,
            .process-steps,
            .btn,
            .inline-edit {
                display: none !important;
            }
            
            .expedition-summary {
                box-shadow: none !important;
                border: 1px solid #000;
            }
            
            .products-table {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
        
        /* Animations de notification */
        .notification-enter {
            animation: notificationSlideIn 0.3s ease;
        }
        
        .notification-exit {
            animation: notificationSlideOut 0.3s ease;
        }
        
        @keyframes notificationSlideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes notificationSlideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* États de chargement */
        .loading-state {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        /* Validation temps réel */
        .field-group.valid {
            border-left: 3px solid var(--adr-success);
        }
        
        .field-group.invalid {
            border-left: 3px solid var(--adr-danger);
        }
        
        /* Seuils de quotas */
        .quota-warning {
            background: linear-gradient(90deg, var(--adr-warning) 0%, var(--adr-danger) 100%);
        }
        
        .quota-danger {
            background: var(--adr-danger);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    `;
    document.head.appendChild(style);
}

// ========== GESTION D'ERREURS ET LOGGING ==========

// Gestion globale des erreurs pour ce module
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('adr-create-expedition')) {
        console.error('🚨 Erreur module création expédition:', e.error);
        showNotification('❌ Erreur technique détectée', 'error');
        
        // Log pour debugging
        const errorLog = {
            error: e.error.message,
            stack: e.error.stack,
            currentStep: currentStep,
            productsCount: expeditionProducts.length,
            timestamp: new Date().toISOString()
        };
        console.error('Error details:', errorLog);
    }
});

// Log des actions utilisateur pour analytics
function logUserAction(action, details = {}) {
    const logEntry = {
        action,
        details,
        currentStep,
        timestamp: new Date().toISOString(),
        session: {
            user: window.ADR_CONFIG?.session?.user || 'unknown',
            products_count: expeditionProducts.length,
            session_duration: Date.now() - (window.adrStartTime || Date.now())
        }
    };
    
    console.log('📊 User action:', logEntry);
    
    // Ici vous pourriez envoyer les logs à votre système d'analytics
    // sendAnalytics(logEntry);
}

// ========== RACCOURCIS ET ACCESSIBILITÉ ==========

// Améliorer l'accessibilité
function initializeAccessibility() {
    // Ajouter des attributs ARIA
    document.querySelectorAll('.step').forEach((step, index) => {
        step.setAttribute('role', 'tab');
        step.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
    });
    
    document.querySelectorAll('.step-content').forEach((content, index) => {
        content.setAttribute('role', 'tabpanel');
        content.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
    });
    
    // Améliorer la navigation clavier
    document.addEventListener('keydown', function(e) {
        // Alt + numéro pour aller directement à une étape
        if (e.altKey && e.key >= '1' && e.key <= '3') {
            e.preventDefault();
            const stepNames = ['destinataire', 'products', 'validation'];
            const stepIndex = parseInt(e.key) - 1;
            if (stepNames[stepIndex]) {
                showStep(stepNames[stepIndex]);
            }
        }
        
        // Echap pour annuler les actions en cours
        if (e.key === 'Escape') {
            // Fermer les suggestions ouvertes
            hideQuotaInfo();
            // Annuler les saisies en cours
            document.activeElement?.blur();
        }
    });
}

// ========== INITIALISATION FINALE ==========

// Attendre que tous les modules soient chargés
document.addEventListener('DOMContentLoaded', function() {
    // Petite pause pour s'assurer que tout est initialisé
    setTimeout(() => {
        initializeAccessibility();
        logUserAction('module_loaded', {
            available_products: availableProducts.length,
            user_agent: navigator.userAgent
        });
    }, 100);
});

// Sauvegarde avant fermeture
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges()) {
        const message = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
        e.returnValue = message;
        
        // Sauvegarde automatique d'urgence
        try {
            saveAsDraft();
        } catch (error) {
            console.error('Erreur sauvegarde d\'urgence:', error);
        }
        
        return message;
    }
});

// Statistiques de fin de session
window.addEventListener('beforeunload', function() {
    if (expeditionProducts.length > 0 || currentStep !== 'destinataire') {
        logUserAction('session_end', {
            final_step: currentStep,
            products_added: expeditionProducts.length,
            total_points: expeditionProducts.reduce((sum, p) => sum + p.points, 0),
            session_duration: Date.now() - (window.adrStartTime || Date.now()),
            completed: expeditionData.numero_expedition ? true : false
        });
    }
});

console.log('✅ Module création expédition ADR chargé avec succès');
console.log('🎯 Fonctionnalités: navigation étapes, gestion produits, quotas, auto-sauvegarde');
console.log('⌨️ Raccourcis: Ctrl+S (brouillon), Ctrl+Enter (créer), Alt+1/2/3 (étapes), Echap (annuler)');

// Marquer le module comme prêt
window.adrCreateExpeditionReady = true;
