/**
 * Titre: Module interface utilisateur - Calculateur
 * Chemin: /public/assets/js/modules/calculateur/ui.js
 * Version: 0.5 beta + build
 * 
 * Gestion de l'interface utilisateur, notifications et interactions
 * Dépendance: calculateur.js, utils.js
 */

// ========================================
// MODULE INTERFACE UTILISATEUR
// ========================================

window.Calculateur = window.Calculateur || {};

Calculateur.UI = {
    
    /**
     * Configuration UI
     */
    config: {
        notificationDuration: 4000,
        animationDuration: 300,
        scrollOffset: 100
    },
    
    /**
     * Container pour les notifications
     */
    notificationContainer: null,
    
    /**
     * Initialisation du module UI
     */
    init() {
        this.createNotificationContainer();
        this.setupKeyboardShortcuts();
        this.setupTooltips();
        
        if (Calculateur.Config && Calculateur.Config.DEBUG) {
            console.log('🎨 Module UI initialisé');
        }
    },
    
    /**
     * Création du container de notifications
     */
    createNotificationContainer() {
        if (document.getElementById('calculateur-notifications')) return;
        
        this.notificationContainer = Calculateur.Utils.dom.create('div', {
            id: 'calculateur-notifications',
            class: 'calculateur-notifications',
            style: {
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: '10000',
                maxWidth:
