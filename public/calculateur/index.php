<?php
/**
 * public/calculateur/index.php - Module Calculateur complet
 * Chemin: /public/calculateur/index.php
 * 
 * CORRECTIONS:
 * - Utilise config/config.php (pas /config.php à la racine)
 * - Utilise la nouvelle classe Transport relocalisée
 */

// Configuration principale
require __DIR__ . '/../../config/config.php';

// Classe Transport relocalisée
require __DIR__ . '/../../src/modules/calculateur/services/TransportCalculator.php';

// Démarrage session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentification (héritée du portail principal)
$auth_required = false; // Synchronisé avec le portail principal

if ($auth_required && !isset($_SESSION['authenticated'])) {
    header('Location: ../');
    exit;
}

// Initialisation de la classe Transport
try {
    $transport = new Transport($db);
} catch (Exception $e) {
    if (DEBUG) {
        die('Erreur initialisation Transport: ' . $e->getMessage());
    } else {
        die('Service temporairement indisponible');
    }
}

// Traitement du formulaire si soumission
$results = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $params = [
            'departement' => $_POST['departement'] ?? '',
            'poids' => (float)($_POST['poids'] ?? 0),
            'type' => $_POST['type'] ?? 'colis',
            'adr' => $_POST['adr'] ?? 'non',
            'option_sup' => $_POST['option_sup'] ?? 'aucune',
            'enlevement' => isset($_POST['enlevement']),
            'palettes' => (int)($_POST['palettes'] ?? 0)
        ];
        
        // Validation basique
        if (empty($params['departement']) || $params['poids'] <= 0) {
            throw new Exception('Département et poids sont obligatoires');
        }
        
        // Calcul des tarifs
        $results = $transport->calculateAll($params);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur de frais - Portail Guldagil</title>
    <link rel="stylesheet" href="../assets/css/portail-base.css">
    <link rel="stylesheet" href="../assets/css/calculateur-module.css">
    <style>
        /* Styles de base pour le rendu immédiat */
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .module-header { background: #2563eb; color: white; padding: 1rem; margin-bottom: 2rem; border-radius: 8px; }
        .header-brand { display: flex; align-items: center; gap: 1rem; }
        .module-title { margin: 0; font-size: 1.5rem; }
        .module-subtitle { opacity: 0.8; margin: 0; }
        .calculator-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 1200px; }
        .form-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-header h2 { margin: 0 0 0.5rem; color: #2563eb; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 0.75rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .results-section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error-container { background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .debug-info { background: #f3f4f6; padding: 1rem; border-radius: 4px; margin-top: 1rem; font-family: monospace; font-size: 0.9rem; }
        .back-link { text-decoration: none; color: white; font-size: 1.2rem; }
        .header-actions { margin-left: auto; }
        .account-info { opacity: 0.8; }
        @media (max-width: 768px) { .calculator-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Header module -->
    <header class="module-header">
        <div class="header-container">
            <div class="header-brand">
                <a href="../" class="back-link" title="Retour à l'accueil">
                    <span>←</span>
                </a>
                <div class="header-info">
                    <h1 class="module-title">Calculateur de frais</h1>
                    <p class="module-subtitle">Interface complète</p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="account-info">
                    👨‍💻 Dev | Version <?= APP_VERSION ?> | Build <?= BUILD_NUMBER ?>
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
                <?php if ($error): ?>
                <div class="error-container">
                    <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <!-- Formulaire principal -->
                <form method="POST" id="calculator-form">
                    <div class="form-group">
                        <label for="departement">Département de destination *</label>
                        <input type="text" id="departement" name="departement" class="form-control" 
                               placeholder="Ex: 75, 69, 13..." 
                               value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>" 
                               pattern="[0-9]{2,3}" maxlength="3" required>
                        <small>Format: 2 ou 3 chiffres (75, 976...)</small>
                    </div>

                    <div class="form-group">
                        <label for="poids">Poids total (kg) *</label>
                        <input type="number" id="poids" name="poids" class="form-control" 
                               placeholder="Ex: 25.5"
                               value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>" 
                               step="0.1" min="0.1" max="10000" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Type d'envoi</label>
                        <select id="type" name="type" class="form-control">
                            <option value="colis" <?= ($_POST['type'] ?? '') === 'colis' ? 'selected' : '' ?>>Colis</option>
                            <option value="palette" <?= ($_POST['type'] ?? '') === 'palette' ? 'selected' : '' ?>>Palette</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="adr">Marchandise dangereuse (ADR)</label>
                        <select id="adr" name="adr" class="form-control">
                            <option value="non" <?= ($_POST['adr'] ?? 'non') === 'non' ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= ($_POST['adr'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="option_sup">Options supplémentaires</label>
                        <select id="option_sup" name="option_sup" class="form-control">
                            <option value="aucune" <?= ($_POST['option_sup'] ?? 'aucune') === 'aucune' ? 'selected' : '' ?>>Aucune</option>
                            <option value="rdv" <?= ($_POST['option_sup'] ?? '') === 'rdv' ? 'selected' : '' ?>>Prise de RDV</option>
                            <option value="datefixe" <?= ($_POST['option_sup'] ?? '') === 'datefixe' ? 'selected' : '' ?>>Livraison date fixe</option>
                            <option value="premium13" <?= ($_POST['option_sup'] ?? '') === 'premium13' ? 'selected' : '' ?>>Premium avant 13h</option>
                            <option value="premium18" <?= ($_POST['option_sup'] ?? '') === 'premium18' ? 'selected' : '' ?>>Premium avant 18h</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="palettes">Nombre de palettes EUR</label>
                        <input type="number" id="palettes" name="palettes" class="form-control" 
                               value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>" 
                               min="0" max="100">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enlevement" value="1" 
                                   <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                            Enlèvement à domicile
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        🧮 Calculer les tarifs
                    </button>
                </form>
            </div>
        </section>

        <!-- Colonne résultats (droite) -->
        <section class="results-section">
            <div class="results-header">
                <h2>📊 Résultats de calcul</h2>
            </div>

            <?php if ($results): ?>
                <div class="results-content">
                    <h3>Meilleur tarif</h3>
                    <?php if (isset($results['best']) && $results['best']): ?>
                        <div class="best-result">
                            <strong><?= htmlspecialchars($results['best']['carrier']) ?></strong>: 
                            <?= number_format($results['best']['price'], 2, ',', ' ') ?> €
                        </div>
                    <?php else: ?>
                        <p>Aucun tarif disponible pour ces critères.</p>
                    <?php endif; ?>

                    <h3>Comparaison complète</h3>
                    <div class="comparison-table">
                        <?php foreach ($results['results'] as $carrier => $price): ?>
                            <div class="comparison-row">
                                <span><?= htmlspecialchars(strtoupper($carrier)) ?></span>
                                <span>
                                    <?php if ($price !== null): ?>
                                        <?= number_format($price, 2, ',', ' ') ?> €
                                    <?php else: ?>
                                        Non disponible
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (DEBUG && !empty($results['debug'])): ?>
                    <div class="debug-info">
                        <h4>Informations de débogage</h4>
                        <pre><?= htmlspecialchars(print_r($results['debug'], true)) ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>👆 Remplissez le formulaire pour obtenir vos tarifs</p>
                    <ul>
                        <li>✅ Comparaison de 3 transporteurs</li>
                        <li>✅ Calcul en temps réel</li>
                        <li>✅ Prise en compte des options</li>
                        <li>✅ Gestion des marchandises dangereuses</li>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer style="text-align: center; margin-top: 2rem; padding: 1rem; color: #666;">
        <p>© <?= COPYRIGHT_YEAR ?> Guldagil - Calculateur de frais de port</p>
        <p>
            <small>
                Version <?= APP_VERSION ?> | 
                Build #<?= BUILD_NUMBER ?> | 
                <?= formatDate(BUILD_DATE) ?>
                <?php if (DEBUG): ?> | 🐛 MODE DEBUG<?php endif; ?>
            </small>
        </p>
    </footer>

    <script>
        // Auto-focus sur le premier champ
        document.getElementById('departement').focus();
        
        // Validation en temps réel du département
        document.getElementById('departement').addEventListener('input', function(e) {
            const value = e.target.value;
            if (value && !/^[0-9]{2,3}$/.test(value)) {
                e.target.style.borderColor = '#dc2626';
            } else {
                e.target.style.borderColor = '#ddd';
            }
        });
        
        // Soumission automatique en mode debug
        <?php if (DEBUG): ?>
        console.log('🐛 Mode debug activé');
        <?php endif; ?>
    </script>
</body>
</html>
