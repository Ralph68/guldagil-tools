<?php
/**
 * Titre: API Statistiques temps réel module qualité
 * Chemin: /public/qualite/api/stats.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Utiliser vue existante pour stats globales
    $stmt = $pdo->query("SELECT * FROM v_control_stats");
    $global_stats = $stmt->fetch() ?: [];

    // Stats par agence
    $stmt = $pdo->query("
        SELECT agency_code, COUNT(*) as total, 
               SUM(CASE WHEN status = 'completed' OR status = 'validated' THEN 1 ELSE 0 END) as completed
        FROM cq_quality_controls 
        GROUP BY agency_code
        ORDER BY total DESC
    ");
    $agency_stats = $stmt->fetchAll();

    // Non-conformités ouvertes
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM cq_quality_controls 
        WHERE status = 'in_progress' 
        AND JSON_EXTRACT(technical_data, '$.quality_checks') IS NOT NULL
    ");
    $non_conformites = $stmt->fetchColumn() ?: 0;

    // Alertes actives (simulation)
    $alerts = [];
    if ($non_conformites > 0) {
        $alerts[] = [
            'type' => 'non_conformite',
            'count' => $non_conformites,
            'message' => "{$non_conformites} non-conformité(s) en cours",
            'priority' => 'high'
        ];
    }

    // Contrôles en retard de validation
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM cq_quality_controls 
        WHERE status = 'completed' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $retard_validation = $stmt->fetchColumn() ?: 0;
    
    if ($retard_validation > 0) {
        $alerts[] = [
            'type' => 'validation_retard',
            'count' => $retard_validation,
            'message' => "{$retard_validation} contrôle(s) en attente validation",
            'priority' => 'medium'
        ];
    }

    // Calcul taux conformité
    $total_evaluated = ($global_stats['completed_count'] ?? 0) + ($global_stats['validated_count'] ?? 0);
    $conformity_rate = $total_evaluated > 0 ? 
        round((($total_evaluated - $non_conformites) / $total_evaluated) * 100, 1) : 100;

    // Données récentes (5 derniers)
    $stmt = $pdo->query("
        SELECT control_number, status, equipment_type, agency_code, created_at
        FROM v_controls_active 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_controls = $stmt->fetchAll();

    // Réponse JSON
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => [
            'controles_total' => $global_stats['total_controls'] ?? 0,
            'controles_mois' => calculateMonthlyCount($pdo),
            'controles_semaine' => $global_stats['this_week_count'] ?? 0,
            'controles_aujourd_hui' => $global_stats['today_count'] ?? 0,
            'taux_conformite' => $conformity_rate,
            'non_conformites_ouvertes' => $non_conformites,
            'en_cours' => $global_stats['in_progress_count'] ?? 0,
            'brouillons' => $global_stats['draft_count'] ?? 0,
            'valides' => $global_stats['validated_count'] ?? 0
        ],
        'agency_stats' => $agency_stats,
        'alerts' => $alerts,
        'recent_controls' => $recent_controls,
        'performance' => calculatePerformanceMetrics($pdo)
    ]);

} catch (Exception $e) {
    error_log("Erreur API stats: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur récupération statistiques'
    ]);
}

function calculateMonthlyCount(PDO $pdo): int {
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM cq_quality_controls 
        WHERE YEAR(created_at) = YEAR(CURDATE()) 
        AND MONTH(created_at) = MONTH(CURDATE())
    ");
    return $stmt->fetchColumn() ?: 0;
}

function calculatePerformanceMetrics(PDO $pdo): array {
    // Temps moyen de traitement
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, validated_date)) as avg_hours
        FROM cq_quality_controls 
        WHERE validated_date IS NOT NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $avg_processing_hours = round($stmt->fetchColumn() ?: 0, 1);

    // Évolution hebdomadaire
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN WEEK(created_at) = WEEK(NOW()) THEN 1 END) as this_week,
            COUNT(CASE WHEN WEEK(created_at) = WEEK(NOW()) - 1 THEN 1 END) as last_week
        FROM cq_quality_controls 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    ");
    $weekly = $stmt->fetch();
    
    $weekly_trend = 0;
    if ($weekly && $weekly['last_week'] > 0) {
        $weekly_trend = round((($weekly['this_week'] - $weekly['last_week']) / $weekly['last_week']) * 100, 1);
    }

    return [
        'avg_processing_hours' => $avg_processing_hours,
        'weekly_trend' => $weekly_trend,
        'productivity_score' => calculateProductivityScore($weekly['this_week'] ?? 0, $avg_processing_hours)
    ];
}

function calculateProductivityScore(int $weekly_count, float $avg_hours): int {
    // Score basé sur volume et rapidité
    $volume_score = min($weekly_count * 10, 50); // Max 50 points
    $speed_score = $avg_hours > 0 ? max(50 - $avg_hours, 0) : 50; // Max 50 points
    
    return min(round($volume_score + $speed_score), 100);
}
?>
