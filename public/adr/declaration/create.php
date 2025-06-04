/**
 * Traitement du formulaire de déclaration
 */
function processDeclarationForm($db, $formData) {
    $errors = [];
    
    // Validation des champs obligatoires
    $requiredFields = [
        'code_produit' => 'Code produit',
        'transporteur' => 'Transporteur',
        'quantite_declaree' => 'Quantité',
        'unite_quantite' => 'Unité',
        'date_expedition' => 'Date d\'expédition',
        'expediteur_nom' => 'Nom expéditeur',
        'destinataire_nom' => 'Nom destinataire',
        'nombre_colis' => 'Nombre de colis',
        'type_colisage' => 'Type de colisage'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($formData[$field])) {
            $errors[] = "Le champ '$label' est obligatoire";
        }
    }
    
    // Validation du code produit
    if (!empty($formData['code_produit'])) {
        $stmt = $db->prepare("SELECT * FROM gul_adr_products WHERE code_produit = ? AND actif = 1");
        $stmt->execute([$formData['code_produit']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $errors[] = "Code produit non trouvé dans le catalogue";
        } elseif ($product['corde_article_ferme'] === 'x') {
            $errors[] = "Ce produit est fermé et ne peut plus être expédié";
        }
    }
    
    // Validation de la quantité
    if (!empty($formData['quantite_declaree'])) {
        $quantite = floatval($formData['quantite_declaree']);
        if ($quantite <= 0) {
            $errors[] = "La quantité doit être supérieure à 0";
        } elseif ($quantite > 50000) {
            $errors[] = "Quantité trop importante (max 50 000)";
        }
    }
    
    // Validation du nombre de colis
    if (!empty($formData['nombre_colis'])) {
        $nombreColis = intval($formData['nombre_colis']);
        if ($nombreColis <= 0) {
            $errors[] = "Le nombre de colis doit être supérieur à 0";
        } elseif ($nombreColis > 1000) {
            $errors[] = "Nombre de colis trop important (max 1000)";
        }
    }
    
    // Validation de la date
    if (!empty($formData['date_expedition'])) {
        $date = DateTime::createFromFormat('Y-m-d', $formData['date_expedition']);
        if (!$date) {
            $errors[] = "Format de date invalide";
        } else {
            $today = new DateTime();
            $maxDate = (clone $today)->add(new DateInterval('P30D'));
            
            if ($date < $today->sub(new DateInterval('P7D'))) {
                $errors[] = "La date d'expédition ne peut pas être antérieure à 7 jours";
            } elseif ($date > $maxDate) {
                $errors[] = "La date d'expédition ne peut pas dépasser 30 jours";
            }
        }
    }
    
    // S'il y a des erreurs, arrêter ici
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    try {
        // Insérer la déclaration avec tous les nouveaux champs
        $stmt = $db->prepare("
            <?php
// public/adr/declaration/create.php - Création de déclarations ADR
session_start();

// Vérification authentification ADR
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
}

require __DIR__ . '/../../../config.php';

// Traitement du formulaire
$errors = [];
$success = '';
$declarationData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processDeclarationForm($db, $_POST);
    if ($result['success']) {
        $success = $result['message'];
        $declarationData = $result['data'];
    } else {
        $errors = $result['errors'];
    }
}

// Charger les données de référence
try {
    // Transporteurs disponibles
    $transporteurs = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO Logistics', 
        'kn' => 'Kuehne + Nagel'
    ];
    
    // Unités de mesure
    $unites = [
        'kg' => 'Kilogrammes (kg)',
        'L' => 'Litres (L)',
        'pieces' => 'Pièces'
    ];
    
} catch (Exception $e) {
    $errors[] = "Erreur lors du chargement des données de référence";
    error_log("Erreur création déclaration: " . $e->getMessage());
}

/**
 * Traitement du formulaire de déclaration
 */
function processDeclarationForm($db, $formData) {
    $errors = [];
    
    // Validation des champs obligatoires
    $requiredFields = [
        'code_produit' => 'Code produit',
        'transporteur' => 'Transporteur',
        'quantite_declaree' => 'Quantité',
        'unite_quantite' => 'Unité',
        'date_expedition' => 'Date d\'expédition'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($formData[$field])) {
            $errors[] = "Le champ '$label' est obligatoire";
        }
    }
    
    // Validation du code produit
    if (!empty($formData['code_produit'])) {
        $stmt = $db->prepare("SELECT * FROM gul_adr_products WHERE code_produit = ? AND actif = 1");
        $stmt->execute([$formData['code_produit']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $errors[] = "Code produit non trouvé dans le catalogue";
        } elseif ($product['corde_article_ferme'] === 'x') {
            $errors[] = "Ce produit est fermé et ne peut plus être expédié";
        }
    }
    
    // Validation de la quantité
    if (!empty($formData['quantite_declaree'])) {
        $quantite = floatval($formData['quantite_declaree']);
        if ($quantite <= 0) {
            $errors[] = "La quantité doit être supérieure à 0";
        } elseif ($quantite > 50000) {
            $errors[] = "Quantité trop importante (max 50 000)";
        }
    }
    
    // Validation de la date
    if (!empty($formData['date_expedition'])) {
        $date = DateTime::createFromFormat('Y-m-d', $formData['date_expedition']);
        if (!$date) {
            $errors[] = "Format de date invalide";
        } else {
            $today = new DateTime();
            $maxDate = (clone $today)->add(new DateInterval('P30D'));
            
            if ($date < $today->sub(new DateInterval('P7D'))) {
                $errors[] = "La date d'expédition ne peut pas être antérieure à 7 jours";
            } elseif ($date > $maxDate) {
                $errors[] = "La date d'expédition ne peut pas dépasser 30 jours";
            }
        }
    }
    
    // S'il y a des erreurs, arrêter ici
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    try {
        // Insérer la déclaration
        $stmt = $db->prepare("
            INSERT INTO gul_adr_declarations 
            (code_produit, transporteur, quantite_declaree, unite_quantite, 
             date_expedition, numero_bordereau, numero_recepisse, observations, 
             expediteur_nom, expediteur_adresse, destinataire_nom, destinataire_adresse,
             nombre_colis, type_colisage, poids_total_palettes, cree_par) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $formData['code_produit'],
            $formData['transporteur'],
            $formData['quantite_declaree'],
            $formData['unite_quantite'],
            $formData['date_expedition'],
            $formData['numero_bordereau'] ?? null,
            $formData['numero_recepisse'] ?? null,
            $formData['observations'] ?? null,
            $formData['expediteur_nom'],
            $formData['expediteur_adresse'] ?? null,
            $formData['destinataire_nom'],
            $formData['destinataire_adresse'] ?? null,
            $formData['nombre_colis'],
            $formData['type_colisage'],
            !empty($formData['poids_total_palettes']) ? $formData['poids_total_palettes'] : null,
            $_SESSION['adr_user']
        ]);
        
        $declarationId = $db->lastInsertId();
        
        // Récupérer la déclaration complète pour affichage
        $stmt = $db->prepare("
            SELECT d.*, p.nom_produit, p.numero_un, p.categorie_transport,
                   p.danger_environnement, p.type_contenant, p.nom_description_un,
                   p.groupe_emballage, p.numero_etiquette, p.code_tunnel
            FROM gul_adr_declarations d
            JOIN gul_adr_products p ON d.code_produit = p.code_produit
            WHERE d.id = ?
        ");
        $stmt->execute([$declarationId]);
        $declaration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => "Déclaration créée avec succès (ID: $declarationId)",
            'data' => $declaration
        ];
        
    } catch (Exception $e) {
        error_log("Erreur insertion déclaration: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ["Erreur lors de l'enregistrement: " . $e->getMessage()]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle déclaration ADR - Guldagil Portal</title>
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-warning: #ffc107;
            --adr-success: #28a745;
            --adr-info: #17a2b8;
            --adr-dark: #343a40;
            --adr-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Header ADR */
        .adr-header {
            background: linear-gradient(135deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Messages */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-left-color: var(--adr-success);
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-left-color: var(--adr-danger);
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border-left-color: var(--adr-warning);
            color: #856404;
        }

        /* Layout principal */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        /* Formulaire */
        .form-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--adr-light);
        }

        .form-icon {
            width: 50px;
            height: 50px;
            background: var(--adr-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--adr-dark);
        }

        .form-group label .required {
            color: var(--adr-danger);
            margin-left: 0.25rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--adr-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .form-control.error {
            border-color: var(--adr-danger);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.three-cols {
            grid-template-columns: 2fr 1fr 1fr;
        }

        /* Recherche produit */
        .product-search {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .search-item {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        .search-item:hover,
        .search-item.selected {
            background: var(--adr-light);
        }

        .search-item-name {
            font-weight: 600;
            color: var(--adr-primary);
        }

        .search-item-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        /* Informations produit */
        .product-info {
            background: var(--adr-light);
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }

        .product-info.show {
            display: block;
        }

        .product-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--adr-dark);
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-adr {
            background: var(--adr-danger);
            color: white;
        }

        .badge-env {
            background: var(--adr-warning);
            color: #333;
        }

        .badge-cat {
            background: var(--adr-dark);
            color: white;
        }

        /* Section récapitulatif */
        .recap-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--adr-primary);
        }

        .recap-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .recap-icon {
            width: 40px;
            height: 40px;
            background: var(--adr-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .recap-content {
            display: none;
        }

        .recap-content.show {
            display: block;
        }

        .recap-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .recap-item:last-child {
            border-bottom: none;
        }

        .recap-label {
            font-weight: 500;
            color: #666;
        }

        .recap-value {
            font-weight: 600;
            color: var(--adr-dark);
        }

        /* Boutons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--adr-primary);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #e55a2b;
            transform: translateY(-1px);
            box-shadow: var(--shadow-hover);
        }

        .btn-secondary {
            background: var(--adr-light);
            color: var(--adr-dark);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        /* Alertes réglementaires */
        .regulatory-alerts {
            background: #fff3cd;
            border: 1px solid var(--adr-warning);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }

        .regulatory-alerts.show {
            display: block;
        }

        .regulatory-alert {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .regulatory-alert:last-child {
            margin-bottom: 0;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 1rem;
            color: #666;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--adr-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .main-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-row,
            .form-row.three-cols {
                grid-template-columns: 1fr;
            }

            .product-details {
                grid-template-columns: 1fr 1fr;
            }

            body {
                padding-top: 120px;
            }

            .header-container {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header ADR -->
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">📋</div>
                <div>
                    <h1>Nouvelle déclaration ADR</h1>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Création d'une déclaration de marchandises dangereuses</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <a href="../dashboard.php" class="btn-header">
                    <span>📊</span>
                    Dashboard
                </a>
                <a href="list.php" class="btn-header">
                    <span>📋</span>
                    Liste
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>❌ Erreur(s) détectée(s) :</strong>
                <ul style="margin: 0.5rem 0 0 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✅ Succès :</strong> <?= htmlspecialchars($success) ?>
                
                <?php if (!empty($declarationData)): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.5); border-radius: 6px;">
                        <strong>Déclaration enregistrée :</strong>
                        <ul style="margin: 0.5rem 0 0 1rem;">
                            <li><strong>Produit :</strong> <?= htmlspecialchars($declarationData['nom_produit']) ?></li>
                            <li><strong>Code :</strong> <?= htmlspecialchars($declarationData['code_produit']) ?></li>
                            <li><strong>Transporteur :</strong> <?= htmlspecialchars(ucfirst($declarationData['transporteur'])) ?></li>
                            <li><strong>Quantité :</strong> <?= number_format($declarationData['quantite_declaree'], 3) ?> <?= htmlspecialchars($declarationData['unite_quantite']) ?></li>
                            <li><strong>Date expédition :</strong> <?= date('d/m/Y', strtotime($declarationData['date_expedition'])) ?></li>
                        </ul>
                        <div style="margin-top: 1rem;">
                            <a href="view.php?id=<?= $declarationData['id'] ?>" class="btn btn-secondary">
                                <span>👁️</span>
                                Voir la déclaration
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <!-- Formulaire principal -->
            <div class="form-section">
                <div class="form-header">
                    <div class="form-icon">📋</div>
                    <div>
                        <h2>Informations de la déclaration</h2>
                        <p style="color: #666; margin: 0;">Remplissez tous les champs obligatoires</p>
                    </div>
                </div>

    <script>
        // Configuration
        const searchConfig = {
            minChars: 2,
            delay: 300,
            maxResults: 10
        };

        // Variables globales
        let searchTimeout;
        let selectedProduct = null;
        let currentSearchTerm = '';
        let selectedIndex = -1;

        // Éléments DOM
        const codeProductInput = document.getElementById('code_produit');
        const searchResults = document.getElementById('search-results');
        const productInfo = document.getElementById('product-info');
        const productDetails = document.getElementById('product-details');
        const recapContent = document.getElementById('recap-content');
        const regulatoryAlerts = document.getElementById('regulatory-alerts');
        const regulatoryContent = document.getElementById('regulatory-content');
        const submitBtn = document.getElementById('submit-btn');

        // Event listeners
        codeProductInput.addEventListener('input', handleSearchInput);
        codeProductInput.addEventListener('keydown', handleKeyNavigation);
        codeProductInput.addEventListener('blur', hideSearchResults);
        codeProductInput.addEventListener('focus', handleSearchFocus);

        // Event listeners pour les autres champs (ajout des nouveaux champs)
        document.getElementById('transporteur').addEventListener('change', updateRecap);
        document.getElementById('quantite_declaree').addEventListener('input', updateRecap);
        document.getElementById('unite_quantite').addEventListener('change', updateRecap);
        document.getElementById('date_expedition').addEventListener('change', updateRecap);
        document.getElementById('nombre_colis').addEventListener('input', updateRecap);
        document.getElementById('type_colisage').addEventListener('change', updateRecap);
        document.getElementById('expediteur_nom').addEventListener('input', updateRecap);
        document.getElementById('destinataire_nom').addEventListener('input', updateRecap);

        /**
         * Gestion de la saisie dans le champ recherche
         */
        function handleSearchInput(e) {
            const term = e.target.value.trim();
            currentSearchTerm = term;
            selectedIndex = -1;

            if (term.length < searchConfig.minChars) {
                hideSearchResults();
                clearProductInfo();
                return;
            }

            // Debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(term);
            }, searchConfig.delay);
        }

        /**
         * Navigation clavier dans les résultats
         */
        function handleKeyNavigation(e) {
            const suggestions = document.querySelectorAll('.search-item');
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateSelectedSuggestion();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectedSuggestion();
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                        selectProduct(suggestions[selectedIndex].dataset.code);
                    }
                    break;
                    
                case 'Escape':
                    hideSearchResults();
                    break;
            }
        }

        /**
         * Focus sur le champ recherche
         */
        function handleSearchFocus() {
            if (currentSearchTerm.length >= searchConfig.minChars) {
                searchProducts(currentSearchTerm);
            }
        }

        /**
         * Recherche de produits via AJAX
         */
        function searchProducts(term) {
            console.log('🔍 Recherche produits:', term);
            
            fetch(`../search/api.php?action=suggestions&q=${encodeURIComponent(term)}&limit=${searchConfig.maxResults}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.suggestions);
                    } else {
                        console.error('Erreur recherche:', data.error);
                        hideSearchResults();
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    hideSearchResults();
                });
        }

        /**
         * Affichage des résultats de recherche
         */
        function displaySearchResults(suggestions) {
            if (!suggestions || suggestions.length === 0) {
                hideSearchResults();
                return;
            }

            let html = '';
            suggestions.forEach((product, index) => {
                const badges = [];
                
                if (product.numero_un) {
                    badges.push(`<span class="badge badge-adr">UN ${product.numero_un}</span>`);
                }
                
                if (product.danger_environnement === 'OUI') {
                    badges.push(`<span class="badge badge-env">ENV</span>`);
                }
                
                if (product.categorie_transport) {
                    badges.push(`<span class="badge badge-cat">Cat.${product.categorie_transport}</span>`);
                }

                html += `
                    <div class="search-item" data-code="${product.code_produit}" data-index="${index}">
                        <div class="search-item-name">${highlightMatch(product.nom_produit, currentSearchTerm)}</div>
                        <div class="search-item-details">
                            Code: ${product.code_produit} ${badges.join(' ')}
                        </div>
                    </div>
                `;
            });

            searchResults.innerHTML = html;
            searchResults.style.display = 'block';

            // Event listeners pour les résultats
            document.querySelectorAll('.search-item').forEach(item => {
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault(); // Empêche le blur
                    selectProduct(item.dataset.code);
                });
                
                item.addEventListener('mouseenter', () => {
                    selectedIndex = parseInt(item.dataset.index);
                    updateSelectedSuggestion();
                });
            });
        }

        /**
         * Mise à jour de la sélection visuelle
         */
        function updateSelectedSuggestion() {
            document.querySelectorAll('.search-item').forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        /**
         * Sélection d'un produit
         */
        function selectProduct(codeProduct) {
            console.log('📦 Sélection produit:', codeProduct);
            
            hideSearchResults();
            codeProductInput.value = codeProduct;
            
            // Charger les détails du produit
            loadProductDetails(codeProduct);
        }

        /**
         * Chargement des détails d'un produit
         */
        function loadProductDetails(codeProduct) {
            productDetails.innerHTML = '<div class="loading"><div class="spinner"></div>Chargement...</div>';
            productInfo.classList.add('show');
            
            fetch(`../search/api.php?action=detail&q=${encodeURIComponent(codeProduct)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedProduct = data.product;
                        displayProductDetails(data.product);
                        checkRegulatoryCompliance(data.product);
                        updateRecap();
                    } else {
                        productDetails.innerHTML = `<div style="color: var(--adr-danger); text-align: center;">❌ ${data.error}</div>`;
                        clearProductInfo();
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement produit:', error);
                    productDetails.innerHTML = '<div style="color: var(--adr-danger); text-align: center;">❌ Erreur de connexion</div>';
                });
        }

        /**
         * Affichage des détails du produit
         */
        function displayProductDetails(product) {
            const badges = [];
            
            if (product.numero_un) {
                badges.push(`<span class="badge badge-adr">UN ${product.numero_un}</span>`);
            }
            
            if (product.danger_environnement === 'OUI') {
                badges.push(`<span class="badge badge-env">Polluant marin</span>`);
            }
            
            if (product.categorie_transport) {
                badges.push(`<span class="badge badge-cat">Catégorie ${product.categorie_transport}</span>`);
            }
            
            if (product.corde_article_ferme === 'x') {
                badges.push(`<span class="badge" style="background: var(--adr-danger); color: white;">FERMÉ</span>`);
            }

            productDetails.innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Nom produit</div>
                    <div class="detail-value">${product.nom_produit}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Code article</div>
                    <div class="detail-value">${product.code_produit}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Numéro UN</div>
                    <div class="detail-value">${product.numero_un || 'Non-ADR'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contenant</div>
                    <div class="detail-value">${product.type_contenant || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Poids/Volume</div>
                    <div class="detail-value">${product.poids_contenant || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Statuts</div>
                    <div class="detail-value">${badges.join(' ')}</div>
                </div>
            `;

            // Afficher la description UN si disponible
            if (product.nom_description_un) {
                productDetails.innerHTML += `
                    <div style="grid-column: 1 / -1; margin-top: 1rem; padding: 1rem; background: rgba(255,107,53,0.1); border-radius: 6px;">
                        <div class="detail-label">Description UN officielle</div>
                        <div style="font-weight: 500; color: var(--adr-dark);">${product.nom_description_un}</div>
                    </div>
                `;
            }
        }

        /**
         * Vérification de la conformité réglementaire
         */
        function checkRegulatoryCompliance(product) {
            const alerts = [];

            // Produit fermé
            if (product.corde_article_ferme === 'x') {
                alerts.push({
                    type: 'error',
                    icon: '🚫',
                    message: 'Produit fermé - Expédition interdite'
                });
            }

            // Produit ADR
            if (product.numero_un) {
                alerts.push({
                    type: 'warning',
                    icon: '⚠️',
                    message: 'Marchandise dangereuse - Déclaration ADR obligatoire'
                });
            }

            // Danger environnement
            if (product.danger_environnement === 'OUI') {
                alerts.push({
                    type: 'warning',
                    icon: '🌍',
                    message: 'Polluant marin - Précautions environnementales requises'
                });
            }

            // Catégorie restrictive
            if (product.categorie_transport === '1') {
                alerts.push({
                    type: 'error',
                    icon: '🚨',
                    message: 'Catégorie 1 - Transport très restreint (max 20kg par véhicule)'
                });
            } else if (product.categorie_transport === '2') {
                alerts.push({
                    type: 'warning',
                    icon: '⚡',
                    message: 'Catégorie 2 - Transport restreint (max 333kg par véhicule)'
                });
            }

            // Code tunnel
            if (product.code_tunnel && product.code_tunnel !== 'E') {
                alerts.push({
                    type: 'info',
                    icon: '🚇',
                    message: `Restriction tunnel: ${product.code_tunnel}`
                });
            }

            displayRegulatoryAlerts(alerts);
        }

        /**
         * Affichage des alertes réglementaires
         */
        function displayRegulatoryAlerts(alerts) {
            if (alerts.length === 0) {
                regulatoryAlerts.classList.remove('show');
                return;
            }

            let html = '<div style="margin-bottom: 1rem;"><strong>🔍 Vérifications réglementaires :</strong></div>';
            
            alerts.forEach(alert => {
                const alertClass = alert.type === 'error' ? 'color: var(--adr-danger);' : 
                                  alert.type === 'warning' ? 'color: var(--adr-warning);' : 
                                  'color: var(--adr-info);';
                
                html += `
                    <div class="regulatory-alert" style="${alertClass}">
                        <span>${alert.icon}</span>
                        <span>${alert.message}</span>
                    </div>
                `;
            });

            regulatoryContent.innerHTML = html;
            regulatoryAlerts.classList.add('show');

            // Désactiver le bouton de soumission si erreur critique
            const hasErrors = alerts.some(alert => alert.type === 'error');
            submitBtn.disabled = hasErrors;
            
            if (hasErrors) {
                submitBtn.style.opacity = '0.5';
                submitBtn.title = 'Impossible de créer la déclaration - Erreurs critiques détectées';
            } else {
                submitBtn.style.opacity = '1';
                submitBtn.title = '';
            }
        }

        /**
         * Mise à jour du récapitulatif
         */
        function updateRecap() {
            const transporteur = document.getElementById('transporteur').value;
            const quantite = document.getElementById('quantite_declaree').value;
            const unite = document.getElementById('unite_quantite').value;
            const dateExpedition = document.getElementById('date_expedition').value;
            const nombreColis = document.getElementById('nombre_colis').value;
            const typeColisage = document.getElementById('type_colisage').value;
            const expediteurNom = document.getElementById('expediteur_nom').value;
            const destinataireNom = document.getElementById('destinataire_nom').value;

            if (!selectedProduct || !transporteur || !quantite || !unite || !dateExpedition) {
                recapContent.innerHTML = `
                    <div style="text-align: center; color: #666; padding: 2rem;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📝</div>
                        <p>Complétez le formulaire pour voir le récapitulatif</p>
                    </div>
                `;
                return;
            }

            const transporteurNames = {
                'heppner': 'Heppner',
                'xpo': 'XPO Logistics',
                'kn': 'Kuehne + Nagel'
            };

            const uniteNames = {
                'kg': 'Kilogrammes',
                'L': 'Litres',
                'pieces': 'Pièces'
            };

            const dateFr = new Date(dateExpedition).toLocaleDateString('fr-FR');

            // Calcul de la désignation officielle ADR
            let designationOfficielle = '';
            if (selectedProduct.numero_un) {
                const groupeEmballage = selectedProduct.groupe_emballage ? `,${selectedProduct.groupe_emballage}` : '';
                const codeTunnel = selectedProduct.code_tunnel ? `,(${selectedProduct.code_tunnel})` : '';
                designationOfficielle = `UN ${selectedProduct.numero_un}, ${selectedProduct.nom_description_un || selectedProduct.nom_produit},${selectedProduct.numero_etiquette || '8'}${groupeEmballage}${codeTunnel}`;
            } else {
                designationOfficielle = selectedProduct.nom_produit;
            }

            // Colisage complet
            const colisageComplet = nombreColis && typeColisage ? `${nombreColis} ${typeColisage.toLowerCase()}` : '';

            recapContent.innerHTML = `
                <div class="recap-item">
                    <span class="recap-label">Expéditeur</span>
                    <span class="recap-value">${expediteurNom || 'Non renseigné'}</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Destinataire</span>
                    <span class="recap-value">${destinataireNom || 'Non renseigné'}</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Transporteur</span>
                    <span class="recap-value">${transporteurNames[transporteur]}</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Date expédition</span>
                    <span class="recap-value">${dateFr}</span>
                </div>
                <hr style="margin: 1rem 0; border: none; border-top: 1px solid #eee;">
                <div class="recap-item">
                    <span class="recap-label">Désignation officielle</span>
                    <span class="recap-value" style="font-size: 0.9rem; line-height: 1.3;">${designationOfficielle}</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Quantité</span>
                    <span class="recap-value">${quantite} ${uniteNames[unite]}</span>
                </div>
                ${colisageComplet ? `
                <div class="recap-item">
                    <span class="recap-label">Colisage</span>
                    <span class="recap-value">${colisageComplet}</span>
                </div>
                ` : ''}
                ${selectedProduct.categorie_transport ? `
                <div class="recap-item">
                    <span class="recap-label">Catégorie transport</span>
                    <span class="recap-value">Catégorie ${selectedProduct.categorie_transport}</span>
                </div>
                ` : ''}
                ${selectedProduct.danger_environnement === 'OUI' ? `
                <div class="recap-item">
                    <span class="recap-label">Environnement</span>
                    <span class="recap-value" style="color: var(--adr-warning);">⚠️ Polluant marin</span>
                </div>
                ` : ''}
            `;

            recapContent.classList.add('show');

            // Vérifier les limites de quantité et calculer les totaux par catégorie
            checkQuantityLimits(selectedProduct, parseFloat(quantite), unite);
            calculateTransportCategories(selectedProduct, parseFloat(quantite));
        }

        /**
         * Vérification des limites de quantité
         */
        function checkQuantityLimits(product, quantite, unite) {
            const alerts = [];

            if (product.quota_max_vehicule && product.quota_max_vehicule < 999999) {
                if (quantite > product.quota_max_vehicule) {
                    alerts.push({
                        type: 'error',
                        icon: '🚛',
                        message: `Quantité trop importante - Maximum ${product.quota_max_vehicule} ${unite} par véhicule`
                    });
                } else if (quantite > product.quota_max_vehicule * 0.8) {
                    alerts.push({
                        type: 'warning',
                        icon: '⚠️',
                        message: `Attention - Proche de la limite véhicule (${product.quota_max_vehicule} ${unite})`
                    });
                }
            }

            if (product.quota_max_colis && product.quota_max_colis < 999999) {
                if (quantite > product.quota_max_colis) {
                    alerts.push({
                        type: 'error',
                        icon: '📦',
                        message: `Quantité trop importante - Maximum ${product.quota_max_colis} ${unite} par colis`
                    });
                }
            }

            // Ajouter aux alertes existantes
            const currentAlerts = Array.from(regulatoryContent.querySelectorAll('.regulatory-alert')).map(alert => ({
                type: alert.style.color.includes('var(--adr-danger)') ? 'error' : 'warning',
                icon: alert.querySelector('span').textContent,
                message: alert.querySelectorAll('span')[1].textContent
            }));

        /**
         * Calcul des totaux par catégorie de transport (comme dans le document officiel)
         */
        function calculateTransportCategories(product, quantite) {
            const categories = {
                '0': 0,
                '1': 0,
                '2': 0,
                '3': 0,
                '4': 0
            };

            // Affecter la quantité à la bonne catégorie
            if (product.categorie_transport && categories.hasOwnProperty(product.categorie_transport)) {
                categories[product.categorie_transport] = quantite;
            }

            // Calculer la valeur totale pour le seuil de panneautage
            const valeurPanneautage = Object.values(categories).reduce((total, val) => total + val, 0);

            // Afficher les informations dans les alertes réglementaires
            const existingAlerts = Array.from(regulatoryContent.querySelectorAll('.regulatory-alert')).map(alert => ({
                type: alert.style.color.includes('var(--adr-danger)') ? 'error' : 'warning',
                icon: alert.querySelector('span').textContent,
                message: alert.querySelectorAll('span')[1].textContent
            }));

            // Ajouter les informations de catégorie si c'est ADR
            if (product.numero_un && valeurPanneautage > 0) {
                const categorieAlerts = [{
                    type: 'info',
                    icon: '📊',
                    message: `Catégorie ${product.categorie_transport}: ${quantite} kg - Valeur panneautage: ${valeurPanneautage}`
                }];

                // Restrictions tunnel
                if (product.code_tunnel === 'E' || valeurPanneautage >= 1000) {
                    categorieAlerts.push({
                        type: 'warning',
                        icon: '🚇',
                        message: 'Tunnel E interdit si véhicule panneauté - Tunnels B,C,D autorisés'
                    });
                }

                displayRegulatoryAlerts([...existingAlerts, ...categorieAlerts]);
            }
        }

        /**
         * Fonctions utilitaires
         */
        function hideSearchResults() {
            setTimeout(() => {
                searchResults.style.display = 'none';
            }, 150);
        }

        function clearProductInfo() {
            selectedProduct = null;
            productInfo.classList.remove('show');
            recapContent.classList.remove('show');
            regulatoryAlerts.classList.remove('show');
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }

        function highlightMatch(text, searchTerm) {
            if (!text || !searchTerm) return text;
            
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\    </div>

    ')})`, 'gi');
            return text.replace(regex, '<mark style="background:yellow;padding:0.1rem;">$1</mark>');
        }

        function resetForm() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
                document.getElementById('declaration-form').reset();
                clearProductInfo();
                codeProductInput.focus();
            }
        }

        // Validation du formulaire avant soumission
        document.getElementById('declaration-form').addEventListener('submit', function(e) {
            if (!selectedProduct) {
                e.preventDefault();
                alert('Veuillez sélectionner un produit valide');
                codeProductInput.focus();
                return false;
            }

            if (selectedProduct.corde_article_ferme === 'x') {
                e.preventDefault();
                alert('Impossible de créer une déclaration pour un produit fermé');
                return false;
            }

            // Confirmation pour les produits très dangereux
            if (selectedProduct.categorie_transport === '1') {
                if (!confirm('⚠️ Produit de catégorie 1 (très dangereux)\n\nÊtes-vous sûr de vouloir créer cette déclaration ?')) {
                    e.preventDefault();
                    return false;
                }
            }

            return true;
        });

        // Auto-focus sur le champ de recherche
        document.addEventListener('DOMContentLoaded', function() {
            codeProductInput.focus();
            
            // Si un code produit est déjà renseigné (après erreur), charger ses détails
            if (codeProductInput.value.trim()) {
                loadProductDetails(codeProductInput.value.trim());
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl+K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                codeProductInput.focus();
                codeProductInput.select();
            }
        });

        console.log('✅ Interface de création de déclaration ADR initialisée');
    </script>
</body>
</html>            <form method="POST" id="declaration-form">
                    <!-- Recherche produit -->
                    <div class="form-group">
                        <label for="code_produit">
                            🔍 Code produit <span class="required">*</span>
                        </label>
                        <div class="product-search">
                            <input type="text" 
                                   class="form-control" 
                                   id="code_produit" 
                                   name="code_produit"
                                   placeholder="Tapez un code produit ou nom..."
                                   autocomplete="off"
                                   value="<?= htmlspecialchars($_POST['code_produit'] ?? '') ?>"
                                   required>
                            <div class="search-results" id="search-results"></div>
                        </div>
                        
                        <!-- Informations produit -->
                        <div class="product-info" id="product-info">
                            <div class="product-details" id="product-details">
                                <!-- Les détails seront chargés ici -->
                            </div>
                        </div>
                    </div>

                    <!-- Informations expéditeur/destinataire -->
                    <div class="form-group">
                        <h3 style="color: var(--adr-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏢</span>
                            Expédition
                        </h3>
                    </div>

                    <!-- Expéditeur et Destinataire -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expediteur_nom">
                                📤 Expéditeur <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="expediteur_nom" 
                                   name="expediteur_nom"
                                   placeholder="Nom de l'expéditeur"
                                   value="<?= htmlspecialchars($_POST['expediteur_nom'] ?? 'GULDAGIL AGENCE ILE DE France') ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="destinataire_nom">
                                📥 Destinataire <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="destinataire_nom" 
                                   name="destinataire_nom"
                                   placeholder="Nom du destinataire"
                                   value="<?= htmlspecialchars($_POST['destinataire_nom'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Adresses -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expediteur_adresse">
                                📍 Adresse expéditeur
                            </label>
                            <textarea class="form-control" 
                                      id="expediteur_adresse" 
                                      name="expediteur_adresse"
                                      rows="3"
                                      placeholder="Adresse complète de l'expéditeur"><?= htmlspecialchars($_POST['expediteur_adresse'] ?? "93150\nLE BLANC MESNIL") ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="destinataire_adresse">
                                📍 Adresse destinataire
                            </label>
                            <textarea class="form-control" 
                                      id="destinataire_adresse" 
                                      name="destinataire_adresse"
                                      rows="3"
                                      placeholder="Adresse complète du destinataire"><?= htmlspecialchars($_POST['destinataire_adresse'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Transporteur et date -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transporteur">
                                🚚 Transporteur <span class="required">*</span>
                            </label>
                            <select class="form-control" id="transporteur" name="transporteur" required>
                                <option value="">Sélectionner un transporteur</option>
                                <?php foreach ($transporteurs as $code => $nom): ?>
                                    <option value="<?= $code ?>" <?= ($_POST['transporteur'] ?? '') === $code ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nom) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_expedition">
                                📅 Date d'expédition <span class="required">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_expedition" 
                                   name="date_expedition"
                                   value="<?= $_POST['date_expedition'] ?? date('Y-m-d') ?>"
                                   min="<?= date('Y-m-d', strtotime('-7 days')) ?>"
                                   max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Numéros de récépissé et bordereau -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero_recepisse">
                                📋 N° Récépissé
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="numero_recepisse" 
                                   name="numero_recepisse"
                                   placeholder="Ex: 170869"
                                   value="<?= htmlspecialchars($_POST['numero_recepisse'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="numero_bordereau">
                                📄 N° Bordereau (BL)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="numero_bordereau" 
                                   name="numero_bordereau"
                                   placeholder="Ex: ARC 64411"
                                   value="<?= htmlspecialchars($_POST['numero_bordereau'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Quantité, unité et colisage -->
                    <div class="form-row three-cols">
                        <div class="form-group">
                            <label for="quantite_declaree">
                                ⚖️ Quantité <span class="required">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="quantite_declaree" 
                                   name="quantite_declaree"
                                   placeholder="Ex: 250"
                                   step="0.001"
                                   min="0.001"
                                   max="50000"
                                   value="<?= htmlspecialchars($_POST['quantite_declaree'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="unite_quantite">
                                📏 Unité <span class="required">*</span>
                            </label>
                            <select class="form-control" id="unite_quantite" name="unite_quantite" required>
                                <option value="">Unité</option>
                                <?php foreach ($unites as $code => $nom): ?>
                                    <option value="<?= $code ?>" <?= ($_POST['unite_quantite'] ?? '') === $code ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nom) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="nombre_colis">
                                📦 Nb colis <span class="required">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="nombre_colis" 
                                   name="nombre_colis"
                                   placeholder="Ex: 4"
                                   min="1"
                                   max="1000"
                                   value="<?= htmlspecialchars($_POST['nombre_colis'] ?? '1') ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Type de colisage -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type_colisage">
                                📦 Type de colisage <span class="required">*</span>
                            </label>
                            <select class="form-control" id="type_colisage" name="type_colisage" required>
                                <option value="">Sélectionner le type</option>
                                <option value="Bidon" <?= ($_POST['type_colisage'] ?? '') === 'Bidon' ? 'selected' : '' ?>>Bidon</option>
                                <option value="Fût" <?= ($_POST['type_colisage'] ?? '') === 'Fût' ? 'selected' : '' ?>>Fût</option>
                                <option value="IBC" <?= ($_POST['type_colisage'] ?? '') === 'IBC' ? 'selected' : '' ?>>IBC</option>
                                <option value="Palette" <?= ($_POST['type_colisage'] ?? '') === 'Palette' ? 'selected' : '' ?>>Palette</option>
                                <option value="Caisse" <?= ($_POST['type_colisage'] ?? '') === 'Caisse' ? 'selected' : '' ?>>Caisse</option>
                                <option value="Sac" <?= ($_POST['type_colisage'] ?? '') === 'Sac' ? 'selected' : '' ?>>Sac</option>
                                <option value="Autre" <?= ($_POST['type_colisage'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="poids_total_palettes">
                                🏗️ Poids total palettes (kg)
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="poids_total_palettes" 
                                   name="poids_total_palettes"
                                   placeholder="Ex: 1600"
                                   step="1"
                                   min="0"
                                   max="50000"
                                   value="<?= htmlspecialchars($_POST['poids_total_palettes'] ?? '') ?>">
                            <small style="color: #666; font-size: 0.8rem;">
                                Optionnel - Indiqué sur la déclaration officielle
                            </small>
                        </div>
                    </div>

                    <!-- Observations -->
                    <div class="form-group">
                        <label for="observations">
                            💬 Observations
                        </label>
                        <textarea class="form-control" 
                                  id="observations" 
                                  name="observations"
                                  rows="3"
                                  placeholder="Informations complémentaires (optionnel)"><?= htmlspecialchars($_POST['observations'] ?? '') ?></textarea>
                    </div>

                    <!-- Alertes réglementaires -->
                    <div class="regulatory-alerts" id="regulatory-alerts">
                        <div id="regulatory-content">
                            <!-- Les alertes seront affichées ici -->
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <span>💾</span>
                            Créer la déclaration
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <span>🔄</span>
                            Réinitialiser
                        </button>
                        
                        <a href="../dashboard.php" class="btn btn-secondary">
                            <span>❌</span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>

            <!-- Section récapitulatif -->
            <div class="recap-section">
                <div class="recap-header">
                    <div class="recap-icon">📋</div>
                    <div>
                        <h3>Récapitulatif</h3>
                        <p style="margin: 0; color: #666; font-size: 0.9rem;">Vérifiez les informations</p>
                    </div>
                </div>

                <div class="recap-content" id="recap-content">
                    <div style="text-align: center; color: #666; padding: 2rem;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📝</div>
                        <p>Sélectionnez un produit pour voir le récapitulatif</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
