<?php
// public/calculateur/index.php - Module Calculateur complet
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

// Authentification (héritée du portail principal)
session_start();
$auth_required = false; // Synchronisé avec le portail principal

if ($auth_required && !isset($_SESSION['authenticated'])) {
    header('Location: ../');
    exit;
}

$transport = new Transport($db);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur de frais - Portail Guldagil</title>
    <link rel="stylesheet" href="../assets/css/portail-base.css">
    <link rel="stylesheet" href="../assets/css/calculateur-module.css">
</head>
<body>
    <!-- Header module -->
    <header class="module-header">
        <div class="header-container">
            <div class="header-brand">
                <a href="../" class="back-link" title="Retour à l'accueil">
                    <span>←</span>
                </a>
                <img src="../assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
                <div class="header-info">
                    <h1 class="module-title">Calculateur de frais</h1>
                    <p class="module-subtitle">Interface complète</p>
                </div>
            </div>
            
            <div class="header-actions">
                <button class="btn btn-outline" onclick="resetCalculator()">
                    <span>🔄</span>
                    Nouveau calcul
                </button>
                <button class="btn btn-secondary" onclick="showHistorique()">
                    <span>📋</span>
                    Historique
                </button>
                <div class="header-account">
                    <span class="account-info">👨‍💻 Dev</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Layout principal calculateur -->
    <main class="calculator-layout">
        <!-- Colonne formulaire (gauche) -->
        <section class="form-section">
            <div class="form-card">
                <div class="form-header">
                    <h2>🚚 Paramètres d'expédition</h2>
                    <p>Renseignez vos critères pour comparer les transporteurs</p>
                </div>

                <!-- Messages d'erreur -->
                <div id="error-container" class="error-container" style="display: none;"></div>

                <!-- Formulaire principal -->
                <form id="calculator-form" class="calculator-form">
                    <!-- Informations de base -->
                    <div class="form-section-group">
                        <h3>📍 Destination et poids</h3>
                        
                        <div class="form-row">
                            <div class="form-field">
                                <label for="departement">Département de livraison</label>
                                <input type="text" 
                                       id="departement" 
                                       placeholder="Ex: 67"
                                       maxlength="2" 
                                       pattern="\d{2}"
                                       required
                                       autocomplete="off">
                                <div class="field-help">2 chiffres (01 à 95)</div>
                                <div class="field-error" id="error-departement"></div>
                            </div>

                            <div class="form-field">
                                <label for="poids">Poids total (kg)</label>
                                <input type="number" 
                                       id="poids" 
                                       placeholder="Ex: 25"
                                       min="1" max="3500" step="0.1"
                                       required>
                                <div class="field-help">Maximum 3500 kg</div>
                                <div class="field-error" id="error-poids"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Type d'envoi -->
                    <div class="form-section-group">
                        <h3>📦 Type d'expédition</h3>
                        
                        <div class="radio-group-enhanced">
                            <label class="radio-card">
                                <input type="radio" name="type" value="colis" id="type-colis" required>
                                <div class="radio-content">
                                    <div class="radio-icon">📦</div>
                                    <div class="radio-info">
                                        <strong>Colis</strong>
                                        <span>Emballage individuel</span>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="radio-card">
                                <input type="radio" name="type" value="palette" id="type-palette" required>
                                <div class="radio-content">
                                    <div class="radio-icon">🏗️</div>
                                    <div class="radio-info">
                                        <strong>Palette</strong>
                                        <span>Sur support EUR</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Nombre de palettes (conditionnel) -->
                        <div id="palette-options" class="palette-options" style="display: none;">
                            <label>Nombre de palettes EUR</label>
                            <div class="palette-selector">
                                <button type="button" class="palette-btn" data-value="1">1</button>
                                <button type="button" class="palette-btn" data-value="2">2</button>
                                <button type="button" class="palette-btn" data-value="3">3</button>
                                <button type="button" class="palette-btn special" data-value="contact">4+</button>
                            </div>
                            <input type="hidden" id="palettes" name="palettes" value="1">
                            <div class="field-help">Au-delà de 3 palettes, contactez le service achat</div>
                        </div>
                    </div>

                    <!-- ADR -->
                    <div class="form-section-group">
                        <h3>⚠️ Marchandises dangereuses (ADR)</h3>
                        
                        <div class="radio-group-enhanced">
                            <label class="radio-card">
                                <input type="radio" name="adr" value="non" id="adr-non" required>
                                <div class="radio-content">
                                    <div class="radio-icon">✅</div>
                                    <div class="radio-info">
                                        <strong>Non ADR</strong>
                                        <span>Marchandise standard</span>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="radio-card adr-warning">
                                <input type="radio" name="adr" value="oui" id="adr-oui" required>
                                <div class="radio-content">
                                    <div class="radio-icon">⚠️</div>
                                    <div class="radio-info">
                                        <strong>ADR</strong>
                                        <span>Marchandise dangereuse</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Options avancées -->
                    <div class="form-section-group">
                        <h3>🚀 Options de livraison</h3>
                        
                        <div class="form-row">
                            <div class="form-field">
                                <label for="option_sup">Service de livraison</label>
                                <select id="option_sup" name="option_sup">
                                    <option value="standard">Livraison standard</option>
                                    <option value="rdv">Prise de rendez-vous</option>
                                    <option value="premium13">Premium avant 13h</option>
                                    <option value="premium18">Premium avant 18h</option>
                                    <option value="datefixe">Date fixe imposée</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="checkbox-enhanced">
                                    <input type="checkbox" id="enlevement" name="enlevement" value="1">
                                    <div class="checkbox-content">
                                        <div class="checkbox-icon">🏢</div>
                                        <div class="checkbox-info">
                                            <strong>Enlèvement</strong>
                                            <span>Collecte sur votre site</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions formulaire -->
                    <div class="form-actions">
                        <button type="button" id="btn-calculate" class="btn btn-primary btn-large">
                            <span>🚀</span>
                            Calculer les tarifs
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <span>🔄</span>
                            Réinitialiser
                        </button>
                    </div>
                </form>
            </div>

            <!-- Conseils utilisateur -->
            <div class="tips-card">
                <h3>💡 Conseils d'utilisation</h3>
                <ul class="tips-list">
                    <li><strong>Poids > 60kg :</strong> Privilégiez la palette</li>
                    <li><strong>Alertes seuils :</strong> Regardez les suggestions "payant pour"</li>
                    <li><strong>ADR :</strong> Utilisez le module dédié pour les déclarations</li>
                    <li><strong>Options :</strong> L'enlèvement désactive les options de livraison</li>
                </ul>
                
                <div class="contact-info">
                    <strong>Support :</strong><br>
                    📧 achats@guldagil.com<br>
                    📞 03 89 63 42 42
                </div>
            </div>
        </section>

        <!-- Colonne résultats (droite) -->
        <section class="results-section">
            <!-- Zone de chargement -->
            <div id="loading-zone" class="loading-zone" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
            </div>

            <!-- Zone de résultat principal -->
            <div id="result-main" class="result-card">
                <div class="result-header">
                    <h3>💰 Votre tarif</h3>
                    <div class="result-status" id="result-status">En attente</div>
                </div>
                
                <div class="result-body">
                    <div id="result-content" class="result-content">
                        <div class="result-placeholder">
                            <div class="placeholder-icon">🚀</div>
                            <h4>Prêt à calculer</h4>
                            <p>Renseignez le formulaire pour voir les tarifs de nos transporteurs partenaires</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone d'alertes seuils -->
            <div id="alerts-zone" class="alerts-zone" style="display: none;">
                <h4>⚡ Optimisations possibles</h4>
                <div id="alerts-content"></div>
            </div>

            <!-- Zone de comparaison -->
            <div id="comparison-zone" class="comparison-zone" style="display: none;">
                <h4>📊 Comparaison transporteurs</h4>
                <div id="comparison-content"></div>
            </div>

            <!-- Actions rapides -->
            <div id="quick-actions" class="quick-actions" style="display: none;">
                <button class="btn btn-secondary" onclick="showDetailedComparison()">
                    <span>📈</span>
                    Analyse détaillée
                </button>
                <button class="btn btn-secondary" onclick="exportCalculation()">
                    <span>📄</span>
                    Exporter PDF
                </button>
                <button class="btn btn-info" onclick="showHistorique()">
                    <span>📋</span>
                    Historique
                </button>
            </div>
        </section>
    </main>

    <!-- Modal historique -->
    <div id="historique-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 Historique des calculs</h3>
                <span class="modal-close" onclick="closeModal('historique-modal')">&times;</span>
            </div>
            <div class="modal-body" id="historique-content">
                <div class="loading-placeholder">Chargement...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" onclick="clearHistorique()">🗑️ Effacer</button>
                <button class="btn btn-secondary" onclick="closeModal('historique-modal')">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/calculateur-module.js"></script>
</body>
</html>
