<?php
/**
 * Enhanced Security Tools - Intégration GitHub + Existant Guldagil
 * Chemin: /core/security/enhanced_security.php
 * Version: 1.0 - Fusion outils GitHub + système existant
 */

require_once __DIR__ . '/ip_geolocation.php';
require_once __DIR__ . '/stealth_methods.php';

class EnhancedSecurityManager {
    
    private $config;
    private $rateLimiter;
    private $ipGeolocator;
    private $cache;
    
    public function __construct() {
        $this->config = [
            // Rate limiting amélioré (inspiré GitHub tools)
            'rate_limits' => [
                'global' => ['requests' => 60, 'window' => 3600], // 1h
                'login' => ['requests' => 5, 'window' => 900],    // 15min
                'api' => ['requests' => 100, 'window' => 3600],   // 1h
                'admin' => ['requests' => 30, 'window' => 3600]   // 1h
            ],
            
            // IP Geolocation renforcée
            'geolocation' => [
                'enabled' => true,
                'allowed_countries' => ['FR', 'BE', 'CH'], // + voisins
                'api_failover' => [
                    'primary' => 'ip-api.com',
                    'secondary' => 'ipinfo.io',
                    'cache_duration' => 86400 // 24h
                ]
            ],
            
            // Détection avancée menaces
            'threat_detection' => [
                'enabled' => true,
                'vpn_detection' => true,
                'proxy_detection' => true,
                'tor_detection' => true,
                'hosting_detection' => true
            ]
        ];
        
        $this->initializeComponents();
    }
    
    private function initializeComponents() {
        // Rate Limiter moderne (Token Bucket Algorithm)
        $this->rateLimiter = new ModernRateLimiter();
        
        // IP Geolocation améliorée
        $this->ipGeolocator = new IpGeolocationSecurity();
        
        // Cache système
        $this->cache = new SecurityCache();
    }
    
    /**
     * Vérification sécurité complète
     */
    public function checkSecurity($endpoint = 'global') {
        $client_ip = $this->getClientIP();
        
        // 1. Rate Limiting intelligent
        if (!$this->checkRateLimit($client_ip, $endpoint)) {
            $this->logThreat('rate_limit_exceeded', $client_ip);
            return $this->handleRateLimitViolation($endpoint);
        }
        
        // 2. Géolocalisation (système existant amélioré)
        if (!$this->checkGeolocation($client_ip)) {
            $this->logThreat('geo_blocked', $client_ip);
            return $this->handleGeoBlock($client_ip);
        }
        
        // 3. Détection menaces avancée
        $threat_level = $this->assessThreatLevel($client_ip);
        if ($threat_level > 0.7) {
            $this->logThreat('high_threat_detected', $client_ip, $threat_level);
            return $this->handleThreat($threat_level);
        }
        
        return true;
    }
    
    /**
     * Rate Limiting moderne avec Token Bucket
     */
    private function checkRateLimit($ip, $endpoint) {
        $limit_config = $this->config['rate_limits'][$endpoint] 
                       ?? $this->config['rate_limits']['global'];
        
        $key = "rate_limit:{$endpoint}:{$ip}";
        $cached = $this->cache->get($key);
        
        $now = time();
        
        if (!$cached) {
            // Premier accès - créer le bucket
            $bucket = [
                'tokens' => $limit_config['requests'] - 1,
                'last_refill' => $now,
                'capacity' => $limit_config['requests']
            ];
            $this->cache->set($key, $bucket, $limit_config['window']);
            return true;
        }
        
        // Refill tokens basé sur le temps écoulé
        $elapsed = $now - $cached['last_refill'];
        $refill_rate = $cached['capacity'] / $limit_config['window'];
        $tokens_to_add = floor($elapsed * $refill_rate);
        
        $cached['tokens'] = min(
            $cached['capacity'], 
            $cached['tokens'] + $tokens_to_add
        );
        $cached['last_refill'] = $now;
        
        if ($cached['tokens'] > 0) {
            $cached['tokens']--;
            $this->cache->set($key, $cached, $limit_config['window']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Géolocalisation avec APIs multiples et cache
     */
    private function checkGeolocation($ip) {
        if (!$this->config['geolocation']['enabled']) {
            return true;
        }
        
        // Cache check
        $cache_key = "geo:{$ip}";
        $cached_result = $this->cache->get($cache_key);
        
        if ($cached_result !== null) {
            return $cached_result;
        }
        
        // Utiliser le système existant d'abord
        $result = $this->ipGeolocator->isIpAllowed($ip);
        
        if (!$result) {
            // Double vérification avec API alternative
            $country = $this->getCountryFromAlternativeAPI($ip);
            $result = in_array($country, $this->config['geolocation']['allowed_countries']);
        }
        
        // Cache le résultat
        $this->cache->set(
            $cache_key, 
            $result, 
            $this->config['geolocation']['api_failover']['cache_duration']
        );
        
        return $result;
    }
    
    /**
     * Évaluation niveau de menace (inspiré GitHub Security)
     */
    private function assessThreatLevel($ip) {
        $threat_score = 0.0;
        $factors = [];
        
        // Facteur 1: User-Agent suspect
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($this->isSuspiciousUserAgent($user_agent)) {
            $threat_score += 0.3;
            $factors[] = 'suspicious_user_agent';
        }
        
        // Facteur 2: Patterns de requête
        $request_pattern = $this->analyzeRequestPattern($ip);
        if ($request_pattern > 0.5) {
            $threat_score += $request_pattern * 0.4;
            $factors[] = 'suspicious_pattern';
        }
        
        // Facteur 3: Détection VPN/Proxy (si APIs disponibles)
        if ($this->config['threat_detection']['vpn_detection']) {
            $vpn_score = $this->checkVPNProxy($ip);
            $threat_score += $vpn_score * 0.2;
            if ($vpn_score > 0) {
                $factors[] = 'vpn_proxy_detected';
            }
        }
        
        // Facteur 4: Réputation IP
        $reputation = $this->checkIPReputation($ip);
        $threat_score += $reputation * 0.1;
        if ($reputation > 0) {
            $factors[] = 'bad_reputation';
        }
        
        $this->logThreatAssessment($ip, $threat_score, $factors);
        
        return min($threat_score, 1.0);
    }
    
    /**
     * API alternative pour géolocalisation
     */
    private function getCountryFromAlternativeAPI($ip) {
        // Utiliser ipinfo.io comme fallback
        $url = "https://ipinfo.io/{$ip}/json";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Guldagil-Security/1.0'
            ]
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                return $data['country'] ?? 'UNKNOWN';
            }
        } catch (Exception $e) {
            error_log("Alternative geolocation API failed: " . $e->getMessage());
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Détection User-Agent suspect
     */
    private function isSuspiciousUserAgent($user_agent) {
        $suspicious_patterns = [
            '/bot|crawler|spider|scraper/i',
            '/wget|curl|python/i',
            '/nikto|sqlmap|nmap/i',
            '/scanner|exploit/i',
            '/^Mozilla\/4\.0$/i', // Trop basique
            '/^$/i' // Vide
        ];
        
        if (empty($user_agent) || strlen($user_agent) < 10) {
            return true;
        }
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyse pattern de requêtes
     */
    private function analyzeRequestPattern($ip) {
        $key = "pattern:{$ip}";
        $pattern = $this->cache->get($key) ?? [
            'requests' => [],
            'endpoints' => [],
            'methods' => []
        ];
        
        $now = time();
        $current_request = [
            'timestamp' => $now,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];
        
        // Ajouter la requête actuelle
        $pattern['requests'][] = $current_request;
        $pattern['endpoints'][] = $current_request['endpoint'];
        $pattern['methods'][] = $current_request['method'];
        
        // Garder seulement les 50 dernières requêtes
        $pattern['requests'] = array_slice($pattern['requests'], -50);
        $pattern['endpoints'] = array_slice($pattern['endpoints'], -50);
        $pattern['methods'] = array_slice($pattern['methods'], -50);
        
        $this->cache->set($key, $pattern, 3600);
        
        // Calculer le score de suspicion
        $suspicion_score = 0;
        
        // Trop de requêtes rapides
        $recent_requests = array_filter($pattern['requests'], function($req) use ($now) {
            return $now - $req['timestamp'] < 60; // Dernière minute
        });
        
        if (count($recent_requests) > 30) {
            $suspicion_score += 0.4;
        }
        
        // Endpoints suspects
        $suspicious_endpoints = ['/admin', '/wp-admin', '/.env', '/config.php'];
        foreach ($pattern['endpoints'] as $endpoint) {
            foreach ($suspicious_endpoints as $suspicious) {
                if (strpos($endpoint, $suspicious) !== false) {
                    $suspicion_score += 0.2;
                    break;
                }
            }
        }
        
        // Méthodes suspectes
        $suspicious_methods = ['OPTIONS', 'TRACE', 'CONNECT'];
        if (array_intersect($pattern['methods'], $suspicious_methods)) {
            $suspicion_score += 0.1;
        }
        
        return min($suspicion_score, 1.0);
    }
    
    /**
     * Détection VPN/Proxy simple
     */
    private function checkVPNProxy($ip) {
        // Headers typiques des proxies
        $proxy_headers = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_COMING_FROM',
            'HTTP_COMING_FROM'
        ];
        
        $proxy_score = 0;
        foreach ($proxy_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $proxy_score += 0.2;
            }
        }
        
        // Plages IP connues de VPN/Hosting (basique)
        $vpn_ranges = [
            '192.99.', '167.114.', '51.222.', // OVH
            '138.197.', '142.93.', '159.89.', // DigitalOcean
            '13.', '18.', '52.', // AWS partiel
        ];
        
        foreach ($vpn_ranges as $range) {
            if (strpos($ip, $range) === 0) {
                $proxy_score += 0.3;
                break;
            }
        }
        
        return min($proxy_score, 1.0);
    }
    
    /**
     * Vérification réputation IP basique
     */
    private function checkIPReputation($ip) {
        // Liste noire basique locale
        $blacklisted_ranges = [
            '127.0.0.1', // Tests
        ];
        
        foreach ($blacklisted_ranges as $blocked_ip) {
            if ($ip === $blocked_ip) {
                return 1.0; // Réputation maximalement mauvaise
            }
        }
        
        return 0.0;
    }
    
    /**
     * Gestion violations rate limiting
     */
    private function handleRateLimitViolation($endpoint) {
        $client_ip = $this->getClientIP();
        
        // Headers standards
        http_response_code(429);
        header('Retry-After: 60');
        header('X-RateLimit-Limit: ' . $this->config['rate_limits'][$endpoint]['requests']);
        header('X-RateLimit-Remaining: 0');
        
        // Méthode de blocage selon endpoint
        if ($endpoint === 'admin') {
            // Admin = blocage dur
            StealthBlockMethods::executeBlock('server_error', $client_ip);
        } else {
            // Autres = méthode adaptative
            $method = StealthBlockMethods::getOptimalBlockMethod(
                $client_ip, 
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            StealthBlockMethods::executeBlock($method, $client_ip);
        }
        
        return false;
    }
    
    /**
     * Gestion blocage géographique
     */
    private function handleGeoBlock($ip) {
        // Utiliser les méthodes stealth existantes
        $method = StealthBlockMethods::getOptimalBlockMethod(
            $ip, 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        StealthBlockMethods::executeBlock($method, $ip);
        return false;
    }
    
    /**
     * Gestion menaces détectées
     */
    private function handleThreat($threat_level) {
        $client_ip = $this->getClientIP();
        
        if ($threat_level > 0.9) {
            // Menace critique = bannissement temporaire
            $this->banIP($client_ip, 3600); // 1h
            StealthBlockMethods::executeBlock('server_error', $client_ip);
        } else {
            // Menace modérée = ralentissement
            sleep(2 + ($threat_level * 3)); // 2-5 secondes
            StealthBlockMethods::executeBlock('infinite_loading', $client_ip);
        }
        
        return false;
    }
    
    /**
     * Bannissement temporaire IP
     */
    private function banIP($ip, $duration) {
        $this->cache->set("banned:{$ip}", true, $duration);
        $this->logThreat('ip_banned', $ip, $duration);
    }
    
    /**
     * Vérification bannissement
     */
    public function isBanned($ip) {
        return $this->cache->get("banned:{$ip}") === true;
    }
    
    /**
     * Obtention IP client (améliorée)
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Logging sécurité centralisé
     */
    private function logThreat($type, $ip, $details = null) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'details' => $details
        ];
        
        $log_file = ROOT_PATH . '/storage/logs/security_enhanced.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents(
            $log_file, 
            json_encode($log_entry) . "\n", 
            FILE_APPEND | LOCK_EX
        );
    }
    
    private function logThreatAssessment($ip, $score, $factors) {
        $this->logThreat('threat_assessment', $ip, [
            'score' => $score,
            'factors' => $factors
        ]);
    }
    
    /**
     * Dashboard stats pour admin
     */
    public function getSecurityStats($days = 7) {
        $stats = [
            'total_threats' => 0,
            'rate_limits' => 0,
            'geo_blocks' => 0,
            'high_threats' => 0,
            'banned_ips' => 0,
            'top_threats' => []
        ];
        
        // Lire logs et calculer statistiques
        $log_file = ROOT_PATH . '/storage/logs/security_enhanced.log';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES);
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if ($entry && $entry['timestamp'] >= $cutoff) {
                    $stats['total_threats']++;
                    
                    switch ($entry['type']) {
                        case 'rate_limit_exceeded':
                            $stats['rate_limits']++;
                            break;
                        case 'geo_blocked':
                            $stats['geo_blocks']++;
                            break;
                        case 'high_threat_detected':
                            $stats['high_threats']++;
                            break;
                        case 'ip_banned':
                            $stats['banned_ips']++;
                            break;
                    }
                    
                    // Top menaces
                    if (!isset($stats['top_threats'][$entry['type']])) {
                        $stats['top_threats'][$entry['type']] = 0;
                    }
                    $stats['top_threats'][$entry['type']]++;
                }
            }
        }
        
        arsort($stats['top_threats']);
        
        return $stats;
    }
}

/**
 * Cache système simple pour sécurité
 */
class SecurityCache {
    private $cache_dir;
    
    public function __construct() {
        $this->cache_dir = ROOT_PATH . '/storage/cache/security';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cache_dir . '/' . md5($key);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['expires'] > time()) {
                return $data['value'];
            } else {
                unlink($file);
            }
        }
        return null;
    }
    
    public function set($key, $value, $ttl = 3600) {
        $file = $this->cache_dir . '/' . md5($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, json_encode($data));
    }
}

/**
 * Rate Limiter moderne
 */
class ModernRateLimiter {
    // Implémentation Token Bucket simplifiée
    // (La logique est dans EnhancedSecurityManager::checkRateLimit)
}

/**
 * Fonction d'initialisation globale
 */
function initEnhancedSecurity() {
    static $security = null;
    
    if ($security === null) {
        $security = new EnhancedSecurityManager();
    }
    
    return $security;
}

/**
 * Middleware de sécurité global
 */
function enhancedSecurityCheck($endpoint = 'global') {
    $security = initEnhancedSecurity();
    
    // Vérifier bannissement d'abord
    $client_ip = $security->getClientIP();
    if ($security->isBanned($client_ip)) {
        StealthBlockMethods::executeBlock('server_error', $client_ip);
        return false;
    }
    
    return $security->checkSecurity($endpoint);
}

/**
 * Widget admin pour tableau de bord
 */
function getSecurityWidget($days = 7) {
    $security = initEnhancedSecurity();
    $stats = $security->getSecurityStats($days);
    
    $html = '<div class="security-widget">';
    $html .= '<h3>🛡️ Sécurité Renforcée</h3>';
    $html .= '<div class="security-stats">';
    $html .= '<div class="stat-item"><span class="count">' . $stats['total_threats'] . '</span><span class="label">Menaces</span></div>';
    $html .= '<div class="stat-item"><span class="count">' . $stats['rate_limits'] . '</span><span class="label">Rate Limits</span></div>';
    $html .= '<div class="stat-item"><span class="count">' . $stats['geo_blocks'] . '</span><span class="label">Géo-blocs</span></div>';
    $html .= '<div class="stat-item"><span class="count">' . $stats['banned_ips'] . '</span><span class="label">IPs bannies</span></div>';
    $html .= '</div></div>';
    
    return $html;
}

// Auto-initialisation si pas en mode CLI
if (php_sapi_name() !== 'cli') {
    // Vérification automatique pour toutes les pages
    if (!defined('DISABLE_ENHANCED_SECURITY')) {
        enhancedSecurityCheck();
    }
}
?>