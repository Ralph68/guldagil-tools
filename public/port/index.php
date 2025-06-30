<?php
/**
 * Titre: Calculateur de frais de port - Interface complète
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
session_start();

// Gestion AJAX pour calculs
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        // Lecture des données POST
        $input_data = file_get_contents('php://input');
        parse_str($input_data, $post_data);
        
        // Paramètres normalisés
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => isset($post_data['enlevement']) && $post_data['enlevement'] === '1',
            'palettes' => max(0, intval($post_data['palettes'] ?? 0)),
        ];
        
        // Validation
        if (empty($params['departement']) || !preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        
        if ($params['poids'] <= 0 || $params['poids'] > 10000) {
            throw new Exception('Poids invalide (1-10000 kg)');
        }
        
        // Charger le calculateur
        $transport_file = __DIR__ . '/../../features/port/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Module de calcul non disponible');
        }
        
        require_once $transport_file;
        $transport = new Transport($db);
        $results = $transport->calculateAll($params);
        
        if (empty($results)) {
            throw new Exception('Aucun tarif trouvé pour ces paramètres');
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'params' => $params,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Génération des départements pour le select
$departements = [
    '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence',
    '05' => 'Hautes-Alpes', '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes',
    '09' => 'Ariège', '10' => 'Aube', '11' => 'Aude', '12' => 'Aveyron',
    '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal', '16' => 'Charente',
    '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '21' => 'Côte-d\'Or',
    '22' => 'Côtes-d\'Armor', '23' => 'Creuse', '24' => 'Dordogne', '25' => 'Doubs',
    '26' => 'Drôme', '27' => 'Eure', '28' => 'Eure-et-Loir', '29' => 'Finistère',
    '30' => 'Gard', '31' => 'Haute-Garonne', '32' => 'Gers', '33' => 'Gironde',
    '34' => 'Hérault', '35' => 'Ille-et-Vilaine', '36' => 'Indre', '37' => 'Indre-et-Loire',
    '38' => 'Isère', '39' => 'Jura', '40' => 'Landes', '41' => 'Loir-et-Cher',
    '42' => 'Loire', '43' => 'Haute-Loire', '44' => 'Loire-Atlantique', '45' => 'Loiret',
    '46' => 'Lot', '47' => 'Lot-et-Garonne', '48' => 'Lozère', '49' => 'Maine-et-Loire',
    '50' => 'Manche', '51' => 'Marne', '52' => 'Haute-Marne', '53' => 'Mayenne',
    '54' => 'Meurthe-et-Moselle', '55' => 'Meuse', '56' => 'Morbihan', '57' => 'Moselle',
    '58' => 'Nièvre', '59' => 'Nord', '60' => 'Oise', '61' => 'Orne',
    '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme', '64' => 'Pyrénées-Atlantiques',
    '65' => 'Hautes-Pyrénées', '66' => 'Pyrénées-Orientales', '67' => 'Bas-Rhin',
    '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône', '71' => 'Saône-et-Loire',
    '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie', '75' => 'Paris',
    '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne', '78' => 'Yvelines',
    '79' => 'Deux-Sèvres', '80' => 'Somme', '81' => 'Tarn', '82' => 'Tarn-et-Garonne',
    '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée', '86' => 'Vienne',
    '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne', '90' => 'Territoire de Belfort',
    '91' => 'Essonne', '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis',
    '94' => 'Val-de-Marne', '95' => 'Val-d\'Oise'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    <meta name="description" content="Calculateur et comparateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    
    <!-- CSS -->
    <style>
        /* Variables CSS */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #059669;
            --warning-color: #d97706;
            --error-color: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 0.75rem;
            --spacing-lg: 1rem;
            --spacing-xl: 1.5rem;
            --spacing-2xl: 2rem;
        }

        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            color: var(--gray-800);
        }

        /* Header */
        .app-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }

        .brand-logo {
            height: 40px;
            width: auto;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin: 0;
        }

        .version-info {
            text-align: right;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Layout principal */
        .app-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: var(--spacing-2xl);
            min-height: calc(100vh - 200px);
        }

        /* Formulaire progressif */
        .form-panel {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            height: fit-content;
        }

        .panel-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: var(--spacing-xl);
            text-align: center;
        }

        .panel-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .panel-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: var(--spacing-sm) 0 0;
        }

        /* Barre de progression */
        .progress-bar {
            height: 4px;
            background: var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), #10b981);
            transition: width 0.3s ease;
            width: 0%;
        }

        /* Contenu formulaire */
        .form-content {
            padding: var(--spacing-xl);
        }

        .form-step {
            display: none;
            animation: slideIn 0.3s ease-in-out;
        }

        .form-step.active {
            display: block;
        }

        .step-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: var(--spacing-sm);
        }

        .step-subtitle {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-xl);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
        }

        .form-input.valid {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .form-input.invalid {
            border-color: var(--error-color);
            background: #fef2f2;
        }

        /* Options en ligne exclusives */
        .options-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .option-card {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            text-align: center;
        }

        .option-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .option-card.selected {
            border-color: var(--primary-color);
            background: #eff6ff;
        }

        .option-card input[type="radio"] {
            display: none;
        }

        .option-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: var(--spacing-xs);
        }

        .option-desc {
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .option-price {
            font-weight: 700;
            color: var(--primary-color);
            margin-top: var(--spacing-xs);
        }

        /* Section enlèvement séparée */
        .enlevement-section {
            border-top: 1px solid var(--gray-200);
            padding-top: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-xl);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Résultats sticky */
        .results-panel {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            position: sticky;
            top: 100px;
            height: fit-content;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .results-content {
            padding: var(--spacing-xl);
        }

        .results-empty {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--gray-500);
        }

        .results-empty .icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-lg);
        }

        .carrier-result {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            transition: all 0.2s ease;
        }

        .carrier-result:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .carrier-result.best {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .carrier-name {
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .carrier-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .carrier-details {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: var(--spacing-sm);
        }

        .best-badge {
            background: var(--success-color);
            color: white;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Footer */
        .app-footer {
            background: var(--gray-800);
            color: white;
            text-align: center;
            padding: var(--spacing-xl);
            margin-top: var(--spacing-2xl);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .app-main {
                grid-template-columns: 1fr;
                padding: var(--spacing-lg);
                gap: var(--spacing-lg);
            }

            .results-panel {
                position: static;
                order: -1;
                max-height: none;
            }

            .header-content {
                flex-direction: column;
                gap: var(--spacing-md);
                text-align: center;
            }

            .footer-content {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="app-header">
        <div class="header-content">
            <div class="brand">
                <img src="/assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
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
    </header>

    <!-- Contenu principal -->
    <main class="app-main">
        <!-- Panneau formulaire progressif -->
        <div class="form-panel">
            <div class="panel-header">
                <h2 class="panel-title">Calculateur de frais de port</h2>
                <p class="panel-subtitle">Comparez les tarifs XPO, Heppner et Kuehne+Nagel</p>
            </div>
            
            <!-- Barre de progression -->
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar"></div>
            </div>
            
            <div class="form-content">
                <form id="calculatorForm">
                    <!-- Étape 1: Destination -->
                    <div class="form-step active" data-step="1">
                        <h3 class="step-title">📍 Destination</h3>
                        <p class="step-subtitle">Où souhaitez-vous expédier ?</p>
                        
                        <div class="form-group">
                            <label class="form-label" for="departement">
                                Département de destination *
                            </label>
                            <select id="departement" name="departement" class="form-select" required>
                                <option value="">Sélectionner un département...</option>
                                <?php foreach ($departements as $code => $nom): ?>
                                    <option value="<?= $code ?>"><?= $code ?> - <?= htmlspecialchars($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Étape 2: Poids et type -->
                    <div class="form-step" data-step="2">
                        <h3 class="step-title">📦 Expédition</h3>
                        <p class="step-subtitle">Caractéristiques de votre envoi</p>
                        
                        <div class="form-group">
                            <label class="form-label" for="poids">
                                Poids total (kg) *
                            </label>
                            <input type="number" id="poids" name="poids" class="form-input" 
                                   min="1" max="10000" step="0.1" placeholder="Ex: 25.5" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="type">
                                Type d'expédition *
                            </label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="colis">Colis</option>
                                <option value="palette">Palette(s)</option>
                            </select>
                        </div>

                        <div class="form-group" id="palettesGroup" style="display: none;">
                            <label class="form-label" for="palettes">
                                Nombre de palettes
                            </label>
                            <input type="number" id="palettes" name="palettes" class="form-input" 
                                   min="1" max="10" value="1">
                        </div>
                    </div>

                    <!-- Étape 3: Services -->
                    <div class="form-step" data-step="3">
                        <h3 class="step-title">🚀 Services de livraison</h3>
                        <p class="step-subtitle">Choisissez votre niveau de service (optionnel)</p>
                        
                        <div class="options-group">
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="standard" checked>
                                <div class="option-title">Standard</div>
                                <div class="option-desc">24-48h selon destination</div>
                                <div class="option-price">Inclus</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="premium_matin">
                                <div class="option-title">Premium Matin</div>
                                <div class="option-desc">Avant 13h garanti</div>
                                <div class="option-price">+30%</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="rdv">
                                <div class="option-title">Sur RDV</div>
                                <div class="option-desc">Créneaux personnalisés</div>
                                <div class="option-price">+15%</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="target">
                                <div class="option-title">Date imposée</div>
                                <div class="option-desc">Date précise imposée</div>
                                <div class="option-price">+15%</div>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="adr" name="adr" value="oui">
                                Transport ADR (marchandises dangereuses)
                            </label>
                        </div>

                        <!-- Section enlèvement séparée -->
                        <div class="enlevement-section">
                            <h4 class="step-title">📤 Enlèvement</h4>
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" id="enlevement" name="enlevement" value="1">
                                    Inclure les frais d'enlèvement
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="calculateBtn">
                            <span>Calculer les tarifs</span>
                            <span>→</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panneau résultats sticky -->
        <div class="results-panel">
            <div class="panel-header">
                <h2 class="panel-title">Résultats</h2>
                <p class="panel-subtitle">Comparaison des transporteurs</p>
            </div>
            
            <div class="results-content">
                <div id="resultsContainer" class="results-empty">
                    <div class="icon">🚚</div>
                    <h3>Prêt pour le calcul</h3>
                    <p>Remplissez le formulaire pour comparer les tarifs</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div>&copy; <?= date('Y') ?> Guldagil - Version <?= $version_info['version'] ?></div>
            <div>Build <?= $version_info['build'] ?> - <?= $version_info['timestamp'] ?></div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // État du formulaire
        let currentStep = 1;
        let maxStep = 3;
        let isCalculating = false;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            updateProgress();
        });
        
        function setupEventListeners() {
            // Navigation entre étapes
            document.getElementById('departement').addEventListener('change', function() {
                if (this.value) {
                    nextStep();
                }
            });
            
            // Type d'expédition
            document.getElementById('type').addEventListener('change', function() {
                const palettesGroup = document.getElementById('palettesGroup');
                if (this.value === 'palette') {
                    palettesGroup.style.display = 'block';
                } else {
                    palettesGroup.style.display = 'none';
                }
                
                if (this.value && document.getElementById('poids').value) {
                    nextStep();
                }
            });
            
            document.getElementById('poids').addEventListener('input', function() {
                if (this.value && this.value > 0 && document.getElementById('type').value) {
                    nextStep();
                }
            });
            
            // Options de service exclusives
            const optionCards = document.querySelectorAll('.option-card');
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Retirer la sélection précédente
                    optionCards.forEach(c => c.classList.remove('selected'));
                    // Ajouter la sélection actuelle
                    this.classList.add('selected');
                    // Cocher le radio
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // Initialiser la première option comme sélectionnée
            optionCards[0].classList.add('selected');
            
            // Soumission du formulaire
            document.getElementById('calculatorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleCalculate();
            });
        }
        
        function nextStep() {
            if (currentStep < maxStep) {
                // Masquer l'étape actuelle
                const currentStepEl = document.querySelector(`[data-step="${currentStep}"]`);
                currentStepEl.classList.remove('active');
                
                // Afficher l'étape suivante
                currentStep++;
                const nextStepEl = document.querySelector(`[data-step="${currentStep}"]`);
                nextStepEl.classList.add('active');
                
                updateProgress();
            }
        }
        
        function updateProgress() {
            const progress = (currentStep / maxStep) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        async function handleCalculate() {
            if (isCalculating) return;
            
            const btn = document.getElementById('calculateBtn');
            const resultsContainer = document.getElementById('resultsContainer');
            
            // État de chargement
            isCalculating = true;
            btn.disabled = true;
            btn.innerHTML = '<span>Calcul en cours...</span><span>⏳</span>';
            
            resultsContainer.innerHTML = `
                <div class="results-empty">
                    <div class="icon loading">🔄</div>
                    <h3>Calcul en cours...</h3>
                    <p>Comparaison des tarifs transporteurs</p>
                </div>
            `;
            
            try {
                // Collecte des données du formulaire
                const formData = new FormData(document.getElementById('calculatorForm'));
                const params = new URLSearchParams();
                
                for (let [key, value] of formData.entries()) {
                    params.append(key, value);
                }
                
                // Appel AJAX
                const response = await fetch('?ajax=calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString()
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.results, data.params);
                } else {
                    throw new Error(data.error || 'Erreur de calcul');
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                resultsContainer.innerHTML = `
                    <div class="results-empty">
                        <div class="icon">❌</div>
                        <h3>Erreur de calcul</h3>
                        <p>${error.message}</p>
                        <button onclick="handleCalculate()" class="btn btn-primary" style="margin-top: 1rem;">
                            Réessayer
                        </button>
                    </div>
                `;
            } finally {
                // Restaurer l'état du bouton
                isCalculating = false;
                btn.disabled = false;
                btn.innerHTML = '<span>Calculer les tarifs</span><span>→</span>';
            }
        }
        
        function displayResults(results, params) {
            const resultsContainer = document.getElementById('resultsContainer');
            
            if (!results || Object.keys(results).length === 0) {
                resultsContainer.innerHTML = `
                    <div class="results-empty">
                        <div class="icon">😔</div>
                        <h3>Aucun tarif trouvé</h3>
                        <p>Vérifiez vos paramètres et réessayez</p>
                    </div>
                `;
                return;
            }
            
            // Trouver le meilleur tarif
            let bestCarrier = null;
            let bestPrice = Infinity;
            
            Object.entries(results).forEach(([carrier, data]) => {
                if (data.total && data.total < bestPrice) {
                    bestPrice = data.total;
                    bestCarrier = carrier;
                }
            });
            
            // Générer le HTML des résultats
            let html = '';
            
            // Informations de la recherche
            html += `
                <div class="search-info" style="background: var(--gray-50); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); font-size: 0.875rem;">
                    <strong>Paramètres:</strong> ${params.departement} • ${params.poids}kg • ${params.type}
                    ${params.adr === 'oui' ? ' • ADR' : ''}
                    ${params.enlevement ? ' • Enlèvement' : ''}
                </div>
            `;
            
            // Résultats par transporteur
            const carrierNames = {
                'xpo': 'XPO Logistics',
                'heppner': 'Heppner',
                'kn': 'Kuehne+Nagel'
            };
            
            const carrierColors = {
                'xpo': '#dc2626',
                'heppner': '#059669', 
                'kn': '#2563eb'
            };
            
            Object.entries(results).forEach(([carrier, data]) => {
                const isBest = carrier === bestCarrier;
                const carrierName = carrierNames[carrier] || carrier.toUpperCase();
                const carrierColor = carrierColors[carrier] || '#6b7280';
                
                html += `
                    <div class="carrier-result ${isBest ? 'best' : ''}" style="border-left: 4px solid ${carrierColor};">
                        <div class="carrier-name">
                            <span style="color: ${carrierColor};">${carrierName}</span>
                            ${isBest ? '<span class="best-badge">Meilleur prix</span>' : ''}
                        </div>
                        <div class="carrier-price">${data.total ? data.total.toFixed(2) + ' €' : 'N/A'}</div>
                        <div class="carrier-details">
                            ${data.base ? `Base: ${data.base.toFixed(2)}€` : ''}
                            ${data.adr ? ` • ADR: +${data.adr.toFixed(2)}€` : ''}
                            ${data.enlevement ? ` • Enlèvement: +${data.enlevement.toFixed(2)}€` : ''}
                            ${data.option_sup && data.option_sup > 0 ? ` • Service: +${data.option_sup.toFixed(2)}€` : ''}
                            <br>
                            <small style="color: var(--gray-500);">
                                Délai: ${data.delais || '24-48h'}
                                ${data.service ? ` • ${data.service}` : ''}
                            </small>
                        </div>
                    </div>
                `;
            });
            
            // Affichage final avec animation
            resultsContainer.innerHTML = html;
            resultsContainer.style.opacity = '0';
            resultsContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                resultsContainer.style.transition = 'all 0.3s ease';
                resultsContainer.style.opacity = '1';
                resultsContainer.style.transform = 'translateY(0)';
            }, 100);
        }
        
        // Fonctions utilitaires
        function resetForm() {
            document.getElementById('calculatorForm').reset();
            currentStep = 1;
            
            // Masquer toutes les étapes
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Afficher la première étape
            document.querySelector('[data-step="1"]').classList.add('active');
            
            // Réinitialiser les options
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector('.option-card').classList.add('selected');
            
            // Masquer le groupe palettes
            document.getElementById('palettesGroup').style.display = 'none';
            
            // Réinitialiser les résultats
            document.getElementById('resultsContainer').innerHTML = `
                <div class="results-empty">
                    <div class="icon">🚚</div>
                    <h3>Prêt pour le calcul</h3>
                    <p>Remplissez le formulaire pour comparer les tarifs</p>
                </div>
            `;
            
            updateProgress();
        }
        
        // Auto-focus et amélioration UX
        function focusNextInput() {
            const currentStepEl = document.querySelector(`[data-step="${currentStep}"]`);
            const firstInput = currentStepEl.querySelector('input, select');
            if (firstInput) {
                firstInput.focus();
            }
        }
        
        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Entrée pour passer à l'étape suivante ou calculer
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (currentStep < maxStep) {
                    // Vérifier que les champs requis sont remplis
                    const currentStepEl = document.querySelector(`[data-step="${currentStep}"]`);
                    const requiredFields = currentStepEl.querySelectorAll('[required]');
                    let allValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value) {
                            allValid = false;
                            field.focus();
                        }
                    });
                    
                    if (allValid) {
                        nextStep();
                        setTimeout(focusNextInput, 100);
                    }
                } else {
                    // Dernière étape, lancer le calcul
                    handleCalculate();
                }
            }
            
            // Échap pour réinitialiser
            if (e.key === 'Escape') {
                resetForm();
            }
        });
        
        // Sauvegarde automatique des données
        function saveFormData() {
            const formData = new FormData(document.getElementById('calculatorForm'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('calculateur_data', JSON.stringify(data));
        }
        
        function loadFormData() {
            const saved = localStorage.getItem('calculateur_data');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    Object.entries(data).forEach(([key, value]) => {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element) {
                            if (element.type === 'checkbox' || element.type === 'radio') {
                                element.checked = element.value === value;
                            } else {
                                element.value = value;
                            }
                        }
                    });
                } catch (e) {
                    console.warn('Erreur lors du chargement des données sauvegardées');
                }
            }
        }
        
        // Charger les données sauvegardées au démarrage
        setTimeout(loadFormData, 100);
        
        // Sauvegarder à chaque modification
        document.getElementById('calculatorForm').addEventListener('input', saveFormData);
        document.getElementById('calculatorForm').addEventListener('change', saveFormData);
    </script>
</body>
</html>
