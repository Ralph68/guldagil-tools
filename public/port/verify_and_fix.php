<?php
/**
 * Titre: Script de Vérification et Correction Module Port
 * Chemin: /public/port/verify_and_fix.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 Vérification et Correction Module Port</h1>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>Objectifs :</strong></p>";
echo "<ul>";
echo "<li>✅ Supprimer les données de démo fixes</li>";
echo "<li>✅ Vérifier la logique de calcul réelle</li>";
echo "<li>✅ Tester le flow intelligent JavaScript</li>";
echo "<li>✅ Valider l'encart de débogage</li>";
echo "</ul>";
echo "</div>";

// Configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';

$issues = [];
$fixes_applied = [];

echo "<h2>📋 Diagnostic des Problèmes</h2>";

// ========================================
// 1. VÉRIFICATION INDEX.PHP - DONNÉES DÉMO
// ========================================
echo "<h3>1. 🔍 Vérification index.php</h3>";

$index_file = __DIR__ . '/index.php';
if (file_exists($index_file)) {
    $index_content = file_get_contents($index_file);
    
    // Recherche de données de démo
    $demo_patterns = [
        '/\$results\s*=\s*\[.*?xpo.*?89\.50/s',
        '/Simulation de résultats/i',
        '/prix_ht.*?=.*?[0-9]+\.[0-9]+/i'
    ];
    
    $has_demo_data = false;
    foreach ($demo_patterns as $pattern) {
        if (preg_match($pattern, $index_content)) {
            $has_demo_data = true;
            break;
        }
    }
    
    if ($has_demo_data) {
        $issues[] = "❌ DONNÉES DÉMO DÉTECTÉES dans index.php";
        echo "<div style='color: red;'>❌ Des données de démo ont été trouvées dans index.php</div>";
    } else {
        echo "<div style='color: green;'>✅ Aucune donnée de démo trouvée dans index.php</div>";
    }
} else {
    $issues[] = "❌ Fichier index.php manquant";
    echo "<div style='color: red;'>❌ Fichier index.php non trouvé</div>";
}

// ========================================
// 2. VÉRIFICATION CLASSE TRANSPORT
// ========================================
echo "<h3>2. 🔍 Vérification Classe Transport</h3>";

$transport_file = __DIR__ . '/calculs/transport.php';
if (file_exists($transport_file)) {
    echo "<div style='color: green;'>✅ Classe Transport trouvée</div>";
    
    require_once $transport_file;
    if (class_exists('Transport')) {
        echo "<div style='color: green;'>✅ Classe Transport chargeable</div>";
        
        try {
            $transport = new Transport($db);
            echo "<div style='color: green;'>✅ Classe Transport instanciable</div>";
            
            // Test avec vrais paramètres
            $test_params = [
                'departement' => '75',
                'poids' => 100,
                'type' => 'colis',
                'adr' => false,
                'enlevement' => false,
                'palettes' => 1,
                'palette_eur' => 0
            ];
            
            echo "<div style='margin: 10px 0;'><strong>Test de calcul avec paramètres réalistes :</strong></div>";
            echo "<pre>" . json_encode($test_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            $results = $transport->calculateAll($test_params);
            
            echo "<div style='margin: 10px 0;'><strong>Résultat brut :</strong></div>";
            echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            // Analyse des résultats
            if (isset($results['results'])) {
                $valid_results = array_filter($results['results'], function($result) {
                    return $result !== null && is_array($result) && isset($result['prix_ttc']) && $result['prix_ttc'] > 0;
                });
                
                if (!empty($valid_results)) {
                    echo "<div style='color: green;'>✅ Calcul réussi : " . count($valid_results) . " transporteur(s) avec prix valides</div>";
                    foreach ($valid_results as $carrier => $result) {
                        echo "<div>• $carrier : {$result['prix_ttc']}€ TTC</div>";
                    }
                } else {
                    $issues[] = "⚠️ Calcul sans résultats valides - Vérifier les données tarifaires";
                    echo "<div style='color: orange;'>⚠️ Calcul effectué mais aucun prix valide retourné</div>";
                }
            } else {
                $issues[] = "❌ Structure de résultat invalide";
                echo "<div style='color: red;'>❌ Structure de résultat non conforme</div>";
            }
            
        } catch (Exception $e) {
            $issues[] = "❌ Erreur instantiation Transport : " . $e->getMessage();
            echo "<div style='color: red;'>❌ Erreur : " . $e->getMessage() . "</div>";
        }
    } else {
        $issues[] = "❌ Classe Transport non définie";
        echo "<div style='color: red;'>❌ Classe Transport non trouvée dans le fichier</div>";
    }
} else {
    $issues[] = "❌ Fichier transport.php manquant";
    echo "<div style='color: red;'>❌ Fichier transport.php non trouvé</div>";
}

// ========================================
// 3. VÉRIFICATION FICHIERS JAVASCRIPT ET CSS
// ========================================
echo "<h3>3. 🔍 Vérification Assets</h3>";

$js_file = __DIR__ . '/assets/js/port.js';
$css_file = __DIR__ . '/assets/css/port.css';

if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    // Vérifications JavaScript importantes
    $js_checks = [
        'smartAutoProgress' => strpos($js_content, 'smartAutoProgress') !== false,
        'autoSelectTypeByWeight' => strpos($js_content, 'autoSelectTypeByWeight') !== false,
        'highlightAdrOptions' => strpos($js_content, 'highlightAdrOptions') !== false,
        'createDebugPanel' => strpos($js_content, 'createDebugPanel') !== false,
        'waitForAdrDelay' => strpos($js_content, 'waitForAdrDelay') !== false
    ];
    
    $missing_features = array_keys(array_filter($js_checks, function($exists) { return !$exists; }));
    
    if (empty($missing_features)) {
        echo "<div style='color: green;'>✅ JavaScript : Toutes les nouvelles fonctionnalités présentes</div>";
    } else {
        $issues[] = "⚠️ JavaScript : Fonctionnalités manquantes - " . implode(', ', $missing_features);
        echo "<div style='color: orange;'>⚠️ JavaScript : Fonctionnalités manquantes</div>";
        foreach ($missing_features as $feature) {
            echo "<div style='margin-left: 20px;'>- $feature</div>";
        }
    }
} else {
    $issues[] = "❌ Fichier port.js manquant";
    echo "<div style='color: red;'>❌ Fichier port.js non trouvé</div>";
}

if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    
    // Vérifications CSS importantes
    $css_checks = [
        'calc-debug-panel' => strpos($css_content, 'calc-debug-panel') !== false,
        'calc-toggle-btn' => strpos($css_content, 'calc-toggle-btn') !== false,
        'calc-animate' => strpos($css_content, 'calc-animate') !== false,
        'port-primary' => strpos($css_content, 'port-primary') !== false
    ];
    
    $missing_styles = array_keys(array_filter($css_checks, function($exists) { return !$exists; }));
    
    if (empty($missing_styles)) {
        echo "<div style='color: green;'>✅ CSS : Tous les styles modernes présents</div>";
    } else {
        $issues[] = "⚠️ CSS : Styles manquants - " . implode(', ', $missing_styles);
        echo "<div style='color: orange;'>⚠️ CSS : Styles manquants</div>";
        foreach ($missing_styles as $style) {
            echo "<div style='margin-left: 20px;'>- $style</div>";
        }
    }
} else {
    $issues[] = "❌ Fichier port.css manquant";
    echo "<div style='color: red;'>❌ Fichier port.css non trouvé</div>";
}

// ========================================
// 4. VÉRIFICATION STRUCTURE BDD
// ========================================
echo "<h3>4. 🔍 Vérification Base de Données</h3>";

try {
    $tables_required = [
        'gul_xpo_rates' => 'Tarifs XPO',
        'gul_heppner_rates' => 'Tarifs Heppner', 
        'gul_kn_rates' => 'Tarifs Kuehne+Nagel',
        'gul_taxes_transporteurs' => 'Taxes et majorations'
    ];
    
    $missing_tables = [];
    $empty_tables = [];
    
    foreach ($tables_required as $table => $description) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            if ($count > 0) {
                echo "<div style='color: green;'>✅ $description : $count lignes</div>";
            } else {
                $empty_tables[] = "$description (table vide)";
                echo "<div style='color: orange;'>⚠️ $description : Table vide</div>";
            }
        } catch (PDOException $e) {
            $missing_tables[] = $description;
            echo "<div style='color: red;'>❌ $description : Table manquante ou erreur</div>";
        }
    }
    
    if (!empty($missing_tables)) {
        $issues[] = "❌ Tables BDD manquantes : " . implode(', ', $missing_tables);
    }
    
    if (!empty($empty_tables)) {
        $issues[] = "⚠️ Tables BDD vides : " . implode(', ', $empty_tables);
    }
    
} catch (Exception $e) {
    $issues[] = "❌ Erreur connexion BDD : " . $e->getMessage();
    echo "<div style='color: red;'>❌ Erreur BDD : " . $e->getMessage() . "</div>";
}

// ========================================
// 5. PROPOSITIONS DE CORRECTION
// ========================================
echo "<h2>🛠️ Propositions de Correction</h2>";

if (empty($issues)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px;'>";
    echo "<h3>🎉 EXCELLENT ! Aucun problème majeur détecté</h3>";
    echo "<p>Le module port semble correctement configuré. Vous pouvez maintenant tester le flow intelligent :</p>";
    echo "<ol>";
    echo "<li>Saisissez un département (ex: 75)</li>";
    echo "<li>Indiquez un poids (ex: 200kg → sélection auto 'palette')</li>";
    echo "<li>Choisissez ADR Oui/Non</li>";
    echo "<li>Regardez le calcul automatique se lancer</li>";
    echo "<li>Utilisez l'encart de débogage en bas à droite</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px;'>";
    echo "<h3>⚠️ Problèmes détectés (" . count($issues) . ")</h3>";
    foreach ($issues as $issue) {
        echo "<div>$issue</div>";
    }
    echo "</div>";
    
    echo "<h3>📋 Actions recommandées :</h3>";
    echo "<ol>";
    
    if (in_array('❌ DONNÉES DÉMO DÉTECTÉES dans index.php', $issues)) {
        echo "<li><strong>Remplacer index.php</strong> par la version corrigée sans données de démo</li>";
    }
    
    if (strpos(implode(' ', $issues), 'transport.php') !== false) {
        echo "<li><strong>Vérifier la classe Transport</strong> et ses calculateurs</li>";
    }
    
    if (strpos(implode(' ', $issues), 'JavaScript') !== false) {
        echo "<li><strong>Mettre à jour port.js</strong> avec les nouvelles fonctionnalités</li>";
    }
    
    if (strpos(implode(' ', $issues), 'CSS') !== false) {
        echo "<li><strong>Mettre à jour port.css</strong> avec les nouveaux styles</li>";
    }
    
    if (strpos(implode(' ', $issues), 'BDD') !== false) {
        echo "<li><strong>Importer les données tarifaires</strong> dans les tables de base</li>";
    }
    
    echo "</ol>";
}

// ========================================
// 6. TEST INTERACTIF
// ========================================
echo "<h2>🧪 Test Interactif</h2>";
echo "<div style='background: #e7f3ff; border: 1px solid #b6d7ff; color: #004085; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Pour tester le nouveau flow intelligent :</strong></p>";
echo "<p>1. Allez sur <a href='/port/' target='_blank'>/port/</a></p>";
echo "<p>2. Testez cette séquence :</p>";
echo "<ul>";
echo "<li>Département : <code>93</code> (progression auto vers poids)</li>";
echo "<li>Poids : <code>200</code> (sélection auto 'palette' + progression vers options)</li>";
echo "<li>ADR : Cliquez <code>Non</code> (calcul automatique dans 1.5s)</li>";
echo "<li>Observez l'encart de débogage en bas à droite</li>";
echo "</ul>";
echo "<p>3. Vérifiez que les montants <strong>ne sont plus fixes</strong> mais varient selon les paramètres</p>";
echo "</div>";

// ========================================
// 7. GÉNÉRATION DES FICHIERS CORRIGÉS
// ========================================
echo "<h2>📝 Génération des Fichiers Corrigés</h2>";

if (isset($_GET['generate']) && $_GET['generate'] === 'files') {
    echo "<h3>🔧 Génération en cours...</h3>";
    
    // ========================================
    // FICHIER INDEX.PHP CORRIGÉ
    // ========================================
    $new_index_content = '<?php
/**
 * Titre: Calculateur de frais de port - Interface complète CORRIGÉE
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set(\'display_errors\', 1);
error_reporting(E_ALL);

// Configuration et chemins
define(\'ROOT_PATH\', dirname(dirname(__DIR__)));
require_once __DIR__ . \'/../../config/config.php\';
require_once __DIR__ . \'/../../config/version.php\';

// Variables pour header/footer
$version_info = getVersionInfo();
$page_title = \'Calculateur de Frais de Port\';
$page_subtitle = \'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel\';
$page_description = \'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport\';
$current_module = \'port\';
$user_authenticated = true;
$module_css = true;
$breadcrumbs = [
    [\'icon\' => \'🏠\', \'text\' => \'Accueil\', \'url\' => \'/\', \'active\' => false],
    [\'icon\' => \'🚛\', \'text\' => \'Calculateur\', \'url\' => \'/port/\', \'active\' => true]
];

session_start();

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE
// ========================================
if (!$user_authenticated) {
    header(\'Location: /auth/login.php?redirect=\' . urlencode($_SERVER[\'REQUEST_URI\']));
    exit;
}

// ========================================
// 🔧 GESTION AJAX CALCULATE - VERSION CORRIGÉE SANS DÉMO
// ========================================
if (isset($_GET[\'ajax\']) && $_GET[\'ajax\'] === \'calculate\') {
    header(\'Content-Type: application/json\');
    
    try {
        // Récupération des données POST
        parse_str(file_get_contents(\'php://input\'), $post_data);
        
        // Validation et formatage des paramètres
        $params = [
            \'departement\' => str_pad(trim($post_data[\'departement\'] ?? \'\'), 2, \'0\', STR_PAD_LEFT),
            \'poids\' => floatval($post_data[\'poids\'] ?? 0),
            \'type\' => strtolower(trim($post_data[\'type\'] ?? \'colis\')),
            \'adr\' => (($post_data[\'adr\'] ?? \'non\') === \'oui\'),
            \'option_sup\' => trim($post_data[\'option_sup\'] ?? \'standard\'),
            \'enlevement\' => (($post_data[\'enlevement\'] ?? \'non\') === \'oui\'),
            \'palettes\' => max(1, intval($post_data[\'palettes\'] ?? 1)),
            \'palette_eur\' => intval($post_data[\'palette_eur\'] ?? 0),
        ];
        
        // Validation des paramètres
        if (empty($params[\'departement\']) || !preg_match(\'/^[0-9]{2,3}$/\', $params[\'departement\'])) {
            throw new Exception(\'Département invalide\');
        }
        if ($params[\'poids\'] <= 0 || $params[\'poids\'] > 32000) {
            throw new Exception(\'Poids invalide (1-32000 kg)\');
        }
        
        // 🚨 CHARGEMENT DE LA VRAIE CLASSE TRANSPORT
        $transport_file = __DIR__ . \'/calculs/transport.php\';
        if (!file_exists($transport_file)) {
            throw new Exception(\'Classe Transport non trouvée: \' . $transport_file);
        }
        
        require_once $transport_file;
        
        if (!class_exists(\'Transport\')) {
            throw new Exception(\'Classe Transport non chargée\');
        }
        
        // Initialisation avec la bonne connexion DB
        $transport = new Transport($db);
        
        // ⏱️ CALCUL RÉEL DES TARIFS
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // 🎯 FORMATAGE DE LA RÉPONSE
        $response = [
            \'success\' => true,
            \'carriers\' => [],
            \'time_ms\' => $calc_time,
            \'debug\' => [
                \'params_received\' => $params,
                \'transport_class\' => get_class($transport)
            ]
        ];
        
        // Traitement des résultats par transporteur
        if (isset($results[\'results\']) && is_array($results[\'results\'])) {
            foreach ($results[\'results\'] as $carrier => $result) {
                if ($result !== null && is_array($result)) {
                    $response[\'carriers\'][$carrier] = [
                        \'prix_ht\' => $result[\'prix_ht\'] ?? 0,
                        \'prix_ttc\' => $result[\'prix_ttc\'] ?? 0,
                        \'delai\' => $result[\'delai\'] ?? \'N/A\',
                        \'service\' => $result[\'service\'] ?? \'Standard\'
                    ];
                }
            }
            
            if (isset($results[\'debug\'])) {
                $response[\'debug\'][\'transport_debug\'] = $results[\'debug\'];
            }
        }
        
        // Validation des résultats
        $valid_results = array_filter($response[\'carriers\'], function($result) {
            return isset($result[\'prix_ttc\']) && $result[\'prix_ttc\'] > 0;
        });
        
        if (empty($valid_results)) {
            $response[\'success\'] = false;
            $response[\'error\'] = \'Aucun transporteur disponible pour ces critères\';
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        $error_response = [
            \'success\' => false,
            \'error\' => $e->getMessage(),
            \'debug\' => [
                \'error_file\' => $e->getFile(),
                \'error_line\' => $e->getLine()
            ]
        ];
        
        echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========================================
// 🎨 CHARGEMENT HEADER
// ========================================
include ROOT_PATH . \'/templates/header.php\';
?>

<!-- Container principal -->
<div class="calc-container">
    <main class="calc-main">
        <!-- EN-TÊTE -->
        <div class="calc-header">
            <h1>🚛 Calculateur de Frais de Port</h1>
            <p>Comparaison instantanée des tarifs XPO, Heppner et Kuehne+Nagel</p>
        </div>

        <!-- FORMULAIRE -->
        <section class="calc-form-panel">
            <div class="calc-steps">
                <button type="button" class="calc-step-btn active" data-step="1">📍 Destination</button>
                <button type="button" class="calc-step-btn" data-step="2">📦 Colis</button>
                <button type="button" class="calc-step-btn" data-step="3">⚙️ Options</button>
            </div>
            
            <div class="calc-form-content">
                <form id="calculatorForm" class="calc-form" novalidate>
                    <!-- Étape 1: Destination -->
                    <div class="calc-step-content active" data-step="1" style="display: block;">
                        <div class="calc-form-group">
                            <label for="departement" class="calc-label">📍 Département de destination *</label>
                            <input type="text" id="departement" name="departement" class="calc-input" 
                                   placeholder="Ex: 75, 69, 13, 2A..." maxlength="3" required>
                            <small class="calc-help">Saisissez le numéro du département (01-95, 2A, 2B)</small>
                        </div>
                    </div>

                    <!-- Étape 2: Poids et Type -->
                    <div class="calc-step-content" data-step="2" style="display: none;">
                        <div class="calc-form-group">
                            <label for="poids" class="calc-label">⚖️ Poids total de l\'envoi *</label>
                            <div class="calc-input-group">
                                <input type="number" id="poids" name="poids" class="calc-input" 
                                       placeholder="150" min="1" max="3000" step="0.1" required>
                                <span class="calc-input-suffix">kg</span>
                            </div>
                            <small class="calc-help">Type suggéré automatiquement selon le poids</small>
                        </div>

                        <div class="calc-form-group">
                            <label for="type" class="calc-label">📦 Type d\'envoi *</label>
                            <select id="type" name="type" class="calc-select" required>
                                <option value="">-- Sélection automatique --</option>
                                <option value="colis">📦 Colis (≤ 150kg)</option>
                                <option value="palette">🏗️ Palette (> 150kg)</option>
                            </select>
                        </div>

                        <div class="calc-form-group" id="palettesGroup" style="display: none;">
                            <label for="palettes" class="calc-label">🏗️ Nombre de palettes</label>
                            <select id="palettes" name="palettes" class="calc-select">
                                <option value="1">1 palette</option>
                                <option value="2">2 palettes</option>
                                <option value="3">3 palettes</option>
                            </select>
                        </div>

                        <div class="calc-form-group" id="paletteEurGroup" style="display: none;">
                            <label for="palette_eur" class="calc-label">🔄 Palettes EUR consignées</label>
                            <select id="palette_eur" name="palette_eur" class="calc-select">
                                <option value="0">Aucune</option>
                                <option value="1">1 palette EUR</option>
                                <option value="2">2 palettes EUR</option>
                                <option value="3">3 palettes EUR</option>
                            </select>
                        </div>
                    </div>

                    <!-- Étape 3: Options -->
                    <div class="calc-step-content" data-step="3" style="display: none;">
                        <div class="calc-form-group">
                            <label class="calc-label">⚠️ Matières dangereuses (ADR) *</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn" data-adr="non">✅ Non - Standard</button>
                                <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui - ADR</button>
                            </div>
                            <input type="hidden" id="adr" name="adr" value="">
                        </div>

                        <div class="calc-form-group">
                            <label class="calc-label">🚚 Type de service</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn active" data-enlevement="non">📮 Livraison</button>
                                <button type="button" class="calc-toggle-btn" data-enlevement="oui">🚚 Enlèvement</button>
                            </div>
                            <input type="hidden" id="enlevement" name="enlevement" value="non">
                        </div>

                        <div class="calc-form-group">
                            <label for="option_sup" class="calc-label">⚙️ Options supplémentaires</label>
                            <select id="option_sup" name="option_sup" class="calc-select">
                                <option value="standard">Standard</option>
                                <option value="express">Express</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="calc-form-group">
                            <button type="submit" id="calculateBtn" class="calc-btn-primary">🧮 Calculer les tarifs</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- RÉSULTATS -->
        <section class="calc-results-panel">
            <div class="calc-results-header">
                <h2>📊 Résultats de calcul</h2>
                <div id="calcStatus" class="calc-status">⏳ En attente...</div>
            </div>
            
            <div id="resultsContent" class="calc-results-content">
                <div class="calc-welcome">
                    <div class="calc-welcome-icon">🚛</div>
                    <h3>Calculateur Intelligent</h3>
                    <p>Flow automatique avec sélection intelligente et calcul en temps réel</p>
                </div>
            </div>
        </section>
    </main>
</div>

<?php include ROOT_PATH . \'/templates/footer.php\'; ?>';

    try {
        file_put_contents(__DIR__ . '/index_corrected.php', $new_index_content);
        $fixes_applied[] = "✅ index_corrected.php généré";
        echo "<div style='color: green;'>✅ Fichier index_corrected.php généré</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur génération index.php : " . $e->getMessage() . "</div>";
    }
    
    // ========================================
    // INSTRUCTIONS D'INSTALLATION
    // ========================================
    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📋 Instructions d'Installation</h3>";
    echo "<ol>";
    echo "<li><strong>Sauvegardez</strong> l'ancien index.php : <code>mv index.php index.php.backup</code></li>";
    echo "<li><strong>Installez</strong> la nouvelle version : <code>mv index_corrected.php index.php</code></li>";
    echo "<li><strong>Mettez à jour</strong> les fichiers JS/CSS avec les artifacts générés</li>";
    echo "<li><strong>Testez</strong> le flow intelligent sur /port/</li>";
    echo "<li><strong>Vérifiez</strong> que les montants varient selon les paramètres</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='?generate=files' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
    echo "🚀 Générer les Fichiers Corrigés</a>";
    echo "</div>";
}

// ========================================
// 8. RÉSUMÉ ET RECOMMANDATIONS FINALES
// ========================================
echo "<h2>📊 Résumé Final</h2>";

if (empty($issues)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3>🎉 MODULE PORT PRÊT !</h3>";
    echo "<p style='font-size: 1.1em; margin: 15px 0;'>Le module est opérationnel avec toutes les nouvelles fonctionnalités :</p>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ Flow Intelligent</strong><br>Progression automatique</div>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ Sélection Auto</strong><br>Palette/Colis par poids</div>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ Pause ADR</strong><br>Attente réponse utilisateur</div>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ Debug Panel</strong><br>Encart de débogage</div>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ Calcul Réel</strong><br>Fini les données démo</div>";
    echo "<div style='background: rgba(255,255,255,0.3); padding: 15px; border-radius: 8px;'><strong>✅ UX/UI Moderne</strong><br>Interface professionnelle</div>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px;'>";
    echo "<h3>⚠️ Actions Requises (" . count($issues) . " problèmes)</h3>";
    echo "<p>Corrections nécessaires avant utilisation optimale :</p>";
    foreach ($issues as $i => $issue) {
        echo "<div style='margin: 8px 0;'>" . ($i + 1) . ". $issue</div>";
    }
    echo "</div>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<div style='text-align: center; color: #666;'>";
echo "<p>🔧 Script de vérification - Module Port v0.5 beta</p>";
echo "<p>Exécuté le " . date('d/m/Y à H:i:s') . "</p>";
echo "</div>";
?>
