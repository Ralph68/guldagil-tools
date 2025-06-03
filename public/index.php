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
  <!-- Header + Zone résultat fixés en haut -->
  <div class="fixed-header">
    <header class="site-header">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo">
      <h1>Comparateur de frais de port</h1>
      <nav>
        <a href="#" onclick="showHistorique(); return false;" title="Historique">📋</a>
        <a href="admin/" title="Administration">⚙️</a>
      </nav>
    </header>

    <!-- Zone de résultat toujours visible -->
    <section class="zone-resultat">
      <div class="loading" id="loading">
        <div class="spinner"></div>
      </div>
      <div class="result-content" id="result-content">
        <h2>💰 Votre tarif</h2>
        <div id="best-result">
          <p class="invite-message">🚀 Commence par renseigner ton département de livraison</p>
        </div>
        
        <!-- Progress bar déplacée ici -->
        <div class="progress-indicator-result">
          <div class="progress-bar" id="progress-bar"></div>
        </div>
      </div>
    </section>
  </div>

  <!-- Contenu principal avec défilement -->
  <div class="main-container">
    <main class="main-content">
      <!-- Messages d'erreur globaux -->
      <div id="error-container" class="error-container"></div>

      <!-- Formulaire en étapes guidées -->
      <form id="calc-form" class="guided-form">
        
        <!-- Étape 1 : Département -->
        <div class="form-step" id="step-departement" data-step="1">
          <div class="step-header">
            <span class="step-number">1</span>
            <h3>Où livres-tu ?</h3>
          </div>
          <div class="step-content">
            <label for="departement">Département de livraison</label>
            <input type="text" 
                   name="departement" 
                   id="departement" 
                   maxlength="2" 
                   pattern="\d{2}" 
                   placeholder="Ex: 67, 75, 13..."
                   autocomplete="off">
            <div class="field-help">
              <small>Saisis le numéro à 2 chiffres du département</small>
            </div>
            <div class="error-message" id="error-departement"></div>
          </div>
        </div>

        <!-- Étape 2 : Poids -->
        <div class="form-step" id="step-poids" data-step="2" style="display: none;">
          <div class="step-header">
            <span class="step-number">2</span>
            <h3>Quel est le poids de ton envoi ?</h3>
          </div>
          <div class="step-content">
            <label for="poids">Poids réel en kg</label>
            <input type="number" id="poids" class="form-control" 
       placeholder="Ex: 25" 
       min="1" max="3500" 
       step="1"
       oninput="this.value = Math.floor(this.value)">
            <div class="field-help">
              <small>⚠️ Au-delà de 3000 kg, nous vous orienterons vers notre service affrètement</small>
            </div>
            <div class="error-message" id="error-poids"></div>
          </div>
        </div>

        <!-- Étape 3 : Type d'envoi -->
        <div class="form-step" id="step-type" data-step="3" style="display: none;">
          <div class="step-header">
            <span class="step-number">3</span>
            <h3>Comment expédies-tu ?</h3>
          </div>
          <div class="step-content">
            <div class="radio-group">
              <div class="radio-option">
                <input type="radio" name="type" value="colis" id="type-colis">
                <label for="type-colis">
                  <div class="option-icon">📦</div>
                  <div class="option-text">
                    <strong>Colis</strong>
                    <small>Envoi en carton, sac, etc.</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="type" value="palette" id="type-palette">
                <label for="type-palette">
                  <div class="option-icon">🏗️</div>
                  <div class="option-text">
                    <strong>Palette</strong>
                    <small>Envoi palettisé</small>
                  </div>
                </label>
              </div>
            </div>
            <div class="error-message" id="error-type"></div>
          </div>
        </div>

        <!-- Étape 4 : ADR -->
        <div class="form-step" id="step-adr" data-step="4" style="display: none;">
          <div class="step-header">
            <span class="step-number">4</span>
            <h3>Ta marchandise est-elle dangereuse ?</h3>
          </div>
          <div class="step-content">
            <div class="radio-group">
              <div class="radio-option">
                <input type="radio" name="adr" value="non" id="adr-non">
                <label for="adr-non">
                  <div class="option-icon">✅</div>
                  <div class="option-text">
                    <strong>Non</strong>
                    <small>Marchandise standard</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="adr" value="oui" id="adr-oui">
                <label for="adr-oui">
                  <div class="option-icon">⚠️</div>
                  <div class="option-text">
                    <strong>Oui</strong>
                    <small>Marchandise ADR</small>
                  </div>
                </label>
              </div>
            </div>
            <div class="field-help">
              <small>Les marchandises dangereuses (ADR) incluent : produits chimiques, aérosols, batteries lithium, peintures, etc.</small>
            </div>
            <div class="error-message" id="error-adr"></div>
          </div>
        </div>

        <!-- Étape 5 : Options (affiché après premier calcul) -->
        <div class="form-step" id="step-options" data-step="5" style="display: none;">
          <div class="step-header">
            <span class="step-number">5</span>
            <h3>Options de livraison</h3>
          </div>
          <div class="step-content">
            <div class="radio-group">
              <div class="radio-option">
                <input type="radio" name="option_sup" value="standard" id="opt-standard" checked>
                <label for="opt-standard">
                  <div class="option-icon">🚛</div>
                  <div class="option-text">
                    <strong>Standard</strong>
                    <small>Livraison normale</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="option_sup" value="rdv" id="opt-rdv">
                <label for="opt-rdv">
                  <div class="option-icon">📞</div>
                  <div class="option-text">
                    <strong>Prise de RDV</strong>
                    <small>+ Supplément</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="option_sup" value="premium13" id="opt-premium13">
                <label for="opt-premium13">
                  <div class="option-icon">⚡</div>
                  <div class="option-text">
                    <strong>Premium 13h</strong>
                    <small>Livraison avant 13h</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="option_sup" value="premium18" id="opt-premium18">
                <label for="opt-premium18">
                  <div class="option-icon">🕕</div>
                  <div class="option-text">
                    <strong>Premium 18h</strong>
                    <small>Livraison avant 18h</small>
                  </div>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="option_sup" value="datefixe" id="opt-datefixe">
                <label for="opt-datefixe">
                  <div class="option-icon">📅</div>
                  <div class="option-text">
                    <strong>Date fixe</strong>
                    <small>Livraison à date précise</small>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Étape 6 : Options complémentaires -->
        <div class="form-step" id="step-complements" data-step="6" style="display: none;">
          <div class="step-header">
            <span class="step-number">6</span>
            <h3>Options complémentaires</h3>
          </div>
          <div class="step-content">
            <!-- Enlèvement -->
            <div class="checkbox-option">
              <input type="checkbox" name="enlevement" id="enlevement" value="1">
              <label for="enlevement">
                <div class="option-icon">🏢</div>
                <div class="option-text">
                  <strong>Enlèvement sur site</strong>
                  <small>Le transporteur vient chercher la marchandise chez vous</small>
                </div>
              </label>
            </div>

            <!-- Palettes EUR (seulement si type = palette) -->
            <div class="palette-section" id="palette-section" style="display: none;">
              <label>Nombre de palettes EUR</label>
              <div class="palette-buttons">
                <button type="button" class="palette-btn" data-palettes="1">1</button>
                <button type="button" class="palette-btn" data-palettes="2">2</button>
                <button type="button" class="palette-btn" data-palettes="3">3</button>
                <button type="button" class="palette-btn palette-plus" data-palettes="plus">+</button>
              </div>
              <input type="hidden" name="palettes" id="palettes" value="1">
              <div class="palette-info" id="palette-info" style="display: none;">
                <p>⚠️ Pour plus de 3 palettes, contactez notre service achat : 📞 03 89 63 42 42</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation et actions -->
        <div class="form-navigation">
          <button type="button" class="btn-secondary" id="btn-reset">🔄 Recommencer</button>
        </div>
      </form>

      <!-- Zone d'aide contextuelle -->
      <div class="help-section">
        <h4>💡 Besoin d'aide ?</h4>
        <div class="help-cards">
          <div class="help-card">
            <h5>📦 Service logistique</h5>
            <p>achats@guldagil.com</p>
            <small>Tarifs et affrètements</small>
          </div>
          <div class="help-card">
            <h5>🐛 Support technique</h5>
            <p>runser.jean.thomas@guldagil.com</p>
            <small>Bugs et améliorations</small>
          </div>
          <div class="help-card">
            <h5>❓ Pas trouvé ?</h5>
            <p>📞 03 89 63 42 42</p>
            <small>Autres demandes</small>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
      <div class="footer-content">
        <div class="footer-info">
          <p><strong>Guldagil Port Calculator</strong> v1.2.0 - Usage interne</p>
          <p>© 2025 Guldagil - Développé par Jean-Thomas Runser</p>
        </div>
        <div class="footer-links">
          <a href="mailto:achats@guldagil.com">📦 Logistique</a>
          <a href="mailto:runser.jean.thomas@guldagil.com">🐛 Support</a>
        </div>
      </div>
    </footer>
  </div>

  <!-- Modal Historique -->
  <div id="historique-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📋 Historique des calculs</h2>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body" id="historique-content">
        <p>Chargement...</p>
      </div>
      <div class="modal-footer">
        <button class="btn-danger" onclick="clearHistorique()">🗑️ Effacer l'historique</button>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/js/guided-calculator.js"></script>
</body>
</html>
