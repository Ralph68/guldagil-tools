// TEST - public/admin/assets/js/admin.js

console.log('🚀 ADMIN JS CHARGÉ !');

// Test simple
document.addEventListener('DOMContentLoaded', function() {
    alert('JavaScript fonctionne !');
    
    // Test des onglets
    window.showTab = function(tabName) {
        alert('Onglet cliqué: ' + tabName);
    };
    
    console.log('Interface admin initialisée');
});
