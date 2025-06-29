<?php
/**
 * Minification CSS pour production
 * Usage: php scripts/minify-css.php
 */

function minifyCSS($css) {
    // Supprimer commentaires
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Supprimer espaces inutiles
    $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace(['; ', ': ', ' {', '{ ', ' }', '} '], [';', ':', '{', '{', '}', '}'], $css);
    
    return trim($css);
}

$source = __DIR__ . '/../public/assets/css/portal.css';
$target = __DIR__ . '/../public/assets/css/portal.min.css';

if (file_exists($source)) {
    $css = file_get_contents($source);
    $minified = minifyCSS($css);
    
    file_put_contents($target, $minified);
    
    $originalSize = filesize($source);
    $minifiedSize = filesize($target);
    $savings = round((1 - $minifiedSize / $originalSize) * 100, 1);
    
    echo "✅ CSS minifié avec succès\n";
    echo "Original: " . number_format($originalSize) . " bytes\n";
    echo "Minifié: " . number_format($minifiedSize) . " bytes\n";
    echo "Économie: {$savings}%\n";
} else {
    echo "❌ Fichier source non trouvé: $source\n";
}
?>
