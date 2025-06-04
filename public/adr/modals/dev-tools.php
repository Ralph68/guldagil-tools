<?php
// public/adr/modals/dev-tools.php - Outils de développement ADR optimisés
?>

<div class="dev-tabs">
    <button class="tab-btn active" onclick="showDevTab('test-data')">📊 Données test</button>
    <button class="tab-btn" onclick="showDevTab('api-test')">🔌 Test API</button>
    <button class="tab-btn" onclick="showDevTab('debug')">🐛 Debug</button>
    <button class="tab-btn" onclick="showDevTab('generators')">⚙️ Générateurs</button>
    <button class="tab-btn" onclick="showDevTab('performance')">⚡ Performance</button>
</div>

<!-- Onglet données de test -->
<div id="dev-tab-test-data" class="dev-tab-content active">
    <h4>📊 Génération de données de test</h4>
    
    <div class="dev-section">
        <h5>🏢 Clients de test</h5>
        <p>Génère des destinataires fictifs pour tester les expéditions ADR</p>
        <div class="dev-controls">
            <input type="number" id="clients-count" value="10" min="1" max="50" style="width: 80px;">
            <button class="btn btn-primary" onclick="generateTestClients()">
                Générer clients
            </button>
        </div>
        <div id="test-clients-result" class="dev-result"></div>
    </div>
    
    <div class="dev-section">
        <h5>📦 Produits ADR de test</h5>
        <p>Crée des produits avec numéros UN pour les tests</p>
        <div class="dev-controls">
            <select id="product-category">
                <option value="all">Toutes catégories</option>
                <option value="1">Catégorie 1 (Très dangereux)</option>
                <option value="2">Catégorie 2 (Dangereux)</option>
                <option value="3">Catégorie 3 (Modéré)</option>
            </select>
            <button class="btn btn-primary" onclick="generateTestProducts()">
                Générer produits
            </button>
        </div>
        <div id="test-products-result" class="dev-result"></div>
    </div>
    
    <div class="dev-section">
        <h5>🚛 Expéditions de test</h5>
        <p>Génère des expéditions complètes avec produits et destinataires</p>
        <div class="dev-controls">
            <input type="number" id="expeditions-count" value="5" min="1" max="20" style="width: 80px;">
            <select id="expedition-transporteur">
                <option value="random">Transporteur aléatoire</option>
                <option value="heppner">Heppner uniquement</option>
                <option value="xpo">XPO uniquement</option>
                <option value="kn">K+N uniquement</option>
            </select>
            <button class="btn btn-primary" onclick="generateTestExpeditions()">
                Générer expéditions
            </button>
        </div>
        <div id="test-expeditions-result" class="dev-result"></div>
    </div>
    
    <div class="dev-section warning-section">
        <h5>🗑️ Nettoyage des données de test</h5>
        <p style="color: #dc3545;">Attention : cette action supprime toutes les données de test générées</p>
        <button class="btn btn-danger" onclick="cleanupTestData()">
            🗑️ Supprimer toutes les données de test
        </button>
        <div id="cleanup-result" class="dev-result"></div>
    </div>
</div>

<!-- Onglet test API -->
<div id="dev-tab-api-test" class="dev-tab-content">
    <h4>🔌 Tests API ADR</h4>
    
    <div class="dev-section">
        <h5>🔍 Test recherche produits</h5>
        <div class="api-test-form">
            <input type="text" id="search-query" placeholder="Code ou nom produit" style="width: 200px;">
            <select id="search-type">
                <option value="suggestions">Suggestions</option>
                <option value="search">Recherche complète</option>
                <option value="detail">Détail produit</option>
            </select>
            <button class="btn btn-primary" onclick="testProductSearch()">
                Tester recherche
            </button>
        </div>
        <pre id="search-result" class="api-result"></pre>
    </div>
    
    <div class="dev-section">
        <h5>✅ Test validation expédition</h5>
        <textarea id="expedition-data" rows="8" placeholder="JSON de l'expédition à valider">{
  "destinataire": {
    "nom": "SARL TEST",
    "adresse": "123 Rue Test",
    "code_postal": "67000",
    "ville": "Strasbourg"
  },
  "transporteur": "heppner",
  "date_expedition": "2025-01-20",
  "produits": [
    {
      "code": "GUL-001",
      "quantite": 25,
      "points_adr": 25
    }
  ]
}</textarea>
        <button class="btn btn-primary" onclick="testExpeditionValidation()">
            Valider expédition
        </button>
        <pre id="validation-result" class="api-result"></pre>
    </div>
    
    <div class="dev-section">
        <h5>📊 Test calcul quotas</h5>
        <div class="api-test-form">
            <select id="quota-transporteur">
                <option value="heppner">Heppner</option>
                <option value="xpo">XPO Logistics</option>
                <option value="kn">Kuehne + Nagel</option>
            </select>
            <input type="date" id="quota-date" value="<?= date('Y-m-d') ?>">
            <button class="btn btn-primary" onclick="testQuotaCalculation()">
                Calculer quotas
            </button>
        </div>
        <pre id="quota-result" class="api-result"></pre>
    </div>
</div>

<!-- Onglet debug -->
<div id="dev-tab-debug" class="dev-tab-content">
    <h4>🐛 Informations de debug</h4>
    
    <div class="dev-section">
        <h5>🔐 Session ADR</h5>
        <div class="debug-info">
            <div class="debug-item">
                <span class="debug-label">Utilisateur :</span>
                <span class="debug-value"><?= htmlspecialchars($_SESSION['adr_user'] ?? 'Non défini') ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Connecté depuis :</span>
                <span class="debug-value"><?= isset($_SESSION['adr_login_time']) ? date('Y-m-d H:i:s', $_SESSION['adr_login_time']) : 'Inconnu' ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Permissions :</span>
                <span class="debug-value"><?= implode(', ', $_SESSION['adr_permissions'] ?? []) ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">ID Session :</span>
                <span class="debug-value"><?= session_id() ?></span>
            </div>
        </div>
        <button class="btn btn-secondary" onclick="refreshSessionInfo()">🔄 Actualiser</button>
    </div>
    
    <div class="dev-section">
        <h5>⚙️ Configuration système</h5>
        <div class="debug-info">
            <div class="debug-item">
                <span class="debug-label">Version PHP :</span>
                <span class="debug-value"><?= PHP_VERSION ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Base de données :</span>
                <span class="debug-value" id="db-status">Vérification...</span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Mode debug :</span>
                <span class="debug-value"><?= ini_get('display_errors') ? 'Activé' : 'Désactivé' ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Limite mémoire :</span>
                <span class="debug-value"><?= ini_get('memory_limit') ?></span>
            </div>
        </div>
        <button class="btn btn-primary" onclick="checkSystemHealth()">🩺 Vérifier système</button>
    </div>
    
    <div class="dev-section">
        <h5>📝 Logs récents</h5>
        <div class="log-controls">
            <select id="log-level-filter">
                <option value="">Tous les niveaux</option>
                <option value="ERROR">Erreurs uniquement</option>
                <option value="WARNING">Avertissements</option>
                <option value="INFO">Informations</option>
            </select>
            <button class="btn btn-secondary" onclick="loadRecentLogs()">📋 Charger logs</button>
            <button class="btn btn-danger" onclick="clearDebugLogs()">🗑️ Effacer logs</button>
        </div>
        <div id="logs-content" class="log-display"></div>
    </div>
</div>

<!-- Onglet générateurs -->
<div id="dev-tab-generators" class="dev-tab-content">
    <h4>⚙️ Générateurs de code</h4>
    
    <div class="dev-section">
        <h5>🗄️ Générateur SQL</h5>
        <div class="generator-controls">
            <select id="sql-type">
                <option value="create-table">CREATE TABLE</option>
                <option value="insert-data">INSERT DATA</option>
                <option value="select-query">SELECT QUERY</option>
                <option value="update-query">UPDATE QUERY</option>
            </select>
            <input type="text" id="table-name" placeholder="Nom de la table" style="width: 150px;">
            <button class="btn btn-primary" onclick="generateSQL()">
                Générer SQL
            </button>
        </div>
        <textarea id="sql-output" rows="10" readonly placeholder="Le code SQL généré apparaîtra ici..."></textarea>
        <button class="btn btn-secondary" onclick="copyToClipboard('sql-output')">📋 Copier</button>
    </div>
    
    <div class="dev-section">
        <h5>📝 Générateur de formulaire</h5>
        <div class="generator-controls">
            <input type="text" id="form-name" placeholder="Nom du formulaire" style="width: 200px;">
            <select id="form-type">
                <option value="expedition">Formulaire expédition</option>
                <option value="client">Formulaire client</option>
                <option value="produit">Formulaire produit</option>
                <option value="custom">Personnalisé</option>
            </select>
            <button class="btn btn-primary" onclick="generateForm()">
                Générer formulaire
            </button>
        </div>
        <textarea id="form-output" rows="10" readonly placeholder="Le code HTML du formulaire apparaîtra ici..."></textarea>
        <button class="btn btn-secondary" onclick="copyToClipboard('form-output')">📋 Copier</button>
    </div>
    
    <div class="dev-section">
        <h5>🔌 Générateur API</h5>
        <div class="generator-controls">
            <input type="text" id="api-endpoint" placeholder="Nom de l'endpoint" style="width: 200px;">
            <select id="api-method">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>
            <button class="btn btn-primary" onclick="generateAPI()">
                Générer API
            </button>
        </div>
        <textarea id="api-output" rows="10" readonly placeholder="Le code PHP de l'API apparaîtra ici..."></textarea>
        <button class="btn btn-secondary" onclick="copyToClipboard('api-output')">📋 Copier</button>
    </div>
</div>

<!-- Onglet performance -->
<div id="dev-tab-performance" class="dev-tab-content">
    <h4>⚡ Tests de performance</h4>
    
    <div class="dev-section">
        <h5>🚀 Benchmark recherche</h5>
        <p>Teste la vitesse de recherche avec différents volumes de données</p>
        <div class="benchmark-controls">
            <select id="benchmark-size">
                <option value="100">100 requêtes</option>
                <option value="500">500 requêtes</option>
                <option value="1000">1000 requêtes</option>
            </select>
            <button class="btn btn-primary" onclick="runSearchBenchmark()">
                🏃‍♂️ Lancer benchmark
            </button>
        </div>
        <div id="benchmark-results" class="dev-result"></div>
    </div>
    
    <div class="dev-section">
        <h5>📊 Profiling base de données</h5>
        <p>Analyse les requêtes lentes et l'utilisation des index</p>
        <button class="btn btn-primary" onclick="profileDatabase()">
            📈 Analyser BDD
        </button>
        <div id="profiling-results" class="dev-result"></div>
    </div>
    
    <div class="dev-section">
        <h5>🧪 Test de charge</h5>
        <p>Simule plusieurs utilisateurs simultanés</p>
        <div class="load-test-controls">
            <input type="number" id="concurrent-users" value="10" min="1" max="50" style="width: 80px;">
            <span>utilisateurs simultanés</span>
            <input type="number" id="test-duration" value="30" min="10" max="300" style="width: 80px;">
            <span>secondes</span>
            <button class="btn btn-warning" onclick="runLoadTest()">
                ⚡ Test de charge
            </button>
        </div>
        <div id="load-test-results" class="dev-result"></div>
    </div>
</div>

<style>
/* Styles pour les outils de développement */
.dev-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 8px 16px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    border-radius: 6px 6px 0 0;
    font-size: 0.9rem;
}

.tab-btn:hover {
    background: #e9ecef;
}

.tab-btn.active {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
}

.dev-tab-content {
    display: none;
}

.dev-tab-content.active {
    display: block;
}

.dev-section {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #ff6b35;
}

.dev-section.warning-section {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.dev-section h5 {
    margin: 0 0 10px 0;
    color: #ff6b35;
    font-size: 1rem;
}

.dev-section p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 0.9rem;
}

.dev-controls, .api-test-form, .generator-controls, .benchmark-controls, .load-test-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.dev-result, .api-result {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
    background: #2d3748;
    color: #e2e8f0;
    border: 1px solid #4a5568;
    white-space: pre-wrap;
}

.dev-result.success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.dev-result.error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.dev-result.warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.debug-info {
    background: white;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
}

.debug-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.debug-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.debug-label {
    font-weight: 600;
    color: #333;
}

.debug-value {
    color: #666;
    font-family: monospace;
}

.log-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.log-display {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    height: 200px;
    overflow-y: auto;
    border: 1px solid #4a5568;
    white-space: pre-wrap;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
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
    background: #5a6268;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

.btn-warning:hover {
    background: #e0a800;
}

input, textarea, select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

textarea {
    width: 100%;
    font-family: 'Courier New', monospace;
    resize: vertical;
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
    
    .dev-controls, .api-test-form, .generator-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .debug-item {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<script>
// Variables globales
let activeTab = 'test-data';

// Gestion des onglets
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
    
    activeTab = tabName;
    
    // Actions spécifiques par onglet
    switch(tabName) {
        case 'debug':
            checkSystemHealth();
            break;
        case 'performance':
            updatePerformanceMetrics();
            break;
    }
}

// ========== FONCTIONS DONNÉES DE TEST ==========

function generateTestClients() {
    const count = document.getElementById('clients-count').value;
    showDevResult('test-clients-result', 'Génération en cours...', 'info');
    
    setTimeout(() => {
        const clients = [];
        const villes = ['Strasbourg', 'Mulhouse', 'Colmar', 'Haguenau', 'Saverne'];
        const types = ['SARL', 'SAS', 'EURL', 'SA', 'Entreprise'];
        const metiers = ['PLOMBERIE', 'ÉLECTRICITÉ', 'CHAUFFAGE', 'CLIMATISATION', 'PISCINES'];
        
        for (let i = 1; i <= count; i++) {
            const type = types[Math.floor(Math.random() * types.length)];
            const metier = metiers[Math.floor(Math.random() * metiers.length)];
            const ville = villes[Math.floor(Math.random() * villes.length)];
            const cp = ville === 'Strasbourg' ? '67000' : ville === 'Mulhouse' ? '68100' : '68000';
            
            clients.push(`${type} ${metier} MARTIN ${i} - ${cp} ${ville}`);
        }
        
        const result = `✅ ${count} clients générés :\n\n${clients.join('\n')}`;
        showDevResult('test-clients-result', result, 'success');
    }, 1000);
}

function generateTestProducts() {
    const category = document.getElementById('product-category').value;
    showDevResult('test-products-result', 'Génération en cours...', 'info');
    
    setTimeout(() => {
        const products = [
            { code: 'GUL-001', nom: 'GULTRAT pH+', un: 'UN1823', cat: '2' },
            { code: 'GUL-002', nom: 'PERFORMAX PLUS', un: 'UN3265', cat: '3' },
            { code: 'GUL-003', nom: 'ALKADOSE 25%', un: 'UN1824', cat: '2' },
            { code: 'GUL-004', nom: 'CHLORE LIQUIDE', un: 'UN1791', cat: '1' },
            { code: 'GUL-005', nom: 'ACIDE MURIATIQUE', un: 'UN1789', cat: '2' }
        ];
        
        const filtered = category === 'all' ? products : products.filter(p => p.cat === category);
        
        const result = `✅ ${filtered.length} produits générés :\n\n` +
            filtered.map(p => `• ${p.code} - ${p.nom} (${p.un}, Cat.${p.cat})`).join('\n');
        
        showDevResult('test-products-result', result, 'success');
    }, 800);
}

function generateTestExpeditions() {
    const count = document.getElementById('expeditions-count').value;
    const transporteur = document.getElementById('expedition-transporteur').value;
    
    showDevResult('test-expeditions-result', 'Génération en cours...', 'info');
    
    setTimeout(() => {
        const expeditions = [];
        const transporteurs = transporteur === 'random' ? 
            ['heppner', 'xpo', 'kn'] : [transporteur];
        
        for (let i = 1; i <= count; i++) {
            const date = new Date();
            date.setDate(date.getDate() + Math.floor(Math.random() * 7));
            const dateStr = date.toISOString().split('T')[0];
            
            const transporteurChoisi = transporteurs[Math.floor(Math.random() * transporteurs.length)];
            const numero = `ADR-${dateStr.replace(/-/g, '')}-${String(i).padStart(3, '0')}`;
            
            expeditions.push(`${numero} - ${transporteurChoisi.toUpperCase()} - ${dateStr}`);
        }
        
        const result = `✅ ${count} expéditions générées :\n\n${expeditions.join('\n')}`;
        showDevResult('test-expeditions-result', result, 'success');
    }, 1200);
}

function cleanupTestData() {
    if (!confirm('⚠️ Supprimer toutes les données de test ?\n\nCette action est irréversible !')) {
        return;
    }
    
    showDevResult('cleanup-result', 'Nettoyage en cours...', 'warning');
    
    setTimeout(() => {
        // En production, ceci ferait des appels AJAX pour nettoyer la base
        const result = `✅ Nettoyage terminé\n\n` +
            `• Clients de test : 47 supprimés\n` +
            `• Produits de test : 23 supprimés\n` +
            `• Expéditions de test : 156 supprimées\n` +
            `• Espace libéré : 12.7 MB`;
        
        showDevResult('cleanup-result', result, 'success');
        
        // Nettoyer l'affichage des autres résultats
        ['test-clients-result', 'test-products-result', 'test-expeditions-result'].forEach(id => {
            document.getElementById(id).innerHTML = '';
        });
    }, 2000);
}

// ========== FONCTIONS TEST API ==========

function testProductSearch() {
    const query = document.getElementById('search-query').value;
    const type = document.getElementById('search-type').value;
    
    if (!query) {
        showDevResult('search-result', 'Erreur: Veuillez saisir un terme de recherche', 'error');
        return;
    }
    
    showDevResult('search-result', 'Recherche en cours...', 'info');
    
    setTimeout(() => {
        const mockResult = {
            success: true,
            type: type,
            query: query,
            results: [
                {
                    code: 'GUL-001',
                    nom: 'GULTRAT pH+',
                    numero_un: '1823',
                    categorie: '8',
                    points_adr: 1
                },
                {
                    code: 'GUL-002',
                    nom: 'PERFORMAX PLUS',
                    numero_un: '3265',
                    categorie: '3',
                    points_adr: 3
                }
            ],
            count: 2,
            execution_time: `${Math.floor(Math.random() * 50 + 20)}ms`
        };
        
        showDevResult('search-result', JSON.stringify(mockResult, null, 2), 'success');
    }, 500);
}

function testExpeditionValidation() {
    const data = document.getElementById('expedition-data').value;
    
    if (!data) {
        showDevResult('validation-result', 'Erreur: Veuillez saisir des données JSON', 'error');
        return;
    }
    
    showDevResult('validation-result', 'Validation en cours...', 'info');
    
    try {
        const expedition = JSON.parse(data);
        
        setTimeout(() => {
            const mockResult = {
                success: true,
                validation: {
                    destinataire: expedition.destinataire ? 'OK' : 'ERREUR',
                    transporteur: expedition.transporteur ? 'OK' : 'ERREUR',
                    produits: expedition.produits && expedition.produits.length > 0 ? 'OK' : 'ERREUR',
                    quotas: 'OK'
                },
                warnings: [
                    'Quota transporteur à 75%'
                ],
                errors: [],
                total_points: expedition.produits ? 
                    expedition.produits.reduce((sum, p) => sum + (p.points_adr || 0), 0) : 0
            };
            
            showDevResult('validation-result', JSON.stringify(mockResult, null, 2), 'success');
        }, 800);
        
    } catch (e) {
        showDevResult('validation-result', `Erreur JSON: ${e.message}`, 'error');
    }
}

function testQuotaCalculation() {
    const transporteur = document.getElementById('quota-transporteur').value;
    const date = document.getElementById('quota-date').value;
    
    showDevResult('quota-result', 'Calcul des quotas...', 'info');
    
    setTimeout(() => {
        const mockResult = {
            success: true,
            transporteur: transporteur,
            date: date,
            quota_max: 1000,
            points_utilises: Math.floor(Math.random() * 800 + 100),
            points_restants: 0, // sera calculé
            pourcentage_utilise: 0, // sera calculé
            expeditions_jour: Math.floor(Math.random() * 20 + 5)
        };
        
        mockResult.points_restants = mockResult.quota_max - mockResult.points_utilises;
        mockResult.pourcentage_utilise = (mockResult.points_utilises / mockResult.quota_max * 100).toFixed(1);
        
        showDevResult('quota-result', JSON.stringify(mockResult, null, 2), 'success');
    }, 600);
}

// ========== FONCTIONS DEBUG ==========

function refreshSessionInfo() {
    showDevResult('logs-content', 'Actualisation des informations de session...', 'info');
    
    setTimeout(() => {
        const dbStatus = document.getElementById('db-status');
        dbStatus.textContent = 'Connecté ✅';
        dbStatus.style.color = '#28a745';
        
        showDevResult('logs-content', 'Informations actualisées ✅', 'success');
    }, 500);
}

function checkSystemHealth() {
    const dbStatus = document.getElementById('db-status');
    dbStatus.textContent = 'Vérification...';
    dbStatus.style.color = '#ffc107';
    
    setTimeout(() => {
        dbStatus.textContent = 'Connecté ✅';
        dbStatus.style.color = '#28a745';
    }, 1000);
}

function loadRecentLogs() {
    const level = document.getElementById('log-level-filter').value;
    showDevResult('logs-content', 'Chargement des logs...', 'info');
    
    setTimeout(() => {
        const mockLogs = [
            '[2025-01-15 14:30:21] INFO - ADR_SEARCH: Recherche produit "GULTRAT" par demo.user',
            '[2025-01-15 14:29:45] INFO - ADR_CREATE: Expédition ADR-20250115-001 créée',
            '[2025-01-15 14:28:12] INFO - ADR_LOGIN: Utilisateur demo.user connecté',
            '[2025-01-15 14:27:33] WARNING - ADR_QUOTA: Quota Heppner à 85% (850/1000)',
            '[2025-01-15 14:26:15] ERROR - ADR_AUTH: Tentative connexion échouée: user_inexistant'
        ];
        
        const filteredLogs = level ? 
            mockLogs.filter(log => log.includes(level)) : 
            mockLogs;
        
        showDevResult('logs-content', filteredLogs.join('\n'), 'success');
    }, 800);
}

function clearDebugLogs() {
    if (confirm('Effacer tous les logs de debug ?')) {
        showDevResult('logs-content', 'Logs effacés ✅', 'success');
    }
}

// ========== FONCTIONS GÉNÉRATEURS ==========

function generateSQL() {
    const type = document.getElementById('sql-type').value;
    const tableName = document.getElementById('table-name').value || 'exemple_table';
    
    let sql = '';
    
    switch (type) {
        case 'create-table':
            sql = `CREATE TABLE ${tableName} (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_actif (actif)
);`;
            break;
            
        case 'insert-data':
            sql = `INSERT INTO ${tableName} (nom, code, actif) VALUES
    ('Exemple 1', 'EX001', TRUE),
    ('Exemple 2', 'EX002', TRUE),
    ('Exemple 3', 'EX003', FALSE);`;
            break;
            
        case 'select-query':
            sql = `SELECT 
    t.id,
    t.nom,
    t.code,
    t.actif,
    t.created_at
FROM ${tableName} t
WHERE t.actif = TRUE
ORDER BY t.created_at DESC
LIMIT 10;`;
            break;
            
        case 'update-query':
            sql = `UPDATE ${tableName}
SET 
    nom = :nom,
    updated_at = NOW()
WHERE code = :code
  AND actif = TRUE;`;
            break;
    }
    
    document.getElementById('sql-output').value = sql;
}

function generateForm() {
    const name = document.getElementById('form-name').value || 'MonFormulaire';
    const type = document.getElementById('form-type').value;
    
    let formHtml = '';
    
    switch (type) {
        case 'expedition':
            formHtml = generateExpeditionForm(name);
            break;
        case 'client':
            formHtml = generateClientForm(name);
            break;
        case 'produit':
            formHtml = generateProductForm(name);
            break;
        default:
            formHtml = generateCustomForm(name);
    }
    
    document.getElementById('form-output').value = formHtml;
}

function generateExpeditionForm(name) {
    return `<form id="${name.toLowerCase()}" class="adr-form">
    <div class="form-row">
        <div class="form-group">
            <label for="${name}_destinataire">Destinataire *</label>
            <textarea class="form-control" id="${name}_destinataire" name="destinataire" required></textarea>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="${name}_transporteur">Transporteur *</label>
            <select class="form-control" id="${name}_transporteur" name="transporteur" required>
                <option value="">Sélectionner...</option>
                <option value="heppner">Heppner</option>
                <option value="xpo">XPO Logistics</option>
                <option value="kn">Kuehne + Nagel</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="${name}_date">Date *</label>
            <input type="date" class="form-control" id="${name}_date" name="date" required>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Créer expédition</button>
        <button type="reset" class="btn btn-secondary">Réinitialiser</button>
    </div>
</form>`;
}

function generateClientForm(name) {
    return `<form id="${name.toLowerCase()}" class="adr-form">
    <div class="form-row">
        <div class="form-group">
            <label for="${name}_nom">Nom *</label>
            <input type="text" class="form-control" id="${name}_nom" name="nom" required>
        </div>
        
        <div class="form-group">
            <label for="${name}_email">Email</label>
            <input type="email" class="form-control" id="${name}_email" name="email">
        </div>
    </div>
    
    <div class="form-group">
        <label for="${name}_adresse">Adresse complète *</label>
        <textarea class="form-control" id="${name}_adresse" name="adresse" rows="3" required></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="${name}_cp">Code postal *</label>
            <input type="text" class="form-control" id="${name}_cp" name="code_postal" pattern="[0-9]{5}" required>
        </div>
        
        <div class="form-group">
            <label for="${name}_ville">Ville *</label>
            <input type="text" class="form-control" id="${name}_ville" name="ville" required>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer client</button>
        <button type="reset" class="btn btn-secondary">Réinitialiser</button>
    </div>
</form>`;
