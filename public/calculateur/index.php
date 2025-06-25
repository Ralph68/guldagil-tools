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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- CSS modulaires séparés -->
<link rel="stylesheet" href="..assets/css/modules/calculateur/modern-interface.css>
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/ux-improvements.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
</head>
<body class="calculateur-app">
    
    <!-- Header modulaire -->
    <?php include __DIR__ . '/views/partials/header.php'; ?>
    
    <!-- Contenu principal -->
    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                
                <!-- Formulaire principal -->
                <div class="form-panel">
                    <form id="calc-form" class="calc-form" autocomplete="off">
                        
                        <!-- Section informations de base -->
                        <section class="form-section">
                            <h2 class="section-title">📍 Informations de base</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="departement" class="field-label">Département destination</label>
                                    <input type="number" 
                                           id="departement" 
                                           name="departement" 
                                           class="form-control auto-calc" 
                                           min="1" 
                                           max="99" 
                                           placeholder="Ex: 75" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="poids" class="field-label">Poids total (kg)</label>
                                    <input type="number" 
                                           id="poids" 
                                           name="poids" 
                                           class="form-control auto-calc" 
                                           min="0.1" 
                                           step="0.1" 
                                           placeholder="Ex: 25.5" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="type" class="field-label">Type d'envoi</label>
                                    <select id="type" 
                                            name="type" 
                                            class="form-control auto-calc" 
                                            required>
                                        <option value="">Sélectionner...</option>
                                        <option value="colis">Colis</option>
                                        <option value="palette">Palette</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="palettes" class="field-label">Nombre palettes EUR</label>
                                    <input type="number" 
                                           id="palettes" 
                                           name="palettes" 
                                           class="form-control auto-calc" 
                                           min="0" 
                                           value="0" 
                                           placeholder="0">
                                </div>
                            </div>
                        </section>
                        
                        <!-- Section ADR améliorée -->
                        <section class="form-section adr-section">
                            <h3 class="section-title">⚠️ Marchandises dangereuses (ADR)</h3>
                            <div class="radio-buttons">
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="non" checked class="auto-calc">
                                    <div class="radio-content">
                                        <span>❌ Non</span>
                                    </div>
                                </label>
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="oui" class="auto-calc">
                                    <div class="radio-content">
                                        <span>⚠️ Oui</span>
                                        <small>+62€ HT</small>
                                    </div>
                                </label>
                            </div>
                            <div class="field-help">
                                ADR : transport de marchandises dangereuses par route
                            </div>
                        </section>
                        
                        <!-- Section Options mutuellement exclusives -->
                        <section class="form-section options-section">
                            <h3 class="section-title">🚀 Options de livraison</h3>
                            <p class="section-subtitle">Sélection unique - Impact calculé dynamiquement</p>
                            <div class="options-grid">
                                <div class="option-card selected" data-option="standard">
                                    <div class="option-title">Standard</div>
                                    <div class="option-description">24-48h</div>
                                    <div class="option-impact">Tarif de base</div>
                                </div>
                                
                                <div class="option-card" data-option="premium13">
                                    <div class="option-title">Premium 13h</div>
                                    <div class="option-description">Avant 13h</div>
                                    <div class="option-impact">Impact calculé</div>
                                </div>
                                
                                <div class="option-card" data-option="premium18">
                                    <div class="option-title">Premium 18h</div>
                                    <div class="option-description">Avant 18h</div>
                                    <div class="option-impact">Impact calculé</div>
                                </div>
                                
                                <div class="option-card" data-option="rdv">
                                    <div class="option-title">RDV</div>
                                    <div class="option-description">Prise RDV</div>
                                    <div class="option-impact">Impact calculé</div>
                                </div>
                            </div>
                            <input type="hidden" name="service_livraison" value="standard">
                        </section>
                        
                        <!-- Section Enlèvement séparée -->
                        <section class="form-section enlevement-section" id="enlevement-section">
                            <div class="checkbox-container">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="enlevement" 
                                           value="oui" 
                                           class="auto-calc" 
                                           id="enlevement-checkbox">
                                    <span class="label-text">
                                        🏭 Enlèvement sur site expéditeur
                                    </span>
                                </label>
                            </div>
                            <div class="field-help" id="enlevement-help">
                                Disponible uniquement avec livraison standard
                            </div>
                        </section>
                        
                    </form>
                </div>
                
                <!-- Panneau résultats -->
                <div class="results-panel">
                    <div class="results-container" id="results-container">
                        <div class="results-header">
                            <h2>📊 Comparaison des tarifs</h2>
                            <div class="calculation-status" id="calculation-status">
                                Saisissez vos critères pour voir les tarifs
                            </div>
                        </div>
                        
                        <div class="results-content" id="results-content">
                            <div class="results-placeholder">
                                <div class="placeholder-icon">🧮</div>
                                <p>Les tarifs s'afficheront automatiquement lors de la saisie</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>
    
    <!-- Footer modulaire -->
    <?php include __DIR__ . '/views/partials/footer.php'; ?>
    
    <!-- JavaScript modulaires séparés -->
    
    <!-- 1. Configuration et utilitaires -->
    <script src="../assets/js/modules/calculateur/core/calculateur-config.js"></script>
    <script src="../assets/js/modules/calculateur/utils/helpers.js"></script>
    
    <!-- 2. Services -->
    <script src="../assets/js/modules/calculateur/core/api-service.js"></script>
    
    <!-- 3. Modèles -->
    <script src="../assets/js/modules/calculateur/models/form-data-model.js"></script>
    <script src="../assets/js/modules/calculateur/models/validation-model.js"></script>
    
    <!-- 4. Contrôleurs -->
    <script src="../assets/js/modules/calculateur/controllers/form-controller.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/calculation-controller.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/ui-controller.js"></script>
    
    <!-- 5. Vues -->
    <script src="../assets/js/modules/calculateur/views/progressive-form-view.js"></script>
    <script src="../assets/js/modules/calculateur/views/results-display-view.js"></script>
    
    <!-- 6. Bootstrap du module -->
    <script src="../assets/js/modules/calculateur/core/module-boot.js"></script>
    
    <!-- 7. Logique UX spécifique -->
    <script src="../assets/js/modules/calculateur/ux-enlevement.js"></script>
    
    <!-- 8. Initialisation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initialisation calculateur MVC');
            
            // Configuration initiale
            if (typeof CalculateurConfig !== 'undefined') {
                CalculateurConfig.log('info', 'Configuration chargée');
            }
            
            // Boot du module si disponible
            if (window.CalculateurModuleBoot) {
                window.CalculateurModuleBoot.init().then(() => {
                    console.log('✅ Module calculateur initialisé');
                }).catch(error => {
                    console.error('❌ Erreur initialisation:', error);
                    // Mode de secours
                    initFallbackMode();
                });
            } else {
                // Mode de secours simple
                initFallbackMode();
            }
        });
        
        /**
         * Mode de secours si modules avancés non disponibles
         */
        function initFallbackMode() {
            console.log('🔄 Mode de secours activé');
            
            // Gestion basique du formulaire
            const form = document.getElementById('calc-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    calculateSimple();
                });
                
                // Auto-calcul basique
                form.addEventListener('input', debounce(calculateSimple, 800));
                form.addEventListener('change', debounce(calculateSimple, 800));
            }
            
            // Gestion options/enlèvement
            setupBasicOptionLogic();
        }
        
        /**
         * Logique basique options/enlèvement
         */
        function setupBasicOptionLogic() {
            // Options mutuellement exclusives
            document.querySelectorAll('.option-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    document.querySelector('input[name="service_livraison"]').value = this.dataset.option;
                    updateEnlevementBasic();
                });
            });
            
            // Enlèvement = standard uniquement
            updateEnlevementBasic();
        }
        
        /**
         * Mise à jour enlèvement basique
         */
        function updateEnlevementBasic() {
            const selectedOption = document.querySelector('.option-card.selected')?.dataset.option;
            const enlevementCheckbox = document.getElementById('enlevement-checkbox');
            const enlevementSection = document.getElementById('enlevement-section');
            
            if (selectedOption === 'standard') {
                enlevementCheckbox.disabled = false;
                enlevementSection.classList.remove('disabled');
            } else {
                enlevementCheckbox.disabled = true;
                enlevementCheckbox.checked = false;
                enlevementSection.classList.add('disabled');
            }
        }
        
        /**
         * Calcul simple
         */
        async function calculateSimple() {
            const formData = new FormData(document.getElementById('calc-form'));
            
            // Validation basique
            if (!formData.get('departement') || !formData.get('poids') || !formData.get('type')) {
                return;
            }
            
            try {
                const response = await fetch('ajax-calculate.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                displayResultsSimple(result);
                
            } catch (error) {
                console.error('Erreur calcul:', error);
                displayError('Erreur de calcul');
            }
        }
        
        /**
         * Affichage résultats simple
         */
        function displayResultsSimple(result) {
            const container = document.getElementById('results-content');
            
            if (result.success && result.best_rate) {
                container.innerHTML = `
                    <div class="best-rate">
                        <h3>🏆 Meilleur tarif</h3>
                        <div class="best-price">${result.best_rate.formatted}</div>
                        <div class="best-carrier">${result.best_rate.carrier_name}</div>
                    </div>
                    <div class="comparison">
                        ${Object.entries(result.carriers).map(([carrier, data]) => `
                            <div class="carrier-row ${carrier === result.best_rate.carrier ? 'best' : ''}">
                                <span>${data.name}</span>
                                <span>${data.formatted}</span>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                displayError(result.message || 'Aucun tarif disponible');
            }
        }
        
        /**
         * Affichage erreur
         */
        function displayError(message) {
            document.getElementById('results-content').innerHTML = `
                <div class="error-message">❌ ${message}</div>
            `;
        }
        
        /**
         * Debounce utilitaire
         */
        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }
    </script>
    
</body>
</html>
