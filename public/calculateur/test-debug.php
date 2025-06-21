<?php
/**
 * Titre: Interface de test calculateur simple - Test des calculs uniquement
 * Chemin: /public/calculateur/test-debug.php
 * Version: 0.5 beta - Interface debug minimaliste
 */

// Configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Démarrage session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Traitement du formulaire et calcul
$results = null;
$debug_info = [];
$calculation_time = 0;

if ($_POST) {
    $start_time = microtime(true);
    
    // Paramètres du formulaire
    $params = [
        'departement' => trim($_POST['departement'] ?? ''),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => $_POST['type'] ?? 'colis',
        'adr' => $_POST['adr'] ?? 'non',
        'option_sup' => $_POST['option_sup'] ?? 'aucune',
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => intval($_POST['palettes'] ?? 0)
    ];
    
    try {
        // Appel AJAX comme dans le vrai calculateur
        $ajax_url = __DIR__ . '/ajax-calculate.php';
        
        // Simulation de l'appel AJAX en incluant directement le fichier
        $_POST = $params; // Préparer les données POST
        
        ob_start();
        include $ajax_url;
        $ajax_response = ob_get_clean();
        
        // Parser la réponse JSON
        $results = json_decode($ajax_response, true);
        
        if ($results === null) {
            throw new Exception("Erreur de parsing JSON: " . $ajax_response);
        }
        
        $calculation_time = round((microtime(true) - $start_time) * 1000, 2);
        
    } catch (Exception $e) {
        $results = ['error' => $e->getMessage()];
        $debug_info = ['exception' => $e->getTraceAsString()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Calculateur - Debug Simple</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.5; }
        .container { max-width: 1000px; margin: 0 auto; }
        .form-box { background: #f5f5f5; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .results-box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .form-row { margin-bottom: 15px; }
        label { display: inline-block; width: 150px; font-weight: bold; }
        input, select { padding: 5px; margin-left: 10px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        .debug { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #333; }
        .carrier { margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #fafafa; }
        .best { border-color: #4caf50; background: #f1f8e9; }
        .error { color: red; background: #ffebee; padding: 10px; }
        pre { background: white; padding: 10px; overflow: auto; font-size: 12px; }
        .step { margin: 15px 0; padding: 10px; background: white; border-left: 3px solid #007cba; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Calculateur - Mode Debug</h1>
        <p>Interface simple pour vérifier les calculs étape par étape</p>
        
        <div class="form-box">
            <h2>Paramètres de test</h2>
            <form method="POST">
                <div class="form-row">
                    <label>Département:</label>
                    <input type="text" name="departement" value="<?= htmlspecialchars($_POST['departement'] ?? '67') ?>" maxlength="2" required>
                    <span style="color: #666; font-size: 12px;">(2 chiffres)</span>
                </div>
                
                <div class="form-row">
                    <label>Poids (kg):</label>
                    <input type="number" name="poids" value="<?= htmlspecialchars($_POST['poids'] ?? '25') ?>" step="0.1" min="0.1" required>
                </div>
                
                <div class="form-row">
                    <label>Type:</label>
                    <select name="type">
                        <option value="colis" <?= ($_POST['type'] ?? 'colis') === 'colis' ? 'selected' : '' ?>>Colis</option>
                        <option value="palette" <?= ($_POST['type'] ?? '') === 'palette' ? 'selected' : '' ?>>Palette</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label>ADR:</label>
                    <select name="adr">
                        <option value="non" <?= ($_POST['adr'] ?? 'non') === 'non' ? 'selected' : '' ?>>Non</option>
                        <option value="oui" <?= ($_POST['adr'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label>Option:</label>
                    <select name="option_sup">
                        <option value="aucune" <?= ($_POST['option_sup'] ?? 'aucune') === 'aucune' ? 'selected' : '' ?>>Aucune</option>
                        <option value="rdv" <?= ($_POST['option_sup'] ?? '') === 'rdv' ? 'selected' : '' ?>>Prise de RDV</option>
                        <option value="datefixe" <?= ($_POST['option_sup'] ?? '') === 'datefixe' ? 'selected' : '' ?>>Date fixe</option>
                        <option value="premium13" <?= ($_POST['option_sup'] ?? '') === 'premium13' ? 'selected' : '' ?>>Premium 13h</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label>Palettes:</label>
                    <input type="number" name="palettes" value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>" min="0">
                </div>
                
                <div class="form-row">
                    <label>Enlèvement:</label>
                    <input type="checkbox" name="enlevement" <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                </div>
                
                <button type="submit">🚀 Calculer</button>
            </form>
        </div>

        <?php if ($results): ?>
        <div class="results-box">
            <h2>📊 Résultats (<?= $calculation_time ?> ms)</h2>
            
            <?php if (isset($results['error'])): ?>
                <div class="error">
                    <h3>❌ Erreur</h3>
                    <p><?= htmlspecialchars($results['error']) ?></p>
                </div>
            <?php else: ?>
                
                <!-- Paramètres utilisés -->
                <div class="debug">
                    <h3>📋 Paramètres envoyés</h3>
                    <pre><?= htmlspecialchars(print_r($params, true)) ?></pre>
                </div>

                <!-- Meilleur tarif -->
                <?php if (isset($results['best_rate'])): ?>
                <div style="background: #e8f5e8; padding: 15px; margin: 15px 0; border: 1px solid #4caf50;">
                    <h3>🏆 Meilleur tarif</h3>
                    <p><strong><?= htmlspecialchars($results['best_rate']['carrier_name'] ?? 'N/A') ?></strong>: 
                       <strong><?= htmlspecialchars($results['best_rate']['formatted'] ?? 'N/A') ?></strong></p>
                </div>
                <?php endif; ?>

                <!-- Résultats par transporteur -->
                <h3>🚛 Détail par transporteur</h3>
                <?php if (isset($results['carriers'])): ?>
                    <?php foreach ($results['carriers'] as $carrier => $data): ?>
                    <div class="carrier <?= isset($results['best_rate']) && $results['best_rate']['carrier'] === $carrier ? 'best' : '' ?>">
                        <h4><?= htmlspecialchars($data['name'] ?? $carrier) ?></h4>
                        
                        <?php if ($data['price'] === null): ?>
                            <p style="color: #f44336;">❌ Pas de tarif disponible</p>
                        <?php elseif ($data['price'] <= 0): ?>
                            <p style="color: #ff9800;">⚠️ Tarif invalide: <?= $data['price'] ?></p>
                        <?php else: ?>
                            <p style="color: #4caf50; font-weight: bold;">💰 <?= htmlspecialchars($data['formatted']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($data['debug'])): ?>
                        <div class="debug">
                            <h5>🔍 Détail calcul</h5>
                            <pre><?= htmlspecialchars(print_r($data['debug'], true)) ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Étapes de calcul -->
                <?php if (isset($results['calculation_steps'])): ?>
                <h3>📝 Étapes de calcul</h3>
                <?php foreach ($results['calculation_steps'] as $step): ?>
                <div class="step">
                    <h4><?= htmlspecialchars($step['title'] ?? 'Étape') ?></h4>
                    <p><?= htmlspecialchars($step['description'] ?? '') ?></p>
                    <?php if (isset($step['details'])): ?>
                    <pre><?= htmlspecialchars(print_r($step['details'], true)) ?></pre>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Debug complet -->
                <div class="debug">
                    <h3>🛠️ Réponse complète</h3>
                    <pre><?= htmlspecialchars(print_r($results, true)) ?></pre>
                </div>
                
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Test de base -->
        <div class="debug">
            <h3>🔌 Tests système</h3>
            <?php
            try {
                echo "<p>✅ Connexion DB: OK</p>";
                
                // Test tables
                $tables = ['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates'];
                foreach ($tables as $table) {
                    $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    echo "<p>✅ Table $table: $count lignes</p>";
                }
                
                // Test fichier AJAX
                $ajax_file = __DIR__ . '/ajax-calculate.php';
                echo "<p>" . (file_exists($ajax_file) ? "✅" : "❌") . " Fichier AJAX: $ajax_file</p>";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <p style="text-align: center; margin-top: 30px; color: #666;">
            <a href="index.php">← Retour au calculateur principal</a> | 
            <a href="../">Portail</a>
        </p>
    </div>
</body>
</html>
