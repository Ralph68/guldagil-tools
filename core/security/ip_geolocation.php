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
     * Bloque l'accès avec message d'erreur
     */
    public function blockAccess($ip = null, $reason = 'Accès non autorisé') {
        if (!$ip) {
            $ip = $this->getUserIp();
        }
        
        $this->logSecurityEvent('access_blocked', $ip, $reason);
        
        // Headers de sécurité
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        
        // Page de blocage
        echo $this->getBlockedPage($ip, $reason);
        exit;
    }
    
    /**
     * Génère la page de blocage
     */
    private function getBlockedPage($ip, $reason) {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès non autorisé</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 10% auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 4rem; color: #e74c3c; margin-bottom: 1rem; }
        h1 { color: #2c3e50; margin-bottom: 1rem; }
        p { color: #7f8c8d; line-height: 1.6; margin-bottom: 1rem; }
        .ip { background: #ecf0f1; padding: 0.5rem; border-radius: 4px; font-family: monospace; margin: 1rem 0; }
        .contact { background: #3498db; color: white; padding: 1rem; border-radius: 4px; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h1>Accès non autorisé</h1>
        <p>Désolé, l\'accès au portail Guldagil est restreint aux connexions depuis la France uniquement.</p>
        
        <div class="ip">
            <strong>Votre IP :</strong> ' . htmlspecialchars($ip) . '<br>
            <strong>Raison :</strong> ' . htmlspecialchars($reason) . '
        </div>
        
        <p>Si vous êtes un utilisateur autorisé et que vous rencontrez cette erreur :</p>
        <ul style="text-align: left; display: inline-block;">
            <li>Vérifiez que vous n\'utilisez pas de VPN ou proxy</li>
            <li>Connectez-vous depuis la France</li>
            <li>Contactez l\'administrateur système</li>
        </ul>
        
        <div class="contact">
            <strong>Support technique</strong><br>
            En cas de problème persistant, contactez votre administrateur en précisant votre adresse IP.
        </div>
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
