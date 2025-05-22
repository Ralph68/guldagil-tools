<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];

// Valeurs par défaut
$dep = '';
$poids = '';
$type = 'palette';
$adr = '';
$option_sup = 'standard';
$enlevement = false;
$palettes = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comparateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Styles spécifiques pour la version dynamique */
    .zone-resultat {
      background-color: #eef6ff;
      border-left: 5px solid #007acc;
      padding: 1.2rem;
      border-radius: 6px;
      margin-bottom: 2rem;
      min-height: 120px;
      position: relative;
    }
    
    .loading {
      display: none;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
    
    .loading.active {
      display: block;
    }
    
    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #007acc;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .toggle-link {
      cursor: pointer;
      color: #007acc;
      text-decoration: underline;
      font-size: 0.9rem;
      margin-top: 0.5rem;
      display: inline-block;
    }
    
    .details-box {
      display: none;
      margin-top: 1rem;
      background: #f9f9f9;
      padding: 1rem;
      border-radius: 4px;
      border-left: 3px solid #ccc;
    }
    
    .details-box.active {
      display: block;
    }
    
    .details-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }
    
    .details-table td {
      padding: 0.3rem 0;
      border-bottom: 1px solid #eee;
    }
    
    .details-table td:first-child {
      font-weight: 600;
      width: 40%;
    }
    
    .all-carriers {
      display: none;
      margin-top: 1.5rem;
      background: white;
      padding: 1rem;
      border-radius: 6px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .all-carriers.active {
      display: block;
    }
    
    .carrier-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }
    
    .carrier-card {
      background: #f8f8f8;
      padding: 1rem;
      border-radius: 4px;
      text-align: center;
      transition: all 0.2s;
    }
    
    .carrier-card.best {
      background: #e7f9e7;
      border: 2px solid #4CAF50;
    }
    
    .carrier-card h4 {
      margin: 0 0 0.5rem 0;
      color: #333;
    }
    
    .carrier-price {
      font-size: 1.2rem;
      font-weight: bold;
      color: #007acc;
    }
    
    .carrier-card.unavailable .carrier-price {
      color: #999;
      font-size: 0.9rem;
    }
    
    .reset-button {
      background-color: #666;
      color: white;
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
      margin-left: 1rem;
    }
    
    .reset-button:hover {
      background-color: #555;
    }
    
    .form-actions {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 2rem;
    }
    
    .error-message {
      color: #d32f2f;
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }
    
    /* Masquer les éléments non nécessaires initialement */
    .result-content {
      opacity: 1;
      transition: opacity 0.3s ease;
    }
    
    .result-content.loading {
      opacity: 0.3;
    }
    
    /* Styles pour les boutons palettes */
    .palette-buttons {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.5rem;
    }
    
    .palette-btn {
      padding: 0.5rem 1rem;
      border: 2px solid #ddd;
      background: white;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 600;
    }
    
    .palette-btn:hover {
      border-color: #007acc;
      background: #f0f8ff;
    }
    
    .palette-btn.active {
      background: #007acc;
      color: white;
      border-color: #007acc;
    }
    
    .palette-btn.palette-plus {
      background: #f0f0f0;
    }
    
    .palette-info {
      margin-top: 0.5rem;
      padding: 0.75rem;
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 4px;
      color: #856404;
      font-size: 0.9rem;
    }
    
    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    
    .modal.active {
      display: block;
    }
    
    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 0;
      border: 1px solid #888;
      width: 80%;
      max-width: 800px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .modal-header {
      padding: 1rem 1.5rem;
      background: #007acc;
      color: white;
      border-radius: 8px 8px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-header h2 {
      margin: 0;
    }
    
    .close {
      color: white;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover {
      opacity: 0.8;
    }
    
    .modal-body {
      padding: 1.5rem;
      max-height: 60vh;
      overflow-y: auto;
    }
    
    .modal-footer {
      padding: 1rem 1.5rem;
      background: #f8f9fa;
      border-top: 1px solid #ddd;
      text-align: right;
      border-radius: 0 0 8px 8px;
    }
    
    .btn-clear {
      background: #dc3545;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .btn-clear:hover {
      background: #c82333;
    }
    
    /* Table historique */
    .historique-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }
    
    .historique-table th,
    .historique-table td {
      padding: 0.75rem;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    
    .historique-table th {
      background: #f8f9fa;
      font-weight: 600;
    }
    
    .historique-table tr:hover {
      background: #f8f9fa;
    }
    
    .affrètement-message {
      background: #fff3cd;
      border: 2px solid #ffc107;
      padding: 1.5rem;
      border-radius: 8px;
      text-align: center;
      margin: 1rem 0;
    }
    
    .affrètement-message h3 {
      color: #856404;
      margin-top: 0;
    }
  </style>
</head>
<body>
<div class="container">

  <header class="site-header">
    <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo">
    <h1>Comparateur de frais de port</h1>
    <nav>
      <a href="#" onclick="showHistorique(); return false;" title="Historique">📋 Historique</a>
      <a href="admin/rates.php">⚙️ Administration</a>
    </nav>
  </header>

  <section class="zone-resultat">
    <div class="loading" id="loading">
      <div class="spinner"></div>
    </div>
    <div class="result-content" id="result-content">
      <h2>Choix recommandé</h2>
      <div id="best-result">
        <p><em>Remplissez le formulaire pour voir les tarifs</em></p>
      </div>
    </div>
  </section>

  <main class="main-content">
    <section class="form-section">
      <div id="error-container"></div>

      <form id="calc-form" method="post">
        <div class="form-step">
          <label for="departement">Département</label>
          <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required 
                 placeholder="Ex: 75" value="<?= htmlspecialchars($dep) ?>">
          <div class="error-message" id="error-departement"></div>
        </div>

        <div class="form-step">
          <label for="poids">Poids réel (kg)</label>
          <input type="number" name="poids" id="poids" step="0.1" min="0.1" required 
                 placeholder="Ex: 25.5" value="<?= htmlspecialchars($poids) ?>">
          <div class="error-message" id="error-poids"></div>
        </div>

        <div class="form-step">
          <label>Type d'envoi</label>
          <div class="radio-group">
            <input type="radio" name="type" value="colis" id="type-colis" <?= $type==='colis'?'checked':'' ?>>
            <label for="type-colis">Colis</label>
            <input type="radio" name="type" value="palette" id="type-palette" <?= $type==='palette'?'checked':'' ?>>
            <label for="type-palette">Palette</label>
          </div>
          <div class="error-message" id="error-type"></div>
        </div>

        <div class="form-step">
          <label>Marchandise dangereuse (ADR)</label>
          <div class="radio-group">
            <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr==='oui'?'checked':'' ?>>
            <label for="adr-oui">Oui</label>
            <input type="radio" name="adr" value="non" id="adr-non" <?= $adr==='non'?'checked':'' ?>>
            <label for="adr-non">Non</label>
          </div>
          <div class="error-message" id="error-adr"></div>
        </div>

        <div class="form-step">
          <label>Options supplémentaires</label>
          <div class="radio-group">
            <input type="radio" name="option_sup" value="standard" id="opt-standard" <?= $option_sup==='standard'?'checked':'' ?>>
            <label for="opt-standard">Standard</label>
            <input type="radio" name="option_sup" value="rdv" id="opt-rdv" <?= $option_sup==='rdv'?'checked':'' ?>>
            <label for="opt-rdv">Prise de RDV</label>
            <input type="radio" name="option_sup" value="premium13" id="opt-premium13" <?= $option_sup==='premium13'?'checked':'' ?>>
            <label for="opt-premium13">Premium avant 13h</label>
            <input type="radio" name="option_sup" value="premium18" id="opt-premium18" <?= $option_sup==='premium18'?'checked':'' ?>>
            <label for="opt-premium18">Premium avant 18h</label>
            <input type="radio" name="option_sup" value="datefixe" id="opt-datefixe" <?= $option_sup==='datefixe'?'checked':'' ?>>
            <label for="opt-datefixe">Date fixe</label>
          </div>
        </div>

        <div class="form-step">
          <label>
            <input type="checkbox" name="enlevement" id="enlevement" value="1" <?= $enlevement ? 'checked' : '' ?>>
            Enlèvement sur site
          </label>
        </div>

        <div class="form-step">
          <label>Nombre de palettes EUR</label>
          <div class="palette-buttons">
            <button type="button" class="palette-btn" data-palettes="1">1</button>
            <button type="button" class="palette-btn" data-palettes="2">2</button>
            <button type="button" class="palette-btn" data-palettes="3">3</button>
            <button type="button" class="palette-btn palette-plus" data-palettes="plus">+</button>
          </div>
          <input type="hidden" name="palettes" id="palettes" value="<?= $palettes ?>">
          <div class="palette-info" id="palette-info" style="display: none;">
            <p>Pour plus de 3 palettes, contactez le service achat pour un affrètement</p>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="reset-button" id="reset-btn">Réinitialiser</button>
        </div>
      </form>
    </section>
  </main>
</div>

<!-- Modal Historique -->
<div id="historique-modal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Historique des calculs</h2>
      <span class="close">&times;</span>
    </div>
    <div class="modal-body" id="historique-content">
      <p>Chargement...</p>
    </div>
    <div class="modal-footer">
      <button class="btn-clear" onclick="clearHistorique()">Effacer l'historique</button>
    </div>
  </div>
</div>

<script src="assets/js/dynamic-calculator.js"></script>
</body>
</html>
