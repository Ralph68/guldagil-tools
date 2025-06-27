<?php
// components/header.php - Header d'administration fixe
?>
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
            <span>🏠</span>
            Calculateur
        </a>
        <a href="export.php?type=all&format=csv" title="Export rapide CSV">
            <span>📥</span>
            Export CSV
        </a>
        <a href="template.php?type=rates" title="Télécharger template">
            <span>📋</span>
            Templates
        </a>
        <a href="#" onclick="showHelp()" title="Aide et documentation">
            <span>❓</span>
            Aide
        </a>
        <div class="user-info" title="Connecté depuis <?= date('H:i') ?>">
            <span>👤</span>
            <?= htmlspecialchars($userInfo['username'] ?? 'Admin') ?>
        </div>
        <a href="logout.php" title="Se déconnecter" class="btn-logout">
            <span>🚪</span>
        </a>
    </nav>
</header>

<style>
.btn-logout {
    background: var(--error-color) !important;
    color: white !important;
}

.btn-logout:hover {
    background: #d32f2f !important;
    transform: translateY(-2px) !important;
}
</style>
