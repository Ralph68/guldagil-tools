<?php
// components/navigation.php - Navigation par onglets fixe
?>
<div class="tab-navigation">
    <button class="tab-button active" onclick="showTab('dashboard')" data-tab="dashboard">
        <span>📊</span>
        Tableau de bord
    </button>
    <button class="tab-button" onclick="showTab('rates')" data-tab="rates">
        <span>💰</span>
        Gestion des tarifs
        <?php if (isset($stats['total_rates']) && $stats['total_rates'] > 0): ?>
            <small style="background: var(--success-color); color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                <?= $stats['total_rates'] ?>
            </small>
        <?php endif; ?>
    </button>
    <button class="tab-button" onclick="showTab('options')" data-tab="options">
        <span>⚙️</span>
        Options supplémentaires
        <?php if (isset($stats['active_options']) && $stats['active_options'] > 0): ?>
            <small style="background: var(--primary-color); color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                <?= $stats['active_options'] ?>
            </small>
        <?php endif; ?>
    </button>
    <button class="tab-button" onclick="showTab('taxes')" data-tab="taxes">
        <span>📋</span>
        Taxes & Majorations
    </button>
    <button class="tab-button" onclick="showTab('analytics')" data-tab="analytics">
        <span>📈</span>
        Analytics
        <?php if (isset($stats['coverage']) && $stats['coverage'] < 50): ?>
            <small style="background: var(--warning-color); color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                ⚠️
            </small>
        <?php endif; ?>
    </button>
    <button class="tab-button" onclick="showTab('import')" data-tab="import">
        <span>📤</span>
        Import/Export
    </button>
</div>

<style>
.tab-button small {
    transition: all 0.3s ease;
}

.tab-button:hover small {
    transform: scale(1.1);
}

.tab-button.active small {
    background: rgba(255,255,255,0.3) !important;
}
</style>
