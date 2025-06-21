<?php
/**
 * Titre: Pied de page du module calculateur
 * Chemin: /public/calculateur/views/partials/footer.php
 * Version: 0.5 beta + build
 */

// Récupération des informations version
$version_info = $version_info ?? getVersionInfo();
$current_year = date('Y');
?>

<footer class="calculator-footer">
    <div class="calc-container">
        <div class="footer-content">
            
            <!-- Informations du module -->
            <div class="footer-section">
                <h4 class="footer-title">Calculateur de frais</h4>
                <p class="footer-description">
                    Interface progressive pour comparaison de tarifs de transport.
                    Compatible Heppner, XPO Logistics et Kuehne + Nagel.
                </p>
                <div class="footer-features">
                    <span class="feature-badge">✓ Temps réel</span>
                    <span class="feature-badge">✓ Progressive</span>
                    <span class="feature-badge">✓ Mobile friendly</span>
                </div>
            </div>
            
            <!-- Liens utiles -->
            <div class="footer-section">
                <h4 class="footer-title">Navigation</h4>
                <nav class="footer-nav">
                    <a href="../" class="footer-link">🏠 Portail principal</a>
                    <a href="../admin/" class="footer-link">⚙️ Administration</a>
                    <a href="../adr/" class="footer-link">📋 Module ADR</a>
                    <a href="?demo=1" class="footer-link">🎮 Mode démo</a>
                </nav>
            </div>
            
            <!-- Informations techniques -->
            <div class="footer-section">
                <h4 class="footer-title">Informations</h4>
                <div class="footer-meta">
                    <div class="meta-item">
                        <span class="meta-label">Version:</span>
                        <span class="meta-value"><?= htmlspecialchars($version_info['version']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Build:</span>
                        <span class="meta-value">#<?= htmlspecialchars($version_info['build']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Mise à jour:</span>
                        <span class="meta-value"><?= htmlspecialchars($version_info['formatted_date']) ?></span>
                    </div>
                    <?php if ($debug_mode ?? false): ?>
                    <div class="meta-item debug">
                        <span class="meta-label">Mode:</span>
                        <span class="meta-value">🐛 Debug</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <!-- Ligne de copyright -->
        <div class="footer-bottom">
            <div class="copyright">
                <span>© <?= $current_year ?> Guldagil</span>
                <span class="separator">•</span>
                <span>Solutions transport & logistique</span>
                <span class="separator">•</span>
                <span>Interface calculateur v<?= htmlspecialchars($version_info['version']) ?></span>
            </div>
            
            <div class="footer-actions">
                <button type="button" class="footer-btn" id="scroll-top" title="Retour en haut">
                    ↑
                </button>
            </div>
        </div>
        
    </div>
</footer>

<style>
/* ========================================
   STYLES FOOTER CALCULATEUR
======================================== */

.calculator-footer {
    background: linear-gradient(135deg, var(--calc-gray-800) 0%, var(--calc-gray-900) 100%);
    color: var(--calc-white);
    margin-top: var(--calc-space-12);
    padding: var(--calc-space-8) 0 var(--calc-space-4);
    border-top: 1px solid var(--calc-gray-700);
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: var(--calc-space-8);
    margin-bottom: var(--calc-space-6);
}

.footer-section {
    display: flex;
    flex-direction: column;
    gap: var(--calc-space-4);
}

.footer-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--calc-white);
    border-bottom: 2px solid var(--calc-primary);
    padding-bottom: var(--calc-space-2);
    width: fit-content;
}

.footer-description {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.8);
}

/* Features badges */
.footer-features {
    display: flex;
    flex-wrap: wrap;
    gap: var(--calc-space-2);
}

.feature-badge {
    padding: var(--calc-space-1) var(--calc-space-3);
    background: rgba(59, 130, 246, 0.2);
    color: var(--calc-accent);
    border-radius: var(--calc-radius);
    font-size: 0.8rem;
    font-weight: 500;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Navigation footer */
.footer-nav {
    display: flex;
    flex-direction: column;
    gap: var(--calc-space-2);
}

.footer-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--calc-transition);
    display: flex;
    align-items: center;
    gap: var(--calc-space-2);
}

.footer-link:hover {
    color: var(--calc-accent);
    transform: translateX(4px);
}

/* Métadonnées techniques */
.footer-meta {
    display: flex;
    flex-direction: column;
    gap: var(--calc-space-2);
}

.meta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--calc-space-1) 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.8rem;
}

.meta-item:last-child {
    border-bottom: none;
}

.meta-item.debug {
    background: rgba(245, 158, 11, 0.1);
    border-radius: var(--calc-radius-sm);
    padding: var(--calc-space-2);
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.meta-label {
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.meta-value {
    color: var(--calc-white);
    font-weight: 600;
}

/* Ligne de copyright */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--calc-space-4);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    gap: var(--calc-space-4);
}

.copyright {
    display: flex;
    align-items: center;
    gap: var(--calc-space-2);
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
}

.separator {
    color: rgba(255, 255, 255, 0.3);
}

.footer-actions {
    display: flex;
    gap: var(--calc-space-2);
}

.footer-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: var(--calc-white);
    cursor: pointer;
    transition: var(--calc-transition);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.footer-btn:hover {
    background: var(--calc-primary);
    border-color: var(--calc-primary);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: var(--calc-space-6);
        text-align: center;
    }
    
    .footer-title {
        margin: 0 auto;
    }
    
    .footer-features {
        justify-content: center;
    }
    
    .footer-nav {
        align-items: center;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: var(--calc-space-3);
        text-align: center;
    }
    
    .copyright {
        flex-direction: column;
        gap: var(--calc-space-1);
    }
    
    .separator {
        display: none;
    }
}

@media (max-width: 480px) {
    .calculator-footer {
        padding: var(--calc-space-6) 0 var(--calc-space-3);
    }
    
    .footer-content {
        gap: var(--calc-space-4);
    }
    
    .footer-title {
        font-size: 1rem;
    }
    
    .footer-description {
        font-size: 0.85rem;
    }
    
    .meta-item {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--calc-space-1);
    }
}

/* Animation d'apparition */
.calculator-footer {
    animation: calc-fade-in 0.5s ease-out;
}

/* États interactifs */
.footer-section:hover .footer-title {
    color: var(--calc-accent);
}

/* Indicateur de performance */
.performance-indicator {
    position: fixed;
    bottom: var(--calc-space-2);
    left: var(--calc-space-2);
    background: rgba(0, 0, 0, 0.8);
    color: var(--calc-white);
    padding: var(--calc-space-1) var(--calc-space-2);
    border-radius: var(--calc-radius-sm);
    font-size: 0.7rem;
    z-index: var(--calc-z-tooltip);
    opacity: 0;
    transition: var(--calc-transition);
}

.performance-indicator.visible {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bouton retour en haut
    const scrollTopBtn = document.getElementById('scroll-top');
    
    if (scrollTopBtn) {
        // Afficher/masquer selon la position
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                scrollTopBtn.style.opacity = '1';
                scrollTopBtn.style.pointerEvents = 'auto';
            } else {
                scrollTopBtn.style.opacity = '0';
                scrollTopBtn.style.pointerEvents = 'none';
            }
        });
        
        // Action de scroll
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Indicateur de performance (si mode debug)
    if (<?= json_encode($debug_mode ?? false) ?> && window.performance) {
        const perfIndicator = document.createElement('div');
        perfIndicator.className = 'performance-indicator';
        document.body.appendChild(perfIndicator);
        
        function updatePerformance() {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            const domTime = timing.domContentLoadedEventEnd - timing.navigationStart;
            
            perfIndicator.innerHTML = `
                DOM: ${domTime}ms | Total: ${loadTime}ms
            `;
            
            // Afficher pendant 3 secondes après le chargement
            setTimeout(() => {
                perfIndicator.classList.add('visible');
                setTimeout(() => {
                    perfIndicator.classList.remove('visible');
                }, 3000);
            }, 1000);
        }
        
        if (window.performance.timing.loadEventEnd > 0) {
            updatePerformance();
        } else {
            window.addEventListener('load', updatePerformance);
        }
    }
    
    // Animation au scroll pour les éléments du footer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'calc-fade-in 0.6s ease-out forwards';
            }
        });
    }, observerOptions);
    
    // Observer les sections du footer
    document.querySelectorAll('.footer-section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        observer.observe(section);
    });
    
    // Gestion des liens externes
    document.querySelectorAll('.footer-link[href^="http"]').forEach(link => {
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
    });
    
    // Easter egg - Konami code pour afficher des stats avancées
    let konamiCode = '';
    const konamiSequence = 'ArrowUpArrowUpArrowDownArrowDownArrowLeftArrowRightArrowLeftArrowRightKeyBKeyA';
    
    document.addEventListener('keydown', function(e) {
        konamiCode += e.code;
        
        if (konamiCode.length > konamiSequence.length) {
            konamiCode = konamiCode.slice(-konamiSequence.length);
        }
        
        if (konamiCode === konamiSequence && window.apiService) {
            const stats = window.apiService.getStats();
            alert(`🎮 Easter Egg!\n\nStatistiques avancées:\n${JSON.stringify(stats, null, 2)}`);
            konamiCode = '';
        }
    });
});

// Fonction pour afficher un toast de feedback
function showFooterToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `footer-toast ${type}`;
    toast.textContent = message;
    
    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '20px',
        right: '20px',
        background: type === 'success' ? 'var(--calc-success)' : 
                   type === 'error' ? 'var(--calc-error)' : 'var(--calc-primary)',
        color: 'white',
        padding: 'var(--calc-space-3) var(--calc-space-4)',
        borderRadius: 'var(--calc-radius)',
        boxShadow: 'var(--calc-shadow-lg)',
        zIndex: 'var(--calc-z-tooltip)',
        animation: 'calc-fade-in 0.3s ease-out',
        maxWidth: '300px'
    });
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'calc-fade-in 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Exposer la fonction globalement
window.showFooterToast = showFooterToast;
</script>
