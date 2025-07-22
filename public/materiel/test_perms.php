<?php
/**
 * Test permissions écriture - /public/materiel/test_perms.php
 */

echo "<!DOCTYPE html><html><head><title>Test Permissions</title></head><body>";
echo "<h1>🔐 Test permissions d'écriture</h1>";

$paths_to_test = [
    '/public/materiel/' => 'Dossier principal',
    '/public/materiel/classes/' => 'Dossier classes', 
    '/public/materiel/assets/css/' => 'Dossier CSS',
    '/public/materiel/reports/' => 'Dossier reports'
];

foreach ($paths_to_test as $path => $desc) {
    $full_path = dirname(dirname(__DIR__)) . $path;
    
    echo "<h3>$desc ($path)</h3>";
    
    if (!is_dir($full_path)) {
        echo "<p style='color:red'>❌ Dossier inexistant</p>";
        continue;
    }
    
    $perms = substr(sprintf('%o', fileperms($full_path)), -4);
    $readable = is_readable($full_path);
    $writable = is_writable($full_path);
    
    echo "<p>Permissions: <strong>$perms</strong></p>";
    echo "<p>Lecture: " . ($readable ? "✅ OUI" : "❌ NON") . "</p>";
    echo "<p>Écriture: " . ($writable ? "✅ OUI" : "❌ NON") . "</p>";
    
    // Test création fichier
    if ($writable) {
        $test_file = $full_path . 'test_write.tmp';
        if (file_put_contents($test_file, 'test')) {
            echo "<p style='color:green'>✅ Test écriture réussi</p>";
            unlink($test_file);
        } else {
            echo "<p style='color:red'>❌ Test écriture échoué</p>";
        }
    }
    
    echo "<hr>";
}

// Alternative si pas de droits d'écriture
echo "<h2>💡 Solutions alternatives</h2>";
echo "<h3>Si pas de droits d'écriture :</h3>";
echo "<ol>";
echo "<li><strong>SSH/FTP :</strong> Modifier les fichiers directement</li>";
echo "<li><strong>Copier-coller :</strong> Éditer manuellement les fichiers</li>";
echo "<li><strong>Panel d'hébergement :</strong> Gestionnaire de fichiers</li>";
echo "</ol>";

echo "<h3>Corrections manuelles urgentes :</h3>";
echo "<p><strong>1. Dans MaterielManager.php :</strong></p>";
echo "<pre>// Remplacer cette ligne :
public function __construct()

// Par :
public function __construct(\$database = null)</pre>";

echo "<p><strong>2. Dans reports/index.php :</strong></p>";
echo "<pre>// Ajouter à la fin :
&lt;?php include ROOT_PATH . '/templates/footer.php'; ?&gt;</pre>";

echo "</body></html>";
?>
