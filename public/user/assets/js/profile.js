/**
 * Titre: JavaScript pour profil utilisateur
 * Chemin: /public/user/assets/js/profile.js
 * Version: 0.5 beta + build auto
 */

// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    console.log('👤 Profile JavaScript initialisé');
    
    // ==============================================
    // GESTION DES ONGLETS
    // ==============================================
    
    const navItems = document.querySelectorAll('.nav-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer active de tous
            navItems.forEach(nav => nav.classList.remove('active'));
            tabContents.forEach(tab => tab.classList.remove('active'));
            
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
                }
        
        // Échap pour fermer les modales/alertes
        if (e.key === 'Escape') {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }
        
        // Navigation entre onglets avec Tab
        if (e.key === 'Tab' && e.altKey) {
            e.preventDefault();
            const activeNav = document.querySelector('.nav-item.active');
            const allNavs = Array.from(document.querySelectorAll('.nav-item'));
            const currentIndex = allNavs.indexOf(activeNav);
            
            let nextIndex;
            if (e.shiftKey) {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : allNavs.length - 1;
            } else {
                nextIndex = currentIndex < allNavs.length - 1 ? currentIndex + 1 : 0;
            }
            
            allNavs[nextIndex].click();
        }
    });
    
    // ==============================================
    // GESTION DES THÈMES
    // ==============================================
    
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                // Prévisualisation du thème
                previewTheme(this.value);
            }
        });
    });
});

// ==============================================
// FONCTIONS UTILITAIRES
// ==============================================

/**
 * Confirmation de suppression de compte
 */
function confirmDelete() {
    const confirmText = document.getElementById('confirm_delete')?.value;
    
    if (confirmText !== 'SUPPRIMER') {
        showNotification('Veuillez taper "SUPPRIMER" pour confirmer', 'error');
        return false;
    }
    
    return confirm('⚠️ ATTENTION: Cette action est irréversible!\n\nÊtes-vous absolument certain de vouloir supprimer votre compte?\n\nToutes vos données seront définitivement perdues.');
}

/**
 * Créer un indicateur de force de mot de passe
 */
function createPasswordStrengthIndicator() {
    const container = document.createElement('div');
    container.className = 'password-strength';
    container.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill"></div>
        </div>
        <div class="strength-text">Force du mot de passe</div>
    `;
    
    return container;
}

/**
 * Mettre à jour l'indicateur de force du mot de passe
 */
function updatePasswordStrength(password, indicator) {
    const fill = indicator.querySelector('.strength-fill');
    const text = indicator.querySelector('.strength-text');
    
    if (!password) {
        fill.style.width = '0%';
        fill.className = 'strength-fill';
        text.textContent = 'Force du mot de passe';
        return;
    }
    
    let score = 0;
    let feedback = [];
    
    // Longueur
    if (password.length >= 8) score += 1;
    else feedback.push('Au moins 8 caractères');
    
    // Minuscules
    if (/[a-z]/.test(password)) score += 1;
    else feedback.push('lettres minuscules');
    
    // Majuscules
    if (/[A-Z]/.test(password)) score += 1;
    else feedback.push('lettres majuscules');
    
    // Chiffres
    if (/\d/.test(password)) score += 1;
    else feedback.push('chiffres');
    
    // Caractères spéciaux
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 1;
    else feedback.push('caractères spéciaux');
    
    const percentage = (score / 5) * 100;
    fill.style.width = percentage + '%';
    
    // Classes et textes selon le score
    fill.className = 'strength-fill';
    if (score < 2) {
        fill.classList.add('weak');
        text.textContent = 'Faible - Ajoutez: ' + feedback.slice(0, 2).join(', ');
    } else if (score < 4) {
        fill.classList.add('medium');
        text.textContent = 'Moyen - Ajoutez: ' + feedback.slice(0, 1).join(', ');
    } else {
        fill.classList.add('strong');
        text.textContent = 'Fort ✓';
    }
}

/**
 * Sauvegarde automatique des préférences
 */
function autoSavePreferences(form) {
    const formData = new FormData(form);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Vérifier si la sauvegarde a réussi
        if (html.includes('alert-success')) {
            showNotification('Préférences sauvegardées automatiquement', 'success');
        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde automatique:', error);
    });
}

/**
 * Prévisualisation du thème
 */
function previewTheme(theme) {
    const body = document.body;
    
    // Retirer les classes de thème existantes
    body.classList.remove('theme-light', 'theme-dark');
    
    // Appliquer le nouveau thème
    body.classList.add('theme-' + theme);
    
    showNotification('Aperçu du thème ' + (theme === 'dark' ? 'sombre' : 'clair'), 'info');
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-message">${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    // Styles inline pour la notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        zIndex: '9999',
        padding: '12px 16px',
        borderRadius: '8px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        maxWidth: '400px',
        opacity: '0',
        transform: 'translateX(100%)',
        transition: 'all 0.3s ease'
    });
    
    // Couleurs selon le type
    const colors = {
        success: { bg: '#f0fff4', border: '#48bb78', text: '#22543d' },
        error: { bg: '#fed7d7', border: '#e53e3e', text: '#742a2a' },
        warning: { bg: '#ffeaa7', border: '#ed8936', text: '#744210' },
        info: { bg: '#ebf8ff', border: '#3182ce', text: '#2c5282' }
    };
    
    const colorScheme = colors[type] || colors.info;
    Object.assign(notification.style, {
        backgroundColor: colorScheme.bg,
        borderLeft: `4px solid ${colorScheme.border}`,
        color: colorScheme.text
    });
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Obtenir l'icône pour les notifications
 */
function getNotificationIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

/**
 * Fonction debounce pour limiter les appels
 */
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

/**
 * Validation en temps réel des formulaires
 */
function setupFormValidation() {
    const inputs = document.querySelectorAll('.form-input');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Supprimer l'état d'erreur lors de la saisie
            this.classList.remove('error');
            const errorMsg = this.parentNode.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
        });
    });
}

/**
 * Valider un champ spécifique
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    let isValid = true;
    let message = '';
    
    // Validation selon le type
    if (required && !value) {
        isValid = false;
        message = 'Ce champ est obligatoire';
    } else if (type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Adresse email invalide';
    } else if (field.name === 'username' && value && value.length < 3) {
        isValid = false;
        message = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
    }
    
    // Affichage du résultat
    if (!isValid) {
        field.classList.add('error');
        showFieldError(field, message);
    } else {
        field.classList.remove('error');
        removeFieldError(field);
    }
    
    return isValid;
}

/**
 * Valider un email
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Afficher une erreur de champ
 */
function showFieldError(field, message) {
    removeFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: var(--profile-error);
        font-size: 0.75rem;
        margin-top: 4px;
        font-weight: 500;
    `;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Supprimer l'erreur de champ
 */
function removeFieldError(field) {
    const errorMsg = field.parentNode.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
}

// Initialiser la validation des formulaires
document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
});

// CSS pour les animations et états
const dynamicStyles = document.createElement('style');
dynamicStyles.textContent = `
    .form-input.error {
        border-color: var(--profile-error) !important;
        box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
    }
    
    .password-strength {
        margin-top: 8px;
    }
    
    .strength-bar {
        width: 100%;
        height: 4px;
        background: var(--profile-gray-200);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 4px;
    }
    
    .strength-fill {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }
    
    .strength-fill.weak {
        background: var(--profile-error);
    }
    
    .strength-fill.medium {
        background: var(--profile-warning);
    }
    
    .strength-fill.strong {
        background: var(--profile-success);
    }
    
    .strength-text {
        font-size: 0.75rem;
        color: var(--profile-gray-500);
        font-weight: 500;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        padding: 0;
        margin-left: 8px;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
    
    .theme-dark {
        filter: invert(1) hue-rotate(180deg);
    }
    
    .theme-dark img,
    .theme-dark video,
    .theme-dark iframe {
        filter: invert(1) hue-rotate(180deg);
    }
`;

document.head.appendChild(dynamicStyles);

console.log('👤 Profile JavaScript complètement chargé');, 50);
            }
        });
    });
    
    // ==============================================
    // VALIDATION MOT DE PASSE
    // ==============================================
    
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (newPasswordInput && confirmPasswordInput) {
        function validatePasswords() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                confirmPasswordInput.setCustomValidity('Les mots de passe ne correspondent pas');
                confirmPasswordInput.classList.add('error');
            } else {
                confirmPasswordInput.setCustomValidity('');
                confirmPasswordInput.classList.remove('error');
            }
        }
        
        newPasswordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
        
        // Indicateur de force du mot de passe
        if (newPasswordInput) {
            const strengthIndicator = createPasswordStrengthIndicator();
            newPasswordInput.parentNode.appendChild(strengthIndicator);
            
            newPasswordInput.addEventListener('input', function() {
                updatePasswordStrength(this.value, strengthIndicator);
            });
        }
    }
    
    // ==============================================
    // TOGGLES ET PRÉFÉRENCES
    // ==============================================
    
    const toggleInputs = document.querySelectorAll('.toggle-input');
    toggleInputs.forEach(toggle => {
        toggle.addEventListener('change', function() {
            // Animation du toggle
            const slider = this.nextElementSibling;
            if (slider && slider.classList.contains('toggle-slider')) {
                slider.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    slider.style.transform = 'scale(1)';
                }, 100);
            }
            
            // Sauvegarde automatique des préférences (optionnel)
            if (this.form && this.form.querySelector('input[name="action"][value="update_preferences"]')) {
                debounce(autoSavePreferences, 1000)(this.form);
            }
        });
    });
    
    // ==============================================
    // ANIMATIONS D'ENTRÉE
    // ==============================================
    
    const animatedElements = document.querySelectorAll('.user-card, .preference-card, .stat-card, .activity-event');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                entry.target.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    animatedElements.forEach(el => observer.observe(el));
    
    // ==============================================
    // GESTION DES FORMULAIRES
    // ==============================================
    
    const forms = document.querySelectorAll('.profile-form, .danger-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const action = this.querySelector('input[name="action"]')?.value;
            
            // Validation spéciale pour suppression de compte
            if (action === 'delete_account') {
                if (!confirmDelete()) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Indicateur de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Traitement...';
                
                // Restaurer le bouton après 3 secondes si pas de redirection
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 3000);
            }
        });
    });
    
    // ==============================================
    // RACCOURCIS CLAVIER
    // ==============================================
    
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S pour sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const activeForm = document.querySelector('.tab-content.active .profile-form');
            if (activeForm) {
                const submitBtn = activeForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                }
            }
