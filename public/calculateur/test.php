<?php
// Test minimal - vérifier que PHP fonctionne
require_once __DIR__ . '/../../config.php';

$debug_mode = true;
$page_title = 'Test Calculateur';

function getVersionInfo() {
    return [
        'version' => '0.5.0-beta-test',
        'build' => date('Ymd'),
        'formatted_date' => date('d/m/Y H:i')
    ];
}

$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test</title>
    <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test { background: #f0f0f0; padding: 20px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Test Calculateur</h1>
    
    <div class="test">
        <h2>✅ PHP fonctionne</h2>
        <p>Version: <?= $version_info['version'] ?></p>
        <p>Build: <?= $version_info['build'] ?></p>
    </div>

    <div class="test">
        <h2>Test Base de données</h2>
        <?php
        try {
            if (isset($db)) {
                echo "✅ Connexion DB OK<br>";
                $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires");
                $result = $stmt->fetch();
                echo "Options trouvées: " . $result['count'];
            } else {
                echo "❌ Variable \$db non définie";
            }
        } catch (Exception $e) {
            echo "❌ Erreur DB: " . $e->getMessage();
        }
        ?>
    </div>

    <div class="test">
        <h2>Test Includes</h2>
        <?php
        $headerPath = __DIR__ . '/views/partials/header.php';
        $footerPath = __DIR__ . '/views/partials/footer.php';
        
        echo "Header existe: " . (file_exists($headerPath) ? "✅" : "❌") . "<br>";
        echo "Footer existe: " . (file_exists($footerPath) ? "✅" : "❌") . "<br>";
        ?>
    </div>

    <div class="test">
        <h2>Test Formulaire</h2>
        <form id="test-form">
            <input type="text" placeholder="Département" required>
            <button type="submit">Test</button>
        </form>
    </div>

    <script>
    console.log('✅ JavaScript fonctionne');
    document.getElementById('test-form').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Formulaire fonctionne');
    });
    </script>
</body>
</html>
