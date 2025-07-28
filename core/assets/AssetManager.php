<?php
/**
 * Titre: Gestionnaire centralisé des assets CSS/JS
 * Chemin: /core/assets/AssetManager.php
 * Version: 0.5 beta + build auto
 */

class AssetManager 
{
    private static $instance = null;
    private $cssFiles = [];
    private $jsFiles = [];
    private $buildNumber;
    private $rootPath;
    
    private function __construct() {
        $this->buildNumber = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
        $this->rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__DIR__));
        
        // CSS critiques OBLIGATOIRES - ne jamais modifier ces chemins
        $this->addCriticalAssets();
    }
    
    public static function getInstance(): AssetManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ajouter les assets critiques du portail (OBLIGATOIRE)
     */
    private function addCriticalAssets(): void {
        // CSS critiques - chemins absolument CRITIQUES à préserver
        $this->cssFiles[] = '/assets/css/portal.css';
        $this->cssFiles[] = '/assets/css/header.css';
        $this->cssFiles[] = '/assets/css/footer.css';
        $this->cssFiles[] = '/assets/css/components.css';
        $this->cssFiles[] = '/assets/css/cookie_banner.css';
    }
    
    /**
     * Charger automatiquement les assets d'un module
     */
    public function loadModuleAssets(string $module, bool $loadCss = true, bool $loadJs = true): void {
        if ($module === 'home') {
            return; // Pas d'assets spécifiques pour home
        }
        
        if ($loadCss) {
            $this->loadModuleCss($module);
        }
        
        if ($loadJs) {
            $this->loadModuleJs($module);
        }
    }
    
    /**
     * Charger CSS d'un module avec système de fallback intelligent
     */
    private function loadModuleCss(string $module): void {
        // Priorité 1: Nouvelle architecture /public/module/assets/css/
        $newCssPath = "/public/{$module}/assets/css/{$module}.css";
        
        if ($this->fileExists($newCssPath)) {
            $this->cssFiles[] = "/{$module}/assets/css/{$module}.css";
            return;
        }
        
        // Priorité 2: Ancien système - chemins de fallback
        $fallbackPaths = [
            "/public/{$module}/css/{$module}.css",
            "/public/assets/css/modules/{$module}.css",
            "/public/{$module}/{$module}.css"
        ];
        
        foreach ($fallbackPaths as $path) {
            if ($this->fileExists($path)) {
                // Convertir le chemin absolu en chemin web
                $webPath = str_replace('/public', '', $path);
                $this->cssFiles[] = $webPath;
                return;
            }
        }
        
        // Log si aucun CSS trouvé pour le module
        error_log("AssetManager: Aucun CSS trouvé pour le module '{$module}'");
    }
    
    /**
     * Charger JavaScript d'un module avec système de fallback
     */
    private function loadModuleJs(string $module): void {
        // Priorité 1: Nouvelle architecture
        $newJsPath = "/public/{$module}/assets/js/{$module}.js";
        
        if ($this->fileExists($newJsPath)) {
            $this->jsFiles[] = "/{$module}/assets/js/{$module}.js";
            return;
        }
        
        // Priorité 2: Ancien système
        $fallbackPaths = [
            "/public/{$module}/js/{$module}.js",
            "/public/assets/js/modules/{$module}.js",
            "/public/{$module}/{$module}.js"
        ];
        
        foreach ($fallbackPaths as $path) {
            if ($this->fileExists($path)) {
                $webPath = str_replace('/public', '', $path);
                $this->jsFiles[] = $webPath;
                return;
            }
        }
    }
    
    /**
     * Ajouter un fichier CSS personnalisé
     */
    public function addCss(string $path): void {
        if (!in_array($path, $this->cssFiles)) {
            $this->cssFiles[] = $path;
        }
    }
    
    /**
     * Ajouter un fichier JS personnalisé
     */
    public function addJs(string $path): void {
        if (!in_array($path, $this->jsFiles)) {
            $this->jsFiles[] = $path;
        }
    }
    
    /**
     * Générer les balises <link> CSS
     */
    public function renderCss(): string {
        $output = [];
        
        foreach ($this->cssFiles as $cssFile) {
            $output[] = sprintf(
                '<link rel="stylesheet" href="%s?v=%s">',
                htmlspecialchars($cssFile),
                $this->buildNumber
            );
        }
        
        return implode("\n    ", $output);
    }
    
    /**
     * Générer les balises <script> JS  
     */
    public function renderJs(): string {
        $output = [];
        
        foreach ($this->jsFiles as $jsFile) {
            $output[] = sprintf(
                '<script src="%s?v=%s"></script>',
                htmlspecialchars($jsFile),
                $this->buildNumber
            );
        }
        
        return implode("\n    ", $output);
    }
    
    /**
     * Vérifier l'existence d'un fichier (avec cache simple)
     */
    private function fileExists(string $path): bool {
        static $cache = [];
        
        if (isset($cache[$path])) {
            return $cache[$path];
        }
        
        $fullPath = $this->rootPath . $path;
        $exists = file_exists($fullPath) && is_readable($fullPath);
        
        $cache[$path] = $exists;
        return $exists;
    }
    
    /**
     * Obtenir la liste des CSS chargés (debug)
     */
    public function getCssFiles(): array {
        return $this->cssFiles;
    }
    
    /**
     * Obtenir la liste des JS chargés (debug)
     */
    public function getJsFiles(): array {
        return $this->jsFiles;
    }
    
    /**
     * Réinitialiser les assets (utile pour les tests)
     */
    public function reset(): void {
        $this->cssFiles = [];
        $this->jsFiles = [];
        $this->addCriticalAssets();
    }
    
    /**
     * Afficher des informations de debug
     */
    public function debug(): array {
        return [
            'css_count' => count($this->cssFiles),
            'js_count' => count($this->jsFiles),
            'build_number' => $this->buildNumber,
            'css_files' => $this->cssFiles,
            'js_files' => $this->jsFiles
        ];
    }
}