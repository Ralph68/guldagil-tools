<?php
/**
 * Titre: Calculateur de frais de port - Interface corrigée
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

session_start();
define('ROOT_PATH', dirname(__DIR__, 2));

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Authentification
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur'];
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';

// Variables template
$page_title = 'Calculateur de frais de port';
$page_subtitle = 'Comparaison des tarifs transport';
$current_module = 'calculateur';

// Inclure header si disponible
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($build_number) ?>">
    <link rel="stylesheet" href="/assets/css/calculateur.css?v=<?= htmlspecialchars($build_number) ?>">
    
    <!-- CSS critique responsive -->
    <style>
        :root {
            --calc-primary: #3b82f6;
            --calc-success: #10b981;
            --calc-error: #ef4444;
            --calc-warning: #f59e0b;
        }
        
        body { margin: 0; font-family: -apple-system, sans-serif; background: #f8fafc; }
        
        .calc-header {
            background: linear-gradient(135deg, var(--calc-primary), #2563eb);
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calc-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .calc-breadcrumb {
            background: white;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .calc-breadcrumb-content {
            max-width: 1200px;
            margin: 0 auto;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .calc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 1.5rem;
            min-height: calc(100vh - 140px);
        }
        
        .calc-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .calc-results {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        
        .calc-form-header, .calc-results-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }
        
        .calc-form-content {
            padding: 1.25rem;
        }
        
        .calc-form-group {
            margin-bottom: 1.25rem;
        }
        
        .calc-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .calc-input, .calc-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .calc-input:focus, .calc-select:focus {
            outline: none;
            border-color: var(--calc-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .calc-input.valid { border-color: var(--calc-success); }
        .calc-input.invalid { border-color: var(--calc-error); }
        
        .calc-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .calc-checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .calc-checkbox-item:hover { background: #f9fafb; }
        
        .calc-checkbox {
            width: 1.125rem;
            height: 1.125rem;
            accent-color: var(--calc-primary);
        }
        
        .calc-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--calc-primary), #2563eb);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .calc-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        .calc-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .calc-results-content {
            padding: 1.25rem;
        }
        
        .calc-result-item {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .calc-result-item.best {
            border-color: var(--calc-success);
            background: rgba(16, 185, 129, 0.05);
            position: relative;
        }
        
        .calc-result-item.best::before {
            content: '🏆 MEILLEUR TARIF';
            position: absolute;
            top: -1px;
            right: 1rem;
            background: var(--calc-success);
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 0 0 6px 6px;
        }
        
        .calc-carrier-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }
        
        .calc-price {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--calc-primary);
            margin: 0.5rem 0;
        }
        
        .calc-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.75rem;
            font-size: 0.875rem;
            margin-top: 0.75rem;
        }
        
        .calc-detail {
            display: flex;
            flex-direction: column;
        }
        
        .calc-detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .calc-detail-value {
            color: #374151;
            font-weight: 600;
        }
        
        .calc-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--calc-primary);
        }
        
        .calc-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid var(--calc-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.75rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .calc-placeholder {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .calc-help {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        /* Mobile responsive - résultats restent visibles */
        @media (max-width: 768px) {
            .calc-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                gap: 1rem;
                padding: 0.75rem;
                height: calc(100vh - 140px);
            }
            
            .calc-form {
                position: static;
                order: 1;
                height: auto;
            }
            
            .calc-results {
                order: 2;
                position: sticky;
                top: 120px;
                height: calc(100vh - 160px);
                overflow-y: auto;
            }
            
            .calc-header-content {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            
            .calc-details {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .calc-form-content, .calc-results-content {
                padding: 1rem;
            }
            
            .calc-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php } ?>

<!-- Header calculateur -->
<div class="calc-header">
    <div class="calc-header-content">
        <div>
            <h1 style="margin: 0; font-size: 1.5rem;">📦 <?= htmlspecialchars($page_title) ?></h1>
            <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
        <div style="font-size: 0.875rem;">
            👤 <?= htmlspecialchars($current_user['username']) ?>
        </div>
    </div>
</div>

<!-- Fil d'Ariane -->
<div class="calc-breadcrumb">
    <div class="calc-breadcrumb-content">
        🏠 <a href="/" style="color: #3b82f6; text-decoration: none;">Accueil</a> › 
        🧮 Calculateur de frais
    </div>
</div>

<!-- Interface principale -->
<div class="calc-container">
    <!-- Formulaire sticky -->
    <div class="calc-form">
        <div class="calc-form-header">
            <h2 style="margin: 0; font-size: 1.125rem; color: #374151;">📋 Paramètres d'expédition</h2>
            <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #6b7280;">Complétez pour calculer</p>
        </div>
        
        <div class="calc-form-content">
            <form id="calcForm">
                <div class="calc-form-group">
                    <label for="dept" class="calc-label">Département destination *</label>
                    <input type="text" id="dept" class="calc-input" placeholder="Ex: 67" maxlength="2" required>
                    <div class="calc-help">Code département français (01-95)</div>
                </div>
                
                <div class="calc-form-group">
                    <label for="poids" class="calc-label">Poids total (kg) *</label>
                    <input type="number" id="poids" class="calc-input" placeholder="Ex: 25.5" min="0.1" step="0.1" required>
                </div>
                
                <div class="calc-form-group">
                    <label for="type" class="calc-label">Type d'envoi</label>
                    <select id="type" class="calc-select">
                        <option value="colis">Colis standard</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>
                
                <div class="calc-form-group" id="palettesGroup" style="display: none;">
                    <label for="palettes" class="calc-label">Nombre de palettes</label>
                    <input type="number" id="palettes" class="calc-input" min="0" value="0">
                </div>
                
                <div class="calc-form-group">
                    <label class="calc-label">Options de service</label>
                    <div class="calc-checkbox-group">
                        <div class="calc-checkbox-item">
                            <input type="checkbox" id="adr" class="calc-checkbox">
                            <label for="adr">⚠️ Matières dangereuses (ADR)</label>
                        </div>
                        <div class="calc-checkbox-item">
                            <input type="checkbox" id="enlevement" class="calc-checkbox">
                            <label for="enlevement">🏭 Enlèvement extérieur</label>
                        </div>
                    </div>
                </div>
                
                <div class="calc-form-group">
                    <label for="service" class="calc-label">Service supplémentaire</label>
                    <select id="service" class="calc-select">
                        <option value="standard">Standard</option>
                        <option value="premium13">Premium 13h</option>
                        <option value="rdv">Livraison sur RDV</option>
                    </select>
                </div>
                
                <button type="submit" class="calc-btn" id="calcBtn">
                    🧮 Calculer les tarifs
                </button>
            </form>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="calc-results">
        <div class="calc-results-header">
            <h2 style="margin: 0; font-size: 1.125rem; color: #374151;">💰 Comparatif des tarifs</h2>
            <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #6b7280;">Résultats en temps réel</p>
        </div>
        
        <div class="calc-results-content" id="results">
            <div class="calc-placeholder">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🧮</div>
                <p>Complétez le formulaire pour voir les tarifs</p>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/calculateur.js?v=<?= htmlspecialchars($build_number) ?>"></script>
<script>
// Version simplifiée intégrée
const CalcSimple = {
    init() {
        this.form = document.getElementById('calcForm');
        this.results = document.getElementById('results');
        this.btn = document.getElementById('calcBtn');
        
        // Events
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.calculate();
        });
        
        document.getElementById('type').addEventListener('change', (e) => {
            document.getElementById('palettesGroup').style.display = 
                e.target.value === 'palette' ? 'block' : 'none';
        });
        
        // Auto-calcul
        ['dept', 'poids'].forEach(id => {
            document.getElementById(id).addEventListener('input', () => {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.autoCalc(), 800);
            });
        });
    },
    
    autoCalc() {
        const dept = document.getElementById('dept').value;
        const poids = document.getElementById('poids').value;
        if (dept.length === 2 && poids > 0) {
            this.calculate();
        }
    },
    
    async calculate() {
        const formData = new FormData(this.form);
        const data = {
            departement: formData.get('dept') || document.getElementById('dept').value,
            poids: formData.get('poids') || document.getElementById('poids').value,
            type: document.getElementById('type').value,
            palettes: document.getElementById('palettes').value || '0',
            adr: document.getElementById('adr').checked ? 'oui' : 'non',
            enlevement: document.getElementById('enlevement').checked,
            option_sup: document.getElementById('service').value
        };
        
        this.showLoading();
        
        try {
            const response = await fetch('/features/port/api/calculate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            });
            
            const result = await response.json();
            this.displayResults(result);
        } catch (error) {
            this.showError('Erreur de calcul');
        }
    },
    
    showLoading() {
        this.results.innerHTML = `
            <div class="calc-loading">
                <div class="calc-spinner"></div>
                Calcul en cours...
            </div>`;
        this.btn.disabled = true;
        this.btn.textContent = '⏳ Calcul...';
    },
    
    displayResults(result) {
        this.btn.disabled = false;
        this.btn.textContent = '🧮 Calculer les tarifs';
        
        if (!result.success || !result.carriers) {
            this.showError(result.message || 'Aucun résultat');
            return;
        }
        
        const carriers = result.carriers.sort((a, b) => a.price - b.price);
        const best = carriers[0].price;
        
        let html = '';
        carriers.forEach(carrier => {
            const isBest = carrier.price === best;
            html += `
                <div class="calc-result-item ${isBest ? 'best' : ''}">
                    <div class="calc-carrier-name">${carrier.carrier_name}</div>
                    <div class="calc-price">${carrier.price_display}</div>
                    <div class="calc-details">
                        <div class="calc-detail">
                            <span class="calc-detail-label">Délai</span>
                            <span class="calc-detail-value">${carrier.delay || '24-48h'}</span>
                        </div>
                        <div class="calc-detail">
                            <span class="calc-detail-label">Service</span>
                            <span class="calc-detail-value">${this.getServiceLabel()}</span>
                        </div>
                    </div>
                </div>`;
        });
        
        this.results.innerHTML = html;
    },
    
    showError(message) {
        this.results.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #ef4444;">
                ❌ ${message}
            </div>`;
        this.btn.disabled = false;
        this.btn.textContent = '🧮 Calculer les tarifs';
    },
    
    getServiceLabel() {
        const service = document.getElementById('service').value;
        return service === 'premium13' ? 'Premium 13h' : 
               service === 'rdv' ? 'Sur RDV' : 'Standard';
    }
};

document.addEventListener('DOMContentLoaded', () => CalcSimple.init());
</script>

<?php if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else { ?>
</body>
</html>
<?php } ?>
