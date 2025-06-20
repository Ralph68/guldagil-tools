<?php
/**
 * Titre: Module Calculateur - Interface step-by-step
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta - Build auto-généré
 * 
 * Interface calculateur style step-by-step comme dans les images
 */

// Configuration et dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Vérification module activé
if (!hasModuleAccess('calculateur')) {
    header('Location: ../');
    exit('Module calculateur non disponible');
}

// Démarrage session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération des options réelles de la BDD
$options_disponibles = [];
try {
    $stmt = $db->query("
        SELECT DISTINCT libelle, code_option 
        FROM gul_options_supplementaires 
        WHERE actif = 1 
        ORDER BY libelle
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options_disponibles[] = $row;
    }
} catch (Exception $e) {
    $options_disponibles = [
        ['libelle' => 'Livraison standard', 'code_option' => 'standard'],
        ['libelle' => 'Livraison express', 'code_option' => 'express'],
        ['libelle' => 'Prise de RDV', 'code_option' => 'rdv']
    ];
}

// Variables d'affichage
$page_title = 'Calculateur de frais';
$version_info = getVersionInfo();

// Présets depuis URL
$preset_data = [
    'departement' => $_GET['dept'] ?? ($_GET['departement'] ?? ''),
    'poids' => $_GET['poids'] ?? '',
    'type' => $_GET['type'] ?? 'colis',
    'adr' => $_GET['adr'] ?? 'non'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Calculateur de frais de port - Interface complète Guldagil">
    <meta name="keywords" content="calculateur, frais de port, transport, Guldagil">
    
    <!-- CSS - UN SEUL FICHIER LAYOUT -->
    <link rel="stylesheet" href="../assets/css/app.min.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/layout.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="calculator-page">

    <!-- Header avec navigation -->
    <header class="calculator-header">
        <div class="header-content">
            <div class="header-brand">
                <a href="../" class="brand-link">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                </a>
                <div class="brand-info">
                    <h1><?= htmlspecialchars($page_title) ?></h1>
                    <p>Interface complète</p>
                </div>
            </div>
            
            <div class="header-actions">
                <button type="button" class="header-btn" id="btn-nouveau-calcul">
                    📋 Nouveau calcul
                </button>
                <button type="button" class="header-btn" id="btn-historique">
                    📊 Historique
                </button>
                <div class="user-info">
                    👤 Interface
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="calculator-main">
        <div class="calculator-layout">
            
            <!-- Section Paramètres (Bleue - Gauche) -->
            <section class="parameters-section">
                <div class="parameters-header">
                    <h2>
                        🚚 Paramètres d'expédition
                    </h2>
                    <p>Renseignez vos critères pour comparer les transporteurs</p>
                </div>
                
                <form id="calculator-form" class="form-steps">
                    
                    <!-- Étape 1: Destination et poids -->
                    <div class="form-step" id="step-destination">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <h3 class="step-title">📍 Destination et poids</h3>
                        </div>
                        
                        <div class="step-fields">
                            <div class="form-field">
                                <label for="departement" class="form-label">Département de livraison</label>
                                <input 
                                    type="text" 
                                    id="departement" 
                                    name="departement" 
                                    class="form-input" 
                                    placeholder="Ex. 67"
                                    maxlength="3"
                                    value="<?= htmlspecialchars($preset_data['departement']) ?>"
                                    required
                                >
                                <div class="field-hint">2 chiffres (01 à 95)</div>
                            </div>
                            
                            <div class="form-field">
                                <label for="poids" class="form-label">Poids total (kg)</label>
                                <input 
                                    type="number" 
                                    id="poids" 
                                    name="poids" 
                                    class="form-input" 
                                    placeholder="Ex. 25"
                                    min="1" 
                                    max="3500" 
                                    step="1"
                                    value="<?= htmlspecialchars($preset_data['poids']) ?>"
                                    required
                                >
                                <div class="field-hint">Maximum 3500 kg</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Type d'expédition -->
                    <div class="form-step" id="step-type">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <h3 class="step-title">📦 Type d'expédition</h3>
                        </div>
                        
                        <div class="option-buttons">
                            <label class="option-button <?= $preset_data['type'] === 'colis' ? 'selected' : '' ?>" for="type-colis">
                                <input type="radio" id="type-colis" name="type" value="colis" <?= $preset_data['type'] === 'colis' ? 'checked' : '' ?> style="display: none;">
                                <div class="option-icon">📦</div>
                                <div class="option-content">
                                    <h4>Colis</h4>
                                    <p>Emballage individuel</p>
                                </div>
                            </label>
                            
                            <label class="option-button <?= $preset_data['type'] === 'palette' ? 'selected' : '' ?>" for="type-palette">
                                <input type="radio" id="type-palette" name="type" value="palette" <?= $preset_data['type'] === 'palette' ? 'checked' : '' ?> style="display: none;">
                                <div class="option-icon">🏗️</div>
                                <div class="option-content">
                                    <h4>Palette</h4>
                                    <p>Sur support EUR</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Étape 3: Marchandises dangereuses -->
                    <div class="form-step" id="step-adr">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <h3 class="step-title">⚠️ Marchandises dangereuses (ADR)</h3>
                        </div>
                        
                        <div class="option-buttons">
                            <label class="option-button <?= $preset_data['adr'] === 'non' ? 'selected' : '' ?>" for="adr-non">
                                <input type="radio" id="adr-non" name="adr" value="non" <?= $preset_data['adr'] === 'non' ? 'checked' : '' ?> style="display: none;">
                                <div class="option-icon">✅</div>
                                <div class="option-content">
                                    <h4>Non ADR</h4>
                                    <p>Marchandise standard</p>
                                </div>
                            </label>
                            
                            <label class="option-button <?= $preset_data['adr'] === 'oui' ? 'selected' : '' ?>" for="adr-oui">
                                <input type="radio" id="adr-oui" name="adr" value="oui" <?= $preset_data['adr'] === 'oui' ? 'checked' : '' ?> style="display: none;">
                                <div class="option-icon">⚠️</div>
                                <div class="option-content">
                                    <h4>ADR</h4>
                                    <p>Marchandise dangereuse</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Étape 4: Options de livraison -->
                    <div class="form-step" id="step-options">
                        <div class="step-header">
                            <div class="step-number">4</div>
                            <h3 class="step-title">🚀 Options de livraison</h3>
                        </div>
                        
                        <div class="step-fields single-column">
                            <div class="form-field">
                                <label for="service_livraison" class="form-label">Service de livraison</label>
                                <select id="service_livraison" name="option_sup" class="form-select">
                                    <option value="">Livraison standard</option>
                                    <?php foreach ($options_disponibles as $option): ?>
                                        <option value="<?= htmlspecialchars($option['code_option']) ?>">
                                            <?= htmlspecialchars($option['libelle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <label class="checkbox-field" for="enlevement">
                                <input type="checkbox" id="enlevement" name="enlevement" style="display: none;">
                                <div class="checkbox-icon">🏭</div>
                                <div class="checkbox-content">
                                    <h4>Enlèvement</h4>
                                    <p>Collecte sur votre site</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn-reset" id="btn-reset">
                            🔄 Réinitialiser
                        </button>
                    </div>
                    
                </form>
            </section>
            
            <!-- Section Tarif (Verte - Droite) -->
            <section class="results-section">
                <div class="results-header">
                    <h2>
                        💰 Votre tarif
                    </h2>
                    <p class="results-status">En attente</p>
                </div>
                
                <div class="results-content">
                    <div class="calculation-waiting" id="waiting-zone">
                        <div class="waiting-icon">🚀</div>
                        <div class="waiting-text">Prêt à calculer</div>
                        <div class="waiting-subtext">Renseignez le formulaire pour voir les tarifs de nos transporteurs partenaires</div>
                    </div>
                    
                    <div class="results-display" id="results-zone" style="display: none;">
                        <!-- Les résultats seront affichés ici par JavaScript -->
                    </div>
                </div>
            </section>
            
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="calculator-footer">
        <div class="footer-content">
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> Guldagil - Tous droits réservés</p>
            </div>
            <div class="footer-meta">
                <span>v<?= $version_info['version'] ?></span>
                <span>Build <?= $version_info['build'] ?></span>
                <?php if (DEBUG): ?>
                    <span class="debug">DEBUG</span>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- JavaScript - Architecture modulaire -->
    <script src="../assets/js/app.min.js"></script>
    <script src="../assets/js/modules/calculateur/utils.js"></script>
    <script src="../assets/js/modules/calculateur/ui.js"></script>
    <script src="../assets/js/modules/calculateur/form-handler.js"></script>
    <script src="../assets/js/modules/calculateur/calculs.js"></script>
    <script src="../assets/js/modules/calculateur/resultats-display.js"></script>
    <script src="../assets/js/modules/calculateur/calculateur.js"></script>
    
    <?php if (!empty($preset_data['departement']) && !empty($preset_data['poids'])): ?>
    <!-- Auto-calcul si présets valides -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Calculateur && Calculateur.Core) {
            setTimeout(() => {
                if (Calculateur.State && Calculateur.State.isFormValid()) {
                    Calculateur.Core.performCalculation();
                }
            }, 1000);
        }
    });
    </script>
    <?php endif; ?>
    
    <?php if (DEBUG): ?>
    <!-- Debug info -->
    <script>
    window.DEBUG_INFO = {
        version: '<?= $version_info['version'] ?>',
        build: '<?= $version_info['build'] ?>',
        environment: 'development',
        module: 'calculateur',
        presets: <?= json_encode($preset_data, JSON_UNESCAPED_UNICODE) ?>
    };
    
    console.log('🧮 Calculateur v<?= $version_info['version'] ?> - Debug activé');
    </script>
    <?php endif; ?>

</body>
</html>
