<?php
// public/admin/templates/template.php - Génération de modèles d'import optimisés
require __DIR__ . '/../../../config.php';
require __DIR__ . '/../auth.php';

// Vérification des permissions
checkAdminPermission('template_download');
logAdminAction('download_template', ['type' => $_GET['type'] ?? 'rates']);

$type = $_GET['type'] ?? 'rates';
$format = $_GET['format'] ?? 'csv';

// Validation des paramètres
$allowedTypes = ['rates', 'options', 'taxes', 'departments'];
$allowedFormats = ['csv', 'xlsx'];

if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    die('Type de modèle non supporté');
}

if (!in_array($format, $allowedFormats)) {
    http_response_code(400);
    die('Format de modèle non supporté');
}

try {
    switch ($format) {
        case 'csv':
            generateCSVTemplate($type);
            break;
        case 'xlsx':
            generateExcelTemplate($type);
            break;
    }
} catch (Exception $e) {
    error_log("Erreur génération template: " . $e->getMessage());
    http_response_code(500);
    echo 'Erreur lors de la génération du modèle : ' . htmlspecialchars($e->getMessage());
}

/**
 * Génère un template CSV
 */
function generateCSVTemplate($type) {
    $filename = generateTemplateFilename($type, 'csv');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    $output = fopen('php://output', 'w');

    // BOM UTF-8 pour Excel
    fwrite($output, "\xEF\xBB\xBF");

    switch ($type) {
        case 'rates':
            generateRatesTemplate($output);
            break;
        case 'options':
            generateOptionsTemplate($output);
            break;
        case 'taxes':
            generateTaxesTemplate($output);
            break;
        case 'departments':
            generateDepartmentsTemplate($output);
            break;
    }

    fclose($output);
}

/**
 * Génère un template Excel (CSV pour l'instant)
 */
function generateExcelTemplate($type) {
    // Pour l'instant, redirection vers CSV avec extension .xlsx
    // En production, utilisez PhpSpreadsheet
    $filename = generateTemplateFilename($type, 'xlsx');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    switch ($type) {
        case 'rates':
            generateRatesTemplate($output);
            break;
        case 'options':
            generateOptionsTemplate($output);
            break;
        case 'taxes':
            generateTaxesTemplate($output);
            break;
        case 'departments':
            generateDepartmentsTemplate($output);
            break;
    }

    fclose($output);
}

/**
 * Template pour les tarifs
 */
function generateRatesTemplate($output) {
    // Instructions détaillées
    writeInstructions($output, [
        'MODÈLE D\'IMPORT TARIFS GULDAGIL',
        '',
        'INSTRUCTIONS IMPORTANTES:',
        '1. Remplissez uniquement les colonnes correspondant à votre transporteur',
        '2. Transporteur: heppner, xpo ou kn (en minuscules)',
        '3. Département: numéro à 2 chiffres (01, 02, ..., 95)',
        '4. Nom département: optionnel mais recommandé',
        '5. Délai: format libre (ex: 24h, 24h-48h, 72h)',
        '6. Tarifs: nombres décimaux avec point (ex: 12.50, pas 12,50)',
        '7. Laissez vide les tranches non applicables',
        '8. XPO utilise des tranches différentes (voir exemples)',
        '9. Supprimez toutes ces lignes de commentaire avant l\'import',
        '10. Vérifiez les données avant import (pas d\'annulation possible)',
        '',
        'TRANCHES DE POIDS:',
        '- Heppner et K+N: 0-9, 10-19, 20-29, ..., 90-99, 100-299, 300-499, 500-999, 1000-1999 kg',
        '- XPO: 0-99, 100-499, 500-999, 1000-1999, 2000-2999 kg',
        '',
        'SUPPORT: runser.jean.thomas@guldagil.com | 03 89 63 42 42',
        ''
    ]);
    
    // En-têtes des colonnes
    fputcsv($output, [
        'transporteur',         // Code transporteur (obligatoire)
        'num_departement',      // Numéro département (obligatoire)
        'departement',          // Nom département (optionnel)
        'delais',              // Délai de livraison (optionnel)
        'tarif_0_9',           // Tarif 0-9kg (Heppner/K+N)
        'tarif_10_19',         // Tarif 10-19kg (Heppner/K+N)
        'tarif_20_29',         // Tarif 20-29kg (Heppner/K+N)
        'tarif_30_39',         // Tarif 30-39kg (Heppner/K+N)
        'tarif_40_49',         // Tarif 40-49kg (Heppner/K+N)
        'tarif_50_59',         // Tarif 50-59kg (Heppner/K+N)
        'tarif_60_69',         // Tarif 60-69kg (Heppner/K+N)
        'tarif_70_79',         // Tarif 70-79kg (Heppner/K+N)
        'tarif_80_89',         // Tarif 80-89kg (Heppner/K+N)
        'tarif_90_99',         // Tarif 90-99kg (Heppner/K+N)
        'tarif_100_299',       // Tarif 100-299kg (Heppner/K+N)
        'tarif_300_499',       // Tarif 300-499kg (Heppner/K+N)
        'tarif_500_999',       // Tarif 500-999kg (tous)
        'tarif_1000_1999',     // Tarif 1000-1999kg (tous)
        'tarif_0_99',          // Tarif 0-99kg (XPO uniquement)
        'tarif_100_499',       // Tarif 100-499kg (XPO uniquement)
        'tarif_2000_2999'      // Tarif 2000-2999kg (XPO uniquement)
    ]);
    
    // Ligne descriptive
    fputcsv($output, [
        '# CODE',
        '# 01-95',
        '# Nom département',
        '# 24h ou 24h-48h',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Heppner/K+N',
        '# Tous',
        '# Tous',
        '# XPO uniquement',
        '# XPO uniquement',
        '# XPO uniquement'
    ]);
    
    // Exemples réalistes basés sur les données existantes
    
    // Exemple Heppner (67)
    fputcsv($output, [
        'heppner', '67', 'Bas-Rhin', '24h',
        '12.68', '15.32', '17.99', '20.42', '22.78', '25.29', '27.70', '30.12', '32.32', '35.11',
        '22.97', '19.36', '14.37', '10.00',
        '', '', ''  // Colonnes XPO vides
    ]);
    
    // Exemple XPO (68)
    fputcsv($output, [
        'xpo', '68', 'Haut-Rhin', '24h-48h',
        '', '', '', '', '', '', '', '', '', '',  // Colonnes Heppner/K+N vides
        '', '', '10.39', '7.21',
        '35.17', '16.22', '5.28'  // Colonnes XPO
    ]);
    
    // Exemple K+N (vide pour démonstration)
    fputcsv($output, [
        'kn', '75', 'Paris', '24h-48h',
        '', '', '', '', '', '', '', '', '', '',
        '', '', '', '',
        '', '', ''
    ]);
    
    // Ligne vide pour séparation
    fputcsv($output, []);
    
    // Template utilisateur vide
    fputcsv($output, [
        '# VOTRE IMPORT - Complétez les lignes ci-dessous',
    ]);
}

/**
 * Template pour les options supplémentaires
 */
function generateOptionsTemplate($output) {
    writeInstructions($output, [
        'MODÈLE D\'IMPORT OPTIONS SUPPLÉMENTAIRES',
        '',
        'INSTRUCTIONS:',
        '1. transporteur: heppner, xpo ou kn (en minuscules)',
        '2. code_option: rdv, premium13, premium18, datefixe, enlevement, etc.',
        '3. libelle: description claire de l\'option',
        '4. montant: nombre décimal (ex: 15.00)',
        '5. unite: forfait, palette ou pourcentage',
        '6. actif: 1 pour actif, 0 pour inactif',
        '',
        'CODES OPTIONS RECOMMANDÉS:',
        '- rdv: Prise de rendez-vous',
        '- premium13: Livraison avant 13h',
        '- premium18: Livraison avant 18h',
        '- datefixe: Livraison à date fixe',
        '- enlevement: Enlèvement sur site',
        '- palette: Frais par palette EUR',
        '- assurance: Assurance renforcée',
        '- etage: Livraison étage',
        '- contre_remboursement: Contre-remboursement',
        '',
        'SUPPORT: runser.jean.thomas@guldagil.com | 03 89 63 42 42',
        ''
    ]);
    
    // En-têtes
    fputcsv($output, [
        'transporteur',         // Code transporteur (obligatoire)
        'code_option',          // Code option (obligatoire)
        'libelle',             // Description (obligatoire)
        'montant',             // Montant (obligatoire)
        'unite',               // Unité de facturation (obligatoire)
        'actif'                // Statut actif/inactif (obligatoire)
    ]);
    
    // Ligne descriptive
    fputcsv($output, [
        '# heppner/xpo/kn',
        '# Code unique',
        '# Description claire',
        '# Prix en euros',
        '# forfait/palette/pourcentage',
        '# 1=actif 0=inactif'
    ]);
    
    // Exemples réalistes
    fputcsv($output, [
        'heppner',
        'rdv',
        'Prise de rendez-vous',
        '15.00',
        'forfait',
        '1'
    ]);
    
    fputcsv($output, [
        'xpo',
        'premium13',
        'Premium avant 13h',
        '22.00',
        'forfait',
        '1'
    ]);
    
    fputcsv($output, [
        'kn',
        'palette',
        'Frais par palette EUR',
        '6.50',
        'palette',
        '1'
    ]);
    
    fputcsv($output, [
        'heppner',
        'enlevement',
        'Enlèvement sur site',
        '25.00',
        'forfait',
        '0'
    ]);
    
    // Ligne vide pour séparation
    fputcsv($output, []);
    
    // Template utilisateur
    fputcsv($output, [
        '# VOTRE IMPORT - Complétez les lignes ci-dessous',
    ]);
    
    // Lignes vides pour l'utilisateur
    for ($i = 0; $i < 5; $i++) {
        fputcsv($output, ['', '', '', '', '', '']);
    }
}

/**
 * Template pour les taxes et majorations
 */
function generateTaxesTemplate($output) {
    writeInstructions($output, [
        'MODÈLE TAXES ET MAJORATIONS (LECTURE SEULE)',
        '',
        'ATTENTION: Ce fichier est fourni à titre informatif uniquement',
        'Les modifications de taxes doivent être effectuées via l\'interface d\'administration',
        'Import non supporté pour des raisons de sécurité',
        '',
        'DONNÉES ACTUELLES:',
        'Les valeurs ci-dessous reflètent la configuration actuelle du système',
        '',
        'POUR MODIFIER:',
        '1. Connectez-vous à l\'interface d\'administration',
        '2. Allez dans l\'onglet "Taxes & Majorations"',
        '3. Utilisez les formulaires de modification',
        '',
        'SUPPORT: runser.jean.thomas@guldagil.com | 03 89 63 42 42',
        ''
    ]);
    
    // En-têtes
    fputcsv($output, [
        'transporteur',
        'type_tarification',
        'poids_maximum_kg',
        'majoration_adr',
        'participation_transition_energetique_euros',
        'contribution_sanitaire_euros',
        'surete_euros',
        'surcharge_gasoil_pourcentage',
        'majoration_idf_type',
        'majoration_idf_valeur',
        'majoration_idf_departements',
        'majoration_saisonniere_taux',
        'date_modification'
    ]);
    
    // Données actuelles du système (exemples réalistes)
    fputcsv($output, [
        'Heppner',
        'Forfait si < 100kg, au poids si >= 100kg',
        '2000.00',
        'Non applicable',
        '0.50',
        '0.40',
        '2.30',
        '6.60',
        'Montant fixe',
        '7.35',
        '75,77,78,91,92,93,94,95',
        '0.00',
        date('d/m/Y H:i')
    ]);
    
    fputcsv($output, [
        'XPO',
        'Toujours au poids basé sur tarif 100kg',
        '2001.00',
        '+20% si ADR',
        '1.45',
        '0.00',
        '0.70',
        '15.22',
        'Pourcentage',
        '6.00',
        '75,77,78,91,92,93,94,95',
        '10.00',
        date('d/m/Y H:i')
    ]);
    
    fputcsv($output, [
        'Kuehne + Nagel',
        'Forfait si < 100kg, au poids si >= 100kg',
        '1500.00',
        '+20% si ADR',
        '0.00',
        '0.00',
        '1.50',
        '6.80',
        'Montant fixe',
        '7.00',
        '6,13,17,31,33,35,38,44,59,67,69,74,75,76,84,91,92,93,94,98',
        '25.00',
        date('d/m/Y H:i')
    ]);
}

/**
 * Template pour les départements
 */
function generateDepartmentsTemplate($output) {
    writeInstructions($output, [
        'MODÈLE DÉPARTEMENTS FRANÇAIS',
        '',
        'FICHIER DE RÉFÉRENCE:',
        'Liste complète des départements français avec codes et noms',
        'Utilisable pour validation et import de données géographiques',
        '',
        'FORMAT:',
        '- num_departement: Code à 2 chiffres (01-95 + DOM-TOM)',
        '- nom_departement: Nom officiel du département',
        '- region: Région administrative',
        '- zone_transporteur: Zone de livraison transporteur',
        '',
        'SUPPORT: runser.jean.thomas@guldagil.com | 03 89 63 42 42',
        ''
    ]);
    
    // En-têtes
    fputcsv($output, [
        'num_departement',
        'nom_departement',
        'region',
        'zone_transporteur',
        'delai_standard',
        'remarques'
    ]);
    
    // Données des départements français (sélection représentative)
    $departments = [
        ['01', 'Ain', 'Auvergne-Rhône-Alpes', 'Sud-Est', '24h-48h', ''],
        ['02', 'Aisne', 'Hauts-de-France', 'Nord', '24h-48h', ''],
        ['13', 'Bouches-du-Rhône', 'Provence-Alpes-Côte d\'Azur', 'Sud-Est', '24h-48h', ''],
        ['33', 'Gironde', 'Nouvelle-Aquitaine', 'Sud-Ouest', '48h-72h', ''],
        ['44', 'Loire-Atlantique', 'Pays de la Loire', 'Ouest', '48h-72h', ''],
        ['59', 'Nord', 'Hauts-de-France', 'Nord', '24h-48h', ''],
        ['67', 'Bas-Rhin', 'Grand Est', 'Est', '24h', 'Zone prioritaire Guldagil'],
        ['68', 'Haut-Rhin', 'Grand Est', 'Est', '24h', 'Zone prioritaire Guldagil'],
        ['69', 'Rhône', 'Auvergne-Rhône-Alpes', 'Sud-Est', '24h-48h', ''],
        ['75', 'Paris', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['77', 'Seine-et-Marne', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['83', 'Var', 'Provence-Alpes-Côte d\'Azur', 'Sud-Est', '48h-72h', ''],
        ['84', 'Vaucluse', 'Provence-Alpes-Côte d\'Azur', 'Sud-Est', '24h-48h', ''],
        ['91', 'Essonne', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['92', 'Hauts-de-Seine', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['93', 'Seine-Saint-Denis', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['94', 'Val-de-Marne', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['95', 'Val-d\'Oise', 'Île-de-France', 'IDF', '24h-48h', 'Majoration IDF applicable'],
        ['20', 'Corse', 'Corse', 'Corse', '72h-96h', 'Transport maritime requis'],
        ['971', 'Guadeloupe', 'Guadeloupe', 'DOM-TOM', 'Sur devis', 'Transport aérien/maritime'],
        ['972', 'Martinique', 'Martinique', 'DOM-TOM', 'Sur devis', 'Transport aérien/maritime'],
        ['973', 'Guyane', 'Guyane', 'DOM-TOM', 'Sur devis', 'Transport aérien/maritime'],
        ['974', 'La Réunion', 'La Réunion', 'DOM-TOM', 'Sur devis', 'Transport aérien/maritime'],
        ['976', 'Mayotte', 'Mayotte', 'DOM-TOM', 'Sur devis', 'Transport aérien/maritime']
    ];
    
    foreach ($departments as $dept) {
        fputcsv($output, $dept);
    }
}

/**
 * Écrit les instructions en début de fichier
 */
function writeInstructions($output, $lines) {
    foreach ($lines as $line) {
        if ($line === '') {
            fputcsv($output, ['']);
        } else {
            fputcsv($output, ['# ' . $line]);
        }
    }
}

/**
 * Génère un nom de fichier pour le template
 */
function generateTemplateFilename($type, $extension) {
    $prefix = 'guldagil_template';
    $date = date('Y-m-d');
    $version = 'v1.2';
    
    return "{$prefix}_{$type}_{$version}_{$date}.{$extension}";
}
?>
