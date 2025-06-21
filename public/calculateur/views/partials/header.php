<?php
/**
 * Titre: En-tête du module calculateur
 * Chemin: /public/calculateur/views/partials/header.php
 * Version: 0.5 beta + build
 */

// Récupération des informations version
$version_info = $version_info ?? getVersionInfo();
?>

<header class="calculator-header">
    <div class="calc-container">
        <div class="header-content">
            
            <!-- Marque et titre -->
            <div class="header-brand">
                <a href="../" class="brand-link" title="Retour au portail">
                    <img src="../assets/img/logo_guldagil.png" alt="Logo Guldagil" class="brand-logo">
                </a>
                <div class="brand-info">
                    <h1 class="brand-title"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="brand-subtitle">Interface progressive • Temps réel</p>
                </div>
            </div>
            
            <!-- Indicateurs de statut -->
            <div class="header-status">
                <div class="status-indicator" id="connection-status">
                    <span class="status-dot online"></span>
                    <span class="status-text">Connecté</span>
                </div>
                <div class="calculation-counter" id="calc-counter">
                    <span class="counter-value">0</span>
                    <span class="counter-label">calculs</span>
                </div>
            </div>
            
            <!-- Actions de navigation -->
            <div class="header-actions">
                <button type="button" class="header-btn secondary" id="btn-nouveau" title="Nouveau calcul">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Nouveau</span>
                </button>
                
                <a href="../admin/" class="header-btn secondary" title="Administration">
                    <span class="btn-icon">⚙️</span>
                    <span class="btn-text">Admin</span>
                </a>
                
                <a href="../" class="header-btn primary" title="Retour au portail">
                    <span class="btn-icon">🏠</span>
                    <span class="btn-text">Portail</span>
                </a>
            </div>
            
        </div>
    </div>
</header>

<style>
/* ========================================
   STYLES HEADER CALCULATEUR
======================================== */

.calculator-header {
    background: var(--calc-white);
    border-bottom: 1px solid var(--calc-gray-200);
    padding: var(--calc-space-4) 0;
    position: sticky;
    top: 0;
    z-index: var(--calc-z-header);
    box-shadow: var(--calc-shadow-sm);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--calc-space-6);
}

/* Marque */
.header-brand {
    display: flex;
    align-items: center;
    gap: var(--calc-space-4);
    flex-shrink: 0;
}

.brand-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: var(--calc-transition);
}

.brand-link:hover {
    transform: scale(1.05);
}

.brand-logo {
    height: 40px;
    width: auto;
}

.brand-info {
    display: flex;
    flex-direction: column;
    gap: var(--calc-space-1);
}

.brand-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--calc-primary);
    line-height: 1.2;
}

.brand-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: var(--calc-gray-600);
    font-weight: 500;
}

/* Statut */
.header-status {
    display: flex;
    align-items: center;
    gap: var(--calc-space-6);
    flex-shrink: 0;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: var(--calc-space-2);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    transition: var(--calc-transition);
}

.status-dot.online {
    background: var(--calc-success);
    box-shadow: 0 0 4px rgba(16, 185, 129, 0.5);
}

.status-dot.offline {
    background: var(--calc-error);
    box-shadow: 0 0 4px rgba(239, 68, 68, 0.5);
}

.status-dot.loading {
    background: var(--calc-warning);
    box-shadow: 0 0 4px rgba(245, 158, 11, 0.5);
    animation: calc-pulse 1.5s infinite;
}

.status-text {
    font-size: 0.8rem;
    color: var(--calc-gray-600);
    font-weight: 500;
}

.calculation-counter {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--calc-space-1);
}

.counter-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--calc-primary);
    line-height: 1;
}

.counter-label {
    font-size: 0.7rem;
    color: var(--calc-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: var(--calc-space-3);
    flex-shrink: 0;
}

.header-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--calc-space-2);
    padding: var(--calc-space-2) var(--calc-space-4);
    border: 2px solid transparent;
    border-radius: var(--calc-radius);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    cursor: pointer;
    transition: var(--calc-transition);
    background: transparent;
}

.header-btn.primary {
    background: var(--calc-primary);
    color: var(--calc-white);
    border-color: var(--calc-primary);
}

.header-btn.primary:hover {
    background: var(--calc-primary-dark);
    border-color: var(--calc-primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--calc-shadow-md);
}

.header-btn.secondary {
    color: var(--calc-primary);
    border-color: var(--calc-gray-300);
}

.header-btn.secondary:hover {
    background: var(--calc-primary);
    color: var(--calc-white);
    border-color: var(--calc-primary);
}

.btn-icon {
    font-size: 1rem;
    line-height: 1;
}

.btn-text {
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: var(--calc-space-4);
        text-align: center;
    }
    
    .header-brand {
        justify-content: center;
    }
    
    .brand-title {
        font-size: 1.1rem;
    }
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .header-status {
        order: -1;
        justify-content: center;
    }
    
    .btn-text {
        display: none;
    }
    
    .header-btn {
        padding: var(--calc-space-2);
        min-width: 40px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .calculator-header {
        padding: var(--calc-space-3) 0;
    }
    
    .brand-logo {
        height: 32px;
    }
    
    .brand-title {
        font-size: 1rem;
    }
    
    .brand-subtitle {
        font-size: 0.7rem;
    }
    
    .header-status {
        gap: var(--calc-space-4);
    }
    
    .status-text {
        display: none;
    }
}

/* Animations d'état */
@keyframes counter-update {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); color: var(--calc-success); }
    100% { transform: scale(1); }
}

.counter-value.updating {
    animation: counter-update 0.3s ease-out;
}

/* Indicateurs de chargement */
.header-loading {
    position: relative;
    overflow: hidden;
}

.header-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--calc-primary), transparent);
    animation: loading-sweep 1.5s infinite;
}

@keyframes loading-sweep {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du compteur de calculs
    let calculationCount = 0;
    const counterElement = document.getElementById('calc-counter')?.querySelector('.counter-value');
    
    // Observer les calculs
    if (window.calculateurState) {
        window.calculateurState.observe('ui.isCalculating', function(isCalculating) {
            const statusDot = document.querySelector('.status-dot');
            const statusText = document.querySelector('.status-text');
            
            if (isCalculating) {
                statusDot?.classList.remove('online', 'offline');
                statusDot?.classList.add('loading');
                if (statusText) statusText.textContent = 'Calcul...';
                
                // Ajouter indicateur de chargement
                document.querySelector('.calculator-header')?.classList.add('header-loading');
            } else {
                statusDot?.classList.remove('loading');
                statusDot?.classList.add('online');
                if (statusText) statusText.textContent = 'Connecté';
                
                // Incrémenter compteur
                calculationCount++;
                if (counterElement) {
                    counterElement.textContent = calculationCount;
                    counterElement.classList.add('updating');
                    setTimeout(() => counterElement.classList.remove('updating'), 300);
                }
                
                // Retirer indicateur de chargement
                document.querySelector('.calculator-header')?.classList.remove('header-loading');
            }
        });
    }
    
    // Gestion du bouton nouveau calcul
    document.getElementById('btn-nouveau')?.addEventListener('click', function() {
        if (window.calculateurState && confirm('Voulez-vous vraiment recommencer ?')) {
            window.calculateurState.reset();
        }
    });
    
    // Détection de l'état de connexion
    window.addEventListener('online', function() {
        const statusDot = document.querySelector('.status-dot');
        const statusText = document.querySelector('.status-text');
        statusDot?.classList.remove('offline');
        statusDot?.classList.add('online');
        if (statusText) statusText.textContent = 'Connecté';
    });
    
    window.addEventListener('offline', function() {
        const statusDot = document.querySelector('.status-dot');
        const statusText = document.querySelector('.status-text');
        statusDot?.classList.remove('online');
        statusDot?.classList.add('offline');
        if (statusText) statusText.textContent = 'Hors ligne';
    });
});
</script>
