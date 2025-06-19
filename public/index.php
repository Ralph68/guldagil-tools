<?php
// public/index.php - VERSION CORRIGÉE
require __DIR__ . '/../config.php';

// Configuration de la page
$pageTitle = 'Calculateur de Frais de Port';
$currentPage = 'calculator';

// Chargement des options depuis la base de données
try {
    $stmt = $db->query("
        SELECT DISTINCT transporteur, code_option, libelle, montant, unite 
        FROM gul_options_supplementaires 
        WHERE actif = 1 
        ORDER BY transporteur, montant
    ");
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $options = [];
    error_log("Erreur chargement options: " . $e->getMessage());
}

// Version et build
require __DIR__ . '/../config/version.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Guldagil</title>
    
    <!-- Meta tags -->
    <meta name="description" content="Calculateur et comparateur de frais de port Guldagil pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
    <meta name="robots" content="noindex, nofollow"> <!-- En développement -->
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/calculator.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- Preload des ressources critiques -->
    <link rel="preload" href="/assets/js/calculator.js" as="script">
</head>
<body>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="/assets/img/logo_guldagil.png" alt="Guldagil" height="40">
                    <span class="logo-text">Port Calculator</span>
                </div>
                
                <nav class="nav">
                    <a href="/" class="nav-link active">
                        📊 Calculateur
                    </a>
                    <a href="/admin" class="nav-link">
                        ⚙️ Administration
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="calculator-container">
            
            <!-- En-tête de page -->
            <div class="page-header">
                <h1>🚛 Calculateur de Frais de Port</h1>
                <p class="page-description">
                    Comparez instantanément les tarifs XPO, Heppner et Kuehne+Nagel selon vos critères d'envoi
                </p>
            </div>

            <!-- Formulaire de calcul -->
            <form id="calculator-form" class="calculator-form" novalidate>
                
                <div class="form-grid">
                    
                    <!-- Département -->
                    <div class="form-group">
                        <label for="departement" class="form-label required">
                            📍 Département de livraison
                        </label>
                        <input 
                            type="text" 
                            id="departement" 
                            name="departement" 
                            class="form-control" 
                            placeholder="ex: 67, 75, 13..."
                            pattern="[0-9]{1,2}"
                            maxlength="2"
                            required
                        >
                        <div class="field-feedback"></div>
                    </div>

                    <!-- Poids -->
                    <div class="form-group">
                        <label for="poids" class="form-label required">
                            ⚖️ Poids total (kg)
                        </label>
                        <input 
                            type="number" 
                            id="poids" 
                            name="poids" 
                            class="form-control" 
                            placeholder="ex: 150"
                            min="1"
                            max="10000"
                            step="0.1"
                            required
                        >
                        <div class="field-feedback"></div>
                    </div>

                    <!-- Type d'envoi -->
                    <div class="form-group">
                        <label for="type" class="form-label required">
                            📦 Type d'envoi
                        </label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Sélectionner...</option>
                            <option value="colis">Colis</option>
                            <option value="palette">Palette</option>
                        </select>
                        <div class="field-feedback"></div>
                    </div>

                    <!-- Options de service -->
                    <div class="form-group">
                        <label for="option_sup" class="form-label">
                            ⭐ Options de service
                        </label>
                        <select id="option_sup" name="option_sup" class="form-control">
                            <option value="standard">Standard (24-48h)</option>
                            <optgroup label="Services Premium">
                                <option value="rdv">Prise de RDV</option>
                                <option value="star18">Star avant 18h</option>
                                <option value="star13">Star avant 13h</option>
                                <option value="premium18">Premium avant 18h (XPO)</option>
                                <option value="premium13">Premium avant 13h (XPO)</option>
                                <option value="datefixe18">Date fixe avant 18h</option>
                                <option value="datefixe13">Date fixe avant 13h</option>
                            </optgroup>
                        </select>
                        <div class="field-feedback"></div>
                    </div>

                </div>

                <!-- Palettes EUR (affiché conditionnellement) -->
                <div class="form-group palettes-group">
                    <label for="palettes" class="form-label">
                        🏭 Nombre de palettes EUR
                    </label>
                    <input 
                        type="number" 
                        id="palettes" 
                        name="palettes" 
                        class="form-control" 
                        placeholder="0"
                        min="0"
                        max="100"
                        value="0"
                    >
                    <small class="text-muted">
                        Coût supplémentaire : XPO 1,80€/pal • K+N 6,50€/pal • Heppner gratuit
                    </small>
                </div>

                <!-- Cases à cocher -->
                <div class="form-grid">
                    <div class="checkbox-group">
                        <input type="checkbox" id="adr" name="adr" value="oui">
                        <label for="adr">
                            ☣️ Matières dangereuses (ADR)
                        </label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                        <label for="enlevement">
                            🚚 Enlèvement (+25€ XPO/K+N, gratuit Heppner)
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="calculate-btn">
                        <span>🔍</span>
                        Calculer et Comparer
                    </button>
                </div>

            </form>

            <!-- Zone de résultats -->
            <div id="results-container" class="results-container" style="display: none;">
                <!-- Les résultats seront injectés ici par JavaScript -->
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <p>&copy; <?= date('Y') ?> Guldagil - Tous droits réservés</p>
                </div>
                
                <div class="footer-version">
                    <span class="version">v<?= defined('APP_VERSION') ? APP_VERSION : '2.0.0' ?></span>
                    <span class="build">Build #<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') ?>001</span>
                    <span class="date"><?= defined('BUILD_DATE') ? date('d/m/Y H:i', strtotime(BUILD_DATE)) : date('d/m/Y H:i') ?></span>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/assets/js/calculator.js"></script>
    
    <!-- Analytics & Debug -->
    <?php if (defined('DEBUG') && DEBUG): ?>
    <script>
        console.log('🐛 Mode debug activé');
        console.log('📊 Options chargées:', <?= json_encode($options) ?>);
    </script>
    <?php endif; ?>

    <!-- Service Worker pour mise en cache (production) -->
    <?php if (!defined('DEBUG') || !DEBUG): ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            });
        }
    </script>
    <?php endif; ?>

</body>
</html>

<style>
/* Styles inline pour des améliorations spécifiques */
.header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo-text {
    font-size: 1.25rem;
    font-weight: 700;
}

.nav {
    display: flex;
    gap: 1rem;
}

.nav-link {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.nav-link:hover,
.nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 1rem;
}

.page-description {
    font-size: 1.125rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .page-description {
        font-size: 1rem;
    }
}
</style>
