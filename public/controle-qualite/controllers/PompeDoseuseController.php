<?php
// /public/controle-qualite/controllers/PompeDoseuseController.php

require_once 'models/Controle.php';
require_once 'models/PompeDoseuse.php';

class PompeDoseuseController {
    private $pdo;
    private $controleModel;
    private $pompeModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->controleModel = new Controle($pdo);
        $this->pompeModel = new PompeDoseuse($pdo);
    }
    
    public function nouveau() {
        session_start();
        
        // Récupérer agences
        $agences = $this->pdo->query("SELECT nom FROM gul_agences WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_COLUMN);
        
        if ($_POST) {
            $this->traiterFormulaire();
        }
        
        $this->loadView('pompe-doseuse/formulaire', [
            'agences' => $agences,
            'equipements' => $this->pompeModel->getEquipements(),
            'documentation' => $this->pompeModel->getDocumentation()
        ]);
    }
    
    public function pdf() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: index.php');
            exit;
        }
        
        $controle = $this->controleModel->getById($id);
        $pompe = $this->pompeModel->getByControleId($id);
        
        if (!$controle) {
            header('Location: index.php');
            exit;
        }
        
        $this->generatePDF($controle, $pompe);
    }
    
    private function traiterFormulaire() {
        try {
            // Validation
            $errors = $this->validateForm($_POST);
            if (!empty($errors)) {
                throw new Exception(implode(', ', $errors));
            }
            
            // Créer contrôle
            $controle_id = $this->controleModel->create([
                'type_equipement' => 'pompe_doseuse',
                'numero_arc' => $_POST['numero_arc'],
                'numero_dossier' => $_POST['numero_dossier'] ?? '',
                'agence' => $_POST['agence'],
                'nom_installation' => $_POST['nom_installation'],
                'operateur_nom' => $_POST['operateur_nom'],
                'operateur_email' => $_POST['operateur_email'],
                'date_expedition' => $_POST['date_expedition'],
                'observations' => $_POST['observations'] ?? ''
            ]);
            
            // Créer détails pompe
            $this->pompeModel->create($controle_id, $_POST);
            
            // Marquer terminé
            $this->controleModel->update($controle_id, [
                'statut' => 'termine',
                'donnees' => [],
                'observations' => $_POST['observations'] ?? ''
            ]);
            
            // Rediriger vers PDF
            header("Location: index.php?controller=pompe-doseuse&action=pdf&id=$controle_id");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
    
    private function validateForm($data) {
        $errors = [];
        
        if (empty($data['numero_arc'])) $errors[] = 'N° ARC requis';
        if (empty($data['agence'])) $errors[] = 'Agence requise';
        if (empty($data['nom_installation'])) $errors[] = 'Nom installation requis';
        if (empty($data['operateur_nom'])) $errors[] = 'Nom opérateur requis';
        if (empty($data['operateur_email'])) $errors[] = 'Email opérateur requis';
        if (!filter_var($data['operateur_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
        if (empty($data['date_expedition'])) $errors[] = 'Date expédition requise';
        if (empty($data['marque'])) $errors[] = 'Marque requise';
        
        return $errors;
    }
    
    private function generatePDF($controle, $pompe) {
        require_once '../lib/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        // Générer contenu PDF
        ob_start();
        include 'views/pompe-doseuse/pdf.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Nom fichier
        $filename = 'controle_pompe_' . $controle['numero_arc'] . '_' . date('Ymd') . '.pdf';
        
        $pdf->Output($filename, 'D');
    }
    
    protected function loadView($view, $data = []) {
        extract($data);
        
        ob_start();
        include "views/$view.php";
        $content = ob_get_clean();
        
        include 'views/layouts/main.php';
    }
}
