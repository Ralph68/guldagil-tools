<?php
/**
 * public/calculateur/index.php
 * Interface calculateur - Architecture MVC respectée
 * Version: 0.5 beta + build
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

// AJOUT: Logique de calcul (reprise de validation-test.php)
$results = null;
$validation_errors = [];
$calculation_time = 0;
$debug_info = [];

function validateCalculatorData($data) {
    $errors = [];
    
    if (empty($data['departement'])) {
        $errors['departement'] = 'Département requis';
    } elseif (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide';
    }
    
    if (empty($data['poids'])) {
        $errors['poids'] = 'Poids requis';
    } elseif (!is_numeric($data['poids']) || $data['poids'] <= 0) {
        $errors['poids'] = 'Poids doit être supérieur à 0';
    } elseif ($data['poids'] > 32000) {
        $errors['poids'] = 'Poids maximum: 32000 kg';
    }
    
    if (empty($data['type'])) {
        $errors['type'] = 'Type d\'envoi requis';
    } elseif (!in_array($data['type'], ['colis', 'palette'])) {
        $errors['type'] = 'Type d\'envoi invalide';
    }
    
    if ($data['type'] === 'palette' && ($data['palettes'] <= 0 || $data['palettes'] > 20)) {
        $errors['palettes'] = 'Nombre de palettes requis (1-20)';
    }
    
    return $errors;
}

if ($_POST) {
    $start_time = microtime(true);
    
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? true : false,
        'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];

    $validation_errors = validateCalculatorData($params);

    if (empty($validation_errors)) {
        try {
            $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            
            if (file_exists($transport_file)) {
                require_once $transport_file;
                $transport = new Transport($db);
                
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- CSS modulaires séparés - CORRIGÉ -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/modern-interface.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/ux-improvements.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
</head>
<body class="calculateur-app">
    
    <!-- Header modulaire - CORRIGÉ -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div>
                        <h1 class="brand-title">🧮 <?= htmlspecialchars($page_title) ?></h1>
                        <p class="brand-subtitle">Comparateur transporteurs professionnels</p>
                    </div>
                </div>
                <div class="version-info">
                    <div>Version <?= $version_info['version'] ?></div>
                    <div>Build <?= $version_info['build'] ?></div>
                </div>
            </div>
        </div>
    </header>

    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                
                <!-- Panneau formulaire -->
                <div class="form-panel">
                    
                    <!-- Section informations -->
                    <div class="form-section">
                        <h2 class="section-title">📦 Informations de l'envoi</h2>
                        <p class="section-subtitle">Renseignez les caractéristiques de votre expédition</p>
                        
                        <form method="POST" action="" id="calc-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="field-label" for="departement">
                                        📍 Département de destination
                                    </label>
                                    <input type="text" id="departement" name="departement" 
                                           class="form-control" placeholder="67" maxlength="3"
                                           value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="field-label" for="poids">
                                        ⚖️ Poids total (kg)
                                    </label>
                                    <input type="number" id="poids" name="poids" 
                                           class="form-control" step="0.1" min="0.1" max="32000"
                                           value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>" required>
                                </div>
                            </div>
                        
                    </div>
                    
                    <!-- Section type d'envoi -->
                    <div class="form-section">
                        <h2 class="section-title">📋 Type d'envoi</h2>
                        
                        <div class="radio-buttons">
                            <label class="radio-btn">
                                <input type="radio" name="type" value="colis" <?= ($_POST['type'] ?? 'colis') === 'colis' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>📦 Colis</strong>
                                    <small>Envoi standard</small>
                                </div>
                            </label>
                            <label class="radio-btn">
                                <input type="radio" name="type" value="palette" <?= ($_POST['type'] ?? '') === 'palette' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>🚛 Palette</strong>
                                    <small>Expédition palettisée</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label class="field-label" for="palettes">
                                📊 Nombre de palettes EUR
                            </label>
                            <input type="number" id="palettes" name="palettes" 
                                   class="form-control" min="0" max="20"
                                   value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>">
                        </div>
                    </div>
                    
                    <!-- Section ADR -->
                    <div class="form-section adr-section">
                        <h2 class="section-title">⚠️ Matières dangereuses (ADR)</h2>
                        
                        <div class="radio-buttons">
                            <label class="radio-btn">
                                <input type="radio" name="adr" value="non" <?= ($_POST['adr'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>✅ Non ADR</strong>
                                    <small>Marchandise normale</small>
                                </div>
                            </label>
                            <label class="radio-btn">
                                <input type="radio" name="adr" value="oui" <?= ($_POST['adr'] ?? '') === 'oui' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>⚠️ ADR</strong>
                                    <small>Matières dangereuses</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Section options -->
                    <div class="form-section">
                        <h2 class="section-title">⚙️ Options de livraison</h2>
                        
                        <div class="options-grid">
                            <label class="option-card <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'selected' : '' ?>">
                                <input type="radio" name="option_sup" value="standard" 
                                       <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'checked' : '' ?>>
                                <div class="option-title">🚚 Standard</div>
                                <div class="option-description">Livraison normale</div>
                                <div class="option-impact">Inclus</div>
                            </label>
                            
                            <label class="option-card <?= ($_POST['option_sup'] ?? '') === 'rdv' ? 'selected' : '' ?>">
                                <input type="radio" name="option_sup" value="rdv" 
                                       <?= ($_POST['option_sup'] ?? '') === 'rdv' ? 'checked' : '' ?>>
                                <div class="option-title">📞 Prise de RDV</div>
                                <div class="option-description">Appel avant livraison</div>
                                <div class="option-impact">+ Supplément</div>
                            </label>
                            
                            <label class="option-card <?= ($_POST['option_sup'] ?? '') === 'premium13' ? 'selected' : '' ?>">
                                <input type="radio" name="option_sup" value="premium13" 
                                       <?= ($_POST['option_sup'] ?? '') === 'premium13' ? 'checked' : '' ?>>
                                <div class="option-title">⏰ Premium 13h</div>
                                <div class="option-description">Livraison avant 13h</div>
                                <div class="option-impact">+ Supplément</div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Section enlèvement -->
                    <div class="form-section enlevement-section <?= isset($_POST['enlevement']) ? 'enabled' : '' ?>">
                        <label class="checkbox-label">
                            <input type="checkbox" name="enlevement" <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                            <span>🏠 Enlèvement à domicile</span>
                        </label>
                        <div class="field-help">Collecte de votre marchandise à votre adresse</div>
                    </div>
                    
                    <div class="form-section">
                        <button type="submit" class="btn-primary">
                            🚀 Calculer les tarifs
                        </button>
                    </div>
                        </form>
                </div>
                
                <!-- Panneau résultats sticky -->
                <div class="results-panel">
                    <div class="results-header">
                        <h2>💰 Tarifs</h2>
                        <?php if ($_POST): ?>
                            <div class="calculation-status">
                                <?php if (!empty($validation_errors)): ?>
                                    ❌ Erreurs de validation
                                <?php elseif ($results): ?>
                                    ✅ Calcul terminé (<?= $calculation_time ?> ms)
                                <?php else: ?>
                                    ⏳ Calcul en cours...
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="results-content">
                        <?php if (!$_POST): ?>
                            <div class="results-placeholder">
                                <div class="placeholder-icon">🧮</div>
                                <p>Remplissez le formulaire pour voir les tarifs</p>
                            </div>
                        <?php elseif (!empty($validation_errors)): ?>
                            <div class="error-message">
                                <strong>❌ Données invalides</strong>
                                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                                    <?php foreach ($validation_errors as $field => $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php elseif ($results && isset($results['results'])): ?>
                            <?php 
                            $valid_results = array_filter($results['results'], fn($price) => $price !== null);
                            if (!empty($valid_results)):
                                $best_carrier = array_keys($valid_results, min($valid_results))[0];
                                $best_price = $valid_results[$best_carrier];
                            ?>
                                <div class="best-rate">
                                    <h3>🏆 Meilleur tarif</h3>
                                    <div class="best-price"><?= number_format($best_price, 2, ',', ' ') ?> €</div>
                                    <div class="best-carrier"><?= strtoupper($best_carrier) ?></div>
                                </div>
                                
                                <div class="comparison">
                                    <?php foreach ($valid_results as $carrier => $price): ?>
                                        <div class="carrier-row <?= $carrier === $best_carrier ? 'best' : '' ?>">
                                            <span><?= strtoupper($carrier) ?></span>
                                            <strong><?= number_format($price, 2, ',', ' ') ?> €</strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="error-message">
                                    ❌ Aucun tarif disponible pour ces critères
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="footer-content">
                <div>&copy; <?= date('Y') ?> Guldagil - Version <?= $version_info['version'] ?></div>
                <div>Build <?= $version_info['build'] ?> - <?= $version_info['timestamp'] ?></div>
            </div>
        </div>
    </footer>

    <!-- Scripts JS existants -->
    <script src="../assets/js/modules/calculateur/controllers/calculation-controller.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/ui-controller.js"></script>
    
    <!-- Debug conditionnel -->
    <?php if ($_POST && defined('DEBUG') && DEBUG): ?>
    <script>
        console.log('Debug calculateur:', {
            'POST': <?= json_encode($_POST, JSON_HEX_TAG) ?>,
            'params': <?= json_encode($params ?? [], JSON_HEX_TAG) ?>,
            'results': <?= json_encode($results, JSON_HEX_TAG) ?>,
            'debug_info': <?= json_encode($debug_info, JSON_HEX_TAG) ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>
