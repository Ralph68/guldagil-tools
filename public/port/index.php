<?php
/**
 * Titre: Calculateur de frais de port - VERSION CORRIGÉE BDD
 * Chemin: /public/port/index.php  
 * Version: 0.5 beta + build auto
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables pour header
$page_title = 'Calculateur de Frais de Port';
$page_subtitle = 'Comparaison multi-transporteurs';
$current_module = 'port';
$module_css = true;

// Traitement POST - Calcul direct via classe Transport existante
$results = null;
$calculation_time = 0;
$form_data = [
    'departement' => $_POST['departement'] ?? '',
    'poids' => floatval($_POST['poids'] ?? 0),
    'type' => $_POST['type'] ?? 'colis',
    'adr' => ($_POST['adr'] ?? 'non') === 'oui',
    'option_sup' => $_POST['option_sup'] ?? 'standard',
    'enlevement' => ($_POST['enlevement'] ?? 'non') === 'oui'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = microtime(true);
    
    $errors = [];
    if (strlen($form_data['departement']) < 2) $errors[] = 'Département invalide';
    if ($form_data['poids'] <= 0) $errors[] = 'Poids invalide';
    
    if (empty($errors)) {
        try {
            // Utiliser la classe Transport du module
            $transport_file = __DIR__ . '/calculs/transport.php';
            if (!file_exists($transport_file)) {
                throw new Exception('Moteur de calcul manquant');
            }
            
            require_once $transport_file;
            $transport = new Transport($db);
            $results = $transport->calculateAll($form_data);
            $calculation_time = round((microtime(true) - $start_time) * 1000, 2);
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}

include_once ROOT_PATH . '/templates/header.php';
?>
?>

<style>
:root {
    --port-primary: #2563eb;
    --port-success: #059669;
    --port-warning: #d97706;
    --port-danger: #dc2626;
}

.calc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.calc-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--port-primary), #1d4ed8);
    color: white;
    border-radius: 0.75rem;
}

.calc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

.calc-panel {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.calc-panel-header {
    background: #f8fafc;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.calc-panel-content {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--port-primary);
}

.radio-group {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.radio-option:hover {
    border-color: var(--port-primary);
}

.calc-btn {
    width: 100%;
    background: var(--port-primary);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.calc-btn:hover {
    background: #1d4ed8;
}

.error-list {
    background: #fef2f2;
    color: var(--port-danger);
    padding: 1rem;
    border-radius: 0.5rem;
    border-left: 4px solid var(--port-danger);
    margin-bottom: 1.5rem;
}

.results-grid {
    display: grid;
    gap: 1rem;
}

.carrier-card {
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    transition: all 0.2s;
    position: relative;
}

.carrier-card:first-child {
    border-color: var(--port-success);
    background: rgba(5, 150, 105, 0.05);
}

.carrier-card:first-child::before {
    content: "🏆 Meilleur prix";
    position: absolute;
    top: -10px;
    right: 1rem;
    background: var(--port-success);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.carrier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.carrier-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1f2937;
}

.carrier-price {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--port-primary);
}

.carrier-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.calc-meta {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .calc-grid {
        grid-template-columns: 1fr;
    }
    .radio-group {
        flex-direction: column;
    }
}
</style>

<!-- Configuration JavaScript pour calcul temps réel -->
<script>
// Configuration chargée depuis la BDD
const CALC_CONFIG = <?= json_encode([
    'ajax_url' => '/port/api/calculate.php',
    'departments' => range(1, 95), // Tous les départements français
    'min_weight' => 0.1,
    'max_weight' => 3000
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="calc-container">
    <div class="calc-header">
        <h1>🚛 Calculateur de Frais de Port</h1>
        <p>Comparaison instantanée des tarifs transporteurs</p>
    </div>
    
    <div class="calc-grid">
        <!-- Formulaire -->
        <div class="calc-panel">
            <div class="calc-panel-header">
                <h2>📝 Informations d'envoi</h2>
            </div>
            <div class="calc-panel-content">
                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <strong>⚠️ Erreurs :</strong>
                        <ul style="margin: 0.5rem 0 0 1rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="calc-form">
                    <div class="form-group">
                        <label class="form-label" for="departement">Département *</label>
                        <input type="text" id="departement" name="departement" class="form-input" 
                               placeholder="Ex: 75, 69, 13..." maxlength="3" required
                               value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="poids">Poids (kg) *</label>
                        <input type="number" id="poids" name="poids" class="form-input" 
                               placeholder="Ex: 25.5" min="0.1" step="0.1" required
                               value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Type d'envoi</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="type" value="colis" 
                                       <?= ($_POST['type'] ?? 'colis') === 'colis' ? 'checked' : '' ?>>
                                <span>📦 Colis</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="type" value="palette"
                                       <?= ($_POST['type'] ?? '') === 'palette' ? 'checked' : '' ?>>
                                <span>🏗️ Palette</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ADR</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="adr" value="non"
                                       <?= ($_POST['adr'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                                <span>✅ Non</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="adr" value="oui"
                                       <?= ($_POST['adr'] ?? '') === 'oui' ? 'checked' : '' ?>>
                                <span>⚠️ Oui</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Enlèvement</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="enlevement" value="non"
                                       <?= ($_POST['enlevement'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                                <span>🏢 Dépôt</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="enlevement" value="oui"
                                       <?= ($_POST['enlevement'] ?? '') === 'oui' ? 'checked' : '' ?>>
                                <span>🏠 Domicile</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="option_sup">Service</label>
                        <select id="option_sup" name="option_sup" class="form-select">
                            <option value="standard" <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>
                                Standard
                            </option>
                            <option value="express" <?= ($_POST['option_sup'] ?? '') === 'express' ? 'selected' : '' ?>>
                                Express 24h
                            </option>
                            <option value="urgent" <?= ($_POST['option_sup'] ?? '') === 'urgent' ? 'selected' : '' ?>>
                                Urgent
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="calc-btn" id="calc-submit">
                        🚛 Calculer les frais
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Résultats -->
        <div class="calc-panel">
            <div class="calc-panel-header">
                <h2>📊 Résultats</h2>
                <?php if ($results): ?>
                    <small>Calculé en <?= $calculation_time ?>ms</small>
                <?php endif; ?>
            </div>
            <div class="calc-panel-content" id="results-container">
                <?php if ($results): ?>
                    
                    <?php if (isset($results['affretement']) && $results['affretement']): ?>
                        <div class="calc-meta">
                            <strong>🚛 Affrètement requis</strong><br>
                            Contact commercial nécessaire
                        </div>
                    <?php endif; ?>
                    
                    <div class="results-grid">
                        <?php 
                        $carriers = $results['carriers'] ?? [];
                        $carrier_names = [
                            'xpo' => 'XPO Logistics',
                            'heppner' => 'Heppner',
                            'kn' => 'Kuehne+Nagel'
                        ];
                        
                        // Trier par prix croissant
                        $sorted_carriers = [];
                        foreach ($carriers as $carrier) {
                            if (isset($carrier['price']) && $carrier['price'] > 0) {
                                $sorted_carriers[] = $carrier;
                            }
                        }
                        usort($sorted_carriers, fn($a, $b) => $a['price'] <=> $b['price']);
                        
                        foreach ($sorted_carriers as $carrier):
                            $prix_ttc = $carrier['price'];
                            $prix_ht = round($prix_ttc / 1.2, 2);
                        ?>
                            <div class="carrier-card">
                                <div class="carrier-header">
                                    <div class="carrier-name"><?= htmlspecialchars($carrier['carrier_name']) ?></div>
                                    <div class="carrier-price"><?= $carrier['price_display'] ?></div>
                                </div>
                                <div class="carrier-details">
                                    <div><strong>HT :</strong> <?= number_format($prix_ht, 2) ?>€</div>
                                    <div><strong>Délai :</strong> <?= htmlspecialchars($carrier['delay'] ?? '24-48h') ?></div>
                                    <div><strong>Service :</strong> <?= htmlspecialchars($form_data['option_sup']) ?></div>
                                    <div><strong>Poids :</strong> <?= $form_data['poids'] ?>kg</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <div style="text-align: center; color: #6b7280; margin-top: 2rem;">
                        <p>🚚 Saisissez vos critères et calculez</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour calcul temps réel -->
<script src="/port/assets/js/calculator.js"></script>

<script>
// Auto-focus et validation simple
document.getElementById('departement').focus();

// Validation poids
document.getElementById('poids').addEventListener('input', function() {
    const poids = parseFloat(this.value);
    if (poids > 3000) {
        this.style.borderColor = '#d97706';
        this.title = 'Affrètement probable';
    } else {
        this.style.borderColor = '#e5e7eb';
        this.title = '';
    }
});

// Style radio buttons
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => {
            r.closest('.radio-option').style.borderColor = '#e5e7eb';
            r.closest('.radio-option').style.background = 'white';
        });
        
        this.closest('.radio-option').style.borderColor = 'var(--port-primary)';
        this.closest('.radio-option').style.background = 'rgba(37, 99, 235, 0.05)';
    });
    
    if (radio.checked) {
        radio.closest('.radio-option').style.borderColor = 'var(--port-primary)';
        radio.closest('.radio-option').style.background = 'rgba(37, 99, 235, 0.05)';
    }
});

<?php if ($results): ?>
setTimeout(() => {
    document.querySelector('#results-container').scrollIntoView({ 
        behavior: 'smooth', block: 'nearest' 
    });
}, 100);
<?php endif; ?>
</script>

<?php include_once ROOT_PATH . '/templates/footer.php'; ?>