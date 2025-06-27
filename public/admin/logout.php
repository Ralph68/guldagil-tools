<?php
// public/admin/logout.php
// Gestion de la déconnexion admin avec sécurité renforcée

session_start();

// Inclure les fonctions d'authentification
require_once __DIR__ . '/auth.php';

// Récupérer les informations utilisateur avant déconnexion (pour les logs)
$userInfo = getAdminUser();
$sessionInfo = getSessionInfo();

// Vérifier si l'utilisateur était bien connecté
$wasLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Log de la déconnexion avec détails
if ($wasLoggedIn) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $userInfo['username'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_duration' => time() - ($userInfo['login_time'] ?? time()),
        'logout_type' => $_GET['type'] ?? 'manual'
    ];
    
    error_log("ADMIN_LOGOUT: " . json_encode($logData));
}

// Détruire complètement la session
destroyAdminSession();

// Invalider le cookie de session côté client
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire toutes les données de session
$_SESSION = [];

// Détruire le fichier de session sur le serveur
if (session_id()) {
    session_destroy();
}

// Régénérer l'ID de session pour empêcher la réutilisation
session_regenerate_id(true);

// Déterminer le type de déconnexion et le message
$logoutType = $_GET['type'] ?? 'manual';
$message = '';
$messageType = 'info';

switch ($logoutType) {
    case 'timeout':
        $message = 'Votre session a expiré pour des raisons de sécurité.';
        $messageType = 'warning';
        break;
    case 'security':
        $message = 'Déconnexion forcée pour des raisons de sécurité.';
        $messageType = 'error';
        break;
    case 'maintenance':
        $message = 'Déconnexion due à une maintenance système.';
        $messageType = 'info';
        break;
    case 'force':
        $message = 'Votre session a été fermée par un administrateur.';
        $messageType = 'warning';
        break;
    case 'manual':
    default:
        $message = 'Vous avez été déconnecté avec succès.';
        $messageType = 'success';
        break;
}

// Préparer les statistiques de session (si l'utilisateur était connecté)
$sessionStats = [];
if ($wasLoggedIn && isset($logData)) {
    $sessionStats = [
        'duration' => formatSessionDuration($logData['session_duration']),
        'actions_performed' => $_SESSION['actions_count'] ?? 0,
        'last_activity' => date('H:i:s', $userInfo['last_activity'] ?? time())
    ];
}

// Headers de sécurité supplémentaires
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Si c'est une requête AJAX, retourner JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'redirect' => 'login.php' . ($logoutType !== 'manual' ? "?reason=$logoutType" : ''),
        'session_stats' => $sessionStats
    ]);
    exit;
}

/**
 * Formate la durée de session en format lisible
 */
function formatSessionDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' seconde(s)';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . ' minute(s)';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Administration Guldagil</title>
    <style>
        :root {
            --primary-color: #007acc;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --error-color: #f44336;
            --info-color: #2196f3;
            --bg-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.5s ease;
        }

        .logout-container {
            background: white;
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            text-align: center;
            animation: slideInUp 0.5s ease;
        }

        .logout-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease;
        }

        .logout-title {
            color: #333;
            margin: 0 0 1rem 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .logout-message {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.5;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .session-stats {
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: left;
        }

        .session-stats h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.1rem;
            text-align: center;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .stat-value {
            color: #333;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #005f99;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .security-notice {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #1565c0;
        }

        .countdown {
            font-weight: bold;
            color: var(--primary-color);
        }

        .footer-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ddd;
            font-size: 0.85rem;
            color: #666;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @media (max-width: 480px) {
            .logout-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <!-- Icône de déconnexion -->
        <div class="logout-icon">
            <?php
            switch ($messageType) {
                case 'success': echo '✅'; break;
                case 'warning': echo '⚠️'; break;
                case 'error': echo '❌'; break;
                default: echo '👋'; break;
            }
            ?>
        </div>

        <!-- Titre -->
        <h1 class="logout-title">
            <?php
            switch ($logoutType) {
                case 'timeout':
                    echo 'Session expirée';
                    break;
                case 'security':
                    echo 'Déconnexion sécurisée';
                    break;
                case 'maintenance':
                    echo 'Maintenance en cours';
                    break;
                case 'force':
                    echo 'Session fermée';
                    break;
                default:
                    echo 'À bientôt !';
                    break;
            }
            ?>
        </h1>

        <!-- Message -->
        <div class="logout-message message-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>

        <!-- Statistiques de session (si disponibles) -->
        <?php if (!empty($sessionStats) && $wasLoggedIn): ?>
        <div class="session-stats">
            <h3>📊 Résumé de votre session</h3>
            <div class="stat-item">
                <span class="stat-label">Utilisateur :</span>
                <span class="stat-value"><?= htmlspecialchars($userInfo['username']) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Durée de connexion :</span>
                <span class="stat-value"><?= $sessionStats['duration'] ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Dernière activité :</span>
                <span class="stat-value"><?= $sessionStats['last_activity'] ?></span>
            </div>
            <?php if ($sessionStats['actions_performed'] > 0): ?>
            <div class="stat-item">
                <span class="stat-label">Actions effectuées :</span>
                <span class="stat-value"><?= $sessionStats['actions_performed'] ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions disponibles -->
        <div class="actions">
            <a href="login.php<?= $logoutType !== 'manual' ? "?reason=$logoutType" : '' ?>" class="btn btn-primary">
                <span>🔐</span>
                Se reconnecter
            </a>
            <a href="../" class="btn btn-secondary">
                <span>🏠</span>
                Retour au calculateur
            </a>
        </div>

        <!-- Redirection automatique pour certains cas -->
        <?php if (in_array($logoutType, ['timeout', 'security', 'maintenance'])): ?>
        <div class="security-notice">
            <strong>🔄 Redirection automatique</strong><br>
            Vous serez redirigé vers la page de connexion dans <span class="countdown" id="countdown">10</span> secondes.
            <br><br>
            <button onclick="clearRedirect()" style="background: none; border: 1px solid #2196f3; color: #2196f3; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">
                Annuler la redirection
            </button>
        </div>
        <?php endif; ?>

        <!-- Informations de contact -->
        <div class="footer-info">
            <strong>Besoin d'aide ?</strong><br>
            📧 Support technique : runser.jean.thomas@guldagil.com<br>
            📞 Service achat : 03 89 63 42 42<br>
            <small>Administration Guldagil Port Calculator v1.2.0</small>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('👋 Page de déconnexion chargée');
            
            // Nettoyer le localStorage admin
            clearAdminData();
            
            // Gérer la redirection automatique si nécessaire
            <?php if (in_array($logoutType, ['timeout', 'security', 'maintenance'])): ?>
                setupAutoRedirect();
            <?php endif; ?>
            
            // Afficher une notification si supportée par le navigateur
            showLogoutNotification();
        });

        /**
         * Nettoie les données admin stockées localement
         */
        function clearAdminData() {
            try {
                // Nettoyer localStorage
                const keysToRemove = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && (key.startsWith('admin_') || key.startsWith('gul_admin_'))) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(key => localStorage.removeItem(key));
                
                // Nettoyer sessionStorage
                sessionStorage.clear();
                
                console.log('🧹 Données admin nettoyées');
            } catch (error) {
                console.error('Erreur nettoyage données:', error);
            }
        }

        /**
         * Configure la redirection automatique
         */
        function setupAutoRedirect() {
            let countdown = 10;
            const countdownEl = document.getElementById('countdown');
            let redirectTimer;
            
            function updateCountdown() {
                countdown--;
                if (countdownEl) {
                    countdownEl.textContent = countdown;
                }
                
                if (countdown <= 0) {
                    window.location.href = 'login.php?reason=<?= $logoutType ?>';
                } else {
                    redirectTimer = setTimeout(updateCountdown, 1000);
                }
            }
            
            // Démarrer le compte à rebours
            redirectTimer = setTimeout(updateCountdown, 1000);
            
            // Fonction globale pour annuler la redirection
            window.clearRedirect = function() {
                clearTimeout(redirectTimer);
                const notice = document.querySelector('.security-notice');
                if (notice) {
                    notice.innerHTML = '<strong>✅ Redirection annulée</strong><br>Vous pouvez rester sur cette page.';
                    notice.style.background = '#d4edda';
                    notice.style.borderColor = '#c3e6cb';
                    notice.style.color = '#155724';
                }
            };
        }

        /**
         * Affiche une notification de déconnexion
         */
        function showLogoutNotification() {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Guldagil Admin', {
                    body: '<?= addslashes($message) ?>',
                    icon: '/favicon.ico',
                    tag: 'admin-logout'
                });
            }
        }

        /**
         * Empêche le retour en arrière vers les pages admin
         */
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Empêcher l'utilisation du cache pour cette page
        window.addEventListener('beforeunload', function() {
            // Forcer le rechargement si l'utilisateur revient
        });

        // Gestion des raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl+L ou F5 pour aller directement au login
            if ((e.ctrlKey && e.key === 'l') || e.key === 'F5') {
                e.preventDefault();
                window.location.href = 'login.php';
            }
            
            // Echap pour aller au calculateur
            if (e.key === 'Escape') {
                window.location.href = '../';
            }
        });

        // Analytics de déconnexion (optionnel)
        try {
            // Vous pouvez ajouter ici du tracking analytics
            console.log('📊 Logout analytics:', {
                type: '<?= $logoutType ?>',
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent
            });
        } catch (error) {
            // Ignorer les erreurs analytics
        }
    </script>
</body>
</html>
