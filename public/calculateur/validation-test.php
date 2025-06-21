<?php
/**
 * Titre: Interface de validation calculateur mise à jour
 * Chemin: /public/calculateur/validation-test.php
 * Version: 0.5 beta - Compatible nouvelle architecture JS
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$results = null;
$debug_info = [];
$validation_errors = [];
$calculation_time = 0;

// FONCTIONS - DÉCLARÉES UNE SEULE FOIS
function validateCalculatorData($data) {
    $errors = [];
    
    if (empty($data['departement']) || !preg_match('/^\d{2}$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide (01-95)';
    } else {
        $dept_num = (int)$data['departement'];
        if ($dept_num < 1 || $dept_num > 95) {
            $errors['departement'] = 'Département hors limites (01-95)';
        }
    }
    
    if (empty($data['poids']) || $data['poids'] < 0.1) {
        $errors['poids'] = 'Poids minimum: 0.1kg';
    }
    if ($data['poids'] > 3500) {
        $errors['poids'] = 'Poids maximum: 3500kg';
    }
    
    if (!in_array($data['type'], ['colis', 'palette'])) {
        $errors['type'] = 'Type d\'envoi invalide';
    }
    
    if (!in_array($data['adr'], ['oui', 'non'])) {
        $errors['adr'] = 'Option ADR invalide';
    }
    
    return $errors;
}

function formatResults($results) {
    if (!$results) return null;
    
    $formatted = [
        'success' => true,
        'carriers' => [],
        'best_rate' => null
    ];
    
    $carrier_names = [
        'xpo' => 'XPO Logistics',
        'heppner' => 'Heppner',
        'kn' => 'Kuehne + Nagel'
    ];
    
    $valid_results = [];
    $carrier_results = $results['results'] ?? $results ?? [];
    
    foreach ($carrier_results as $carrier => $price) {
        $name = $carrier_names[$carrier] ?? strtoupper($carrier);
        
        if ($price !== null && $price > 0) {
            $valid_results[$carrier] = $price;
            $formatted['carriers'][$carrier] = [
                'name' => $name,
                'price' => $price,
                'formatted' => number_format($price, 2, ',', ' ') . ' €'
            ];
        } else {
            $formatted['carriers'][$carrier] = [
                'name' => $name,
                'price' => null,
                'formatted' => 'Non disponible'
            ];
        }
    }
    
    if (!empty($valid_results)) {
        $best_carrier = array_keys($valid_results, min($valid_results))[0];
        $formatted['best_rate'] = [
            'carrier' => $best_carrier,
            'carrier_name' => $carrier_names[$best_carrier] ?? strtoupper($best_carrier),
            'price' => $valid_results[$best_carrier],
            'formatted' => number_format($valid_results[$best_carrier], 2, ',', ' ') . ' €'
        ];
    }
    
    return $formatted;
}

// TRAITEMENT DU FORMULAIRE
if ($_POST) {
    $adr = (isset($_POST['adr']) && $_POST['adr'] === 'oui');
    $start_time = microtime(true);
    
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? 'oui' : 'non',
        'service_livraison' => trim($_POST['service_livraison'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']) && $_POST['enlevement'],
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];
    
    $validation_errors = validateCalculatorData($params);
    
    if (empty($validation_errors)) {
        try {
            $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            
            if (file_exists($transport_file)) {
                require_once $transport_file;
                $transport = new Transport($db);
                
                if (method_exists($transport, 'calculateAll')) {
                    try {
                        $results = $transport->calculateAll($params);
                        $debug_info['signature'] = 'array';
                    } catch (Exception $e) {
                        $results = $transport->calculateAll(
                            $params['type'],
                            $params['adr'], 
                            $params['poids'],
                            $params['service_livraison'],
                            $params['departement'],
                            $params['palettes'],
                            $params['enlevement']
                        );
                        $debug_info['signature'] = 'separated_params';
                    }
                }
                
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
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Calculateur - Compatible JS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: white; }
        input, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 200px; }
        input.error { border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1); }
        input.valid { border-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1); }
        
        button { padding: 12px 24px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #047857; }
        
        .field-error { color: #ef4444; font-size: 12px; margin-top: 4px; }
        .field-error::before { content: "⚠️ "; }
        
        .best-rate { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; border-radius: 12px; padding: 24px; margin: 20px 0; text-align: center; }
        .price { font-size: 32px; font-weight: 700; margin: 8px 0; }
        .carrier-name { font-size: 16px; font-weight: 500; }
        
        .carriers-list { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        .carrier-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; }
        .carrier-item.best { border-color: #10b981; background: rgba(16, 185, 129, 0.05); }
        .best-badge { background: #10b981; color: white; font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: 500; margin-left: 8px; }
        
        .debug { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .debug h4 { margin-top: 0; color: #475569; }
        .debug pre { background: white; padding: 10px; border-radius: 4px; overflow: auto; font-size: 12px; }
        
        .error { color: #dc2626; background: #fef2f2; padding: 10px; border: 1px solid #fecaca; border-radius: 6px; }
        .success { color: #16a34a; background: #f0fdf4; padding: 10px; border: 1px solid #bbf7d0; border-radius: 6px; }
        .warning { color: #d97706; background: #fffbeb; padding: 10px; border: 1px solid #fed7aa; border-radius: 6px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-item { background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #1e40af; }
        .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Validation Calculateur - Compatible Architecture JS</h1>
        
        <!-- Formulaire -->
        <div class="section form-section">
            <h2>📝 Paramètres de test</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="departement">Département:</label>
                        <input type="text" id="departement" name="departement" 
                               value="<?= htmlspecialchars($_POST['departement'] ?? '67') ?>" 
                               maxlength="2" required>
                        <?php if (isset($validation_errors['departement'])): ?>
                            <div class="field-error"><?= htmlspecialchars($validation_errors['departement']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="poids">Poids (kg):</label>
                        <input type="number" id="poids" name="poids" 
                               value="<?= htmlspecialchars($_POST['poids'] ?? '25') ?>" 
                               step="0.1" min="0.1" max="3500" required>
                        <?php if (isset($validation_errors['poids'])): ?>
                            <div class="field-error"><?= htmlspecialchars($validation_errors['poids']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type d'envoi:</label>
                        <select id="type" name="type">
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
                        <label for="service_livraison">Service:</label>
                        <select id="service_livraison" name="service_livraison">
                            <option value="standard" <?= ($_POST['service_livraison'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="rdv" <?= ($_POST['service_livraison'] ?? '') === 'rdv' ? 'selected' : '' ?>>Prise de RDV</option>
                            <option value="premium13" <?= ($_POST['service_livraison'] ?? '') === 'premium13' ? 'selected' : '' ?>>Premium 13h</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="palettes">Palettes:</label>
                        <input type="number" id="palettes" name="palettes" 
                               value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>" min="0" max="20">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="enlevement" <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                        Enlèvement à domicile
                    </label>
                </div>
                
                <button type="submit" style="margin-top: 20px;">🚀 Tester les calculs</button>
            </form>
        </div>

        <?php if ($_POST): ?>
        <!-- Statistiques -->
        <div class="section">
            <h2>📊 Statistiques</h2>
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
                    <div class="stat-value"><?= $debug_info['signature'] ?? 'N/A' ?></div>
                    <div class="stat-label">Signature utilisée</div>
                </div>
            </div>
        </div>

        <?php if (!empty($validation_errors)): ?>
        <!-- Erreurs -->
        <div class="section">
            <h2>❌ Erreurs de validation</h2>
            <?php foreach ($validation_errors as $field => $error): ?>
                <div class="error">
                    <strong><?= htmlspecialchars($field) ?>:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($results && empty($validation_errors)): ?>
        <!-- Résultats -->
        <div class="section">
            <h2>📊 Résultats de calcul</h2>
            
            <?php $formatted = formatResults($results); ?>
            
            <?php if ($formatted && $formatted['best_rate']): ?>
            <div class="best-rate">
                <h3>🏆 Meilleur tarif</h3>
                <div class="carrier-name"><?= htmlspecialchars($formatted['best_rate']['carrier_name']) ?></div>
                <div class="price"><?= htmlspecialchars($formatted['best_rate']['formatted']) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($formatted && $formatted['carriers']): ?>
            <h3>🚛 Comparaison des transporteurs</h3>
            <div class="carriers-list">
                <?php foreach ($formatted['carriers'] as $carrier => $data): ?>
                <?php $is_best = $formatted['best_rate'] && $formatted['best_rate']['carrier'] === $carrier; ?>
                <div class="carrier-item <?= $is_best ? 'best' : '' ?>">
                    <div style="display: flex; align-items: center;">
                        <span><?= htmlspecialchars($data['name']) ?></span>
                        <?php if ($is_best): ?>
                            <span class="best-badge">Meilleur</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight: 600; color: #1e40af;">
                        <?= htmlspecialchars($data['formatted']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Debug -->
        <div class="section">
            <h2>🔍 Debug</h2>
            <div class="debug">
                <h4>Résultats bruts</h4>
                <pre><?= htmlspecialchars(print_r($results, true)) ?></pre>
            </div>
            
            <?php if (!empty($debug_info['transport_debug'])): ?>
            <div class="debug">
                <h4>Debug Transport</h4>
                <pre><?= htmlspecialchars(print_r($debug_info['transport_debug'], true)) ?></pre>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
    </div>
</body>
</html>
