<?php
// components/stats-cards.php - Cartes de statistiques
// Variables attendues : $stats, $trends
?>
<div class="stats-grid">
    <!-- Transporteurs actifs -->
    <div class="stat-card slide-in-up">
        <div class="stat-header">
            <div class="stat-title">Transporteurs actifs</div>
            <div class="stat-icon primary">🚚</div>
        </div>
        <div class="stat-value"><?= $stats['carriers'] ?></div>
        <div class="stat-trend <?= $trends['carriers']['class'] ?? 'neutral' ?>">
            <span><?= $trends['carriers']['icon'] ?? '📊' ?></span>
            Heppner, XPO, K+N configurés
        </div>
    </div>

    <!-- Départements couverts -->
    <div class="stat-card slide-in-up" style="animation-delay: 0.1s">
        <div class="stat-header">
            <div class="stat-title">Départements couverts</div>
            <div class="stat-icon success">📍</div>
        </div>
        <div class="stat-value"><?= $stats['departments'] ?></div>
        <div class="stat-trend <?= $stats['coverage'] >= 50 ? 'positive' : 'warning' ?>">
            <span><?= $stats['coverage'] >= 50 ? '📈' : '⚠️' ?></span>
            <?= $stats['coverage'] ?>% de couverture complète
        </div>
    </div>

    <!-- Options configurées -->
    <div class="stat-card slide-in-up" style="animation-delay: 0.2s">
        <div class="stat-header">
            <div class="stat-title">Options configurées</div>
            <div class="stat-icon warning">⚙️</div>
        </div>
        <div class="stat-value"><?= $stats['total_options'] ?></div>
        <div class="stat-trend <?= $stats['active_options'] > 0 ? 'positive' : 'neutral' ?>">
            <span><?= $stats['active_options'] > 0 ? '✅' : '➖' ?></span>
            <?= $stats['active_options'] ?> actives / <?= $stats['inactive_options'] ?> inactives
        </div>
    </div>

    <!-- Calculs aujourd'hui -->
    <div class="stat-card slide-in-up" style="animation-delay: 0.3s">
        <div class="stat-header">
            <div class="stat-title">Calculs aujourd'hui</div>
            <div class="stat-icon primary">📊</div>
        </div>
        <div class="stat-value"><?= $stats['calculations_today'] ?></div>
        <div class="stat-trend <?= $trends['calculations']['class'] ?? 'positive' ?>">
            <span><?= $trends['calculations']['icon'] ?? '📈' ?></span>
            <?= $trends['calculations']['text'] ?? '+15' ?>% vs hier
        </div>
    </div>

    <!-- Statut système -->
    <div class="stat-card slide-in-up" style="animation-delay: 0.4s">
        <div class="stat-header">
            <div class="stat-title">Statut système</div>
            <div class="stat-icon <?= $stats['system_status']['color'] ?>"><?= $stats['system_status']['icon'] ?></div>
        </div>
        <div class="stat-value" style="font-size: 1.2rem; color: var(--<?= $stats['system_status']['color'] ?>-color);">
            <?= $stats['system_status']['text'] ?>
        </div>
        <div class="stat-trend positive">
            <span>⚡</span>
            Dernière MàJ : <?= date('H:i') ?>
        </div>
    </div>

    <!-- Alertes système -->
    <div class="stat-card slide-in-up" style="animation-delay: 0.5s">
        <div class="stat-header">
            <div class="stat-title">Alertes système</div>
            <div class="stat-icon <?= $stats['alerts_count'] > 0 ? 'warning' : 'success' ?>">
                <?= $stats['alerts_count'] > 0 ? '⚠️' : '✅' ?>
            </div>
        </div>
        <div class="stat-value" style="color: <?= $stats['alerts_count'] > 0 ? 'var(--warning-color)' : 'var(--success-color)' ?>;">
            <?= $stats['alerts_count'] ?>
        </div>
        <div class="stat-trend <?= $stats['alerts_count'] > 0 ? 'warning' : 'positive' ?>">
            <span><?= $stats['alerts_count'] > 0 ? '⚠️' : '✅' ?></span>
            <?= $stats['alerts_count'] > 0 ? 'Problème(s) détecté(s)' : 'Aucun problème détecté' ?>
        </div>
    </div>
</div>

<!-- Alertes de couverture faible -->
<?php if ($stats['coverage'] < 30): ?>
<div class="admin-card" style="margin-top: 1rem; border-left: 4px solid var(--warning-color);">
    <div class="admin-card-body" style="background: #fff3cd; padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem;">⚠️</div>
            <div>
                <strong style="color: #856404;">Couverture tarifaire faible (<?= $stats['coverage'] ?>%)</strong>
                <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.9rem;">
                    Recommandation : Compléter les tarifs manquants pour améliorer la précision des calculs.
                    <a href="#" onclick="showTab('rates')" style="color: #856404; text-decoration: underline; font-weight: bold;">
                        → Gérer les tarifs
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Alerts d'options manquantes -->
<?php if ($stats['active_options'] === 0): ?>
<div class="admin-card" style="margin-top: 1rem; border-left: 4px solid var(--primary-color);">
    <div class="admin-card-body" style="background: #e3f2fd; padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem;">💡</div>
            <div>
                <strong style="color: #1565c0;">Aucune option supplémentaire configurée</strong>
                <p style="margin: 0.5rem 0 0 0; color: #1565c0; font-size: 0.9rem;">
                    Ajoutez des options comme la prise de RDV, livraison premium, etc. pour enrichir vos calculs.
                    <a href="#" onclick="showTab('options')" style="color: #1565c0; text-decoration: underline; font-weight: bold;">
                        → Configurer les options
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
