<?php
/**
 * Titre: Configuration version et constantes - CORRECTION URGENTE
 * Chemin: /config/version.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// FONCTIONS BUILD - DOIVENT ÊTRE DÉFINIES EN PREMIER
// =====================================

/**
 * Génère automatiquement le numéro de build
 * CRITIQUE : Cette fonction doit être définie AVANT les define()
 */
function generateBuildNumber(): array {
    // Fichiers clés à surveiller
    $key_files = [
        __FILE__,                                    // Ce fichier
        __DIR__ . '/../config/config.php',           // Config principale
        __DIR__ . '/../public/index.php',            // Point d'entrée
        __DIR__ . '/../public/port/index.php',       // Module port
        __DIR__ . '/../public/admin/index.php',      // Module admin
        __DIR__ . '/../templates/header.php',        // Template global
        __DIR__ . '/../templates/footer.php'         // Template global
    ];
    
    $latest_time = 0;
    $latest_file = '';
    
    foreach ($key_files as $file) {
        if (file_exists($file)) {
            $mtime = filemtime($file);
            if ($mtime > $latest_time) {
                $latest_time = $mtime;
                $latest_file = basename($file);
            }
        }
    }
    
    // Fallback si aucun fichier trouvé
    if ($latest_time === 0) {
        $latest_time = time();
        $latest_file = 'fallback';
    }
    
    return [
        'number' => date('YmdHis', $latest_time),
        'date' => date('d/m/Y H:i:s', $latest_time),
        'timestamp' => $latest_time,
        'source_file' => $latest_file
    ];
}

// =====================================
// VERSION ET BUILD - AUTO-GÉNÉRATION
// =====================================

// Version manuelle (changée uniquement par responsable projet)
define('APP_VERSION', '0.5.0-beta');
define('APP_VERSION_MAJOR', 0);
define('APP_VERSION_MINOR', 5);
define('APP_VERSION_PATCH', 0);
define('APP_VERSION_STATUS', 'beta');

// Build automatique
$build_info = generateBuildNumber();
define('APP_BUILD_NUMBER', $build_info['number']);
define('APP_BUILD_DATE', $build_info['date']);
define('APP_BUILD_TIMESTAMP', $build_info['timestamp']);
define('APP_BUILD_SOURCE_FILE', $build_info['source_file']);

// COMPATIBILITÉ AVEC L'ANCIEN SYSTÈME (legacy)
define('BUILD_NUMBER', APP_BUILD_NUMBER);
define('BUILD_DATE', APP_BUILD_DATE);
define('BUILD_TIMESTAMP', APP_BUILD_TIMESTAMP);

// =====================================
// IDENTITÉ APPLICATION
// =====================================

define('APP_SLUG', 'portail-guldagil');
define('APP_NAME', 'Portail Interne Guldagil');
define('APP_NAME_SHORT', 'Guldagil');
define('APP_TAGLINE', 'Solutions Intégrées Achats & Logistique');
define('APP_DESCRIPTION', 'Plateforme interne de gestion des achats, transport, ADR, EPI et contrôle qualité pour Guldagil');
define('APP_KEYWORDS', 'frais de port, transport, ADR, EPI, outillages, contrôle qualité, achats, logistique');

// =====================================
// INFORMATIONS LÉGALES
// =====================================

define('APP_AUTHOR', 'Jean-Thomas RUNSER');
define('APP_COMPANY', 'Guldagil');
define('APP_COMPANY_LEGAL', 'Guldagil SARL');
define('APP_COPYRIGHT_YEAR', (int)date('Y'));
define('APP_COPYRIGHT_START_YEAR', 2024);
define('COPYRIGHT_YEAR', APP_COPYRIGHT_YEAR); // Compatibilité legacy

// =====================================
// ENVIRONNEMENT ET DEBUG
// =====================================

$app_env = $_ENV['APP_ENV'] ?? 'development';
$valid_environments = ['development', 'staging', 'production'];
if (!in_array($app_env, $valid_environments, true)) {
    $app_env = 'development';
}
define('APP_ENV', $app_env);

// Debug et environnement
define('APP_DEBUG', APP_ENV === 'development');
define('APP_IS_PRODUCTION', APP_ENV === 'production');
define('APP_IS_DEVELOPMENT', APP_ENV === 'development');
define('APP_IS_STAGING', APP_ENV === 'staging');

// Compatibilité legacy - ÉVITER REDÉFINITION
if (!defined('DEBUG')) {
    define('DEBUG', APP_DEBUG);
}

// =====================================
// CONTACT
// =====================================

define('APP_SUPPORT_EMAIL', 'support@guldagil.com');
define('APP_CONTACT_EMAIL', 'contact@guldagil.com');
define('APP_ADMIN_EMAIL', 'admin@guldagil.com');

// =====================================
// FONCTIONS UTILITAIRES - CRITIQUES
// =====================================

/**
 * FONCTION CRITIQUE : getVersionInfo()
 * Cette fonction est appelée par /public/port/index.php ligne 267
 * ELLE DOIT ABSOLUMENT ÊTRE DÉFINIE
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

/**
 * Informations complètes de l'application
 */
function getAppInfo(): array {
    return [
        // Identité
        'name' => APP_NAME,
        'name_short' => APP_NAME_SHORT,
        'slug' => APP_SLUG,
        'tagline' => APP_TAGLINE,
        'description' => APP_DESCRIPTION,
        'keywords' => explode(', ', APP_KEYWORDS),
        
        // Version
        'version' => APP_VERSION,
        'version_parts' => [
            'major' => APP_VERSION_MAJOR,
            'minor' => APP_VERSION_MINOR,
            'patch' => APP_VERSION_PATCH,
            'status' => APP_VERSION_STATUS
        ],
        'build' => APP_BUILD_NUMBER,
        'build_date' => APP_BUILD_DATE,
        
        // Légal
        'author' => APP_AUTHOR,
        'company' => APP_COMPANY,
        'copyright' => sprintf('© %d-%d %s', 
            APP_COPYRIGHT_START_YEAR, 
            APP_COPYRIGHT_YEAR, 
            APP_COMPANY
        ),
        
        // Technique
        'environment' => APP_ENV,
        'debug' => APP_DEBUG,
        
        // Contact
        'support_email' => APP_SUPPORT_EMAIL,
        'contact_email' => APP_CONTACT_EMAIL
    ];
}

/**
 * Génère titre complet pour pages HTML
 */
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

/**
 * Métadonnées pour HTML head
 */
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

/**
 * FONCTIONS LEGACY - COMPATIBILITÉ ANCIENS FICHIERS
 */

/**
 * Version footer (legacy)
 */
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

/**
 * Version compacte (legacy)
 */
function renderVersionCompact(): string {
    return sprintf('v%s', APP_VERSION);
}

/**
 * Version JSON pour JavaScript (legacy)
 */
function getVersionJson(): string {
    return json_encode(getVersionInfo(), JSON_PRETTY_PRINT);
}

/**
 * Check nouveau build (legacy)
 */
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

/**
 * Version pour assets (cache busting)
 */
function getAssetVersion(): string {
    return substr(APP_BUILD_NUMBER, -6);
}

/**
 * URL versionnée (cache busting)
 */
function versionedUrl(string $url): string {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'v=' . getAssetVersion();
}

// =====================================
// VALIDATION ET DEBUG
// =====================================

/**
 * Validation des constantes critiques
 */
function validateAppConstants(): array {
    $errors = [];
    
    $required_constants = [
        'APP_NAME' => 'string',
        'APP_VERSION' => 'string', 
        'APP_AUTHOR' => 'string',
        'APP_ENV' => 'string',
        'APP_BUILD_NUMBER' => 'string'
    ];
    
    foreach ($required_constants as $const => $type) {
        if (!defined($const)) {
            $errors[] = "Constante manquante: {$const}";
            continue;
        }
        
        $value = constant($const);
        $actual_type = gettype($value);
        
        if ($actual_type !== $type) {
            $errors[] = "Type incorrect pour {$const}: attendu {$type}, reçu {$actual_type}";
        }
        
        if ($type === 'string' && empty(trim($value))) {
            $errors[] = "Valeur vide pour {$const}";
        }
    }
    
    return $errors;
}

// Log de chargement en développement
if (APP_IS_DEVELOPMENT && function_exists('error_log')) {
    error_log(sprintf(
        '[VERSION.PHP] %s v%s (Build %s) - Env: %s - CHARGÉ AVEC SUCCÈS', 
        APP_NAME, 
        APP_VERSION, 
        APP_BUILD_NUMBER, 
        APP_ENV
    ));
}

// Validation automatique
if (APP_IS_DEVELOPMENT) {
    $validation_errors = validateAppConstants();
    if (!empty($validation_errors)) {
        error_log("ERREURS VERSION.PHP:");
        foreach ($validation_errors as $error) {
            error_log("  - {$error}");
        }
    }
}

// =====================================
// FIN DU FICHIER
// =====================================
