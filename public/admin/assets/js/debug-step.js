// debug-step.js - Test étape par étape
console.log('🔧 DEBUG STEP - Fichier chargé');

// Test 1: Fonction de test simple
function testSimpleFunction() {
    console.log('✅ Test fonction simple OK');
    alert('Fonction simple fonctionne !');
}

// Test 2: Appel API direct
async function testApiDirect() {
    console.log('🌐 Test API direct...');
    
    try {
        const response = await fetch('api-rates.php?action=list&limit=5');
        console.log('📡 Réponse brute:', response);
        
        const data = await response.json();
        console.log('📊 Données:', data);
        
        if (data.success) {
            alert(`API OK: ${data.data.rates.length} tarifs trouvés`);
        } else {
            alert(`API Erreur: ${data.error}`);
        }
        
    } catch (error) {
        console.error('❌ Erreur API:', error);
        alert('Erreur API: ' + error.message);
    }
}

// Test 3: Affichage dans le tableau
function testDisplayInTable() {
    console.log('📋 Test affichage tableau...');
    
    const tbody = document.getElementById('rates-tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 2rem; background: lightgreen;">
                    ✅ TEST: Affichage dans le tableau fonctionne !
                    <br><br>
                    <button onclick="testApiDirect()">Tester API</button>
                </td>
            </tr>
        `;
        alert('Tableau trouvé et modifié !');
    } else {
        alert('❌ Tableau rates-tbody NON TROUVÉ !');
    }
}

// Test 4: Test complet
async function testComplete() {
    console.log('🚀 Test complet...');
    
    // Étape 1: Vérifier les éléments DOM
    const elements = {
        'rates-tbody': document.getElementById('rates-tbody'),
        'filter-carrier': document.getElementById('filter-carrier'),
        'search-rates': document.getElementById('search-rates')
    };
    
    console.log('🎯 Éléments DOM:', elements);
    
    let missingElements = [];
    for (const [name, element] of Object.entries(elements)) {
        if (!element) {
            missingElements.push(name);
        }
    }
    
    if (missingElements.length > 0) {
        alert(`❌ Éléments manquants: ${missingElements.join(', ')}`);
        return;
    }
    
    // Étape 2: Test API
    try {
        const response = await fetch('api-rates.php?action=carriers');
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ API carriers OK:', result.data);
        } else {
            console.error('❌ API carriers erreur:', result.error);
        }
    } catch (error) {
        console.error('❌ Erreur appel API:', error);
    }
    
    // Étape 3: Affichage test
    testDisplayInTable();
}

// Exposer les fonctions de test
window.testSimpleFunction = testSimpleFunction;
window.testApiDirect = testApiDirect;
window.testDisplayInTable = testDisplayInTable;
window.testComplete = testComplete;

// Auto-test au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 DOM chargé, lancement auto-test dans 2 secondes...');
    setTimeout(() => {
        testComplete();
    }, 2000);
});

console.log('✅ Debug step chargé - Fonctions disponibles:', {
    testSimpleFunction,
    testApiDirect, 
    testDisplayInTable,
    testComplete
});
