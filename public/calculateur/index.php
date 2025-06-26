<?php
/**
 * Titre: Interface calculateur - Architecture MVC
 * Chemin: /public/calculateur/index.php
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
    
    <!-- CSS UNIFIÉ - UN SEUL FICHIER -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-unified.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="calculateur-app">
    
    <!-- Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                
                <!-- Marque -->
                <div class="header-brand">
                    <a href="../index.php" class="brand-link">
                        <img src="../assets/img/logo_guldagil.png" alt="Logo Guldagil" class="brand-logo">
                        <div class="brand-info">
                            <h1 class="brand-title">Calculateur de Frais de Port</h1>
                            <p class="brand-subtitle">Interface progressive • Temps réel</p>
                        </div>
                    </a>
                </div>
                
                <!-- Status et navigation -->
                <div class="header-status">
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        <span class="status-text">Connecté • <span id="calc-counter">0</span> calculs</span>
                    </div>
                    
                    <div class="header-actions">
                        <a href="../admin/index.php" class="header-btn" title="Administration">
                            <span class="btn-icon">⚙️</span>
                            <span class="btn-text">Admin</span>
                        </a>
                        <a href="../index.php" class="header-btn" title="Retour au portail">
                            <span class="btn-icon">🏠</span>
                            <span class="btn-text">Portail</span>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </header>
    
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
                            <div class="form-content">
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
                                        <label for="type_envoi" class="field-label">Type d'envoi</label>
                                        <select id="type_envoi" name="type_envoi" class="form-control form-select auto-calc" required>
                                            <option value="">Sélectionner...</option>
                                            <option value="colis">📦 Colis</option>
                                            <option value="palette">🏗️ Palette</option>
                                            <option value="express">⚡ Express</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="nb_palettes" class="field-label">Nombre palettes EUR</label>
                                        <input type="number" 
                                               id="nb_palettes" 
                                               name="nb_palettes" 
                                               class="form-control auto-calc" 
                                               min="0" 
                                               max="33" 
                                               value="0" 
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Section ADR -->
                        <section class="form-section">
                            <h2 class="section-title">⚠️ Marchandises dangereuses (ADR)</h2>
                            <div class="form-content">
                                <div class="adr-section">
                                    <p class="adr-title">🚛 ADR : transport de marchandises dangereuses par route</p>
                                    <div class="adr-options">
                                        <label class="adr-option">
                                            <input type="radio" name="adr" value="non" class="adr-radio auto-calc" checked>
                                            <div>
                                                <div class="adr-label">❌ Non</div>
                                                <div class="adr-description">Marchandises classiques</div>
                                            </div>
                                        </label>
                                        
                                        <label class="adr-option">
                                            <input type="radio" name="adr" value="oui" class="adr-radio auto-calc">
                                            <div>
                                                <div class="adr-label">⚠️ Oui</div>
                                                <div class="adr-description">+65€ HT - Supplément ADR</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Section Options de livraison -->
                        <section class="form-section">
                            <h2 class="section-title">🚚 Options de livraison</h2>
                            <div class="form-content">
                                <p class="text-muted mb-4">Sélection unique - Impact calculé dynamiquement</p>
                                <div class="delivery-options">
                                    
                                    <label class="delivery-option">
                                        <input type="radio" name="service_livraison" value="standard" class="visually-hidden auto-calc" checked>
                                        <div class="option-icon">📦</div>
                                        <div class="option-title">Standard</div>
                                        <div class="option-subtitle">24-48h</div>
                                        <div class="option-description">Tarif de base</div>
                                    </label>
                                    
                                    <label class="delivery-option">
                                        <input type="radio" name="service_livraison" value="premium_13h" class="visually-hidden auto-calc">
                                        <div class="option-icon">⚡</div>
                                        <div class="option-title">Premium 13h</div>
                                        <div class="option-subtitle">Avant 13h</div>
                                        <div class="option-description">Impact calculé</div>
                                    </label>
                                    
                                    <label class="delivery-option">
                                        <input type="radio" name="service_livraison" value="premium_18h" class="visually-hidden auto-calc">
                                        <div class="option-icon">🕕</div>
                                        <div class="option-title">Premium 18h</div>
                                        <div class="option-subtitle">Avant 18h</div>
                                        <div class="option-description">Impact calculé</div>
                                    </label>
                                    
                                    <label class="delivery-option">
                                        <input type="radio" name="service_livraison" value="rdv" class="visually-hidden auto-calc">
                                        <div class="option-icon">📅</div>
                                        <div class="option-title">RDV</div>
                                        <div class="option-subtitle">Prise RDV</div>
                                        <div class="option-description">Impact calculé</div>
                                    </label>
                                    
                                </div>
                                
                                <!-- Option enlèvement -->
                                <div class="mt-4">
                                    <label class="adr-option">
                                        <input type="checkbox" name="enlevement_expediteur" value="1" class="adr-radio auto-calc">
                                        <div>
                                            <div class="adr-label">🏗️ Enlèvement sur site expéditeur</div>
                                            <div class="adr-description">Coches pour ajouter l'enlèvement sur site expéditeur</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </section>
                        
                    </form>
                </div>
                
                <!-- Panel des résultats -->
                <div class="results-panel">
                    <div class="results-container">
                        
                        <!-- Header résultats -->
                        <div class="results-header">
                            <h3 class="results-title">📊 Comparaison des tarifs</h3>
                            <p class="results-subtitle">Saisissez vos critères pour voir les tarifs</p>
                        </div>
                        
                        <!-- Contenu résultats -->
                        <div class="results-content">
                            
                            <!-- État initial -->
                            <div id="results-empty" class="text-center text-muted">
                                <div class="mb-4">🎯</div>
                                <p>Complétez le formulaire pour obtenir une comparaison détaillée des frais de port.</p>
                            </div>
                            
                            <!-- État de chargement -->
                            <div id="results-loading" class="loading" style="display: none;">
                                Calcul en cours...
                            </div>
                            
                            <!-- Meilleur tarif -->
                            <div id="best-rate" class="best-rate" style="display: none;">
                                <div class="best-rate-title">🏆 Meilleur tarif</div>
                                <div class="best-rate-price" id="best-price">-</div>
                                <div class="best-rate-carrier" id="best-carrier">-</div>
                            </div>
                            
                            <!-- Comparaison transporteurs -->
                            <div id="carriers-comparison" class="carriers-results" style="display: none;">
                                <!-- Résultats générés dynamiquement -->
                            </div>
                            
                            <!-- Affectement si nécessaire -->
                            <div id="affretement-notice" class="alert alert-warning" style="display: none;">
                                <strong>⚠️ Affrètement nécessaire</strong><br>
                                Votre envoi nécessite un affrètement. Contactez-nous pour un devis personnalisé.
                            </div>
                            
                            <!-- Erreurs -->
                            <div id="calculation-error" class="alert alert-error" style="display: none;">
                                <strong>❌ Erreur de calcul</strong><br>
                                <span id="error-message">Une erreur est survenue lors du calcul.</span>
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
                <div>© <?= date('Y') ?> Guldagil - Tous droits réservés</div>
                <div>
                    <?= $version_info['version'] ?> 
                    • Build <?= $version_info['build'] ?>
                    • <?= $version_info['date'] ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        /**
         * Calculateur de frais de port - Interface JavaScript
         * Version: 0.5 beta + build
         */
        
        class ShippingCalculator {
            constructor() {
                this.form = document.getElementById('calc-form');
                this.counter = document.getElementById('calc-counter');
                this.calcCount = 0;
                this.debounceTimer = null;
                
                this.init();
            }
            
            init() {
                // Écouteurs d'événements
                this.form.addEventListener('change', (e) => {
                    if (e.target.classList.contains('auto-calc')) {
                        this.debounceCalculation();
                    }
                });
                
                this.form.addEventListener('input', (e) => {
                    if (e.target.classList.contains('auto-calc')) {
                        this.debounceCalculation();
                    }
                });
                
                // Gestion des options de livraison
                this.initDeliveryOptions();
                this.initAdrOptions();
                
                console.log('📊 Calculateur initialisé');
            }
            
            initDeliveryOptions() {
                const options = document.querySelectorAll('.delivery-option');
                options.forEach(option => {
                    option.addEventListener('click', () => {
                        // Retirer la sélection précédente
                        options.forEach(opt => opt.classList.remove('selected'));
                        // Ajouter la sélection actuelle
                        option.classList.add('selected');
                        // Cocher le radio bouton
                        const radio = option.querySelector('input[type="radio"]');
                        if (radio) radio.checked = true;
                        // Déclencher le calcul
                        this.debounceCalculation();
                    });
                });
            }
            
            initAdrOptions() {
                const options = document.querySelectorAll('.adr-option');
                options.forEach(option => {
                    option.addEventListener('click', () => {
                        // Gérer la sélection visuelle pour les radios ADR
                        const radio = option.querySelector('input[type="radio"]');
                        if (radio) {
                            options.forEach(opt => opt.classList.remove('selected'));
                            option.classList.add('selected');
                        } else {
                            // Pour les checkboxes, toggle la sélection
                            option.classList.toggle('selected');
                        }
                    });
                });
            }
            
            debounceCalculation() {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.calculateRates();
                }, 300);
            }
            
            async calculateRates() {
                // Vérifier que les champs requis sont remplis
                const formData = new FormData(this.form);
                const departement = formData.get('departement');
                const poids = formData.get('poids');
                const type_envoi = formData.get('type_envoi');
                
                if (!departement || !poids || !type_envoi) {
                    this.showEmptyState();
                    return;
                }
                
                // Afficher l'état de chargement
                this.showLoading();
                
                try {
                    // Préparer les données
                    const data = {
                        departement: departement,
                        poids: parseFloat(poids),
                        type: type_envoi,
                        adr: formData.get('adr') || 'non',
                        service_livraison: formData.get('service_livraison') || 'standard',
                        nb_palettes: parseInt(formData.get('nb_palettes')) || 0,
                        enlevement_expediteur: formData.get('enlevement_expediteur') ? 1 : 0
                    };
                    
                    // Appel API
                    const response = await fetch('ajax-calculate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.displayResults(result);
                        this.updateCounter();
                    } else {
                        this.showError(result.message || 'Erreur de calcul');
                    }
                    
                } catch (error) {
                    console.error('Erreur calcul:', error);
                    this.showError('Erreur de connexion au serveur');
                }
            }
            
            displayResults(result) {
                // Cacher les autres états
                this.hideAllStates();
                
                if (result.affretement) {
                    document.getElementById('affretement-notice').style.display = 'block';
                    return;
                }
                
                // Afficher le meilleur tarif
                if (result.best_rate) {
                    const bestRate = document.getElementById('best-rate');
                    document.getElementById('best-price').textContent = result.best_rate.price_display;
                    document.getElementById('best-carrier').textContent = result.best_rate.carrier_name;
                    bestRate.style.display = 'block';
                }
                
                // Afficher la comparaison
                const comparison = document.getElementById('carriers-comparison');
                comparison.innerHTML = '';
                
                result.carriers.forEach(carrier => {
                    const card = this.createCarrierCard(carrier, result.best_rate);
                    comparison.appendChild(card);
                });
                
                comparison.style.display = 'block';
            }
            
            createCarrierCard(carrier, bestRate) {
                const card = document.createElement('div');
                card.className = 'carrier-card';
                
                if (!carrier.available) {
                    card.classList.add('unavailable');
                }
                
                if (bestRate && carrier.carrier_code === bestRate.carrier_code) {
                    card.classList.add('best-price');
                }
                
                card.innerHTML = `
                    <div class="carrier-info">
                        <h4>${carrier.carrier_name}</h4>
                        <p>${carrier.service_description || ''}</p>
                    </div>
                    <div class="carrier-price ${carrier.available ? '' : 'unavailable'}">
                        ${carrier.available ? carrier.price_display : 'Non disponible'}
                        ${bestRate && carrier.carrier_code === bestRate.carrier_code ? '<span class="best-price-badge">Meilleur prix</span>' : ''}
                    </div>
                `;
                
                return card;
            }
            
            showLoading() {
                this.hideAllStates();
                document.getElementById('results-loading').style.display = 'block';
            }
            
            showEmptyState() {
                this.hideAllStates();
                document.getElementById('results-empty').style.display = 'block';
            }
            
            showError(message) {
                this.hideAllStates();
                document.getElementById('error-message').textContent = message;
                document.getElementById('calculation-error').style.display = 'block';
            }
            
            hideAllStates() {
                document.getElementById('results-empty').style.display = 'none';
                document.getElementById('results-loading').style.display = 'none';
                document.getElementById('best-rate').style.display = 'none';
                document.getElementById('carriers-comparison').style.display = 'none';
                document.getElementById('affretement-notice').style.display = 'none';
                document.getElementById('calculation-error').style.display = 'none';
            }
            
            updateCounter() {
                this.calcCount++;
                this.counter.textContent = this.calcCount;
                this.counter.classList.add('updating');
                setTimeout(() => {
                    this.counter.classList.remove('updating');
                }, 300);
            }
        }
        
        // Classes utilitaires
        const visibilityHidden = `
            .visually-hidden {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0, 0, 0, 0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }
        `;
        
        // Injection du CSS
        const style = document.createElement('style');
        style.textContent = visibilityHidden;
        document.head.appendChild(style);
        
        // Initialisation au chargement
        document.addEventListener('DOMContentLoaded', () => {
            new ShippingCalculator();
        });
    </script>
</body>
</html>
