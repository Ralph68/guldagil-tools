// public/assets/js/theme-switcher.js - Gestion du mode sombre

class ThemeSwitcher {
    constructor() {
        this.init();
    }

    init() {
        // Récupérer le thème sauvegardé ou utiliser les préférences système
        this.currentTheme = this.getSavedTheme() || this.getSystemTheme();
        
        // Appliquer le thème
        this.applyTheme(this.currentTheme);
        
        // Créer le bouton de basculement
        this.createToggleButton();
        
        // Écouter les changements de préférences système
        this.watchSystemTheme();
        
        console.log('🎨 Theme Switcher initialisé -', this.currentTheme);
    }

    getSavedTheme() {
        try {
            return localStorage.getItem('guldagil-theme');
        } catch (e) {
            return null;
        }
    }

    getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        
        // Sauvegarder la préférence
        try {
            localStorage.setItem('guldagil-theme', theme);
        } catch (e) {
            console.warn('Impossible de sauvegarder le thème');
        }
        
        // Dispatch event pour les autres modules
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme }
        }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
        this.updateToggleButton();
        
        // Animation de transition douce
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    createToggleButton() {
        // Créer le bouton s'il n'existe pas déjà
        if (document.getElementById('theme-toggle')) return;

        const button = document.createElement('button');
        button.id = 'theme-toggle';
        button.className = 'theme-toggle-btn';
        button.setAttribute('aria-label', 'Basculer le mode sombre');
        button.setAttribute('title', 'Basculer le mode sombre');
        
        this.updateToggleButton(button);
        
        button.addEventListener('click', () => this.toggleTheme());
        
        // Ajouter le bouton dans le header s'il existe
        const header = document.querySelector('.header-actions, .admin-nav, .header-container');
        if (header) {
            header.appendChild(button);
        } else {
            // Sinon créer un container flottant
            this.createFloatingToggle(button);
        }
    }

    updateToggleButton(button = null) {
        const btn = button || document.getElementById('theme-toggle');
        if (!btn) return;

        const isDark = this.currentTheme === 'dark';
        btn.innerHTML = isDark ? '☀️' : '🌙';
        btn.setAttribute('title', isDark ? 'Mode clair' : 'Mode sombre');
        
        // Ajouter une classe pour l'état actuel
        btn.classList.toggle('dark-mode', isDark);
    }

    createFloatingToggle(button) {
        const container = document.createElement('div');
        container.className = 'floating-theme-toggle';
        container.appendChild(button);
        document.body.appendChild(container);
    }

    watchSystemTheme() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                // Seulement suivre le système si l'utilisateur n'a pas de préférence sauvegardée
                if (!this.getSavedTheme()) {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                    this.updateToggleButton();
                }
            });
        }
    }

    // Méthode publique pour forcer un thème
    setTheme(theme) {
        if (theme === 'dark' || theme === 'light') {
            this.applyTheme(theme);
            this.updateToggleButton();
        }
    }

    // Méthode publique pour obtenir le thème actuel
    getTheme() {
        return this.currentTheme;
    }
}

// CSS pour le bouton de basculement (à injecter dynamiquement)
const themeToggleCSS = `
.theme-toggle-btn {
    width: 2.5rem;
    height: 2.5rem;
    border: 1px solid var(--border-medium);
    background: var(--bg-primary);
    color: var(--text-primary);
    border-radius: var(--radius-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: var(--transition-fast);
    position: relative;
    overflow: hidden;
}

.theme-toggle-btn:hover {
    background: var(--bg-tertiary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.theme-toggle-btn:active {
    transform: translateY(0);
}

.floating-theme-toggle {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    z-index: var(--z-fixed);
}

.floating-theme-toggle .theme-toggle-btn {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    box-shadow: var(--shadow-lg);
}

/* Animation de rotation pour le changement */
.theme-toggle-btn.transitioning {
    animation: rotateIcon 0.3s ease-in-out;
}

@keyframes rotateIcon {
    0% { transform: rotate(0deg); }
    50% { transform: rotate(180deg) scale(0.8); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .floating-theme-toggle {
        bottom: 5rem; /* Éviter les contrôles mobiles */
    }
}
`;

// Injecter le CSS
function injectThemeToggleCSS() {
    if (document.getElementById('theme-toggle-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'theme-toggle-styles';
    style.textContent = themeToggleCSS;
    document.head.appendChild(style);
}

// Initialisation automatique
let themeSwitcher;

document.addEventListener('DOMContentLoaded', function() {
    injectThemeToggleCSS();
    themeSwitcher = new ThemeSwitcher();
    
    // Exposer globalement pour l'utilisation dans d'autres scripts
    window.ThemeSwitcher = themeSwitcher;
});

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeSwitcher;
}
