<?php
/**
 * Titre: Configuration version - PRODUCTION READY
 * Chemin: /config/version.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    exit('Accès direct interdit');
}

// =====================================
// VERSION MANUELLE - Contrôlée par responsable projet
// =====================================

define('APP_VERSION', '0.5 beta');

// Parsing version
$version_parts = explode('.', str_replace(' beta', '', APP_VERSION));
define('APP_VERSION_MAJOR', (int)($version_parts[0] ?? 0));
define('APP_VERSION_MINOR', (int)($version_parts[1] ?? 0));
define('APP_VERSION_PATCH', (int)($version_parts[2] ?? 0));
define('APP_VERSION_STATUS', strpos(APP_VERSION, 'beta') !== false ? 'beta' : 'stable');

// =====================================
// BUILD AUTO-GÉNÉRÉ
// =====================================

function generateBuildNumber(): array {
    $key_files = [
        __DIR__ . '/../public/index.php',
        __DIR__ . '/../public/calculateur/index.php',
        __DIR__ . '/../public/admin/index.php',
        __DIR__ . '/../templates/header.php',
        __DIR__ . '/../templates/footer.php',
        __DIR__ . '/config.php',
        __FILE__
    ];
    
    $latest_timestamp = 0;
    foreach ($key_files as $file) {
        if (file_exists($file)) {
            $timestamp = filemtime($file);
            if ($timestamp > $latest_timestamp) {
                $latest_timestamp = $timestamp;
            }
        }
    }
    
    if ($latest_timestamp === 0) {
        $latest_timestamp = time();
    }
    
    return [
        'number' => date('YmdHis', $latest_timestamp),
        'date' => date('Y-m-d H:i:s', $latest_timestamp),
        'timestamp' => $latest_timestamp
    ];
}

$build_info = generateBuildNumber();
define('APP_BUILD_NUMBER', $build_info['number']);
define('APP_BUILD_DATE', $build_info['date']);
define('APP_BUILD_TIMESTAMP', $build_info['timestamp']);

// Compatibilité legacy
define('BUILD_NUMBER', APP_BUILD_NUMBER);
define('BUILD_DATE', APP_BUILD_DATE);
define('BUILD_TIMESTAMP', APP_BUILD_TIMESTAMP);

// =====================================
// ENVIRONNEMENT SÉCURISÉ
// =====================================

$app_env = getenv('APP_ENV') ?: 'production';
$valid_environments = ['development', 'staging', 'production'];
if (!in_array($app_env, $valid_environments, true)) {
    $app_env = 'production';
}
define('APP_ENV', $app_env);

define('APP_DEBUG', APP_ENV === 'development');
define('APP_IS_PRODUCTION', APP_ENV === 'production');
define('APP_IS_DEVELOPMENT', APP_ENV === 'development');
define('APP_IS_STAGING', APP_ENV === 'staging');

// Compatibilité legacy
if (!defined('DEBUG')) {
    define('DEBUG', APP_DEBUG);
}

// =====================================
// INFORMATIONS APPLICATION
// =====================================

define('APP_NAME', 'Portail Guldagil - Achats et Logistique');
define('APP_NAME_SHORT', 'Guldagil Portal');
define('APP_SLUG', 'guldagil-portal');
define('APP_TAGLINE', 'Solutions logistiques et achats intelligents');
define('APP_DESCRIPTION', 'Calc frais port, ADR, contrôle qualité...');
define('APP_KEYWORDS', 'transport, logistique, frais de port, ADR, achats, Guldagil');

// =====================================
// INFORMATIONS LÉGALES
// =====================================

define('APP_AUTHOR', 'Jean-Thomas RUNSER');
define('APP_COMPANY', 'Guldagil');
define('APP_COPYRIGHT_START_YEAR', 2024);
define('APP_COPYRIGHT_YEAR', date('Y'));

// =====================================
// CONTACT
// =====================================

define('APP_SUPPORT_EMAIL', 'support@guldagil.com');
define('APP_CONTACT_EMAIL', 'contact@guldagil.com');
define('APP_ADMIN_EMAIL', 'admin@guldagil.com');

// =====================================
// FONCTIONS CRITIQUES - PRÉSERVÉES
// =====================================

/**
 * FONCTION CRITIQUE : getVersionInfo()
 * Appelée par /public/port/index.php ligne 267
 * DOIT ABSOLUMENT ÊTRE DÉFINIE
 */
function getVersionInfo(): array {
    return [
        'version' => APP_VERSION,
        'build' => APP_BUILD_NUMBER,
        'date' => APP_BUILD_DATE,
        'timestamp' => APP_BUILD_TIMESTAMP,
        'environment' => APP_ENV,
        'debug' => APP_DEBUG,
        'formatted_date' => date('d/m/Y H:i', APP_BUILD_TIMESTAMP),
        'short_build' => substr(APP_BUILD_NUMBER, -8)
    ];
}

function getAppInfo(): array {
    return [
        'name' => APP_NAME,
        'name_short' => APP_NAME_SHORT,
        'slug' => APP_SLUG,
        'tagline' => APP_TAGLINE,
        'description' => APP_DESCRIPTION,
        'keywords' => explode(', ', APP_KEYWORDS),
        'version' => APP_VERSION,
        'version_parts' => [
            'major' => APP_VERSION_MAJOR,
            'minor' => APP_VERSION_MINOR,
            'patch' => APP_VERSION_PATCH,
            'status' => APP_VERSION_STATUS
        ],
        'build' => APP_BUILD_NUMBER,
        'build_date' => APP_BUILD_DATE,
        'author' => APP_AUTHOR,
        'company' => APP_COMPANY,
        'copyright' => sprintf('© %d-%d %s', 
            APP_COPYRIGHT_START_YEAR, 
            APP_COPYRIGHT_YEAR, 
            APP_COMPANY
        ),
        'environment' => APP_ENV,
        'debug' => APP_DEBUG,
        'support_email' => APP_SUPPORT_EMAIL,
        'contact_email' => APP_CONTACT_EMAIL
    ];
}

function getPageTitle(string $page_title = '', bool $include_tagline = false): string {
    $parts = [];
    if (!empty($page_title)) {
        $parts[] = $page_title;
    }
    $parts[] = APP_NAME;
    if ($include_tagline) {
        $parts[] = APP_TAGLINE;
    }
    return implode(' - ', $parts);
}

function getPageMetadata(array $page_data = []): array {
    $defaults = [
        'title' => APP_NAME,
        'description' => APP_DESCRIPTION,
        'keywords' => APP_KEYWORDS,
        'author' => APP_AUTHOR,
        'copyright' => sprintf('© %d %s', APP_COPYRIGHT_YEAR, APP_COMPANY),
        'generator' => sprintf('%s v%s', APP_NAME, APP_VERSION),
        'application-name' => APP_NAME,
        'build' => APP_BUILD_NUMBER
    ];
    return array_merge($defaults, $page_data);
}

// =====================================
// FONCTIONS LEGACY - COMPATIBILITÉ
// =====================================

function renderVersionFooter(): string {
    $info = getVersionInfo();
    return sprintf(
        '<div class="version-footer">
            <span class="version">v%s</span>
            <span class="build">Build #%s</span>
            <span class="date">%s</span>
            <span class="copyright">© %s %s</span>
        </div>',
        $info['version'],
        $info['short_build'],
        $info['formatted_date'],
        APP_COPYRIGHT_YEAR,
        APP_AUTHOR
    );
}

function renderVersionCompact(): string {
    return sprintf('v%s', APP_VERSION);
}

function getVersionJson(): string {
    return json_encode(getVersionInfo(), JSON_PRETTY_PRINT);
}

function isNewBuild(): bool {
    $cache_file = __DIR__ . '/.last_build';
    $current_build = APP_BUILD_NUMBER;
    
    if (!file_exists($cache_file)) {
        @file_put_contents($cache_file, $current_build);
        return true;
    }
    
    $last_build = trim(@file_get_contents($cache_file));
    if ($last_build !== $current_build) {
        @file_put_contents($cache_file, $current_build);
        return true;
    }
    
    return false;
}

function getAssetVersion(): string {
    return substr(APP_BUILD_NUMBER, -6);
}

function versionedUrl(string $url): string {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'v=' . getAssetVersion();
}

function isMaintenanceMode(): bool {
    $maintenance_file = ROOT_PATH . '/storage/maintenance.flag';
    return file_exists($maintenance_file);
}

function setMaintenanceMode(bool $enabled, string $message = ''): bool {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    $maintenance_file = ROOT_PATH . '/storage/maintenance.flag';
    
    if ($enabled) {
        $data = [
            'enabled_at' => time(),
            'enabled_by' => $_SESSION['username'] ?? 'admin',
            'message' => $message ?: 'Maintenance en cours',
            'expected_duration' => '30 minutes'
        ];
        return file_put_contents($maintenance_file, json_encode($data)) !== false;
    } else {
        return @unlink($maintenance_file);
    }
}

function getMaintenanceInfo(): ?array {
    $maintenance_file = ROOT_PATH . '/storage/maintenance.flag';
    
    if (!file_exists($maintenance_file)) {
        return null;
    }
    
    $content = file_get_contents($maintenance_file);
    $data = json_decode($content, true);
    
    if (!$data) {
        return null;
    }
    
    return [
        'enabled' => true,
        'message' => $data['message'] ?? 'Maintenance en cours',
        'enabled_at' => $data['enabled_at'] ?? time(),
        'enabled_by' => $data['enabled_by'] ?? 'admin',
        'duration' => time() - ($data['enabled_at'] ?? time())
    ];
}

// =====================================
// VALIDATION SÉCURITÉ
// =====================================

if (APP_IS_PRODUCTION && APP_DEBUG) {
    error_log('SECURITY WARNING: DEBUG is enabled in PRODUCTION environment!');
}

if (APP_DEBUG) {
    error_log(sprintf(
        'Application started - Version: %s, Build: %s, Environment: %s',
        APP_VERSION,
        APP_BUILD_NUMBER,
        APP_ENV
    ));
}

?>
