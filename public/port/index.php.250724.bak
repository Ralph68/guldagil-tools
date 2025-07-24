<?php
/**
 * Titre: Calculateur de frais de port - Version améliorée avec présentation intuitive
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 * AMÉLIORATIONS :
 * - Boutons colis/palette au lieu de dropdown
 * - Options visuellement différenciées 
 * - Meilleur tarif mis en avant
 * - Affichage des infos tarifaires manquantes
 * - Interface plus intuitive et moderne
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
        
        if (!function_exists('calculateAll')) {
            throw new Exception('Fonction calculateAll introuvable');
        }
        
        $results = calculateAll($params);
        
        if (empty($results)) {
            throw new Exception('Aucun résultat obtenu');
        }
        
        // Trier par prix (meilleur en premier)
        usort($results, fn($a, $b) => ($a['prix_total'] ?? PHP_INT_MAX) <=> ($b['prix_total'] ?? PHP_INT_MAX));
        
        // Marquer le meilleur tarif
        if (!empty($results)) {
            $results[0]['is_best'] = true;
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'params' => $params,
            'best_price' => $results[0]['prix_total'] ?? null
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

<main class="calc-container">
    <div class="calc-main">
        <!-- PANNEAU FORMULAIRE AMÉLIORÉ -->
        <div class="calc-form-panel">
            <!-- En-tête avec informations -->
            <div class="calc-form-header">
                <h1 class="calc-form-title">
                    <span class="calc-form-icon">🚛</span>
                    Calculateur de Frais de Port
                </h1>
                <p class="calc-form-subtitle">
                    Comparez instantanément les tarifs XPO, Heppner et Kuehne+Nagel
                </p>
            </div>

            <!-- Formulaire principal -->
            <form id="calculatorForm" class="calc-form">
                <!-- ÉTAPE 1: DESTINATION -->
                <div class="calc-form-section">
                    <h3 class="calc-section-title">
                        <span class="calc-step-number">1</span>
                        📍 Destination
                    </h3>
                    
                    <div class="calc-form-group">
                        <label for="departement" class="calc-form-label">
                            Code département de destination *
                        </label>
                        <input type="text" 
                               id="departement" 
                               name="departement" 
                               class="calc-form-input"
                               placeholder="Ex: 67, 75, 13..." 
                               maxlength="3" 
                               pattern="[0-9]{2,3}"
                               required>
                        <small class="calc-form-hint">
                            💡 Code département français (2-3 chiffres)
                        </small>
                    </div>
                </div>

                <!-- ÉTAPE 2: EXPÉDITION -->
                <div class="calc-form-section">
                    <h3 class="calc-section-title">
                        <span class="calc-step-number">2</span>
                        📦 Expédition
                    </h3>
                    
                    <!-- Poids -->
                    <div class="calc-form-group">
                        <label for="poids" class="calc-form-label">
                            ⚖️ Poids total (kg) *
                        </label>
                        <input type="number" 
                               id="poids" 
                               name="poids" 
                               class="calc-form-input"
                               min="0.1" 
                               max="32000" 
                               step="0.1" 
                               placeholder="Ex: 25.5" 
                               required>
                        <small class="calc-form-hint">
                            💡 Entre 0.1 et 32 000 kg maximum
                        </small>
                    </div>

                    <!-- TYPE D'EXPÉDITION - BOUTONS AMÉLIORÉS -->
                    <div class="calc-form-group">
                        <label class="calc-form-label">
                            📋 Type d'expédition *
                        </label>
                        <div class="calc-type-buttons">
                            <button type="button" 
                                    class="calc-type-btn active" 
                                    data-type="colis">
                                <div class="calc-type-icon">📦</div>
                                <div class="calc-type-content">
                                    <h4>Colis</h4>
                                    <p>Envoi classique en carton</p>
                                    <small>Jusqu'à 1 tonne</small>
                                </div>
                            </button>
                            
                            <button type="button" 
                                    class="calc-type-btn" 
                                    data-type="palette">
                                <div class="calc-type-icon">🏗️</div>
                                <div class="calc-type-content">
                                    <h4>Palette(s)</h4>
                                    <p>Expédition sur palette EUR</p>
                                    <small>1 à 33 palettes</small>
                                </div>
                            </button>
                        </div>
                        <input type="hidden" id="type" name="type" value="colis">
                    </div>

                    <!-- PALETTES (masqué par défaut) -->
                    <div class="calc-form-group" id="palettesGroup" style="display: none;">
                        <label for="palettes" class="calc-form-label">
                            🏗️ Nombre de palettes EUR
                        </label>
                        <input type="number" 
                               id="palettes" 
                               name="palettes" 
                               class="calc-form-input"
                               min="1" 
                               max="33" 
                               value="1">
                        <small class="calc-form-hint">
                            💡 Palettes Europe standard (120x80cm)
                        </small>
                    </div>

                    <!-- PALETTES EUR CONSIGNÉES -->
                    <div class="calc-form-group" id="paletteEurGroup" style="display: none;">
                        <label for="palette_eur" class="calc-form-label">
                            🇪🇺 Palettes EUR consignées
                            <span class="calc-optional">- Facultatif</span>
                        </label>
                        <input type="number" 
                               id="palette_eur" 
                               name="palette_eur" 
                               class="calc-form-input"
                               min="0" 
                               value="0">
                        <small class="calc-form-hint">
                            💡 <strong>0 = palette perdue</strong> (économise 1,80€ de consigne par palette)
                        </small>
                    </div>
                </div>

                <!-- ÉTAPE 3: OPTIONS DE SERVICE AMÉLIORÉES -->
                <div class="calc-form-section">
                    <h3 class="calc-section-title">
                        <span class="calc-step-number">3</span>
                        ⚙️ Options de service
                    </h3>

                    <!-- ADR avec visual différent -->
                    <div class="calc-form-group">
                        <label class="calc-form-label">⚠️ Matières dangereuses (ADR)</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-adr="non">
                                <span class="calc-toggle-icon">✅</span>
                                <span>Non - Standard</span>
                            </button>
                            <button type="button" class="calc-toggle-btn calc-toggle-danger" data-adr="oui">
                                <span class="calc-toggle-icon">⚠️</span>
                                <span>Oui - ADR</span>
                                <small>+62€ forfait</small>
                            </button>
                        </div>
                        <input type="hidden" id="adr" name="adr" value="non">
                    </div>

                    <!-- OPTIONS SUPPLÉMENTAIRES avec visuels -->
                    <div class="calc-form-group">
                        <label class="calc-form-label">🚀 Options de livraison</label>
                        <div class="calc-service-options">
                            <label class="calc-service-option calc-service-standard">
                                <input type="radio" name="option_sup" value="standard" checked>
                                <div class="calc-service-content">
                                    <div class="calc-service-icon">🚚</div>
                                    <div class="calc-service-info">
                                        <h4>Standard</h4>
                                        <p>Livraison normale</p>
                                        <small class="calc-service-price">Inclus</small>
                                    </div>
                                </div>
                            </label>

                            <label class="calc-service-option calc-service-express">
                                <input type="radio" name="option_sup" value="express">
                                <div class="calc-service-content">
                                    <div class="calc-service-icon">⚡</div>
                                    <div class="calc-service-info">
                                        <h4>Express</h4>
                                        <p>Livraison prioritaire</p>
                                        <small class="calc-service-price">+30% à +90%</small>
                                    </div>
                                </div>
                            </label>

                            <label class="calc-service-option calc-service-premium">
                                <input type="radio" name="option_sup" value="premium">
                                <div class="calc-service-content">
                                    <div class="calc-service-icon">💎</div>
                                    <div class="calc-service-info">
                                        <h4>Premium</h4>
                                        <p>Service haut de gamme</p>
                                        <small class="calc-service-price">+50% à +150%</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ÉTAPE 4: ENLÈVEMENT (case séparée à la fin) -->
                <div class="calc-form-section">
                    <h3 class="calc-section-title">
                        <span class="calc-step-number">4</span>
                        🏭 Service d'enlèvement
                    </h3>
                    
                    <div class="calc-form-group">
                        <div class="calc-checkbox-group">
                            <label class="calc-checkbox-label">
                                <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                                <span class="calc-checkbox-custom"></span>
                                <div class="calc-checkbox-content">
                                    <h4>🚛 Enlèvement à domicile</h4>
                                    <p>Le transporteur vient récupérer votre colis</p>
                                    <small class="calc-checkbox-price">Payant selon transporteur</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- INFORMATIONS TARIFAIRES MANQUANTES -->
                <div class="calc-info-section">
                    <h4 class="calc-info-title">💡 Informations importantes</h4>
                    <div class="calc-info-list">
                        <div class="calc-info-item">
                            <span class="calc-info-icon">💰</span>
                            <span>Tous les tarifs sont HT (hors TVA)</span>
                        </div>
                        <div class="calc-info-item">
                            <span class="calc-info-icon">📦</span>
                            <span>Valeur déclarée conseillée : 100€ minimum</span>
                        </div>
                        <div class="calc-info-item">
                            <span class="calc-info-icon">🔒</span>
                            <span>Assurance incluse jusqu'à 762€ par colis</span>
                        </div>
                        <div class="calc-info-item">
                            <span class="calc-info-icon">⏱️</span>
                            <span>Délais indicatifs sous réserve de disponibilité</span>
                        </div>
                    </div>
                </div>

                <!-- BOUTON DE CALCUL -->
                <div class="calc-form-actions">
                    <button type="submit" class="calc-btn-primary" id="calculateBtn">
                        <span class="calc-btn-icon">🔍</span>
                        <span class="calc-btn-text">Calculer les frais de port</span>
                        <div class="calc-btn-loader" style="display: none;">
                            <div class="calc-spinner"></div>
                        </div>
                    </button>
                </div>
            </form>
        </div>

        <!-- PANNEAU RÉSULTATS AMÉLIORÉ -->
        <div class="calc-results-panel">
            <div class="calc-results-header">
                <h2 class="calc-results-title">
                    <span class="calc-results-icon">📊</span>
                    Comparaison des tarifs
                </h2>
                <div class="calc-results-status" id="resultsStatus">
                    En attente de calcul...
                </div>
            </div>

            <div class="calc-results-content" id="resultsContent">
                <!-- État vide par défaut -->
                <div class="calc-empty-state">
                    <div class="calc-empty-icon">🚛</div>
                    <h3 class="calc-empty-title">Prêt pour le calcul</h3>
                    <p class="calc-empty-text">
                        Remplissez le formulaire et cliquez sur "Calculer" pour comparer les tarifs de transport.
                    </p>
                    <div class="calc-empty-features">
                        <div class="calc-empty-feature">
                            <span class="calc-feature-icon">⚡</span>
                            <span>Comparaison instantanée</span>
                        </div>
                        <div class="calc-empty-feature">
                            <span class="calc-feature-icon">💰</span>
                            <span>Meilleur tarif mis en avant</span>
                        </div>
                        <div class="calc-empty-feature">
                            <span class="calc-feature-icon">🏆</span>
                            <span>3 transporteurs comparés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript amélioré -->
<script>
// Configuration et variables globales
const CalculatorApp = {
    form: null,
    currentType: 'colis',
    isCalculating: false,
    
    // Initialisation
    init() {
        this.form = document.getElementById('calculatorForm');
        this.setupEventListeners();
        this.setupTypeButtons();
        this.setupToggleButtons();
        this.setupServiceOptions();
        console.log('🚛 Calculateur de frais de port initialisé');
    },
    
    // Configuration des événements
    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Validation en temps réel
        document.getElementById('departement').addEventListener('input', this.validateDepartement);
        document.getElementById('poids').addEventListener('input', this.validatePoids);
    },
    
    // Gestion des boutons type (colis/palette)
    setupTypeButtons() {
        const typeButtons = document.querySelectorAll('.calc-type-btn');
        const palettesGroup = document.getElementById('palettesGroup');
        const paletteEurGroup = document.getElementById('paletteEurGroup');
        
        typeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                
                // Mise à jour UI
                typeButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Mise à jour champ hidden
                document.getElementById('type').value = type;
                this.currentType = type;
                
                // Affichage conditionnel des champs palettes
                if (type === 'palette') {
                    palettesGroup.style.display = 'block';
                    paletteEurGroup.style.display = 'block';
                } else {
                    palettesGroup.style.display = 'none';
                    paletteEurGroup.style.display = 'none';
                }
                
                console.log(`Type sélectionné: ${type}`);
            });
        });
    },
    
    // Gestion des boutons toggle (ADR)
    setupToggleButtons() {
        const toggleBtns = document.querySelectorAll('.calc-toggle-btn');
        
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const adrValue = btn.dataset.adr;
                
                // Mise à jour UI
                toggleBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Mise à jour champ hidden
                document.getElementById('adr').value = adrValue;
                
                console.log(`ADR: ${adrValue}`);
            });
        });
    },
    
    // Gestion des options de service
    setupServiceOptions() {
        const serviceOptions = document.querySelectorAll('input[name="option_sup"]');
        
        serviceOptions.forEach(option => {
            option.addEventListener('change', () => {
                console.log(`Option sélectionnée: ${option.value}`);
            });
        });
    },
    
    // Validation département
    validateDepartement(e) {
        const input = e.target;
        const value = input.value.trim();
        
        if (value.length >= 2 && /^[0-9]+$/.test(value)) {
            input.classList.add('valid');
            input.classList.remove('error');
        } else {
            input.classList.remove('valid');
            if (value.length > 0) input.classList.add('error');
        }
    },
    
    // Validation poids
    validatePoids(e) {
        const input = e.target;
        const value = parseFloat(input.value);
        
        if (value >= 0.1 && value <= 32000) {
            input.classList.add('valid');
            input.classList.remove('error');
        } else {
            input.classList.remove('valid');
            if (input.value.length > 0) input.classList.add('error');
        }
    },
    
    // Soumission du formulaire
    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isCalculating) return;
        
        try {
            this.isCalculating = true;
            this.showLoading();
            
            const formData = new FormData(this.form);
            const params = new URLSearchParams(formData).toString();
            
            const response = await fetch('?ajax=calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayResults(data.results, data.params);
            } else {
                this.showError(data.error || 'Erreur de calcul');
            }
            
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('Erreur de connexion');
        } finally {
            this.isCalculating = false;
            this.hideLoading();
        }
    },
    
    // Affichage du loading
    showLoading() {
        const btn = document.getElementById('calculateBtn');
        const text = btn.querySelector('.calc-btn-text');
        const loader = btn.querySelector('.calc-btn-loader');
        
        btn.disabled = true;
        text.style.display = 'none';
        loader.style.display = 'block';
        
        document.getElementById('resultsStatus').textContent = 'Calcul en cours...';
    },
    
    // Masquer le loading
    hideLoading() {
        const btn = document.getElementById('calculateBtn');
        const text = btn.querySelector('.calc-btn-text');
        const loader = btn.querySelector('.calc-btn-loader');
        
        btn.disabled = false;
        text.style.display = 'block';
        loader.style.display = 'none';
    },
    
    // Affichage des résultats AMÉLIORÉ
    displayResults(results, params) {
        const container = document.getElementById('resultsContent');
        const status = document.getElementById('resultsStatus');
        
        if (!results || results.length === 0) {
            this.showError('Aucun tarif disponible pour cette destination');
            return;
        }
        
        // Mettre à jour le statut
        status.innerHTML = `
            <span class="calc-status-icon">✅</span>
            ${results.length} tarif${results.length > 1 ? 's' : ''} trouvé${results.length > 1 ? 's' : ''} 
            pour ${params.departement} - ${params.poids}kg
        `;
        
        // Générer le HTML des résultats
        let html = '<div class="calc-carriers-list">';
        
        results.forEach((result, index) => {
            const isBest = result.is_best || index === 0;
            const isHidden = index > 0; // Masquer les autres sauf le meilleur
            
            html += `
                <div class="calc-carrier-result ${isBest ? 'calc-carrier-best' : ''}" 
                     style="${isHidden ? 'display: none;' : ''}" 
                     data-index="${index}">
                    
                    ${isBest ? '<div class="calc-best-badge">🏆 MEILLEUR TARIF</div>' : ''}
                    
                    <div class="calc-carrier-header">
                        <div class="calc-carrier-info">
                            <h3 class="calc-carrier-name">${result.transporteur || 'Transporteur'}</h3>
                            <div class="calc-carrier-service">${result.service || 'Service standard'}</div>
                        </div>
                        <div class="calc-carrier-price">
                            <div class="calc-price-amount">${result.prix_total?.toFixed(2) || '0.00'}€</div>
                            <div class="calc-price-label">HT</div>
                        </div>
                    </div>
                    
                    <div class="calc-carrier-details">
                        <div class="calc-detail-grid">
                            <div class="calc-detail-item">
                                <span class="calc-detail-label">Délai</span>
                                <span class="calc-detail-value">${result.delai || '1-2 jours'}</span>
                            </div>
                            <div class="calc-detail-item">
                                <span class="calc-detail-label">Type</span>
                                <span class="calc-detail-value">${params.type === 'palette' ? 'Palette' : 'Colis'}</span>
                            </div>
                            ${result.prix_base ? `
                                <div class="calc-detail-item">
                                    <span class="calc-detail-label">Base</span>
                                    <span class="calc-detail-value">${result.prix_base.toFixed(2)}€</span>
                                </div>
                            ` : '
