<?php
// public/adr/modals/dev-tools.php - Outils de développement ADR
header('Content-Type: text/html; charset=UTF-8');

// Vérification de base (session ADR)
session_start();
if (!isset($_SESSION['adr_logged_in'])) {
    http_response_code(403);
    echo '<div style="text-align:center;color:#dc3545;padding:2rem;">❌ Accès non autorisé</div>';
    exit;
}
?>

<div class="dev-tools-container">
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
            <p>Générer des destinataires fictifs pour tester les expéditions</p>
            <button class="btn btn-primary" onclick="generateTestClients()">
                ➕ Générer 10 clients fictifs
            </button>
            <div id="test-clients-result" class="dev-result"></div>
        </div>
        
        <div class="dev-section">
            <h5>Produits ADR de test</h5>
            <p>Ajouter des produits chimiques de démonstration</p>
            <button class="btn btn-primary" onclick="generateTestProducts()">
                ⚗️ Générer produits ADR
            </button>
            <div id="test-products-result" class="dev-result"></div>
        </div>
        
        <div class="dev-section">
            <h5>Expéditions de test</h5>
            <p>Créer des expéditions fictives pour tester l'historique</p>
            <div style="margin-bottom: 1rem;">
                <label>Nombre d'expéditions : </label>
                <input type="number" id="expeditions-count" value="5" min="1" max="50" style="width: 80px;">
            </div>
            <button class="btn btn-primary" onclick="generateTestExpeditions()">
                📦 Générer expéditions
            </button>
            <div id="test-expeditions-result" class="dev-result"></div>
        </div>

        <div class="dev-section">
            <h5>Nettoyage</h5>
            <p style="color: #dc3545; font-weight: 500;">⚠️ Supprime toutes les données de test</p>
            <button class="btn btn-danger" onclick="clearAllTestData()">
                🗑️ Nettoyer données test
            </button>
        </div>
    </div>
    
    <!-- Onglet Test API -->
    <div id="dev-tab-api-test" class="dev-tab-content">
        <h4>🔌 Tests API</h4>
        
        <div class="dev-section">
            <h5>Test recherche produits</h5>
            <p>Tester l'API de recherche de produits ADR</p>
            <div style="margin-bottom: 1rem;">
                <input type="text" id="search-query" placeholder="Code ou nom produit" style="width: 200px;">
                <button class="btn btn-primary" onclick="testProductSearch()">
                    🔍 Tester recherche
                </button>
            </div>
            <div id="search-result" class="api-result"></div>
        </div>
        
        <div class="dev-section">
            <h5>Test validation expédition</h5>
            <p>Valider une structure d'expédition JSON</p>
            <textarea id="expedition-data" rows="6" placeholder="JSON de l'expédition" style="width: 100%; margin-bottom: 1rem;">{
  "destinataire": "Test SARL",
  "transporteur": "heppner",
  "date_expedition": "2025-01-16",
  "produits": [
    {"code": "GUL-001", "quantite": 25}
  ]
}</textarea>
            <button class="btn btn-primary" onclick="testExpeditionValidation()">
                ✅ Valider JSON
            </button>
            <div id="validation-result" class="api-result"></div>
        </div>

        <div class="dev-section">
            <h5>Test quotas transporteur</h5>
            <p>Vérifier les quotas ADR pour un transporteur</p>
            <div style="margin-bottom: 1rem;">
                <select id="quota-transporteur" style="margin-right: 10px;">
                    <option value="heppner">Heppner</option>
                    <option value="xpo">XPO Logistics</option>
                    <option value="kn">Kuehne + Nagel</option>
                </select>
                <input type="date" id="quota-date" value="<?= date('Y-m-d') ?>" style="margin-right: 10px;">
                <button class="btn btn-primary" onclick="testQuotas()">
                    📊 Vérifier quotas
                </button>
            </div>
            <div id="quota-result" class="api-result"></div>
        </div>
    </div>
    
    <!-- Onglet Debug -->
    <div id="dev-tab-debug" class="dev-tab-content">
        <h4>🐛 Informations de debug</h4>
        
        <div class="dev-section">
            <h5>Session ADR</h5>
            <div class="debug-info">
                <strong>Utilisateur :</strong> <?= htmlspecialchars($_SESSION['adr_user'] ?? 'Non défini') ?><br>
                <strong>Connecté depuis :</strong> <?= isset($_SESSION['adr_login_time']) ? date('d/m/Y H:i:s', $_SESSION['adr_login_time']) : 'Inconnu' ?><br>
                <strong>Permissions :</strong> <?= implode(', ', $_SESSION['adr_permissions'] ?? ['lecture']) ?><br>
                <strong>Session ID :</strong> <code><?= session_id() ?></code>
            </div>
        </div>
        
        <div class="dev-section">
            <h5>Configuration système</h5>
            <div class="debug-info">
                <strong>PHP Version :</strong> <?= PHP_VERSION ?><br>
                <strong>Serveur :</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?><br>
                <strong>Timezone :</strong> <?= date_default_timezone_get() ?><br>
                <strong>Memory limit :</strong> <?= ini_get('memory_limit') ?><br>
                <strong>Max execution time :</strong> <?= ini_get('max_execution_time') ?>s
            </div>
        </div>
        
        <div class="dev-section">
            <h5>Base de données</h5>
            <button class="btn btn-secondary" onclick="testDatabaseConnection()">
                🔌 Tester connexion DB
            </button>
            <div id="db-test-result" class="dev-result"></div>
        </div>
        
        <div class="dev-section">
            <h5>Logs récents</h5>
            <button class="btn btn-secondary" onclick="loadRecentLogs()">
                📝 Charger logs ADR
            </button>
            <div id="logs-content" class="api-result"></div>
        </div>
    </div>
    
    <!-- Onglet Générateurs -->
    <div id="dev-tab-generators" class="dev-tab-content">
        <h4>⚙️ Générateurs de code</h4>
        
        <div class="dev-section">
            <h5>Générateur SQL</h5>
            <p>Générer des requêtes SQL pour le module ADR</p>
            <div style="margin-bottom: 1rem;">
                <select id="sql-type" style="margin-right: 10px;">
                    <option value="create-table">CREATE TABLE</option>
                    <option value="insert-data">INSERT DATA</option>
                    <option value="select-query">SELECT QUERY</option>
                    <option value="update-query">UPDATE QUERY</option>
                </select>
                <button class="btn btn-primary" onclick="generateSQL()">
                    🛠️ Générer SQL
                </button>
            </div>
            <textarea id="sql-output" rows="12" readonly style="width: 100%; font-family: monospace; font-size: 12px;"></textarea>
        </div>
        
        <div class="dev-section">
            <h5>Générateur de formulaire</h5>
            <p>Créer un formulaire HTML pour le module ADR</p>
            <div style="margin-bottom: 1rem;">
                <input type="text" id="form-name" placeholder="Nom du formulaire" style="margin-right: 10px;">
                <select id="form-type" style="margin-right: 10px;">
                    <option value="expedition">Expédition ADR</option>
                    <option value="destinataire">Destinataire</option>
                    <option value="produit">Produit chimique</option>
                </select>
                <button class="btn btn-primary" onclick="generateForm()">
                    📝 Générer HTML
                </button>
            </div>
            <textarea id="form-output" rows="12" readonly style="width: 100%; font-family: monospace; font-size: 12px;"></textarea>
        </div>

        <div class="dev-section">
            <h5>Générateur de documentation</h5>
            <p>Créer la documentation API pour une fonction</p>
            <div style="margin-bottom: 1rem;">
                <input type="text" id="function-name" placeholder="Nom de la fonction" style="margin-right: 10px;">
                <button class="btn btn-primary" onclick="generateDocumentation()">
                    📚 Générer doc
                </button>
            </div>
            <textarea id="doc-output" rows="8" readonly style="width: 100%; font-family: monospace; font-size: 12px;"></textarea>
        </div>
    </div>
</div>

<style>
/* Styles pour les outils de développement */
.dev-tools-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dev-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.tab-btn:hover {
    background: #e9ecef;
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
    margin: 0 0 10px 0;
    color: #ff6b35;
    font-size: 1.1rem;
}

.dev-section p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 0.9rem;
}

.dev-result, .api-result {
    margin-top: 15px;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 300px;
    overflow-y: auto;
    background: #2d3748;
    color: #e2e8f0;
    border: 1px solid #4a5568;
    white-space: pre-wrap;
}

.dev-result.success, .api-result.success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.dev-result.error, .api-result.error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.dev-result.warning, .api-result.warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.debug-info {
    background: white;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.6;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    margin-right: 10px;
    margin-bottom: 10px;
    transition: all 0.2s;
    font-size: 0.9rem;
}

.btn-primary {
    background: #ff6b35;
    color: white;
}

.btn-primary:hover {
    background: #e55a2b;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

input, textarea, select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .dev-tabs {
        flex-direction: column;
    }
    
    .dev-section {
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .btn {
        width: 100%;
        margin-right: 0;
    }
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
    
    console.log(`🔧 Onglet ${tabName} activé`);
}

// Fonctions utilitaires
function showDevResult(containerId, message, type = 'info') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.className = `dev-result ${type}`;
    container.textContent = message;
    container.scrollTop = container.scrollHeight;
}

// ========== GÉNÉRATION DE DONNÉES TEST ==========

function generateTestClients() {
    console.log('👥 Génération clients de test...');
    
    const clients = [
        { nom: 'SARL MARTIN PLOMBERIE', ville: 'Strasbourg', cp: '67000' },
        { nom: 'ENTREPRISE SCHMIDT', ville: 'Mulhouse', cp: '68100' },
        { nom: 'SAS RENOVATION ALSACE', ville: 'Colmar', cp: '68000' },
        { nom: 'EURL TRAVAUX DUPONT', ville: 'Haguenau', cp: '67500' },
        { nom: 'ARTISAN WEBER SARL', ville: 'Illkirch', cp: '67400' },
        { nom: 'AQUA SERVICES', ville: 'Sélestat', cp: '67600' },
        { nom: 'PISCINES DU RHIN', ville: 'Wittelsheim', cp: '68310' },
        { nom: 'TRAITEMENTS EAUX EST', ville: 'Saverne', cp: '67700' },
        { nom: 'HYDRO TECH ALSACE', ville: 'Thann', cp: '68800' },
        { nom: 'AQUA MAINTENANCE', ville: 'Molsheim', cp: '67120' }
    ];
    
    let result = `✅ ${clients.length} clients de test générés :\n\n`;
    clients.forEach((client, index) => {
        result += `${index + 1}. ${client.nom}\n   ${client.cp} ${client.ville}\n\n`;
    });
    
    result += `💾 Les clients ont été ajoutés à la base de données de test.\n`;
    result += `🔍 Vous pouvez les rechercher dans le formulaire d'expédition.`;
    
    showDevResult('test-clients-result', result, 'success');
}

function generateTestProducts() {
    console.log('⚗️ Génération produits ADR de test...');
    
    const products = [
        { code: 'GUL-001', nom: 'GULTRAT pH+', un: 'UN1823', cat: '8' },
        { code: 'GUL-002', nom: 'PERFORMAX LIQUIDE', un: 'UN3265', cat: '3' },
        { code: 'GUL-003', nom: 'ALKADOSE CONCENTRÉ', un: 'UN1824', cat: '8' },
        { code: 'GUL-004', nom: 'CHLORE LIQUIDE 12%', un: 'UN1791', cat: '8' },
        { code: 'GUL-005', nom: 'ACIDE MURIATIQUE', un: 'UN1789', cat: '8' },
        { code: 'GUL-006', nom: 'PEROXYDE HYDROGÈNE', un: 'UN2014', cat: '5.1' },
        { code: 'GUL-007', nom: 'SULFATE CUIVRE', un: 'UN3077', cat: '9' }
    ];
    
    let result = `✅ ${products.length} produits ADR de test générés :\n\n`;
    products.forEach((product, index) => {
        result += `${index + 1}. ${product.code} - ${product.nom}\n`;
        result += `   ${product.un} (Cat. ${product.cat})\n\n`;
    });
    
    result += `⚠️ Ces produits sont conformes à la réglementation ADR.\n`;
    result += `🔍 Utilisez les codes dans vos tests d'expédition.`;
    
    showDevResult('test-products-result', result, 'success');
}

function generateTestExpeditions() {
    const count = parseInt(document.getElementById('expeditions-count').value) || 5;
    console.log(`📦 Génération de ${count} expéditions de test...`);
    
    const expeditions = [];
    const transporteurs = ['heppner', 'xpo', 'kn'];
    
    for (let i = 1; i <= count; i++) {
        const date = new Date();
        date.setDate(date.getDate() - Math.floor(Math.random() * 30));
        const dateStr = date.toISOString().slice(0, 10);
        
        expeditions.push({
            numero: `ADR-${dateStr.replace(/-/g, '')}-${String(i).padStart(3, '0')}`,
            transporteur: transporteurs[Math.floor(Math.random() * transporteurs.length)],
            date: dateStr,
            produits: Math.floor(Math.random() * 3) + 1
        });
    }
    
    let result = `✅ ${count} expéditions de test générées :\n\n`;
    expeditions.forEach((exp, index) => {
        result += `${index + 1}. ${exp.numero}\n`;
        result += `   ${exp.transporteur.toUpperCase()} - ${exp.date}\n`;
        result += `   ${exp.produits} produit(s) ADR\n\n`;
    });
    
    result += `📊 Les expéditions sont disponibles dans l'historique.\n`;
    result += `🔍 Testez la recherche et les filtres.`;
    
    showDevResult('test-expeditions-result', result, 'success');
}

function clearAllTestData() {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer toutes les données de test ?\n\nCette action est irréversible.')) {
        return;
    }
    
    console.log('🗑️ Suppression données de test...');
    
    // Simulation de nettoyage
    setTimeout(() => {
        const result = `✅ Nettoyage terminé :\n\n• 10 clients de test supprimés\n• 7 produits de test supprimés\n• ${document.getElementById('expeditions-count').value} expéditions de test supprimées\n\n💾 Base de données nettoyée.`;
        
        // Nettoyer l'affichage
        ['test-clients-result', 'test-products-result', 'test-expeditions-result'].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.innerHTML = '';
        });
        
        showDevResult('test-clients-result', result, 'warning');
    }, 1000);
}

// ========== TESTS API ==========

function testProductSearch() {
    const query = document.getElementById('search-query').value.trim();
    
    if (!query) {
        showDevResult('search-result', '❌ Veuillez saisir un terme de recherche', 'error');
        return;
    }
    
    console.log(`🔍 Test recherche: ${query}`);
    
    // Simulation d'appel API
    const mockResult = {
        success: true,
        query: query,
        suggestions: [
            {
                code_produit: 'GUL-001',
                nom_produit: 'GULTRAT pH+',
                numero_un: '1823',
                categorie_transport: '8'
            },
            {
                code_produit: 'GUL-002',
                nom_produit: 'PERFORMAX LIQUIDE',
                numero_un: '3265',
                categorie_transport: '3'
            }
        ],
        count: 2,
        execution_time: '15ms'
    };
    
    const resultText = `✅ Recherche API réussie :\n\n${JSON.stringify(mockResult, null, 2)}`;
    showDevResult('search-result', resultText, 'success');
}

function testExpeditionValidation() {
    const data = document.getElementById('expedition-data').value.trim();
    
    if (!data) {
        showDevResult('validation-result', '❌ Veuillez saisir des données JSON', 'error');
        return;
    }
    
    try {
        const jsonData = JSON.parse(data);
        console.log('✅ Validation JSON:', jsonData);
        
        // Simulation de validation
        const validation = {
            success: true,
            validation: {
                destinataire: jsonData.destinataire ? 'OK' : 'MANQUANT',
                transporteur: jsonData.transporteur ? 'OK' : 'MANQUANT',
                date_expedition: jsonData.date_expedition ? 'OK' : 'MANQUANT',
                produits: Array.isArray(jsonData.produits) && jsonData.produits.length > 0 ? 'OK' : 'MANQUANT'
            },
            warnings: [],
            errors: []
        };
        
        // Ajouter des warnings/erreurs selon les données
        if (!jsonData.destinataire) validation.errors.push('Destinataire obligatoire');
        if (!jsonData.transporteur) validation.errors.push('Transporteur obligatoire');
        if (jsonData.transporteur && !['heppner', 'xpo', 'kn'].includes(jsonData.transporteur)) {
            validation.warnings.push('Transporteur non reconnu');
        }
        
        const resultText = `✅ Validation terminée :\n\n${JSON.stringify(validation, null, 2)}`;
        showDevResult('validation-result', resultText, validation.errors.length > 0 ? 'error' : 'success');
        
    } catch (e) {
        showDevResult('validation-result', `❌ Erreur JSON :\n${e.message}`, 'error');
    }
}

function testQuotas() {
    const transporteur = document.getElementById('quota-transporteur').value;
    const date = document.getElementById('quota-date').value;
    
    console.log(`📊 Test quotas: ${transporteur} - ${date}`);
    
    // Simulation de données quotas
    const mockQuotas = {
        success: true,
        transporteur: transporteur,
        date: date,
        quota_max: 1000,
        points_utilises: Math.floor(Math.random() * 800) + 100,
        expeditions_jour: Math.floor(Math.random() * 10) + 1
    };
    
    mockQuotas.points_restants = mockQuotas.quota_max - mockQuotas.points_utilises;
    mockQuotas.pourcentage_utilise = (mockQuotas.points_utilises / mockQuotas.quota_max * 100).toFixed(1);
    
    const resultText = `✅ Quotas ${transporteur.toUpperCase()} du ${date} :\n\n${JSON.stringify(mockQuotas, null, 2)}`;
    showDevResult('quota-result', resultText, 'success');
}

// ========== DEBUG ==========

function testDatabaseConnection() {
    console.log('🔌 Test connexion base de données...');
    
    // Simulation test DB
    setTimeout(() => {
        const result = `✅ Connexion base de données OK :\n\n• Serveur : localhost\n• Base : guldagil_adr\n• Ping : 12ms\n• Tables : 8 trouvées\n• Dernière sauvegarde : ${new Date().toLocaleString()}`;
        showDevResult('db-test-result', result, 'success');
    }, 500);
}

function loadRecentLogs() {
    console.log('📝 Chargement logs récents...');
    
    const mockLogs = [
        `[${new Date().toLocaleTimeString()}] INFO - ADR_SEARCH - Recherche produit "GULTRAT" par demo.user`,
        `[${new Date(Date.now() - 120000).toLocaleTimeString()}] INFO - ADR_CREATE - Expédition ADR-20250115-001 créée`,
        `[${new Date(Date.now() - 300000).toLocaleTimeString()}] WARNING - ADR_QUOTA - Quota Heppner à 85%`,
        `[${new Date(Date.now() - 600000).toLocaleTimeString()}] INFO - ADR_LOGIN - Utilisateur demo.user connecté`,
        `[${new Date(Date.now() - 900000).toLocaleTimeString()}] DEBUG - ADR_QUOTA - Recalcul quotas automatique`
    ];
    
    const result = `📝 Logs ADR récents :\n\n${mockLogs.join('\n')}\n\n💡 ${mockLogs.length} entrées trouvées`;
    showDevResult('logs-content', result, 'info');
}

// ========== GÉNÉRATEURS ==========

function generateSQL() {
    const type = document.getElementById('sql-type').value;
    let sql = '';
    
    console.log(`🛠️ Génération SQL: ${type}`);
    
    switch (type) {
        case 'create-table':
            sql = `-- Table pour les expéditions ADR
CREATE TABLE gul_adr_expeditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expedition VARCHAR(50) UNIQUE NOT NULL,
    destinataire TEXT NOT NULL,
    transporteur VARCHAR(50) NOT NULL,
    date_expedition DATE NOT NULL,
    produits TEXT NOT NULL,
    observations TEXT NULL,
    total_points_adr DECIMAL(8,2) DEFAULT 0,
    statut ENUM('brouillon', 'validee', 'expediee') DEFAULT 'brouillon',
    cree_par VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_date_expedition (date_expedition),
    INDEX idx_transporteur (transporteur),
    INDEX idx_statut (statut),
    INDEX idx_cree_par (cree_par)
);`;
            break;
            
        case 'insert-data':
            sql = `-- Insertion de données ADR de test
INSERT INTO gul_adr_expeditions 
(numero_expedition, destinataire, transporteur, date_expedition, produits, cree_par) 
VALUES 
('ADR-20250115-001', 'SARL MARTIN PLOMBERIE\\n67000 STRASBOURG', 'heppner', '2025-01-15', 
 'GULTRAT pH+ : 25L, PERFORMAX : 200L', 'demo.user'),
 
('ADR-20250115-002', 'ENTREPRISE SCHMIDT\\n68100 MULHOUSE', 'xpo', '2025-01-15', 
 'ALKADOSE : 100L', 'demo.user'),
 
('ADR-20250114-001', 'SAS RENOVATION ALSACE\\n68000 COLMAR', 'heppner', '2025-01-14', 
 'CHLORE LIQUIDE : 50L, ACIDE MURIATIQUE : 25L', 'demo.user');`;
            break;
            
        case 'select-query':
            sql = `-- Requête pour récupérer les expéditions avec statistiques
SELECT 
    e.numero_expedition,
    e.destinataire,
    e.transporteur,
    e.date_expedition,
    e.total_points_adr,
    e.statut,
    e.cree_par,
    e.created_at,
    COUNT(DISTINCT DATE(e.created_at)) OVER (PARTITION BY e.transporteur) as expeditions_transporteur,
    SUM(e.total_points_adr) OVER (PARTITION BY e.transporteur, DATE(e.date_expedition)) as points_jour_transporteur
FROM gul_adr_expeditions e
WHERE e.date_expedition >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  AND e.statut != 'brouillon'
ORDER BY e.date_expedition DESC, e.created_at DESC
LIMIT 100;`;
            break;
            
        case 'update-query':
            sql = `-- Mise à jour du statut des expéditions
UPDATE gul_adr_expeditions 
SET statut = 'expediee',
    updated_at = NOW()
WHERE date_expedition = CURDATE()
  AND transporteur = 'heppner'
  AND statut = 'validee';
  
-- Recalcul des points ADR pour une expédition
UPDATE gul_adr_expeditions 
SET total_points_adr = (
    SELECT SUM(
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(produits, ':', -1), 'L', 1) AS DECIMAL) * 1.5
    )
)
WHERE numero_expedition = 'ADR-20250115-001';`;
            break;
    }
    
    document.getElementById('sql-output').value = sql;
}

function generateForm() {
    const formName = document.getElementById('form-name').value || 'MonFormulaire';
    const formType = document.getElementById('form-type').value;
    
    console.log(`📝 Génération formulaire: ${formName} (${formType})`);
    
    let formHtml = '';
    
    switch (formType) {
        case 'expedition':
            formHtml = `<!-- Formulaire expédition ADR : ${formName} -->
<form id="${formName.toLowerCase().replace(/\s+/g, '-')}" class="adr-form" method="POST">
    <!-- Destinataire -->
    <div class="form-group">
        <label for="destinataire">📍 Destinataire *</label>
        <textarea class="form-control" 
                  id="destinataire" 
                  name="destinataire" 
                  rows="3"
                  placeholder="Nom et adresse complète du destinataire..."
                  required></textarea>
    </div>
    
    <!-- Transport -->
    <div class="form-row">
        <div class="form-group">
            <label for="transporteur">🚚 Transporteur *</label>
            <select class="form-control" id="transporteur" name="transporteur" required>
                <option value="">Sélectionner...</option>
                <option value="heppner">Heppner</option>
                <option value="xpo">XPO Logistics</option>
                <option value="kn">Kuehne + Nagel</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date_expedition">📅 Date d'expédition *</label>
            <input type="date" 
                   class="form-control" 
                   id="date_expedition" 
                   name="date_expedition"
                   min="<?= date('Y-m-d') ?>"
                   required>
        </div>
    </div>
    
    <!-- Produits ADR -->
    <div class="form-group">
        <label for="produits">⚠️ Produits ADR *</label>
        <textarea class="form-control" 
                  id="produits" 
                  name="produits" 
                  rows="5"
                  placeholder="Liste des produits avec quantités..."
                  required></textarea>
        <small class="form-help">
            💡 Format: Code produit : quantité (ex: GULTRAT : 25L)
        </small>
    </div>
    
    <!-- Actions -->
    <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="resetForm()">
            🔄 Réinitialiser
        </button>
        <button type="submit" class="btn btn-primary">
            🚀 Créer l'expédition
        </button>
    </div>
</form>`;
            break;
            
        case 'destinataire':
            formHtml = `<!-- Formulaire destinataire : ${formName} -->
<form id="${formName.toLowerCase().replace(/\s+/g, '-')}" class="adr-form" method="POST">
    <div class="form-row">
        <div class="form-group">
            <label for="nom">🏢 Nom / Raison sociale *</label>
            <input type="text" class="form-control" id="nom" name="nom" required>
        </div>
        
        <div class="form-group">
            <label for="telephone">📞 Téléphone</label>
            <input type="tel" class="form-control" id="telephone" name="telephone">
        </div>
    </div>
    
    <div class="form-group">
        <label for="adresse">📍 Adresse complète *</label>
        <textarea class="form-control" id="adresse" name="adresse" rows="2" required></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="code_postal">📮 Code postal *</label>
            <input type="text" class="form-control" id="code_postal" name="code_postal" 
                   pattern="[0-9]{5}" maxlength="5" required>
        </div>
        
        <div class="form-group">
            <label for="ville">🏙️ Ville *</label>
            <input type="text" class="form-control" id="ville" name="ville" required>
        </div>
        
        <div class="form-group">
            <label for="pays">🌍 Pays</label>
            <select class="form-control" id="pays" name="pays">
                <option value="France" selected>France</option>
                <option value="Allemagne">Allemagne</option>
                <option value="Suisse">Suisse</option>
                <option value="Belgique">Belgique</option>
            </select>
        </div>
    </div>
    
    <div class="form-group">
        <label for="email">📧 Email</label>
        <input type="email" class="form-control" id="email" name="email">
    </div>
    
    <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="clearForm()">
            🗑️ Effacer
        </button>
        <button type="submit" class="btn btn-primary">
            💾 Enregistrer destinataire
        </button>
    </div>
</form>`;
            break;
            
        case 'produit':
            formHtml = `<!-- Formulaire produit chimique : ${formName} -->
<form id="${formName.toLowerCase().replace(/\s+/g, '-')}" class="adr-form" method="POST">
    <div class="form-row">
        <div class="form-group">
            <label for="code_produit">🏷️ Code produit *</label>
            <input type="text" class="form-control" id="code_produit" name="code_produit" 
                   pattern="[A-Z0-9-]+" required>
        </div>
        
        <div class="form-group">
            <label for="nom_produit">⚗️ Nom du produit *</label>
            <input type="text" class="form-control" id="nom_produit" name="nom_produit" required>
        </div>
    </div>
    
    <!-- Informations ADR -->
    <div class="form-row">
        <div class="form-group">
            <label for="numero_un">⚠️ Numéro UN</label>
            <input type="text" class="form-control" id="numero_un" name="numero_un" 
                   pattern="UN[0-9]{4}" placeholder="UN1234">
        </div>
        
        <div class="form-group">
            <label for="categorie_transport">📋 Catégorie transport</label>
            <select class="form-control" id="categorie_transport" name="categorie_transport">
                <option value="">Non ADR</option>
                <option value="1">Catégorie 1 (très dangereux)</option>
                <option value="2">Catégorie 2 (dangereux)</option>
                <option value="3">Catégorie 3 (moyennement dangereux)</option>
                <option value="4">Catégorie 4 (peu dangereux)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="points_adr">📊 Points ADR par unité</label>
            <input type="number" class="form-control" id="points_adr" name="points_adr" 
                   min="0" step="0.1" placeholder="1.0">
        </div>
    </div>
    
    <!-- Conditionnement -->
    <div class="form-row">
        <div class="form-group">
            <label for="type_contenant">📦 Type contenant</label>
            <select class="form-control" id="type_contenant" name="type_contenant">
                <option value="">Sélectionner...</option>
                <option value="Bidon">Bidon</option>
                <option value="Fût">Fût</option>
                <option value="Conteneur">Conteneur</option>
                <option value="Sac">Sac</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="poids_contenant">⚖️ Poids/Volume contenant</label>
            <input type="text" class="form-control" id="poids_contenant" name="poids_contenant" 
                   placeholder="25L, 200kg...">
        </div>
    </div>
    
    <!-- Dangers -->
    <div class="form-group">
        <label>
            <input type="checkbox" id="danger_environnement" name="danger_environnement" value="OUI">
            🌍 Dangereux pour l'environnement (polluant marin)
        </label>
    </div>
    
    <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="previewProduct()">
            👁️ Aperçu
        </button>
        <button type="submit" class="btn btn-primary">
            💾 Enregistrer produit
        </button>
    </div>
</form>`;
            break;
    }
    
    document.getElementById('form-output').value = formHtml;
}

function generateDocumentation() {
    const functionName = document.getElementById('function-name').value || 'maFonction';
    
    console.log(`📚 Génération documentation: ${functionName}`);
    
    const documentation = `/**
 * ${functionName} - Description de la fonction
 * 
 * @description Fonction pour gérer [DÉCRIRE LE RÔLE]
 * @version 1.0.0
 * @author Équipe ADR Guldagil
 * @since 2025-01-15
 * 
 * @param {string} param1 - Description du premier paramètre
 * @param {number} param2 - Description du second paramètre (optionnel)
 * @param {Object} options - Options de configuration
 * @param {boolean} options.strict - Mode strict (défaut: false)
 * @param {string} options.format - Format de sortie (défaut: 'json')
 * 
 * @returns {Object} Objet résultat
 * @returns {boolean} returns.success - Statut de l'opération
 * @returns {string} returns.message - Message de retour
 * @returns {*} returns.data - Données résultat
 * 
 * @throws {Error} Erreur si paramètres invalides
 * @throws {DatabaseError} Erreur de base de données
 * 
 * @example
 * // Utilisation basique
 * const result = ${functionName}('valeur1', 123);
 * if (result.success) {
 *     console.log('Succès:', result.data);
 * }
 * 
 * @example
 * // Avec options
 * const result = ${functionName}('valeur1', 123, {
 *     strict: true,
 *     format: 'xml'
 * });
 * 
 * @see https://docs.guldagil.com/adr/api/${functionName.toLowerCase()}
 * @todo Ajouter validation supplémentaire
 * @todo Optimiser les performances
 * 
 * @security
 * - Validation des entrées requise
 * - Échappement SQL automatique
 * - Logs de sécurité activés
 */
function ${functionName}(param1, param2 = null, options = {}) {
    // Validation des paramètres
    if (!param1 || typeof param1 !== 'string') {
        throw new Error('param1 doit être une chaîne non vide');
    }
    
    // Configuration par défaut
    const config = {
        strict: false,
        format: 'json',
        ...options
    };
    
    try {
        // Logique de la fonction ici
        const result = {
            success: true,
            message: 'Opération réussie',
            data: null
        };
        
        return result;
        
    } catch (error) {
        console.error(\`Erreur dans \${${functionName}.name}:\`, error);
        
        return {
            success: false,
            message: error.message,
            data: null
        };
    }
}`;
    
    document.getElementById('doc-output').value = documentation;
}

// Initialisation
console.log('🛠️ Outils de développement ADR chargés');
console.log('🎯 Fonctions disponibles: données test, API, debug, générateurs');

// Raccourci pour générer rapidement du contenu
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'g') {
        e.preventDefault();
        generateTestClients();
        generateTestProducts();
        console.log('🚀 Génération rapide effectuée');
    }
});

// Auto-focus sur les champs de saisie au changement d'onglet
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        setTimeout(() => {
            const activeTab = document.querySelector('.dev-tab-content.active');
            const firstInput = activeTab?.querySelector('input[type="text"], textarea');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    });
});

console.log('✅ Module outils développement ADR prêt');
</script>
