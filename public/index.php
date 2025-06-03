<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comparateur de frais de port - Guldagil</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <!-- Header simple (non-fixe) -->
  <header class="main-header">
    <div class="header-container">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
      <h1 class="header-title">Calculateur de frais de port</h1>
      <div class="header-actions">
        <button class="btn-header" onclick="showHistorique()" title="Historique">📋</button>
        <a href="admin/" class="btn-header" title="Administration">⚙️</a>
      </div>
    </div>
  </header>

  <!-- Container principal avec grid -->
  <main class="main-container">
    <!-- Colonne formulaire (scrollable) -->
    <div class="form-column">
      <div class="calculator-card">
        <div class="calculator-header">
          <h2>🚚 Calculer vos frais de transport</h2>
          <p>Comparez instantanément nos transporteurs partenaires</p>
        </div>

        <!-- Messages d'erreur globaux -->
        <div id="error-container" class="error-container"></div>

        <!-- Formulaire simplifié -->
        <form id="calc-form" class="calc-form">
          <div class="form-row">
            <!-- Département -->
            <div class="form-field">
              <label for="departement">📍 Département</label>
              <input type="text" 
                     id="departement" 
                     placeholder="Ex: 67"
                     maxlength="2" 
                     pattern="\d{2}">
              <div class="field-error" id="error-departement"></div>
            </div>

            <!-- Poids -->
            <div class="form-field">
              <label for="poids">⚖️ Poids (kg)</label>
              <input type="number" 
                     id="poids" 
                     placeholder="Ex: 25"
                     min="1" max="3500" step="1"
                     oninput="this.value = Math.floor(this.value)">
              <div class="field-error" id="error-poids"></div>
            </div>

            <!-- Type -->
            <div class="form-field">
              <label>📦 Type</label>
              <div class="radio-inline">
                <label class="radio-label">
                  <input type="radio" name="type" value="colis" id="type-colis">
                  <span class="radio-text">Colis</span>
                </label>
                <label class="radio-label">
                  <input type="radio" name="type" value="palette" id="type-palette">
                  <span class="radio-text">Palette</span>
                </label>
              </div>
              <div class="field-error" id="error-type"></div>
            </div>

            <!-- ADR -->
            <div class="form-field">
              <label>⚠️ ADR</label>
              <div class="radio-inline">
                <label class="radio-label">
                  <input type="radio" name="adr" value="non" id="adr-non">
                  <span class="radio-text">Non</span>
                </label>
                <label class="radio-label">
                  <input type="radio" name="adr" value="oui" id="adr-oui">
                  <span class="radio-text">Oui</span>
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
                <label>🚀 Livraison</label>
                <select id="option_sup" name="option_sup">
                  <option value="standard">Standard</option>
                  <option value="rdv">Prise de RDV</option>
                  <option value="premium13">Premium 13h</option>
                  <option value="premium18">Premium 18h</option>
                  <option value="datefixe">Date fixe</option>
                </select>
              </div>

              <div class="form-field">
                <label>
                  <input type="checkbox" id="enlevement" name="enlevement" value="1">
                  🏢 Enlèvement sur site
                </label>
              </div>

              <!-- Palettes (affiché si type=palette) -->
              <div class="form-field" id="palette-field" style="display: none;">
                <label>🏗️ Nb palettes</label>
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

          <!-- Actions -->
          <div class="form-actions">
            <button type="button" id="btn-reset" class="btn-secondary">🔄 Recommencer</button>
          </div>
        </form>

        <!-- Section d'aide compacte -->
        <div class="help-section">
          <h3>💡 Besoin d'aide ?</h3>
          <div class="help-cards">
            <div class="help-card">
              <strong>📦 Logistique</strong>
              <span>achats@guldagil.com</span>
            </div>
            <div class="help-card">
              <strong>🐛 Support</strong>
              <span>runser.jean.thomas@guldagil.com</span>
            </div>
            <div class="help-card">
              <strong>📞 Standard</strong>
              <span>03 89 63 42 42</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Colonne résultat (FIXE - toujours visible) -->
    <div class="result-column">
      <div class="result-card">
        <div class="result-header">
          <h3>💰 Votre tarif</h3>
          <div class="result-status" id="result-status">
            En attente...
          </div>
        </div>

        <div class="result-body">
          <div id="loading" class="loading-state" style="display: none;">
            <div class="spinner"></div>
            <span>Calcul en cours...</span>
          </div>
          
          <div id="result-content" class="result-content">
            <div class="result-placeholder">
              <div class="placeholder-icon">🚀</div>
              <p>Renseignez vos informations pour voir les tarifs</p>
            </div>
          </div>

          <!-- Zone d'alertes seuils -->
          <div id="alerts-container" class="alerts-container"></div>

          <!-- Actions rapides -->
          <div class="result-actions" id="result-actions" style="display: none;">
            <button class="btn-primary" id="btn-compare">📊 Comparer tous</button>
            <button class="btn-secondary" onclick="showHistorique()">📋 Historique</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer simple -->
  <footer class="main-footer">
    <p>© 2025 Guldagil - Calculateur v1.2.0 - Usage interne</p>
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
        <button class="btn-danger" onclick="clearHistorique()">🗑️ Effacer</button>
      </div>
    </div>
  </div>

  <script src="assets/js/guided-calculator.js"></script>
</body>
</html>
