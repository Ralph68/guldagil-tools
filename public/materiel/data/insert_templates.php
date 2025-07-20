<?php
// Script d'insertion des templates d'outils basé sur les listes technicien et monteur

require_once '../../../config/database.php';

try {
    $db = getDB();
    
    // Templates pour TECHNICIEN
    $templates_technicien = [
        // Brosses et outils de nettoyage
        ['categorie_id' => 7, 'designation' => 'Brosse métallique', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Brosse plastique (Type brosse à dent)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Brosse large de tapissier (pour cimentage)', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de base
        ['categorie_id' => 7, 'designation' => 'Burin', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 4, 'designation' => 'Calculatrice', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Caisse métallique', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Casquette de sécurité', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Chiffons', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés spécialisées
        ['categorie_id' => 1, 'designation' => 'Clef coffret bleu 3132 A', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef coffret rouge H520', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à griffe 1" 1/2 suèdoise', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à griffe 3" Stillson', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés à molette
        ['categorie_id' => 1, 'designation' => 'Clef à molette 12"', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à molette 6"', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés à pipe
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 5,5', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 6', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 7', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 8', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 10', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 13', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 17', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 19', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à pipe de 24', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés spéciales
        ['categorie_id' => 1, 'designation' => 'Clef 6 pans mâle Ø 4 mm', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef Torx Mâle T 15', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à sangle', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef pour vanne Clack', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clefs carrés de purgeurs D4 et D5mm', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés mixtes
        ['categorie_id' => 1, 'designation' => 'Clef mixte 7 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 8 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 10 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 13 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés plates
        ['categorie_id' => 1, 'designation' => 'Clef plate 16 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef plate 17 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef plate 19 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef plate 22 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef plate 24 (fourche + œil)', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils divers
        ['categorie_id' => 7, 'designation' => 'Craie de briancon', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Couteau électricien', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Dénude cable Jokari Facom', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Diable pliable', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Escabeau conforme (taille à adapter suivant vehicule)', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils électriques
        ['categorie_id' => 8, 'designation' => 'Fer à souder 30 W', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Outils de mesure
        ['categorie_id' => 4, 'designation' => 'Fil à Plomb', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 4, 'designation' => 'Métre ruban 3 m', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 4, 'designation' => 'Multimètre de type Multimetrix DMM 210 avec sa sacoche', 'marque' => 'Multimetrix', 'modele' => 'DMM 210', 'observations' => 'Marque et type:', 'quantite_standard' => 1],
        ['categorie_id' => 4, 'designation' => 'Pied à coulisse', 'observations' => 'Plastique', 'quantite_standard' => 1],
        
        // Forets
        ['categorie_id' => 7, 'designation' => 'Foret 3-4-5-7-8-10', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Foret béton 6-8-10', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de frappe
        ['categorie_id' => 7, 'designation' => 'Grattoir', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Marteau 500 grs', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Mallet plastique', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Pointeau', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements spécialisés
        ['categorie_id' => 4, 'designation' => 'Kit pour analyse oxygène (réduction + tuyau Ø 10)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Grattoir pour corps de chauffe', 'observations' => 'Triangulaire', 'quantite_standard' => 1],
        ['categorie_id' => 4, 'designation' => 'Jeu cordon à fiche banane de 4', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Jeu de clef allen 2 à 10', 'observations' => '', 'quantite_standard' => 1],
        
        // Éclairage
        ['categorie_id' => 6, 'designation' => 'Lampe frontale', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Outils de finition
        ['categorie_id' => 7, 'designation' => 'Lime demi-ronde', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Marqueur', 'observations' => '', 'quantite_standard' => 1],
        
        // Électroportatif
        ['categorie_id' => 5, 'designation' => 'Perceuse à percussion pneumatique', 'observations' => 'Marque et type:', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Outils manuels divers
        ['categorie_id' => 7, 'designation' => 'Petite pelle', 'observations' => '', 'quantite_standard' => 1],
        
        // Pinces
        ['categorie_id' => 3, 'designation' => 'Pince à bec long 1/2 rond coudée isolée', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince à dénuder/sertir Knipex isolée', 'marque' => 'Knipex', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince à Rylsan', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince coupante de côté isolée', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince à sertir embouts', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince d\'horloger', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince multiprise isolée', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince universelle isolée', 'observations' => '', 'quantite_standard' => 1],
        
        // Pinceaux
        ['categorie_id' => 7, 'designation' => 'Pinceau de nettoyage', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Pinceau à rechampir', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements électriques
        ['categorie_id' => 8, 'designation' => 'Rallonge électrique 3G 2.5 mm² 25 m', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de coupe
        ['categorie_id' => 7, 'designation' => 'Scie à métaux (avec 2 lames)', 'observations' => '', 'quantite_standard' => 1],
        
        // Récipients
        ['categorie_id' => 7, 'designation' => 'Seau 10 l', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Savon', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements spécialisés
        ['categorie_id' => 8, 'designation' => 'Séche cheveux pour manchettes témoins', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Tournevis isolés
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 2.5 x 75 ou x 50', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 3.5 x 100', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 5.5 x 150', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 6.5 x 200', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé cruciforme PH0 1 x 80', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé cruciforme PZ1 1 x 100', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé cruciforme PH2 2 x 125', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis poing isolé 4 x 40 (suivant besoin)', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis poing cruciforme 2 x 40 (suivant besoin)', 'observations' => '', 'quantite_standard' => 1],
        
        // Valise
        ['categorie_id' => 7, 'designation' => 'Valise de maintenance', 'observations' => 'Type:', 'quantite_standard' => 1]
    ];
    
    // Templates pour MONTEUR
    $templates_monteur = [
        // Éclairage
        ['categorie_id' => 6, 'designation' => 'Baladeuse 24 V', 'observations' => '', 'quantite_standard' => 2, 'maintenance_requise' => 1],
        ['categorie_id' => 6, 'designation' => 'Lampe de poche', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Équipements de protection
        ['categorie_id' => 7, 'designation' => 'Bottes', 'observations' => '', 'quantite_standard' => 2],
        
        // Outils électroportatifs
        ['categorie_id' => 8, 'designation' => 'Bouloneuse', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        ['categorie_id' => 5, 'designation' => 'Perceuse', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Brosses
        ['categorie_id' => 7, 'designation' => 'Brosse métallique', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Brosse pour cimentage', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de base
        ['categorie_id' => 7, 'designation' => 'Burin', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés spécialisées monteur
        ['categorie_id' => 1, 'designation' => 'Clef à course libre de 30', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à course libre de 32', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 10-13', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 17-19', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte 22-24', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés à molette monteur
        ['categorie_id' => 1, 'designation' => 'Clef à molette 10"', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à molette 15"', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés à tube
        ['categorie_id' => 1, 'designation' => 'Clef à tube 10', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à tube 13', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à tube 30', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à tube 32', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef à tube 36', 'observations' => '', 'quantite_standard' => 1],
        
        // Clés coudées
        ['categorie_id' => 1, 'designation' => 'Clef mixte contre coudé 30/32', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Clef mixte contre coudé 36', 'observations' => '', 'quantite_standard' => 1],
        
        // Douilles de choc
        ['categorie_id' => 1, 'designation' => 'Douille de choc 17', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 19', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 21', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 22', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 24', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 27', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 30', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 1, 'designation' => 'Douille de choc 32', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements spécialisés
        ['categorie_id' => 7, 'designation' => 'Eléments échelle 1 m pour intérieur réservoir', 'observations' => '', 'quantite_standard' => 1],
        
        // Forets monteur
        ['categorie_id' => 7, 'designation' => 'Foret béton 8', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Foret béton 10', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Foret + adaptateur', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de nettoyage
        ['categorie_id' => 7, 'designation' => 'Grattoir', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Outil pour nettoyer c/c', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements eau
        ['categorie_id' => 7, 'designation' => 'Jet d\'eau 25 m', 'observations' => '', 'quantite_standard' => 1],
        
        // Jeux d'outils
        ['categorie_id' => 7, 'designation' => 'Jeu emporte pièces pour joint', 'observations' => '', 'quantite_standard' => 1],
        
        // Limes
        ['categorie_id' => 7, 'designation' => 'Lime 1/2 ronde', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de frappe
        ['categorie_id' => 7, 'designation' => 'Marteau', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils de mélange
        ['categorie_id' => 7, 'designation' => 'Mélangeur pour ciment', 'observations' => '', 'quantite_standard' => 1],
        
        // Mesure
        ['categorie_id' => 4, 'designation' => 'Mètre ruban 3 m', 'observations' => '', 'quantite_standard' => 1],
        
        // Pelles
        ['categorie_id' => 7, 'designation' => 'Pelle grand modèle', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Pelle petit modèle', 'observations' => '', 'quantite_standard' => 1],
        
        // Pinces monteur
        ['categorie_id' => 3, 'designation' => 'Pince à griffe 2"', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince à griffe 3"', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince multiprise', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince universelle', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 3, 'designation' => 'Pince coupante', 'observations' => '', 'quantite_standard' => 1],
        
        // Équipements électriques
        ['categorie_id' => 8, 'designation' => 'Rallonge électrique 3 x 1.5 25 m', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 8, 'designation' => 'Transformateur 220 / 24 V', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Outils de coupe
        ['categorie_id' => 7, 'designation' => 'Scie à métaux', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 7, 'designation' => 'Ensemble scie trépaud Ø 25', 'observations' => '', 'quantite_standard' => 1],
        
        // Récipients
        ['categorie_id' => 7, 'designation' => 'Seau 20 l', 'observations' => '', 'quantite_standard' => 1],
        
        // Outils électriques
        ['categorie_id' => 4, 'designation' => 'Vérificateur d\'absence de tension', 'observations' => '', 'quantite_standard' => 1, 'maintenance_requise' => 1],
        
        // Tournevis monteur
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 3.5 x 100', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 5.5 x 150', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé 6.5 x 200', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé cruciforme 2 x 100', 'observations' => '', 'quantite_standard' => 1],
        ['categorie_id' => 2, 'designation' => 'Tournevis isolé cruciforme 3 x 150', 'observations' => '', 'quantite_standard' => 1],
        
        // Rangement
        ['categorie_id' => 7, 'designation' => 'Caisse à outil', 'observations' => '', 'quantite_standard' => 1]
    ];
    
    // Fonction pour insérer les templates
    function insertTemplates($db, $templates, $profil_name) {
        echo "Insertion des templates pour le profil: $profil_name\n";
        
        // Récupérer l'ID du profil
        $stmt = $db->prepare("SELECT id FROM outillage_profils WHERE nom = ?");
        $stmt->execute([$profil_name]);
        $profil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profil) {
            echo "Erreur: Profil $profil_name non trouvé\n";
            return;
        }
        
        $profil_id = $profil['id'];
        $count = 0;
        
        foreach ($templates as $template) {
            // Insérer le template
            $sql = "INSERT INTO outillage_templates (categorie_id, designation, marque, modele, observations, quantite_standard, maintenance_requise) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $template['categorie_id'],
                $template['designation'],
                $template['marque'] ?? null,
                $template['modele'] ?? null,
                $template['observations'] ?? null,
                $template['quantite_standard'],
                $template['maintenance_requise'] ?? 0
            ]);
            
            if ($result) {
                $template_id = $db->lastInsertId();
                
                // Lier le template au profil
                $sql_profil = "INSERT INTO outillage_profil_templates (profil_id, template_id, quantite, obligatoire) 
                              VALUES (?, ?, ?, 1)";
                
                $stmt_profil = $db->prepare($sql_profil);
                $stmt_profil->execute([
                    $profil_id,
                    $template_id,
                    $template['quantite_standard']
                ]);
                
                $count++;
            } else {
                echo "Erreur lors de l'insertion: " . $template['designation'] . "\n";
            }
        }
        
        echo "Templates insérés pour $profil_name: $count\n";
    }
    
    // Insertion des templates
    insertTemplates($db, $templates_technicien, 'technicien');
    insertTemplates($db, $templates_monteur, 'monteur');
    
    echo "Insertion terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>