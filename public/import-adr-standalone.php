<?php
// import-adr-standalone.php - Script d'import standalone pour produits ADR
// SANS MOT DE PASSE - Supprimez après utilisation !

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// Utilisation de votre config.php existant
$configPaths = [
    __DIR__ . '/../config.php',        // Si script dans /public/
    __DIR__ . '/config.php',           // Si script à la racine
    dirname(__DIR__) . '/config.php'   // Dossier parent
];

$configLoaded = false;
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        try {
            require_once $configPath;
            $configLoaded = true;
            break;
        } catch (Exception $e) {
            echo "<div style='background: #fff3cd; padding: 10px; margin: 10px; border-radius: 4px;'>⚠️ Erreur config : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (!$configLoaded) {
    echo "<div style='background: #f8d7da; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3>❌ Configuration non trouvée</h3>";
    echo "<p>Le fichier config.php n'a pas pu être chargé. Chemins testés :</p>";
    echo "<ul>";
    foreach ($configPaths as $path) {
        $exists = file_exists($path) ? '✅' : '❌';
        echo "<li>$exists " . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
    exit;
}

// Vérifier que la connexion BDD fonctionne
if (!isset($db) || !($db instanceof PDO)) {
    echo "<div style='background: #f8d7da; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3>❌ Problème de connexion base de données</h3>";
    echo "<p>Vérifiez votre fichier .env</p>";
    exit;
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Import ADR - Guldagil</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007acc; background: #f8f9fa; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007acc; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .form-group { margin: 15px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type=file] { width: 100%; padding: 10px; border: 2px dashed #ddd; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; text-align: center; border-radius: 8px; }
        .stat-card.info { background: #d1ecf1; }
        .stat-card.success { background: #d4edda; }
        .stat-card.warning { background: #fff3cd; }
        .stat-card.danger { background: #f8d7da; }
        .stat-value { font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 12px; color: #666; }
        .preview-table { max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>";

echo "<div class='container'>
    <h1>🚛 Import Produits ADR - Guldagil</h1>
    <p><strong>Script standalone</strong> - Version : " . date('Y-m-d H:i:s') . "</p>
    <div style='background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;'>
        ⚠️ <strong>Sécurité :</strong> Supprimez ce script après utilisation !
    </div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 10px; border-radius: 4px;'>✅ Configuration chargée avec succès</div>";

// Étape 1 : Création des tables si nécessaire
if (isset($_POST['create_tables'])) {
    createADRTables($db);
}

// Étape 2 : Traitement de l'upload
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $mode = $_POST['mode'] ?? 'preview';
    
    if ($mode === 'preview') {
        previewCSV($_FILES['csv_file'], $db);
    } elseif ($mode === 'import') {
        importCSV($_FILES['csv_file'], $db);
    }
} else {
    showUploadForm($db);
}

echo "</div></body></html>";

/**
 * Création des tables ADR
 */
function createADRTables($db) {
    echo "<div class='step'>";
    echo "<h3>🗄️ Création des tables ADR</h3>";
    
    try {
        // Vérifier si la table existe déjà
        $stmt = $db->query("SHOW TABLES LIKE 'gul_adr_products'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='warning'>⚠️ Table 'gul_adr_products' existe déjà - ignoré</div>";
        } else {
            // Créer la table principale
            $sql = "CREATE TABLE `gul_adr_products` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code_produit` varchar(50) NOT NULL COMMENT 'Code produit Guldagil',
                `corde_article_ferme` enum('x','') DEFAULT '' COMMENT 'Article fermé (x = oui)',
                `nom_produit` varchar(255) DEFAULT NULL COMMENT 'Nom commercial du produit',
                `poids_contenant` varchar(50) DEFAULT NULL COMMENT 'Poids/contenant (ex: 20 Kg)',
                `type_contenant` varchar(50) DEFAULT NULL COMMENT 'Type de contenant (Bidon, IBC, etc.)',
                `numero_un` varchar(10) DEFAULT NULL COMMENT 'Numéro UN (ex: 3412)',
                `nom_description_un` text DEFAULT NULL COMMENT 'Nom et description UN officielle',
                `nom_technique` varchar(255) DEFAULT NULL COMMENT 'Nom technique du produit',
                `groupe_emballage` varchar(10) DEFAULT NULL COMMENT 'Groupe emballage (I, II, III)',
                `numero_etiquette` varchar(20) DEFAULT NULL COMMENT 'Numéro étiquette danger',
                `categorie_transport` varchar(10) DEFAULT NULL COMMENT 'Catégorie transport (0, 1, 2, 3, 4)',
                `code_tunnel` varchar(10) DEFAULT NULL COMMENT 'Code restriction tunnel',
                `danger_environnement` enum('OUI','NON','') DEFAULT '' COMMENT 'Dangereux pour environnement',
                `colonne_3` varchar(10) DEFAULT NULL COMMENT 'Colonne mystère à identifier',
                `actif` tinyint(1) DEFAULT 1 COMMENT 'Produit actif dans le catalogue',
                `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
                `date_modification` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `cree_par` varchar(50) DEFAULT 'import_standalone',
                PRIMARY KEY (`id`),
                UNIQUE KEY `code_produit` (`code_produit`),
                KEY `numero_un` (`numero_un`),
                KEY `categorie_transport` (`categorie_transport`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Produits ADR catalogue Guldagil'";
            
            $db->exec($sql);
            echo "<div class='success'>✅ Table 'gul_adr_products' créée avec succès</div>";
        }
        
        echo "<p><strong>✅ Base de données prête pour l'import ADR</strong></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}

/**
 * Affiche le formulaire d'upload
 */
function showUploadForm($db) {
    // Vérifier si les tables existent
    $tablesExist = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'gul_adr_products'");
        $tablesExist = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // Ignore
    }
    
    if (!$tablesExist) {
        echo "<div class='step error'>
            <h3>⚠️ Tables manquantes</h3>
            <p>Les tables ADR n'existent pas encore dans la base de données.</p>
            <form method='POST'>
                <button type='submit' name='create_tables' class='btn btn-warning'>🗄️ Créer les tables ADR</button>
            </form>
        </div>";
        return;
    }
    
    // Statistiques existantes
    try {
        $stmt = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 ELSE 0 END) as adr_count,
            SUM(CASE WHEN corde_article_ferme = 'x' THEN 1 ELSE 0 END) as fermes_count
            FROM gul_adr_products WHERE actif = 1");
        $stats = $stmt->fetch();
        
        if ($stats['total'] > 0) {
            echo "<div class='step success'>
                <h3>📊 Produits existants</h3>
                <div class='stats'>
                    <div class='stat-card info'>
                        <div class='stat-value'>" . $stats['total'] . "</div>
                        <div class='stat-label'>Total produits</div>
                    </div>
                    <div class='stat-card warning'>
                        <div class='stat-value'>" . $stats['adr_count'] . "</div>
                        <div class='stat-label'>Produits ADR</div>
                    </div>
                    <div class='stat-card danger'>
                        <div class='stat-value'>" . $stats['fermes_count'] . "</div>
                        <div class='stat-label'>Articles fermés</div>
                    </div>
                </div>
            </div>";
        }
    } catch (Exception $e) {
        // Ignore les erreurs de stats
    }
    
    echo "<div class='step'>
        <h3>📤 Upload du fichier CSV</h3>
        <form method='POST' enctype='multipart/form-data'>
            
            <div class='form-group'>
                <label>📄 Sélectionnez votre fichier CSV :</label>
                <input type='file' name='csv_file' accept='.csv,.txt' required>
                <small>Format attendu : séparateur ';' - encodage UTF-8</small>
            </div>
            
            <div class='form-group'>
                <button type='submit' name='mode' value='preview' class='btn btn-primary'>👁️ Aperçu (recommandé)</button>
                <button type='submit' name='mode' value='import' class='btn btn-success' onclick='return confirm(\"Êtes-vous sûr de vouloir importer directement ?\")'>📥 Import direct</button>
            </div>
        </form>
        
        <div class='warning'>
            <h4>⚠️ Format attendu :</h4>
            <p><strong>Colonnes requises :</strong> Code produit, Nom Produit, UN, NOM ET DESCRIPTION, etc.</p>
            <p><strong>Séparateur :</strong> Point-virgule (;)</p>
            <p><strong>Encodage :</strong> UTF-8 avec ou sans BOM</p>
        </div>
    </div>";
}

/**
 * Aperçu du CSV
 */
function previewCSV($file, $db) {
    echo "<div class='step'>
        <h3>👁️ Aperçu du fichier : " . htmlspecialchars($file['name']) . "</h3>";
    
    try {
        $data = parseCSV($file);
        
        if (!$data['success']) {
            echo "<div class='error'>❌ " . htmlspecialchars($data['error']) . "</div>";
            return;
        }
        
        $rows = $data['rows'];
        $headers = $data['headers'];
        
        // Statistiques d'aperçu
        $stats = [
            'total' => count($rows),
            'adr' => 0,
            'non_adr' => 0,
            'conflicts' => 0
        ];
        
        $preview = array_slice($rows, 0, 10); // 10 premières lignes
        
        foreach ($rows as $row) {
            $numeroUN = $row['UN'] ?? '';
            if (!empty($numeroUN) && $numeroUN !== '#N/A') {
                $stats['adr']++;
            } else {
                $stats['non_adr']++;
            }
            
            // Vérifier conflits
            $codeProduit = $row['Code produit'] ?? '';
            if (!empty($codeProduit)) {
                $stmt = $db->prepare("SELECT id FROM gul_adr_products WHERE code_produit = ?");
                $stmt->execute([$codeProduit]);
                if ($stmt->fetch()) {
                    $stats['conflicts']++;
                }
            }
        }
        
        echo "<div class='stats'>
            <div class='stat-card info'>
                <div class='stat-value'>" . $stats['total'] . "</div>
                <div class='stat-label'>Lignes total</div>
            </div>
            <div class='stat-card success'>
                <div class='stat-value'>" . $stats['adr'] . "</div>
                <div class='stat-label'>Produits ADR</div>
            </div>
            <div class='stat-card warning'>
                <div class='stat-value'>" . $stats['non_adr'] . "</div>
                <div class='stat-label'>Non-ADR</div>
            </div>
            <div class='stat-card danger'>
                <div class='stat-value'>" . $stats['conflicts'] . "</div>
                <div class='stat-label'>Conflits détectés</div>
            </div>
        </div>";
        
        // En-têtes détectés
        echo "<h4>📋 En-têtes détectés :</h4>";
        echo "<p><code>" . implode(' | ', $headers) . "</code></p>";
        
        // Aperçu des données
        echo "<h4>👁️ Aperçu (10 premières lignes) :</h4>";
        echo "<div class='preview-table'>";
        echo "<table>";
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($preview as $row) {
            echo "<tr>";
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                if ($header === 'UN' && !empty($value) && $value !== '#N/A') {
                    $value = "<strong style='color: red;'>$value</strong>"; // Highlighter les UN
                }
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Actions  
        echo "<p><strong>🎯 Pour importer ces données :</strong></p>";
        echo "<p>1. Vérifiez l'aperçu ci-dessus</p>";
        echo "<p>2. Re-uploadez le même fichier en choisissant 'Import direct'</p>";
        echo "<p>3. Ou recommencez le processus</p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}

/**
 * Import du CSV
 */
function importCSV($file, $db) {
    echo "<div class='step'>
        <h3>📥 Import en cours...</h3>";
    
    try {
        $data = parseCSV($file);
        
        if (!$data['success']) {
            echo "<div class='error'>❌ " . htmlspecialchars($data['error']) . "</div>";
            return;
        }
        
        $rows = $data['rows'];
        
        $results = [
            'total' => count($rows),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        $db->beginTransaction();
        
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 1;
            $result = importRow($db, $row, $lineNumber);
            
            if ($result['success']) {
                if ($result['action'] === 'insert') {
                    $results['imported']++;
                } else {
                    $results['updated']++;
                }
            } else {
                $results['skipped']++;
                $results['errors'][] = "Ligne $lineNumber: " . $result['error'];
            }
            
            // Afficher progression
            if ($lineNumber % 50 === 0) {
                echo "<p>📊 Traitement ligne $lineNumber / " . $results['total'] . "</p>";
                flush();
            }
        }
        
        $db->commit();
        
        // Afficher résultats
        echo "<div class='stats'>
            <div class='stat-card info'>
                <div class='stat-value'>" . $results['total'] . "</div>
                <div class='stat-label'>Lignes traitées</div>
            </div>
            <div class='stat-card success'>
                <div class='stat-value'>" . $results['imported'] . "</div>
                <div class='stat-label'>Nouveaux produits</div>
            </div>
            <div class='stat-card warning'>
                <div class='stat-value'>" . $results['updated'] . "</div>
                <div class='stat-label'>Mis à jour</div>
            </div>
            <div class='stat-card danger'>
                <div class='stat-value'>" . $results['skipped'] . "</div>
                <div class='stat-label'>Ignorés</div>
            </div>
        </div>";
        
        if (!empty($results['errors'])) {
            echo "<h4>❌ Erreurs détectées :</h4>";
            echo "<ul>";
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            if (count($results['errors']) > 10) {
                echo "<li><em>... et " . (count($results['errors']) - 10) . " autres erreurs</em></li>";
            }
            echo "</ul>";
        }
        
        echo "<div class='success'>✅ Import terminé avec succès !</div>";
        
    } catch (Exception $e) {
        $db->rollback();
        echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}

/**
 * Parse le CSV
 */
function parseCSV($file) {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Impossible d\'ouvrir le fichier'];
    }
    
    // Gérer l'encodage
    $content = file_get_contents($file['tmp_name']);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        file_put_contents($file['tmp_name'], $content);
    }
    
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        file_put_contents($file['tmp_name'], $content);
    }
    
    rewind($handle);
    
    $rows = [];
    $headers = null;
    
    while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        if (empty(array_filter($row))) continue;
        
        if ($headers === null) {
            $headers = array_map('trim', $row);
            continue;
        }
        
        $rowData = [];
        foreach ($headers as $index => $header) {
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            if (in_array($value, ['#N/A', '#REF!', ''])) {
                $value = null;
            }
            $rowData[$header] = $value;
        }
        
        $rows[] = $rowData;
    }
    
    fclose($handle);
    
    return ['success' => true, 'rows' => $rows, 'headers' => $headers];
}

/**
 * Import une ligne
 */
function importRow($db, $row, $lineNumber) {
    try {
        $codeProduit = $row['Code produit'] ?? '';
        
        if (empty($codeProduit)) {
            return ['success' => false, 'error' => 'Code produit manquant'];
        }
        
        // Vérifier si existe
        $stmt = $db->prepare("SELECT id FROM gul_adr_products WHERE code_produit = ?");
        $stmt->execute([$codeProduit]);
        $existing = $stmt->fetch();
        
        // Préparer données
        $data = [
            'code_produit' => $codeProduit,
            'corde_article_ferme' => ($row['Corde article fermé'] === 'x') ? 'x' : '',
            'nom_produit' => $row['Nom Produit'] ?: null,
            'poids_contenant' => $row['POIDS / CONT'] ?: null,
            'type_contenant' => $row['CONTENANT'] ?: null,
            'numero_un' => (!empty($row['UN']) && $row['UN'] !== '#N/A') ? $row['UN'] : null,
            'nom_description_un' => $row['NOM ET DESCRIPTION'] ?: null,
            'nom_technique' => $row['NOM TECHNIQUE'] ?: null,
            'groupe_emballage' => $row['GR EMBAL'] ?: null,
            'numero_etiquette' => $row['N° D\'ETIQT'] ?: null,
            'categorie_transport' => $row['CAT TRANS'] ?: null,
            'code_tunnel' => $row['CODE TUNNEL'] ?: null,
            'danger_environnement' => in_array($row['DANGER ENV'], ['OUI', 'NON']) ? $row['DANGER ENV'] : '',
            'colonne_3' => $row['3'] ?: null
        ];
        
        if ($existing) {
            // Update
            $setParts = [];
            $params = [];
            foreach ($data as $field => $value) {
                if ($field !== 'code_produit') {
                    $setParts[] = "`$field` = ?";
                    $params[] = $value;
                }
            }
            $params[] = $codeProduit;
            
            $sql = "UPDATE gul_adr_products SET " . implode(', ', $setParts) . " WHERE code_produit = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'action' => 'update'];
        } else {
            // Insert
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO gul_adr_products (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return ['success' => true, 'action' => 'insert'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
