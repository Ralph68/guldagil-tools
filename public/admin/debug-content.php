<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DEBUG Contenu</title>
    
    <!-- Test direct du CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <style>
        .debug-box {
            border: 2px solid #000;
            padding: 20px;
            margin: 10px;
            background: #f0f0f0;
        }
        
        /* Test si le CSS externe écrase celui-ci */
        body {
            font-family: Arial;
            background: lightgray !important;
        }
    </style>
</head>
<body>
    <div class="debug-box">
        <h1>🔧 TEST CONTENU</h1>
        
        <h2>Contenu du CSS :</h2>
        <pre><?php 
        $css_file = __DIR__ . '/assets/css/admin-style.css';
        if (file_exists($css_file)) {
            echo htmlspecialchars(file_get_contents($css_file));
        } else {
            echo "Fichier CSS non trouvé";
        }
        ?></pre>
        
        <h2>Contenu du JS :</h2>
        <pre><?php 
        $js_file = __DIR__ . '/assets/js/admin.js';
        if (file_exists($js_file)) {
            echo htmlspecialchars(file_get_contents($js_file));
        } else {
            echo "Fichier JS non trouvé";
        }
        ?></pre>
        
        <h2>Test bouton JS :</h2>
        <button onclick="testFunction()" style="padding: 10px; font-size: 16px;">
            Cliquer pour tester JS
        </button>
        
        <div id="js-result" style="margin: 10px; padding: 10px; border: 1px solid blue;">
            Résultat JS apparaîtra ici...
        </div>
    </div>

    <!-- Chargement du JS -->
    <script src="assets/js/admin.js"></script>
    
    <script>
        // Test inline
        console.log('🔧 Script inline du test fonctionne');
        
        function testFunction() {
            document.getElementById('js-result').innerHTML = '✅ Fonction inline fonctionne !';
            
            // Test si la fonction du fichier externe existe
            if (typeof showTab === 'function') {
                document.getElementById('js-result').innerHTML += '<br>✅ showTab() du fichier externe existe !';
            } else {
                document.getElementById('js-result').innerHTML += '<br>❌ showTab() du fichier externe manquante !';
            }
        }
        
        // Vérifier si le CSS s'applique
        document.addEventListener('DOMContentLoaded', function() {
            var bodyStyle = getComputedStyle(document.body);
            var bgColor = bodyStyle.backgroundColor;
            
            console.log('Couleur de fond du body:', bgColor);
            
            if (bgColor.includes('255, 0, 0') || bgColor.includes('red')) {
                console.log('✅ CSS externe (rouge) détecté !');
                document.getElementById('js-result').innerHTML = '✅ CSS externe fonctionne (rouge détecté)';
            } else {
                console.log('❌ CSS externe non appliqué. Couleur actuelle:', bgColor);
                document.getElementById('js-result').innerHTML = '❌ CSS externe ne fonctionne pas. Couleur: ' + bgColor;
            }
        });
    </script>
</body>
</html>
