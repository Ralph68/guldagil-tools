<?php
// public/admin/modals/confirmation-modal.php
// Modal de confirmation réutilisable pour l'administration
?>

<!-- Modal de confirmation générique -->
<div id="confirmation-modal" class="modal" style="display: none;">
    <div class="modal-content confirmation-content">
        <div class="modal-header">
            <h3 id="confirmation-title">
                <span id="confirmation-icon">❓</span>
                <span id="confirmation-title-text">Confirmation</span>
            </h3>
            <button class="modal-close" onclick="closeConfirmationModal()" title="Fermer">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="confirmation-message">
                <div id="confirmation-message-text">
                    Êtes-vous sûr de vouloir effectuer cette action ?
                </div>
                
                <!-- Zone de détails (optionnelle) -->
                <div id="confirmation-details" class="confirmation-details" style="display: none;">
                    <div class="details-content"></div>
                </div>
                
                <!-- Zone d'avertissement (optionnelle) -->
                <div id="confirmation-warning" class="confirmation-warning" style="display: none;">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-text"></div>
                </div>
                
                <!-- Zone d'information supplémentaire (optionnelle) -->
                <div id="confirmation-info" class="confirmation-info" style="display: none;">
                    <div class="info-content"></div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" 
                    class="btn btn-secondary" 
                    onclick="closeConfirmationModal()"
                    id="confirmation-cancel-btn">
                <span>❌</span>
                Annuler
            </button>
            <button type="button" 
                    class="btn btn-primary" 
                    onclick="executeConfirmationAction()"
                    id="confirmation-confirm-btn">
                <span>✅</span>
                Confirmer
            </button>
        </div>
    </div>
</div>

<!-- Styles spécifiques à la modal de confirmation -->
<style>
.confirmation-content {
    max-width: 500px !important;
    width: 90% !important;
}

.confirmation-message {
    text-align: center;
    padding: 1rem 0;
}

#confirmation-message-text {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.confirmation-details {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    margin: 1rem 0;
    text-align: left;
}

.confirmation-details .details-content {
    font-size: 0.9rem;
    color: #666;
}

.confirmation-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 1rem;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-align: left;
}

.confirmation-warning .warning-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.confirmation-warning .warning-text {
    color: #856404;
    font-weight: 500;
}

.confirmation-info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 6px;
    padding: 1rem;
    margin: 1rem 0;
    text-align: left;
}

.confirmation-info .info-content {
    color: #0c5460;
    font-size: 0.9rem;
}

/* Styles pour différents types de confirmation */
.modal.danger #confirmation-confirm-btn {
    background: var(--error-color) !important;
}

.modal.danger #confirmation-confirm-btn:hover {
    background: #d32f2f !important;
}

.modal.warning #confirmation-confirm-btn {
    background: var(--warning-color) !important;
}

.modal.warning #confirmation-confirm-btn:hover {
    background: #f57c00 !important;
}

.modal.success #confirmation-confirm-btn {
    background: var(--success-color) !important;
}

.modal.success #confirmation-confirm-btn:hover {
    background: #45a049 !important;
}

/* Animation pour attirer l'attention */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.modal.danger .confirmation-content {
    animation: shake 0.5s ease-in-out;
}

/* Responsive */
@media (max-width: 480px) {
    .confirmation-content {
        width: 95% !important;
        margin: 1rem !important;
    }
    
    #confirmation-message-text {
        font-size: 1rem;
    }
    
    .modal-footer {
        flex-direction: column-reverse;
        gap: 0.5rem;
    }
    
    .modal-footer .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Variables globales pour la gestion de la confirmation
let confirmationCallback = null;
let confirmationData = null;

/**
 * Affiche une modal de confirmation
 * @param {Object} options - Options de configuration
 * @param {string} options.title - Titre de la modal
 * @param {string} options.message - Message principal
 * @param {string} options.type - Type: 'danger', 'warning', 'success', 'info'
 * @param {string} options.confirmText - Texte du bouton de confirmation
 * @param {string} options.cancelText - Texte du bouton d'annulation
 * @param {string} options.details - Détails supplémentaires (optionnel)
 * @param {string} options.warning - Message d'avertissement (optionnel)
 * @param {string} options.info - Information supplémentaire (optionnel)
 * @param {Function} options.onConfirm - Fonction à exécuter lors de la confirmation
 * @param {Object} options.data - Données à passer à la fonction de confirmation
 */
function showConfirmation(options = {}) {
    const modal = document.getElementById('confirmation-modal');
    const titleIcon = document.getElementById('confirmation-icon');
    const titleText = document.getElementById('confirmation-title-text');
    const messageText = document.getElementById('confirmation-message-text');
    const confirmBtn = document.getElementById('confirmation-confirm-btn');
    const cancelBtn = document.getElementById('confirmation-cancel-btn');
    const detailsDiv = document.getElementById('confirmation-details');
    const warningDiv = document.getElementById('confirmation-warning');
    const infoDiv = document.getElementById('confirmation-info');
    
    if (!modal) {
        console.error('Modal de confirmation non trouvée');
        return;
    }
    
    // Configuration par défaut
    const config = {
        title: 'Confirmation',
        message: 'Êtes-vous sûr de vouloir effectuer cette action ?',
        type: 'info',
        confirmText: 'Confirmer',
        cancelText: 'Annuler',
        ...options
    };
    
    // Icônes selon le type
    const typeIcons = {
        'danger': '⚠️',
        'warning': '⚠️',
        'success': '✅',
        'info': '❓',
        'delete': '🗑️'
    };
    
    // Textes de boutons selon le type
    const typeButtonTexts = {
        'danger': 'Supprimer',
        'warning': 'Continuer',
        'success': 'Confirmer',
        'info': 'Confirmer',
        'delete': 'Supprimer'
    };
    
    // Mettre à jour le contenu
    titleIcon.textContent = typeIcons[config.type] || typeIcons.info;
    titleText.textContent = config.title;
    messageText.textContent = config.message;
    
    // Mettre à jour les boutons
    confirmBtn.innerHTML = `<span>${typeIcons[config.type] || '✅'}</span> ${config.confirmText || typeButtonTexts[config.type] || 'Confirmer'}`;
    cancelBtn.innerHTML = `<span>❌</span> ${config.cancelText}`;
    
    // Gérer les sections optionnelles
    if (config.details) {
        detailsDiv.querySelector('.details-content').textContent = config.details;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
    
    if (config.warning) {
        warningDiv.querySelector('.warning-text').textContent = config.warning;
        warningDiv.style.display = 'flex';
    } else {
        warningDiv.style.display = 'none';
    }
    
    if (config.info) {
        infoDiv.querySelector('.info-content').textContent = config.info;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
    
    // Appliquer le style selon le type
    modal.className = `modal ${config.type}`;
    
    // Sauvegarder le callback et les données
    confirmationCallback = config.onConfirm;
    confirmationData = config.data;
    
    // Afficher la modal
    modal.style.display = 'flex';
    modal.classList.add('active');
    
    // Focus sur le bouton d'annulation par défaut (sécurité)
    setTimeout(() => {
        if (config.type === 'danger' || config.type === 'delete') {
            cancelBtn.focus();
        } else {
            confirmBtn.focus();
        }
    }, 100);
    
    console.log('Modal de confirmation affichée:', config.type, config.message);
}

/**
 * Ferme la modal de confirmation
 */
function closeConfirmationModal() {
    const modal = document.getElementById('confirmation-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
        
        // Nettoyer les callbacks
        confirmationCallback = null;
        confirmationData = null;
    }
}

/**
 * Exécute l'action de confirmation
 */
function executeConfirmationAction() {
    if (confirmationCallback && typeof confirmationCallback === 'function') {
        try {
            confirmationCallback(confirmationData);
        } catch (error) {
            console.error('Erreur lors de l\'exécution de la confirmation:', error);
            if (typeof showAlert === 'function') {
                showAlert('error', 'Erreur lors de l\'exécution de l\'action');
            }
        }
    }
    
    closeConfirmationModal();
}

// Fermer la modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.id === 'confirmation-modal') {
        closeConfirmationModal();
    }
});

// Gérer la touche Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('confirmation-modal');
        if (modal && modal.style.display === 'flex') {
            closeConfirmationModal();
        }
    }
    
    // Gérer Entrée pour confirmer
    if (e.key === 'Enter') {
        const modal = document.getElementById('confirmation-modal');
        if (modal && modal.style.display === 'flex') {
            const confirmBtn = document.getElementById('confirmation-confirm-btn');
            if (document.activeElement === confirmBtn) {
                executeConfirmationAction();
            }
        }
    }
});

// =============================================================================
// FONCTIONS D'AIDE POUR DES CAS D'USAGE COURANTS
// =============================================================================

/**
 * Confirmation de suppression
 */
function confirmDelete(itemName, callback, data = null) {
    showConfirmation({
        title: 'Supprimer un élément',
        message: `Êtes-vous sûr de vouloir supprimer "${itemName}" ?`,
        type: 'delete',
        warning: 'Cette action est irréversible !',
        confirmText: 'Supprimer définitivement',
        cancelText: 'Annuler',
        onConfirm: callback,
        data: data
    });
}

/**
 * Confirmation de modification importante
 */
function confirmUpdate(itemName, callback, data = null) {
    showConfirmation({
        title: 'Modifier un élément',
        message: `Confirmer la modification de "${itemName}" ?`,
        type: 'warning',
        info: 'Les modifications seront sauvegardées immédiatement.',
        confirmText: 'Sauvegarder',
        cancelText: 'Annuler',
        onConfirm: callback,
        data: data
    });
}

/**
 * Confirmation d'action critique
 */
function confirmCriticalAction(actionName, callback, data = null) {
    showConfirmation({
        title: 'Action critique',
        message: `Vous êtes sur le point d'effectuer : ${actionName}`,
        type: 'danger',
        warning: 'Cette action peut avoir des conséquences importantes !',
        details: 'Vérifiez que vous avez bien compris les implications de cette action.',
        confirmText: 'Je comprends, continuer',
        cancelText: 'Annuler',
        onConfirm: callback,
        data: data
    });
}

/**
 * Confirmation de déconnexion
 */
function confirmLogout(callback) {
    showConfirmation({
        title: 'Déconnexion',
        message: 'Voulez-vous vraiment vous déconnecter ?',
        type: 'info',
        info: 'Vous devrez vous reconnecter pour accéder à l\'administration.',
        confirmText: 'Se déconnecter',
        cancelText: 'Rester connecté',
        onConfirm: callback
    });
}

// Exposer les fonctions globalement
window.showConfirmation = showConfirmation;
window.closeConfirmationModal = closeConfirmationModal;
window.executeConfirmationAction = executeConfirmationAction;
window.confirmDelete = confirmDelete;
window.confirmUpdate = confirmUpdate;
window.confirmCriticalAction = confirmCriticalAction;
window.confirmLogout = confirmLogout;

console.log('✅ Modal de confirmation chargée et prête');
</script>
