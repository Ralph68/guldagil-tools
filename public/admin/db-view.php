<?php
/**
 * Titre : DB View - Visualisation BDD Guldagil
 * Description : Interface de visualisation simple des tables MySQL
 * Emplacement : /public/admin/db-view.php
 * Version : 0.5 beta
 */

require_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../includes/sheader.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connexion échouée : " . $conn->connect_error);

$table = $_GET['table'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>DB View - Admin BDD</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f9f9f9;color:#111;padding:2rem">
  <h1 style="margin-bottom:1rem">🗃️ Visualiseur de base de données</h1>
  <div style="display:flex;gap:2rem">
    <aside style="min-width:200px">
      <h2 style="font-size:1rem;margin-bottom:0.5rem">Tables disponibles</h2>
      <ul style="list-style:none;padding-left:0">
        <?php
        $res = $conn->query("SHOW TABLES");
        while ($row = $res->fetch_array()) {
          $t = $row[0];
          $active = ($t === $table) ? 'font-weight:bold;color:#0066cc;' : '';
          echo "<li style='margin-bottom:0.5rem'><a href='?table=$t' style='text-decoration:none;$active'>$t</a></li>";
        }
        ?>
      </ul>
    </aside>

    <main style="flex:1">
      <?php if ($table): ?>
        <h2 style="font-size:1.25rem;margin-bottom:1rem">📋 Contenu de <code><?= htmlspecialchars($table) ?></code></h2>
        <?php
        $res = $conn->query("SELECT * FROM `$table` LIMIT 100");
        if ($res && $res->num_rows > 0):
          echo "<table style='width:100%;border-collapse:collapse;font-size:0.9rem'>";
          echo "<thead><tr style='background:#eee'>";
          while ($field = $res->fetch_field()) {
            echo "<th style='border:1px solid #ccc;padding:6px;text-align:left'>" . htmlspecialchars($field->name) . "</th>";
          }
          echo "</tr></thead><tbody>";
          while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
              echo "<td style='border:1px solid #ddd;padding:6px'>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
          }
          echo "</tbody></table>";
        else:
          echo "<p style='color:#dc2626'>Table vide ou erreur d’accès.</p>";
        endif;
        ?>
      <?php else: ?>
        <p style="color:#555">Veuillez sélectionner une table à gauche pour afficher son contenu.</p>
      <?php endif; ?>
    </main>
  </div>
<?php include_once __DIR__ . '/../includes/fgfooter.php'; ?>
</body>
</html>
