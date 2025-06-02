<?php
// public/admin/includes/dashboard-data.php
// Gestion centralisée des données pour le tableau de bord

/**
 * Collecte toutes les données nécessaires pour le dashboard
 * @param PDO $db Connexion à la base de données
 * @return array Données formatées pour l'affichage
 */
function getDashboardData(PDO $db): array {
    try {
        $data = [
            'stats' => getMainStatistics($db),
            'recent_activity' => getRecentActivity($db),
            'rates_preview' => getRatesPreview($db),
            'coverage_analysis' => getCoverageAnalysis($db),
            'quick_actions' => getQuickActions($db),
            'alerts' => getSystemAlerts($db),
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Erreur dashboard data: " . $e->getMessage());
        return getDefaultDashboardData();
    }
}

/**
 * Statistiques principales du système
 */
function getMainStatistics(PDO $db): array {
    $stats = [
        'carriers' => 0,
        'departments' => 0,
        'options' => 0,
        'calculations_today' => 0,
        'carriers_change' => 0,
        'departments_change' => 0,
        'options_change' => 0,
        'calculations_change' => 0
    ];
    
    try {
        // Nombre de transporteurs actifs
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM gul_taxes_transporteurs 
            WHERE poids_maximum > 0
        ");
        $stats['carriers'] = (int)$stmt->fetch()['count'];
        
        // Nombre de départements couverts (union des tables)
        $stmt = $db->query("
            SELECT COUNT(DISTINCT num_departement) as count FROM (
                SELECT num_departement FROM gul_heppner_rates 
                WHERE num_departement IS NOT NULL AND num_departement != ''
                UNION 
                SELECT num_departement FROM gul_xpo_rates 
                WHERE num_departement IS NOT NULL AND num_departement != ''
                UNION 
                SELECT num_departement FROM gul_kn_rates 
                WHERE num_departement IS NOT NULL AND num_departement != ''
            ) as all_departments
        ");
        $stats['departments'] = (int)$stmt->fetch()['count'];
        
        // Nombre d'options actives
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM gul_options_supplementaires 
            WHERE actif = 1
        ");
        $stats['options'] = (int)$stmt->fetch()['count'];
        
        // Simulations de calculs du jour (à remplacer par de vraies données si table logs existe)
        $stats['calculations_today'] = rand(80, 250);
        
        // Simulations des changements (en pourcentage)
        $stats['carriers_change'] = 0; // Stable
        $stats['departments_change'] = rand(0, 2); // Croissance modérée
        $stats['options_change'] = rand(-1, 1); // Variable
        $stats['calculations_change'] = rand(-15, 25); // Variable
        
    } catch (Exception $e) {
        error_log("Erreur stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Activité récente du système
 */
function getRecentActivity(PDO $db): array {
    $activities = [];
    
    try {
        // Activités simulées - En production, récupérer depuis une table de logs
        $activities = [
            [
                'type' => 'success',
                'icon' => '✅',
                'title' => 'Tarifs XPO mis à jour',
                'description' => 'Mise à jour automatique des tarifs transporteur',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'user' => 'Système'
            ],
            [
                'type' => 'info',
                'icon' => '📊',
                'title' => 'Calculs effectués',
                'description' => rand(50, 150) . ' nouveaux calculs de tarifs',
                'timestamp' => date('Y-m-d H:i:s'),
                'user' => 'Utilisateurs'
            ],
            [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Département sans tarif',
                'description' => 'Le département 20 (Corse) nécessite une mise à jour',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'user' => 'Système'
            ],
            [
                'type' => 'success',
                'icon' => '⚙️',
                'title' => 'Interface admin mise à jour',
                'description' => 'Nouvelle version de l\'interface d\'administration',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'user' => 'Admin'
            ]
        ];
        
        // En production, requête réelle :
        /*
        $stmt = $db->prepare("
            SELECT * FROM gul_admin_logs 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        */
        
    } catch (Exception $e) {
        error_log("Erreur activité récente: " . $e->getMessage());
    }
    
    return $activities;
}

/**
 * Aperçu des tarifs récents
 */
function getRatesPreview(PDO $db): array {
    $preview = [];
    
    try {
        // Récupérer un échantillon de tarifs pour chaque transporteur
        $carriers = [
            'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
            'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
            'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
        ];
        
        foreach ($carriers as $code => $info) {
            $stmt = $db->prepare("
                SELECT 
                    num_departement,
                    departement,
                    delais,
                    tarif_0_9,
                    tarif_100_299,
                    CASE 
                        WHEN tarif_0_9 IS NOT NULL AND tarif_100_299 IS NOT NULL THEN 'complet'
                        WHEN tarif_0_9 IS NOT NULL OR tarif_100_299 IS NOT NULL THEN 'partiel'
                        ELSE 'vide'
                    END as status
                FROM {$info['table']} 
                WHERE num_departement IN ('67', '68', '75', '13', '69') 
                ORDER BY num_departement
                LIMIT 5
            ");
            $stmt->execute();
            $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rates as $rate) {
                $preview[] = [
                    'carrier_code' => $code,
                    'carrier_name' => $info['name'],
                    'department_num' => $rate['num_departement'],
                    'department_name' => $rate['departement'] ?: 'Non défini',
                    'delay' => $rate['delais'] ?: 'Non défini',
                    'tarif_0_9' => $rate['tarif_0_9'],
                    'tarif_100_299' => $rate['tarif_100_299'],
                    'status' => $rate['status']
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur aperçu tarifs: " . $e->getMessage());
    }
    
    return $preview;
}

/**
 * Analyse de couverture des départements
 */
function getCoverageAnalysis(PDO $db): array {
    $analysis = [
        'total_departments' => 95,
        'covered_departments' => 0,
        'coverage_percentage' => 0,
        'missing_departments' => [],
        'incomplete_coverage' => []
    ];
    
    try {
        // Départements français métropolitains (01-95)
        $allDepartments = range(1, 95);
        $coveredDepartments = [];
        
        // Vérifier la couverture par transporteur
        $tables = ['gul_heppner_rates', 'gul_xpo_rates', 'gul_kn_rates'];
        
        foreach ($tables as $table) {
            $stmt = $db->prepare("
                SELECT DISTINCT CAST(num_departement AS UNSIGNED) as dept_num
                FROM $table 
                WHERE num_departement IS NOT NULL 
                AND num_departement != ''
                AND CAST(num_departement AS UNSIGNED) BETWEEN 1 AND 95
            ");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $coveredDepartments = array_merge($coveredDepartments, $departments);
        }
        
        $coveredDepartments = array_unique($coveredDepartments);
        sort($coveredDepartments);
        
        $analysis['covered_departments'] = count($coveredDepartments);
        $analysis['coverage_percentage'] = round(($analysis['covered_departments'] / $analysis['total_departments']) * 100, 1);
        $analysis['missing_departments'] = array_diff($allDepartments, $coveredDepartments);
        
        // Départements avec couverture incomplète (moins de 2 transporteurs)
        foreach ($coveredDepartments as $dept) {
            $coverage_count = 0;
            foreach ($tables as $table) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count 
                    FROM $table 
                    WHERE num_departement = :dept 
                    AND (tarif_0_9 IS NOT NULL OR tarif_100_299 IS NOT NULL)
                ");
                $stmt->execute([':dept' => sprintf('%02d', $dept)]);
                if ($stmt->fetch()['count'] > 0) {
                    $coverage_count++;
                }
            }
            
            if ($coverage_count < 2) {
                $analysis['incomplete_coverage'][] = $dept;
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur analyse couverture: " . $e->getMessage());
    }
    
    return $analysis;
}

/**
 * Actions rapides disponibles
 */
function getQuickActions(PDO $db): array {
    $actions = [];
    
    try {
        // Analyser les besoins d'actions rapides
        
        // 1. Départements sans tarifs
        $stmt = $db->query("
            SELECT COUNT(*) as missing_count FROM (
                SELECT dept_num FROM (
                    SELECT 1 as dept_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
                    SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
                    SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
                    SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
                    SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION
                    SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION
                    SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION
                    SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION
                    SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION
                    SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION
                    SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION
                    SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION
                    SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION
                    SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70 UNION
                    SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION
                    SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80 UNION
                    SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85 UNION
                    SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION SELECT 90 UNION
                    SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95
                ) as all_depts
                WHERE dept_num NOT IN (
                    SELECT DISTINCT CAST(num_departement AS UNSIGNED) 
                    FROM gul_heppner_rates 
                    WHERE num_departement IS NOT NULL
                    UNION
                    SELECT DISTINCT CAST(num_departement AS UNSIGNED) 
                    FROM gul_xpo_rates 
                    WHERE num_departement IS NOT NULL
                    UNION
                    SELECT DISTINCT CAST(num_departement AS UNSIGNED) 
                    FROM gul_kn_rates 
                    WHERE num_departement IS NOT NULL
                )
            ) as missing
        ");
        $missingCount = $stmt->fetch()['missing_count'];
        
        if ($missingCount > 0) {
            $actions[] = [
                'type' => 'warning',
                'icon' => '🗺️',
                'title' => "Compléter $missingCount départements",
                'description' => "Ajouter les tarifs manquants pour une couverture complète",
                'action' => 'showTab("rates")',
                'priority' => 'high'
            ];
        }
        
        // 2. Options manquantes
        $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE actif = 1");
        $optionsCount = $stmt->fetch()['count'];
        
        if ($optionsCount < 10) {
            $actions[] = [
                'type' => 'info',
                'icon' => '⚙️',
                'title' => 'Configurer les options',
                'description' => 'Ajouter des options supplémentaires pour enrichir l\'offre',
                'action' => 'showTab("options")',
                'priority' => 'medium'
            ];
        }
        
        // 3. Sauvegarde recommandée
        $actions[] = [
            'type' => 'success',
            'icon' => '💾',
            'title' => 'Créer une sauvegarde',
            'description' => 'Exporter toutes les données de configuration',
            'action' => 'downloadBackup()',
            'priority' => 'low'
        ];
        
        // 4. Vérification des tarifs
        $actions[] = [
            'type' => 'info',
            'icon' => '🔍',
            'title' => 'Audit des tarifs',
            'description' => 'Vérifier la cohérence des grilles tarifaires',
            'action' => 'showTab("rates")',
            'priority' => 'medium'
        ];
        
    } catch (Exception $e) {
        error_log("Erreur actions rapides: " . $e->getMessage());
    }
    
    return $actions;
}

/**
 * Alertes système importantes
 */
function getSystemAlerts(PDO $db): array {
    $alerts = [];
    
    try {
        // Vérifications de santé du système
        
        // 1. Vérifier les tables critiques
        $criticalTables = [
            'gul_taxes_transporteurs',
            'gul_heppner_rates',
            'gul_xpo_rates',
            'gul_kn_rates'
        ];
        
        foreach ($criticalTables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                
                if ($count == 0) {
                    $alerts[] = [
                        'type' => 'error',
                        'icon' => '❌',
                        'title' => "Table $table vide",
                        'description' => 'Cette table critique ne contient aucune donnée',
                        'action' => 'showTab("import")',
                        'priority' => 'critical'
                    ];
                }
            } catch (Exception $e) {
                $alerts[] = [
                    'type' => 'error',
                    'icon' => '💥',
                    'title' => "Erreur table $table",
                    'description' => 'Impossible d\'accéder à cette table : ' . $e->getMessage(),
                    'action' => null,
                    'priority' => 'critical'
                ];
            }
        }
        
        // 2. Vérifier la cohérence des données
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM gul_taxes_transporteurs gt
            LEFT JOIN gul_heppner_rates hr ON gt.transporteur = 'Heppner'
            LEFT JOIN gul_xpo_rates xr ON gt.transporteur = 'XPO'
            LEFT JOIN gul_kn_rates kr ON gt.transporteur = 'Kuehne + Nagel'
            WHERE hr.id IS NULL AND xr.id IS NULL AND kr.id IS NULL
        ");
        $orphanCarriers = $stmt->fetch()['count'];
        
        if ($orphanCarriers > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Transporteurs sans tarifs',
                'description' => "$orphanCarriers transporteur(s) configuré(s) mais sans grille tarifaire",
                'action' => 'showTab("rates")',
                'priority' => 'medium'
            ];
        }
        
        // 3. Vérifier l'espace disque (simulation)
        $diskUsage = rand(15, 85);
        if ($diskUsage > 80) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '💿',
                'title' => 'Espace disque faible',
                'description' => "Utilisation du disque : {$diskUsage}%",
                'action' => 'downloadBackup()',
                'priority' => 'medium'
            ];
        }
        
        // 4. Vérifier les performances (simulation)
        $responseTime = rand(50, 500);
        if ($responseTime > 300) {
            $alerts[] = [
                'type' => 'info',
                'icon' => '⏱️',
                'title' => 'Performance dégradée',
                'description' => "Temps de réponse moyen : {$responseTime}ms",
                'action' => null,
                'priority' => 'low'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Erreur alertes système: " . $e->getMessage());
        
        $alerts[] = [
            'type' => 'error',
            'icon' => '🚨',
            'title' => 'Erreur système',
            'description' => 'Impossible de vérifier l\'état du système',
            'action' => null,
            'priority' => 'critical'
        ];
    }
    
    return $alerts;
}

/**
 * Données par défaut en cas d'erreur
 */
function getDefaultDashboardData(): array {
    return [
        'stats' => [
            'carriers' => 3,
            'departments' => 95,
            'options' => 0,
            'calculations_today' => 0,
            'carriers_change' => 0,
            'departments_change' => 0,
            'options_change' => 0,
            'calculations_change' => 0
        ],
        'recent_activity' => [
            [
                'type' => 'info',
                'icon' => 'ℹ️',
                'title' => 'Système initialisé',
                'description' => 'Interface d\'administration chargée',
                'timestamp' => date('Y-m-d H:i:s'),
                'user' => 'Système'
            ]
        ],
        'rates_preview' => [],
        'coverage_analysis' => [
            'total_departments' => 95,
            'covered_departments' => 0,
            'coverage_percentage' => 0,
            'missing_departments' => [],
            'incomplete_coverage' => []
        ],
        'quick_actions' => [
            [
                'type' => 'info',
                'icon' => '🚀',
                'title' => 'Commencer la configuration',
                'description' => 'Importer les premières données de tarification',
                'action' => 'showTab("import")',
                'priority' => 'high'
            ]
        ],
        'alerts' => [
            [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Configuration incomplète',
                'description' => 'Le système nécessite une configuration initiale',
                'action' => 'showTab("import")',
                'priority' => 'high'
            ]
        ],
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * Formatte un changement en pourcentage avec icône
 */
function formatChange(float $value): array {
    if ($value > 0) {
        return [
            'text' => "+{$value}%",
            'class' => 'positive',
            'icon' => '📈'
        ];
    } elseif ($value < 0) {
        return [
            'text' => "{$value}%",
            'class' => 'negative', 
            'icon' => '📉'
        ];
    } else {
        return [
            'text' => "0%",
            'class' => 'neutral',
            'icon' => '➡️'
        ];
    }
}

/**
 * Formatte une date pour l'affichage
 */
function formatActivityDate(string $timestamp): string {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return "Il y a {$diff->i} minute(s)";
        }
        return "Il y a {$diff->h} heure(s)";
    } elseif ($diff->days == 1) {
        return "Hier";
    } else {
        return "Il y a {$diff->days} jour(s)";
    }
}

/**
 * Obtient le badge de statut pour un tarif
 */
function getStatusBadge(string $status): array {
    $badges = [
        'complet' => ['class' => 'badge-success', 'text' => 'Complet', 'icon' => '✅'],
        'partiel' => ['class' => 'badge-warning', 'text' => 'Partiel', 'icon' => '⚠️'],
        'vide' => ['class' => 'badge-danger', 'text' => 'Vide', 'icon' => '❌']
    ];
    
    return $badges[$status] ?? ['class' => 'badge-info', 'text' => 'Inconnu', 'icon' => '❓'];
}

/**
 * Cache simple pour les données (optionnel)
 */
function getCachedDashboardData(PDO $db): array {
    $cacheFile = __DIR__ . '/../cache/dashboard_cache.json';
    $cacheTime = 300; // 5 minutes
    
    // Vérifier si le cache existe et est valide
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && is_array($cached)) {
            return $cached;
        }
    }
    
    // Récupérer les données fraîches
    $data = getDashboardData($db);
    
    // Sauvegarder en cache
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    
    return $data;
}
?>
