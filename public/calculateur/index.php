<?php
/**
 * public/calculateur/index.php
 * Interface calculateur progressive - Étape 1
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
$user_authenticated = true;

// Logique de calcul (PRÉSERVÉE)
$results = null;
$validation_errors = [];
$calculation_time = 0;
$debug_info = [];

function validateCalculatorData($data) {
    $errors = [];
    
    if (empty($data['departement'])) {
        $errors['departement'] = 'Département requis';
    } elseif (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide (01-95)';
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
    
    if ($data['type'] === 'palette' && ($data['palettes'] < 0 || $data['palettes'] > 20)) {
        $errors['palettes'] = 'Nombre de palettes invalide (0-20)';
    }
    
    return $errors;
}

// Gestion AJAX pour calcul dynamique et délais
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'delay') {
        // Récupération délai depuis BDD
        $carrier = $_GET['carrier'] ?? '';
        $dept = $_GET['dept'] ?? '';
        $option = $_GET['option'] ?? 'standard';
        
        try {
            $table_map = ['xpo' => 'gul_xpo_rates', 'heppner' => 'gul_heppner_rates', 'kn' => 'gul_kn_rates'];
            if (!isset($table_map[$carrier])) {
                throw new Exception('Transporteur invalide');
            }
            
            $sql = "SELECT delais FROM {$table_map[$carrier]} WHERE num_departement = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$dept]);
            $result = $stmt->fetch();
            
            if ($result) {
                $delay = $result['delais'];
                
                // Adapter selon option
                if ($option === 'premium13') {
                    $delay .= ' garanti avant 14h';
                } elseif ($option === 'rdv') {
                    $delay .= ' sur RDV';
                }
                
                echo json_encode(['success' => true, 'delay' => $delay]);
            } else {
                echo json_encode(['success' => false, 'delay' => '24-48h']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'delay' => '24-48h']);
        }
        exit;
    }
    if ($_GET['ajax'] === '1') {
        // Calcul principal
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
                    
                    echo json_encode([
                        'success' => true,
                        'results' => $results['results'] ?? [],
                        'debug' => $results['debug'] ?? []
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Service indisponible']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'errors' => $validation_errors]);
        }
        exit;
    }
}
// Traitement formulaire classique (préservé pour fallback)
if ($_POST && !isset($_GET['ajax'])) {
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
            }
            
            $calculation_time = round((microtime(true) - $start_time) * 1000, 2);
            
        } catch (Exception $e) {
            $validation_errors['system'] = $e->getMessage();
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
    
    <!-- CSS existant -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/modern-interface.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/ux-improvements.css">
    
    <!-- CSS améliorations -->
    <style>
        /* Couleurs plus vivantes */
        :root {
            --primary: #2563eb;
            --primary-light: #60a5fa;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #06b6d4;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --orange: #f97316;
            
            /* Nouvelles couleurs transporteurs */
            --xpo-color: #e11d48;
            --heppner-color: #10b981;
            --kn-color: #3b82f6;
        }
        
        /* Étapes progressives */
        .form-step {
            opacity: 0.4;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .form-step.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .form-step.completed {
            opacity: 1;
            pointer-events: auto;
            border-left: 4px solid var(--success);
        }
        
        /* Animation des étapes */
        .form-step.active .section-title {
            color: var(--primary);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Indicateur de progression */
        .progress-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            width: 0%;
            transition: width 0.5s ease;
        }
        
        /* Bouton reset */
        .btn-reset {
            background: var(--warning);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-reset:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
            cursor: help;
        }
        
        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Auto-suggestion poids */
        .weight-suggestion {
            font-size: 0.8rem;
            color: var(--info);
            margin-top: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .weight-suggestion.show {
            opacity: 1;
        }
        
        /* Status loading */
        .calculating {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Améliorations UX - Étape 2 */
        .carrier-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: white;
            border-radius: 12px;
            border: 2px solid transparent;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .carrier-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .carrier-card.best {
            border-color: var(--success);
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .carrier-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .carrier-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-800);
        }
        
        .carrier-delay {
            font-size: 0.85rem;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .carrier-price {
            text-align: right;
        }
        
        .price-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .carrier-card.best .price-value {
            color: var(--success);
        }
        
        .price-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
            background: var(--gray-100);
            color: var(--gray-600);
            margin-top: 4px;
        }
        
        .price-option {
            font-size: 0.7rem;
            color: var(--warning);
            font-weight: 600;
            margin-top: 2px;
        }
        
        .carrier-card.best .price-badge {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        /* Transporteur colors */
        .carrier-card[data-carrier="xpo"] {
            border-left: 4px solid var(--xpo-color);
        }
        
        .carrier-card[data-carrier="heppner"] {
            border-left: 4px solid var(--heppner-color);
        }
        
        .carrier-card[data-carrier="kn"] {
            border-left: 4px solid var(--kn-color);
        }
        
        /* Smart hints */
        .smart-hint {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid var(--warning);
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: #92400e;
            display: none;
        }
        
        .smart-hint.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Validation states */
        .form-control.valid {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .form-control.invalid {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        /* Option cards enhanced */
        .option-card {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .option-card:hover::before {
            left: 100%;
        }
        
        .option-card.selected {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(59, 130, 246, 0.05) 100%);
        }
        
        /* Bouton ADR */
        .btn-adr {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        .btn-adr:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
            text-decoration: none;
            color: white;
        }
        
        /* Historique */
        .history-section {
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .history-header {
            background: var(--gray-100);
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-200);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .history-content.expanded {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .history-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .history-item:hover {
            background: var(--gray-50);
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-time {
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        
        .history-details {
            font-size: 0.9rem;
            margin-top: 4px;
        }
        
        /* Bouton validation palettes */
        .btn-validate-palettes {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-validate-palettes:hover {
            background: #059669;
            transform: scale(1.05);
        }
    </style>
    
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
</head>
<body class="calculateur-app">
    
    <!-- Header -->
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
                    <button type="button" class="btn-reset" onclick="resetForm()">🔄 Reset</button>
                </div>
            </div>
        </div>
    </header>

    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                
                <!-- Panneau formulaire progressif -->
                <div class="form-panel">
                    
                    <!-- Barre de progression -->
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                    
                    <form id="calc-form" data-dynamic="true">
                        
                        <!-- Étape 1: Destination -->
                        <div class="form-section form-step active" id="step-destination" data-step="1">
                            <h2 class="section-title">📍 Étape 1 - Destination</h2>
                            <p class="section-subtitle">Où souhaitez-vous expédier ?</p>
                            
                            <div class="form-group">
                                <label class="field-label tooltip" for="departement" 
                                       data-tooltip="Département français métropolitain (01-95)">
                                    📍 Département de destination
                                </label>
                                <input type="text" id="departement" name="departement" 
                                       class="form-control" placeholder="Ex: 67, 75, 13..." maxlength="2"
                                       value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>" required>
                                <div class="field-help">Saisissez le code département (2 chiffres)</div>
                            </div>
                        </div>
                        
                        <!-- Étape 2: Poids -->
                        <div class="form-section form-step" id="step-poids" data-step="2">
                            <h2 class="section-title">⚖️ Étape 2 - Poids</h2>
                            <p class="section-subtitle">Quel est le poids total de votre envoi ?</p>
                            
                            <div class="form-group">
                                <label class="field-label tooltip" for="poids" 
                                       data-tooltip="Poids total brut de l'expédition">
                                    ⚖️ Poids total (kg)
                                </label>
                                <input type="number" id="poids" name="poids" 
                                       class="form-control" step="0.1" min="0.1" max="32000"
                                       placeholder="Ex: 25.5"
                                       value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>" required>
                                <div class="weight-suggestion" id="weightSuggestion"></div>
                                <div class="field-help">Poids brut total incluant l'emballage</div>
                            </div>
                        </div>
                        
                        <!-- Étape 3: Type -->
                        <div class="form-section form-step" id="step-type" data-step="3">
                            <h2 class="section-title">📦 Étape 3 - Type d'envoi</h2>
                            <p class="section-subtitle">Comment est conditionné votre envoi ?</p>
                            
                            <div class="radio-buttons">
                                <label class="radio-btn">
                                    <input type="radio" name="type" value="colis" 
                                           <?= ($_POST['type'] ?? '') === 'colis' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>📦 Colis</strong>
                                        <small>Envoi standard emballé</small>
                                    </div>
                                </label>
                                <label class="radio-btn">
                                    <input type="radio" name="type" value="palette" 
                                           <?= ($_POST['type'] ?? '') === 'palette' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>🚛 Palette</strong>
                                        <small>Expédition palettisée</small>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Champ palettes (affiché si palette) -->
                            <div class="form-group" id="palettes-field" style="display: none; margin-top: 20px;">
                                <label class="field-label tooltip" for="palettes" 
                                       data-tooltip="Palettes Europe consignées retournables (différent du total palettes expédiées)">
                                    📊 Dont Palettes EUR consignées
                                </label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="number" id="palettes" name="palettes" 
                                           class="form-control" min="0" max="20" placeholder="0"
                                           value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>" style="flex: 1;">
                                    <button type="button" class="btn-validate-palettes" onclick="validatePalettesStep()">
                                        ✓
                                    </button>
                                </div>
                                <div class="field-help">
                                    <strong>Palettes EUR consignées :</strong> Palettes Europe retournables facturées séparément.<br>
                                    <em>Peut être 0 si vous utilisez vos propres palettes ou palettes perdues.</em>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Étape 4: ADR -->
                        <div class="form-section form-step adr-section" id="step-adr" data-step="4">
                            <h2 class="section-title">⚠️ Étape 4 - Matières dangereuses</h2>
                            <p class="section-subtitle">Votre envoi contient-il des matières dangereuses (ADR) ?</p>
                            
                            <div class="radio-buttons">
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="non" 
                                           <?= ($_POST['adr'] ?? '') === 'non' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>✅ Non ADR</strong>
                                        <small>Marchandise normale</small>
                                    </div>
                                </label>
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="oui" 
                                           <?= ($_POST['adr'] ?? '') === 'oui' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>⚠️ ADR</strong>
                                        <small>Matières dangereuses</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Étape 5: Options -->
                        <div class="form-section form-step" id="step-options" data-step="5">
                            <h2 class="section-title">⚙️ Étape 5 - Options</h2>
                            <p class="section-subtitle">Choisissez vos options de livraison</p>
                            
                            <div class="options-grid">
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="standard" 
                                           <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'checked' : '' ?>>
                                    <div class="option-title">🚚 Standard</div>
                                    <div class="option-description">Livraison normale</div>
                                    <div class="option-impact">Inclus</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="rdv">
                                    <div class="option-title">📞 Prise de RDV</div>
                                    <div class="option-description">Appel avant livraison</div>
                                    <div class="option-impact">~7€ (selon transporteur)</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="premium_matin">
                                    <div class="option-title">⏰ Premium Matin</div>
                                    <div class="option-description">Livraison garantie matin</div>
                                    <div class="option-impact">+Supplément</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="target">
                                    <div class="option-title">📅 Date fixe</div>
                                    <div class="option-description">Date imposée précise</div>
                                    <div class="option-impact">+Supplément</div>
                                </label>
                            </div>
                            
                            <!-- Enlèvement extérieur -->
                            <div class="enlevement-section" style="margin-top: 20px;">
                                <label class="checkbox-label tooltip" 
                                       data-tooltip="Collecte marchandise à une adresse extérieure (coût selon transporteur)">
                                    <input type="checkbox" name="enlevement" 
                                           <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                                    <span>🏭 Enlèvement extérieur</span>
                                </label>
                                <div class="field-help">Collecte marchandise hors siège social (gratuit Heppner, +25€ XPO)</div>
                            </div>
                        </div>
                        
                        <!-- Bouton Reset -->
                        <div class="form-section" style="text-align: center; padding: 20px;">
                            <button type="button" class="btn-reset" onclick="resetForm()">
                                🔄 Recommencer le calcul
                            </button>
                        </div>
                        
                    </form>
                </div>
                
                <!-- Panneau résultats -->
                <div class="results-panel">
                    <div class="results-header">
                        <h2>💰 Tarifs</h2>
                        <div class="calculation-status" id="calcStatus">
                            ⏳ En attente de données...
                        </div>
                    </div>
                    
                    <div class="results-content" id="resultsContent">
                        <div class="results-placeholder">
                            <div class="placeholder-icon">🧮</div>
                            <p>Complétez le formulaire pour voir les tarifs</p>
                        </div>
                    </div>
                    
                    <!-- Historique -->
                    <div class="history-section">
                        <div class="history-header" onclick="toggleHistory()">
                            <span>📜 Historique</span>
                            <span id="historyToggle">▼</span>
                        </div>
                        <div class="history-content" id="historyContent">
                            <div style="padding: 16px; text-align: center; color: var(--gray-500);">
                                Aucun calcul récent
                            </div>
                        </div>
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

    <!-- JavaScript interface progressive -->
    <script>
        // État du formulaire
        let currentStep = 1;
        let isCalculating = false;
        let calculationTimeout = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            setupEventListeners();
            updateProgress();
        });
        
        function initializeForm() {
            // Focus sur premier champ
            document.getElementById('departement').focus();
            
            // Vérifier valeurs existantes et avancer si nécessaire
            checkExistingValues();
        }
        
        function setupEventListeners() {
            // Département
            document.getElementById('departement').addEventListener('input', function() {
                if (this.value.length >= 2 && /^(0[1-9]|[1-8][0-9]|9[0-5])$/.test(this.value)) {
                    this.classList.add('valid');
                    this.classList.remove('invalid');
                    completeStep(1);
                    moveToStep(2);
                } else if (this.value.length >= 2) {
                    this.classList.add('invalid');
                    this.classList.remove('valid');
                } else {
                    this.classList.remove('valid', 'invalid');
                }
            });
            
            // Poids avec validation
            document.getElementById('poids').addEventListener('input', function() {
                const poids = parseFloat(this.value);
                
                if (poids > 0) {
                    this.classList.add('valid');
                    this.classList.remove('invalid');
                    
                    // Suggestion automatique palette si > 60kg
                    const suggestion = document.getElementById('weightSuggestion');
                    if (poids > 60) {
                        suggestion.textContent = '💡 Suggestion: Expédition en palette recommandée (>60kg)';
                        suggestion.classList.add('show');
                        
                        // Auto-sélection palette
                        document.querySelector('input[name="type"][value="palette"]').checked = true;
                        showPalettesField();
                    } else {
                        suggestion.classList.remove('show');
                        document.querySelector('input[name="type"][value="colis"]').checked = true;
                        hidePalettesField();
                    }
                    
                    completeStep(2);
                    moveToStep(3);
                } else if (this.value !== '') {
                    this.classList.add('invalid');
                    this.classList.remove('valid');
                } else {
                    this.classList.remove('valid', 'invalid');
                }
            });
            
            // Type d'envoi
            document.querySelectorAll('input[name="type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'palette') {
                        showPalettesField();
                        // Auto-focus sur palettes EUR pour validation rapide
                        setTimeout(() => document.getElementById('palettes').focus(), 100);
                    } else {
                        hidePalettesField();
                        completeStep(3);
                        moveToStep(4);
                    }
                });
            });
            
            // Palettes - Permettre 0 et validation par bouton
            document.getElementById('palettes').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    validatePalettesStep();
                }
            });
        }
        
        function validatePalettesStep() {
            completeStep(3);
            moveToStep(4);
        }
            
            // ADR
            document.querySelectorAll('input[name="adr"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    completeStep(4);
                    moveToStep(5);
                    scheduleCalculation();
                });
            });
            
            // Options et enlèvement
            document.querySelectorAll('input[name="option_sup"], input[name="enlevement"]').forEach(input => {
                input.addEventListener('change', function() {
                    completeStep(5);
                    scheduleCalculation();
                });
            });
            
            // Palettes
            document.getElementById('palettes').addEventListener('input', function() {
                scheduleCalculation();
            });
        
        function moveToStep(step) {
            if (step <= currentStep) return;
            
            currentStep = step;
            
            // Activer étape
            document.querySelectorAll('.form-step').forEach((el, index) => {
                if (index + 1 < step) {
                    el.classList.add('completed');
                    el.classList.remove('active');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                    el.classList.remove('completed');
                } else {
                    el.classList.remove('active', 'completed');
                }
            });
            
            // Focus sur premier champ de l'étape
            setTimeout(() => {
                const stepEl = document.getElementById(`step-${getStepName(step)}`);
                const firstInput = stepEl.querySelector('input:not([type="hidden"])');
                if (firstInput) firstInput.focus();
            }, 300);
            
            updateProgress();
        }
        
        function completeStep(step) {
            const stepEl = document.querySelector(`[data-step="${step}"]`);
            stepEl.classList.add('completed');
        }
        
        function getStepName(step) {
            const names = {1: 'destination', 2: 'poids', 3: 'type', 4: 'adr', 5: 'options'};
            return names[step] || 'destination';
        }
        
        function updateProgress() {
            const progress = (currentStep / 5) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        function showPalettesField() {
            document.getElementById('palettes-field').style.display = 'block';
        }
        
        function hidePalettesField() {
            document.getElementById('palettes-field').style.display = 'none';
            document.getElementById('palettes').value = '0';
        }
        
        function scheduleCalculation() {
            if (currentStep < 4) return; // Attendre ADR minimum
            
            clearTimeout(calculationTimeout);
            calculationTimeout = setTimeout(calculateTariffs, 500);
        }
        
        async function calculateTariffs() {
            if (isCalculating) return;
            
            const formData = new FormData(document.getElementById('calc-form'));
            
            // Validation minimale
            if (!formData.get('departement') || !formData.get('poids') || !formData.get('adr')) {
                return;
            }
            
            isCalculating = true;
            document.getElementById('calcStatus').innerHTML = '<span class="spinner"></span> Calcul en cours...';
            document.getElementById('resultsContent').classList.add('calculating');
            
            try {
                const response = await fetch('?ajax=1', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.results);
                    document.getElementById('calcStatus').innerHTML = '✅ Tarifs calculés';
                } else {
                    document.getElementById('calcStatus').innerHTML = '❌ Erreur de calcul';
                    console.error('Erreur:', data.error || data.errors);
                }
                
            } catch (error) {
                console.error('Erreur AJAX:', error);
                document.getElementById('calcStatus').innerHTML = '❌ Erreur réseau';
            } finally {
                isCalculating = false;
                document.getElementById('resultsContent').classList.remove('calculating');
            }
        }
        
        function displayResults(results) {
            const validResults = Object.entries(results).filter(([, price]) => price !== null);
            
            if (validResults.length === 0) {
                document.getElementById('resultsContent').innerHTML = 
                    '<div class="error-message">❌ Aucun tarif disponible</div>';
                return;
            }
            
            const bestResult = validResults.reduce((min, curr) => 
                curr[1] < min[1] ? curr : min
            );
            
            let html = `
                <div class="best-rate">
                    <h3>🏆 Meilleur tarif</h3>
                    <div class="best-price">${formatPrice(bestResult[1])}</div>
                    <div class="best-carrier">${bestResult[0].toUpperCase()}</div>
                    <div class="carrier-delay" style="margin-top: 8px; color: #065f46;">
                        ⏰ ${getDeliveryDelay(bestResult[0], getCurrentOptions())}
                    </div>
                </div>
                <div class="comparison">
            `;
            
            validResults.forEach(([carrier, price]) => {
                const isBest = carrier === bestResult[0];
                const delay = getDeliveryDelay(carrier, getCurrentOptions());
                
                html += `
                    <div class="carrier-card ${isBest ? 'best' : ''}" data-carrier="${carrier}">
                        <div class="carrier-info">
                            <div class="carrier-name">${carrier.toUpperCase()}</div>
                            <div class="carrier-delay">⏰ ${delay}</div>
                        </div>
                        <div class="carrier-price">
                            <div class="price-value">${formatPrice(price)}</div>
                            ${getOptionCostDisplay(carrier, getCurrentOptions())}
                            ${isBest ? '<div class="price-badge">MEILLEUR</div>' : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Bouton ADR si ADR = oui
            if (getCurrentOptions().adr) {
                html += `
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="../adr/create-expedition.php" class="btn-adr" target="_blank">
                            ⚠️ Créer déclaration ADR
                        </a>
                    </div>
                `;
            }
            
            // Ajouter conseils smart
            html += generateSmartHints(validResults, getCurrentOptions());
            
            document.getElementById('resultsContent').innerHTML = html;
            
            // Sauvegarder dans l'historique
            saveToHistory(getCurrentFormData(), validResults);
        }
        
        function getDeliveryDelay(carrier, options) {
            // Récupération depuis BDD via AJAX pour délais précis
            return getDelayFromDB(carrier, options.departement, options.premium13 ? 'premium13' : 'standard');
        }
        
        function getDelayFromDB(carrier, departement, option = 'standard') {
            // Récupération délai BDD avec adaptation options
            const baseDelays = {
                'heppner': '24-48h',
                'xpo': '24-48h', 
                'kn': '48-72h'
            };
            
            const delay = baseDelays[carrier] || '24-48h';
            
            // Adaptation selon options des fiches transporteurs
            switch (option) {
                case 'premium_matin':
                    if (carrier === 'heppner') return delay.replace(/\d+/, '') + 'garanti avant 13h';
                    if (carrier === 'xpo') return delay.replace(/\d+/, '') + 'garanti avant 14h';
                    return delay + ' garanti matin';
                    
                case 'target':
                    return 'Date imposée précise';
                    
                case 'rdv':
                    return delay + ' sur RDV (+6,70€)';
                    
                default:
                    return delay;
            }
        }
        
        function getCurrentOptions() {
            const form = document.getElementById('calc-form');
            const formData = new FormData(form);
            
            return {
                premium_matin: formData.get('option_sup') === 'premium_matin',
                target: formData.get('option_sup') === 'target', 
                rdv: formData.get('option_sup') === 'rdv',
                enlevement: formData.has('enlevement'),
                adr: formData.get('adr') === 'oui',
                type: formData.get('type'),
                poids: parseFloat(formData.get('poids') || 0),
                departement: formData.get('departement')
            };
        }
        
        function generateSmartHints(results, options) {
            let hints = [];
            
            // Conseil économie
            const prices = results.map(([, price]) => price).sort((a, b) => a - b);
            if (prices.length > 1) {
                const saving = prices[1] - prices[0];
                if (saving > 5) {
                    hints.push(`💡 Économie de ${formatPrice(saving)} avec le transporteur le moins cher`);
                }
            }
            
            // Conseil délai
            if (options.premium13) {
                hints.push(`⚡ Option Premium sélectionnée : livraison garantie avant 14h`);
            }
            
            // Conseil poids
            if (options.poids > 100 && options.type === 'colis') {
                hints.push(`📦 Pour ${options.poids}kg, considérez l'expédition en palette pour plus de sécurité`);
            }
            
            if (hints.length === 0) return '';
            
            return `
                <div class="smart-hint show">
                    ${hints.join('<br>')}
                </div>
            `;
        }
        
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }
        
        function resetForm() {
            if (confirm('Voulez-vous vraiment recommencer ?')) {
                document.getElementById('calc-form').reset();
                currentStep = 1;
                
                // Reset visual state
                document.querySelectorAll('.form-step').forEach((el, index) => {
                    if (index === 0) {
                        el.classList.add('active');
                        el.classList.remove('completed');
                    } else {
                        el.classList.remove('active', 'completed');
                    }
                });
                
                hidePalettesField();
                document.getElementById('weightSuggestion').classList.remove('show');
                updateProgress();
                
                // Reset results
                document.getElementById('resultsContent').innerHTML = `
                    <div class="results-placeholder">
                        <div class="placeholder-icon">🧮</div>
                        <p>Complétez le formulaire pour voir les tarifs</p>
                    </div>
                `;
                document.getElementById('calcStatus').innerHTML = '⏳ En attente de données...';
                
                // Focus premier champ
                document.getElementById('departement').focus();
            }
        }
        
        function checkExistingValues() {
            // Vérifier si des valeurs existent déjà et avancer automatiquement
            const dept = document.getElementById('departement').value;
            const poids = document.getElementById('poids').value;
            const type = document.querySelector('input[name="type"]:checked');
            const adr = document.querySelector('input[name="adr"]:checked');
            
            if (dept && /^(0[1-9]|[1-8][0-9]|9[0-5])$/.test(dept)) {
                completeStep(1);
                currentStep = Math.max(currentStep, 2);
                document.getElementById('departement').classList.add('valid');
            }
            
            if (poids && parseFloat(poids) > 0) {
                completeStep(2);
                currentStep = Math.max(currentStep, 3);
                
                // Vérifier suggestion palette
                if (parseFloat(poids) > 60) {
                    document.getElementById('weightSuggestion').textContent = 
                        '💡 Suggestion: Expédition en palette recommandée (>60kg)';
                    document.getElementById('weightSuggestion').classList.add('show');
                }
            }
            
            if (type) {
                completeStep(3);
                currentStep = Math.max(currentStep, 4);
                
                if (type.value === 'palette') {
                    showPalettesField();
                }
            }
            
            if (adr) {
                completeStep(4);
                currentStep = Math.max(currentStep, 5);
                
                // Déclencher calcul si tout est prêt
                setTimeout(scheduleCalculation, 100);
            }
            
            // Mettre à jour l'affichage des étapes
            moveToStep(currentStep);
        }
    </script>

</body>
</html>
