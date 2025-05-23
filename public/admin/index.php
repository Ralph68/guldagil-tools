<?php
// DEBUG - public/admin/index.php
echo "PHP fonctionne<br>";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DEBUG Admin</title>
    
    <!-- TEST CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <style>
        /* Fallback si CSS externe ne marche pas */
        .test-fallback {
            border: 5px solid green;
            padding: 10px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="test-fallback">
        <h1>🔧 MODE DEBUG</h1>
        <p>Si vous voyez du rouge/bleu = CSS externe OK</p>
        <p>Si vous voyez du vert = CSS inline OK</p>
    </div>

    <header class="admin-header">
        <h1>Administration TEST</h1>
        <nav class="admin-nav">
            <a href="../">🏠 Retour</a>
        </nav>
    </header>

    <div class="admin-container">
        <h2>Tests des boutons :</h2>
        
        <button onclick="showTab('test')" style="padding: 10px; margin: 5px;">
            Test Onglet
        </button>
        
        <button onclick="alert('Bouton direct fonctionne')" style="padding: 10px; margin: 5px;">
            Test Direct
        </button>
        
        <div id="test-output" style="margin: 20px; padding: 20px; border: 1px solid #ccc;">
            Résultats des tests apparaîtront ici...
        </div>
    </div>

    <!-- TEST JS -->
    <script src="assets/js/admin.js"></script>
    
    <script>
        // Test inline
        console.log('Script inline fonctionne');
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('test-output').innerHTML += '<br>✅ Script inline chargé';
        });
    </script>
</body>
</html>
