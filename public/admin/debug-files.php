<?php
// debug-files.php - À placer dans /public/admin/
echo "<h1>🔍 Vérification des fichiers</h1>";

$files_to_check = [
    'assets/css/admin-style.css',
    'assets/js/admin.js',
    'assets/',
    'assets/css/',
    'assets/js/'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    $readable = is_readable($full_path);
    
    echo "<p>";
    echo $exists ? "✅" : "❌";
    echo " <strong>$file</strong><br>";
    echo "   Chemin complet: $full_path<br>";
    echo "   Existe: " . ($exists ? "OUI" : "NON") . "<br>";
    echo "   Lisible: " . ($readable ? "OUI" : "NON") . "<br>";
    
    if ($exists) {
        if (is_dir($full_path)) {
            echo "   Type: Dossier<br>";
            $contents = scandir($full_path);
            echo "   Contenu: " . implode(', ', array_diff($contents, ['.', '..'])) . "<br>";
        } else {
            echo "   Type: Fichier<br>";
            echo "   Taille: " . filesize($full_path) . " octets<br>";
        }
    }
    echo "</p><hr>";
}

echo "<h2>🌐 Test des URLs</h2>";
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
echo "<p>URL de base: $base_url</p>";

$test_urls = [
    'assets/css/admin-style.css',
    'assets/js/admin.js'
];

foreach ($test_urls as $url) {
    $full_url = $base_url . '/' . $url;
    echo "<p><a href='$full_url' target='_blank'>🔗 $url</a></p>";
}
?>

<script>
// Test des chemins JavaScript
var urls = [
    'assets/css/admin-style.css',
    'assets/js/admin.js'
];

urls.forEach(function(url) {
    fetch(url)
        .then(function(response) {
            console.log(url + ' - Status:', response.status);
            if (response.status === 200) {
                console.log('✅ ' + url + ' accessible');
            } else {
                console.log('❌ ' + url + ' erreur ' + response.status);
            }
        })
        .catch(function(error) {
            console.log('❌ ' + url + ' erreur:', error);
        });
});
</script>
