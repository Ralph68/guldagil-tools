<?php
/**
 * Titre : DB View - Visualisation & édition BDD Guldagil
 * Emplacement : /public/admin/db-view.php
 */
require_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../includes/sheader.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connexion échouée : " . $conn->connect_error);

$table = $_GET['table'] ?? null;
$edit_id = $_GET['edit'] ?? null;

// Gestion suppression
if (isset($_GET['delete']) && $table) {
    $delete_id = intval($_GET['delete']);
    // Cherche clé primaire
    $pk = '';
    $pk_res = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if ($pk_row = $pk_res->fetch_assoc()) $pk = $pk_row['Column_name'];
    if ($pk) {
        $conn->query("DELETE FROM `$table` WHERE `$pk` = $delete_id");
        header("Location: db-view.php?table=$table");
        exit;
    }
}

// Gestion édition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $pk = '';
    $pk_res = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if ($pk_row = $pk_res->fetch_assoc()) $pk = $pk_row['Column_name'];
    if ($pk) {
        $fields = [];
        foreach ($_POST as $k => $v) {
            if ($k !== 'edit_id') {
                $val = $conn->real_escape_string($v);
                $fields[] = "`$k` = '$val'";
            }
        }
        $sql = "UPDATE `$table` SET " . implode(',', $fields) . " WHERE `$pk` = $edit_id";
        $conn->query($sql);
        header("Location: db-view.php?table=$table");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>DB View - Admin BDD</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f9f9f9;color:#111;padding:2rem">
  <h1 style="margin-bottom:1rem">🗃️ Visualiseur & édition BDD</h1>
  <div style="display:flex;gap:2rem">
    <aside style="min-width:200px">
      <h2 style="font-size:1rem;margin-bottom:0.5rem">Tables</h2>
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
        $pk = '';
        $pk_res = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        if ($pk_row = $pk_res->fetch_assoc()) $pk = $pk_row['Column_name'];
        if ($res && $res->num_rows > 0):
          echo "<table style='width:100%;border-collapse:collapse;font-size:0.9rem'>";
          echo "<thead><tr style='background:#eee'>";
          foreach ($res->fetch_fields() as $field) {
            echo "<th style='border:1px solid #ccc;padding:6px;text-align:left'>" . htmlspecialchars($field->name) . "</th>";
          }
          echo "<th style='border:1px solid #ccc;padding:6px'>Action</th></tr></thead><tbody>";
          while ($row = $res->fetch_assoc()) {
            // Affiche le formulaire inline si édition
            if ($edit_id && $pk && $row[$pk] == $edit_id) {
                echo "<form method='post' action='?table=$table'><tr>";
                foreach ($row as $col => $value) {
                    echo "<td style='border:1px solid #ddd;padding:6px'>";
                    if ($col == $pk) {
                        echo htmlspecialchars($value) . "<input type='hidden' name='edit_id' value='$value'>";
                    } else {
                        echo "<input type='text' name='" . htmlspecialchars($col) . "' value='" . htmlspecialchars($value) . "' style='width:95%;padding:2px'>";
                    }
                    echo "</td>";
                }
                echo "<td style='border:1px solid #ddd;padding:6px'>
                        <button type='submit' style='background:#16a34a;color:white;border:none;padding:5px 10px;border-radius:4px'>Enregistrer</button>
                        <a href='?table=$table' style='margin-left:7px;color:#555'>Annuler</a>
                      </td></tr></form>";
            } else {
                echo "<tr>";
                foreach ($row as $col => $value) {
                    echo "<td style='border:1px solid #ddd;padding:6px'>" . htmlspecialchars($value) . "</td>";
                }
                // Actions : éditer et supprimer (si clé primaire détectée)
                echo "<td style='border:1px solid #ddd;padding:6px'>";
                if ($pk) {
                    echo "<a href='?table=$table&edit=" . urlencode($row[$pk]) . "' style='color:#0284c7;margin-right:8px'>✏️</a>
                          <a href='?table=$table&delete=" . urlencode($row[$pk]) . "' style='color:#dc2626' onclick='return confirm(\"Supprimer cette ligne ?\")'>🗑️</a>";
                }
                echo "</td></tr>";
            }
          }
          echo "</tbody></table>";
        else:
          echo "<p style='color:#dc2626'>Table vide ou erreur d’accès.</p>";
        endif;
        ?>
      <?php else: ?>
        <p style="color:#555">Veuillez sélectionner une table à gauche pour afficher/éditer son contenu.</p>
      <?php endif; ?>
    </main>
  </div>
<?php include_once __DIR__ . '/../includes/fgfooter.php'; ?>
</body>
</html>
