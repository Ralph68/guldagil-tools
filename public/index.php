<?php
// public/index.php - Nouvelle page d'accueil V2
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

// Authentification simple (désactivée en dev)
$auth_required = false; // Passer à true en production
$auth_password = 'GulPot';

if ($auth_required) {
    session_start();
    
    if (!isset($_SESSION['authenticated'])) {
        if ($_POST['password'] ?? '' === $auth_password) {
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Afficher la page de connexion
        include 'auth-login.php';
        exit;
    }
}

$transport = new Transport($db);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Guldagil - Outils logistiques</title>
    <link rel="stylesheet" href="assets/css/portail-v2.css">
</head>
<body>
    <!-- Header avec navigation claire -->
    <header class="main-header">
        <div class="header-container">
            <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
            <h1 class="header-title">Portail Guldagil</h1>
            
            <div class="header-actions">
                <a href="#calculateur" class="btn-nav calculateur active">
                    <span>🚚</span>
                    Calculateur
                </a>
                <a href="adr/" class="btn-nav adr">
                    <span>⚠️</span>
                    ADR
                </a>
                <a href="admin/" class="btn-nav admin">
                    <span>⚙️</span>
                    Admin
                </a>
                <?php if ($auth_required): ?>
                <a href="?logout=1" class="btn-nav logout" onclick="return confirm('Se déconnecter ?')">
                    <span>🚪</span>
                    Déconnexion
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Layout principal en colonnes -->
    <main class="main-container">
        <!-- Section calculateur (colonne principale) -->
        <section id="calculateur" class="calculator-section">
            <div class="calculator-header">
                <h2>🚚 Calculateur de frais de transport</h2>
                <p>Comparez instantanément Heppner, XPO et Kuehne+Nagel</p>
            </div>

            <div class="calculator-content">
                <!-- Messages d'erreur globaux -->
                <div id="error-container" class="error-container" style="display: none;"></div>

                <!-- Formulaire calculateur -->
                <form id="calc-form" class="calc-form">
                    <div class="form-row">
                        <!-- Département -->
                        <div class="form-field">
                            <label for="departement">📍 Département</label>
                            <input type="text" 
                                   id="departement" 
                                   placeholder="Ex: 67"
                                   maxlength="2" 
                                   pattern="\d{2}"
                                   required>
                            <div class="field-error" id="error-departement"></div>
                        </div>

                        <!-- Poids -->
                        <div class="form-field">
                            <label for="poids">⚖️ Poids (kg)</label>
                            <input type="number" 
                                   id="poids" 
                                   placeholder="Ex: 25"
                                   min="1" max="3500" step="1"
                                   required>
                            <div class="field-error" id="error-poids"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <!-- Type -->
                        <div class="form-field">
                            <label>📦 Type d'envoi</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="type" value="colis" id="type-colis" required>
                                    <span>Colis</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="type" value="palette" id="type-palette" required>
                                    <span>Palette</span>
                                </label>
                            </div>
                            <div class="field-error" id="error-type"></div>
                        </div>

                        <!-- ADR -->
                        <div class="form-field">
                            <label>⚠️ Marchandise dangereuse</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="adr" value="non" id="adr-non" required>
                                    <span>Non</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="adr" value="oui" id="adr-oui" required>
                                    <span>Oui</span>
                                </label>
                            </div>
                            <div class="field-error" id="error-adr"></div>
                        </div>
                    </div>

                    <!-- Options avancées (masquées par défaut) -->
                    <div id="advanced-options" class="advanced-options" style="display: none;">
                        <h3>Options supplémentaires</h3>
                        
                        <div class="form-row">
                            <div class="form-field">
                                <label for="option_sup">🚀 Livraison</label>
                                <select id="option_sup" name="option_sup">
                                    <option value="standard">Standard</option>
                                    <option value="rdv">Prise de RDV</option>
                                    <option value="premium13">Premium 13h</option>
                                    <option value="premium18">Premium 18h</option>
                                    <option value="datefixe">Date fixe</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="enlevement" name="enlevement" value="1">
                                    <span class="checkmark"></span>
                                    🏢 Enlèvement sur site
                                </label>
                            </div>

                            <!-- Palettes (affiché si type=palette) -->
                            <div class="form-field" id="palette-field" style="display: none;">
                                <label>🏗️ Nombre de palettes</label>
                                <div class="palette-buttons">
                                    <button type="button" class="palette-btn" data-value="1">1</button>
                                    <button type="button" class="palette-btn" data-value="2">2</button>
                                    <button type="button" class="palette-btn" data-value="3">3</button>
                                    <button type="button" class="palette-btn special" data-value="plus">4+</button>
                                </div>
                                <input type="hidden" id="palettes" name="palettes" value="1">
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Zone de chargement -->
                <div id="loading" class="loading-state" style="display: none;">
                    <div class="spinner"></div>
                    <span>Calcul en cours...</span>
                </div>

                <!-- Zone de résultat -->
                <div id="result-zone" class="result-zone">
                    <div class="result-placeholder">
                        <div class="placeholder-icon">🚀</div>
                        <p>Renseignez vos informations pour voir les tarifs</p>
                    </div>
                </div>

                <!-- Zone d'alertes seuils -->
                <div id="alerts-container" class="alerts-container"></div>

                <!-- Actions -->
                <div class="form-actions" id="form-actions" style="display: none;">
                    <button type="button" id="btn-compare" class="btn btn-secondary">📊 Comparer tous</button>
                    <button type="button" id="btn-historique" class="btn btn-secondary">📋 Historique</button>
                    <button type="button" id="btn-reset" class="btn btn-outline">🔄 Recommencer</button>
                </div>
            </div>
        </section>

        <!-- Sidebar info (colonne droite) -->
        <aside class="sidebar">
            <!-- Liens transporteurs (près du calculateur) -->
            <div class="info-card transporteurs-card">
                <div class="card-content">
                    <h3 style="color: var(--gul-blue-primary); margin-bottom: var(--spacing-md);">
                        🔗 Suivi des expéditions
                    </h3>
                    <p style="color: var(--gul-gray-500); font-size: 0.9rem; margin-bottom: var(--spacing-md);">
                        Accès direct aux portails transporteurs
                    </p>
                    <div class="transporteur-links">
                        <a href="https://myportal.heppner-group.com/home" target="_blank" class="transporteur-link heppner">
                            <span class="transporteur-icon">🚛</span>
                            <span>Portal Heppner</span>
                            <span class="external-icon">↗</span>
                        </a>
                        <a href="https://xpoconnecteu.xpo.com/customer/orders/list" target="_blank" class="transporteur-link xpo">
                            <span class="transporteur-icon">📦</span>
                            <span>XPO Connect</span>
                            <span class="external-icon">↗</span>
                        </a>
                        <a href="#" target="_blank" class="transporteur-link kn">
                            <span class="transporteur-icon">🌐</span>
                            <span>Kuehne+Nagel</span>
                            <span class="external-icon">↗</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Module ADR -->
            <div class="info-card adr-card">
                <div class="adr-header">
                    <h3>⚠️ Module ADR</h3>
                    <p>Marchandises dangereuses</p>
                </div>
                <div class="card-content">
                    <ul class="feature-list">
                        <li>Déclarations individuelles</li>
                        <li>Récapitulatifs quotidiens</li>
                        <li>Export PDF réglementaire</li>
                        <li>Gestion des quotas</li>
                    </ul>
                    <a href="adr/" class="btn-card btn-orange">
                        <span>🔐</span>
                        Accéder au module
                    </a>
                </div>
            </div>

            <!-- Support -->
            <div class="info-card support-card">
                <div class="card-content">
                    <h3 style="color: var(--gul-success); margin-bottom: var(--spacing-md);">💡 Besoin d'aide ?</h3>
                    <ul class="feature-list">
                        <li><strong>Logistique :</strong> achats@guldagil.com</li>
                        <li><strong>Support :</strong> runser.jean.thomas@guldagil.com</li>
                        <li><strong>Standard :</strong> 03 89 63 42 42</li>
                    </ul>
                </div>
            </div>
        </aside>
    </main>

    <!-- Footer simple -->
    <footer class="main-footer">
        <p>© 2025 Guldagil - Portail v2.0 - Usage interne</p>
    </footer>

    <!-- Modal historique -->
    <div id="historique-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 Historique des calculs</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="historique-content">
                <p>Chargement...</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" onclick="clearHistorique()">🗑️ Effacer</button>
                <button class="btn btn-secondary modal-close">Fermer</button>
            </div>
        </div>
    </div>

    <script src="assets/js/portail-calculateur.js"></script>
</body>
</html>

<?php
// Gestion de la déconnexion
if (isset($_GET['logout']) && $auth_required) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
