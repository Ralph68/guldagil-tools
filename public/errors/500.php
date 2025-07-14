<?php
/**
 * Titre: Page 500 - Erreur serveur interne
 * Chemin: /public/500.php
 * Version: 0.5 beta + build auto
 */

http_response_code(500);
header('Location: /error.php?type=500&code=500&message=' . urlencode('Erreur interne du serveur'));
exit;
?>
