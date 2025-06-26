<?php
/**
 * public/calculateur/index.php
 * Interface calculateur - Architecture MVC respectée
 * Version: 0.5 beta + build - CORRIGÉ
 */

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Informations de version
$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';

// Session et authentification (développement)
session_start();
$user_authenticated = true; // Simplifié pour développement

// Fonction de validation (reprise du fichier validation-test.php)
function validateCalculatorData($data) {
    $errors = [];
    
    // Département obligatoire et valide
    if (empty($data['departement'])) {
        $errors['departement'] = 'Département requis';
    } elseif (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide (01-95, 971-976)';
    }
    
    // Poids obligatoire et dans les limites
    if (empty($data['poids'])) {
        $errors['poids'] = 'Poids requis';
    } elseif (!is_numeric($data['poids']) || $data['poids'] <= 0) {
        $errors['poids'] = 'Poids doit être supérieur à 0';
    } elseif ($data['poids'] > 32000) {
        $errors['poids'] = 'Poids maximum: 32000 kg';
    }
    
    // Type obligatoire
    if (empty($data['type'])) {
        $errors['type'] = 'Type d\'envoi requis';
    } elseif (!in_array($data['type'], ['colis', 'palette'])) {
        $errors['type'] = 'Type d\'envoi invalide';
    }
    
    // Palettes pour type palette
    if ($data['type'] === 'palette' && ($data['palettes'] <= 0 || $data['palettes'] > 20)) {
        $errors['palettes'] = 'Nombre de palettes requis (1-20)';
    }
    
    return $errors;
}

// Traitement du formulaire
$results = null;
$validation_errors = [];
$calculation_time = 0;
$debug_info = [];

if ($_POST) {
    $start_time = microtime(true);
    
    // Préparer les paramètres
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? true : false,
        'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];

    // Validation
    $validation_errors = validateCalculatorData($params);

    if (empty($validation_errors)) {
        try {
            // Charger la classe Transport
            $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            
            if (file_exists($transport_file)) {
                require_once $transport_file;
                $transport = new Transport($db);
                
                // Calculer avec la nouvelle signature
                $results = $transport->calculateAll($params);
                $debug_info['signature'] = 'array';
                $debug_info['transport_debug'] = $transport->debug ?? [];
                
            } else {
                throw new Exception("Fichier Transport non trouvé: $transport_file");
            }
            
            $calculation_time = round((microtime(true) - $start_time) * 1000, 2);
            
        } catch (Exception $e) {
            $validation_errors['system'] = $e->getMessage();
            $debug_info['exception'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    }
}

// Fonction pour formater les résultats
function formatResults($results) {
    if (!$results || !isset($results['results'])) {
        return [];
    }
    
    $formatted = [];
    $valid_results = array_filter($results['results'], fn($price) => $price !== null);
    
    foreach ($valid_results as $carrier => $price) {
        $formatted[$carrier] = [
            'carrier' => strtoupper($carrier),
            'price' => $price,
            'formatted' => number_format($price, 2, ',', ' ') . ' €'
        ];
    }
    
    // Ajouter le meilleur tarif
    if (!empty($valid_results)) {
        $best_carrier = array_keys($valid_results, min($valid_results))[0];
        $formatted['best'] = [
            'carrier' => strtoupper($best_carrier),
            'price' => $valid_results[$best_carrier],
            'formatted' => number_format($valid_results[$best_carrier], 2, ',', ' ') . ' €'
        ];
    }
    
    return $formatted;
}

$formatted_results = formatResults($results);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- CSS modulaires corrigés -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/modern-interface.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/ux-improvements.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
    
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: white; }
        input, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
        input.error { border-color: #ef4444; }
        .field-error { color: #fecaca; font-size: 0.9em; margin-top: 5px; }
        button { background: #059669; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #047857; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .result-card { background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .result-card.best { border-left-color: #059669; background: #ecfdf5; }
        .result-price { font-size: 1.5em; font-weight: bold; color: #1e40af; }
        .result-card.best .result-price { color: #059669; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-top: 15px; }
        .stat-item { text-align: center; padding: 10px; background: #e5e7eb; border-radius: 8px; }
        .stat-value { font-size: 1.2em; font-weight: bold; color: #1e40af; }
        .stat-label { font-size: 0.9em; color: #6b7280; margin-top: 5px; }
        .debug-section { background: #1f2937; color: #f9fafb; }
        .debug-content { background: #111827; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; }
    </style>
</head>
<body class="calculateur-app">
    
    <!-- Header modulaire -->
    <header class="app-header">
        <div class="container">
            <h1>🧮 <?= htmlspecialchars($page_title) ?></h1>
            <div class="version-info">Version <?= $version_info['version'] ?> - Build <?= $version_info['build'] ?></div>
        </div>
    </header>

    <div class="container">
        
        <!-- Section formulaire -->
        <div class="section form-section">
            <h2>📦 Paramètres de calcul</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="departement">Département:</label>
                        <input type="text" id="departement" name="departement" 
                               class="<?= isset($validation_errors['departement']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($_POST['departement'] ?? '67') ?>" 
                               placeholder="67" maxlength="3" required>
                        <?php if (isset($validation_errors['departement'])): ?>
                            <div class="field-error"><?= htmlspecialchars($validation_errors['departement']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="poids">Poids (kg):</label>
                        <input type="number" id="poids" name="poids" 
                               class="<?= isset($validation_errors['poids']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($_POST['poids'] ?? '25') ?>" 
                               step="0.1" min="0.1" max="32000" required>
                        <?php if (isset($validation_errors['poids'])): ?>
                            <div class="field-error"><?= htmlspecialchars($validation_errors['poids']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type d'envoi:</label>
                        <select id="type" name="type" required>
                            <option value="colis" <?= ($_POST['type'] ?? 'colis') === 'colis' ? 'selected' : '' ?>>Colis</option>
                            <option value="palette" <?= ($_POST['type'] ?? '') === 'palette' ? 'selected' : '' ?>>Palette</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="adr">ADR:</label>
                        <select id="adr" name="adr">
                            <option value="non" <?= ($_POST['adr'] ?? 'non') === 'non' ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= ($_POST['adr'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_sup">Service:</label>
                        <select id="option_sup" name="option_sup">
                            <option value="standard" <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="rdv" <?= ($_POST['option_sup'] ?? '') === 'rdv' ? 'selected' : '' ?>>Prise de RDV</option>
                            <option value="premium13" <?= ($_POST['option_sup'] ?? '') === 'premium13' ? 'selected' : '' ?>>Premium 13h</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="palettes">Palettes:</label>
                        <input type="number" id="palettes" name="palettes" 
                               value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>" 
                               min="0" max="20">
                        <?php if (isset($validation_errors['palettes'])): ?>
                            <div class="field-error"><?= htmlspecialchars($validation_errors['palettes']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="enlevement" <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                        Enlèvement à domicile
                    </label>
                </div>
                
                <button type="submit" style="margin-top: 20px;">🚀 Calculer les tarifs</button>
            </form>
        </div>

        <?php if ($_POST): ?>
        
        <!-- Section erreurs -->
        <?php if (!empty($validation_errors)): ?>
        <div class="section" style="border-left: 4px solid #ef4444;">
            <h2>❌ Erreurs de validation</h2>
            <ul>
                <?php foreach ($validation_errors as $field => $error): ?>
                    <li><strong><?= ucfirst($field) ?>:</strong> <?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Section résultats -->
        <?php if (!empty($formatted_results) && empty($validation_errors)): ?>
        <div class="section">
            <h2>📊 Résultats de calcul</h2>
            
            <?php if (isset($formatted_results['best'])): ?>
            <div class="result-card best">
                <h3>🏆 Meilleur tarif</h3>
                <div class="result-price"><?= $formatted_results['best']['formatted'] ?></div>
                <div>Transporteur: <?= $formatted_results['best']['carrier'] ?></div>
            </div>
            <?php endif; ?>
            
            <div class="results-grid">
                <?php foreach ($formatted_results as $carrier => $data): ?>
                    <?php if ($carrier !== 'best'): ?>
                    <div class="result-card">
                        <h4><?= $data['carrier'] ?></h4>
                        <div class="result-price"><?= $data['formatted'] ?></div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section statistiques -->
        <?php if ($_POST): ?>
        <div class="section">
            <h2>📈 Statistiques</h2>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($validation_errors) ?></div>
                    <div class="stat-label">Erreurs validation</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $calculation_time ?> ms</div>
                    <div class="stat-label">Temps calcul</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count($formatted_results) - (isset($formatted_results['best']) ? 1 : 0) ?></div>
                    <div class="stat-label">Transporteurs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $debug_info['signature'] ?? 'N/A' ?></div>
                    <div class="stat-label">Signature API</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section debug (développement) -->
        <?php if ($_POST && (defined('DEBUG') && DEBUG)): ?>
        <div class="section debug-section">
            <h2>🔧 Informations de debug</h2>
            <div class="debug-content"><?= htmlspecialchars(json_encode([
                'POST' => $_POST,
                'params' => $params ?? [],
                'results' => $results,
                'debug_info' => $debug_info
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer style="text-align: center; margin-top: 40px; padding: 20px; background: #1f2937; color: white;">
        <p>&copy; <?= date('Y') ?> Guldagil - Version <?= $version_info['version'] ?> (<?= $version_info['build'] ?>)</p>
        <p>Horodatage: <?= $version_info['timestamp'] ?></p>
    </footer>

    <!-- Scripts -->
    <script>
        // Auto-focus sur le premier champ avec erreur
        document.addEventListener('DOMContentLoaded', function() {
            const errorField = document.querySelector('input.error');
            if (errorField) {
                errorField.focus();
                errorField.select();
            }
        });
        
        // Soumission automatique en mode développement (optionnel)
        <?php if (defined('DEBUG') && DEBUG): ?>
        setTimeout(function() {
            if (document.querySelector('form') && !document.querySelector('.results-grid')) {
                // Auto-submit pour les tests seulement si pas encore de résultats
                // document.querySelector('form').submit();
            }
        }, 1000);
        <?php endif; ?>
    </script>

</body>
</html>
