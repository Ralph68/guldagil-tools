<?php
/**
 * Titre: Module Calculateur - Interface progressive
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build
 * 
 * Interface calculateur progressive avec architecture MVC
 */

// =========================================================================
// CONFIGURATION ET SÉCURITÉ
// =========================================================================

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Définir BASE_PATH si pas défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Démarrage session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification accès module (si système d'authentification activé)
if (function_exists('hasModuleAccess') && !hasModuleAccess('calculateur')) {
    header('Location: ../');
    exit('Module calculateur non disponible');
}

// =========================================================================
// RÉCUPÉRATION DES DONNÉES
// =========================================================================

try {
    // Options de service depuis BDD
    $options_service = [];
    $stmt = $db->query("
        SELECT DISTINCT transporteur, code_option, libelle, montant 
        FROM gul_options_supplementaires 
        WHERE actif = 1 
        ORDER BY transporteur, libelle
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options_service[] = $row;
    }
    
    // Départements avec restrictions (pour validation côté client)
    $dept_restrictions = [];
    $stmt = $db->query("
        SELECT transporteur, departements_blacklistes 
        FROM gul_taxes_transporteurs 
        WHERE departements_blacklistes IS NOT NULL AND departements_blacklistes != ''
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['departements_blacklistes']) {
            $dept_restrictions[$row['transporteur']] = explode(',', $row['departements_blacklistes']);
        }
    }
    
} catch (Exception $e) {
    // Fallback en cas d'erreur BDD
    $options_service = [
        ['transporteur' => 'Tous', 'code_option' => 'standard', 'libelle' => 'Livraison standard', 'montant' => 0],
        ['transporteur' => 'Tous', 'code_option' => 'rdv', 'libelle' => 'Prise de RDV', 'montant' => 15]
    ];
    $dept_restrictions = [];
    error_log("Erreur récupération données calculateur: " . $e->getMessage());
}

// Variables d'affichage
$page_title = 'Calculateur de frais de port';
$version_info = getVersionInfo();

// Présets depuis URL ou session
$preset_data = [
    'departement' => $_GET['dept'] ?? ($_GET['departement'] ?? ($_SESSION['calc_dept'] ?? '')),
    'poids' => $_GET['poids'] ?? ($_SESSION['calc_poids'] ?? ''),
    'type' => $_GET['type'] ?? ($_SESSION['calc_type'] ?? ''),
    'adr' => $_GET['adr'] ?? ($_SESSION['calc_adr'] ?? 'non')
];

// Mode debug
$debug_mode = isset($_GET['debug']) || (defined('DEBUG') && DEBUG);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Meta tags SEO et techniques -->
    <meta name="description" content="Calculateur de frais de port - Interface progressive pour comparaison Heppner, XPO et K+N">
    <meta name="keywords" content="calculateur, frais de port, transport, Heppner, XPO, Kuehne Nagel, Guldagil">
    <meta name="author" content="Guldagil">
    <meta name="robots" content="noindex, nofollow">
    <?php if ($debug_mode): ?>
    <meta name="environment" content="development">
    <?php endif; ?>
    
    <!-- Preconnect pour optimisation -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- CSS - Architecture modulaire -->
    <link rel="stylesheet" href="assets/css/modules/calculateur/base.css">
    <link rel="stylesheet" href="assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="assets/css/modules/calculateur/progressive-form.css">
    <link rel="stylesheet" href="assets/css/modules/calculateur/results.css">
    <link rel="stylesheet" href="assets/css/modules/calculateur/components.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    
    <!-- Données pour JavaScript -->
    <script>
        window.CALCULATEUR_CONFIG = {
            presetData: <?= json_encode($preset_data, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            optionsService: <?= json_encode($options_service, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            deptRestrictions: <?= json_encode($dept_restrictions, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            debugMode: <?= json_encode($debug_mode) ?>,
            version: <?= json_encode($version_info, JSON_HEX_TAG | JSON_HEX_AMP) ?>
        };
    </script>
</head>
<body class="calculator-page">

    <!-- Header avec navigation -->
    <?php include __DIR__ . '/views/partials/header.php'; ?>

    <!-- Contenu principal -->
    <main class="calculator-main">
        <div class="calc-container">
            <div class="calculator-layout">
                
                <!-- Section formulaire progressif -->
                <section class="form-section">
                    <!-- En-tête formulaire -->
                    <div class="form-header">
                        <h2>
                            <span>🚚</span>
                            Calculateur de frais de port
                        </h2>
                        <p>Interface progressive - Comparez Heppner, XPO et K+N en temps réel</p>
                    </div>
                    
                    <!-- Barre de progression -->
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill"></div>
                        </div>
                        <div class="progress-steps" id="progress-steps">
                            <!-- Généré par JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Contenu du formulaire -->
                    <div class="form-content">
                        <form id="calculator-form" class="form-steps-container">
                            
                            <!-- Étape 1: Destination et poids -->
                            <div class="form-step active" id="step-destination" data-step="0">
                                <div class="step-header">
                                    <div class="step-number">1</div>
                                    <h3 class="step-title">📍 Destination et poids</h3>
                                    <p class="step-description">Indiquez où livrer et le poids de votre envoi</p>
                                </div>
                                
                                <div class="form-fields">
                                    <div class="form-field">
                                        <label for="departement" class="form-label">
                                            Département de livraison <span class="required">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="departement" 
                                            name="departement" 
                                            class="form-input" 
                                            placeholder="Ex: 67, 75, 13..."
                                            maxlength="3"
                                            autocomplete="off"
                                            value="<?= htmlspecialchars($preset_data['departement']) ?>"
                                            required
                                        >
                                        <div class="field-hint">Code département français (01 à 95)</div>
                                        <div class="field-error" id="error-departement" style="display: none;"></div>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="poids" class="form-label">
                                            Poids total <span class="required">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="poids" 
                                            name="poids" 
                                            class="form-input" 
                                            placeholder="150"
                                            min="0.1" 
                                            max="3500" 
                                            step="0.1"
                                            value="<?= htmlspecialchars($preset_data['poids']) ?>"
                                            required
                                        >
                                        <div class="field-hint">Poids en kilogrammes (0.1 à 3500 kg)</div>
                                        <div class="field-error" id="error-poids" style="display: none;"></div>
                                    </div>
                                </div>
                                
                                <div class="step-summary" id="summary-step-1" style="display: none;">
                                    <div class="summary-title">Récapitulatif</div>
                                    <div class="summary-content" id="summary-content-1"></div>
                                </div>
                            </div>
                            
                            <!-- Étape 2: Type d'envoi -->
                            <div class="form-step" id="step-type" data-step="1">
                                <div class="step-header">
                                    <div class="step-number">2</div>
                                    <h3 class="step-title">📦 Type d'expédition</h3>
                                    <p class="step-description">Choisissez comment vous expédiez</p>
                                </div>
                                
                                <div class="form-fields">
                                    <div class="form-field">
                                        <label class="form-label">Type d'envoi <span class="required">*</span></label>
                                        <div class="radio-group">
                                            <label class="radio-option" for="type-colis">
                                                <input type="radio" id="type-colis" name="type" value="colis" class="radio-input">
                                                <div class="radio-content">
                                                    <div class="radio-title">📦 Colis</div>
                                                    <div class="radio-description">Envoi jusqu'à 60kg - Maximum 2 colis</div>
                                                </div>
                                            </label>
                                            <label class="radio-option" for="type-palette">
                                                <input type="radio" id="type-palette" name="type" value="palette" class="radio-input">
                                                <div class="radio-content">
                                                    <div class="radio-title">🏗️ Palette(s)</div>
                                                    <div class="radio-description">Palette EUR 80x120cm - Jusqu'à 26 palettes</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-field" id="field-palettes" style="display: none;">
                                        <label for="palettes" class="form-label">Nombre de palettes EUR</label>
                                        <input 
                                            type="number" 
                                            id="palettes" 
                                            name="palettes" 
                                            class="form-input" 
                                            placeholder="1"
                                            min="0" 
                                            max="26"
                                            value="0"
                                        >
                                        <div class="field-hint">Palettes EUR 80x120cm (0 à 26 palettes)</div>
                                    </div>
                                </div>
                                
                                <div class="step-summary" id="summary-step-2" style="display: none;">
                                    <div class="summary-title">Type sélectionné</div>
                                    <div class="summary-content" id="summary-content-2"></div>
                                </div>
                            </div>
                            
                            <!-- Étape 3: Options -->
                            <div class="form-step" id="step-options" data-step="2">
                                <div class="step-header">
                                    <div class="step-number">3</div>
                                    <h3 class="step-title">⚡ Options et services</h3>
                                    <p class="step-description">Personnalisez votre expédition</p>
                                </div>
                                
                                <div class="form-fields">
                                    <div class="form-field">
                                        <label for="adr" class="form-label">Marchandises dangereuses (ADR)</label>
                                        <select id="adr" name="adr" class="form-select">
                                            <option value="non" <?= $preset_data['adr'] === 'non' ? 'selected' : '' ?>>Non - Marchandises normales</option>
                                            <option value="oui" <?= $preset_data['adr'] === 'oui' ? 'selected' : '' ?>>Oui - ADR requis</option>
                                        </select>
                                        <div class="field-hint">Les marchandises ADR ont des restrictions et surcoûts</div>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="service_livraison" class="form-label">Service de livraison</label>
                                        <select id="service_livraison" name="service_livraison" class="form-select">
                                            <option value="standard">Livraison standard</option>
                                            <option value="rdv">Prise de RDV (+15€)</option>
                                            <option value="star18">Star 18h - Heppner uniquement</option>
                                            <option value="star13">Star 13h - Heppner uniquement</option>
                                            <option value="datefixe18">Date fixe avant 18h</option>
                                            <option value="datefixe13">Date fixe avant 13h</option>
                                            <option value="premium18">Premium 18h - XPO uniquement</option>
                                            <option value="premium13">Premium 13h - XPO uniquement</option>
                                        </select>
                                        <div class="field-hint">Choisissez le service adapté à vos besoins</div>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label class="checkbox-field">
                                            <input type="checkbox" id="enlevement" name="enlevement" class="checkbox-input" value="1">
                                            <span class="checkbox-label">
                                                Enlèvement chez l'expéditeur (+25€)
                                            </span>
                                        </label>
                                        <div class="field-hint">Service d'enlèvement à domicile ou en entreprise</div>
                                    </div>
                                </div>
                                
                                <div class="step-summary" id="summary-step-3" style="display: none;">
                                    <div class="summary-title">Options sélectionnées</div>
                                    <div class="summary-content" id="summary-content-3"></div>
                                </div>
                            </div>
                            
                            <!-- Navigation cachée (gérée par JS) -->
                            <div class="step-navigation auto-advance-nav">
                                <button type="button" class="nav-btn secondary" id="btn-previous">
                                    ← Précédent
                                </button>
                                <button type="button" class="nav-btn primary" id="btn-next">
                                    Suivant →
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </section>
                
                <!-- Section résultats -->
                <section class="results-section">
                    <?php include __DIR__ . '/views/components/results-panel.php'; ?>
                </section>
                
            </div>
        </div>
    </main>

    <!-- Actions flottantes -->
    <div class="floating-actions">
        <button type="button" class="floating-btn" id="btn-reset" title="Nouveau calcul">
            🔄
        </button>
        <?php if ($debug_mode): ?>
        <button type="button" class="floating-btn debug" id="btn-debug" title="Debug">
            🐛
        </button>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/views/partials/footer.php'; ?>

    <!-- Scripts JavaScript - TEST TEMPORAIRE -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé');
        
        // Gestion simple du type
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const palettesField = document.getElementById('field-palettes');
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Type changé:', this.value);
                if (palettesField) {
                    palettesField.style.display = this.value === 'palette' ? 'block' : 'none';
                }
            });
        });
        
        // Navigation étapes simple
        const steps = document.querySelectorAll('.form-step');
        const progressSteps = document.querySelectorAll('.progress-step');
        let currentStep = 0;
        
        function goToStep(stepIndex) {
            console.log('Aller à étape:', stepIndex);
            
            steps.forEach((step, index) => {
                step.classList.toggle('active', index === stepIndex);
            });
            
            progressSteps.forEach((step, index) => {
                step.classList.remove('pending', 'current', 'completed');
                if (index < stepIndex) {
                    step.classList.add('completed');
                } else if (index === stepIndex) {
                    step.classList.add('current');
                } else {
                    step.classList.add('pending');
                }
            });
            
            currentStep = stepIndex;
        }
        
        // Auto-avancement simple
        const departement = document.getElementById('departement');
        const poids = document.getElementById('poids');
        
        function checkStep1() {
            if (departement.value.length >= 2 && poids.value) {
                setTimeout(() => goToStep(1), 500);
            }
        }
        
        departement?.addEventListener('input', checkStep1);
        poids?.addEventListener('input', checkStep1);
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                setTimeout(() => goToStep(2), 300);
            });
        });
        
        // Navigation manuelle
        progressSteps.forEach((step, index) => {
            step.addEventListener('click', () => goToStep(index));
        });
    });
    </script>

    <!-- Initialisation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier que tous les modules sont chargés
            if (typeof window.calculateurApp !== 'undefined') {
                // Initialiser avec les données serveur
                window.calculateurApp.init(window.CALCULATEUR_CONFIG);
            } else {
                console.error('❌ Modules calculateur non chargés');
                
                // Fallback basique
                document.getElementById('calculator-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    alert('Module calculateur en cours de chargement...');
                });
            }
        });
        
        // Gestion des erreurs globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript:', e.error);
            
            // En production, masquer les erreurs techniques
            <?php if (!$debug_mode): ?>
            e.preventDefault();
            <?php endif; ?>
        });
        
        // Performance monitoring
        window.addEventListener('load', function() {
            if (window.performance && window.performance.timing) {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                CalculateurConfig?.log('info', `Page chargée en ${loadTime}ms`);
            }
        });
    </script>

    <?php if ($debug_mode): ?>
    <!-- Debug panel -->
    <div id="debug-panel" class="debug-panel" style="display: none;">
        <div class="debug-header">
            <h3>🐛 Debug Calculateur</h3>
            <button onclick="document.getElementById('debug-panel').style.display='none'">×</button>
        </div>
        <div class="debug-content">
            <div class="debug-section">
                <h4>Configuration</h4>
                <pre id="debug-config"></pre>
            </div>
            <div class="debug-section">
                <h4>État actuel</h4>
                <pre id="debug-state"></pre>
            </div>
            <div class="debug-section">
                <h4>Dernière requête</h4>
                <pre id="debug-request"></pre>
            </div>
            <div class="debug-section">
                <h4>Statistiques API</h4>
                <pre id="debug-stats"></pre>
            </div>
        </div>
    </div>
    
    <style>
        .debug-panel {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 400px;
            max-height: 80vh;
            background: var(--calc-gray-900);
            color: var(--calc-white);
            border-radius: var(--calc-radius);
            box-shadow: var(--calc-shadow-xl);
            z-index: var(--calc-z-modal);
            overflow: hidden;
        }
        
        .debug-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--calc-space-3);
            background: var(--calc-gray-800);
            border-bottom: 1px solid var(--calc-gray-700);
        }
        
        .debug-header h3 {
            margin: 0;
            font-size: 1rem;
        }
        
        .debug-header button {
            background: none;
            border: none;
            color: var(--calc-white);
            font-size: 1.2rem;
            cursor: pointer;
            padding: var(--calc-space-1);
        }
        
        .debug-content {
            max-height: 60vh;
            overflow-y: auto;
            padding: var(--calc-space-3);
        }
        
        .debug-section {
            margin-bottom: var(--calc-space-4);
        }
        
        .debug-section h4 {
            margin: 0 0 var(--calc-space-2);
            color: var(--calc-accent);
            font-size: 0.9rem;
        }
        
        .debug-section pre {
            background: var(--calc-gray-800);
            padding: var(--calc-space-2);
            border-radius: var(--calc-radius-sm);
            font-size: 0.8rem;
            overflow-x: auto;
            margin: 0;
            white-space: pre-wrap;
        }
        
        .floating-actions {
            position: fixed;
            bottom: var(--calc-space-6);
            right: var(--calc-space-6);
            display: flex;
            flex-direction: column;
            gap: var(--calc-space-2);
            z-index: var(--calc-z-tooltip);
        }
        
        .floating-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            background: var(--calc-primary);
            color: var(--calc-white);
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--calc-shadow-lg);
            transition: var(--calc-transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .floating-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--calc-shadow-xl);
        }
        
        .floating-btn.debug {
            background: var(--calc-warning);
        }
        
        @media (max-width: 768px) {
            .debug-panel {
                width: calc(100vw - 20px);
                max-width: 400px;
            }
            
            .floating-actions {
                bottom: var(--calc-space-4);
                right: var(--calc-space-4);
            }
        }
    </style>
    
    <script>
        // Debug helpers
        document.getElementById('btn-debug')?.addEventListener('click', function() {
            const panel = document.getElementById('debug-panel');
            
            if (panel.style.display === 'none') {
                // Mettre à jour les informations debug
                if (window.debugCalculateur) {
                    document.getElementById('debug-config').textContent = 
                        JSON.stringify(CalculateurConfig, null, 2);
                    document.getElementById('debug-state').textContent = 
                        JSON.stringify(window.debugCalculateur.summary(), null, 2);
                    document.getElementById('debug-stats').textContent = 
                        JSON.stringify(window.apiService?.getStats(), null, 2);
                }
                
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        });
        
        // Mise à jour périodique du debug
        if (<?= json_encode($debug_mode) ?>) {
            setInterval(() => {
                const panel = document.getElementById('debug-panel');
                if (panel && panel.style.display !== 'none' && window.debugCalculateur) {
                    document.getElementById('debug-state').textContent = 
                        JSON.stringify(window.debugCalculateur.summary(), null, 2);
                    document.getElementById('debug-stats').textContent = 
                        JSON.stringify(window.apiService?.getStats(), null, 2);
                }
            }, 2000);
        }
    </script>
    <?php endif; ?>

</body>
</html>

<?php
// =========================================================================
// SAUVEGARDE SESSION ET NETTOYAGE
// =========================================================================

// Sauvegarder les données en session pour persister entre rechargements
if (!empty($preset_data['departement'])) {
    $_SESSION['calc_dept'] = $preset_data['departement'];
}
if (!empty($preset_data['poids'])) {
    $_SESSION['calc_poids'] = $preset_data['poids'];
}
if (!empty($preset_data['type'])) {
    $_SESSION['calc_type'] = $preset_data['type'];
}

// Logs pour monitoring (en production, utiliser un vrai système de logs)
if ($debug_mode) {
    error_log("Calculateur - Page chargée: " . json_encode([
        'preset_data' => $preset_data,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'timestamp' => date('c')
    ]));
}
?>
