<?php
/**
 * Titre: Page 503 - Service indisponible
 * Chemin: /public/503.php
 * Version: 0.5 beta + build auto
 */

http_response_code(503);
header('Retry-After: 3600'); // Réessayer dans 1 heure
header('Location: /error.php?type=503&code=503&message=' . urlencode('Service temporairement indisponible - Maintenance en cours'));
exit;
?>
