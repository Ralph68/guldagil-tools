<?php
// ajax-calculate.php
// Version complète avec nouvelle logique de calcul

require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');

// Démarrer la session pour l'historique
session_start();
if (!isset($_SESSION['historique'])) {
    $_SESSION['historique'] = [];
}

$transport = new Transport($db);
$carriers = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne+Nagel'];

// Seuils pour les alertes côté client
$response = [
    'success'      => false,
    'results'      => [],
    'best'         => null,
    'bestCarrier'  => null,
    'errors'       => [],
    'debug'        => [],
    'affretement'  => false,
    'message'      => '',
    'thresholds'   => [100, 1000, 2000, 3000],
    'alerts'       => [],
    'fallback'     => null
];

// Récupération des paramètres
$dep = $_POST['departement'] ?? '';
$poids = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$type = $_POST['type'] ?? '';
$adr  = $_POST['adr'] ?? '';
$option_sup = $_POST['option_sup'] ?? 'standard';
$enlevement = isset($_POST['enlevement']) && $_POST['enlevement'] === '1';
$palettes   = (isset($_POST['palettes']) && $_POST['palettes'] !== '') ? (int)$_POST['palettes'] : 0;

// Validation renforcée
if (!preg_match('/^[0-9]{2}$/', $dep)) {
    $response['errors'][] = "Le département doit être constitué de 2 chiffres";
}
if ($poids === null || $poids <= 0) {
    $response['errors'][] = "Le poids doit être supérieur à 0";
}
if (!in_array($type, ['colis', 'palette'], true)) {
    $response['errors'][] = "Le type d'envoi est invalide";
}
if (!in_array($adr, ['oui', 'non'], true)) {
    $response['errors'][] = "Le choix ADR est requis";
}

// Si erreurs, on renvoie directement
if (!empty($response['errors'])) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Gestion de l'affrètement si trop lourd
if ($poids > 3000) {
    $response['affretement'] = true;
    $response['message']     = "Pour un poids supérieur à 3000 kg, veuillez contacter le service achat au 03 89 63 42 42 pour un affrètement.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // =============================================================================
    // CALCUL PRINCIPAL POUR TOUS LES TRANSPORTEURS
    // =============================================================================
    
    $results = [];
    $allDebug = [];
    
    foreach ($carriers as $carrier => $name) {
        $carrierResult = calculateCarrierPrice($db, $carrier, $dep, $poids, $type, $adr, $option_sup, $enlevement, $palettes);
        $results[$carrier] = $carrierResult['price'];
        $allDebug[$carrier] = $carrierResult['debug'];
    }
    
    // =============================================================================
    // LOGIQUE DE REMISE EN PALETTE (si type = colis)
    // =============================================================================
    
    if ($type === 'colis') {
        $fallbackResults = checkPaletteFallback($db, $dep, $poids, $adr, $option_sup, $enlevement, $palettes, $results['heppner']);
        
        if ($fallbackResults['hasBetter']) {
            $response['fallback'] = $fallbackResults;
            // Remplacer le résultat du transporteur le moins cher
            $results[$fallbackResults['carrier']] = $fallbackResults['price'];
            $allDebug[$fallbackResults['carrier']]['remise_palette'] = true;
        }
    }
    
    // =============================================================================
    // ALERTES DE SEUILS (payant pour déclarer XXX kg)
    // =============================================================================
    
    $response['alerts'] = generateThresholdAlerts($results, $poids, [100, 1000, 2000, 3000]);
    
    // =============================================================================
    // SÉLECTION DU MEILLEUR TARIF
    // =============================================================================
    
    $response['results'] = $results;
    $response['debug'] = $allDebug;
    
    // Filtrer les tarifs valides
    $validResults = array_filter($results, fn($price) => $price !== null);
    
    if (!empty($validResults)) {
        $response['best'] = min($validResults);
        $response['bestCarrier'] = array_search($response['best'], $results);
        
        // Enregistrement dans l'historique
        $entry = [
            'date'         => date('Y-m-d H:i:s'),
            'departement'  => $dep,
            'poids'        => $poids,
            'type'         => $type,
            'adr'          => $adr,
            'option'       => $option_sup,
            'palettes'     => $palettes,
            'best_carrier' => $carriers[$response['bestCarrier']] ?? $response['bestCarrier'],
            'best_price'   => $response['best'],
        ];
        array_unshift($_SESSION['historique'], $entry);
        $_SESSION['historique'] = array_slice($_SESSION['historique'], 0, 10);
        $response['success'] = true;
    }
    
} catch (Exception $e) {
    $response['errors'][] = "Erreur lors du calcul : " . $e->getMessage();
}

// Formattage des résultats si succès
if ($response['success']) {
    $formatted = [];
    foreach ($response['results'] as $carrier => $price) {
        $formatted[$carrier] = [
            'name'      => $carriers[$carrier] ?? $carrier,
            'price'     => $price,
            'formatted' => $price !== null ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
            'debug'     => $response['debug'][$carrier] ?? null,
        ];
    }
    $response['formatted'] = $formatted;
    $response['poids'] = $poids;
}

// Envoi de la réponse JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// =============================================================================
// FONCTIONS DE CALCUL
// =============================================================================

/**
 * Calcule le prix pour un transporteur donné
 */
function calculateCarrierPrice($db, $carrier, $dep, $poids, $type, $adr, $option_sup, $enlevement, $palettes) {
    $debug = [
        'carrier' => $carrier,
        'poids' => $poids,
        'type' => $type,
        'departement' => $dep
    ];
    
    try {
        // 1. Récupérer les informations du transporteur
        $carrierInfo = getCarrierInfo($db, $carrier);
        if (!$carrierInfo) {
            return ['price' => null, 'debug' => array_merge($debug, ['error' => 'Transporteur non trouvé'])];
        }
        
        // 2. Vérifications de compatibilité
        if ($poids > $carrierInfo['poids_maximum']) {
            return ['price' => null, 'debug' => array_merge($debug, ['error' => "Poids dépassé (max: {$carrierInfo['poids_maximum']} kg)"])];
        }
        
        // XPO et K+N n'acceptent que les palettes
        if (($carrier === 'xpo' || $carrier === 'kn') && $type === 'colis') {
            return ['price' => null, 'debug' => array_merge($debug, ['error' => 'Colis non accepté par ce transporteur'])];
        }
        
        // 3. Calcul du tarif de base
        $tarifBase = getBaseTariff($db, $carrier, $dep, $poids);
        if ($tarifBase === null) {
            return ['price' => null, 'debug' => array_merge($debug, ['error' => 'Aucun tarif trouvé'])];
        }
        
        $debug['tarif_base_brut'] = $tarifBase;
        
        // 4. Logique forfait vs poids pour < 100kg
        $finalTarif = $tarifBase;
        if ($poids < 100) {
            $tarif100kg = getBaseTariff($db, $carrier, $dep, 100);
            if ($tarif100kg !== null) {
                $tarifAuPoids = ($tarif100kg / 100) * $poids;
                $debug['tarif_forfait'] = $tarifBase;
                $debug['tarif_au_poids'] = $tarifAuPoids;
                
                // Pour XPO : toujours au poids même < 100kg selon la règle
                if ($carrier === 'xpo' || $tarifAuPoids < $tarifBase) {
                    $finalTarif = $tarifAuPoids;
                    $debug['methode_retenue'] = 'au_poids';
                } else {
                    $debug['methode_retenue'] = 'forfait';
                }
            }
        } else {
            // >= 100kg : calcul au poids
            $tarif100kg = getBaseTariff($db, $carrier, $dep, 100);
            if ($tarif100kg !== null) {
                $multiplicateur = $poids / 100;
                $finalTarif = $tarif100kg * $multiplicateur;
                $debug['multiplicateur'] = $multiplicateur;
                $debug['methode_retenue'] = 'au_poids';
            }
        }
        
        $debug['tarif_apres_base'] = $finalTarif;
        
        // 5. Majoration IDF
        $majIdf = getMajorationIDF($db, $carrier, $dep, $finalTarif);
        $finalTarif += $majIdf;
        if ($majIdf > 0) {
            $debug['majoration_idf'] = $majIdf;
        }
        
        // 6. Majoration saisonnière
        $majSaison = getMajorationSaisonniere($db, $carrier, $dep, $finalTarif);
        $finalTarif += $majSaison;
        if ($majSaison > 0) {
            $debug['majoration_saisonniere'] = $majSaison;
        }
        
        // 7. Majoration ADR
        $majAdr = getMajorationADR($db, $carrier, $adr, $finalTarif);
        $finalTarif += $majAdr;
        if ($majAdr > 0) {
            $debug['majoration_adr'] = $majAdr;
        }
        
        // 8. Options supplémentaires
        $coutOption = getOptionCost($db, $carrier, $option_sup);
        $finalTarif += $coutOption;
        if ($coutOption > 0) {
            $debug['option_' . $option_sup] = $coutOption;
        }
        
        // 9. Enlèvement
        if ($enlevement) {
            $coutEnlevement = getOptionCost($db, $carrier, 'enlevement');
            $finalTarif += $coutEnlevement;
            if ($coutEnlevement > 0) {
                $debug['enlevement'] = $coutEnlevement;
            }
        }
        
        // 10. Palettes EUR
        if ($type === 'palette' && $palettes > 0) {
            $coutPalettes = getPaletteCost($carrier, $palettes);
            $finalTarif += $coutPalettes;
            if ($coutPalettes > 0) {
                $debug['palettes_eur'] = $coutPalettes;
            }
        }
        
        // 11. Taxes fixes
        $taxes = getTaxes($db, $carrier);
        $finalTarif += $taxes['montant'];
        if ($taxes['montant'] > 0) {
            $debug = array_merge($debug, $taxes['detail']);
        }
        
        // 12. Surcharge gasoil (appliquée en multiplicateur à la fin)
        $surchargeGasoil = getSurchargeGasoil($db, $carrier);
        if ($surchargeGasoil > 0) {
            $montantSurcharge = $finalTarif * $surchargeGasoil;
            $finalTarif *= (1 + $surchargeGasoil);
            $debug['surcharge_gasoil'] = $montantSurcharge;
        }
        
        $debug['tarif_final'] = round($finalTarif, 2);
        
        return ['price' => round($finalTarif, 2), 'debug' => $debug];
        
    } catch (Exception $e) {
        return ['price' => null, 'debug' => array_merge($debug, ['error' => $e->getMessage()])];
    }
}

/**
 * Vérifie si une remise en palette est intéressante pour un colis
 */
function checkPaletteFallback($db, $dep, $poids, $adr, $option_sup, $enlevement, $palettes, $heppnerPrice) {
    $result = ['hasBetter' => false];
    
    if ($heppnerPrice === null) return $result;
    
    // Tester XPO et K+N en mode palette
    foreach (['xpo', 'kn'] as $carrier) {
        $paletteResult = calculateCarrierPrice($db, $carrier, $dep, $poids, 'palette', $adr, $option_sup, $enlevement, max(1, $palettes));
        
        if ($paletteResult['price'] !== null && $paletteResult['price'] < $heppnerPrice) {
            $result = [
                'hasBetter' => true,
                'carrier' => $carrier,
                'price' => $paletteResult['price'],
                'savings' => $heppnerPrice - $paletteResult['price'],
                'message' => "Remise en palette disponible"
            ];
            // Prendre le premier trouvé (ou on pourrait prendre le moins cher)
            break;
        }
    }
    
    return $result;
}

/**
 * Génère les alertes de seuils de poids
 */
function generateThresholdAlerts($results, $poids, $thresholds) {
    $alerts = [];
    
    foreach ($results as $carrier => $price) {
        if ($price === null) continue;
        
        $unitRate = $price / $poids;
        
        foreach ($thresholds as $threshold) {
            // Alerte si on est à 80% du seuil
            if ($poids >= $threshold * 0.8 && $poids < $threshold) {
                $thresholdPrice = $unitRate * $threshold;
                if ($thresholdPrice < $price) {
                    $alerts[] = [
                        'carrier' => $carrier,
                        'threshold' => $threshold,
                        'current_price' => $price,
                        'threshold_price' => round($thresholdPrice, 2),
                        'savings' => round($price - $thresholdPrice, 2),
                        'message' => "Payant pour → déclarer {$threshold} kg"
                    ];
                }
            }
        }
    }
    
    return $alerts;
}

// =============================================================================
// FONCTIONS D'ACCÈS AUX DONNÉES
// =============================================================================

function getCarrierInfo($db, $carrier) {
    $carrierNames = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO', 
        'kn' => 'Kuehne + Nagel'
    ];
    
    $sql = "SELECT * FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBaseTariff($db, $carrier, $dep, $poids) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$carrier] ?? null;
    if (!$table) return null;
    
    // Déterminer la colonne selon le transporteur et le poids
    if ($carrier === 'xpo') {
        $column = getXPOTariffColumn($poids);
    } else {
        $column = getHeppnerKNTariffColumn($poids);
    }
    
    if (!$column) return null;
    
    $sql = "SELECT `$column` FROM `$table` WHERE num_departement = :dep AND `$column` IS NOT NULL AND `$column` > 0 LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':dep' => $dep]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (float)$result[$column] : null;
}

function getXPOTariffColumn($poids) {
    if ($poids <= 99.99) return 'tarif_0_99';
    if ($poids <= 499.99) return 'tarif_100_499';
    if ($poids <= 999.99) return 'tarif_500_999';
    if ($poids <= 1999.99) return 'tarif_1000_1999';
    if ($poids <= 2999.99) return 'tarif_2000_2999';
    return null;
}

function getHeppnerKNTariffColumn($poids) {
    if ($poids <= 9.99) return 'tarif_0_9';
    if ($poids <= 19.99) return 'tarif_10_19';
    if ($poids <= 29.99) return 'tarif_20_29';
    if ($poids <= 39.99) return 'tarif_30_39';
    if ($poids <= 49.99) return 'tarif_40_49';
    if ($poids <= 59.99) return 'tarif_50_59';
    if ($poids <= 69.99) return 'tarif_60_69';
    if ($poids <= 79.99) return 'tarif_70_79';
    if ($poids <= 89.99) return 'tarif_80_89';
    if ($poids <= 99.99) return 'tarif_90_99';
    if ($poids <= 299.99) return 'tarif_100_299';
    if ($poids <= 499.99) return 'tarif_300_499';
    if ($poids <= 999.99) return 'tarif_500_999';
    if ($poids <= 1999.99) return 'tarif_1000_1999';
    return null;
}

function getMajorationIDF($db, $carrier, $dep, $montantBase) {
    $carrierNames = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne + Nagel'];
    
    $sql = "SELECT majoration_idf_type, majoration_idf_valeur, majoration_idf_departements 
            FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || !$row['majoration_idf_departements']) return 0;
    
    $departements = explode(',', $row['majoration_idf_departements']);
    if (!in_array($dep, $departements)) return 0;
    
    if ($row['majoration_idf_type'] === 'Pourcentage') {
        return $montantBase * ($row['majoration_idf_valeur'] / 100);
    } elseif ($row['majoration_idf_type'] === 'Montant fixe') {
        return $row['majoration_idf_valeur'];
    }
    
    return 0;
}

function getMajorationSaisonniere($db, $carrier, $dep, $montantBase) {
    $carrierNames = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne + Nagel'];
    
    $sql = "SELECT * FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || !$row['majoration_saisonniere_applicable']) return 0;
    
    // Vérifier le département
    if ($row['majoration_saisonniere_departements']) {
        $departements = explode(',', $row['majoration_saisonniere_departements']);
        if (!in_array($dep, $departements)) return 0;
    }
    
    // Vérifier la période (TODO: implémenter quand les dates seront définies)
    // Pour l'instant on applique si le taux est défini
    if ($row['majoration_saisonniere_taux'] > 0) {
        return $montantBase * ($row['majoration_saisonniere_taux'] / 100);
    }
    
    return 0;
}

function getMajorationADR($db, $carrier, $adr, $montantBase) {
    if ($adr !== 'oui') return 0;
    
    $carrierNames = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne + Nagel'];
    
    $sql = "SELECT majoration_adr FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) return 0;
    
    // Heppner : pas d'incidence
    if ($carrier === 'heppner' || $row['majoration_adr'] === 'Non applicable') {
        return 0;
    }
    
    // XPO et K+N : +20%
    if ($row['majoration_adr'] === '+20% si ADR') {
        return $montantBase * 0.20;
    }
    
    return 0;
}

function getOptionCost($db, $carrier, $optionCode) {
    if ($optionCode === 'standard') return 0;
    
    $sql = "SELECT montant FROM gul_options_supplementaires 
            WHERE transporteur = :carrier AND code_option = :option AND actif = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':carrier' => $carrier, ':option' => $optionCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (float)$result['montant'] : 0;
}

function getPaletteCost($carrier, $nbPalettes) {
    $couts = [
        'xpo' => 1.80,
        'heppner' => 0,
        'kn' => 6.50
    ];
    
    return ($couts[$carrier] ?? 0) * $nbPalettes;
}

function getTaxes($db, $carrier) {
    $carrierNames = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne + Nagel'];
    
    $sql = "SELECT participation_transition_energetique, contribution_sanitaire, surete 
            FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = 0;
    $detail = [];
    
    if ($row) {
        if ($row['participation_transition_energetique'] > 0) {
            $total += $row['participation_transition_energetique'];
            $detail['participation_transition_energetique'] = $row['participation_transition_energetique'];
        }
        if ($row['contribution_sanitaire'] > 0) {
            $total += $row['contribution_sanitaire'];
            $detail['contribution_sanitaire'] = $row['contribution_sanitaire'];
        }
        if ($row['surete'] > 0) {
            $total += $row['surete'];
            $detail['surete'] = $row['surete'];
        }
    }
    
    return ['montant' => $total, 'detail' => $detail];
}

function getSurchargeGasoil($db, $carrier) {
    $carrierNames = ['heppner' => 'Heppner', 'xpo' => 'XPO', 'kn' => 'Kuehne + Nagel'];
    
    $sql = "SELECT surcharge_gasoil FROM gul_taxes_transporteurs WHERE transporteur = :name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $carrierNames[$carrier]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row && $row['surcharge_gasoil'] > 0 ? (float)$row['surcharge_gasoil'] : 0;
}
?>
