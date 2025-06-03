<?php
// maintenance-adr.php - Script de maintenance des données ADR  
// À exécuter périodiquement pour nettoyer et optimiser

require_once __DIR__ . '/config.php';

// Pas de sécurité pour simplifier (supprimez après usage)
echo "<h1>🔧 Maintenance ADR - Guldagil</h1>";
echo "<p>Exécution le " . date('d/m/Y à H:i:s') . "</p>";

$action = $_GET['action'] ?? 'menu';

switch ($action) {
    case 'clean':
        cleanADRData($db);
        break;
    case 'optimize':
        optimizeADRTables($db);
        break;
    case 'stats':
        generateADRStats($db);
        break;
    case 'export':
        exportADRData($db);
        break;
    case 'backup':
        backupADRData($db);
        break;
    default:
        showMaintenanceMenu();
}

/**
 * Menu principal de maintenance
 */
function showMaintenanceMenu() {
    echo "<h2>🛠️ Actions de maintenance disponibles</h2>";
    echo "<ul>";
    echo "<li><a href='?action=stats'>📊 Générer statistiques complètes</a></li>";
    echo "<li><a href='?action=clean'>🧹 Nettoyer les données</a></li>";
    echo "<li><a href='?action=optimize'>⚡ Optimiser les tables</a></li>";
    echo "<li><a href='?action=export'>📤 Exporter données ADR</a></li>";
    echo "<li><a href='?action=backup'>💾 Sauvegarder tables ADR</a></li>";
    echo "</ul>";
}

/**
 * Nettoyage des données ADR
 */
function cleanADRData($db) {
    echo "<h2>🧹 Nettoyage des données ADR</h2>";
    
    $cleanupActions = [];
    
    try {
        // 1. Supprimer les doublons par code produit (garder le plus récent)
        $sql = "DELETE p1 FROM gul_adr_products p1
                INNER JOIN gul_adr_products p2 
                WHERE p1.id < p2.id AND p1.code_produit = p2.code_produit";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $doublons = $stmt->rowCount();
        $cleanupActions[] = "Supprimé $doublons doublons";
        
        // 2. Normaliser les catégories de transport
        $normalisations = [
            "UPDATE gul_adr_products SET categorie_transport = '0' WHERE categorie_transport IN ('', 'NULL', 'null')",
            "UPDATE gul_adr_products SET danger_environnement = '' WHERE danger_environnement NOT IN ('OUI', 'NON')",
            "UPDATE gul_adr_products SET groupe_emballage = UPPER(groupe_emballage) WHERE groupe_emballage IS NOT NULL",
            "UPDATE gul_adr_products SET numero_un = NULL WHERE numero_un IN ('', '#N/A', '#REF!')",
        ];
        
        foreach ($normalisations as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $cleanupActions[] = "Normalisé $affected enregistrements";
            }
        }
        
        // 3. Mettre à jour les timestamps
        $sql = "UPDATE gul_adr_products SET date_modification = NOW() WHERE date_modification IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $updated = $stmt->rowCount();
        if ($updated > 0) {
            $cleanupActions[] = "Mis à jour $updated timestamps";
        }
        
        // 4. Marquer les produits sans nom comme inactifs
        $sql = "UPDATE gul_adr_products SET actif = 0 WHERE nom_produit IS NULL OR nom_produit = ''";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $deactivated = $stmt->rowCount();
        if ($deactivated > 0) {
            $cleanupActions[] = "Désactivé $deactivated produits sans nom";
        }
        
        echo "<h3>✅ Nettoyage terminé</h3>";
        echo "<ul>";
        foreach ($cleanupActions as $action) {
            echo "<li>$action</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
}

/**
 * Optimisation des tables
 */
function optimizeADRTables($db) {
    echo "<h2>⚡ Optimisation des tables ADR</h2>";
    
    try {
        // Optimiser les tables
        $tables = ['gul_adr_products', 'gul_adr_quotas'];
        
        foreach ($tables as $table) {
            $db->exec("OPTIMIZE TABLE `$table`");
            echo "<p>✅ Table `$table` optimisée</p>";
        }
        
        // Recalculer les statistiques
        foreach ($tables as $table) {
            $db->exec("ANALYZE TABLE `$table`");
            echo "<p>📊 Statistiques de `$table` recalculées</p>";
        }
        
        // Vérifier l'intégrité
        foreach ($tables as $table) {
            $stmt = $db->query("CHECK TABLE `$table`");
            $result = $stmt->fetch();
            echo "<p>🔍 Vérification `$table`: " . $result['Msg_text'] . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
}

/**
 * Génération de statistiques complètes
 */
function generateADRStats($db) {
    echo "<h2>📊 Statistiques complètes ADR</h2>";
    
    try {
        // Statistiques générales
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 ELSE 0 END) as adr_count,
                SUM(CASE WHEN corde_article_ferme = 'x' THEN 1 ELSE 0 END) as fermes_count,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as actifs_count,
                SUM(CASE WHEN danger_environnement = 'OUI' THEN 1 ELSE 0 END) as danger_env_count
            FROM gul_adr_products
        ");
        $stats = $stmt->fetch();
        
        echo "<h3>📈 Vue d'ensemble</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Métrique</th><th>Valeur</th><th>Pourcentage</th></tr>";
        echo "<tr><td>Total produits</td><td>{$stats['total']}</td><td>100%</td></tr>";
        echo "<tr><td>Produits ADR</td><td>{$stats['adr_count']}</td><td>" . round($stats['adr_count'] * 100 / $stats['total'], 1) . "%</td></tr>";
        echo "<tr><td>Articles fermés</td><td>{$stats['fermes_count']}</td><td>" . round($stats['fermes_count'] * 100 / $stats['total'], 1) . "%</td></tr>";
        echo "<tr><td>Produits actifs</td><td>{$stats['actifs_count']}</td><td>" . round($stats['actifs_count'] * 100 / $stats['total'], 1) . "%</td></tr>";
        echo "<tr><td>Danger environnemental</td><td>{$stats['danger_env_count']}</td><td>" . round($stats['danger_env_count'] * 100 / $stats['total'], 1) . "%</td></tr>";
        echo "</table>";
        
        // Répartition par catégorie
        echo "<h3>🏷️ Répartition par catégorie de transport</h3>";
        $stmt = $db->query("
            SELECT 
                COALESCE(categorie_transport, 'Non défini') as categorie,
                COUNT(*) as count,
                q.description
            FROM gul_adr_products p
            LEFT JOIN gul_adr_quotas q ON p.categorie_transport = q.categorie_transport
            WHERE p.numero_un IS NOT NULL
            GROUP BY p.categorie_transport, q.description
            ORDER BY p.categorie_transport
        ");
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Catégorie</th><th>Description</th><th>Nombre</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['categorie']}</td><td>{$row['description']}</td><td>{$row['count']}</td></tr>";
        }
        echo "</table>";
        
        // Top 10 des UN
        echo "<h3>🔥 Top 10 des numéros UN</h3>";
        $stmt = $db->query("
            SELECT 
                numero_un,
                nom_description_un,
                COUNT(*) as occurrences
            FROM gul_adr_products 
            WHERE numero_un IS NOT NULL 
            GROUP BY numero_un, nom_description_un 
            ORDER BY occurrences DESC 
            LIMIT 10
        ");
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Numéro UN</th><th>Description</th><th>Occurrences</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>UN{$row['numero_un']}</td><td>" . substr($row['nom_description_un'], 0, 50) . "...</td><td>{$row['occurrences']}</td></tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
}

/**
 * Export des données ADR
 */
function exportADRData($db) {
    $format = $_GET['format'] ?? 'csv';
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="guldagil_adr_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8
        
        // En-têtes
        fputcsv($output, [
            'code_produit', 'nom_produit', 'numero_un', 'nom_description_un',
            'categorie_transport', 'groupe_emballage', 'type_contenant',
            'poids_contenant', 'danger_environnement', 'actif', 'date_creation'
        ], ';');
        
        // Données
        $stmt = $db->query("SELECT * FROM gul_adr_products ORDER BY code_produit");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['code_produit'],
                $row['nom_produit'],
                $row['numero_un'],
                $row['nom_description_un'],
                $row['categorie_transport'],
                $row['groupe_emballage'],
                $row['type_contenant'],
                $row['poids_contenant'],
                $row['danger_environnement'],
                $row['actif'] ? 'Oui' : 'Non',
                $row['date_creation']
            ], ';');
        }
        
        fclose($output);
    } else {
        echo "<h2>📤 Export des données ADR</h2>";
        echo "<p><a href='?action=export&format=csv'>💾 Télécharger CSV complet</a></p>";
    }
}

/**
 * Sauvegarde des tables ADR
 */
function backupADRData($db) {
    echo "<h2>💾 Sauvegarde des tables ADR</h2>";
    
    try {
        $backupFile = "backup_adr_" . date('Y-m-d_H-i-s') . ".sql";
        $backupPath = __DIR__ . "/backups/" . $backupFile;
        
        // Créer le dossier backups s'il n'existe pas
        if (!is_dir(__DIR__ . "/backups/")) {
            mkdir(__DIR__ . "/backups/", 0755, true);
        }
        
        $backup = "-- Sauvegarde ADR Guldagil - " . date('Y-m-d H:i:s') . "\n\n";
        
        $tables = ['gul_adr_products', 'gul_adr_quotas'];
        
        foreach ($tables as $table) {
            // Structure
            $stmt = $db->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            $backup .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup .= $createTable['Create Table'] . ";\n\n";
            
            // Données
            $stmt = $db->query("SELECT * FROM `$table`");
            $backup .= "INSERT INTO `$table` VALUES\n";
            $values = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $escapedRow = array_map(function($value) use ($db) {
                    return $value === null ? 'NULL' : $db->quote($value);
                }, $row);
                $values[] = "(" . implode(',', $escapedRow) . ")";
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
        
        file_put_contents($backupPath, $backup);
        
        echo "<p>✅ Sauvegarde créée : <code>$backupFile</code></p>";
        echo "<p>📁 Taille : " . round(filesize($backupPath) / 1024, 2) . " KB</p>";
        echo "<p><a href='backups/$backupFile' download>📥 Télécharger la sauvegarde</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
}
?>
