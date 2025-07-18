<?php
/**
 * Titre: Maintenance du système Analytics
 * Chemin: /public/admin/analytics_maintenance.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));
require_once ROOT_PATH . '/config/config.php';

// Vérification authentification admin
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'dev'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit('Accès non autorisé');
}

// Variables de page
$page_title = 'Maintenance Analytics - Portail Admin';
$current_module = 'admin';

// Dossier analytics
$analytics_dir = ROOT_PATH . '/storage/analytics/';
$result_message = '';
$result_status = '';

// Traitement des actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'cleanup':
            // Nettoyage des logs anciens
            $before_date = isset($_GET['before']) ? $_GET['before'] : date('Y-m-d', strtotime('-90 days'));
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $before_date)) {
                $before_date = date('Y-m-d', strtotime('-90 days'));
            }
            
            $files_deleted = 0;
            $space_freed = 0;
            
            if (file_exists($analytics_dir)) {
                $files = glob($analytics_dir . 'visits_*.log');
                
                foreach ($files as $file) {
                    // Extraire la date du nom de fichier
                    if (preg_match('/visits_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                        $file_date = $matches[1];
                        
                        // Supprimer si antérieur à la date limite
                        if (strtotime($file_date) < strtotime($before_date)) {
                            $space_freed += filesize($file);
                            if (unlink($file)) {
                                $files_deleted++;
                            }
                        }
                    }
                }
            }
            
            if ($files_deleted > 0) {
                $result_message = sprintf(
                    'Nettoyage terminé : %d fichiers supprimés (%.2f MB libérés)',
                    $files_deleted,
                    $space_freed / 1024 / 1024
                );
                $result_status = 'success';
            } else {
                $result_message = 'Aucun fichier à supprimer pour la période spécifiée.';
                $result_status = 'info';
            }
            break;
            
        case 'optimize':
            // Optimisation du stockage
            $files_optimized = 0;
            $space_saved = 0;
            
            if (file_exists($analytics_dir)) {
                $files = glob($analytics_dir . 'visits_*.log');
                
                foreach ($files as $file) {
                    // Lire le fichier
                    $original_size = filesize($file);
                    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    
                    // Optimiser (enlever doublons, entrées invalides, etc)
                    $optimized_lines = [];
                    $entries_hash = [];
                    
                    foreach ($lines as $line) {
                        $entry = json_decode($line, true);
                        
                        // Vérifier validité entrée
                        if (!$entry || !isset($entry['timestamp']) || !isset($entry['page'])) {
                            continue;
                        }
                        
                        // Créer hash unique pour éviter doublons exacts
                        $entry_hash = md5($entry['ip_hash'] . $entry['page'] . $entry['timestamp']);
                        
                        // Si ce hash n'a pas été vu avant, le conserver
                        if (!isset($entries_hash[$entry_hash])) {
                            $entries_hash[$entry_hash] = true;
                            $optimized_lines[] = $line;
                        }
                    }
                    
                    // Si optimisation a réduit le nombre de lignes, réécrire le fichier
                    if (count($optimized_lines) < count($lines)) {
                        file_put_contents($file, implode("\n", $optimized_lines));
                        $new_size = filesize($file);
                        $space_saved += ($original_size - $new_size);
                        $files_optimized++;
                    }
                }
            }
            
            if ($files_optimized > 0) {
                $result_message = sprintf(
                    'Optimisation terminée : %d fichiers optimisés (%.2f MB économisés)',
                    $files_optimized,
                    $space_saved / 1024 / 1024
                );
                $result_status = 'success';
            } else {
                $result_message = 'Aucune optimisation nécessaire. Tous les fichiers sont déjà optimaux.';
                $result_status = 'info';
            }
            break;
            
        case 'rebuild_index':
            // Reconstruction de l'index analytics (pour accélérer les requêtes)
            $index_file = $analytics_dir . 'index.json';
            $index_data = [
                'last_updated' => date('Y-m-d H:i:s'),
                'total_entries' => 0,
                'modules' => [],
                'pages' => [],
                'files' => []
            ];
            
            if (file_exists($analytics_dir)) {
                $files = glob($analytics_dir . 'visits_*.log');
                
                foreach ($files as $file) {
                    // Extraire la date du nom de fichier
                    if (preg_match('/visits_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                        $file_date = $matches[1];
                        $file_size = filesize($file);
                        $lines_count = 0;
                        
                        // Lire et analyser le fichier
                        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $lines_count = count($lines);
                        $index_data['total_entries'] += $lines_count;
                        
                        // Indexer le contenu
                        $modules_in_file = [];
                        $pages_in_file = [];
                        
                        foreach ($lines as $line) {
                            $entry = json_decode($line, true);
                            if (!$entry) continue;
                            
                            // Compter les modules
                            if (isset($entry['module'])) {
                                $module = $entry['module'];
                                if (!isset($modules_in_file[$module])) {
                                    $modules_in_file[$module] = 0;
                                }
                                $modules_in_file[$module]++;
                                
                                // Mettre à jour l'index global
                                if (!isset($index_data['modules'][$module])) {
                                    $index_data['modules'][$module] = 0;
                                }
                                $index_data['modules'][$module]++;
                            }
                            
                            // Compter les pages
                            if (isset($entry['page'])) {
                                $page = $entry['page'];
                                if (!isset($pages_in_file[$page])) {
                                    $pages_in_file[$page] = 0;
                                }
                                $pages_in_file[$page]++;
                                
                                // Mettre à jour l'index global
                                if (!isset($index_data['pages'][$page])) {
                                    $index_data['pages'][$page] = 0;
                                }
                                $index_data['pages'][$page]++;
                            }
                        }
                        
                        // Ajouter information sur le fichier
                        $index_data['files'][$file_date] = [
                            'path' => basename($file),
                            'date' => $file_date,
                            'size' => $file_size,
                            'entries' => $lines_count,
                            'modules' => $modules_in_file,
                            'pages' => $pages_in_file
                        ];
                    }
                }
                
                // Écrire l'index
                file_put_contents($index_file, json_encode($index_data, JSON_PRETTY_PRINT));
                
                $result_message = sprintf(
                    'Index reconstruit : %d entrées indexées sur %d fichiers',
                    $index_data['total_entries'],
                    count($index_data['files'])
                );
                $result_status = 'success';
            } else {
                $result_message = 'Impossible de reconstruire l\'index : dossier analytics introuvable.';
                $result_status = 'error';
            }
            break;
    }
}

// Header
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><span class="module-icon">🔧</span> Maintenance Analytics</h1>
        <p class="admin-description">Gestion et optimisation du système d'analytics</p>
    </div>

    <?php if ($result_message): ?>
    <div class="alert alert-<?= $result_status ?>">
        <?= htmlspecialchars($result_message) ?>
    </div>
    <?php endif; ?>

    <div class="maintenance-panel">
        <h2>État du système d'analytics</h2>
        
        <div class="system-status">
            <?php
            $total_size = 0;
            $files_count = 0;
            $oldest_file = null;
            $newest_file = null;
            $total_entries = 0;
            
            if (file_exists($analytics_dir)) {
                $files = glob($analytics_dir . 'visits_*.log');
                $files_count = count($files);
                
                foreach ($files as $file) {
                    $total_size += filesize($file);
                    
                    // Déterminer la date du fichier
                    if (preg_match('/visits_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                        $file_date = $matches[1];
                        
                        if ($oldest_file === null || strtotime($file_date) < strtotime($oldest_file)) {
                            $oldest_file = $file_date;
                        }
                        
                        if ($newest_file === null || strtotime($file_date) > strtotime($newest_file)) {
                            $newest_file = $file_date;
                        }
                    }
                    
                    // Compter les entrées
                    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $total_entries += count($lines);
                }
            }
            ?>
            
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($total_entries) ?></div>
                        <div class="stat-label">Entrées analytics</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($files_count) ?></div>
                        <div class="stat-label">Fichiers de logs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($total_size / 1024 / 1024, 2) ?> MB</div>
                        <div class="stat-label">Espace disque utilisé</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $oldest_file ? htmlspecialchars($oldest_file) : 'N/A' ?></div>
                        <div class="stat-label">Données les plus anciennes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <h2>Actions de maintenance</h2>
        
        <div class="maintenance-actions">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon">🧹</div>
                    <h3>Nettoyage des anciennes données</h3>
                </div>
                <p class="action-description">
                    Supprime les fichiers de logs antérieurs à une date spécifique pour libérer de l'espace disque.
                </p>
                <form action="" method="GET" class="action-form">
                    <input type="hidden" name="action" value="cleanup">
                    <div class="form-group">
                        <label for="before">Supprimer les données avant le</label>
                        <input type="date" id="before" name="before" value="<?= date('Y-m-d', strtotime('-90 days')) ?>">
                    </div>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces données? Cette action est irréversible.')">
                        Nettoyer les données
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon">🔄</div>
                    <h3>Optimisation du stockage</h3>
                </div>
                <p class="action-description">
                    Optimise les fichiers de logs en supprimant les doublons et les entrées invalides pour réduire leur taille.
                </p>
                <div class="action-buttons">
                    <a href="?action=optimize" class="btn btn-primary">
                        Optimiser le stockage
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon">🔍</div>
                    <h3>Reconstruire l'index</h3>
                </div>
                <p class="action-description">
                    Reconstruit l'index des données analytics pour accélérer les requêtes et améliorer les performances.
                </p>
                <div class="action-buttons">
                    <a href="?action=rebuild_index" class="btn btn-primary">
                        Reconstruire l'index
                    </a>
                </div>
            </div>
        </div>
        
        <div class="back-link">
            <a href="analytics.php" class="btn btn-secondary">
                <span class="btn-icon">⬅️</span>
                Retour aux statistiques
            </a>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques pour la page maintenance */
.maintenance-panel {
    background-color: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-top: 24px;
}

.maintenance-panel h2 {
    font-size: 1.25rem;
    color: var(--gray-800);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-200);
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: #065f46;
}

.alert-error {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #991b1b;
}

.alert-info {
    background-color: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    color: #1e40af;
}

.maintenance-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.action-card {
    background-color: var(--gray-50);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
}

.action-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.action-icon {
    font-size: 24px;
    color: var(--primary-blue);
}

.action-header h3 {
    font-size: 1.1rem;
    color: var(--gray-800);
    margin: 0;
}

.action-description {
    color: var(--gray-600);
    font-size: 0.875rem;
    margin-bottom: 16px;
}

.action-form {
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 12px;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    color: var(--gray-700);
    margin-bottom: 4px;
}

.form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--gray-300);
    border-radius: 4px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: var(--primary-blue);
    color: white;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-warning {
    background-color: #f59e0b;
    color: white;
}

.back-link {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--gray-200);
    text-align: center;
}

.btn-icon {
    font-size: 1rem;
}
</style>

<?php
// Footer
include_once ROOT_PATH . '/templates/footer.php';
?>
