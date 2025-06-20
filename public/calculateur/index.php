<?php
/**
 * Titre: Module Calculateur - Interface principale
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build
 * 
 * Interface du calculateur de frais de port avec architecture modulaire
 * Compatible avec la nouvelle structure JavaScript
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

// Variables d'affichage
$page_title = 'Calculateur de frais de port';
$module_name = 'calculateur';
$version_info = getVersionInfo();

// Mode démo si demandé
$demo_mode = isset($_GET['demo']) && $_GET['demo'] === '1';

// Présets depuis paramètres URL
$preset_data = [
    'departement' => $_GET['dept'] ?? ($_GET['departement'] ?? ''),
    'poids' => $_GET['poids'] ?? '',
    'type' => $_GET['type'] ?? 'colis',
    'adr' => $_GET['adr'] ?? 'non'
];

// Nettoyage des présets
$preset_data['departement'] = preg_replace('/[^0-9]/', '', $preset_data['departement']);
if (strlen($preset_data['departement']) === 1) {
    $preset_data['departement'] = '0' . $preset_data['departement'];
}

// Statistiques du module (pour debug)
$module_stats = [];
if (DEBUG) {
    try {
        $stmt = $db->query("SELECT 
            COUNT(*) as total_today,
            COUNT(DISTINCT DATE(created_at)) as days_active,
            AVG(CASE WHEN best_price > 0 THEN best_price END) as avg_price
            FROM gul_calculator_history 
            WHERE created_at >= CURDATE() - INTERVAL 30 DAY");
        $module_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // Base stats par défaut en cas d'erreur
        $module_stats = ['total_today' => 0, 'days_active' => 0, 'avg_price' => 0];
    }
}

// Meta tags pour SEO
$meta_description = 'Calculateur de frais de port - Comparez instantanément les tarifs des transporteurs XPO, Heppner et Kuehne+Nagel';
$meta_keywords = 'calculateur, frais de port, transport, XPO, Heppner, Kuehne+Nagel, tarifs, expédition';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
    <meta name="author" content="Guldagil">
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/app.min.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur.css">
    
    <!-- Preconnect pour optimisation -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    
    <?php if ($demo_mode): ?>
    <!-- Mode démo -->
    <style>
        .demo-banner {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
    <?php endif; ?>
</head>
<body class="module-page module-calculateur">

    <?php if ($demo_mode): ?>
    <div class="demo-banner">
        🎮 MODE DÉMO - Données de test
    </div>
    <?php endif; ?>

    <!-- Header module -->
    <header class="module-header">
        <div class="container">
            <div class="header-content">
                <div class="header-brand">
                    <a href="../" class="back-link" title="Retour au portail">
                        ← Portail Guldagil
                    </a>
                    <h1 class="module-title">
                        🧮 <?= htmlspecialchars($page_title) ?>
                    </h1>
                    <p class="module-subtitle">
                        Comparaison instantanée des tarifs de transport
                    </p>
                </div>
                
                <div class="header-info">
                    <div class="version-badge">
                        v<?= APP_VERSION ?>
                    </div>
                    <?php if (DEBUG): ?>
                    <div class="debug-indicator">
                        <span class="debug-icon">🐛</span>
                        <span class="debug-text">Debug</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation module -->
    <nav class="module-navigation">
        <div class="container">
            <div class="nav-content">
                <div class="nav-stats">
                    <?php if (!empty($module_stats)): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?= (int)$module_stats['total_today'] ?></span>
                        <span class="stat-label">calculs aujourd'hui</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= number_format((float)$module_stats['avg_price'], 0) ?>€</span>
                        <span class="stat-label">prix moyen</span>
                    </div>
                    <?php endif; ?>
                    <div class="stat-item">
                        <span class="stat-value">3</span>
                        <span class="stat-label">transporteurs</span>
                    </div>
                </div>
                
                <div class="nav-actions">
                    <button class="btn btn-link" onclick="Calculateur.Core.resetCalculator()" title="Nouveau calcul">
                        🔄 Reset
                    </button>
                    <?php if (DEBUG): ?>
                    <button class="btn btn-link" onclick="Calculateur.Utils.debug.dump(Calculateur.State, 'État Calculateur')" title="Debug état">
                        🔍 Debug
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="module-main">
        <div class="container">
            <div class="calculator-layout">
                
                <!-- Formulaire de calcul -->
                <section class="calculator-form-section">
                    <div class="form-container">
                        <form id="calculator-form" class="calculator-form" method="post" novalidate>
                            
                            <!-- En-tête formulaire -->
                            <div class="form-header">
                                <h2 class="form-title">📦 Paramètres d'expédition</h2>
                                <p class="form-subtitle">Renseignez les critères pour comparer les tarifs</p>
                            </div>
                            
                            <!-- Champs principaux -->
                            <div class="form-grid">
                                
                                <!-- Département -->
                                <div class="form-group">
                                    <label for="departement" class="form-label required">
                                        📍 Département de destination
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               id="departement" 
                                               name="departement" 
                                               class="form-control" 
                                               placeholder="Ex: 75" 
                                               pattern="[0-9]{2}" 
                                               maxlength="2" 
                                               required
                                               value="<?= htmlspecialchars($preset_data['departement']) ?>"
                                               data-tooltip="Code département français (01 à 95)">
                                        <div class="input-suffix">
                                            <span id="departement-name" class="departement-display"></span>
                                        </div>
                                    </div>
                                    <div class="form-help">
                                        Saisissez le code à 2 chiffres (01 à 95)
                                    </div>
                                </div>
                                
                                <!-- Poids -->
                                <div class="form-group">
                                    <label for="poids" class="form-label required">
                                        ⚖️ Poids total
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               id="poids" 
                                               name="poids" 
                                               class="form-control" 
                                               placeholder="Ex: 25" 
                                               min="0.1" 
                                               max="3500" 
                                               step="0.1" 
                                               required
                                               value="<?= htmlspecialchars($preset_data['poids']) ?>"
                                               data-tooltip="Poids total en kilogrammes">
                                        <div class="input-suffix">kg</div>
                                    </div>
                                    <div id="poids-suggestions" class="form-suggestions"></div>
                                </div>
                                
                                <!-- Type d'envoi -->
                                <div class="form-group">
                                    <label class="form-label required">📦 Type d'envoi</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" 
                                                   name="type" 
                                                   value="colis" 
                                                   <?= $preset_data['type'] === 'colis' ? 'checked' : '' ?>>
                                            <span class="radio-label">
                                                <span class="radio-icon">📦</span>
                                                <span class="radio-text">Colis</span>
                                            </span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" 
                                                   name="type" 
                                                   value="palette" 
                                                   <?= $preset_data['type'] === 'palette' ? 'checked' : '' ?>>
                                            <span class="radio-label">
                                                <span class="radio-icon">🛏️</span>
                                                <span class="radio-text">Palette</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- ADR -->
                                <div class="form-group">
                                    <label class="form-label required">⚠️ Matières dangereuses (ADR)</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" 
                                                   name="adr" 
                                                   value="non" 
                                                   <?= $preset_data['adr'] === 'non' ? 'checked' : '' ?>>
                                            <span class="radio-label">
                                                <span class="radio-icon">✅</span>
                                                <span class="radio-text">Non</span>
                                            </span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" 
                                                   name="adr" 
                                                   value="oui" 
                                                   <?= $preset_data['adr'] === 'oui' ? 'checked' : '' ?>>
                                            <span class="radio-label">
                                                <span class="radio-icon">⚠️</span>
                                                <span class="radio-text">Oui</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <!-- Options avancées -->
                            <div class="form-advanced">
                                
                                <div class="advanced-header">
                                    <h3 class="advanced-title">⚙️ Options supplémentaires</h3>
                                </div>
                                
                                <div class="form-grid">
                                    
                                    <!-- Option supplémentaire -->
                                    <div class="form-group">
                                        <label for="option_sup" class="form-label">🎯 Service supplémentaire</label>
                                        <select id="option_sup" name="option_sup" class="form-control">
                                            <option value="standard">Standard</option>
                                            <option value="rdv">Prise de rendez-vous (+15€)</option>
                                            <option value="datefixe">Date fixe (+18€)</option>
                                            <option value="premium13">Premium avant 13h (+22€)</option>
                                            <option value="premium18">Premium avant 18h (+16€)</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Enlèvement -->
                                    <div class="form-group">
                                        <label class="checkbox-group">
                                            <input type="checkbox" id="enlevement" name="enlevement">
                                            <span class="checkbox-label">
                                                <span class="checkbox-icon">🚚</span>
                                                <span class="checkbox-text">Enlèvement à domicile</span>
                                            </span>
                                        </label>
                                    </div>
                                    
                                </div>
                                
                                <!-- Options palette (masquées par défaut) -->
                                <div id="palette-options" class="palette-options" style="display: none;">
                                    <div class="form-group">
                                        <label for="palettes" class="form-label">🛏️ Nombre de palettes EUR</label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   id="palettes" 
                                                   name="palettes" 
                                                   class="form-control" 
                                                   min="0" 
                                                   max="10" 
                                                   value="0">
                                            <div class="input-suffix">palettes</div>
                                        </div>
                                        <div id="palette-buttons" class="palette-buttons"></div>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <!-- Actions formulaire -->
                            <div class="form-actions">
                                <button type="submit" id="btn-calculate" class="btn btn-primary btn-large">
                                    <span>🚀</span>
                                    <span>Calculer les tarifs</span>
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <span>🔄</span>
                                    <span>Effacer</span>
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </section>
                
                <!-- Résultats -->
                <section class="calculator-results-section">
                    
                    <!-- Zone de loading -->
                    <div id="loading-zone" class="loading-zone" style="display: none;">
                        <div class="loading-content">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">Calcul en cours...</div>
                            <div class="loading-detail">Comparaison des transporteurs</div>
                        </div>
                    </div>
                    
                    <!-- Résultat principal -->
                    <div id="result-main" class="result-main">
                        <div class="result-header">
                            <h2 class="result-title">🎯 Résultat</h2>
                            <div id="result-status" class="result-status">En attente</div>
                        </div>
                        
                        <div id="result-content" class="result-content">
                            <div class="result-placeholder">
                                <div class="placeholder-icon">🚀</div>
                                <h4>Prêt à calculer</h4>
                                <p>Renseignez le formulaire pour voir les tarifs</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Zone alertes -->
                    <div id="alerts-zone" class="alerts-zone" style="display: none;"></div>
                    
                    <!-- Zone comparaison -->
                    <div id="comparison-zone" class="comparison-zone" style="display: none;"></div>
                    
                    <!-- Actions rapides -->
                    <div id="quick-actions" class="quick-actions" style="display: none;"></div>
                    
                </section>
                
            </div>
        </div>
    </main>

    <!-- Footer module -->
    <footer class="module-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <p>© <?= COPYRIGHT_YEAR ?> Guldagil - Solutions transport & logistique</p>
                    <p>Module calculateur version <?= APP_VERSION ?></p>
                </div>
                
                <div class="footer-version">
                    <?= renderVersionFooter() ?>
                </div>
                
                <?php if (DEBUG): ?>
                <div class="footer-debug">
                    <small>
                        Build <?= BUILD_NUMBER ?> - 
                        Env: <?= APP_ENV ?> - 
                        Debug: <?= DEBUG ? 'ON' : 'OFF' ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- JavaScript - Ordre de chargement modulaire -->
    <!-- 1. JS de base du portail (NE PAS TOUCHER) -->
    <script src="../assets/js/app.min.js"></script>
    
    <!-- 2. Modules calculateur dans l'ordre de dépendance -->
    <script src="../assets/js/modules/calculateur/utils.js"></script>
    <script src="../assets/js/modules/calculateur/ui.js"></script>
    <script src="../assets/js/modules/calculateur/form-handler.js"></script>
    <script src="../assets/js/modules/calculateur/calculs.js"></script>
    <script src="../assets/js/modules/calculateur/resultats-display.js"></script>
    <script src="../assets/js/modules/calculateur/calculateur.js"></script>
    
    <?php if ($demo_mode): ?>
    <!-- Script mode démo -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pré-remplir avec des données de démo
        const demoData = {
            departement: '75',
            poids: '25.5',
            type: 'colis',
            adr: 'non'
        };
        
        if (window.Calculateur && Calculateur.Form) {
            setTimeout(() => {
                Calculateur.Form.populateForm(demoData);
                Calculateur.UI.showInfo('Mode démo activé - Données de test chargées');
            }, 500);
        }
    });
    </script>
    <?php endif; ?>
    
    <?php if (!empty($preset_data['departement']) && !empty($preset_data['poids'])): ?>
    <!-- Auto-calcul si présets valides -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Calculateur && Calculateur.Core) {
            // Attendre que le module soit complètement initialisé
            setTimeout(() => {
                const state = Calculateur.State;
                if (state && state.isFormValid()) {
                    Calculateur.Core.performCalculation();
                }
            }, 1000);
        }
    });
    </script>
    <?php endif; ?>
    
    <?php if (DEBUG): ?>
    <!-- Debug helpers -->
    <script>
    // Exposer variables PHP pour debug
    window.DEBUG_INFO = {
        version: '<?= APP_VERSION ?>',
        build: '<?= BUILD_NUMBER ?>',
        environment: '<?= APP_ENV ?>',
        module: '<?= $module_name ?>',
        demo: <?= $demo_mode ? 'true' : 'false' ?>,
        presets: <?= json_encode($preset_data, JSON_UNESCAPED_UNICODE) ?>,
        stats: <?= json_encode($module_stats, JSON_UNESCAPED_UNICODE) ?>
    };
    
    // Commandes debug console
    console.log('🧮 Module Calculateur v<?= APP_VERSION ?> - Debug activé');
    console.log('💡 Utilisez window.DEBUG_INFO pour les infos debug');
    console.log('💡 Utilisez Calculateur.Utils.debug.* pour les outils debug');
    </script>
    <?php endif; ?>

</body>
</html>
