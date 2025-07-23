/**
 * Titre: CSS Calculateur Port - Version corrigée accessibilité
 * Chemin: /public/port/assets/css/port.css
 * Version: 0.5 beta + build auto
 * CORRECTIFS: Mode sombre désactivé + Contraste amélioré
 */

/* ============================= */
/*        VARIABLES CSS          */
/* ============================= */
:root {
    /* COULEURS PRINCIPALES - Thème clair UNIQUEMENT */
    --port-primary: #0066cc;
    --port-primary-hover: #0052a3;
    --port-primary-light: #e6f3ff;
    
    /* Couleurs de surface - FOND CLAIR OBLIGATOIRE */
    --port-background: #ffffff;
    --port-surface: #ffffff;
    --port-card-bg: #ffffff;
    --port-border: #e5e7eb;
    --port-shadow: rgba(0, 0, 0, 0.08);
    
    /* TEXTE - CONTRASTE MAXIMAL */
    --port-text-primary: #111827;    /* Noir intense */
    --port-text-secondary: #374151;  /* Gris très foncé */
    --port-text-muted: #4b5563;      /* Gris foncé */
    --port-text-inverse: #ffffff;    /* Blanc sur fond coloré */
    
    /* Couleurs d'état avec contraste amélioré */
    --port-success: #059669;         /* Vert plus foncé */
    --port-warning: #d97706;         /* Orange plus foncé */
    --port-error: #dc2626;           /* Rouge plus foncé */
    --port-info: #0284c7;            /* Bleu plus foncé */
    
    /* États interactifs */
    --port-hover-bg: #f9fafb;
    --port-active-bg: #f3f4f6;
    --port-focus-ring: 0 0 0 3px rgba(59, 130, 246, 0.1);
    
    /* Dimensionnement */
    --port-max-width: 1200px;
    --port-form-width: 480px;
    --port-results-width: 720px;
    
    /* Espacements cohérents */
    --port-space-xs: 0.25rem;  /* 4px */
    --port-space-sm: 0.5rem;   /* 8px */
    --port-space-md: 1rem;     /* 16px */
    --port-space-lg: 1.5rem;   /* 24px */
    --port-space-xl: 2rem;     /* 32px */
    --port-space-2xl: 3rem;    /* 48px */
    
    /* Rayons et ombres */
    --port-radius-sm: 6px;
    --port-radius-md: 8px;
    --port-radius-lg: 12px;
    --port-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --port-shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --port-shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    
    /* Transitions fluides mais moins agressives */
    --port-transition-fast: all 0.2s ease;
    --port-transition-normal: all 0.3s ease;
    --port-transition-slow: all 0.5s ease;
}

/* ============================= */
/*     STRUCTURE GÉNÉRALE        */
/* ============================= */
.calc-container {
    min-height: 100vh;
    background: var(--port-background);
    color: var(--port-text-primary);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    padding: var(--port-space-lg);
}

.calc-main {
    max-width: var(--port-max-width);
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--port-space-xl);
    align-items: start;
}

/* ============================= */
/*        EN-TÊTE CALCULATEUR     */
/* ============================= */
.calc-header {
    max-width: var(--port-max-width);
    margin: 0 auto var(--port-space-2xl);
    text-align: center;
}

.calc-hero {
    background: linear-gradient(135deg, var(--port-primary), var(--port-primary-hover));
    color: var(--port-text-inverse);
    padding: var(--port-space-2xl) var(--port-space-xl);
    border-radius: var(--port-radius-lg);
    box-shadow: var(--port-shadow-lg);
}

.calc-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 var(--port-space-md);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--port-space-md);
}

.calc-icon {
    font-size: 3rem;
}

.calc-subtitle {
    font-size: 1.25rem;
    opacity: 0.95;
    margin: 0 0 var(--port-space-lg);
    font-weight: 400;
}

.calc-features {
    display: flex;
    justify-content: center;
    gap: var(--port-space-md);
    flex-wrap: wrap;
}

.feature-badge {
    background: rgba(255, 255, 255, 0.15);
    color: var(--port-text-inverse);
    padding: var(--port-space-sm) var(--port-space-md);
    border-radius: var(--port-radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* ============================= */
/*     CARTE FORMULAIRE          */
/* ============================= */
.calc-form-section {
    width: 100%;
}

.calc-form-card {
    background: var(--port-card-bg);
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-lg);
    padding: var(--port-space-xl);
    box-shadow: var(--port-shadow-md);
    position: sticky;
    top: var(--port-space-lg);
}

.calc-form-title {
    color: var(--port-text-primary);
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 var(--port-space-lg);
    text-align: center;
}

/* ============================= */
/*      PROGRESSION ÉTAPES       */
/* ============================= */
.calc-progress {
    margin-bottom: var(--port-space-xl);
}

.calc-progress-bar {
    background: var(--port-border);
    height: 4px;
    border-radius: 2px;
    margin-bottom: var(--port-space-lg);
    position: relative;
    overflow: hidden;
}

.calc-progress-fill {
    background: linear-gradient(90deg, var(--port-primary), var(--port-primary-hover));
    height: 100%;
    width: 25%;
    border-radius: 2px;
    transition: width 0.4s ease;
}

.calc-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--port-space-sm);
}

.calc-step-btn {
    background: var(--port-background);
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-md);
    padding: var(--port-space-md);
    cursor: pointer;
    transition: var(--port-transition-normal);
    text-align: center;
    font-family: inherit;
    color: var(--port-text-secondary);
}

.calc-step-btn:hover {
    background: var(--port-hover-bg);
    border-color: var(--port-primary);
    transform: translateY(-1px);
}

.calc-step-btn.active {
    background: var(--port-primary);
    border-color: var(--port-primary);
    color: var(--port-text-inverse);
    box-shadow: var(--port-shadow-md);
}

.calc-step-btn.completed {
    background: var(--port-success);
    border-color: var(--port-success);
    color: var(--port-text-inverse);
}

.step-number {
    display: block;
    font-weight: 700;
    font-size: 1.125rem;
    margin-bottom: var(--port-space-xs);
}

.step-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
}

/* ============================= */
/*       ÉTAPES DU FORMULAIRE    */
/* ============================= */
.calc-step-content {
    display: none;
    animation: fadeInUp 0.4s ease;
}

.calc-step-content.active {
    display: block;
}

.calc-step-content h3 {
    color: var(--port-text-primary);
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 var(--port-space-lg);
    display: flex;
    align-items: center;
    gap: var(--port-space-sm);
}

/* ============================= */
/*        CHAMPS DE SAISIE       */
/* ============================= */
.calc-field-group {
    margin-bottom: var(--port-space-lg);
}

.calc-label {
    display: block;
    color: var(--port-text-primary);
    font-weight: 600;
    margin-bottom: var(--port-space-sm);
    font-size: 0.975rem;
}

.required {
    color: var(--port-error);
    margin-left: var(--port-space-xs);
}

.calc-input,
.calc-select {
    width: 100%;
    padding: var(--port-space-md);
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-md);
    font-size: 1rem;
    font-family: inherit;
    background: var(--port-background);
    color: var(--port-text-primary);
    transition: var(--port-transition-normal);
    box-sizing: border-box;
}

.calc-input:focus,
.calc-select:focus {
    outline: none;
    border-color: var(--port-primary);
    box-shadow: var(--port-focus-ring);
    background: var(--port-background);
}

.calc-input:hover,
.calc-select:hover {
    border-color: var(--port-primary);
}

.calc-input.error {
    border-color: var(--port-error);
    background: #fef2f2;
}

.calc-input-with-unit {
    position: relative;
    display: flex;
    align-items: center;
}

.calc-input-unit {
    position: absolute;
    right: var(--port-space-md);
    color: var(--port-text-muted);
    font-weight: 500;
    pointer-events: none;
}

.calc-field-help {
    font-size: 0.875rem;
    color: var(--port-text-muted);
    margin-top: var(--port-space-sm);
}

.calc-error {
    color: var(--port-error);
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: var(--port-space-sm);
    display: block;
    min-height: 1.25rem;
}

/* ============================= */
/*        OPTIONS ET TOGGLES     */
/* ============================= */
.calc-options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--port-space-md);
    margin-bottom: var(--port-space-lg);
}

.calc-option-card {
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-md);
    padding: var(--port-space-md);
    cursor: pointer;
    transition: var(--port-transition-normal);
    background: var(--port-background);
    display: block;
}

.calc-option-card:hover {
    border-color: var(--port-primary);
    background: var(--port-hover-bg);
    transform: translateY(-1px);
}

.calc-option-card input[type="radio"] {
    display: none;
}

.calc-option-card input[type="radio"]:checked + .calc-option-content {
    color: var(--port-primary);
}

.calc-option-card:has(input[type="radio"]:checked) {
    border-color: var(--port-primary);
    background: var(--port-primary-light);
    box-shadow: var(--port-shadow-md);
}

.calc-option-content {
    display: flex;
    align-items: center;
    gap: var(--port-space-md);
}

.calc-option-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.calc-option-text strong {
    display: block;
    color: var(--port-text-primary);
    font-weight: 600;
    margin-bottom: var(--port-space-xs);
}

.calc-option-text span {
    color: var(--port-text-muted);
    font-size: 0.875rem;
}

/* Toggle amélioré */
.calc-toggle-group {
    margin-top: var(--port-space-lg);
}

.calc-toggle {
    display: flex;
    align-items: center;
    gap: var(--port-space-md);
    cursor: pointer;
    padding: var(--port-space-md);
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-md);
    background: var(--port-background);
    transition: var(--port-transition-normal);
}

.calc-toggle:hover {
    border-color: var(--port-primary);
    background: var(--port-hover-bg);
}

.calc-toggle input[type="checkbox"] {
    display: none;
}

.calc-toggle-slider {
    position: relative;
    width: 48px;
    height: 24px;
    background: var(--port-border);
    border-radius: 12px;
    transition: var(--port-transition-normal);
    flex-shrink: 0;
}

.calc-toggle-slider::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: var(--port-background);
    border-radius: 50%;
    transition: var(--port-transition-normal);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.calc-toggle input[type="checkbox"]:checked + .calc-toggle-slider {
    background: var(--port-primary);
}

.calc-toggle input[type="checkbox"]:checked + .calc-toggle-slider::before {
    transform: translateX(24px);
}

.calc-toggle-label {
    flex: 1;
}

.calc-toggle-label strong {
    display: block;
    color: var(--port-text-primary);
    font-weight: 600;
    margin-bottom: var(--port-space-xs);
}

.calc-toggle-label small {
    color: var(--port-text-muted);
    font-size: 0.875rem;
}

/* ============================= */
/*         BOUTONS D'ACTION      */
/* ============================= */
.calc-form-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--port-space-md);
    margin-top: var(--port-space-xl);
    padding-top: var(--port-space-lg);
    border-top: 1px solid var(--port-border);
}

.calc-btn {
    padding: var(--port-space-md) var(--port-space-lg);
    border: none;
    border-radius: var(--port-radius-md);
    font-size: 1rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: var(--port-transition-normal);
    display: inline-flex;
    align-items: center;
    gap: var(--port-space-sm);
    text-decoration: none;
    box-sizing: border-box;
    min-height: 48px;
    justify-content: center;
}

.calc-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.calc-btn-primary {
    background: var(--port-primary);
    color: var(--port-text-inverse);
    border: 2px solid var(--port-primary);
}

.calc-btn-primary:hover:not(:disabled) {
    background: var(--port-primary-hover);
    border-color: var(--port-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--port-shadow-md);
}

.calc-btn-secondary {
    background: var(--port-background);
    color: var(--port-text-primary);
    border: 2px solid var(--port-border);
}

.calc-btn-secondary:hover:not(:disabled) {
    background: var(--port-hover-bg);
    border-color: var(--port-primary);
    transform: translateY(-1px);
}

.calc-btn-success {
    background: var(--port-success);
    color: var(--port-text-inverse);
    border: 2px solid var(--port-success);
}

.calc-btn-success:hover:not(:disabled) {
    background: #047857;
    border-color: #047857;
    transform: translateY(-1px);
    box-shadow: var(--port-shadow-md);
}

/* ============================= */
/*       ZONE DES RÉSULTATS      */
/* ============================= */
.calc-results-section {
    width: 100%;
}

.calc-results-container {
    position: sticky;
    top: var(--port-space-lg);
}

.calc-results-placeholder {
    background: var(--port-background);
    border: 2px dashed var(--port-border);
    border-radius: var(--port-radius-lg);
    padding: var(--port-space-2xl);
    text-align: center;
    color: var(--port-text-muted);
}

.calc-placeholder-icon {
    font-size: 3rem;
    margin-bottom: var(--port-space-md);
    opacity: 0.6;
}

/* Résultats de calcul */
.calc-results {
    display: grid;
    gap: var(--port-space-lg);
}

.calc-result-card {
    background: var(--port-background);
    border: 2px solid var(--port-border);
    border-radius: var(--port-radius-lg);
    padding: var(--port-space-lg);
    transition: var(--port-transition-normal);
    box-shadow: var(--port-shadow-sm);
}

.calc-result-card:hover {
    border-color: var(--port-primary);
    box-shadow: var(--port-shadow-md);
    transform: translateY(-2px);
}

.calc-result-card.best-price {
    border-color: var(--port-success);
    background: linear-gradient(135deg, #ecfdf5, var(--port-background));
}

.calc-result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--port-space-md);
}

.calc-carrier-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--port-text-primary);
}

.calc-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--port-primary);
}

.calc-price-label {
    font-size: 0.875rem;
    color: var(--port-text-muted);
    margin-top: var(--port-space-xs);
}

.calc-result-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--port-space-md);
    margin-top: var(--port-space-md);
    padding-top: var(--port-space-md);
    border-top: 1px solid var(--port-border);
}

.calc-detail-item {
    display: flex;
    justify-content: space-between;
    color: var(--port-text-secondary);
    font-size: 0.875rem;
}

.calc-detail-value {
    font-weight: 600;
    color: var(--port-text-primary);
}

/* Badge "Meilleur prix" */
.best-price-badge {
    background: var(--port-success);
    color: var(--port-text-inverse);
    padding: var(--port-space-xs) var(--port-space-sm);
    border-radius: var(--port-radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ============================= */
/*         STATUS ET LOADING     */
/* ============================= */
.calc-status {
    margin-top: var(--port-space-lg);
    padding: var(--port-space-md);
    border-radius: var(--port-radius-md);
    font-weight: 500;
    text-align: center;
    display: none;
}

.calc-status.loading {
    background: var(--port-primary-light);
    color: var(--port-primary);
    border: 1px solid var(--port-primary);
    display: block;
}

.calc-status.success {
    background: #ecfdf5;
    color: var(--port-success);
    border: 1px solid var(--port-success);
    display: block;
}

.calc-status.error {
    background: #fef2f2;
    color: var(--port-error);
    border: 1px solid var(--port-error);
    display: block;
}

/* Spinner de loading */
.calc-loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--port-border);
    border-radius: 50%;
    border-top-color: var(--port-primary);
    animation: spin 1s ease-in-out infinite;
}

/* ============================= */
/*         ANIMATIONS            */
/* ============================= */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* ============================= */
/*         RESPONSIVE DESIGN     */
/* ============================= */
@media (max-width: 1024px) {
    .calc-main {
        grid-template-columns: 1fr;
        gap: var(--port-space-lg);
    }
    
    .calc-results-container {
        position: static;
    }
}

@media (max-width: 768px) {
    .calc-container {
        padding: var(--port-space-md);
    }
    
    .calc-hero {
        padding: var(--port-space-xl) var(--port-space-lg);
    }
    
    .calc-title {
        font-size: 1.875rem;
        flex-direction: column;
        gap: var(--port-space-sm);
    }
    
    .calc-icon {
        font-size: 2.5rem;
    }
    
    .calc-features {
        flex-direction: column;
        align-items: center;
    }
    
    .calc-steps {
        grid-template-columns: 1fr 1fr;
        gap: var(--port-space-xs);
    }
    
    .calc-step-btn {
        padding: var(--port-space-sm);
    }
    
    .step-number {
        font-size: 1rem;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
    
    .calc-options-grid {
        grid-template-columns: 1fr;
    }
    
    .calc-form-actions {
        flex-direction: column;
    }
    
    .calc-result-details {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .calc-form-card {
        padding: var(--port-space-lg);
    }
    
    .calc-steps {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .calc-step-btn {
        padding: var(--port-space-xs);
    }
    
    .step-label {
        display: none;
    }
    
    .calc-btn {
        padding: var(--port-space-md);
        font-size: 0.875rem;
    }
}

/* ============================= */
/*      AMÉLIORATIONS FOCUS      */
/* ============================= */
.calc-step-btn:focus,
.calc-option-card:focus,
.calc-toggle:focus {
    outline: none;
    box-shadow: var(--port-focus-ring);
}

/* États pour les champs conditionnels */
.palette-fields {
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.palette-fields.show {
    opacity: 1;
    max-height: 200px;
}

/* ============================= */
/*     DÉSACTIVATION MODE SOMBRE */
/* ============================= */
/* IMPORTANT: Pas de mode sombre automatique */
/* Le thème reste toujours clair pour un contraste optimal */

/* Forcer le thème clair même si le système préfère le sombre */
@media (prefers-color-scheme: dark) {
    :root {
        /* On garde TOUJOURS les couleurs claires */
        color-scheme: light;
    }
    
    /* Override forcé pour empêcher le mode sombre */
    .calc-container,
    .calc-form-card,
    .calc-input,
    .calc-select,
    .calc-option-card,
    .calc-result-card {
        background: var(--port-background) !important;
        color: var(--port-text-primary) !important;
        border-color: var(--port-border) !important;
    }
}
