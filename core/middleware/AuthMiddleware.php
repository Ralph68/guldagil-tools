<?php
/**
 * Titre: Middleware authentification adapté pour BDD
 * Chemin: /core/middleware/AuthMiddleware.php
 * Version: 0.5 beta + build auto
 */

class AuthMiddleware {
    private $auth;
    private $exemptPaths = ['/auth', '/assets', '/api/public', '/diagnostic.php'];

    public function __construct() {
        $this->auth = AuthManager::getInstance();
    }

    /**
     * Vérifier accès avant exécution
     */
    public function handle($path, $module = null) {
        // Chemins exemptés
        foreach ($this->exemptPaths as $exempt) {
            if (strpos($path, $exempt) !== false) {
                return true;
            }
        }

        // Vérifier authentification
        if (!$this->auth->isAuthenticated()) {
            $this->redirectToLogin();
            return false;
        }

        // Vérifier accès module si spécifié
        if ($module && !$this->auth->canAccessModule($module)) {
            $this->accessDenied($module);
            return false;
        }

        return true;
    }

    /**
     * Obtenir modules accessibles selon le rôle utilisateur
     */
    public function getAccessibleModules() {
        if (!$this->auth->isAuthenticated()) {
            return $this->getGuestModules();
        }

        $user = $this->auth->getCurrentUser();
        $allModules = $this->getAllModules();
        
        $accessible = [];
        foreach ($allModules as $moduleId => $info) {
            if (in_array($moduleId, $user['modules'])) {
                $info['status'] = 'active';
                $info['path'] = "/{$moduleId}/";
            } else {
                $info['status'] = 'disabled';
                $info['reason'] = 'Accès insuffisant';
                $info['path'] = '#';
            }
            $accessible[$moduleId] = $info;
        }

        return $accessible;
    }

    /**
     * Modules pour visiteurs non connectés
     */
    private function getGuestModules() {
        $modules = $this->getAllModules();
        
        // Marquer comme nécessitant une connexion
        foreach ($modules as $moduleId => &$module) {
            if (in_array($moduleId, ['calculateur', 'adr'])) {
                $module['status'] = 'login_required';
                $module['path'] = '/auth/login.php';
            } else {
                $module['status'] = 'development';
                $module['path'] = '#';
            }
        }
        
        return $modules;
    }

    /**
     * Définition de tous les modules
     */
    private function getAllModules() {
        return [
            'calculateur' => [
                'name' => 'Calculateur de frais',
                'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
                'icon' => '🧮',
                'color' => 'blue',
                'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique']
            ],
            'adr' => [
                'name' => 'Gestion ADR',
                'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
                'icon' => '⚠️',
                'color' => 'orange',
                'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire']
            ],
            'controle-qualite' => [
                'name' => 'Contrôle Qualité',
                'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
                'icon' => '✅',
                'color' => 'green',
                'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements']
            ],
            'epi' => [
                'name' => 'Équipements EPI',
                'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
                'icon' => '🛡️',
                'color' => 'purple',
                'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes']
            ],
            'outillages' => [
                'name' => 'Outillages',
                'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
                'icon' => '🔧',
                'color' => 'gray',
                'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation']
            ]
        ];
    }

    private function redirectToLogin() {
        header('Location: /auth/login.php');
        exit;
    }

    private function accessDenied($module) {
        http_response_code(403);
        echo "Accès refusé au module: $module";
        exit;
    }
}
