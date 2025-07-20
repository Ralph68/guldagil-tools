<?php
session_start();

// Vérification de l'authentification et des droits
require_once '../../config/auth_database.php';
require_once '../../core/auth/AuthManager.php';
require_once '../../config/roles.php';

$authManager = new AuthManager();
if (!$authManager->isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

$user = $authManager->getCurrentUser();
$userRole = $user['role'] ?? 'guest';

// Vérifier les droits d'accès au module outillage
if (!canAccessModule('outillages', getModuleAccessByRole($userRole, $modules)[$userRole])) {
    header('Location: ../../index.php?error=access_denied');
    exit();
}

require_once './classes/OutillageManager.php';
$outillageManager = new OutillageManager();

// Récupération des statistiques
$stats = $outillageManager->getStatistiquesGenerales();
$demandesEnAttente = $outillageManager->getDemandesEnAttente();

// Configuration des droits selon le rôle
$canManageInventory = in_array($userRole, ['admin', 'dev']);
$canValidateDemands = in_array($userRole, ['admin', 'dev']);
$canViewStats = in_array($userRole, ['admin', 'dev']);
$canManageEmployees = in_array($userRole, ['admin', 'dev']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outillage - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="./assets/css/outillage.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../templates/header.php'; ?>
    
    <div class="container">
        <div class="module-header">
            <h1><i class="fas fa-tools"></i> Gestion de l'Outillage</h1>
            <p>Dashboard de gestion des outils et équipements</p>
        </div>

        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_outils']) ?></h3>
                    <p>Outils total</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['outils_attribues']) ?></h3>
                    <p>Outils attribués</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['demandes_attente']) ?></h3>
                    <p>Demandes en attente</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['maintenance_due']) ?></h3>
                    <p>Maintenance due</p>
                </div>
            </div>
        </div>

        <!-- Menu principal -->
        <div class="actions-grid">
            <?php if ($canManageInventory): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3>Inventaire</h3>
                <p>Gérer les outils et équipements</p>
                <a href="./inventory.php" class="btn btn-primary">Accéder</a>
            </div>
            <?php endif; ?>

            <?php if ($canManageEmployees): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Employés</h3>
                <p>Gérer les employés et attributions</p>
                <a href="./employees.php" class="btn btn-primary">Accéder</a>
            </div>
            <?php endif; ?>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Nouvelle Demande</h3>
                <p>Demander du matériel ou un remplacement</p>
                <a href="./demande.php" class="btn btn-success">Créer</a>
            </div>

            <?php if ($canValidateDemands): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Validation</h3>
                <p>Valider les demandes en attente</p>
                <a href="./validation.php" class="btn btn-warning">
                    Valider 
                    <?php if ($stats['demandes_attente'] > 0): ?>
                        <span class="badge"><?= $stats['demandes_attente'] ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3>Mes Outils</h3>
                <p>Voir mes outils attribués</p>
                <a href="./mes-outils.php" class="btn btn-info">Voir</a>
            </div>

            <?php if ($canViewStats): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Statistiques</h3>
                <p>Rapports et analyses</p>
                <a href="./stats.php" class="btn btn-secondary">Analyser</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Alertes et notifications -->
        <?php if ($stats['maintenance_due'] > 0 || $stats['demandes_attente'] > 0): ?>
        <div class="alerts-section">
            <h2><i class="fas fa-bell"></i> Alertes</h2>
            
            <?php if ($stats['maintenance_due'] > 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-wrench"></i>
                <strong>Maintenance requise:</strong> 
                <?= $stats['maintenance_due'] ?> outil(s) nécessitent une maintenance.
                <a href="./maintenance.php" class="alert-link">Voir les détails</a>
            </div>
            <?php endif; ?>

            <?php if ($stats['demandes_attente'] > 0 && $canValidateDemands): ?>
            <div class="alert alert-info">
                <i class="fas fa-clock"></i>
                <strong>Demandes en attente:</strong> 
                <?= $stats['demandes_attente'] ?> demande(s) à traiter.
                <a href="./validation.php" class="alert-link">Traiter maintenant</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Activité récente -->
        <div class="recent-activity">
            <h2><i class="fas fa-history"></i> Activité Récente</h2>
            <div class="activity-list">
                <!-- Sera rempli par AJAX ou PHP selon les dernières actions -->
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="activity-content">
                        <p><strong>Nouvel outil ajouté</strong></p>
                        <small>Il y a 2 heures</small>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="activity-content">
                        <p><strong>Attribution effectuée</strong></p>
                        <small>Il y a 5 heures</small>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-content">
                        <p><strong>Demande validée</strong></p>
                        <small>Hier</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../templates/footer.php'; ?>
    <script src="./assets/js/outillage.js"></script>
</body>
</html>