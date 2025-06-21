<?php
// Debug erreur 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP OK<br>";

try {
    require_once __DIR__ . '/../../config/config.php';
    echo "2. Config OK<br>";
} catch (Exception $e) {
    die("Erreur config: " . $e->getMessage());
}

if ($_POST) {
    echo "3. POST reçu<br>";
    
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? 'oui' : 'non',
        'service_livraison' => trim($_POST['service_livraison'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];
    
    echo "4. Params OK<br>";
    
    $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
    
    if (file_exists($transport_file)) {
        echo "5. Fichier Transport trouvé<br>";
        
        try {
            require_once $transport_file;
            echo "6. Transport chargé<br>";
            
            $transport = new Transport($db);
            echo "7. Instance créée<br>";
            
            // Test calcul
            try {
                $results = $transport->calculateAll($params);
                echo "8. Calcul array OK<br>";
                echo "<pre>";
                print_r($results);
                echo "</pre>";
            } catch (Exception $e) {
                echo "8. Erreur array: " . $e->getMessage() . "<br>";
                
                try {
                    $results = $transport->calculateAll(
                        $params['type'],
                        $params['adr'], 
                        $params['poids'],
                        $params['service_livraison'],
                        $params['departement'],
                        $params['palettes'],
                        $params['enlevement']
                    );
                    echo "9. Calcul params OK<br>";
                    echo "<pre>";
                    print_r($results);
                    echo "</pre>";
                } catch (Exception $e2) {
                    echo "9. Erreur params: " . $e2->getMessage() . "<br>";
                    echo "Stack: " . $e2->getTraceAsString() . "<br>";
                }
            }
            
        } catch (Exception $e) {
            echo "Erreur chargement: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "5. Fichier manquant: $transport_file<br>";
    }
}
?>

<form method="POST">
    <input type="text" name="departement" value="67" placeholder="Dept">
    <input type="number" name="poids" value="25" placeholder="Poids">
    <select name="type">
        <option value="colis">Colis</option>
        <option value="palette">Palette</option>
    </select>
    <button type="submit">Test</button>
</form>
