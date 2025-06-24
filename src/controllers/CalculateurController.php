<?php
/**
 * Titre: Controller MVC Calculateur
 * Chemin: /src/controllers/CalculateurController.php
 * Version: 0.5.0 - Build 20250624-001
 */

class CalculateurController {
    private $db;
    private $transportService;
    
    public function __construct($database) {
        $this->db = $database;
        $this->transportService = new TransportCalculateur($database);
    }

    /**
     * Page principale - GET
     */
    public function index($params = []) {
        try {
            // Récupération des données nécessaires
            $viewData = [
                'preset_data' => $this->getPresetData($params),
                'options_service' => $this->getOptionsService(),
                'dept_restrictions' => $this->getDepartmentRestrictions(),
                'error' => false
            ];

            return $viewData;

        } catch (Exception $e) {
            error_log("Erreur controller index: " . $e->getMessage());
            
            return [
                'error' => true,
                'message' => 'Erreur lors du chargement des données',
                'preset_data' => [],
                'options_service' => [],
                'dept_restrictions' => []
            ];
        }
    }

    /**
     * Calcul des tarifs - POST AJAX
     */
    public function calculate($postData) {
        try {
            // Validation des données
            $validationResult = $this->validateCalculationData($postData);
            if (!$validationResult['valid']) {
                return [
                    'error' => true,
                    'message' => 'Données invalides',
                    'details' => $validationResult['errors']
                ];
            }

            // Nettoyage et préparation des données
            $calculationData = $this->prepareCalculationData($postData);

            // Sauvegarde en session
            $this->saveToSession($calculationData);

            // Calcul via le service transport
            $results = $this->transportService->calculateAll($calculationData);

            // Formatage de la réponse
            return $this->formatCalculationResponse($results, $calculationData);

        } catch (Exception $e) {
            error_log("Erreur calcul: " . $e->getMessage());
            
            return [
                'error' => true,
                'message' => 'Erreur lors du calcul des tarifs',
                'details' => DEBUG ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Récupération des données préset (URL/session)
     */
    private function getPresetData($params) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return [
            'departement' => $params['dept'] ?? $params['departement'] ?? ($_SESSION['calc_dept'] ?? ''),
            'poids' => $params['poids'] ?? ($_SESSION['calc_poids'] ?? ''),
            'type' => $params['type'] ?? ($_SESSION['calc_type'] ?? ''),
            'adr' => $params['adr'] ?? ($_SESSION['calc_adr'] ?? ''),
            'options' => $params['options'] ?? ($_SESSION['calc_options'] ?? []),
            'palettes' => $params['palettes'] ?? ($_SESSION['calc_palettes'] ?? ''),
            'enlevement' => isset($params['enlevement']) || ($_SESSION['calc_enlevement'] ?? false)
        ];
    }

    /**
     * Récupération des options de service depuis BDD
     */
    private function getOptionsService() {
        try {
            $options = [];
            $stmt = $this->db->query("
                SELECT DISTINCT transporteur, code_option, libelle, montant 
                FROM gul_options_supplementaires 
                WHERE actif = 1 
                ORDER BY transporteur, libelle
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $options[] = $row;
            }
            
            return $options;

        } catch (Exception $e) {
            error_log("Erreur options service: " . $e->getMessage());
            
            // Fallback options
            return [
                ['transporteur' => 'Tous', 'code_option' => 'standard', 'libelle' => 'Livraison standard', 'montant' => 0],
                ['transporteur' => 'Tous', 'code_option' => 'rdv', 'libelle' => 'Prise de RDV', 'montant' => 15]
            ];
        }
    }

    /**
     * Récupération des restrictions départementales
     */
    private function getDepartmentRestrictions() {
        try {
            $restrictions = [];
            $stmt = $this->db->query("
                SELECT transporteur, departements_blacklistes 
                FROM gul_taxes_transporteurs 
                WHERE departements_blacklistes IS NOT NULL AND departements_blacklistes != ''
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['departements_blacklistes']) {
                    $restrictions[$row['transporteur']] = explode(',', $row['departements_blacklistes']);
                }
            }
            
            return $restrictions;

        } catch (Exception $e) {
            error_log("Erreur restrictions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validation des données de calcul
     */
    private function validateCalculationData($data) {
        $errors = [];

        // Département obligatoire
        if (empty($data['departement'])) {
            $errors[] = 'Département requis';
        } elseif (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/', $data['departement'])) {
            $errors[] = 'Département invalide';
        }

        // Poids obligatoire
        if (empty($data['poids'])) {
            $errors[] = 'Poids requis';
        } elseif (!is_numeric($data['poids']) || $data['poids'] <= 0) {
            $errors[] = 'Poids doit être supérieur à 0';
        } elseif ($data['poids'] > 32000) {
            $errors[] = 'Poids maximum 32000 kg';
        }

        // Type obligatoire
        if (empty($data['type'])) {
            $errors[] = 'Type d\'envoi requis';
        } elseif (!in_array($data['type'], ['colis', 'palette'])) {
            $errors[] = 'Type d\'envoi invalide';
        }

        // Palettes si type palette
        if ($data['type'] === 'palette') {
            if (empty($data['palettes'])) {
                $errors[] = 'Nombre de palettes requis pour type palette';
            } elseif (!is_numeric($data['palettes']) || $data['palettes'] <= 0) {
                $errors[] = 'Nombre de palettes doit être supérieur à 0';
            } elseif ($data['palettes'] > 20) {
                $errors[] = 'Nombre maximum de palettes: 20';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Préparation et nettoyage des données pour calcul
     */
    private function prepareCalculationData($data) {
        return [
            'departement' => trim($data['departement']),
            'poids' => floatval($data['poids']),
            'type' => trim($data['type']),
            'adr' => isset($data['adr']) && $data['adr'] == '1',
            'palettes' => isset($data['palettes']) ? intval($data['palettes']) : 1,
            'enlevement' => isset($data['enlevement']) && $data['enlevement'] == '1',
            'options' => isset($data['options']) ? (array)$data['options'] : []
        ];
    }

    /**
     * Sauvegarde en session pour persistance
     */
    private function saveToSession($data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['calc_dept'] = $data['departement'];
        $_SESSION['calc_poids'] = $data['poids'];
        $_SESSION['calc_type'] = $data['type'];
        $_SESSION['calc_adr'] = $data['adr'];
        $_SESSION['calc_palettes'] = $data['palettes'];
        $_SESSION['calc_enlevement'] = $data['enlevement'];
        $_SESSION['calc_options'] = $data['options'];
        $_SESSION['calc_last_calculation'] = time();
    }

    /**
     * Formatage de la réponse de calcul
     */
    private function formatCalculationResponse($results, $inputData) {
        $startTime = microtime(true);

        // Noms des transporteurs
        $carrierNames = [
            'xpo' => 'XPO Logistics',
            'heppner' => 'Heppner',
            'kn' => 'Kuehne + Nagel'
        ];

        $response = [
            'success' => true,
            'carriers' => [],
            'best_rate' => null,
            'stats' => [
                'calculation_time' => round((microtime(true) - $startTime) * 1000),
                'carriers_available' => 0,
                'input_data' => $inputData
            ]
        ];

        // Traitement des résultats
        $validResults = [];
        $carrierResults = isset($results['results']) ? $results['results'] : $results;

        foreach ($carrierResults as $carrier => $price) {
            $name = $carrierNames[$carrier] ?? strtoupper($carrier);

            if ($price !== null && $price > 0) {
                $validResults[$carrier] = $price;
                $response['carriers'][$carrier] = [
                    'name' => $name,
                    'price' => $price,
                    'formatted' => number_format($price, 2, ',', ' ') . ' €',
                    'available' => true
                ];
                $response['stats']['carriers_available']++;
            } else {
                $response['carriers'][$carrier] = [
                    'name' => $name,
                    'price' => null,
                    'formatted' => 'Non disponible',
                    'available' => false
                ];
            }
        }

        // Détermination du meilleur tarif
        if (!empty($validResults)) {
            $bestCarrier = array_keys($validResults, min($validResults))[0];
            $bestPrice = $validResults[$bestCarrier];

            $response['best_rate'] = [
                'carrier' => $bestCarrier,
                'carrier_name' => $carrierNames[$bestCarrier] ?? strtoupper($bestCarrier),
                'price' => $bestPrice,
                'formatted' => number_format($bestPrice, 2, ',', ' ') . ' €',
                'savings' => $this->calculateSavings($validResults, $bestPrice)
            ];
        }

        // Debug info si activé
        if (DEBUG && isset($results['debug'])) {
            $response['debug'] = $results['debug'];
        }

        return $response;
    }

    /**
     * Calcul des économies par rapport aux autres transporteurs
     */
    private function calculateSavings($results, $bestPrice) {
        $savings = [];
        
        foreach ($results as $carrier => $price) {
            if ($price > $bestPrice) {
                $savings[$carrier] = [
                    'amount' => $price - $bestPrice,
                    'percentage' => round((($price - $bestPrice) / $price) * 100, 1)
                ];
            }
        }

        return $savings;
    }

    /**
     * Statistiques d'utilisation (pour admin)
     */
    public function getUsageStats() {
        try {
            $stats = [];

            // Calculs aujourd'hui
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM gul_adr_expeditions 
                WHERE DATE(date_creation) = CURDATE()
            ");
            $stats['calculations_today'] = $stmt->fetchColumn() ?: 0;

            // Départements les plus demandés
            $stmt = $this->db->query("
                SELECT departement_destination, COUNT(*) as count
                FROM gul_adr_expeditions 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY departement_destination 
                ORDER BY count DESC 
                LIMIT 10
            ");
            $stats['top_departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Moyennes de poids
            $stmt = $this->db->query("
                SELECT AVG(poids_total) as avg_weight, 
                       MIN(poids_total) as min_weight,
                       MAX(poids_total) as max_weight
                FROM gul_adr_expeditions 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stats['weight_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $stats;

        } catch (Exception $e) {
            error_log("Erreur stats: " . $e->getMessage());
            return [];
        }
    }
}
