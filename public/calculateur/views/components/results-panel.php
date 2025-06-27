<?php
/**
 * Titre: Panneau de résultats du calculateur
 * Chemin: /public/calculateur/views/components/results-panel.php
 * Version: 0.5 beta + build
 */
?>

<div class="results-panel">
    <!-- En-tête résultats -->
    <div class="results-header">
        <h3 class="results-title">
            <span class="title-icon">💰</span>
            Résultats du calcul
        </h3>
        <div class="results-status" id="results-status">
            <span class="status-text">Remplissez le formulaire pour voir les tarifs</span>
            <div class="status-progress" id="status-progress"></div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="results-content">
        
        <!-- État initial -->
        <div class="results-empty" id="results-empty">
            <div class="empty-icon">📋</div>
            <h4 class="empty-title">Prêt pour le calcul</h4>
            <p class="empty-description">
                Renseignez le département et le poids pour commencer la comparaison automatique.
            </p>
            <div class="empty-features">
                <div class="feature-item">✓ Calcul temps réel</div>
                <div class="feature-item">✓ 3 transporteurs</div>
                <div class="feature-item">✓ Meilleur prix automatique</div>
            </div>
        </div>

        <!-- Loading -->
        <div class="results-loading" id="results-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <div class="loading-text">
                <div class="loading-title">Calcul en cours...</div>
                <div class="loading-subtitle">Comparaison des transporteurs</div>
            </div>
            <div class="loading-steps">
                <div class="loading-step active">Validation données</div>
                <div class="loading-step">Calcul Heppner</div>
                <div class="loading-step">Calcul XPO</div>
                <div class="loading-step">Calcul K+N</div>
                <div class="loading-step">Comparaison</div>
            </div>
        </div>

        <!-- Résultats -->
        <div class="results-display" id="results-display" style="display: none;">
            
            <!-- Meilleur tarif -->
            <div class="best-rate-card" id="best-rate-card">
                <div class="best-rate-badge">Meilleur tarif</div>
                <div class="best-rate-content">
                    <div class="carrier-info">
                        <div class="carrier-icon" id="best-carrier-icon">🚛</div>
                        <div class="carrier-details">
                            <div class="carrier-name" id="best-carrier-name">Heppner</div>
                            <div class="carrier-delay" id="best-carrier-delay">24-48h</div>
                        </div>
                    </div>
                    <div class="rate-price">
                        <span class="price-value" id="best-price-value">89,50</span>
                        <span class="price-currency">€</span>
                    </div>
                </div>
                <div class="best-rate-actions">
                    <button class="action-btn primary" id="btn-select-best">
                        Sélectionner
                    </button>
                    <button class="action-btn secondary" id="btn-details-best">
                        Détails
                    </button>
                </div>
            </div>

            <!-- Comparaison complète -->
            <div class="comparison-section">
                <div class="section-header">
                    <h4 class="section-title">Comparaison détaillée</h4>
                    <button class="toggle-btn" id="btn-toggle-comparison">
                        <span class="toggle-text">Afficher tout</span>
                        <span class="toggle-icon">▼</span>
                    </button>
                </div>
                
                <div class="comparison-list" id="comparison-list">
                    <!-- Généré par JavaScript -->
                </div>
            </div>

            <!-- Suggestions -->
            <div class="suggestions-section" id="suggestions-section" style="display: none;">
                <div class="section-header">
                    <h4 class="section-title">💡 Suggestions</h4>
                </div>
                <div class="suggestions-list" id="suggestions-list">
                    <!-- Généré par JavaScript -->
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions">
                <button class="quick-btn" id="btn-export" title="Exporter les résultats">
                    📊 Exporter
                </button>
                <button class="quick-btn" id="btn-print" title="Imprimer">
                    🖨️ Imprimer
                </button>
                <button class="quick-btn" id="btn-share" title="Partager">
                    📤 Partager
                </button>
                <button class="quick-btn" id="btn-new-calc" title="Nouveau calcul">
                    🔄 Nouveau
                </button>
            </div>
        </div>

        <!-- Erreur -->
        <div class="results-error" id="results-error" style="display: none;">
            <div class="error-icon">⚠️</div>
            <h4 class="error-title">Erreur de calcul</h4>
            <p class="error-message" id="error-message">
                Une erreur est survenue lors du calcul des tarifs.
            </p>
            <div class="error-actions">
                <button class="error-btn retry" id="btn-retry">
                    🔄 Réessayer
                </button>
                <button class="error-btn contact" id="btn-contact">
                    📞 Support
                </button>
            </div>
        </div>

    </div>

    <!-- Détails expandables -->
    <div class="details-panel" id="details-panel" style="display: none;">
        <div class="details-header">
            <h4 class="details-title">Détail des calculs</h4>
            <button class="details-close" id="btn-close-details">×</button>
        </div>
        <div class="details-content" id="details-content">
            <!-- Généré par JavaScript -->
        </div>
    </div>

    <!-- Footer statistiques -->
    <div class="results-footer">
        <div class="calc-stats" id="calc-stats">
            <div class="stat-item">
                <span class="stat-value" id="stat-calculations">0</span>
                <span class="stat-label">calculs</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="stat-time">-</span>
                <span class="stat-label">temps</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="stat-savings">-</span>
                <span class="stat-label">économie</span>
            </div>
        </div>
    </div>
</div>
