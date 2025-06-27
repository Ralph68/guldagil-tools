<!-- /public/controle-qualite/views/accueil/index.php -->
<div class="cq-dashboard">
    <h1>🔍 Contrôle Qualité</h1>
    <p>Module de contrôle et validation des équipements</p>

    <!-- Actions rapides -->
    <div class="cq-actions">
        <a href="index.php?controller=pompe-doseuse&action=nouveau" class="btn btn-primary">
            ➕ Nouveau Contrôle
        </a>
        <a href="index.php?controller=recherche" class="btn btn-secondary">
            🔍 Rechercher
        </a>
    </div>

    <!-- Stats -->
    <div class="cq-stats">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['today'] ?></div>
            <div class="stat-label">Aujourd'hui</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['en_cours'] ?></div>
            <div class="stat-label">En cours</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['termines_7j'] ?></div>
            <div class="stat-label">Terminés (7j)</div>
        </div>
    </div>

    <!-- Contrôles récents -->
    <div class="cq-recent">
        <h2>Contrôles récents</h2>
        <?php if (empty($recents)): ?>
            <p class="no-data">Aucun contrôle récent</p>
        <?php else: ?>
            <div class="controls-list">
                <?php foreach ($recents as $ctrl): ?>
                    <div class="control-item">
                        <div class="control-info">
                            <strong><?= htmlspecialchars($ctrl['numero_arc'] ?? 'N/A') ?></strong>
                            <span>•</span>
                            <span><?= htmlspecialchars($ctrl['nom_installation']) ?></span>
                            <span>•</span>
                            <span><?= htmlspecialchars($ctrl['agence']) ?></span>
                        </div>
                        <div class="control-meta">
                            <span class="status status-<?= $ctrl['statut'] ?>">
                                <?= ucfirst($ctrl['statut']) ?>
                            </span>
                            <span><?= date('d/m/Y H:i', strtotime($ctrl['date_controle'])) ?></span>
                        </div>
                        <div class="control-actions">
                            <a href="index.php?controller=pompe-doseuse&action=pdf&id=<?= $ctrl['id'] ?>" class="btn btn-small">PDF</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
