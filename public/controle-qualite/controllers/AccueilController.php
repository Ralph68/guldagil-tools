<?php
// /public/controle-qualite/controllers/AccueilController.php

require_once 'models/Controle.php';

class AccueilController {
    private $pdo;
    private $controleModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->controleModel = new Controle($pdo);
    }
    
    public function index() {
        // Stats du jour
        $stats = [
            'today' => $this->getStatsToday(),
            'en_cours' => $this->getStatsEnCours(),
            'termines_7j' => $this->getStatsTermines7j()
        ];
        
        // Contrôles récents
        $recents = $this->controleModel->search(['limit' => 5]);
        
        // Charger la vue
        $this->loadView('accueil/index', [
            'stats' => $stats,
            'recents' => $recents
        ]);
    }
    
    private function getStatsToday() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM gul_controles WHERE DATE(date_controle) = CURDATE()");
        return $stmt->fetchColumn();
    }
    
    private function getStatsEnCours() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'en_cours'");
        return $stmt->fetchColumn();
    }
    
    private function getStatsTermines7j() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'termine' AND DATE(date_controle) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        return $stmt->fetchColumn();
    }
    
    protected function loadView($view, $data = []) {
        extract($data);
        
        ob_start();
        include "views/$view.php";
        $content = ob_get_clean();
        
        include 'views/layouts/main.php';
    }
}
