<?php
// /public/admin/controle-qualite/index.php
require_once '../../config/config.php';
require_once '../../config/version.php';
require_once '../controle-qualite/lib/Controle.php';

session_start();
$controle = new Controle($pdo);

// Traitement actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($controle->delete($id)) {
                $_SESSION['success'] = 'Contrôle supprimé avec succès';
            } else {
                $_SESSION['error'] = 'Erreur lors de la suppression';
            }
            break;
            
        case 'update_agence':
            $nom = $_POST['nom'] ?? '';
            $email = $_POST['email'] ?? '';
            if ($nom && $email) {
                $stmt = $pdo->prepare("INSERT INTO gul_agences (nom, email) VALUES (?, ?) ON DUPLICATE KEY UPDATE email = ?");
                $stmt->execute([$nom, $email, $email]);
                $_SESSION['success'] = 'Agence mise à jour';
            }
            break;
    }
    
    header('Location: index.php');
    exit;
}

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM gul_controles")->fetchColumn(),
    'aujourd_hui' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE DATE(date_controle) = CURDATE()")->fetchColumn(),
    'cette_semaine' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE WEEK(date_controle) = WEEK(NOW())")->fetchColumn(),
    'ce_mois' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE MONTH(date_controle) = MONTH(NOW())")->fetchColumn()
];

// Contrôles récents
$controles = $controle->search(['limit' => 20]);

// Agences
$agences = $pdo->query("SELECT * FROM gul_agences ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Contrôle Qualité - Guldagil</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/modules/controle-qualite.css">
    <style>
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .admin-tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #eee; }
        .admin-tab { padding: 1rem 2rem; background: #f8f9fa; border: none; cursor: pointer; border-radius: 8px 8px 0 0; }
        .admin-tab.active { background: var(--cq-primary); color: white; }
        .admin-content { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .admin-table th, .admin-table td { padding: 0.75rem; border-bottom: 1px solid #eee; text-align: left; }
        .admin-table th { background: #f8f9fa; font-weight: 600; }
        .admin-table tr:hover { background: #f8f9fa; }
        .admin-actions { display: flex; gap: 0.5rem; }
        .btn-danger { background: var(--cq-error); color: white; }
        .btn-danger:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="cq-header">
            <h1>⚙️ Administration Contrôle Qualité</h1>
            <a href="../index.php" class="btn btn-secondary">← Retour Admin</a>
        </header>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']) ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']) ?>
        <?php endif; ?>

        <!-- Onglets -->
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="showTab('dashboard')">📊 Dashboard</button>
            <button class="admin-tab" onclick="showTab('controles')">📋 Contrôles</button>
            <button class="admin-tab" onclick="showTab('agences')">🏢 Agences</button>
            <button class="admin-tab" onclick="showTab('export')">📤 Export</button>
        </div>

        <!-- Dashboard -->
        <div id="tab-dashboard" class="admin-content">
            <h2>Statistiques</h2>
            <div class="cq-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total contrôles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['aujourd_hui'] ?></div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['cette_semaine'] ?></div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['ce_mois'] ?></div>
                    <div class="stat-label">Ce mois</div>
                </div>
            </div>

            <h3>Répartition par agence</h3>
            <?php
            $repartition = $pdo->query("
                SELECT agence, COUNT(*) as total 
                FROM gul_controles 
                WHERE DATE(date_controle) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY agence 
                ORDER BY total DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="admin-table">
                <thead>
                    <tr><th>Agence</th><th>Contrôles (30j)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($controles as $ctrl): ?>
                        <tr>
                            <td><?= $ctrl['id'] ?></td>
                            <td><?= htmlspecialchars($ctrl['numero_arc'] ?? 'N/A') ?></td>
                            <td><?= ucfirst($ctrl['type_equipement']) ?></td>
                            <td><?= htmlspecialchars($ctrl['agence']) ?></td>
                            <td><?= htmlspecialchars($ctrl['nom_installation']) ?></td>
                            <td><?= htmlspecialchars($ctrl['operateur_nom']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($ctrl['date_controle'])) ?></td>
                            <td><span class="status status-<?= $ctrl['statut'] ?>"><?= ucfirst($ctrl['statut']) ?></span></td>
                            <td class="admin-actions">
                                <a href="../controle-qualite/index.php?controller=pompe-doseuse&action=pdf&id=<?= $ctrl['id'] ?>" 
                                   class="btn btn-small" target="_blank">PDF</a>
                                <button onclick="deleteControl(<?= $ctrl['id'] ?>)" class="btn btn-small btn-danger">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Agences -->
        <div id="tab-agences" class="admin-content" style="display: none;">
            <h2>Configuration des agences</h2>
            
            <form method="POST" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="update_agence">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom agence</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email contrôle qualité</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter/Modifier</button>
            </form>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Agence</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agences as $agence): ?>
                        <tr>
                            <td><?= htmlspecialchars($agence['nom']) ?></td>
                            <td><?= htmlspecialchars($agence['email'] ?? 'Non défini') ?></td>
                            <td><?= $agence['actif'] ? '✅ Active' : '❌ Inactive' ?></td>
                            <td>
                                <button onclick="editAgence('<?= htmlspecialchars($agence['nom']) ?>', '<?= htmlspecialchars($agence['email']) ?>')" 
                                        class="btn btn-small">Modifier</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Export -->
        <div id="tab-export" class="admin-content" style="display: none;">
            <h2>Export des données</h2>
            
            <form method="GET" action="export.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date début</label>
                        <input type="date" id="date_debut" name="date_debut" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date fin</label>
                        <input type="date" id="date_fin" name="date_fin" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="agence_filter">Agence</label>
                        <select id="agence_filter" name="agence">
                            <option value="">Toutes les agences</option>
                            <?php foreach ($agences as $agence): ?>
                                <option value="<?= htmlspecialchars($agence['nom']) ?>">
                                    <?= htmlspecialchars($agence['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="format">Format</label>
                        <select id="format" name="format">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">📤 Exporter</button>
            </form>

            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                <h4>Informations export</h4>
                <ul>
                    <li>L'export inclut tous les détails des contrôles</li>
                    <li>Format CSV : Compatible Excel et autres tableurs</li>
                    <li>Format Excel : Fichier .xlsx avec formatage</li>
                    <li>Les données sensibles sont anonymisées</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal confirmation suppression -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px;">
            <h3>Confirmer la suppression</h3>
            <p>Êtes-vous sûr de vouloir supprimer ce contrôle ? Cette action est irréversible.</p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Gestion onglets
        function showTab(tabName) {
            document.querySelectorAll('.admin-content').forEach(content => {
                content.style.display = 'none';
            });
            
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById('tab-' + tabName).style.display = 'block';
            event.target.classList.add('active');
        }

        // Suppression contrôle
        function deleteControl(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Édition agence
        function editAgence(nom, email) {
            document.getElementById('nom').value = nom;
            document.getElementById('email').value = email;
            document.getElementById('nom').focus();
        }

        // Recherche en temps réel
        function filterTable() {
            const input = document.getElementById('search-input');
            const filter = input.value.toLowerCase();
            const table = document.querySelector('#tab-controles .admin-table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Export rapide
        function exportQuick() {
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'export.php';
            
            const dateDebut = document.createElement('input');
            dateDebut.type = 'hidden';
            dateDebut.name = 'date_debut';
            dateDebut.value = document.getElementById('date_debut')?.value || '<?= date('Y-m-01') ?>';
            
            const dateFin = document.createElement('input');
            dateFin.type = 'hidden';
            dateFin.name = 'date_fin';
            dateFin.value = document.getElementById('date_fin')?.value || '<?= date('Y-m-d') ?>';
            
            const format = document.createElement('input');
            format.type = 'hidden';
            format.name = 'format';
            format.value = 'csv';
            
            form.appendChild(dateDebut);
            form.appendChild(dateFin);
            form.appendChild(format);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // Actualisation auto stats
        function refreshStats() {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = data.aujourd_hui || 0;
                })
                .catch(() => {}); // Ignore erreurs
        }

        // Fermer modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Actualiser stats toutes les minutes
        setInterval(refreshStats, 60000);

        // Ajouter champ recherche aux contrôles
        document.addEventListener('DOMContentLoaded', function() {
            const controlesTab = document.getElementById('tab-controles');
            if (controlesTab) {
                const searchHtml = `
                    <div style="margin-bottom: 1rem;">
                        <input type="text" id="search-input" placeholder="Rechercher..." 
                               style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;"
                               onkeyup="filterTable()">
                        <button onclick="exportQuick()" class="btn btn-secondary" style="margin-left: 1rem;">
                            📤 Export rapide CSV
                        </button>
                    </div>
                `;
                controlesTab.querySelector('h2').insertAdjacentHTML('afterend', searchHtml);
            }
        });
    </script>
</body>
</html>
                    <?php foreach ($repartition as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['agence']) ?></td>
                            <td><?= $row['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Contrôles -->
        <div id="tab-controles" class="admin-content" style="display: none;">
            <h2>Gestion des contrôles</h2>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>N° ARC</th>
                        <th>Type</th>
                        <th>Agence</th>
                        <th>Installation</th>
                        <th>Opérateur</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
