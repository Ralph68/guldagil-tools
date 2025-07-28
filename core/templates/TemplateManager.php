<?php
/**
 * Titre: Gestionnaire de templates centralisé
 * Chemin: /core/templates/TemplateManager.php
 * Version: 0.5 beta + build auto
 */

class TemplateManager 
{
    private static $instance = null;
    private $templatePath;
    private $layoutVars = [];
    private $sections = [];
    private $currentSection = '';
    
    private function __construct() {
        $this->templatePath = ROOT_PATH . '/templates';
    }
    
    public static function getInstance(): TemplateManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Définir une variable pour le layout
     */
    public function setVar(string $name, $value): void {
        $this->layoutVars[$name] = $value;
    }
    
    /**
     * Définir plusieurs variables
     */
    public function setVars(array $vars): void {
        $this->layoutVars = array_merge($this->layoutVars, $vars);
    }
    
    /**
     * Obtenir une variable
     */
    public function getVar(string $name, $default = null) {
        return $this->layoutVars[$name] ?? $default;
    }
    
    /**
     * Démarrer une section
     */
    public function startSection(string $name): void {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * Terminer une section
     */
    public function endSection(): void {
        if (!empty($this->currentSection)) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }
    
    /**
     * Obtenir le contenu d'une section
     */
    public function getSection(string $name, string $default = ''): string {
        return $this->sections[$name] ?? $default;
    }
    
    /**
     * Inclure un template avec variables
     */
    public function include(string $template, array $vars = []): void {
        $templateFile = $this->templatePath . '/' . ltrim($template, '/');
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template non trouvé: {$templateFile}");
        }
        
        // Rendre les variables disponibles dans le template
        extract($this->layoutVars);
        extract($vars);
        
        include $templateFile;
    }
    
    /**
     * Rendre un template et retourner le contenu
     */
    public function render(string $template, array $vars = []): string {
        ob_start();
        $this->include($template, $vars);
        return ob_get_clean();
    }
    
    /**
     * Initialiser le layout principal (amélioration du header existant)
     */
    public function initLayout(array $config = []): void {
        // Configuration par défaut compatible avec l'existant
        $defaultConfig = [
            'page_title' => 'Portail Guldagil',
            'page_subtitle' => '',
            'current_module' => 'home',
            'module_icon' => '🏠',
            'module_color' => '#3b82f6',
            'module_status' => 'stable',
            'user_authenticated' => false,
            'current_user' => null,
            'build_number' => defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis'),
            'app_name' => defined('APP_NAME') ? APP_NAME : 'Portail Guldagil',
            'show_breadcrumbs' => true,
            'enable_search' => false,
            'custom_css' => [],
            'custom_js' => []
        ];
        
        $this->setVars(array_merge($defaultConfig, $config));
        
        // Auto-détection module si RouteManager disponible
        if (class_exists('RouteManager')) {
            $routeManager = RouteManager::getInstance();
            $currentModule = $routeManager->getCurrentModule();
            $this->setVar('current_module', $currentModule);
            
            // Auto-génération breadcrumbs
            if ($this->getVar('show_breadcrumbs')) {
                $this->setVar('breadcrumbs', $routeManager->getBreadcrumbs());
            }
        }
        
        // Auto-détection authentification si AuthManager disponible
        if (class_exists('AuthManager')) {
            $authManager = AuthManager::getInstance();
            $this->setVar('user_authenticated', $authManager->isAuthenticated());
            $this->setVar('current_user', $authManager->getCurrentUser());
        }
    }
    
    /**
     * Rendu du header principal (compatible avec templates/header.php existant)
     */
    public function renderHeader(): void {
        // Vérifier si le template header existant est présent
        $existingHeader = ROOT_PATH . '/templates/header.php';
        
        if (file_exists($existingHeader)) {
            // Utiliser le header existant en injectant nos variables
            extract($this->layoutVars);
            include $existingHeader;
        } else {
            // Fallback vers un header minimal
            $this->renderMinimalHeader();
        }
    }
    
    /**
     * Header minimal de fallback
     */
    private function renderMinimalHeader(): void {
        $pageTitle = $this->getVar('page_title', 'Portail Guldagil');
        $buildNumber = $this->getVar('build_number', 'dev');
        $currentModule = $this->getVar('current_module', 'home');
        
        echo "<!DOCTYPE html>\n";
        echo "<html lang=\"fr\">\n";
        echo "<head>\n";
        echo "    <meta charset=\"UTF-8\">\n";
        echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo "    <title>{$pageTitle}</title>\n";
        
        // CSS globaux obligatoires
        echo "    <link rel=\"stylesheet\" href=\"/assets/css/portal.css?v={$buildNumber}\">\n";
        echo "    <link rel=\"stylesheet\" href=\"/assets/css/header.css?v={$buildNumber}\">\n";
        echo "    <link rel=\"stylesheet\" href=\"/assets/css/footer.css?v={$buildNumber}\">\n";
        echo "    <link rel=\"stylesheet\" href=\"/assets/css/components.css?v={$buildNumber}\">\n";
        
        // CSS custom
        foreach ($this->getVar('custom_css', []) as $css) {
            echo "    <link rel=\"stylesheet\" href=\"{$css}?v={$buildNumber}\">\n";
        }
        
        echo "</head>\n";
        echo "<body class=\"module-{$currentModule}\">\n";
        
        // Header minimal
        echo "<header class=\"portal-header\">\n";
        echo "    <div class=\"header-container\">\n";
        echo "        <h1>{$pageTitle}</h1>\n";
        echo "    </div>\n";
        echo "</header>\n";
    }
    
    /**
     * Rendu du footer principal (compatible avec templates/footer.php existant)
     */
    public function renderFooter(): void {
        $existingFooter = ROOT_PATH . '/templates/footer.php';
        
        if (file_exists($existingFooter)) {
            extract($this->layoutVars);
            include $existingFooter;
        } else {
            $this->renderMinimalFooter();
        }
    }
    
    /**
     * Footer minimal de fallback
     */
    private function renderMinimalFooter(): void {
        $buildNumber = $this->getVar('build_number', 'dev');
        $appName = $this->getVar('app_name', 'Portail Guldagil');
        
        echo "<footer class=\"portal-footer\">\n";
        echo "    <div class=\"footer-container\">\n";
        echo "        <p>&copy; " . date('Y') . " {$appName} - Version 0.5 beta - Build {$buildNumber}</p>\n";
        echo "    </div>\n";
        echo "</footer>\n";
        
        // JavaScript custom
        foreach ($this->getVar('custom_js', []) as $js) {
            echo "<script src=\"{$js}?v={$buildNumber}\"></script>\n";
        }
        
        echo "</body>\n";
        echo "</html>\n";
    }
    
    /**
     * Génération automatique des chemins CSS/JS par module
     */
    public function getModuleAssets(string $module): array {
        $buildNumber = $this->getVar('build_number', 'dev');
        $assets = ['css' => [], 'js' => []];
        
        // CSS du module
        $moduleCssPaths = [
            "/public/{$module}/assets/css/{$module}.css",
            "/{$module}/assets/css/{$module}.css"
        ];
        
        foreach ($moduleCssPaths as $path) {
            if (file_exists(ROOT_PATH . $path)) {
                $assets['css'][] = $path . "?v=" . $buildNumber;
                break;
            }
        }
        
        // JS du module
        $moduleJsPaths = [
            "/public/{$module}/assets/js/{$module}.js",
            "/{$module}/assets/js/{$module}.js",
            "/assets/js/modules/{$module}.js"
        ];
        
        foreach ($moduleJsPaths as $path) {
            if (file_exists(ROOT_PATH . $path)) {
                $assets['js'][] = $path . "?v=" . $buildNumber;
                break;
            }
        }
        
        return $assets;
    }
    
    /**
     * Rendu complet d'une page avec layout
     */
    public function renderPage(string $content, array $config = []): void {
        $this->initLayout($config);
        
        // Header
        $this->renderHeader();
        
        // Breadcrumbs si activés
        if ($this->getVar('show_breadcrumbs') && !empty($this->getVar('breadcrumbs'))) {
            $this->renderBreadcrumbs();
        }
        
        // Contenu principal
        echo "<main class=\"main-content\">\n";
        echo $content;
        echo "</main>\n";
        
        // Footer
        $this->renderFooter();
    }
    
    /**
     * Rendu des breadcrumbs
     */
    private function renderBreadcrumbs(): void {
        $breadcrumbs = $this->getVar('breadcrumbs', []);
        
        if (!empty($breadcrumbs)) {
            echo "<div class=\"breadcrumb-container\">\n";
            echo "    <nav aria-label=\"breadcrumb\">\n";
            echo "        <ol class=\"breadcrumb\">\n";
            
            foreach ($breadcrumbs as $crumb) {
                if (isset($crumb['active']) && $crumb['active']) {
                    echo "            <li class=\"breadcrumb-item active\">\n";
                    echo "                {$crumb['icon']} " . htmlspecialchars($crumb['text']) . "\n";
                    echo "            </li>\n";
                } else {
                    echo "            <li class=\"breadcrumb-item\">\n";
                    echo "                <a href=\"" . htmlspecialchars($crumb['url']) . "\">\n";
                    echo "                    {$crumb['icon']} " . htmlspecialchars($crumb['text']) . "\n";
                    echo "                </a>\n";
                    echo "            </li>\n";
                }
            }
            
            echo "        </ol>\n";
            echo "    </nav>\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Utilitaire: injecter les assets CSS/JS dans le header existant
     */
    public function injectAssets(string $module): void {
        $assets = $this->getModuleAssets($module);
        
        $existingCss = $this->getVar('custom_css', []);
        $existingJs = $this->getVar('custom_js', []);
        
        $this->setVar('custom_css', array_merge($existingCss, $assets['css']));
        $this->setVar('custom_js', array_merge($existingJs, $assets['js']));
    }
    
    /**
     * Helper: obtenir la configuration de couleur par module
     */
    public function getModuleColor(string $module): string {
        $colors = [
            'home' => '#3b82f6',
            'admin' => '#374151', 
            'user' => '#6b7280',
            'port' => '#059669',
            'materiel' => '#dc2626',
            'qualite' => '#7c3aed',
            'epi' => '#ea580c',
            'adr' => '#d97706'
        ];
        
        return $colors[$module] ?? '#6b7280';
    }
    
    /**
     * Helper: obtenir l'icône par module
     */
    public function getModuleIcon(string $module): string {
        $icons = [
            'home' => '🏠',
            'admin' => '⚙️',
            'user' => '👤', 
            'port' => '🚚',
            'materiel' => '🔧',
            'qualite' => '🔬',
            'epi' => '🦺',
            'adr' => '⚠️'
        ];
        
        return $icons[$module] ?? '📄';
    }
    
    /**
     * Empêcher le clonage
     */
    private function __clone() {}
    
    /**
     * Empêcher la désérialisation  
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}