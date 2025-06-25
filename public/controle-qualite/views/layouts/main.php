<!-- /public/controle-qualite/views/layouts/main.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Contrôle Qualité' ?> - Guldagil</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modules/controle-qualite.css">
</head>
<body>
    <div class="cq-container">
        <header class="cq-header">
            <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="logo">
            <nav class="cq-nav">
                <a href="index.php">🏠 Accueil</a>
                <a href="index.php?controller=pompe-doseuse&action=nouveau">➕ Nouveau</a>
                <a href="index.php?controller=recherche">🔍 Recherche</a>
            </nav>
            <div class="version"><?= renderVersionFooter() ?></div>
        </header>

        <main class="cq-main">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>

        <footer class="cq-footer">
            <div>© <?= date('Y') ?> Guldagil - Tous droits réservés</div>
            <div><?= renderVersionFooter() ?></div>
        </footer>
    </div>

    <script src="../assets/js/modules/controle-qualite.js"></script>
</body>
</html>
