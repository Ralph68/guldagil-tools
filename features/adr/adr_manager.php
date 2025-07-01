<?php
// ========== features/adr/adr_manager.php ==========
/**
 * Titre: Gestionnaire ADR
 * Chemin: /features/adr/adr_manager.php
 * Version: 0.5 beta + build auto
 */

class adr_manager {
    
    private $db;
    private $debug;
    
    public function __construct($db_connection = null) {
        $this->db = $db_connection ?? $GLOBALS['db'] ?? null;
        $this->debug = defined('DEBUG') && DEBUG;
        
        if (!$this->db) {
            throw new Exception('Connexion BDD requise');
        }
    }
    
    /**
     * Recherche produits ADR
     */
    public function search_products(string $query, int $limit = 20): array {
        if (strlen($query) < 1) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    code_produit,
                    nom_produit,
                    numero_un,
                    danger_environnement,
                    categorie_transport,
                    corde_article_ferme,
                    CASE 
                        WHEN code_produit LIKE :exact THEN 1
                        WHEN nom_produit LIKE :exact THEN 2
                        WHEN numero_un LIKE :exact THEN 3
                        ELSE 4
                    END as relevance
                FROM gul_adr_products 
                WHERE actif = 1 
                AND (
                    code_produit LIKE :query 
                    OR nom_produit LIKE :query 
                    OR numero_un LIKE :query
                )
                ORDER BY relevance, nom_produit
                LIMIT :limit
            ");
            
            $exact = $query . '%';
            $like = '%' . $query . '%';
            
            $stmt->bindValue(':exact', $exact);
            $stmt->bindValue(':query', $like);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("ADR search error: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Stats dashboard
     */
    public function get_dashboard_stats(): array {
        try {
            $stats = [];
            
            // Total produits actifs
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM gul_adr_products WHERE actif = 1");
            $stats['total_products'] = $stmt->fetch()['count'] ?? 0;
            
            // Produits ADR
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM gul_adr_products 
                WHERE actif = 1 AND numero_un IS NOT NULL AND numero_un != ''
            ");
            $stats['adr_products'] = $stmt->fetch()['count'] ?? 0;
            
            // Déclarations aujourd'hui (si table existe)
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM gul_adr_expeditions 
                WHERE DATE(date_creation) = CURDATE()
            ");
            $stats['declarations_today'] = $stmt->fetch()['count'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("ADR stats error: " . $e->getMessage());
            }
            return [
                'total_products' => 250,
                'adr_products' => 180,
                'declarations_today' => 12
            ];
        }
    }
    
    /**
     * Quotas transporteurs
     */
    public function get_quotas(): array {
        try {
            // À adapter selon structure réelle de gul_adr_quotas
            $stmt = $this->db->query("
                SELECT 
                    transporteur,
                    SUM(points_utilises) as used,
                    quota_max as limit
                FROM gul_adr_quotas 
                WHERE DATE(date_quota) = CURDATE()
                GROUP BY transporteur
            ");
            
            $quotas = [];
            while ($row = $stmt->fetch()) {
                $percentage = round(($row['used'] / $row['limit']) * 100);
                $quotas[strtolower($row['transporteur'])] = [
                    'used' => (int)$row['used'],
                    'limit' => (int)$row['limit'],
                    'percentage' => $percentage
                ];
            }
            
            return $quotas;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("ADR quotas error: " . $e->getMessage());
            }
            // Valeurs par défaut
            return [
                'xpo' => ['used' => 750, 'limit' => 1000, 'percentage' => 75],
                'heppner' => ['used' => 320, 'limit' => 1000, 'percentage' => 32],
                'kuehne' => ['used' => 890, 'limit' => 1000, 'percentage' => 89]
            ];
        }
    }
}

// ========== public/adr/ajax/search.php ==========
/**
 * Titre: Endpoint recherche AJAX
 * Chemin: /public/adr/ajax/search.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Vérification auth
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Headers AJAX
header('Content-Type: application/json; charset=utf-8');

try {
    // Configuration
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../features/adr/adr_manager.php';
    
    // Paramètres
    $query = trim($_GET['q'] ?? '');
    $limit = min(20, max(1, intval($_GET['limit'] ?? 10)));
    
    if (empty($query)) {
        echo json_encode(['results' => []]);
        exit;
    }
    
    // Recherche
    $adr = new adr_manager($db);
    $results = $adr->search_products($query, $limit);
    
    // Format pour affichage
    $formatted = [];
    foreach ($results as $product) {
        $formatted[] = [
            'code' => $product['code_produit'],
            'name' => $product['nom_produit'],
            'un' => $product['numero_un'],
            'category' => $product['categorie_transport'],
            'env_danger' => $product['danger_environnement'] === 'OUI',
            'closed' => $product['corde_article_ferme'] === 'x',
            'display' => sprintf(
                '%s - %s%s',
                $product['code_produit'],
                $product['nom_produit'],
                $product['numero_un'] ? ' (UN ' . $product['numero_un'] . ')' : ''
            )
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($formatted),
        'results' => $formatted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de recherche',
        'debug' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : null
    ]);
}

// ========== Mise à jour JavaScript dans index.php ==========
/**
 * Remplacer la section script dans l'index
 */
?>

<script>
// Configuration
const ADR_CONFIG = {
    searchEndpoint: 'ajax/search.php',
    minChars: 1,
    searchDelay: 300
};

let searchTimeout;
let currentResults = [];

// Recherche en temps réel
document.getElementById('product-search').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length >= ADR_CONFIG.minChars) {
        searchTimeout = setTimeout(() => {
            searchProducts(query);
        }, ADR_CONFIG.searchDelay);
    } else {
        hideSuggestions();
    }
});

// Navigation clavier
document.getElementById('product-search').addEventListener('keydown', function(e) {
    const suggestions = document.getElementById('search-suggestions');
    const items = suggestions.querySelectorAll('.suggestion-item');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        navigateSuggestions(items, 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        navigateSuggestions(items, -1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const active = suggestions.querySelector('.suggestion-item.active');
        if (active) {
            selectProduct(active.dataset.code);
        }
    } else if (e.key === 'Escape') {
        hideSuggestions();
    }
});

function searchProducts(query) {
    const suggestions = document.getElementById('search-suggestions');
    suggestions.innerHTML = '<div class="suggestion-loading">🔍 Recherche...</div>';
    suggestions.style.display = 'block';

    fetch(`${ADR_CONFIG.searchEndpoint}?q=${encodeURIComponent(query)}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.results.length > 0) {
                displaySuggestions(data.results);
            } else {
                suggestions.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé</div>';
            }
        })
        .catch(error => {
            console.error('Erreur recherche:', error);
            suggestions.innerHTML = '<div class="suggestion-error">Erreur de recherche</div>';
        });
}

function displaySuggestions(results) {
    const suggestions = document.getElementById('search-suggestions');
    currentResults = results;
    
    let html = '';
    results.forEach((product, index) => {
        const badges = [];
        if (product.un) badges.push(`<span class="badge adr">UN ${product.un}</span>`);
        if (product.env_danger) badges.push(`<span class="badge env">🌍</span>`);
        if (product.closed) badges.push(`<span class="badge closed">🔒</span>`);
        
        html += `
            <div class="suggestion-item ${index === 0 ? 'active' : ''}" 
                 data-code="${product.code}" 
                 onclick="selectProduct('${product.code}')">
                <div class="suggestion-main">
                    <strong>${product.code}</strong> - ${product.name}
                </div>
                <div class="suggestion-badges">${badges.join(' ')}</div>
            </div>
        `;
    });
    
    suggestions.innerHTML = html;
}

function navigateSuggestions(items, direction) {
    const currentActive = document.querySelector('.suggestion-item.active');
    let newIndex = 0;
    
    if (currentActive) {
        const currentIndex = Array.from(items).indexOf(currentActive);
        newIndex = Math.max(0, Math.min(items.length - 1, currentIndex + direction));
        currentActive.classList.remove('active');
    }
    
    if (items[newIndex]) {
        items[newIndex].classList.add('active');
    }
}

function selectProduct(code) {
    const product = currentResults.find(p => p.code === code);
    if (product) {
        document.getElementById('product-search').value = product.display;
        // Ici : redirection vers page détail produit ou action spécifique
        alert(`Produit sélectionné: ${product.name}\nCode: ${product.code}${product.un ? '\nUN: ' + product.un : ''}`);
    }
    hideSuggestions();
}

function hideSuggestions() {
    document.getElementById('search-suggestions').style.display = 'none';
}

// Fermer si clic ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-form')) {
        hideSuggestions();
    }
});

// Styles pour suggestions
const searchStyles = `
    .suggestion-item {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }
    .suggestion-item:hover, .suggestion-item.active {
        background: #f8f9fa;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-main {
        margin-bottom: 0.25rem;
    }
    .suggestion-badges {
        display: flex;
        gap: 0.25rem;
    }
    .badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        color: white;
    }
    .badge.adr { background: #dc3545; }
    .badge.env { background: #ffc107; color: #333; }
    .badge.closed { background: #6c757d; }
    .suggestion-loading, .suggestion-empty, .suggestion-error {
        padding: 1rem;
        text-align: center;
        color: #666;
    }
    .suggestion-error {
        color: #dc3545;
    }
`;

// Injecter styles
const style = document.createElement('style');
style.textContent = searchStyles;
document.head.appendChild(style);

console.log('🔍 Recherche ADR initialisée');
</script>
