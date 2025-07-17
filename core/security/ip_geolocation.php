<?php
/**
 * Titre: Système de sécurité par géolocalisation IP française
 * Chemin: /core/security/ip_geolocation.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    exit('Accès direct interdit');
}

class IpGeolocationSecurity {
    
    private $allowed_countries = ['FR']; // France uniquement
    private $whitelisted_ips = [
        '127.0.0.1',    // Localhost
        '::1',          // Localhost IPv6
        // Ajoutez ici des IPs spécifiques si nécessaire
    ];
    
    private $geolocation_apis = [
        // API gratuite avec limite quotidienne
        'ipapi' => 'http://ip-api.com/json/{ip}?fields=country,countryCode,status,message',
        // API de secours
        'ipinfo' => 'https://ipinfo.io/{ip}/json',
        // API locale si disponible
        'maxmind' => null // À implémenter si base locale disponible
    ];
    
    /**
     * Vérifie si l'IP est autorisée (France uniquement)
     */
    public function isIpAllowed($ip = null) {
        // Récupération de l'IP si non fournie
        if (!$ip) {
            $ip = $this->getUserIp();
        }
        
        // Nettoyage et validation IP
        $ip = $this->cleanIp($ip);
        if (!$this->isValidIp($ip)) {
            $this->logSecurityEvent('invalid_ip', $ip, 'IP invalide détectée');
            return false;
        }
        
        // IPs en whitelist (localhost, développement)
        if (in_array($ip, $this->whitelisted_ips)) {
            $this->logSecurityEvent('whitelist_access', $ip, 'Accès via IP whitelistée');
            return true;
        }
        
        // IPs privées (développement local)
        if ($this->isPrivateIp($ip)) {
            $this->logSecurityEvent('private_access', $ip, 'Accès via IP privée');
            return true;
        }
        
        // Vérification géolocalisation
        $country_code = $this->getCountryCode($ip);
        
        if (in_array($country_code, $this->allowed_countries)) {
            $this->logSecurityEvent('geo_allowed', $ip, 'Accès autorisé depuis ' . $country_code);
            return true;
        } else {
            $this->logSecurityEvent('geo_blocked', $ip, 'Accès bloqué depuis ' . ($country_code ?: 'UNKNOWN'));
            return false;
        }
    }
    
    /**
     * Récupère l'IP réelle de l'utilisateur
     */
    private function getUserIp() {
        // Priorité aux en-têtes de proxy/CDN
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy standard
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_X_FORWARDED',          // Alternative
            'HTTP_FORWARDED_FOR',        // Alternative
            'HTTP_FORWARDED',            // Alternative
            'REMOTE_ADDR'                // IP directe
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Gestion des IPs multiples (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1'; // Fallback
    }
    
    /**
     * Nettoie et valide une IP
     */
    private function cleanIp($ip) {
        $ip = trim($ip);
        
        // Suppression des ports
        if (strpos($ip, ':') !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = explode(':', $ip)[0];
        }
        
        return $ip;
    }
    
    /**
     * Valide si l'IP est correcte
     */
    private function isValidIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            || $this->isPrivateIp($ip);
    }
    
    /**
     * Vérifie si l'IP est privée/locale
     */
    private function isPrivateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
    
    /**
     * Récupère le code pays via géolocalisation
     */
    private function getCountryCode($ip) {
        // Cache pour éviter les appels multiples
        static $cache = [];
        
        if (isset($cache[$ip])) {
            return $cache[$ip];
        }
        
        $country_code = null;
        
        // Tentative API ip-api.com (gratuite)
        try {
            $url = str_replace('{ip}', $ip, $this->geolocation_apis['ipapi']);
            $response = $this->makeApiRequest($url);
            
            if ($response && isset($response['status']) && $response['status'] === 'success') {
                $country_code = $response['countryCode'] ?? null;
            }
        } catch (Exception $e) {
            $this->logSecurityEvent('api_error', $ip, 'Erreur API ip-api: ' . $e->getMessage());
        }
        
        // Fallback: API ipinfo.io
        if (!$country_code) {
            try {
                $url = str_replace('{ip}', $ip, $this->geolocation_apis['ipinfo']);
                $response = $this->makeApiRequest($url);
                
                if ($response && isset($response['country'])) {
                    $country_code = $response['country'];
                }
            } catch (Exception $e) {
                $this->logSecurityEvent('api_error', $ip, 'Erreur API ipinfo: ' . $e->getMessage());
            }
        }
        
        // Cache du résultat
        $cache[$ip] = $country_code;
        
        return $country_code;
    }
    
    /**
     * Fait un appel API avec timeout
     */
    private function makeApiRequest($url, $timeout = 5) {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'method' => 'GET',
                'header' => [
                    'User-Agent: Guldagil-Portal/0.5',
                    'Accept: application/json'
                ]
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Échec de la requête API');
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Réponse JSON invalide');
        }
        
        return $data;
    }
    
    /**
     * Bloque l'accès de manière discrète
     */
    public function blockAccess($ip = null, $method = 'maintenance') {
        if (!$ip) {
            $ip = $this->getUserIp();
        }
        
        $this->logSecurityEvent('access_blocked', $ip, 'Blocage discret: ' . $method);
        
        switch ($method) {
            case 'blank':
                $this->showBlankPage();
                break;
            case 'timeout':
                $this->simulateTimeout();
                break;
            case 'maintenance':
            default:
                $this->showMaintenancePage();
                break;
        }
        
        exit;
    }
    
    /**
     * Page blanche (méthode discrète)
     */
    private function showBlankPage() {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title></title></head><body></body></html>';
    }
    
    /**
     * Simulation timeout (très discret)
     */
    private function simulateTimeout() {
        // Délai aléatoire pour simuler lenteur réseau
        sleep(rand(3, 8));
        
        // Puis timeout HTTP
        http_response_code(408);
        header('Connection: close');
        echo '';
    }
    
    /**
     * Page maintenance générique (recommandé)
     */
    private function showMaintenancePage() {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600'); // 1 heure
        
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance en cours</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: rgba(255,255,255,0.95); padding: 3rem; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; }
        h1 { color: #2c3e50; margin-bottom: 1rem; font-weight: 600; }
        p { color: #7f8c8d; line-height: 1.6; margin-bottom: 1.5rem; }
        .time { background: #3498db; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Maintenance en cours</h1>
        <p>Nous effectuons actuellement une maintenance programmée pour améliorer nos services.</p>
        <p>Le portail sera de nouveau accessible sous peu.</p>
        <div class="time">⏱️ Retour prévu dans quelques heures</div>
    </div>
</body>
</html>';
    }
    
    /**
     * Log des événements de sécurité
     */
    private function logSecurityEvent($type, $ip, $message) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'ip' => $ip,
            'message' => $message,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        $log_file = ROOT_PATH . '/storage/logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_line = json_encode($log_data) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Configuration avancée
     */
    public function setAllowedCountries($countries) {
        $this->allowed_countries = $countries;
    }
    
    public function addWhitelistIp($ip) {
        if (!in_array($ip, $this->whitelisted_ips)) {
            $this->whitelisted_ips[] = $ip;
        }
    }
    
    /**
     * Analyse des logs pour reporting
     */
    public function getSecurityStats($days = 7) {
        $log_file = ROOT_PATH . '/storage/logs/security.log';
        
        if (!file_exists($log_file)) {
            return ['total' => 0, 'blocked' => 0, 'allowed' => 0];
        }
        
        $stats = ['total' => 0, 'blocked' => 0, 'allowed' => 0, 'countries' => []];
        $cutoff_date = date('Y-m-d H:i:s', time() - ($days * 24 * 3600));
        
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            
            if ($data && $data['timestamp'] >= $cutoff_date) {
                $stats['total']++;
                
                if (strpos($data['type'], 'blocked') !== false) {
                    $stats['blocked']++;
                } else if (strpos($data['type'], 'allowed') !== false) {
                    $stats['allowed']++;
                }
            }
        }
        
        return $stats;
    }
}

// Fonction d'initialisation globale
function initIpGeolocationSecurity() {
    static $security = null;
    
    if ($security === null) {
        $security = new IpGeolocationSecurity();
    }
    
    return $security;
}

// Fonction de vérification rapide
function checkIpGeolocation($block_on_fail = true) {
    $security = initIpGeolocationSecurity();
    
    if (!$security->isIpAllowed()) {
        if ($block_on_fail) {
            $security->blockAccess();
        }
        return false;
    }
    
    return true;
}
?>
