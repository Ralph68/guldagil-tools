<?php
// public/admin/template.php - Génération du modèle d'import
require __DIR__ . '/../../config.php';

$type = $_GET['type'] ?? 'rates';

$filename = "guldagil_template_" . $type . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

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
    default:
        generateRatesTemplate($output);
        break;
}

fclose($output);

function generateRatesTemplate($output) {
    // Instructions en commentaire
    fputcsv($output, ['# MODÈLE D\'IMPORT TARIFS GULDAGIL']);
    fputcsv($output, ['# Instructions:']);
    fputcsv($output, ['# 1. Remplissez les colonnes avec vos données']);
    fputcsv($output, ['# 2. Le transporteur doit être: heppner, xpo ou kn']);
    fputcsv($output, ['# 3. Le département doit être un nombre à 2 chiffres (01-95)']);
    fputcsv($output, ['# 4. Les tarifs doivent être des nombres décimaux (ex: 12.50)']);
    fputcsv($output, ['# 5. Laissez vide les colonnes non applicables']);
    fputcsv($output, ['# 6. Supprimez ces lignes de commentaire avant l\'import']);
    fputcsv($output, []);
    
    // En-têtes
    fputcsv($output, [
        'transporteur',
        'num_departement',
        'departement',
        'delais',
        'tarif_0_9',
        'tarif_10_19',
        'tarif_20_29',
        'tarif_30_39',
        'tarif_40_49',
        'tarif_50_59',
        'tarif_60_69',
        'tarif_70_79',
        'tarif_80_89',
        'tarif_90_99',
        'tarif_100_299',
        'tarif_300_499',
        'tarif_500_999',
        'tarif_1000_1999'
    ]);
    
    // Exemples de données
    fputcsv($output, [
        'heppner',
        '67',
        'Bas-Rhin',
        '24h',
        '12.68',
        '15.32',
        '17.99',
        '20.42',
        '22.78',
        '25.29',
        '27.70',
        '30.12',
        '32.32',
        '35.11',
        '22.97',
        '19.36',
        '14.37',
        '10.00'
    ]);
    
    fputcsv($output, [
        'xpo',
        '67',
        'Bas-Rhin',
        '24h-48h',
        '35.17',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '18.49',
        '',
        '11.87',
        '8.22'
    ]);
    
    fputcsv($output, [
        'kn',
        '67',
        'Bas-Rhin',
        '24h-48h',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ]);
}

function generateOptionsTemplate($output) {
    // Instructions
    fputcsv($output, ['# MODÈLE D\'IMPORT OPTIONS SUPPLÉMENTAIRES']);
    fputcsv($output, ['# Instructions:']);
    fputcsv($output, ['# 1. transporteur: heppner, xpo ou kn']);
    fputcsv($output, ['# 2. code_option: rdv, premium13, premium18, datefixe, enlevement, etc.']);
    fputcsv($output, ['# 3. montant: nombre décimal (ex: 15.00)']);
    fputcsv($output, ['# 4. unite: forfait, palette ou pourcentage']);
    fputcsv($output, ['# 5. actif: 1 pour actif, 0 pour inactif']);
    fputcsv($output, []);
    
    // En-têtes
    fputcsv($output, [
        'transporteur',
        'code_option',
        'libelle',
        'montant',
        'unite',
        'actif'
    ]);
    
    // Exemples
    fputcsv($output, [
        'heppner',
        'rdv',
        'Prise de RDV',
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
        '0'
    ]);
}

function generateTaxesTemplate($output) {
    // Instructions
    fputcsv($output, ['# MODÈLE TAXES ET MAJORATIONS (lecture seule - modification via interface)']);
    fputcsv($output, ['# Ce modèle est fourni à titre informatif']);
    fputcsv($output, ['# Les modifications doivent être faites via l\'interface d\'administration']);
    fputcsv($output, []);
    
    // En-têtes
    fputcsv($output, [
        'transporteur',
        'type_tarification',
        'poids_maximum',
        'majoration_adr',
        'participation_transition_energetique',
        'contribution_sanitaire',
        'surete',
        'surcharge_gasoil'
    ]);
    
    // Données actuelles (à titre d'exemple)
    fputcsv($output, [
        'Heppner',
        'Forfait si < 100kg, au poids si >= 100kg',
        '2000',
        'Non applicable',
        '0.50',
        '0.40',
        '2.30',
        '0.0660'
    ]);
    
    fputcsv($output, [
        'XPO',
        'Toujours au poids basé sur 100kg',
        '2001',
        '+20% si ADR',
        '1.45',
        '0.00',
        '0.70',
        '0.1522'
    ]);
    
    fputcsv($output, [
        'Kuehne + Nagel',
        'Forfait si < 100kg, au poids si >= 100kg',
        '1500',
        '+20% si ADR',
        '0.00',
        '0.00',
        '1.50',
        '0.0680'
    ]);
}
?>
