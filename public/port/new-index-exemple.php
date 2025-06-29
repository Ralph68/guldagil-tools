<?php
/**
 * Titre: Module Calculateur - Page principale
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration du module
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Variables pour le header/footer
$page_title = 'Calculateur de frais';
$page_subtitle = 'Comparaison des tarifs transport';
$page_description = 'Calculateur et comparateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel';
$current_module = 'calculateur';
$module_css = true; // Charge calculateur.css
$module_js = true; // Charge calculateur.js

// Couleurs spécifiques au module
$module_colors = [
    'primary' => '#3b82f6',
    'secondary' => '#64748b', 
    'accent' => '#60a5fa'
];

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🧮', 'text' => 'Calculateur', 'url' => '/calculateur/', 'active' => true]
];

$nav_info = 'Calcul des frais de transport';
$show_admin_footer = false; // Pas d'admin sur les modules

// Inclure le header
include __DIR__ . '/../../templates/header.php';
?>

<!-- Contenu spécifique au calculateur -->
<section class="calculator-section">
    <div class="calc-header">
        <h2>🧮 Calculateur de frais de port</h2>
        <p>Comparez instantanément les tarifs XPO, Heppner et Kuehne+Nagel</p>
    </div>
    
    <div class="calc-layout">
        <div class="calc-form">
            <!-- Formulaire de calcul -->
            <form id="calculator-form" class="calc-form-container">
                <div class="form-group">
                    <label for="destination">Département de destination</label>
                    <select id="destination" name="destination" required>
                        <option value="">Sélectionner...</option>
                        <!-- Options générées dynamiquement -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="weight">Poids (kg)</label>
                    <input type="number" id="weight" name="weight" min="1" max="3000" required>
                </div>
                
                <div class="form-group">
                    <label for="pallets">Nombre de palettes</label>
                    <input type="number" id="pallets" name="pallets" min="1" max="10" value="1">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="adr" name="adr">
                        Transport ADR (marchandises dangereuses)
                    </label>
                </div>
                
                <button type="submit" class="calc-submit">
                    <span>Calculer les tarifs</span>
                    <span>→</span>
                </button>
            </form>
        </div>
        
        <div class="calc-results">
            <!-- Résultats de calcul -->
            <div id="results-container" class="results-empty">
                <div class="results-placeholder">
                    <div class="placeholder-icon">🚚</div>
                    <h3>Prêt pour le calcul</h3>
                    <p>Remplissez le formulaire pour comparer les tarifs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Scripts additionnels pour ce module
$additional_scripts = [
    '/assets/js/modules/calculateur/transport.js',
    '/assets/js/modules/calculateur/form-handler.js'
];

// Inclure le footer
include __DIR__ . '/../../templates/footer.php';
?>
