/**
 * Titre: JavaScript Module Admin - Fonctionnalités complètes
 * Chemin: /public/admin/assets/js/admin.js
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION GLOBALE
// =====================================
const AdminConfig = {
    apiBaseUrl: '/admin/api/',
    refreshInterval: 30000, // 30 secondes
    animationDuration: 300,
    version: '0.5 beta',
    debug: true
};

// =====================================
// UTILITAIRES
// =====================================
class AdminUtils {
    static log(message, type = 'info') {
        if (AdminConfig.debug) {
            console.log(`[Admin ${type.toUpperCase()}] ${message}`);
        }
    }

    static formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    static timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'À l\'instant';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' min';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' h';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' j';
        return Math.floor(diffInSeconds / 2592000) + ' mois';
    }

    static showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `admin-notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                ${this.getNotificationIcon(type)}
                <span>${message}</span>
            </div>
            <button class="notification-close">✕</button>
        `;

        // Insérer dans le DOM
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }

        container.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Fermeture automatique
        const autoClose = setTimeout(() => {
            this.closeNotification(notification);
        }, duration);

        // Fermeture manuelle
        notification.querySelector('.notification-close').addEventListener('click', () => {
            clearTimeout(autoClose);
            this.closeNotification(notification);
        });
    }

    static getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    static closeNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, AdminConfig.animationDuration);
    }

    static async apiCall(endpoint, options = {}) {
        const url = AdminConfig.apiBaseUrl + endpoint;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            this.log(`API Error: ${error.message}`, 'error');
            throw error;
        }
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// =====================================
// GESTIONNAIRE PRINCIPAL
// =====================================
class AdminDashboard {
    constructor() {
        this.initialized = false;
        this.refreshTimer = null;
        this.currentUser = null;
        this.systemStats = {};
    }

    async init() {
        if (this.initialized) return;

        AdminUtils.log('Initialisation du dashboard admin...');

        try {
            await this.loadSystemInfo();
            this.setupEventListeners();
            this.setupAnimations();
            this.startAutoRefresh();
            
            this.initialized = true;
            AdminUtils.log('Dashboard admin initialisé avec succès');
            
        } catch (error) {
            AdminUtils.log(`Erreur d'initialisation: ${error.message}`, 'error');
            AdminUtils.showNotification('Erreur lors de l\'initialisation du dashboard', 'error');
        }
    }

    async loadSystemInfo() {
        try {
            // Charger les stats système (si API disponible)
            const stats = await AdminUtils.apiCall('system.php?action=stats');
            this.systemStats = stats.data || {};
            this.updateStatsDisplay();
        } catch (error) {
            AdminUtils.log('Impossible de charger les stats système', 'warning');
        }
    }

    updateStatsDisplay() {
        // Mettre à jour les badges de statistiques
        const statElements = {
            tables: document.querySelector('.stat-badge .stat-number'),
            users: document.querySelectorAll('.stat-badge .stat-number')[1],
            sessions: document.querySelectorAll('.stat-badge .stat-number')[2],
            modules: document.querySelectorAll('.stat-badge .stat-number')[3]
        };

        if (this.systemStats.tables && statElements.tables) {
            statElements.tables.textContent = this.systemStats.tables;
        }
        // Mettre à jour autres stats...
    }

    setupEventListeners() {
        // Navigation et interactions
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        document.addEventListener('keydown', this.handleKeyboard.bind(this));

        // Recherche globale
        const searchInput = document.querySelector('#globalSearch');
        if (searchInput) {
            searchInput.addEventListener('input', 
                AdminUtils.debounce(this.handleGlobalSearch.bind(this), 300)
            );
        }

        // Boutons d'action rapide
        const quickActions = document.querySelectorAll('.quick-action');
        quickActions.forEach(action => {
            action.addEventListener('click', this.handleQuickAction.bind(this));
        });

        // Gestion des modales
        this.setupModalHandlers();
    }

    handleGlobalClick(event) {
        const target = event.target;

        // Fermer les dropdowns ouverts
        if (!target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }

        // Gestion des boutons avec confirmation
        if (target.classList.contains('btn-danger') || target.dataset.confirm) {
            event.preventDefault();
            const message = target.dataset.confirm || 'Êtes-vous sûr de vouloir continuer ?';
            if (!confirm(message)) {
                return false;
            }
        }
    }

    handleKeyboard(event) {
        // Raccourcis clavier globaux
        if (event.ctrlKey || event.metaKey) {
            switch (event.key) {
                case 's':
                    event.preventDefault();
                    this.saveCurrentForm();
                    break;
                case 'r':
                    event.preventDefault();
                    this.refreshCurrentView();
                    break;
                case '/':
                    event.preventDefault();
                    this.focusGlobalSearch();
                    break;
            }
        }

        // Échap pour fermer les modales
        if (event.key === 'Escape') {
            this.closeActiveModals();
        }
    }

    handleGlobalSearch(event) {
        const query = event.target.value.toLowerCase();
        AdminUtils.log(`Recherche globale: ${query}`);

        // Recherche dans les éléments visibles
        const searchableElements = document.querySelectorAll('[data-searchable]');
        searchableElements.forEach(element => {
            const text = element.textContent.toLowerCase();
            const matches = text.includes(query);
            element.style.display = matches || query === '' ? '' : 'none';
        });
    }

    handleQuickAction(event) {
        event.preventDefault();
        const action = event.currentTarget;
        const url = action.href;
        const title = action.querySelector('.action-content h3')?.textContent;

        AdminUtils.log(`Action rapide: ${title}`);
        
        // Vérifier si la page existe
        const status = action.querySelector('.action-status');
        if (status && status.textContent === 'À créer') {
            AdminUtils.showNotification(`La page "${title}" n'est pas encore disponible`, 'warning');
            return;
        }

        // Navigation normale
        window.location.href = url;
    }

    setupModalHandlers() {
        // Fermeture des modales par clic sur l'overlay
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                this.closeModal(event.target);
            }
        });

        // Boutons de fermeture
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', (event) => {
                const modal = event.target.closest('.modal');
                if (modal) this.closeModal(modal);
            });
        });
    }

    setupAnimations() {
        // Animation des cartes au chargement
        const animateElements = document.querySelectorAll(
            '.quick-action, .status-card, .module-card, .activity-item'
        );

        animateElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.5s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Effet parallax léger sur les cartes
        this.setupParallaxEffects();
    }

    setupParallaxEffects() {
        const cards = document.querySelectorAll('.quick-action, .status-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.refreshTimer = setInterval(() => {
            this.refreshSystemStats();
        }, AdminConfig.refreshInterval);

        AdminUtils.log(`Auto-refresh activé (${AdminConfig.refreshInterval/1000}s)`);
    }

    async refreshSystemStats() {
        try {
            await this.loadSystemInfo();
            AdminUtils.log('Stats système rafraîchies');
        } catch (error) {
            AdminUtils.log('Erreur lors du rafraîchissement des stats', 'warning');
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, AdminConfig.animationDuration);
        }
    }

    closeActiveModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            this.closeModal(modal);
        });
    }

    saveCurrentForm() {
        const activeForm = document.querySelector('form:focus-within');
        if (activeForm) {
            AdminUtils.log('Sauvegarde du formulaire actif');
            AdminUtils.showNotification('Formulaire sauvegardé', 'success');
            // Logique de sauvegarde spécifique selon le contexte
        }
    }

    refreshCurrentView() {
        AdminUtils.log('Rafraîchissement de la vue');
        location.reload();
    }

    focusGlobalSearch() {
        const searchInput = document.querySelector('#globalSearch');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
}

// =====================================
// GESTIONNAIRE UTILISATEURS
// =====================================
class AdminUsers {
    constructor() {
        this.currentUserId = null;
        this.users = [];
    }

    async loadUsers() {
        try {
            const response = await AdminUtils.apiCall('users.php?action=list');
            this.users = response.data || [];
            this.renderUsersTable();
            return this.users;
        } catch (error) {
            AdminUtils.showNotification('Erreur lors du chargement des utilisateurs', 'error');
            throw error;
        }
    }

    renderUsersTable() {
        const tbody = document.querySelector('.admin-table tbody');
        if (!tbody || !this.users.length) return;

        tbody.innerHTML = this.users.map(user => `
            <tr data-user-id="${user.id}">
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            ${user.username.substring(0, 2).toUpperCase()}
                        </div>
                        <div class="user-details">
                            <strong>${AdminUtils.escape(user.username)}</strong>
                            <small>ID: ${user.id}</small>
                        </div>
                    </div>
                </td>
                <td>${AdminUtils.escape(user.email)}</td>
                <td>
                    <span class="role-badge role-${user.role}">
                        ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${user.status}">
                        ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                    </span>
                </td>
                <td>
                    ${user.last_seen ? 
                        `<span title="${user.last_seen}">${AdminUtils.timeAgo(user.last_seen)}</span>` : 
                        '<span class="text-muted">Jamais connecté</span>'
                    }
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" onclick="adminUsers.editUser(${user.id})" title="Modifier">
                            ✏️
                        </button>
                        <button class="btn-icon" onclick="adminUsers.toggleUserStatus(${user.id})" title="Activer/Désactiver">
                            ${user.status === 'active' ? '🔒' : '🔓'}
                        </button>
                        <button class="btn-icon danger" onclick="adminUsers.deleteUser(${user.id})" title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    createUser() {
        this.currentUserId = null;
        this.openUserModal('Nouvel utilisateur');
        this.resetUserForm();
    }

    async editUser(userId) {
        this.currentUserId = userId;
        this.openUserModal('Modifier l\'utilisateur');
        
        try {
            const response = await AdminUtils.apiCall(`users.php?action=get&id=${userId}`);
            this.fillUserForm(response.data);
        } catch (error) {
            AdminUtils.showNotification('Erreur lors du chargement des données utilisateur', 'error');
        }
    }

    openUserModal(title) {
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        
        if (modalTitle) modalTitle.textContent = title;
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }
    }

    closeUserModal() {
        const modal = document.getElementById('userModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }
        this.currentUserId = null;
    }

    resetUserForm() {
        const form = document.getElementById('userForm');
        if (form) form.reset();
    }

    fillUserForm(userData) {
        const fields = ['username', 'email', 'role', 'status'];
        fields.forEach(field => {
            const input = document.getElementById(field);
            if (input && userData[field]) {
                input.value = userData[field];
            }
        });
    }

    async saveUser() {
        const form = document.getElementById('userForm');
        if (!form) return;

        const formData = new FormData(form);
        
        if (this.currentUserId) {
            formData.append('user_id', this.currentUserId);
        }

        try {
            const response = await AdminUtils.apiCall('users.php', {
                method: 'POST',
                body: formData
            });

            AdminUtils.showNotification(response.message, 'success');
            this.closeUserModal();
            await this.loadUsers();
            
        } catch (error) {
            AdminUtils.showNotification('Erreur: ' + error.message, 'error');
        }
    }

    async toggleUserStatus(userId) {
        if (!confirm('Voulez-vous changer le statut de cet utilisateur ?')) return;

        try {
            const response = await AdminUtils.apiCall('users.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'toggle_status',
                    user_id: userId
                })
            });

            AdminUtils.showNotification(response.message, 'success');
            await this.loadUsers();
            
        } catch (error) {
            AdminUtils.showNotification('Erreur: ' + error.message, 'error');
        }
    }

    async deleteUser(userId) {
        if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) return;

        try {
            const response = await AdminUtils.apiCall('users.php', {
                method: 'DELETE',
                body: JSON.stringify({ user_id: userId })
            });

            AdminUtils.showNotification(response.message, 'success');
            await this.loadUsers();
            
        } catch (error) {
            AdminUtils.showNotification('Erreur: ' + error.message, 'error');
        }
    }

    exportUsers() {
        window.open('/admin/api/users.php?action=export&format=csv', '_blank');
        AdminUtils.showNotification('Export des utilisateurs en cours...', 'info');
    }

    setupSearch() {
        const searchInput = document.getElementById('userSearch');
        const roleFilter = document.getElementById('roleFilter');

        if (searchInput) {
            searchInput.addEventListener('input', AdminUtils.debounce(() => {
                this.filterUsers();
            }, 300));
        }

        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                this.filterUsers();
            });
        }
    }

    filterUsers() {
        const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
        const selectedRole = document.getElementById('roleFilter')?.value || '';
        const rows = document.querySelectorAll('.admin-table tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const roleCell = row.querySelector('.role-badge');
            const userRole = roleCell ? roleCell.textContent.toLowerCase() : '';

            const matchesSearch = text.includes(searchTerm);
            const matchesRole = !selectedRole || userRole === selectedRole;

            row.style.display = matchesSearch && matchesRole ? '' : 'none';
        });
    }
}

// =====================================
// GESTIONNAIRE SYSTÈME
// =====================================
class AdminSystem {
    constructor() {
        this.healthCheckInterval = null;
    }

    async checkSystemHealth() {
        try {
            const response = await AdminUtils.apiCall('system.php?action=health');
            this.updateHealthDisplay(response.data);
            return response.data;
        } catch (error) {
            AdminUtils.log('Erreur lors de la vérification système', 'error');
            return null;
        }
    }

    updateHealthDisplay(healthData) {
        const healthScore = document.querySelector('.health-score');
        const healthIssues = document.querySelector('.health-issues');

        if (healthScore) {
            healthScore.textContent = healthData.score + '%';
            healthScore.className = `health-score ${this.getHealthClass(healthData.score)}`;
        }

        if (healthIssues && healthData.issues) {
            healthIssues.innerHTML = healthData.issues.map(issue => 
                `<div class="health-issue">⚠️ ${issue}</div>`
            ).join('');
        }
    }

    getHealthClass(score) {
        if (score >= 90) return 'excellent';
        if (score >= 70) return 'good';
        if (score >= 50) return 'warning';
        return 'critical';
    }

    async clearCache() {
        if (!confirm('Voulez-vous vider le cache système ?')) return;

        try {
            const response = await AdminUtils.apiCall('system.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'clear_cache' })
            });

            AdminUtils.showNotification(response.message, 'success');
        } catch (error) {
            AdminUtils.showNotification('Erreur lors du vidage du cache', 'error');
        }
    }

    async optimizeDatabase() {
        if (!confirm('Voulez-vous optimiser la base de données ? Cette opération peut prendre du temps.')) return;

        try {
            const response = await AdminUtils.apiCall('system.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'optimize_db' })
            });

            AdminUtils.showNotification(response.message, 'success');
        } catch (error) {
            AdminUtils.showNotification('Erreur lors de l\'optimisation', 'error');
        }
    }
}

// =====================================
// GESTIONNAIRE DE MODULES
// =====================================
class AdminModules {
    constructor() {
        this.modules = [];
    }

    async analyzeModule(moduleName) {
        AdminUtils.log(`Analyse du module: ${moduleName}`);
        
        try {
            const response = await AdminUtils.apiCall(`modules.php?action=analyze&module=${moduleName}`);
            this.showModuleAnalysis(response.data);
        } catch (error) {
            AdminUtils.showNotification(`Analyse du module "${moduleName}" - Fonctionnalité à développer`, 'warning');
        }
    }

    showModuleAnalysis(analysisData) {
        // TODO: Afficher les résultats d'analyse dans une modale
        console.log('Analyse du module:', analysisData);
    }
}

// =====================================
// INITIALISATION GLOBALE
// =====================================
document.addEventListener('DOMContentLoaded', function() {
    AdminUtils.log('DOM chargé, initialisation des composants admin...');

    // Instances globales
    window.adminDashboard = new AdminDashboard();
    window.adminUsers = new AdminUsers();
    window.adminSystem = new AdminSystem();
    window.adminModules = new AdminModules();

    // Initialisation principale
    adminDashboard.init();

    // Initialisation spécifique selon la page
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('/admin/pages/users.php')) {
        adminUsers.loadUsers();
        adminUsers.setupSearch();
    }

    AdminUtils.log('Composants admin initialisés');
});

// =====================================
// FONCTIONS GLOBALES (COMPATIBILITÉ)
// =====================================

// Fonctions appelées directement depuis le HTML
function createUser() {
    window.adminUsers?.createUser();
}

function editUser(userId) {
    window.adminUsers?.editUser(userId);
}

function closeUserModal() {
    window.adminUsers?.closeUserModal();
}

function saveUser() {
    window.adminUsers?.saveUser();
}

function toggleUserStatus(userId) {
    window.adminUsers?.toggleUserStatus(userId);
}

function deleteUser(userId) {
    window.adminUsers?.deleteUser(userId);
}

function exportUsers() {
    window.adminUsers?.exportUsers();
}

function analyzeModule(moduleName) {
    window.adminModules?.analyzeModule(moduleName);
}

function refreshStats() {
    window.adminDashboard?.refreshSystemStats();
}

// Utilitaires globaux
function escape(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

AdminUtils.escape = escape;

// =====================================
// STYLES CSS POUR NOTIFICATIONS
// =====================================
const notificationStyles = `
<style>
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.admin-notification {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 400px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.admin-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-close {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.notification-close:hover {
    background-color: rgba(0,0,0,0.1);
}

.notification-success {
    border-left: 4px solid #27ae60;
}

.notification-error {
    border-left: 4px solid #e74c3c;
}

.notification-warning {
    border-left: 4px solid #f39c12;
}

.notification-info {
    border-left: 4px solid #3498db;
}
</style>
`;

// Injecter les styles
document.head.insertAdjacentHTML('beforeend', notificationStyles);

AdminUtils.log('Module Admin JavaScript chargé avec succès ✅');