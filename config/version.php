<?php
// config/version.php - Gestion centralisée des versions et builds

// Version actuelle de l'application
define('APP_VERSION', '0.5 beta');

// Numéro de build (format: YYYYMMDDNNN)
define('BUILD_NUMBER', '20250620001');

// Date et heure de build
define('BUILD_DATE', '2025-06-20 14:30:00');

// Année pour le copyright
define('COPYRIGHT_YEAR', date('Y'));

// Environnement (dev, staging, production)
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

// Mode debug
define('DEBUG', APP_ENV === 'development');

// Informations supplémentaires
define('APP_NAME', 'Guldagil Port Calculator');
define('APP_DESCRIPTION', 'Calculateur et comparateur de frais de port');
define('APP_AUTHOR', 'Guldagil');

// Fonction pour obtenir les informations de version formatées
function getVersionInfo(): array {
    return [
        'version' => APP_VERSION,
        'build' => BUILD_NUMBER,
        'date' => BUILD_DATE,
        'environment' => APP_ENV,
        'debug' => DEBUG,
        'formatted_date' => date('d/m/Y H:i', strtotime(BUILD_DATE))
    ];
}

// Fonction pour afficher la version en footer
function renderVersionFooter(): string {
    $info = getVersionInfo();
    return sprintf(
        '<span class="version">v%s</span><span class="build">Build #%s</span><span class="date">%s</span>',
        $info['version'],
        $info['build'],
        $info['formatted_date']
    );
}
?>
