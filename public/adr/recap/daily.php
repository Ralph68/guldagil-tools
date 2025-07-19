<?php
/**
 * Titre: Récapitulatif quotidien par transporteur
 * Chemin: /public/adr/recap/daily.php
 * Affiche pour chaque transporteur le nombre d'expéditions et les points ADR du jour sélectionné.
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection vers la page de connexion si nécessaire
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Récapitulatif quotidien';
$page_subtitle = 'Expéditions par transporteur';
$current_module = 'adr';
$module_css = true;
$user_authenticated = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

// Date sélectionnée
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Connexion BDD
$db_connected = false;
$recap_data = [];
try {
    $db = $db ?? new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    error_log('ADR recap db error: ' . $e->getMessage());
}

if ($db_connected) {
    try {
        $stmt = $db->prepare(
            "SELECT transporteur, COUNT(*) AS expeditions, SUM(total_points_adr) AS total_points
             FROM gul_adr_expeditions
             WHERE DATE(date_expedition) = ?
             GROUP BY transporteur
             ORDER BY transporteur"
        );
        $stmt->execute([$selected_date]);
        $recap_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('ADR recap query error: ' . $e->getMessage());
    }
}

include ROOT_PATH . '/templates/header.php';
?>
<style>
.recap-container { max-width: 900px; margin: 0 auto; padding: 2rem; }
.recap-form { margin-bottom: 1.5rem; display: flex; gap: 0.5rem; align-items: center; }
.recap-table { width: 100%; border-collapse: collapse; }
.recap-table th, .recap-table td { padding: 0.5rem; border: 1px solid #e5e7eb; text-align: left; }
.recap-table th { background: #f3f4f6; }
</style>
<div class="recap-container">
    <h2>Récapitulatif du <?= htmlspecialchars(date('d/m/Y', strtotime($selected_date))) ?></h2>
    <form method="get" class="recap-form">
        <label for="date">Date :</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>">
        <button type="submit" class="btn btn-primary">Afficher</button>
    </form>
    <?php if (!empty($recap_data)): ?>
        <table class="recap-table">
            <thead>
                <tr>
                    <th>Transporteur</th>
                    <th>Expéditions</th>
                    <th>Total points ADR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recap_data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars(strtoupper($row['transporteur'])) ?></td>
                        <td><?= (int)$row['expeditions'] ?></td>
                        <td><?= (int)$row['total_points'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune expédition enregistrée pour cette date.</p>
    <?php endif; ?>
</div>
<?php include ROOT_PATH . '/templates/footer.php'; ?>
