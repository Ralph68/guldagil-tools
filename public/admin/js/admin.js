// public/assets/js/admin.js - JavaScript pour l'administration

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Interface d\'administration Guldagil chargée');
    
    // Initialiser l'interface
    initializeAdmin();
    
    // Charger les données par défaut
    loadRates();
    
    // Configurer les événements
    setupEventListeners();
    
    showNotification('Interface d\'administration prête', 'success');
});

// =============================================================================
// INITIALISATION
// =============================================================================

function initializeAdmin() {
    // Gestion des clics extérieurs pour fermer les modaux
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Configuration du drag & drop pour l'upload
    setupDragAndDrop();
    
    // Configuration des filtres de recherche
    setupSearchFilters();
}

function setupEventListeners() {
    // Recherche en temps réel pour les tarifs
    const searchRates = document.getElementById('search-rates');
    if (searchRates) {
        searchRates.addEventListener('input', debounce(function() {
            loadRates(this.value);
        }, 300));
    }
    
    // Filtre par transporteur pour les tarifs
    const filterCarrier = document.getElementById('filter-carrier');
    if (filterCarrier) {
        filterCarrier.addEventListener('change', function() {
            loadRates(document.getElementById('search-rates').value, this.value);
        });
    }
    
    // Recherche pour les options
    const searchOptions = document.getElementById('search-options');
    if (searchOptions) {
        searchOptions.addEventListener('input', debounce(function() {
            loadOptions(this.value);
        }, 300));
    }
    
    // Filtre par transporteur pour les options
    const filterOptionCarrier = document.getElementById('filter-option-carrier');
    if (filterOptionCarrier) {
        filterOptionCarrier.addEventListener('change', function() {
            loadOptions(document.getElementById('search-options').value, this.value);
        });
    }
}

function setupDragAndDrop() {
    const uploadZone = document.querySelector('.upload-zone');
    if (!uploadZone) return;
    
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('file-input').files = files;
            handleFileUpload(document.getElementById('file-input'));
        }
    });
}

function setupSearchFilters() {
    // Les filtres sont configurés dans setupEventListeners()
}

// =============================================================================
// GESTION DES ONGLETS
// =============================================================================

function showTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    document.getElementById(`tab-${tabName}`).classList.add('active');
    event.target.classList.add('active');
    
    // Charger les données selon l'onglet
    switch(tabName) {
        case 'rates':
            loadRates();
            break;
        case 'options':
            loadOptions();
            break;
        case 'taxes':
            loadTaxes();
            break;
        case 'import':
            // Pas de chargement spécifique
            break;
    }
}

// =============================================================================
// GESTION DES TARIFS
// =============================================================================

async function loadRates(search = '', carrier = '') {
    const tbody = document.getElementById('rates-tbody');
    
    // Afficher le loading
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="loading-spinner">Chargement des tarifs...</div></td></tr>';
    
    try {
        const params = new URLSearchParams({
            action: 'get_rates',
            search: search,
            carrier: carrier
        });
        
        const response = await fetch(`api.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayRates(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des tarifs:', error);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Erreur lors du chargement</td></tr>';
        showNotification('Err
