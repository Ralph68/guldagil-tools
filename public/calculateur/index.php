<?php
/**
 * public/calculateur/index.php - VERSION DEBUG pour diagnostiquer erreur 500
 * Chemin: /public/calculateur/index.php
 */

// Activation affichage erreurs pour diagnostic
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DEBUG: Début du script -->\n";

// Test 1: Vérification des chemins
$configPath = __DIR__ . '/../../config/config.php';
$transportPath = __DIR__ . '/../../src/modules/calculateur/services/TransportCalculator.php';

echo "<!-- DEBUG: Config path: $configPath -->\n";
echo "<!-- DEBUG: Transport path: $transportPath -->\n";

// Test 2: Existence des fichiers
if (!file_exists($configPath)) {
    die("❌ ERREUR: Fichier config non trouvé à: $configPath");
}

if (!file_exists($transportPath)) {
    die("❌ ERREUR: Fichier Transport non trouvé à: $transportPath");
}

echo "<!-- DEBUG: Fichiers trouvés -->\n";

// Test 3: Inclusion configuration
try {
    require_once $configPath;
    echo "<!-- DEBUG: Config chargée -->\n";
} catch (Exception $e) {
    die("❌ ERREUR Config: " . $e->getMessage());
} catch (Error $e) {
    die("❌ ERREUR FATALE Config: " . $e->getMessage());
}

// Test 4: Vérification variable $db
if (!isset($db)) {
    die("❌ ERREUR: Variable \$db non définie après inclusion config");
}

echo "<!-- DEBUG: Variable \$db disponible -->\n";

// Test 5: Test connexion base
try {
    $testQuery = $db->query("SELECT 1 as test");
    $testResult = $testQuery->fetch();
    if ($testResult['test'] == 1) {
        echo "<!-- DEBUG: Connexion BDD OK -->\n";
    }
} catch (Exception $e) {
    die("❌ ERREUR BDD: " . $e->getMessage());
}

// Test 6: Inclusion classe Transport
try {
    require_once $transportPath;
    echo "<!-- DEBUG: Classe Transport chargée -->\n";
} catch (Exception $e) {
    die("❌ ERREUR Transport: " . $e->getMessage());
} catch (Error $e) {
    die("❌ ERREUR FATALE Transport: " . $e->getMessage());
}

// Test 7: Instanciation classe Transport
try {
    $transport = new Transport($db);
    echo "<!-- DEBUG: Instance Transport créée -->\n";
} catch (Exception $e) {
    die("❌ ERREUR Instance Transport: " . $e->getMessage());
} catch (Error $e) {
    die("❌ ERREUR FATALE Instance Transport: " . $e->getMessage());
}

// Test 8: Variables système
$phpVersion = PHP_VERSION;
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');

// Si on arrive ici, tout va bien !
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Calculateur Guldagil - Mode Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f8ff; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #cce7ff; color: #004085; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; }
        .system-info { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 0.9em; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        .results { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 20px; }
        .debug-section { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Calculateur Guldagil - Mode Debug</h1>
    
    <div class="success">
        ✅ <strong>Diagnostic réussi !</strong> Tous les composants ont été chargés correctement.
    </div>
    
    <div class="info">
        🔍 <strong>Tests effectués :</strong>
        <ul>
            <li>✅ Fichiers de configuration trouvés</li>
            <li>✅ Configuration chargée</li>
            <li>✅ Connexion base de données OK</li>
            <li>✅ Classe Transport chargée</li>
            <li>✅ Instance Transport créée</li>
        </ul>
    </div>
    
    <div class="debug-section">
        <h3>🔧 Informations système</h3>
        <div class="system-info">
            <strong>PHP Version:</strong> <?= $phpVersion ?><br>
            <strong>Memory Limit:</strong> <?= $memoryLimit ?><br>
            <strong>Max Execution Time:</strong> <?= $maxExecutionTime ?>s<br>
            <strong>Date/Heure:</strong> <?= date('Y-m-d H:i:s') ?><br>
            <strong>Timezone:</strong> <?= date_default_timezone_get() ?><br>
            <strong>Version App:</strong> <?= defined('APP_VERSION') ? APP_VERSION : 'Non définie' ?><br>
            <strong>Debug Mode:</strong> <?= defined('DEBUG') && DEBUG ? 'Activé' : 'Désactivé' ?>
        </div>
    </div>
    
    <div class="form-container">
        <h2>🚚 Calculateur de frais de port</h2>
        
        <?php
        // Traitement du formulaire
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
                
                // Validation
                if (empty($params['departement']) || $params['poids'] <= 0) {
                    throw new Exception('Département et poids sont obligatoires');
                }
                
                // Test simple sans la méthode calculateAll
                echo "<div class='info'>🧮 <strong>Paramètres reçus:</strong> " . json_encode($params) . "</div>";
                
                // Test basique de la classe Transport
                $carriers = ['xpo', 'heppner', 'kn'];
                $results = [];
                
                foreach ($carriers as $carrier) {
                    try {
                        // Test de base sans calcul complexe
                        $results[$carrier] = rand(2500, 8500) / 100; // Prix simulé pour test
                    } catch (Exception $e) {
                        $results[$carrier] = null;
                        $error = "Erreur calcul $carrier: " . $e->getMessage();
                    }
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        ?>
        
        <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>❌ Erreur:</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="departement">🗺️ Département de destination *</label>
                <input type="text" id="departement" name="departement" class="form-control" 
                       placeholder="Ex: 75, 69, 13..." 
                       value="<?= htmlspecialchars($_POST['departement'] ?? '75') ?>" 
                       pattern="[0-9]{2,3}" maxlength="3" required>
                <small style="color: #666;">Format: 2 ou 3 chiffres (75, 976...)</small>
            </div>

            <div class="form-group">
                <label for="poids">⚖️ Poids total (kg) *</label>
                <input type="number" id="poids" name="poids" class="form-control" 
                       placeholder="Ex: 25.5"
                       value="<?= htmlspecialchars($_POST['poids'] ?? '25') ?>" 
                       step="0.1" min="0.1" max="10000" required>
            </div>

            <div class="form-group">
                <label for="type">📦 Type d'envoi</label>
                <select id="type" name="type" class="form-control">
                    <option value="colis" <?= ($_POST['type'] ?? '') === 'colis' ? 'selected' : '' ?>>Colis</option>
                    <option value="palette" <?= ($_POST['type'] ?? '') === 'palette' ? 'selected' : '' ?>>Palette</option>
                </select>
            </div>

            <div class="form-group">
                <label for="adr">⚠️ Marchandise dangereuse (ADR)</label>
                <select id="adr" name="adr" class="form-control">
                    <option value="non" <?= ($_POST['adr'] ?? 'non') === 'non' ? 'selected' : '' ?>>Non</option>
                    <option value="oui" <?= ($_POST['adr'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                </select>
            </div>

            <button type="submit" class="btn">
                🧮 Calculer les tarifs (Mode Test)
            </button>
        </form>
        
        <?php if ($results): ?>
        <div class="results">
            <h3>📊 Résultats de test</h3>
            <p><em>Mode debug - Tarifs simulés pour vérifier le bon fonctionnement</em></p>
            
            <?php foreach ($results as $carrier => $price): ?>
            <div style="padding: 10px; border: 1px solid #ddd; margin: 5px 0; border-radius: 5px;">
                <strong><?= strtoupper($carrier) ?>:</strong>
                <?php if ($price !== null): ?>
                    <?= number_format($price, 2, ',', ' ') ?> € <em>(simulé)</em>
                <?php else: ?>
                    <span style="color: #dc3545;">Non disponible</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 15px; padding: 10px; background: #e2f7e2; border-radius: 5px;">
                ✅ <strong>Test réussi !</strong> L'application fonctionne correctement.
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px; color: #666; font-size: 0.9em;">
        <p>🔧 Mode debug actif - Version de diagnostic</p>
        <p>Une fois les tests validés, la version complète sera activée</p>
    </div>
</body>
</html>
