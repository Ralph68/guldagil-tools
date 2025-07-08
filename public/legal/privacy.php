<?php
/**
 * Titre: Politique de confidentialité du portail
 * Chemin: /public/privacy.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../config/version.php';

// Meta données de la page
$page_title = "Politique de confidentialité";
$page_description = "Protection des données personnelles - Portail Guldagil";
$page_type = "legal";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="<?= $page_description ?>">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/legal.css">
</head>
<body class="legal-page">
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand">
                <h1 class="brand-title"><?= APP_NAME ?></h1>
                <span class="brand-version">v<?= APP_VERSION ?> build <?= BUILD_NUMBER ?></span>
            </div>
            <nav class="header-nav">
                <a href="/index.php" class="nav-link">🏠 Accueil</a>
            </nav>
        </div>
    </header>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>🔒 Politique de confidentialité</h1>
                <p class="legal-meta">
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <section class="legal-section">
                    <h2>1. Responsable du traitement</h2>
                    <p>
                        Le responsable du traitement des données personnelles collectées sur ce portail est :<br>
                        <strong>Entreprise Guldagil</strong><br>
                        Secteur : Traitement de l'eau et logistique<br>
                        Contact : <a href="mailto:contact@guldagil.com">contact@guldagil.com</a>
                    </p>
                </section>

                <section class="legal-section">
                    <h2>2. Données collectées</h2>
                    <p>Dans le cadre de l'utilisation de ce portail, nous pouvons collecter :</p>
                    <ul>
                        <li><strong>Données de connexion</strong> : Identifiants de session (futures fonctionnalités d'authentification)</li>
                        <li><strong>Données d'utilisation</strong> : Pages consultées, modules utilisés</li>
                        <li><strong>Données techniques</strong> : Adresse IP, navigateur, logs de performance</li>
                        <li><strong>Données de calcul</strong> : Paramètres saisis dans les calculateurs (frais de port, ADR)</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>3. Finalités du traitement</h2>
                    <p>Les données sont traitées pour :</p>
                    <ul>
                        <li>Assurer le fonctionnement du portail</li>
                        <li>Améliorer l'expérience utilisateur</li>
                        <li>Maintenir la sécurité du système</li>
                        <li>Réaliser des statistiques d'usage anonymisées</li>
                        <li>Respecter nos obligations légales</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Base légale</h2>
                    <p>
                        Le traitement est fondé sur l'intérêt légitime de l'entreprise à fournir 
                        et améliorer ses services internes de gestion logistique et de calcul 
                        des frais de transport.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>5. Conservation des données</h2>
                    <ul>
                        <li><strong>Logs techniques</strong> : 12 mois maximum</li>
                        <li><strong>Données de calcul</strong> : Session uniquement (non conservées)</li>
                        <li><strong>Données d'usage</strong> : 24 mois pour les statistiques anonymisées</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>6. Droits des utilisateurs</h2>
                    <p>Vous disposez des droits suivants :</p>
                    <ul>
                        <li><strong>Droit d'accès</strong> : Connaître les données vous concernant</li>
                        <li><strong>Droit de rectification</strong> : Corriger vos données</li>
                        <li><strong>Droit d'effacement</strong> : Supprimer vos données</li>
                        <li><strong>Droit à la portabilité</strong> : Récupérer vos données</li>
                        <li><strong>Droit d'opposition</strong> : Vous opposer au traitement</li>
                    </ul>
                    <p>
                        Pour exercer ces droits, contactez-nous à : 
                        <a href="mailto:dpo@guldagil.com">dpo@guldagil.com</a>
                    </p>
                </section>

                <section class="legal-section">
                    <h2>7. Sécurité</h2>
                    <p>
                        Nous mettons en œuvre des mesures techniques et organisationnelles 
                        appropriées pour protéger vos données contre tout accès, modification, 
                        divulgation ou destruction non autorisés.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>8. Cookies et technologies similaires</h2>
                    <p>
                        Ce portail utilise uniquement des cookies techniques nécessaires 
                        au fonctionnement des services (session, préférences d'affichage).
                        Aucun cookie de tracking ou publicitaire n'est utilisé.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>9. Modifications</h2>
                    <p>
                        Cette politique peut être mise à jour. La date de dernière modification 
                        est indiquée en en-tête. Les utilisateurs seront informés des 
                        modifications importantes.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>10. Contact</h2>
                    <p>
                        Pour toute question relative à cette politique de confidentialité :<br>
                        📧 <a href="mailto:dpo@guldagil.com">dpo@guldagil.com</a><br>
                        📞 Délégué à la Protection des Données
                    </p>
                </section>
            </div>

            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/terms.php" class="btn btn-secondary">📋 Conditions d'utilisation</a>
                    <a href="/security.php" class="btn btn-secondary">🔐 Sécurité</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-info">
                <p>&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?> - <?= APP_NAME ?></p>
                <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?> (<?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?>)</p>
            </div>
            <div class="footer-links">
                <a href="/privacy.php">Confidentialité</a>
                <a href="/terms.php">CGU</a>
                <a href="/security.php">Sécurité</a>
            </div>
        </div>
    </footer>
</body>
</html>
