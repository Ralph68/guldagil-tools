<?php
/**
 * Titre: Gestion centralisée des versions et builds auto-générés
 * Chemin: /config/version.php
 * Version: 0.5 beta + build auto
 */

// Version manuelle (changée uniquement par le responsable projet)
define('APP_VERSION', '0.5 beta');

// Auto-génération du build basé sur la date/heure du fichier
$build_info = generateBuildNumber();
define('BUILD_NUMBER', $build_info['number']);
define('BUILD_DATE', $build_info['date']);
define('BUILD_TIMESTAMP', $build_info['timestamp']);

// Constantes fixes
define('COPYRIGHT_YEAR', date('Y'));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
//define('DEBUG', APP_ENV === 'development');
define('APP_NAME', 'Portail Guldagil - Achats et Logistique');
define('APP_DESCRIPTION', 'Calc frais port, ADR, contrôle qualité...');
define('APP_AUTHOR', 'Jean-Thomas RUNSER');

/**
 * Génère automatiquement le numéro de build basé sur les fichiers
 * Format: YYYYMMDDHHMMSS (14 chiffres)
 */
function generateBuildNumber(): array {
    // Fichiers clés à surveiller pour déterminer la dernière modification
    $key_files = [
        __DIR__ . '/../public/index.php',
        __DIR__ . '/../public/calculateur/index.php',
        __DIR__ . '/../public/admin/index.php',
        __DIR__ . '/../public/controle-qualite/index.php',
        __FILE__ // Ce fichier lui-même
    ];
    
    $latest_timestamp = 0;
    
    // Trouve la modification la plus récente
    foreach ($key_files as $file) {
        if (file_exists($file)) {
            $mtime = filemtime($file);
            if ($mtime > $latest_timestamp) {
                $latest_timestamp = $mtime;
            }
        }
    }
    
    // Si aucun fichier trouvé, utilise l'heure actuelle
    if ($latest_timestamp === 0) {
        $latest_timestamp = time();
    }
    
    return [
        'number' => date('YmdHis', $latest_timestamp),
        'date' => date('Y-m-d H:i:s', $latest_timestamp),
        'timestamp' => $latest_timestamp
    ];
}

/**
 * Obtient les informations de version formatées
 */
function getVersionInfo(): array {
    return [
        'version' => APP_VERSION,
        'build' => BUILD_NUMBER,
        'date' => BUILD_DATE,
        'timestamp' => BUILD_TIMESTAMP,
        'environment' => APP_ENV,
        'debug' => DEBUG,
        'formatted_date' => date('d/m/Y H:i', BUILD_TIMESTAMP),
        'short_build' => substr(BUILD_NUMBER, -8) // 8 derniers chiffres pour affichage compact
    ];
}

/**
 * Affiche la version complète en footer
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
        COPYRIGHT_YEAR,
        APP_AUTHOR
    );
}

/**
 * Version compacte pour header/menu
 */
function renderVersionCompact(): string {
    $info = getVersionInfo();
    return sprintf('v%s', $info['version']);
}

/**
 * Version JSON pour JavaScript
 */
function getVersionJson(): string {
    return json_encode(getVersionInfo(), JSON_PRETTY_PRINT);
}

/**
 * Vérifie si c'est un nouveau build (utile pour cache busting)
 */
function isNewBuild(): bool {
    $cache_file = __DIR__ . '/.last_build';
    $current_build = BUILD_NUMBER;
    
    if (!file_exists($cache_file)) {
        file_put_contents($cache_file, $current_build);
        return true;
    }
    
    $last_build = trim(file_get_contents($cache_file));
    if ($last_build !== $current_build) {
        file_put_contents($cache_file, $current_build);
        return true;
    }
    
    return false;
}

/**
 * Version pour les assets (cache busting)
 */
function getAssetVersion(): string {
    return substr(BUILD_NUMBER, -6); // 6 derniers chiffres
}

/**
 * URL avec version pour cache busting
 */
function versionedUrl(string $url): string {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'v=' . getAssetVersion();
}

// Debug info si mode développement
if (DEBUG && php_sapi_name() === 'cli') {
    $info = getVersionInfo();
    echo "🏷️ Version: {$info['version']}\n";
    echo "🔨 Build: #{$info['build']}\n";
    echo "📅 Date: {$info['formatted_date']}\n";
    echo "🆔 Build court: #{$info['short_build']}\n";
    echo "🔧 Environnement: {$info['environment']}\n";
}
