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
        
        console.log('🎨 Theme Switcher initialisé -', this.currentTheme
