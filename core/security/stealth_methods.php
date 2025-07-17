<?php
/**
 * Titre: Méthodes avancées de blocage furtif
 * Chemin: /core/security/stealth_methods.php
 * Version: 0.5 beta + build auto
 */

class StealthBlockMethods {
    
    /**
     * Page d'erreur générique serveur (très discret)
     */
    public static function showServerError() {
        http_response_code(500);
        header('Content-Type: text/html');
        echo '<!DOCTYPE html>
<html>
<head><title>Internal Server Error</title></head>
<body>
<h1>Internal Server Error</h1>
<p>The server encountered an internal error and was unable to complete your request.</p>
</body>
</html>';
    }
    
    /**
     * Page de connexion factice (piège)
     */
    public static function showFakeLogin() {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion - Portail</title>
    <style>
        body { font-family: system-ui; background: #f8fafc; margin: 0; padding: 2rem; }
        .login { max-width: 400px; margin: 5rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #1f2937; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #374151; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 1rem; }
        button { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        .error { color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="login">
        <h1>Connexion</h1>
        <form method="post" action="">
            <div class="form-group">
                <label>Nom d\'utilisateur</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
            <div class="error">Session expirée. Veuillez vous reconnecter.</div>
        </form>
    </div>
</body>
</html>';
    }
    
    /**
     * Redirection vers site officiel/neutre
     */
    public static function redirectToNeutralSite() {
        $neutral_sites = [
            'https://www.service-public.fr/',
            'https://www.gouvernement.fr/',
            'https://www.ecologie.gouv.fr/',
        ];
        
        $redirect_url = $neutral_sites[array_rand($neutral_sites)];
        
        http_response_code(302);
        header('Location: ' . $redirect_url);
        exit;
    }
    
    /**
     * Page "Site non configuré" (très crédible)
     */
    public static function showUnconfiguredSite() {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Site en construction</title>
    <style>
        body { font-family: system-ui; background: #f9fafb; margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { text-align: center; max-width: 500px; padding: 2rem; }
        .icon { font-size: 5rem; margin-bottom: 1rem; opacity: 0.6; }
        h1 { color: #374151; margin-bottom: 1rem; }
        p { color: #6b7280; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚧</div>
        <h1>Site en construction</h1>
        <p>Ce site est actuellement en cours de développement.</p>
        <p>Merci de revenir ultérieurement.</p>
    </div>
</body>
</html>';
    }
    
    /**
     * Erreur DNS simulée (très technique, décourage)
     */
    public static function showDnsError() {
        http_response_code(503);
        header('Content-Type: text/html');
        echo '<!DOCTYPE html>
<html>
<head><title>DNS Resolution Error</title></head>
<body style="font-family:monospace;background:#000;color:#0f0;padding:2rem;">
<pre>
DNS_PROBE_FINISHED_NXDOMAIN

Error resolving hostname: www.guldagil.local
DNS query failed (Code: 3)
Server response time: TIMEOUT

Contact your system administrator.
</pre>
</body>
</html>';
    }
    
    /**
     * Boucle de chargement infinie (frustrant mais discret)
     */
    public static function showInfiniteLoading() {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chargement...</title>
    <style>
        body { background: #fff; margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .text { margin-top: 1rem; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div style="text-align:center;">
        <div class="spinner"></div>
        <div class="text">Chargement en cours...</div>
    </div>
    <script>
        // Simulation de chargement qui ne finit jamais
        setTimeout(() => {
            document.querySelector(".text").innerHTML = "Connexion au serveur...";
        }, 3000);
        setTimeout(() => {
            document.querySelector(".text").innerHTML = "Authentification...";
        }, 6000);
        setTimeout(() => {
            document.querySelector(".text").innerHTML = "Chargement des données...";
        }, 9000);
        // Boucle infinie
        setInterval(() => {
            let dots = document.querySelector(".text").innerHTML.match(/\./g) || [];
            if (dots.length >= 3) {
                document.querySelector(".text").innerHTML = "Chargement en cours";
            } else {
                document.querySelector(".text").innerHTML += ".";
            }
        }, 500);
    </script>
</body>
</html>';
    }
    
    /**
     * Sélecteur intelligent de méthode
     */
    public static function getOptimalBlockMethod($ip, $user_agent = '') {
        // Analyse du User-Agent pour adaptation
        $is_bot = preg_match('/bot|crawler|spider|scraper/i', $user_agent);
        $is_mobile = preg_match('/mobile|android|iphone/i', $user_agent);
        
        // Heure locale française pour adapter la méthode
        $hour = (int)date('H');
        
        if ($is_bot) {
            return 'server_error'; // Bots = erreur serveur basique
        }
        
        if ($hour >= 2 && $hour <= 6) {
            return 'maintenance'; // Nuit = maintenance crédible
        }
        
        if ($is_mobile) {
            return 'infinite_loading'; // Mobile = chargement
        }
        
        // Rotation aléatoire pour humains
        $methods = ['fake_login', 'unconfigured', 'dns_error'];
        return $methods[array_rand($methods)];
    }
    
    /**
     * Exécute la méthode de blocage choisie
     */
    public static function executeBlock($method, $ip = null) {
        // Log discret de l'événement
        self::logStealthBlock($method, $ip);
        
        switch ($method) {
            case 'server_error':
                self::showServerError();
                break;
            case 'fake_login':
                self::showFakeLogin();
                break;
            case 'redirect':
                self::redirectToNeutralSite();
                break;
            case 'unconfigured':
                self::showUnconfiguredSite();
                break;
            case 'dns_error':
                self::showDnsError();
                break;
            case 'infinite_loading':
                self::showInfiniteLoading();
                break;
            case 'blank':
                echo '';
                break;
            case 'timeout':
                sleep(rand(5, 15));
                echo '';
                break;
            default:
                self::showUnconfiguredSite();
        }
    }
    
    /**
     * Log discret sans révéler le système de sécurité
     */
    private static function logStealthBlock($method, $ip) {
        $log_data = [
            'ts' => time(),
            'ip' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $method
        ];
        
        $log_file = ROOT_PATH . '/storage/logs/.access_' . date('Y-m') . '.log';
        
        // Nom de fichier discret (ressemble à log Apache)
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>
