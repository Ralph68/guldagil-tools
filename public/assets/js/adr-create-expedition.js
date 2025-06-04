// public/assets/js/adr-create-expedition.js - Création expédition ADR
console.log('🚚 Chargement module création expédition ADR...');

// Variables globales
let currentStep = 'destinataire';
let selectedClient = null;
let expeditionProducts = [];
let quotasData = null;
let availableProducts = [];
let searchTimeout = null;

// Configuration
const CONFIG = {
    searchDelay: 300,
    minSearchChars: 2,
    maxSuggestions: 10,
    quotaMaxDefault: 1000
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Initialisation création expédition ADR');
    initializeForm();
});

function initializeForm() {
    setupEventListeners();
    loadAvailableProducts();
    initializeClientSearch();
    updateStepDisplay();
}

function setupEventListeners() {
    // Recherche client
    const searchInput = document.getElementById('search-client');
    if (searchInput) {
        searchInput.addEventListener('input', handleClientSearch);
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.length >= CONFIG.minSearchChars) {
                handleClientSearch();
            }
        });
        searchInput.addEventListener('blur', () => {
            setTimeout(hideClientSuggestions, 150);
        });
    }

    // Transporteur et date
    const transporteurSelect = document.getElementById('expedition-transporteur');
    const dateInput = document.getElementById('expedition-date');
    
    if (transporteurSelect) {
        transporteurSelect.addEventListener('change', updateQuotas);
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', updateQuotas);
    }

    // Produits
    const produitCodeInput = document.getElementById('produit-code');
    const produitQuantiteInput = document.getElementById('produit-quantite');
    
    if (produitCodeInput) {
        produitCodeInput.addEventListener('input', handleProductSearch);
        produitCodeInput.addEventListener('change', loadProductInfo);
    }
    
    if (produitQuantiteInput) {
        produitQuantiteInput.addEventListener('input', updatePointsCalculation);
    }

    // Navigation étapes
    setupStepNavigation();
}

// ========== GESTION CLIENTS ==========

function handleClientSearch() {
    const searchInput = document.getElementById('search-client');
    const query = searchInput.value.trim();
    
    if (query.length < CONFIG.minSearchChars) {
        hideClientSuggestions();
        return;
    }
    
    // Debounce
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchClients(query);
    }, CONFIG.searchDelay);
}

function searchClients(query) {
    console.log('🔍 Recherche clients:', query);
    
    const formData = new FormData();
    formData.append('action', 'search_clients');
    formData.append('query', query);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayClientSuggestions(data.clients);
        } else {
            console.error('Erreur recherche clients:', data.error);
            hideClientSuggestions();
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        hideClientSuggestions();
    });
}

function displayClientSuggestions(clients) {
    const container = document.getElementById('client-suggestions');
    if (!container) return;
    
    let html = '';
    
    if (clients.length === 0) {
        html = `
            <div class="client-suggestion" onclick="showNewClientForm()">
                <div class="client-name">➕ Créer un nouveau client</div>
                <div class="client-details">Aucun client trouvé - Cliquez pour créer</div>
            </div>
        `;
    } else {
        clients.forEach(client => {
            html += `
                <div class="client-suggestion" onclick="selectClient(${escapeJson(client)})">
                    <div class="client-name">${escapeHtml(client.nom)}</div>
                    <div class="client-details">${escapeHtml(client.adresse_complete || '')} - ${escapeHtml(client.code_postal)} ${escapeHtml(client.ville)}</div>
                </div>
            `;
        });
        
        html += `
            <div class="client-suggestion" onclick="showNewClientForm()" style="border-top: 2px solid var(--adr-primary);">
                <div class="client-name">➕ Créer un nouveau client</div>
                <div class="client-details">Créer un client qui n'existe pas</div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

function hideClientSuggestions() {
    const container = document.getElementById('client-suggestions');
    if (container) {
        container.style.display = 'none';
    }
}

function selectClient(client) {
    console.log('👤 Client sélectionné:', client);
    
    selectedClient = client;
    
    const searchInput = document.getElementById('search-client');
    const selectedClientDiv = document.getElementById('selected-client');
    const selectedClientInfo = document.getElementById('selected-client-info');
    const newClientForm = document.getElementById('new-client-form');
    const nextButton = document.getElementById('btn-next-to-products');
    
    if (searchInput) searchInput.value = client.nom;
    hideClientSuggestions();
    
    // Afficher les infos client
    if (selectedClientInfo) {
        selectedClientInfo.innerHTML = `
            <div><strong>${escapeHtml(client.nom)}</strong></div>
            <div>${escapeHtml(client.adresse_complete || 'Adresse non renseignée')}</div>
            <div><strong>${escapeHtml(client.code_postal)} ${escapeHtml(client.ville)}</strong> (${escapeHtml(client.pays || 'France')})</div>
            ${client.telephone ? `<div>Tél: ${escapeHtml(client.telephone)}</div>` : ''}
            ${client.email ? `<div>Email: ${escapeHtml(client.email)}</div>` : ''}
        `;
    }
    
    if (selectedClientDiv) selectedClientDiv.style.display = 'block';
    if (newClientForm) newClientForm.style.display = 'none';
    if (nextButton) nextButton.disabled = false;
    
    updateProgressInfo();
}

function showNewClientForm() {
    console.log('➕ Affichage formulaire nouveau client');
    
    hideClientSuggestions();
    
    const newClientForm = document.getElementById('new-client-form');
    const selectedClientDiv = document.getElementById('selected-client');
    const clientNomInput = document.getElementById('client-nom');
    
    if (newClientForm) newClientForm.style.display = 'block';
    if (selectedClientDiv) selectedClientDiv.style.display = 'none';
    if (clientNomInput) clientNomInput.focus();
}

function saveNewClient() {
    console.log('💾 Sauvegarde nouveau client');
    
    const formData = new FormData();
    formData.append('action', 'save_client');
    formData.append('nom', getInputValue('client-nom'));
    formData.append('adresse_complete', getInputValue('client-adresse'));
    formData.append('code_postal', getInputValue('client-codepostal'));
    formData.append('ville', getInputValue('client-ville'));
    formData.append('pays', getInputValue('client-pays'));
    formData.append('telephone', getInputValue('client-telephone'));
    formData.append('email', getInputValue('client-email'));
    
    // Validation basique
    if (!formData.get('nom') || !formData.get('code_postal') || !formData.get('ville')) {
        alert('❌ Veuillez remplir au minimum : nom, code postal et ville');
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectClient(data.client);
            showNotification('✅ Client créé avec succès', 'success');
        } else {
            showNotification('❌ Erreur: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('❌ Erreur lors de la création du client', 'error');
    });
}

function cancelNewClient() {
    const newClientForm = document.getElementById('new-client-form');
    const searchInput = document.getElementById('search-client');
    
    if (newClientForm) newClientForm.style.display = 'none';
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
}

function changeClient() {
    selectedClient = null;
    
    const selectedClientDiv = document.getElementById('selected-client');
    const searchInput = document.getElementById('search-client');
    const nextButton = document.getElementById('btn-next-to-products');
    
    if (selectedClientDiv) selectedClientDiv.style.display = 'none';
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    if (nextButton) nextButton.disabled = true;
    
    updateProgressInfo();
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
            designation: 'Acide chlorhydrique 33%', 
            numero_onu: 'UN1789', 
            points_adr_par_unite: 1,
            categorie_transport: '2'
        },
        { 
            code_produit: 'GUL-002', 
            designation: 'Hydroxyde de sodium 25%', 
            numero_onu: 'UN1824', 
            points_adr_par_unite: 1,
            categorie_transport: '2'
        },
        { 
            code_produit: 'GUL-003', 
            designation: 'Peroxyde d\'hydrogène 35%', 
            numero_onu: 'UN2014', 
            points_adr_par_unite: 3,
            categorie_transport: '1'
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

function handleProductSearch() {
    const codeInput = document.getElementById('produit-code');
    if (!codeInput) return;
    
    const code = codeInput.value.trim();
    if (code.length >= 3) {
        loadProductInfo();
    }
}

function loadProductInfo() {
    const codeInput = document.getElementById('produit-code');
    if (!codeInput) return;
    
    const code = codeInput.value.trim();
    
    // Rechercher dans les produits chargés
    const product = availableProducts.find(p => p.code_produit === code);
    
    if (product) {
        updateProductFields(product);
    } else {
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
}

function updateProductFields(product) {
    setInputValue('produit-designation', product.designation || '');
    setInputValue('produit-numero-onu', product.numero_onu || '');
    
    window.currentProductPoints = parseFloat(product.points_adr_par_unite) || 1;
    window.currentProductCategory = product.categorie_transport || '0';
    
    updatePointsCalculation();
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
    
    console.log(`Calcul points: ${quantite} x ${window.currentProductPoints} = ${points}`);
}

function addProductToExpedition() {
    console.log('➕ Ajout produit à l\'expédition');
    
    const code = getInputValue('produit-code');
    const designation = getInputValue('produit-designation');
    const numeroOnu = getInputValue('produit-numero-onu');
    const quantite = parseFloat(getInputValue('produit-quantite')) || 0;
    
    // Validation
    if (!code || !quantite || quantite <= 0) {
        showNotification('❌ Veuillez remplir tous les champs requis', 'error');
        return;
    }
    
    if (!designation || !numeroOnu) {
        showNotification('❌ Produit non reconnu. Vérifiez le code produit.', 'error');
        return;
    }
    
    const points = quantite * (window.currentProductPoints || 0);
    
    const product = {
        id: Date.now(), // ID temporaire
        code,
        designation,
        numero_onu: numeroOnu,
        quantite,
        points,
        points_par_unite: window.currentProductPoints || 0,
        categorie_transport: window.currentProductCategory || '0'
    };
    
    expeditionProducts.push(product);
    updateProductsTable();
    clearProductForm();
    updateProgressInfo();
    updateQuotasWithCurrentProducts();
    
    showNotification('✅ Produit ajouté à l\'expédition', 'success');
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
    
    expeditionProducts.forEach(product => {
        totalPoints += product.points;
        html += `
            <tr>
                <td>
                    <input type="text" class="inline-edit" value="${escapeHtml(product.code)}" 
                           onchange="updateProduct(${product.id}, 'code', this.value)">
                </td>
                <td>
                    <input type="text" class="inline-edit" value="${escapeHtml(product.designation)}" 
                           onchange="updateProduct(${product.id}, 'designation', this.value)">
                </td>
                <td>${escapeHtml(product.numero_onu)}</td>
                <td>
                    <input type="number" class="inline-edit" value="${product.quantite}" step="0.1" min="0.1"
                           onchange="updateProductQuantite(${product.id}, this.value)">
                </td>
                <td><strong>${product.points.toFixed(1)}</strong></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeProduct(${product.id})" 
                            title="Supprimer">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    const totalElement = document.getElementById('total-points-adr');
    if (totalElement) {
        totalElement.textContent = `${totalPoints.toFixed(1)} points`;
    }
}

function updateProduct(id, field, value) {
    const product = expeditionProducts.find(p => p.id === id);
    if (product) {
        product[field] = value;
        updateProgressInfo();
    }
}

function updateProductQuantite(id, quantite) {
    const product = expeditionProducts.find(p => p.id === id);
    if (product) {
        product.quantite = parseFloat(quantite) || 0;
        product.points = product.quantite * (product.points_par_unite || 1);
        updateProductsTable();
        updateProgressInfo();
        updateQuotasWithCurrentProducts();
    }
}

function removeProduct(id) {
    if (confirm('❌ Supprimer ce produit de l\'expédition ?')) {
        expeditionProducts = expeditionProducts.filter(p => p.id !== id);
        updateProductsTable();
        updateProgressInfo();
        updateQuotasWithCurrentProducts();
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
}

// ========== GESTION QUOTAS ==========

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
        const names = { heppner: 'Heppner', xpo: 'XPO', kn: 'K+N' };
        transporteurName.textContent = names[transporteur] || transporteur;
    }
    
    // Date
    const quotaDate = document.getElementById('quota-date');
    if (quotaDate) {
        quotaDate.textContent = new Date(date).toLocaleDateString('fr-FR');
    }
    
    // Barre de progression
    if (quotaFill) {
        const percentage = Math.min(100, data.pourcentage_utilise);
        quotaFill.style.width = percentage + '%';
    }
    
    // Valeurs
    const quotaUtilise = document.getElementById('quota-utilise');
    const quotaRestant = document.getElementById('quota-restant');
    
    if (quotaUtilise) {
        quotaUtilise.textContent = `${data.points_utilises.toFixed(1)} points`;
    }
    
    if (quotaRestant) {
        quotaRestant.textContent = `${data.points_restants.toFixed(1)} points`;
    }
    
    // Alerte dépassement
    if (quotaAlert) {
        if (data.alerte_depassement) {
            quotaAlert.style.display = 'block';
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
    // Recalculer les quotas avec les produits actuels
    if (quotasData) {
        const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
        const newPointsUtilises = quotasData.points_utilises + totalPoints;
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
}

// ========== NAVIGATION ÉTAPES ==========

function setupStepNavigation() {
    // Boutons navigation
    const nextToProductsBtn = document.getElementById('btn-next-to-products');
    const nextToValidationBtn = document.getElementById('btn-next-to-validation');
    
    if (nextToProductsBtn) {
        nextToProductsBtn.addEventListener('click', nextToProducts);
    }
    
    if (nextToValidationBtn) {
        nextToValidationBtn.addEventListener('click', nextToValidation);
    }
    
    // Clics sur les étapes
    document.querySelectorAll('.step').forEach(step => {
        step.addEventListener('click', function() {
            const stepName = this.getAttribute('data-step');
            if (stepName && !this.classList.contains('disabled')) {
                showStep(stepName);
            }
        });
    });
}

function nextToProducts() {
    if (!selectedClient) {
        showNotification('❌ Veuillez sélectionner un client', 'error');
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
    
    generateExpeditionSummary();
    showStep('validation');
}

function backToDestinataire() {
    showStep('destinataire');
}

function backToProducts() {
    showStep('products');
}

function showStep(stepName) {
    console.log('📍 Navigation vers étape:', stepName);
    
    currentStep = stepName;
    
    // Masquer tous les contenus
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Afficher le contenu demandé
    const targetContent = document.getElementById(`step-${stepName}`);
    if (targetContent) {
        targetContent.classList.add('active');
    }
    
    updateStepDisplay();
}

function updateStepDisplay() {
    // Mettre à jour l'affichage des étapes dans la sidebar
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
            return selectedClient !== null;
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
            return selectedClient !== null;
        case 'validation':
            return selectedClient !== null && 
                   expeditionProducts.length > 0 &&
                   getInputValue('expedition-transporteur') &&
                   getInputValue('expedition-date');
        default:
            return false;
    }
}

// ========== VALIDATION ET CRÉATION ==========

function generateExpeditionSummary() {
    console.log('📋 Génération récapitulatif expédition');
    
    const summaryDiv = document.getElementById('expedition-summary');
    if (!summaryDiv) return;
    
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    
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
                    <div>Tél: 03 89 44 13 17</div>
                </div>
            </div>
            
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📥 Destinataire</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>${escapeHtml(selectedClient.nom)}</strong></div>
                    <div>${escapeHtml(selectedClient.adresse_complete || '')}</div>
                    <div><strong>${escapeHtml(selectedClient.code_postal)} ${escapeHtml(selectedClient.ville)}</strong></div>
                    ${selectedClient.telephone ? `<div>Tél: ${escapeHtml(selectedClient.telephone)}</div>` : ''}
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">🚚 Transport</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>Transporteur:</strong> ${transporteurNames[transporteur] || transporteur}</div>
                    <div><strong>Date d'expédition:</strong> ${new Date(date).toLocaleDateString('fr-FR')}</div>
                </div>
            </div>
            
            <div>
                <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">⚠️ Quotas ADR</h4>
                <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius);">
                    <div><strong>Points cette expédition:</strong> ${totalPoints.toFixed(1)}</div>
                    ${quotasData ? `<div><strong>Points restants:</strong> ${(quotasData.points_restants - totalPoints).toFixed(1)}</div>` : ''}
                </div>
            </div>
        </div>
        
        <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Produits ADR</h4>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Code produit</th>
                    <th>Désignation</th>
                    <th>N° ONU</th>
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
                <td>${product.quantite} kg/L</td>
                <td><strong>${product.points.toFixed(1)}</strong></td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr style="background: var(--adr-light); font-weight: bold;">
                    <td colspan="4">TOTAL</td>
                    <td><strong>${totalPoints.toFixed(1)} points</strong></td>
                </tr>
            </tfoot>
        </table>
    `;
    
    summaryDiv.innerHTML = html;
}

function saveAsDraft() {
    console.log('💾 Sauvegarde brouillon');
    showNotification('💾 Fonction sauvegarde brouillon en développement', 'info');
}

function createExpedition() {
    console.log('🚀 Création expédition finale');
    
    if (!validateExpedition()) {
        return;
    }
    
    const expeditionData = {
        client_id: selectedClient.id,
        transporteur: getInputValue('expedition-transporteur'),
        date_expedition: getInputValue('expedition-date'),
        products: expeditionProducts.map(p => ({
            code_produit: p.code,
            quantite_declaree: p.quantite,
            unite_quantite: 'kg', // Par défaut
            points_adr_calcules: p.points
        })),
        observations: getInputValue('expedition-observations') || '',
        total_points_adr: expeditionProducts.reduce((sum, p) => sum + p.points, 0)
    };
    
    // Afficher loading
    showNotification('🚀 Création de l\'expédition en cours...', 'info');
    
    // TODO: Envoi au serveur
    // Pour l'instant, simulation
    setTimeout(() => {
        showNotification('✅ Expédition créée avec succès !', 'success');
        
        setTimeout(() => {
            if (confirm('✅ Expédition créée !\n\nVoulez-vous être redirigé vers la liste des expéditions ?')) {
                window.location.href = 'list.php';
            }
        }, 1000);
    }, 2000);
}

function validateExpedition() {
    if (!selectedClient) {
        showNotification('❌ Client manquant', 'error');
        return false;
    }
    
    if (expeditionProducts.length === 0) {
        showNotification('❌ Aucun produit dans l\'expédition', 'error');
        return false;
    }
    
    const transporteur = getInputValue('expedition-transporteur');
    const date = getInputValue('expedition-date');
    
    if (!transporteur || !date) {
        showNotification('❌ Transporteur et date obligatoires', 'error');
        return false;
    }
    
    // Vérifier quotas
    const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
    if (quotasData && quotasData.points_restants < totalPoints) {
        if (!confirm('⚠️ Cette expédition dépasse les quotas ADR du jour.\n\nContinuer quand même ?')) {
            return false;
        }
    }
    
    return true;
}

// ========== PROGRESS ET RÉSUMÉ ==========

function updateProgressInfo() {
    console.log('📊 Mise à jour informations de progression');
    
    const progressDiv = document.getElementById('expedition-progress');
    const progressClient = document.getElementById('progress-client');
    const progressProducts = document.getElementById('progress-products');
    const progressPoints = document.getElementById('progress-points');
    
    if (!progressDiv) return;
    
    if (selectedClient || expeditionProducts.length > 0) {
        progressDiv.style.display = 'block';
        
        if (progressClient) {
            progressClient.textContent = selectedClient ? 
                `👤 ${selectedClient.nom}` : 
                '👤 Aucun client sélectionné';
        }
        
        if (progressProducts) {
            progressProducts.textContent = `📦 ${expeditionProducts.length} produit(s)`;
        }
        
        if (progressPoints) {
            const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            progressPoints.textContent = `⚠️ ${totalPoints.toFixed(1)} points ADR`;
        }
    } else {
        progressDiv.style.display = 'none';
    }
}

// ========== FONCTIONS UTILITAIRES ==========

function initializeClientSearch() {
    console.log('🔍 Initialisation recherche client');
    
    // Focus automatique sur le champ de recherche
    const searchInput = document.getElementById('search-client');
    if (searchInput) {
        searchInput.focus();
    }
}

function getInputValue(id) {
    const element = document.getElementById(id);
    return element ? element.value.trim() : '';
}

function setInputValue(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.value = value;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJson(obj) {
    return JSON.stringify(obj).replace(/"/g, '&quot;');
}

function showNotification(message, type = 'info') {
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Créer ou utiliser le container de notifications
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

// ========== FONCTIONS GLOBALES ==========

// Exposer les fonctions nécessaires globalement
window.selectClient = selectClient;
window.showNewClientForm = showNewClientForm;
window.saveNewClient = saveNewClient;
window.cancelNewClient = cancelNewClient;
window.changeClient = changeClient;
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

// Ajouter les styles d'animation si nécessaire
if (!document.getElementById('adr-animations')) {
    const style = document.createElement('style');
    style.id = 'adr-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .inline-edit:focus {
            background: white !important;
            border: 2px solid var(--adr-primary) !important;
            border-radius: 4px !important;
            outline: none !important;
        }
        
        .btn:disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }
        
        .step.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }
        
        .step.disabled:hover {
            transform: none !important;
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ Module création expédition ADR chargé avec succès');
console.log('🎯 Fonctions disponibles: gestion clients, produits, quotas, navigation');

// Analytics et debug
window.addEventListener('beforeunload', function() {
    if (selectedClient || expeditionProducts.length > 0) {
        console.log('📊 Session stats:', {
            client_selected: !!selectedClient,
            products_count: expeditionProducts.length,
            current_step: currentStep,
            session_duration: Date.now() - window.sessionStartTime
        });
    }
});

window.sessionStartTime = Date.now();
