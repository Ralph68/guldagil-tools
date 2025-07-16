<?php
/**
 * Titre: Configuration version et constantes - BONNES PRATIQUES PHP
 * Chemin: /config/version.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// BONNES PRATIQUES CONSTANTES PHP
// =====================================

/**
 * Bonnes pratiques pour les constantes PHP :
 * 1. MAJUSCULES avec underscores
 * 2. Préfixe cohérent pour l'application
 * 3. Types primitifs uniquement (string, int, bool, float)
 * 4. Pas d'arrays ou objects dans les constantes
 * 5. Documentation claire
 * 6. Validation des valeurs
 */

// =====================================
// VERSION ET BUILD - Gestion automatique
// =====================================

// Version manuelle (MAJOR.MINOR.PATCH-STATUS)
define('APP_VERSION', '0.5.0-beta');
define('APP_VERSION_MAJOR', 0);
define('APP_VERSION_MINOR', 5);
define('APP_VERSION_PATCH', 0);
define('APP_VERSION_STATUS', 'beta');

// Build auto-généré
$build_info = generateBuildNumber();
define('APP_BUILD_NUMBER', $build_info['number']);
define('APP_BUILD_DATE', $build_info['date']);
define('APP_BUILD_TIMESTAMP', $build_info['timestamp']);
define('APP_BUILD_SOURCE_FILE', $build_info['source_file']);

// =====================================
// IDENTITÉ APPLICATION - Bonnes pratiques
// =====================================

// Nom technique (slug) - minuscules, tirets, pas d'espaces
define('APP_SLUG', 'portail-guldagil');

// Nom complet affiché - Clair et professionnel
define('APP_NAME', 'Portail Interne Guldagil');

// Nom court pour espaces restreints (mobile, notifications)
define('APP_NAME_SHORT', 'Guldagil');

// Baseline/tagline - Description courte et percutante
define('APP_TAGLINE', 'Solutions Intégrées Achats & Logistique');

// Description détaillée - SEO et documentation
define('APP_DESCRIPTION', 'Plateforme interne de gestion des achats, transport, ADR, EPI et contrôle qualité pour Guldagil');

// Mots-clés pour SEO et recherche
define('APP_KEYWORDS', 'frais de port, transport, ADR, EPI, outillages, contrôle qualité, achats, logistique');

// =====================================
// INFORMATIONS LÉGALES
// =====================================

define('APP_AUTHOR', 'Jean-Thomas RUNSER');
define('APP_COMPANY', 'Guldagil');
define('APP_COMPANY_LEGAL', 'Guldagil SARL');
define('APP_COPYRIGHT_YEAR', (int)date('Y'));
define('APP_COPYRIGHT_START_YEAR', 2024);

// =====================================
// ENVIRONNEMENT ET DEBUG
// =====================================

// Environnement avec validation
$app_env = $_ENV['APP_ENV'] ?? 'development';
$valid_environments = ['development', 'staging', 'production'];
if (!in_array($app_env, $valid_environments, true)) {
    $app_env = 'development';
    error_log("WARN: APP_ENV invalide, utilisation de 'development' par défaut");
}
define('APP_ENV', $app_env);

// Debug basé sur l'environnement
define('APP_DEBUG', APP_ENV === 'development');
define('APP_IS_PRODUCTION', APP_ENV === 'production');
define('APP_IS_DEVELOPMENT', APP_ENV === 'development');
define('APP_IS_STAGING', APP_ENV === 'staging');

// =====================================
// URLS ET CHEMINS
// =====================================

// URL de base (sera définie dans config.php selon contexte)
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', ''); // Sera surchargé par config.php
}

// Domaine principal
define('APP_DOMAIN', 'guldagil.local'); // À adapter selon environnement

// =====================================
// CONTACT ET SUPPORT
// =====================================

define('APP_SUPPORT_EMAIL', 'support@guldagil.com');
define('APP_CONTACT_EMAIL', 'contact@guldagil.com');
define('APP_ADMIN_EMAIL', 'admin@guldagil.com');

// =====================================
// FONCTIONS UTILITAIRES - Bonnes pratiques
// =====================================

/**
 * Génère automatiquement le numéro de build
 * Bonnes pratiques : Format standardisé, fichiers clés surveillés
 * 
 * @return array Informations de build
 */
function generateBuildNumber(): array {
    // Fichiers clés à surveiller (ordre d'importance)
    $key_files = [
        __FILE__,                                    // Ce fichier (version)
        __DIR__ . '/../config/config.php',           // Config principale
        __DIR__ . '/../public/index.php',            // Point d'entrée
        __DIR__ . '/../public/auth/login.php',       // Authentification
        __DIR__ . '/../public/port/index.php',       // Module principal
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

/**
 * Retourne informations complètes de l'application
 * Bonnes pratiques : Structure normalisée, validation
 * 
 * @return array
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
        'domain' => APP_DOMAIN,
        'base_url' => APP_BASE_URL,
        
        // Contact
        'support_email' => APP_SUPPORT_EMAIL,
        'contact_email' => APP_CONTACT_EMAIL
    ];
}

/**
 * Génère titre complet pour les pages HTML
 * Bonnes pratiques : SEO, hiérarchie, cohérence
 * 
 * @param string $page_title Titre de la page
 * @param bool $include_tagline Inclure la tagline
 * @return string
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
 * Retourne métadonnées pour HTML head
 * Bonnes pratiques : SEO, Open Graph, structure
 * 
 * @param array $page_data Données spécifiques à la page
 * @return array
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
 * Validation des constantes critiques
 * Bonnes pratiques : Validation au démarrage
 * 
 * @return array Erreurs trouvées
 */
function validateAppConstants(): array {
    $errors = [];
    
    // Vérifications obligatoires
    $required_constants = [
        'APP_NAME' => 'string',
        'APP_VERSION' => 'string', 
        'APP_AUTHOR' => 'string',
        'APP_ENV' => 'string'
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
    
    // Validations spécifiques
    if (defined('APP_VERSION') && !preg_match('/^\d+\.\d+\.\d+(-\w+)?$/', APP_VERSION)) {
        $errors[] = "Format de version invalide: " . APP_VERSION;
    }
    
    if (defined('APP_ENV') && !in_array(APP_ENV, ['development', 'staging', 'production'], true)) {
        $errors[] = "Environnement invalide: " . APP_ENV;
    }
    
    return $errors;
}

// =====================================
// VALIDATION AU CHARGEMENT
// =====================================

// Validation automatique en mode développement
if (APP_IS_DEVELOPMENT) {
    $validation_errors = validateAppConstants();
    if (!empty($validation_errors)) {
        error_log("ERREURS CONFIGURATION APP:");
        foreach ($validation_errors as $error) {
            error_log("  - {$error}");
        }
    }
}

// Log de chargement en développement
if (APP_IS_DEVELOPMENT && function_exists('error_log')) {
    error_log(sprintf(
        '[CONFIG] %s v%s (Build %s) - Environnement: %s', 
        APP_NAME, 
        APP_VERSION, 
        APP_BUILD_NUMBER, 
        APP_ENV
    ));
}
