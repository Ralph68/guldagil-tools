<?php
// /public/controle-qualite/index.php
require_once '../../config/config.php';
require_once '../../config/version.php';

session_start();

// Stats rapides (sans model pour l'instant)
try {
    $stats_today = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE DATE(date_controle) = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    $stats_today = 0;
}

try {
    $stats_en_cours = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'en_cours'")->fetchColumn();
} catch (Exception $e) {
    $stats_en_cours = 0;
}

try {
    $stats_termines = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'termine' AND DATE(date_controle) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {
    $stats_termines = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrôle Qualité - Guldagil</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modules/controle-qualite.css">
</head>
<body>
    <div class="container">
        <header class="cq-header">
            <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="logo">
            <div>
                <h1>🔍 Contrôle Qualité</h1>
                <p>Module de contrôle et validation des équipements</p>
            </div>
            <div class="version"><?= renderVersionFooter() ?></div>
        </header>

        <main>
            <div class="cq-actions">
                <a href="nouveau.php" class="btn btn-primary">➕ Nouveau Contrôle</a>
                <a href="recherche.php" class="btn btn-secondary">🔍 Rechercher</a>
            </div>

            <div class="cq-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_today ?></div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_en_cours ?></div>
                    <div class="stat-label">En cours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_termines ?></div>
                    <div class="stat-label">Terminés (7j)</div>
                </div>
            </div>

            <div class="cq-recent">
                <h2>Module en cours de développement</h2>
                <p>Le module contrôle qualité sera bientôt disponible.</p>
            </div>
        </main>

        <footer class="cq-footer">
            <div>© <?= date('Y') ?> Guldagil</div>
            <div><?= renderVersionFooter() ?></div>
        </footer>
    </div>
</body>
</html>

session_start();

// Stats rapides (sans model pour l'instant)
try {
    $stats_today = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE DATE(date_controle) = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    $stats_today = 0;
}

try {
    $stats_en_cours = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'en_cours'")->fetchColumn();
} catch (Exception $e) {
    $stats_en_cours = 0;
}

try {
    $stats_termines = $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'termine' AND DATE(date_controle) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {
    $stats_termines = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrôle Qualité - Guldagil</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modules/controle-qualite.css">
</head>
<body>
    <div class="container">
        <header class="cq-header">
            <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="logo">
            <div>
                <h1>🔍 Contrôle Qualité</h1>
                <p>Module de contrôle et validation des équipements</p>
            </div>
            <div class="version"><?= renderVersionFooter() ?></div>
        </header>

        <main>
            <div class="cq-actions">
                <a href="nouveau.php" class="btn btn-primary">➕ Nouveau Contrôle</a>
                <a href="recherche.php" class="btn btn-secondary">🔍 Rechercher</a>
            </div>

            <div class="cq-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_today ?></div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_en_cours ?></div>
                    <div class="stat-label">En cours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats_termines ?></div>
                    <div class="stat-label">Terminés (7j)</div>
                </div>
            </div>

            <div class="cq-recent">
                <h2>Module en cours de développement</h2>
                <p>Le module contrôle qualité sera bientôt disponible.</p>
            </div>
        </main>

        <footer class="cq-footer">
            <div>© <?= date('Y') ?> Guldagil</div>
            <div><?= renderVersionFooter() ?></div>
        </footer>
    </div>
</body>
</html>
