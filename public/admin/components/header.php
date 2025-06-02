<header class="admin-header">
    <h1>
        <div>⚙️</div>
        <div>
            Administration
            <div class="subtitle">Guldagil Port Calculator v1.2.0</div>
        </div>
    </h1>
    <nav class="admin-nav">
        <a href="../" title="Retour au calculateur">
            <span>🏠</span> Calculateur
        </a>
        <a href="export.php?type=all&format=csv" title="Export rapide">
            <span>📥</span> Export
        </a>
        <div class="user-info">
            <span>👤</span> <?= htmlspecialchars($userInfo['username']) ?>
        </div>
        <a href="logout.php" class="btn-logout">
            <span>🚪</span>
        </a>
    </nav>
</header>
