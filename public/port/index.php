<?php
/**
 * Titre: Calculateur de frais de port - Version complète corrigée
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Charger les fonctions helper pour les permissions
if (file_exists(ROOT_PATH . '/config/functions.php')) {
    require_once ROOT_PATH . '/config/functions.php';
} else {
    // Fallback des fonctions si fichier manquant
    if (!function_exists('canAccessModule')) {
        function canAccessModule($module_key, $module_data, $user_role) {
            return in_array($user_role, ['admin', 'dev']) || $module_key === 'port';
        }
    }
    if (!function_exists('shouldShowModule')) {
        function shouldShowModule($module_key, $module_data, $user_role) {
            return true;
        }
    }
}

// Variables pour header/footer - DÉFINIR AVANT session_start()
$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
$page_subtitle = 'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel';
$page_description = 'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport';
$current_module = 'port';
$user_authenticated = true;
$module_css = true;
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🚛', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

// Chargement header (qui gère l'authentification et les sessions)
require_once ROOT_PATH . '/templates/header.php';

// GESTION AJAX CALCULATE
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        parse_str(file_get_contents('php://input'), $post_data);
        
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => ($post_data['enlevement'] ?? 'non') === 'oui',
            'palettes' => max(1, intval($post_data['palettes'] ?? 1)),
            'palette_eur' => intval($post_data['palette_eur'] ?? 0),
        ];
        
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide');
        }
        
        $transport_file = __DIR__ . '/calculs/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Transport non trouvé: ' . $transport_file);
        }
        
        require_once $transport_file;
        $transport = new TransportCalculateur();
        $results = $transport->calculateAll($params);
        
        echo json_encode([
            'success' => true,
            'carriers' => $results,
            'timestamp' => date('H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<div class="calc-container">
    <!-- Header calculateur -->
    <div class="calc-header">
        <div class="calc-header-content">
            <div class="calc-title-group">
                <h1 class="calc-title">
                    <span class="calc-icon">🚛</span>
                    Calculateur de Frais de Port
                </h1>
                <p class="calc-subtitle">Comparaison instantanée des tarifs XPO, Heppner, Kuehne+Nagel</p>
            </div>
            <div class="calc-version">
                <span class="calc-version-badge">v<?= htmlspecialchars($version_info['version']) ?></span>
                <span class="calc-build">Build <?= htmlspecialchars($version_info['build_number']) ?></span>
            </div>
        </div>
    </div>

    <!-- Formulaire principal -->
    <div class="calc-main">
        <div class="calc-form-section">
            <form id="calculatorForm" class="calc-form" novalidate>
                <!-- Étape 1: Destination -->
                <div class="calc-step-content" data-step="1">
                    <h2 class="calc-step-title">📍 Destination</h2>
                    <div class="calc-form-group">
                        <label for="departement" class="calc-form-label">
                            📍 Département de destination *
                        </label>
                        <input type="text" 
                               id="departement" 
                               name="departement" 
                               class="calc-form-input" 
                               placeholder="Ex: 75, 69, 13..."
                               maxlength="3">
                        <div class="calc-error-message" id="departementError"></div>
                        <div class="calc-field-hint">💡 Numéro de département (ex: 75, 69, 13)</div>
                    </div>
                </div>
                
                <!-- Étape 2: Colis -->
                <div class="calc-step-content" data-step="2" style="display: none;">
                    <h2 class="calc-step-title">📦 Informations colis</h2>
                    <div class="calc-form-group">
                        <label for="poids" class="calc-form-label">
                            ⚖️ Poids total (kg) *
                        </label>
                        <input type="number" 
                               id="poids" 
                               name="poids" 
                               class="calc-form-input"
                               placeholder="Ex: 25"
                               step="1" 
                               min="1" 
                               max="3000">
                        <div class="calc-error-message" id="poidsError"></div>
                        <div class="calc-field-hint">💡 Saisissez un poids entier de 1 à 3000 kg</div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label for="type" class="calc-form-label">
                            📦 Type d'envoi
                        </label>
                        <select id="type" name="type" class="calc-form-input">
                            <option value="colis">📦 Colis standard</option>
                            <option value="palette">🏗️ Palette(s) EUR</option>
                        </select>
                    </div>
                    
                    <div class="calc-form-group" id="palettesGroup" style="display: none;">
                        <label for="palettes" class="calc-form-label">
                            🏗️ Nombre de palettes EUR
                        </label>
                        <input type="number" 
                               id="palettes" 
                               name="palettes" 
                               class="calc-form-input" 
                               min="1" 
                               max="20" 
                               value="1">
                        <div class="calc-field-hint">💡 Palettes européennes standard (120x80x144cm)</div>
                    </div>
                </div>
                
                <!-- Étape 3: Options -->
                <div class="calc-step-content" data-step="3" style="display: none;">
                    <h2 class="calc-step-title">⚙️ Options de transport</h2>
                    
                    <div class="calc-form-group">
                        <label class="calc-form-label">
                            ⚠️ Transport ADR (matières dangereuses)
                        </label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-adr="non">❌ Non</button>
                            <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui</button>
                        </div>
                        <div class="calc-field-hint">💡 Les matières dangereuses nécessitent des frais supplémentaires</div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label for="option_sup" class="calc-form-label">
                            🚀 Service de livraison
                        </label>
                        <select id="option_sup" name="option_sup" class="calc-form-input">
                            <option value="standard">📅 Standard (24-48h)</option>
                            <option value="rdv">📞 Sur rendez-vous</option>
                            <option value="premium_13h">⏰ Premium avant 13h</option>
                            <option value="premium_18h">🌅 Premium avant 18h</option>
                        </select>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-form-label">
                            🏭 Enlèvement chez l'expéditeur
                        </label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-enlevement="non">❌ Non</button>
                            <button type="button" class="calc-toggle-btn" data-enlevement="oui">✅ Oui</button>
                        </div>
                        <div class="calc-field-hint">💡 Service d'enlèvement directement chez vous</div>
                    </div>
                </div>
                
                <!-- Boutons navigation -->
                <div class="calc-form-actions">
                    <button type="button" id="prevBtn" class="calc-btn calc-btn-secondary" style="display: none;">
                        ← Précédent
                    </button>
                    <button type="button" id="nextBtn" class="calc-btn calc-btn-primary">
                        Suivant →
                    </button>
                    <button type="submit" id="calculateBtn" class="calc-btn calc-btn-primary" style="display: none;">
                        🧮 Calculer les tarifs
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Résultats -->
        <div class="calc-results-section">
            <div id="resultsContent" class="calc-results-content">
                <div class="calc-empty-state">
                    <div class="calc-empty-icon">⏳</div>
                    <p class="calc-empty-text">Remplissez le formulaire pour voir les tarifs</p>
                    <div class="calc-status">Prêt pour calcul</div>
                </div>
            </div>
            
            <!-- Zone de chargement -->
            <div id="loadingState" class="calc-loading" style="display: none;">
                <div class="calc-loading-spinner"></div>
                <p>Calcul des tarifs en cours...</p>
            </div>
        </div>
    </div>
    
    <!-- Informations utiles -->
    <div class="calc-info-section">
        <div class="calc-info-cards">
            <div class="calc-info-card">
                <div class="calc-info-icon">📋</div>
                <h3>Transporteurs inclus</h3>
                <ul>
                    <li>🚛 XPO Logistics</li>
                    <li>🚚 Heppner</li>
                    <li>📦 Kuehne+Nagel</li>
                </ul>
            </div>
            
            <div class="calc-info-card">
                <div class="calc-info-icon">⚡</div>
                <h3>Avantages</h3>
                <ul>
                    <li>💰 Comparaison instantanée</li>
                    <li>📊 Tarifs négociés</li>
                    <li>🎯 Recommandation automatique</li>
                </ul>
            </div>
            
            <div class="calc-info-card">
                <div class="calc-info-icon">🔒</div>
                <h3>Sécurité</h3>
                <ul>
                    <li>🛡️ Données chiffrées</li>
                    <li>🔐 Accès sécurisé</li>
                    <li>📝 Historique privé</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
// Correction immédiate des problèmes
document.addEventListener('DOMContentLoaded', function() {
    // 1. Corriger le problème "invalid form control"
    const poidsField = document.getElementById('poids');
    if (poidsField) {
        // Retirer required temporairement pour éviter le blocage HTML5
        poidsField.removeAttribute('required');
        
        // Validation JavaScript personnalisée
        poidsField.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value) && value >= 1 && value <= 3000) {
                this.setCustomValidity('');
                this.classList.remove('error');
                this.classList.add('valid');
            } else {
                this.setCustomValidity('Poids requis entre 1 et 3000 kg');
                this.classList.add('error');
                this.classList.remove('valid');
            }
        });
    }
    
    // 2. Gestion des étapes du formulaire
    let currentStep = 1;
    const totalSteps = 3;
    
    function showStep(step) {
        // Masquer toutes les étapes
        document.querySelectorAll('.calc-step-content').forEach(el => {
            el.style.display = 'none';
        });
        
        // Afficher l'étape actuelle
        const currentStepEl = document.querySelector(`[data-step="${step}"]`);
        if (currentStepEl) {
            currentStepEl.style.display = 'block';
        }
        
        // Gestion des boutons
        document.getElementById('prevBtn').style.display = step > 1 ? 'inline-block' : 'none';
        document.getElementById('nextBtn').style.display = step < totalSteps ? 'inline-block' : 'none';
        document.getElementById('calculateBtn').style.display = step === totalSteps ? 'inline-block' : 'none';
        
        currentStep = step;
    }
    
    // Navigation étapes
    document.getElementById('nextBtn').addEventListener('click', function() {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });
    
    document.getElementById('prevBtn').addEventListener('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
    
    // 3. Gestion des toggles
    document.querySelectorAll('.calc-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Retirer active de tous les boutons du groupe
            this.parentNode.querySelectorAll('.calc-toggle-btn').forEach(b => b.classList.remove('active'));
            // Ajouter active au bouton cliqué
            this.classList.add('active');
        });
    });
    
    // 4. Gestion du type (palette/colis)
    document.getElementById('type').addEventListener('change', function() {
        const palettesGroup = document.getElementById('palettesGroup');
        if (this.value === 'palette') {
            palettesGroup.style.display = 'block';
        } else {
            palettesGroup.style.display = 'none';
        }
    });
    
    // 5. Soumission du formulaire
    document.getElementById('calculatorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        calculateRates();
    });
    
    // Fonction de calcul
    function calculateRates() {
        const formData = new FormData(document.getElementById('calculatorForm'));
        
        // Ajouter les valeurs des toggles
        const adrBtn = document.querySelector('[data-adr].active');
        const enlevementBtn = document.querySelector('[data-enlevement].active');
        
        formData.append('adr', adrBtn ? adrBtn.dataset.adr : 'non');
        formData.append('enlevement', enlevementBtn ? enlevementBtn.dataset.enlevement : 'non');
        
        // Validation simple
        const dept = formData.get('departement');
        const poids = formData.get('poids');
        
        if (!dept || !/^[0-9]{2,3}$/.test(dept)) {
            alert('Veuillez saisir un département valide (ex: 75, 69, 13)');
            return;
        }
        
        if (!poids || poids < 1 || poids > 3000) {
            alert('Veuillez saisir un poids entre 1 et 3000 kg');
            return;
        }
        
        // Afficher le loading
        document.getElementById('resultsContent').style.display = 'none';
        document.getElementById('loadingState').style.display = 'block';
        
        // Appel AJAX
        fetch('?ajax=calculate', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('resultsContent').style.display = 'block';
            
            if (data.success) {
                displayResults(data.carriers);
            } else {
                displayError(data.error || 'Erreur lors du calcul');
            }
        })
        .catch(error => {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('resultsContent').style.display = 'block';
            displayError('Erreur de connexion: ' + error.message);
        });
    }
    
    // Affichage des résultats
    function displayResults(carriers) {
        const resultsEl = document.getElementById('resultsContent');
        
        if (!carriers || carriers.length === 0) {
            resultsEl.innerHTML = `
                <div class="calc-empty-state">
                    <div class="calc-empty-icon">❌</div>
                    <p class="calc-empty-text">Aucun tarif disponible</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="calc-results-grid">';
        
        carriers.forEach((carrier, index) => {
            const isBest = index === 0; // Premier = meilleur tarif
            html += `
                <div class="calc-carrier-card ${isBest ? 'calc-carrier-best' : ''}">
                    ${isBest ? '<div class="calc-best-badge">🏆 Meilleur tarif</div>' : ''}
                    <div class="calc-carrier-header">
                        <h3 class="calc-carrier-name">${carrier.name || 'Transporteur'}</h3>
                        <div class="calc-carrier-price">${carrier.total || 'N/C'}€ HT</div>
                    </div>
                    <div class="calc-carrier-details">
                        ${carrier.details ? carrier.details.map(d => `<div>• ${d}</div>`).join('') : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        resultsEl.innerHTML = html;
    }
    
    // Affichage des erreurs
    function displayError(message) {
        document.getElementById('resultsContent').innerHTML = `
            <div class="calc-empty-state">
                <div class="calc-empty-icon">❌</div>
                <p class="calc-empty-text">Erreur: ${message}</p>
                <div class="calc-status">Veuillez réessayer</div>
            </div>
        `;
    }
    
    // Initialiser l'affichage
    showStep(1);
});
</script>

<?php require_once ROOT_PATH . '/templates/footer.php'; ?>
