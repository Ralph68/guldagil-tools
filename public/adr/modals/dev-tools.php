<?php
// public/adr/modals/dev-tools.php - Outils de développement
if (!isset($_SESSION['adr_logged_in']) || !in_array('dev', $_SESSION['adr_permissions'] ?? [])) {
    die('Accès non autorisé');
}
?>

<!-- Modal Outils de développement -->
<div id="dev-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <h3>🛠️ Outils de développement ADR</h3>
            <button class="modal-close" onclick="closeDevModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="dev-tabs">
                <button class="tab-btn active" onclick="showDevTab('test-data')">📊 Données test</button>
                <button class="tab-btn" onclick="showDevTab('api-test')">🔌 Test API</button>
                <button class="tab-btn" onclick="showDevTab('debug')">🐛 Debug</button>
                <button class="tab-btn" onclick="showDevTab('generators')">⚙️ Générateurs</button>
            </div>
            
            <!-- Onglet Données test -->
            <div id="dev-tab-test-data" class="dev-tab-content active">
                <h4>📊 Génération de données de test</h4>
                
                <div class="dev-section">
                    <h5>Clients de test</h5>
                    <button class="btn btn-primary" onclick="generateTestClients()">
                        Générer 10 clients fictifs
                    </button>
                    <div id="test-clients-result"></div>
                </div>
                
                <div class="dev-section">
                    <h5>Produits ADR de test</h5>
                    <button class="btn btn-primary" onclick="generateTestProducts()">
                        Générer produits ADR
                    </button>
                    <div id="test-products-result"></div>
                </div>
                
                <div class="dev-section">
                    <h5>Expéditions de test</h5>
                    <input type="number" id="expeditions-count" value="5" min="1" max="50" style="width: 80px;">
                    <button class="btn btn-primary" onclick="generateTestExpeditions()">
                        Générer expéditions
                    </button>
                    <div id="test-expeditions-result"></div>
                </div>
            </div>
            
            <!-- Onglet Test API -->
            <div id="dev-tab-api-test" class="dev-tab-content">
                <h4>🔌 Tests API</h4>
                
                <div class="dev-section">
                    <h5>Test recherche produits</h5>
                    <input type="text" id="search-query" placeholder="Code ou nom produit">
                    <button class="btn btn-primary" onclick="testProductSearch()">
                        Tester recherche
                    </button>
                    <pre id="search-result" class="api-result"></pre>
                </div>
                
                <div class="dev-section">
                    <h5>Test validation expédition</h5>
                    <textarea id="expedition-data" rows="6" placeholder="JSON de l'expédition"></textarea>
                    <button class="btn btn-primary" onclick="testExpeditionValidation()">
                        Valider
                    </button>
                    <pre id="validation-result" class="api-result"></pre>
                </div>
            </div>
            
            <!-- Onglet Debug -->
            <div id="dev-tab-debug" class="dev-tab-content">
                <h4>🐛 Informations de debug</h4>
                
                <div class="dev-section">
                    <h5>Session ADR</h5>
                    <pre id="session-info"><?= json_encode($_SESSION, JSON_PRETTY_PRINT) ?></pre>
                </div>
                
                <div class="dev-section">
                    <h5>Configuration</h5>
                    <pre id="config-info"><?= json_encode([
                        'module_enabled' => true,
                        'auth_mode' => 'dev',
                        'database' => 'connected',
                        'php_version' => PHP_VERSION
                    ], JSON_PRETTY_PRINT) ?></pre>
                </div>
                
                <div class="dev-section">
                    <h5>Logs récents</h5>
                    <button class="btn btn-secondary" onclick="loadRecentLogs()">
                        Charger logs
                    </button>
                    <pre id="logs-content"></pre>
                </div>
            </div>
            
            <!-- Onglet Générateurs -->
            <div id="dev-tab-generators" class="dev-tab-content">
                <h4>⚙️ Générateurs de code</h4>
                
                <div class="dev-section">
                    <h5>Générateur SQL</h5>
                    <select id="sql-type">
                        <option value="create-table">CREATE TABLE</option>
                        <option value="insert-data">INSERT DATA</option>
                        <option value="select-query">SELECT QUERY</option>
                    </select>
                    <button class="btn btn-primary" onclick="generateSQL()">
                        Générer SQL
                    </button>
                    <textarea id="sql-output" rows="8" readonly></textarea>
                </div>
                
                <div class="dev-section">
                    <h5>Générateur de formulaire</h5>
                    <input type="text" id="form-name" placeholder="Nom du formulaire">
                    <button class="btn btn-primary" onclick="generateForm()">
                        Générer HTML
                    </button>
                    <textarea id="form-output" rows="8" readonly></textarea>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="clearAllTestData()">
                🗑️ Nettoyer données test
            </button>
            <button class="btn btn-secondary" onclick="closeDevModal()">
                Fermer
            </button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
}

.modal-header {
    background: #ff6b35;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 4px;
}

.modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.modal-body {
    padding: 20px;
    max-height: calc(90vh - 140px);
    overflow-y: auto;
}

.modal-footer {
    background: #f8f9fa;
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
}

.dev-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
    font-weight: 500;
}

.tab-btn.active {
    background: #ff6b35;
    color: white;
}

.dev-tab-content {
    display: none;
}

.dev-tab-content.active {
    display: block;
}

.dev-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #ff6b35;
}

.dev-section h5 {
    margin: 0 0 15px 0;
    color: #ff6b35;
}

.api-result {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    margin-right: 10px;
}

.btn-primary {
    background: #ff6b35;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

input, textarea, select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
    margin-bottom: 10px;
}

textarea {
    width: 100%;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}
</style>

<script>
// Fonctions de gestion des onglets
function showDevTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.dev-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    document.getElementById(`dev-tab-${tabName}`).classList.add('active');
    event.target.classList.add('active');
}

// Génération de données de test
function generateTestClients() {
    const clients = [
        'SARL MARTIN PLOMBERIE - 67000 Strasbourg',
        'ENTREPRISE SCHMIDT - 68100 Mulhouse',
        'SAS RENOVATION ALSACE - 67200 Strasbourg',
        'EURL TRAVAUX DUPONT - 68200 Mulhouse',
        'ARTISAN WEBER - 67500 Haguenau'
    ];
    
    document.getElementById('test-clients-result').innerHTML = 
        '<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
        '✅ ' + clients.length + ' clients générés :<br>' +
        clients.map(c => `• ${c}`).join('<br>') +
        '</div>';
}

function generateTestProducts() {
    const products = [
        'GULTRAT pH+ (UN 1823)',
        'PERFORMAX (UN 3265)', 
        'ALKADOSE (UN 1824)',
        'CHLORE LIQUIDE (UN 1791)',
        'ACIDE MURIATIQUE (UN 1789)'
    ];
    
    document.getElementById('test-products-result').innerHTML = 
        '<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
        '✅ ' + products.length + ' produits ADR générés :<br>' +
        products.map(p => `• ${p}`).join('<br>') +
        '</div>';
}

function generateTestExpeditions() {
    const count = document.getElementById('expeditions-count').value;
    const expeditions = [];
    
    for (let i = 1; i <= count; i++) {
        expeditions.push(`EXP-${new Date().getFullYear()}${String(new Date().getMonth()+1).padStart(2,'0')}${String(i).padStart(3,'0')}`);
    }
    
    document.getElementById('test-expeditions-result').innerHTML = 
        '<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
        `✅ ${count} expéditions générées :<br>` +
        expeditions.map(e => `• ${e}`).join('<br>') +
        '</div>';
}

// Tests API
function testProductSearch() {
    const query = document.getElementById('search-query').value;
    
    if (!query) {
        alert('Veuillez saisir un terme de recherche');
        return;
    }
    
    // Simulation d'appel API
    const mockResult = {
        success: true,
        query: query,
        results: [
            {
                code: 'GUL-001',
                nom: 'GULTRAT pH+',
                numero_un: '1823',
                categorie: '8'
            },
            {
                code: 'GUL-002', 
                nom: 'PERFORMAX',
                numero_un: '3265',
                categorie: '3'
            }
        ],
        count: 2,
        execution_time: '15ms'
    };
    
    document.getElementById('search-result').textContent = JSON.stringify(mockResult, null, 2);
}

function testExpeditionValidation() {
    const data = document.getElementById('expedition-data').value;
    
    if (!data) {
        alert('Veuillez saisir des données JSON');
        return;
    }
    
    try {
        JSON.parse(data);
        
        const mockResult = {
            success: true,
            validation: {
                destinataire: 'OK',
                produits: 'OK', 
                quotas: 'OK'
            },
            warnings: [],
            errors: []
        };
        
        document.getElementById('validation-result').textContent = JSON.stringify(mockResult, null, 2);
    } catch (e) {
        document.getElementById('validation-result').textContent = 'Erreur JSON: ' + e.message;
    }
}

// Debug
function loadRecentLogs() {
    const mockLogs = [
        '[2025-01-15 14:30:21] ADR_SEARCH: Recherche produit "GULTRAT"',
        '[2025-01-15 14:29:45] ADR_CREATE: Expédition ADR-20250115-001 créée',
        '[2025-01-15 14:28:12] ADR_LOGIN: Utilisateur demo.user connecté',
        '[2025-01-15 14:27:33] ADR_QUOTA: Vérification quotas Heppner'
    ];
    
    document.getElementById('logs-content').textContent = mockLogs.join('\n');
}

// Générateurs
function generateSQL() {
    const type = document.getElementById('sql-type').value;
    let sql = '';
    
    switch (type) {
        case 'create-table':
            sql = `CREATE TABLE gul_adr_expeditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expedition VARCHAR(50) UNIQUE NOT NULL,
    destinataire TEXT NOT NULL,
    transporteur VARCHAR(50) NOT NULL,
    date_expedition DATE NOT NULL,
    produits TEXT NOT NULL,
    cree_par VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);`;
            break;
            
        case 'select-query':
            sql = `SELECT 
    e.numero_expedition,
    e.destinataire,
    e.transporteur,
    e.date_expedition,
    COUNT(l.id) as nb_lignes,
    SUM(l.points_adr) as total_points
FROM gul_adr_expeditions e
LEFT JOIN gul_adr_expedition_lignes l ON e.id = l.expedition_id
WHERE e.date_expedition >= CURDATE()
GROUP BY e.id
ORDER BY e.created_at DESC;`;
            break;
    }
    
    document.getElementById('sql-output').value = sql;
}

function generateForm() {
    const name = document.getElementById('form-name').value || 'MonFormulaire';
    
    const formHtml = `<form id="${name.toLowerCase()}" class="adr-form">
    <div class="form-group">
        <label for="${name.toLowerCase()}_destinataire">Destinataire *</label>
        <textarea class="form-control" 
                  id="${name.toLowerCase()}_destinataire" 
                  name="destinataire" 
                  required></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="${name.toLowerCase()}_transporteur">Transporteur *</label>
            <select class="form-control" id="${name.toLowerCase()}_transporteur" name="transporteur" required>
                <option value="">Sélectionner...</option>
                <option value="heppner">Heppner</option>
                <option value="xpo">XPO Logistics</option>
                <option value="kn">Kuehne + Nagel</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="${name.toLowerCase()}_date">Date *</label>
            <input type="date" class="form-control" id="${name.toLowerCase()}_date" name="date" required>
        </div>
    </div>
    
    <div class="form-group">
        <label for="${name.toLowerCase()}_produits">Produits ADR *</label>
        <textarea class="form-control" 
                  id="${name.toLowerCase()}_produits" 
                  name="produits" 
                  rows="4" 
                  required></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Valider</button>
        <button type="reset" class="btn btn-secondary">Réinitialiser</button>
    </div>
</form>`;
    
    document.getElementById('form-output').value = formHtml;
}

// Nettoyage
function clearAllTestData() {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer toutes les données de test ?')) {
        // En production, ceci ferait des appels AJAX pour nettoyer la base
        alert('🗑️ Données de test supprimées (simulation)');
        
        // Nettoyer l'affichage
        document.getElementById('test-clients-result').innerHTML = '';
        document.getElementById('test-products-result').innerHTML = '';
        document.getElementById('test-expeditions-result').innerHTML = '';
    }
}

// Gestion de la modal
function closeDevModal() {
    document.getElementById('dev-modal').style.display = 'none';
}

function showDevModal() {
    document.getElementById('dev-modal').style.display = 'flex';
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDevModal();
    }
    
    // Ctrl+D pour ouvrir les outils dev
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        showDevModal();
    }
});

console.log('🛠️ Outils de développement ADR chargés');
console.log('💡 Raccourci : Ctrl+D pour ouvrir les outils');
</script>
            
        case 'insert-data':
            sql = `INSERT INTO gul_adr_expeditions 
(numero_expedition, destinataire, transporteur, date_expedition, produits, cree_par) 
VALUES 
('ADR-20250115-001', 'SARL MARTIN - 67000 Strasbourg', 'heppner', '2025-01-15', 'GULTRAT pH+ : 25L', 'demo.user'),
('ADR-20250115-002', 'ENTREPRISE SCHMIDT - 68100 Mulhouse', 'xpo', '2025-01-15', 'PERFORMAX : 200L', 'demo.user');`;
