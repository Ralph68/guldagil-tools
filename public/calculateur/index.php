<?php
/**
 * Interface UX optimisée - Auto-progression et calcul permanent
 * Chemin: /public/calculateur/index.php
 * Version: 2.1.0 - Build 20250624002
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../src/controllers/CalculateurController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

try {
    $controller = new CalculateurController($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_calculate'])) {
        header('Content-Type: application/json');
        echo json_encode($controller->calculate($_POST));
        exit;
    }
    
    $viewData = $controller->index($_GET);
    
} catch (Exception $e) {
    error_log("Erreur calculateur: " . $e->getMessage());
    $viewData = [
        'error' => true,
        'message' => 'Service temporairement indisponible',
        'preset_data' => [],
        'options_service' => [],
        'dept_restrictions' => []
    ];
}

extract($viewData);
$page_title = 'Calculateur de frais de port';
$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    
    <style>
    /* UX Améliorations spécifiques */
    .calc-layout-optimized {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .form-flow {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .field-inline {
        display: grid;
        grid-template-columns: 120px 1fr;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: white;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .field-inline:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }
    
    .field-inline.completed {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.02);
    }
    
    .field-label-inline {
        font-weight: 600;
        color: var(--gray-700);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .input-clean {
        border: none;
        background: transparent;
        font-size: 1rem;
        padding: 0.5rem 0;
        width: 100%;
        outline: none;
    }
    
    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .option-card {
        padding: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
    }
    
    .option-card:hover {
        border-color: var(--primary-light);
        transform: translateY(-1px);
    }
    
    .option-card.selected {
        border-color: var(--primary);
        background: rgba(30, 64, 175, 0.05);
    }
    
    .option-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .option-desc {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-bottom: 0.5rem;
    }
    
    .option-price {
        font-weight: 600;
        color: var(--primary);
        font-size: 0.9rem;
    }
    
    /* Results sticky */
    .results-sticky {
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    
    .results-always-visible {
        min-height: 400px;
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .quick-result {
        text-align: center;
        padding: 2rem;
    }
    
    .best-price-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--success);
        margin: 1rem 0;
    }
    
    .comparison-mini {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        font-size: 0.875rem;
    }
    
    @media (max-width: 1024px) {
        .calc-layout-optimized {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .results-sticky {
            position: static;
        }
        
        .field-inline {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .options-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body class="calculateur-app">
    
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div class="brand-info">
                        <h1 class="brand-title">Calculateur Intelligent</h1>
                        <p class="brand-subtitle">Calcul automatique en temps réel</p>
                    </div>
                </div>
                <div class="version-info">
                    <span>v<?= $version_info['version'] ?></span>
                    <small>Build <?= $version_info['build'] ?></small>
                </div>
            </div>
        </div>
    </header>

    <main class="app-main">
        <div class="container">
            <div class="calc-layout-optimized">
                
                <!-- Formulaire fluide -->
                <section class="form-section">
                    <form id="calc-form" class="form-flow">
                        
                        <!-- Département -->
                        <div class="field-inline" id="field-dept">
                            <label class="field-label-inline">
                                📍 Département
                            </label>
                            <input type="text" id="departement" name="departement" 
                                   class="input-clean" 
                                   placeholder="Ex: 67, 75, 13..."
                                   maxlength="3"
                                   autocomplete="off"
                                   value="<?= htmlspecialchars($preset_data['departement']) ?>">
                        </div>
                        
                        <!-- Poids -->
                        <div class="field-inline" id="field-poids">
                            <label class="field-label-inline">
                                ⚖️ Poids (kg)
                            </label>
                            <input type="number" id="poids" name="poids" 
                                   class="input-clean" 
                                   placeholder="Ex: 150"
                                   min="0.1" max="32000" step="0.1"
                                   value="<?= htmlspecialchars($preset_data['poids']) ?>">
                        </div>
                        
                        <!-- Type auto-détecté -->
                        <div class="field-inline" id="field-type">
                            <label class="field-label-inline">
                                📦 Type
                            </label>
                            <div class="type-auto-display">
                                <span id="type-detected">Détection automatique...</span>
                                <input type="hidden" id="type" name="type" value="">
                            </div>
                        </div>
                        
                        <!-- Options service -->
                        <div class="field-section">
                            <h3 style="margin-bottom: 1rem;">🚀 Options de livraison</h3>
                            <div class="options-grid">
                                <label class="option-card selected" data-value="standard">
                                    <input type="radio" name="service_livraison" value="standard" checked style="display: none;">
                                    <div class="option-title">📦 Standard</div>
                                    <div class="option-desc">Livraison normale</div>
                                    <div class="option-price">Inclus</div>
                                </label>
                                
                                <label class="option-card" data-value="rdv">
                                    <input type="radio" name="service_livraison" value="rdv" style="display: none;">
                                    <div class="option-title">📞 Prise de RDV</div>
                                    <div class="option-desc">Rendez-vous client</div>
                                    <div class="option-price">+15€</div>
                                </label>
                                
                                <label class="option-card" data-value="datefixe">
                                    <input type="radio" name="service_livraison" value="datefixe" style="display: none;">
                                    <div class="option-title">📅 Date fixe</div>
                                    <div class="option-desc">Jour précis</div>
                                    <div class="option-price">+25€</div>
                                </label>
                                
                                <label class="option-card" data-value="premium13">
                                    <input type="radio" name="service_livraison" value="premium13" style="display: none;">
                                    <div class="option-title">⚡ Premium 13h</div>
                                    <div class="option-desc">Avant 13h</div>
                                    <div class="option-price">+35€</div>
                                </label>
                                
                                <label class="option-card" data-value="premium18">
                                    <input type="radio" name="service_livraison" value="premium18" style="display: none;">
                                    <div class="option-title">⚡ Premium 18h</div>
                                    <div class="option-desc">Avant 18h</div>
                                    <div class="option-price">+25€</div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Options spéciales -->
                        <div class="field-section">
                            <h3 style="margin-bottom: 1rem;">⚙️ Options spéciales</h3>
                            <div class="options-grid">
                                <label class="option-card" data-checkbox="adr">
                                    <input type="checkbox" name="adr" value="1" style="display: none;"
                                           <?= ($preset_data['adr']) ? 'checked' : '' ?>>
                                    <div class="option-title">⚠️ Transport ADR</div>
                                    <div class="option-desc">Matières dangereuses</div>
                                    <div class="option-price">Selon transporteur</div>
                                </label>
                                
                                <label class="option-card" data-checkbox="enlevement">
                                    <input type="checkbox" name="enlevement" value="1" style="display: none;"
                                           <?= ($preset_data['enlevement']) ? 'checked' : '' ?>>
                                    <div class="option-title">🚚 Enlèvement</div>
                                    <div class="option-desc">Collecte domicile</div>
                                    <div class="option-price">Variable</div>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="ajax_calculate" value="1">
                        <input type="hidden" name="palettes" id="palettes" value="0">
                    </form>
                </section>
                
                <!-- Résultats permanents -->
                <section class="results-sticky">
                    <div class="results-always-visible">
                        <div id="results-content">
                            <div class="quick-result">
                                <h3>💰 Meilleur tarif</h3>
                                <div class="best-price-display" id="best-price">--</div>
                                <div id="best-carrier">Saisissez vos critères</div>
                                
                                <div class="comparison-mini" id="comparison-mini" style="display: none;">
                                    <div id="carrier-xpo">XPO: --</div>
                                    <div id="carrier-heppner">Heppner: --</div>
                                    <div id="carrier-kn">K+N: --</div>
                                </div>
                                
                                <div id="calc-detail" style="margin-top: 2rem; display: none;">
                                    <button type="button" class="btn btn-secondary" onclick="toggleDetail()">
                                        📊 Voir détail calcul
                                    </button>
                                    <div id="detail-content" style="display: none; margin-top: 1rem;">
                                        <!-- Détail injecté ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= COPYRIGHT_YEAR ?> Guldagil - Transport et Logistique</p>
                <div class="footer-version">
                    <?= renderVersionFooter() ?>
                </div>
            </div>
        </div>
    </footer>

    <script>
    window.CalculateurConfig = {
        preset: <?= json_encode($preset_data ?? []) ?>,
        options: <?= json_encode($options_service ?? []) ?>,
        restrictions: <?= json_encode($dept_restrictions ?? []) ?>,
        debug: <?= json_encode(defined('DEBUG') && DEBUG) ?>,
        urls: { calculate: window.location.href },
        version: '<?= $version_info['version'] ?>',
        build: '<?= $version_info['build'] ?>'
    };
    
    // Auto-progression et calcul permanent
    document.addEventListener('DOMContentLoaded', function() {
        const deptInput = document.getElementById('departement');
        const poidsInput = document.getElementById('poids');
        const typeDisplay = document.getElementById('type-detected');
        const typeHidden = document.getElementById('type');
        const palettesHidden = document.getElementById('palettes');
        
        let calcTimeout;
        
        // Auto-focus progression
        deptInput.addEventListener('input', function() {
            if (this.value.length >= 2) {
                markFieldCompleted('field-dept');
                setTimeout(() => poidsInput.focus(), 100);
            }
            triggerCalc();
        });
        
        poidsInput.addEventListener('input', function() {
            const poids = parseFloat(this.value);
            if (poids > 0) {
                markFieldCompleted('field-poids');
                
                // Auto-détection type
                if (poids > 60) {
                    typeDisplay.textContent = '🏗️ Palette (auto-détecté > 60kg)';
                    typeHidden.value = 'palette';
                    palettesHidden.value = '1'; // 1 palette EUR par défaut
                } else {
                    typeDisplay.textContent = '📦 Colis (auto-détecté ≤ 60kg)';
                    typeHidden.value = 'colis';
                    palettesHidden.value = '0';
                }
                markFieldCompleted('field-type');
            }
            triggerCalc();
        });
        
        // Options selection
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('click', function() {
                if (this.dataset.value) {
                    // Radio options
                    document.querySelectorAll('.option-card[data-value]').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input').checked = true;
                } else if (this.dataset.checkbox) {
                    // Checkbox options
                    const checkbox = this.querySelector('input');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected', checkbox.checked);
                }
                triggerCalc();
            });
        });
        
        // Calcul automatique
        function triggerCalc() {
            clearTimeout(calcTimeout);
            calcTimeout = setTimeout(performCalculation, 500);
        }
        
        async function performCalculation() {
            const dept = deptInput.value;
            const poids = poidsInput.value;
            const type = typeHidden.value;
            
            if (!dept || !poids || !type) return;
            
            try {
                const formData = new FormData(document.getElementById('calc-form'));
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                displayResults(data);
                
            } catch (error) {
                console.error('Erreur calcul:', error);
            }
        }
        
        function displayResults(data) {
            const bestPriceEl = document.getElementById('best-price');
            const bestCarrierEl = document.getElementById('best-carrier');
            const comparisonEl = document.getElementById('comparison-mini');
            const detailEl = document.getElementById('calc-detail');
            
            if (data.best_rate) {
                bestPriceEl.textContent = data.best_rate.formatted;
                bestCarrierEl.textContent = data.best_rate.carrier_name;
                
                // Comparaison mini
                Object.entries(data.carriers).forEach(([carrier, info]) => {
                    const el = document.getElementById(`carrier-${carrier}`);
                    if (el) {
                        el.textContent = `${info.name}: ${info.formatted}`;
                    }
                });
                
                comparisonEl.style.display = 'flex';
                detailEl.style.display = 'block';
                
                // Stocker détail pour affichage
                window.calcDetail = data.debug;
                
            } else {
                bestPriceEl.textContent = 'Non disponible';
                bestCarrierEl.textContent = 'Aucun transporteur disponible';
                comparisonEl.style.display = 'none';
                detailEl.style.display = 'none';
            }
        }
        
        function markFieldCompleted(fieldId) {
            document.getElementById(fieldId).classList.add('completed');
        }
        
        // Init avec preset data
        if (deptInput.value) markFieldCompleted('field-dept');
        if (poidsInput.value) {
            markFieldCompleted('field-poids');
            poidsInput.dispatchEvent(new Event('input'));
        }
        
        // Trigger initial calc si données preset
        if (deptInput.value && poidsInput.value) {
            triggerCalc();
        }
    });
    
    function toggleDetail() {
        const content = document.getElementById('detail-content');
        if (content.style.display === 'none') {
            content.style.display = 'block';
            if (window.calcDetail) {
                content.innerHTML = formatDetailHtml(window.calcDetail);
            }
        } else {
            content.style.display = 'none';
        }
    }
    
    function formatDetailHtml(debug) {
        let html = '';
        Object.entries(debug).forEach(([carrier, details]) => {
            if (!details.error && details.detail_calcul) {
                const calc = details.detail_calcul;
                html += `<div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4>${carrier.toUpperCase()}</h4>
                    <div>Tarif base: ${formatPrice(calc.tarif_base)}</div>
                    ${calc.surcharge_gasoil ? `<div>Surcharge gasoil: +${formatPrice(calc.surcharge_gasoil)}</div>` : ''}
                    ${calc.option ? `<div>Options: +${formatPrice(calc.option)}</div>` : ''}
                    <div><strong>Total: ${formatPrice(calc.total)}</strong></div>
                </div>`;
            }
        });
        return html;
    }
    
    function formatPrice(price) {
        return typeof price === 'number' ? 
            new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price) :
            price;
    }
    </script>
</body>
</html>
