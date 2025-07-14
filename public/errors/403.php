<?php
/**
 * Titre: Page 403 - Accès interdit
 * Chemin: /public/403.php
 * Version: 0.5 beta + build auto
 */

http_response_code(403);
header('Location: /error.php?type=403&code=403&message=' . urlencode('Accès refusé - Permissions insuffisantes'));
exit;
?>
