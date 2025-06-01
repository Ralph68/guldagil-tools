// test-rates.js - Test simple du gestionnaire de tarifs

console.log('🧪 Test du gestionnaire de tarifs');

// Test 1: Vérifier que l'API répond
async function testAPI() {
    console.log('🔍 Test API...');
    try {
        const response = await fetch('api-rates.php?action=list&limit=3');
        const data = await response.json();
        
        console.log('📊 Réponse API:', data);
        
        if (data.success) {
            console.log('✅ API fonctionne !', data.data.rates.length, 'tarifs');
            displayTestResults('API OK', `${data.data.rates.length} tarifs trouvés`);
        } else {
            console.error('❌ API erreur:', data.error);
            displayTestResults('API Erreur', data.error);
        }
    } catch (error) {
        console.error('❌ Erreur réseau:', error);
        displayTestResults('Erreur réseau', error.message);
    }
}

// Test 2: Vérifier le gestionnaire de tarifs
function testRatesManager() {
    console.log('🔍 Test RatesManager...');
    
    if (window.RatesManager) {
        console.log('✅ Classe RatesManager disponible');
        displayTestResults('RatesManager', 'Classe disponible');
        
        if (window.initRatesManager) {
            console.log('✅ Fonction initRatesManager disponible');
            displayTestResults('initRatesManager', 'Fonction disponible');
        } else {
            console.error('❌ Fonction initRatesManager manquante');
            displayTestResults('initRatesManager', 'Fonction manquante');
        }
    } else {
        console.error('❌ Classe RatesManager manquante');
        displayTestResults('RatesManager', 'Classe manquante');
    }
}

// Test 3: Vérifier les éléments DOM
function testDOM() {
    console.log('🔍 Test éléments DOM...');
    
    const elements = {
        'rates-tbody': document.getElementById('rates-tbody'),
        'filter-carrier': document.getElementById('filter-carrier'),
        'search-rates': document.getElementById('search-rates'),
        'pagination-container': document.getElementById('pagination-container')
    };
    
    let found = 0;
    let missing = [];
    
    for (const [name, element] of Object.entries(elements)) {
        if (element) {
            found++;
            console.log('✅', name, 'trouvé');
        } else {
            missing.push(name);
            console.error('❌', name, 'manquant');
        }
    }
    
    displayTestResults('Éléments DOM', `${found}/4 trouvés${missing.length ? ' - Manquants: ' + missing.join(', ') : ''}`);
}

// Afficher les résultats de test
function displayTestResults(test, result) {
    const tbody = document.getElementById('rates-tbody');
    if (tbody) {
        const existingTests = tbody.querySelector('.test-results');
        if (!existingTests) {
            tbody.innerHTML = `
                <tr class="test-results">
                    <td colspan="9" style="padding: 1rem; background: #f0f8ff;">
                        <h4>🧪 Résultats des tests</h4>
                        <div id="test-results-content"></div>
                        <br>
                        <button onclick="runAllTests()" style="padding: 0.5rem 1rem; background: #007acc; color: white; border: none; border-radius: 4px;">
                            🔄 Relancer tous les tests
                        </button>
                        <button onclick="initRealRatesManager()" style="padding: 0.5rem 1rem; background: #4CAF50; color: white; border: none; border-radius: 4px; margin-left: 0.5rem;">
                            🚀 Initialiser le vrai gestionnaire
                        </button>
                    </td>
                </tr>
            `;
        }
        
        const content = document.getElementById('test-results-content');
        if (content) {
            content.innerHTML += `<div style="margin: 0.25rem 0;"><strong>${test}:</strong> ${result}</div>`;
        }
    }
}

// Lancer tous les tests
async function runAllTests() {
    console.log('🚀 Lancement de tous les tests...');
    
    // Vider les résultats précédents
    const content = document.getElementById('test-results-content');
    if (content) {
        content.innerHTML = '';
    }
    
    testDOM();
    testRatesManager();
    await testAPI();
    
    console.log('✅ Tous les tests terminés');
}

// Initialiser le vrai gestionnaire
async function initRealRatesManager() {
    console.log('🚀 Initialisation du vrai gestionnaire...');
    
    if (window.initRatesManager) {
        try {
            await window.initRatesManager();
            displayTestResults('Initialisation', '✅ Gestionnaire initialisé avec succès');
        } catch (error) {
            console.error('❌ Erreur init:', error);
            displayTestResults('Initialisation', '❌ Erreur: ' + error.message);
        }
    } else {
        displayTestResults('Initialisation', '❌ initRatesManager non disponible');
    }
}

// Exposer les fonctions globalement
window.testAPI = testAPI;
window.testRatesManager = testRatesManager;
window.testDOM = testDOM;
window.runAllTests = runAllTests;
window.initRealRatesManager = initRealRatesManager;

// Auto-test au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Attendre un peu que tout soit chargé
    setTimeout(() => {
        runAllTests();
    }, 1000);
});

console.log('✅ Module de test chargé');
