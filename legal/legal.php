<?php
// Page des mentions légales du portail Guldagil

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
$page_title = "Mentions légales";
$page_description = "Mentions légales du portail Guldagil";
$current_module = "home";
$module_css = false;
include ROOT_PATH . '/templates/header.php';
?>
<main class="portal-main legal-main">
    <section class="legal-section">
        <h1>Mentions légales</h1>
        <h2>Éditeur du site</h2>
        <p>
            <strong>Guldagil SAS</strong><br>
            123 rue de l’Industrie<br>
            67000 Strasbourg, France<br>
            SIRET : 123 456 789 00012<br>
            Email : <a href="mailto:contact@guldagil.com">contact@guldagil.com</a>
        </p>
        <h2>Directeur de la publication</h2>
        <p>
            Jean-Thomas Runser<br>
            Email : <a href="mailto:runser.jean.thomas@guldagil.com">runser.jean.thomas@guldagil.com</a>
        </p>
        <h2>Hébergement</h2>
        <p>
            OVH SAS<br>
            2 rue Kellermann<br>
            59100 Roubaix, France<br>
            <a href="https://www.ovh.com/fr/" target="_blank" rel="noopener">www.ovh.com</a>
        </p>
        <h2>Propriété intellectuelle</h2>
        <p>
            L’ensemble des contenus (textes, images, logos, documents, etc.) présents sur ce portail sont la propriété exclusive de Guldagil ou de ses partenaires. Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite, sauf autorisation écrite préalable de Guldagil.
        </p>
        <h2>Protection des données personnelles</h2>
        <p>
            Les informations recueillies via le portail sont destinées exclusivement à un usage interne (gestion des accès, statistiques, support). Conformément à la loi « Informatique et Libertés » et au RGPD, vous disposez d’un droit d’accès, de rectification et de suppression des données vous concernant. Pour exercer ce droit, contactez <a href="mailto:contact@guldagil.com">contact@guldagil.com</a>.
        </p>
        <h2>Cookies</h2>
        <p>
            Ce portail utilise des cookies strictement nécessaires à son fonctionnement et à la sécurité. Aucun cookie publicitaire ou de suivi externe n’est utilisé.
        </p>
        <h2>Responsabilité</h2>
        <p>
            Guldagil s’efforce d’assurer au mieux l’exactitude et la mise à jour des informations diffusées sur ce portail. Toutefois, la société ne saurait être tenue responsable des erreurs ou omissions, d’une absence de disponibilité des informations et des services.
        </p>
        <h2>Contact</h2>
        <p>
            Pour toute question ou réclamation concernant le portail, contactez : <a href="mailto:contact@guldagil.com">contact@guldagil.com</a>
        </p>
    </section>
</main>
<?php include ROOT_PATH . '/templates/footer.php'; ?>
