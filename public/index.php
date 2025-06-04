<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);

// Vérifier si le module ADR est activé
$adrEnabled = true; // Pour l'instant, toujours activé
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guldagil Portal - Calculateur de frais de port et outils logistiques</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Styles additionnels pour les nouveaux éléments */
    .header-nav {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .nav-link {
      padding: 0.5rem 1rem;
      background: var(--gul-gray-100);
      color: var(--gul-blue-primary);
      text-decoration: none;
      border-radius: var(--gul-radius);
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .nav-link:hover {
      background: var(--gul-blue-primary);
      color: white;
      transform: translateY(-1px);
    }
    
    .nav-link.restricted {
      background: var(--gul-orange-light);
      color: var(--gul-orange-dark);
      position: relative;
    }
    
    .nav-link.restricted:hover {
      background: var(--gul-orange);
      color: white;
    }
    
    .nav-link.restricted::after {
      content: "🔒";
      font-size: 0.8rem;
    }
    
    .hero-section {
      background: linear-gradient(135deg, var(--gul-blue-primary) 0%, var(--gul-blue-light) 100%);
      color: white;
      padding: 2rem 0;
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .hero-content h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    
    .hero-content p {
      font-size: 1.1rem;
      opacity: 0.9;
      margin-bottom: 1.5rem;
    }
    
    .module-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }
    
    .module-card {
      background: white;
      border-radius: var(--gul-radius);
      box-shadow: var(--gul-shadow-lg);
      padding: 1.5rem;
      transition: transform 0.2s ease;
      border: 1px solid var(--gul-gray-200);
    }
    
    .module-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .module-card.restricted {
      border-left: 4px solid var(--gul-orange);
    }
    
    .module-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    
    .module-icon {
      font-size: 2rem;
      width: 3rem;
      height: 3rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: var(--gul-gray-50);
    }
    
    .module-info h3 {
      margin: 0;
      color: var(--gul-blue-primary);
      font-size: 1.2rem;
    }
    
    .module-status {
      font-size: 0.8rem;
      color: var(--gul-gray-500);
      margin-top: 0.25rem;
    }
    
    .module-description {
      margin-bottom: 1.5rem;
      color: var(--gul-gray-600);
      line-height: 1.5;
    }
    
    .module-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .btn-module {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: var(--gul-radius);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn-module.primary {
      background: var(--gul-blue-primary);
      color: white;
    }
    
    .btn-module.primary:hover {
      background: var(--gul-blue-dark);
      transform: translateY(-1px);
    }
    
    .btn-module.secondary {
      background: var(--gul-gray-100);
      color: var(--gul-gray-700);
      border: 1px solid var(--gul-gray-300);
    }
    
    .btn-module.secondary:hover {
      background: var(--gul-gray-200);
    }
    
    .btn-module.restricted {
      background: var(--gul-orange);
      color: white;
    }
    
    .btn-module.restricted:hover {
      background: var(--gul-orange-dark);
    }
    
    .quick-access {
      background: var(--gul-gray-50);
      padding: 1.5rem;
      border-radius: var(--gul-radius);
      margin: 2rem 0;
    }
    
    .quick-access h3 {
      margin: 0 0 1rem 0;
      color: var(--gul-blue-primary);
    }
    
    .quick-links {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .quick-link {
      padding: 0.5rem 1rem;
      background: white;
      border: 1px solid var(--gul-gray-300);
      border-radius: var(--gul-radius);
      text-decoration: none;
      color: var(--gul-gray-700);
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }
    
    .quick-link:hover {
      background: var(--gul-blue-primary);
      color: white;
      border-color: var(--gul-blue-primary);
    }
    
    @media (max-width: 768px) {
      .header-nav {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
      }
      
      .hero-content h1 {
        font-size: 1.5rem;
      }
      
      .module-cards {
        grid-template-columns: 1fr;
      }
      
      .quick-links {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <!-- Header avec navigation élargie -->
  <header class="main-header">
    <div class="header-container">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="header-logo">
      <h1 class="header-title">Guldagil Portal</h1>
      
      <!-- Navigation principale -->
      <nav class="header-nav">
        <a href="#calculateur" class="nav-link">
          <span>🚚</span>
          Calculateur
        </a>
        
        <a href="#suivi" class="nav-link">
          <span>📦</span>
          Suivi colis
        </a>
        
        <?php if ($adrEnabled): ?>
        <a href="adr/" class="nav-link restricted" title="Module ADR - Accès sécurisé">
          <span>⚠️</span>
          ADR
        </a>
        <?php endif; ?>
        
        <a href="admin/" class="nav-link restricted" title="Administration - Accès restreint">
          <span>⚙️</span>
          Admin
        </a>
      </nav>
    </div>
  </header>

  <!-- Section hero -->
  <section class="hero-section">
    <div class="header-container">
      <div class="hero-content">
        <h1>🏭 Guldagil Portal</h1>
        <p>Vos outils logistiques centralisés pour le transport et la gestion ADR</p>
      </div>
    </div>
  </section>

  <!-- Container principal -->
  <main class="main-container">
    <!-- Section calculateur principal (mise en avant) -->
    <section id="calculateur" class="form-column">
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
    </section>

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

  <!-- Section modules complémentaires -->
  <section class="main-container" style="grid-template-columns: 1fr; margin-top: 2rem;">
    <div class="module-cards">
      
      <!-- Module ADR -->
      <?php if ($adrEnabled): ?>
      <div class="module-card restricted">
        <div class="module-header">
          <div class="module-icon" style="background: var(--gul-orange-light);">⚠️</div>
          <div class="module-info">
            <h3>Gestion ADR</h3>
            <div class="module-status">🔒 Accès sécurisé</div>
          </div>
        </div>
        <div class="module-description">
          Déclarations de marchandises dangereuses, récapitulatifs quotidiens et gestion des expéditions ADR selon la réglementation transport.
        </div>
        <div class="module-actions">
          <a href="adr/" class="btn-module restricted">
            <span>🚪</span>
            Accéder au module
          </a>
          <button class="btn-module secondary" onclick="showADRInfo()">
            <span>ℹ️</span>
            En savoir plus
          </button>
        </div>
      </div>
      <?php endif; ?>

      <!-- Module Suivi -->
      <div class="module-card" id="suivi">
        <div class="module-header">
          <div class="module-icon" style="background: var(--gul-blue-light); color: white;">📦</div>
          <div class="module-info">
            <h3>Suivi des expéditions</h3>
            <div class="module-status">✅ Accès libre</div>
          </div>
        </div>
        <div class="module-description">
          Suivez vos colis et palettes directement sur les portails transporteurs. Liens rapides vers vos espaces clients.
        </div>
        <div class="module-actions">
          <button class="btn-module primary" onclick="showTrackingLinks()">
            <span>🔗</span>
            Liens transporteurs
          </button>
        </div>
      </div>

      <!-- Module Administration -->
      <div class="module-card restricted">
        <div class="module-header">
          <div class="module-icon" style="background: var(--gul-gray-300);">⚙️</div>
          <div class="module-info">
            <h3>Administration</h3>
            <div class="module-status">🔒 Administrateurs uniquement</div>
          </div>
        </div>
        <div class="module-description">
          Gestion des tarifs transporteurs, options supplémentaires, taxes et configuration système.
        </div>
        <div class="module-actions">
          <a href="admin/" class="btn-module restricted">
            <span>🔧</span>
            Interface admin
          </a>
        </div>
      </div>
    </div>

    <!-- Accès rapide -->
    <div class="quick-access">
      <h3>🚀 Accès rapide</h3>
      <div class="quick-links">
        <a href="#calculateur" class="quick-link">💰 Nouveau calcul</a>
        <a href="admin/export.php?type=all&format=csv" class="quick-link">📥 Export tarifs</a>
        <a href="https://myportal.heppner-group.com/home" target="_blank" class="quick-link">🚚 Portal Heppner</a>
        <a href="https://xpoconnecteu.xpo.com/customer/orders/list" target="_blank" class="quick-link">📦 XPO Connect</a>
        <?php if ($adrEnabled): ?>
        <a href="adr/dashboard.php" class="quick-link">⚠️ Dashboard ADR</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer simple -->
  <footer class="main-footer">
    <p>© 2025 Guldagil - Portal v1.2.0 - Usage interne</p>
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

  <!-- Modal Info ADR -->
  <div id="adr-info-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>⚠️ Module ADR - Informations</h3>
        <span class="modal-close" onclick="closeModal('adr-info-modal')">&times;</span>
      </div>
      <div class="modal-body">
        <h4>🚛 Qu'est-ce que le module ADR ?</h4>
        <p>Le module ADR permet la gestion complète des expéditions de marchandises dangereuses selon la réglementation européenne ADR (Accord pour le transport des marchandises Dangereuses par Route).</p>
        
        <h4>📋 Fonctionnalités disponibles :</h4>
        <ul>
          <li><strong>Déclarations individuelles</strong> - Création et modification des déclarations</li>
          <li><strong>Récapitulatifs quotidiens</strong> - Génération automatique par transporteur</li>
          <li><strong>Historique complet</strong> - Recherche et consultation des expéditions</li>
          <li><strong>Export PDF</strong> - Documents conformes à la réglementation</li>
          <li><strong>Gestion produits</strong> - Base de données des matières dangereuses</li>
        </ul>
        
        <h4>🔒 Accès sécurisé</h4>
        <p>Ce module nécessite une authentification spécifique et est réservé au personnel autorisé à gérer les expéditions ADR.</p>
        
        <div style="background: var(--gul-orange-light); padding: 1rem; border-radius: 6px; margin-top: 1rem;">
          <strong>⚠️ Important :</strong> La gestion des marchandises dangereuses est soumise à une réglementation stricte. Seules les personnes formées et autorisées peuvent utiliser ce module.
        </div>
      </div>
      <div class="modal-footer">
        <a href="adr/" class="btn-primary">🚪 Accéder au module</a>
        <button class="btn-secondary" onclick="closeModal('adr-info-modal')">Fermer</button>
      </div>
    </div>
  </div>

  <!-- Modal liens transporteurs -->
  <div id="tracking-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>🔗 Liens transporteurs</h3>
        <span class="modal-close" onclick="closeModal('tracking-modal')">&times;</span>
      </div>
      <div class="modal-body">
        <div class="module-cards">
          <div class="module-card">
            <div class="module-header">
              <div class="module-icon">🚚</div>
              <div class="module-info">
                <h3>Heppner</h3>
                <div class="module-status">MyPortal Heppner</div>
              </div>
            </div>
            <div class="module-description">
              Accédez à votre espace client Heppner pour suivre vos expéditions, consulter vos factures et gérer vos enlèvements.
            </div>
            <div class="module-actions">
              <a href="https://myportal.heppner-group.com/home" target="_blank" class="btn-module primary">
                <span>🔗</span>
                Ouvrir MyPortal
              </a>
            </div>
          </div>
          
          <div class="module-card">
            <div class="module-header">
              <div class="module-icon">📦</div>
              <div class="module-info">
                <h3>XPO Logistics</h3>
                <div class="module-status">XPO Connect</div>
              </div>
            </div>
            <div class="module-description">
              Consultez XPO Connect pour le suivi en temps réel de vos commandes et la gestion de votre compte client.
            </div>
            <div class="module-actions">
              <a href="https://xpoconnecteu.xpo.com/customer/orders/list" target="_blank" class="btn-module primary">
                <span>🔗</span>
                Ouvrir XPO Connect
              </a>
            </div>
          </div>
        </div>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--gul-gray-50); border-radius: 6px;">
          <h4>💡 Prochainement</h4>
          <p>Interface unifiée de suivi intégrant tous vos transporteurs dans un seul tableau de bord.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeModal('tracking-modal')">Fermer</button>
      </div>
    </div>
  </div>

  <script src="assets/js/calculateur.js"></script>
  <script>
    // Fonctions pour les modals et interactions
    function showADRInfo() {
      document.getElementById('adr-info-modal').style.display = 'flex';
    }
    
    function showTrackingLinks() {
      document.getElementById('tracking-modal').style.display = 'flex';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }
    
    // Fermer les modals en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
      }
    });
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
          modal.style.display = 'none';
        });
      }
    });
    
    // Scroll smooth vers les sections
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
    
    // Analytics de clics sur liens externes
    document.querySelectorAll('a[target="_blank"]').forEach(link => {
      link.addEventListener('click', function() {
        console.log('🔗 Clic lien externe:', this.href);
        // Ici vous pouvez ajouter du tracking analytics
      });
    });
  </script>
</body>
</html>
