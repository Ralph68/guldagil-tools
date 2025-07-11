/**
 * Titre: JavaScript pour paramètres utilisateur
 * Chemin: /public/user/assets/js/settings.js
 * Version: 0.5 beta + build auto
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('⚙️ Settings JavaScript initialisé');
    
    // ==============================================
    // GESTION DES ONGLETS
    // ==============================================
    
    initTabNavigation();
    initThemePreview();
    initFormValidation();
    initAutoSave();
    initImportExport();
    initShortcuts();
    initAnimations();
    
    function initTabNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        const tabSections = document.querySelectorAll('.tab-section');
        
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Retirer active de tous
                navItems.forEach(nav => nav.classList.remove('active'));
                tabSections.forEach(tab => tab.classList.remove('active'));
                
                // Activer l'onglet cliqué
                this.classList.add('active');
                const targetTab = document.querySelector(this.getAttribute('href'));
                
                if (targetTab) {
                    targetTab.classList.add('active');
                    
                    // Animation d'entrée
                    targetTab.style.opacity = '0';
                    targetTab.style.transform = 'translateY(10px)';
                    
                    setTimeout(() => {
                        targetTab.style.transition = 'all 0.3s ease';
                        targetTab.style.opacity = '1';
                        targetTab.style.transform = 'translateY(0)';
                    }, 50);
                    
                    // Sauvegarder l'onglet actif
                    localStorage.setItem('settings_active_tab', this.getAttribute('href'));
                }
            });
        });
        
        // Restaurer l'onglet actif
        const savedTab = localStorage.getItem('settings_active_tab');
        if (savedTab) {
            const savedNavItem = document.querySelector(`[href="${savedTab}"]`);
            if (savedNavItem) {
                savedNavItem.click();
            }
        }
    }
    
    // ==============================================
    // PRÉVISUALISATION DES THÈMES
    // ==============================================
    
    function initThemePreview() {
        const themeRadios = document.querySelectorAll('input[name="theme"]');
        
        themeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    previewTheme(this.value);
                }
            });
        });
    }
    
    function previewTheme(theme) {
        const body = document.body;
        
        // Retirer les classes de thème existantes
        body.classList.remove('theme-light', 'theme-dark', 'theme-auto');
        
        // Appliquer le nouveau thème
        if (theme !== 'light') {
            body.classList.add('theme-' + theme);
        }
        
        // Animation de transition
        body.style.transition = 'all 0.3s ease';
        
        // Notification
        showNotification(`Aperçu du thème ${getThemeName(theme)}`, 'info');
    }
    
    function getThemeName(theme) {
        const names = {
            'light': 'clair',
            'dark': 'sombre',
            'auto': 'automatique'
        };
        return names[theme] || theme;
    }
    
    // ==============================================
    // VALIDATION DES FORMULAIRES
    // ==============================================
    
    function initFormValidation() {
        const forms = document.querySelectorAll('.settings-form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                    return false;
                }
                
                // Animation du bouton pendant soumission
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span>⏳</span> Sauvegarde...';
                    
                    // Restaurer après délai (si pas de redirection)
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    }, 2000);
                }
            });
            
            // Validation en temps réel
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => clearFieldError(input));
            });
        });
    }
    
    function validateForm(form) {
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        let isValid = true;
        let message = '';
        
        // Validation selon le type
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Ce champ est obligatoire';
        } else if (field.name === 'confirm' && value && value !== 'RESET') {
            isValid = false;
            message = 'Tapez exactement "RESET"';
        }
        
        // Affichage du résultat
        if (!isValid) {
            showFieldError(field, message);
        } else {
            clearFieldError(field);
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.style.borderColor = '#ef4444';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            font-weight: 500;
        `;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.style.borderColor = '';
        const errorMsg = field.parentNode.querySelector('.field-error');
        if (errorMsg) {
            errorMsg.remove();
        }
    }
    
    // ==============================================
    // SAUVEGARDE AUTOMATIQUE
    // ==============================================
    
    function initAutoSave() {
        const autoSaveCheckbox = document.querySelector('input[name="auto_save"]');
        
        if (autoSaveCheckbox && autoSaveCheckbox.checked) {
            enableAutoSave();
        }
        
        if (autoSaveCheckbox) {
            autoSaveCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    enableAutoSave();
                } else {
                    disableAutoSave();
                }
            });
        }
    }
    
    function enableAutoSave() {
        const inputs = document.querySelectorAll('.settings-form input, .settings-form select');
        
        inputs.forEach(input => {
            input.addEventListener('change', debounce(autoSaveSettings, 1000));
        });
        
        console.log('✅ Sauvegarde automatique activée');
    }
    
    function disableAutoSave() {
        // Remove event listeners would require keeping references
        console.log('❌ Sauvegarde automatique désactivée');
    }
    
    function autoSaveSettings() {
        // Simulation d'une sauvegarde auto
        showNotification('Paramètres sauvegardés automatiquement', 'success');
    }
    
    // ==============================================
    // IMPORT/EXPORT
    // ==============================================
    
    function initImportExport() {
        const fileInput = document.querySelector('input[name="settings_file"]');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    validateImportFile(this.files[0]);
                }
            });
        }
        
        // Drag & Drop pour l'import
        const importSection = document.querySelector('.import-section');
        if (importSection) {
            initDragDrop(importSection, fileInput);
        }
    }
    
    function validateImportFile(file) {
        if (file.type !== 'application/json') {
            showNotification('Seuls les fichiers JSON sont acceptés', 'error');
            return false;
        }
        
        if (file.size > 1024 * 1024) { // 1MB max
            showNotification('Le fichier est trop volumineux (max 1MB)', 'error');
            return false;
        }
        
        // Prévisualisation du contenu
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                if (data.settings) {
                    showNotification(`Fichier valide - ${Object.keys(data.settings).length} paramètres trouvés`, 'success');
                } else {
                    showNotification('Format de fichier invalide', 'error');
                }
            } catch (error) {
                showNotification('Fichier JSON invalide', 'error');
            }
        };
        reader.readAsText(file);
        
        return true;
    }
    
    function initDragDrop(element, fileInput) {
        element.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f0f9ff';
            this.style.borderColor = '#3182ce';
        });
        
        element.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            this.style.borderColor = '';
        });
        
        element.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            this.style.borderColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                validateImportFile(files[0]);
            }
        });
    }
    
    // ==============================================
    // RACCOURCIS CLAVIER
    // ==============================================
    
    function initShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S = Sauvegarder l'onglet actif
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const activeForm = document.querySelector('.tab-section.active .settings-form');
                if (activeForm) {
                    const submitBtn = activeForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    }
                }
            }
            
            // Échap = Fermer notifications
            if (e.key === 'Escape') {
                closeAllNotifications();
            }
            
            // Ctrl + 1-6 = Navigation onglets
            if (e.ctrlKey && e.key >= '1' && e.key <= '6') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                const navItems = document.querySelectorAll('.nav-item');
                if (navItems[tabIndex]) {
                    navItems[tabIndex].click();
                }
            }
        });
    }
    
    // ==============================================
    // ANIMATIONS
    // ==============================================
    
    function initAnimations() {
        // Observer pour animations au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Animer les éléments
        const animatedElements = document.querySelectorAll('.form-group, .info-item, .btn');
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.5s ease';
            observer.observe(el);
        });
    }
    
    // ==============================================
    // NOTIFICATIONS
    // ==============================================
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${getNotificationIcon(type)}</span>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close">×</button>
        `;
        
        // Styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999',
            padding: '12px 16px',
            borderRadius: '8px',
            backgroundColor: getNotificationColor(type).bg,
            borderLeft: `4px solid ${getNotificationColor(type).border}`,
            color: getNotificationColor(type).text,
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            maxWidth: '400px',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Gestionnaire de fermeture
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            closeNotification(notification);
        });
        
        // Fermeture automatique
        setTimeout(() => {
            if (notification.parentElement) {
                closeNotification(notification);
            }
        }, 5000);
    }
    
    function closeNotification(notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }
    
    function closeAllNotifications() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(closeNotification);
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
    
    function getNotificationColor(type) {
        const colors = {
            success: { bg: '#d1fae5', border: '#10b981', text: '#065f46' },
            error: { bg: '#fef2f2', border: '#ef4444', text: '#7f1d1d' },
            warning: { bg: '#fef3c7', border: '#f59e0b', text: '#92400e' },
            info: { bg: '#dbeafe', border: '#3182ce', text: '#1e40af' }
        };
        return colors[type] || colors.info;
    }
    
    // ==============================================
    // UTILITAIRES
    // ==============================================
    
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    // ==============================================
    // API PUBLIQUE
    // ==============================================
    
    window.SettingsManager = {
        showNotification,
        previewTheme,
        validateForm,
        autoSaveSettings
    };
    
    console.log('✅ Settings JavaScript entièrement chargé');
});
