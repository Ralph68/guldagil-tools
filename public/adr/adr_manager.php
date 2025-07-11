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

