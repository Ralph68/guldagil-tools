<?php
/**
 * Titre: Conditions générales d'utilisation du portail
 * Chemin: /public/terms.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../config/version.php';

// Meta données de la page
$page_title = "Conditions générales d'utilisation";
$page_description = "Règles d'usage du portail Guldagil - Achats et Logistique";
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
                <h1>📋 Conditions générales d'utilisation</h1>
                <p class="legal-meta">
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <section class="legal-section">
                    <h2>1. Objet et champ d'application</h2>
                    <p>
                        Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès 
                        et l'utilisation du portail interne "<?= APP_NAME ?>" destiné à la gestion 
                        des achats, du calcul des frais de port, de la réglementation ADR et 
                        du contrôle qualité.
                    </p>
                    <p>
                        Ce portail est actuellement en <strong>version <?= APP_VERSION ?></strong> 
                        et en phase de développement. Les fonctionnalités peuvent évoluer sans préavis.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>2. Accès au service</h2>
                    <p>
                        Le portail est accessible aux collaborateurs de l'entreprise Guldagil 
                        et aux partenaires autorisés dans le cadre de leurs missions professionnelles.
                    </p>
                    <div class="status-box status-beta">
                        <h4>🔧 Version beta</h4>
                        <p>
                            L'authentification n'est pas encore activée. L'accès est libre 
                            pour les tests et le développement. Une authentification sera 
                            mise en place dans les versions futures.
                        </p>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>3. Services disponibles</h2>
                    <h3>3.1 Modules actifs</h3>
                    <ul>
                        <li><strong>Calculateur frais de port</strong> : Calcul automatisé des coûts de transport</li>
                        <li><strong>Administration</strong> : Gestion des tarifs et maintenance du système</li>
                    </ul>
                    
                    <h3>3.2 Modules en développement</h3>
                    <ul>
                        <li><strong>Gestion ADR</strong> : Réglementation matières dangereuses (Q2 2025)</li>
                        <li><strong>Contrôle qualité</strong> : Suivi et contrôle des processus (Q3 2025)</li>
                        <li><strong>Gestion EPI</strong> : Équipements de protection (Q4 2025)</li>
                        <li><strong>Gestion outillage</strong> : Maintenance et suivi (2026)</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Obligations des utilisateurs</h2>
                    <h3>4.1 Usage professionnel</h3>
                    <ul>
                        <li>Utiliser le portail uniquement dans le cadre professionnel</li>
                        <li>Respecter la confidentialité des données accessibles</li>
                        <li>Ne pas divulguer les informations tarifaires à des tiers</li>
                        <li>Signaler tout dysfonctionnement ou faille de sécurité</li>
                    </ul>

                    <h3>4.2 Usage interdit</h3>
                    <ul>
                        <li>Tentative d'accès non autorisé aux systèmes</li>
                        <li>Usage commercial des données pour compte propre</li>
                        <li>Perturbation volontaire du fonctionnement</li>
                        <li>Extraction massive de données</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>5. Disponibilité et performance</h2>
                    <div class="status-box status-info">
                        <h4>📊 Engagement de service</h4>
                        <ul>
                            <li><strong>Disponibilité cible</strong> : 99% en horaires ouvrés</li>
                            <li><strong>Maintenance programmée</strong> : Dimanche 2h-6h</li>
                            <li><strong>Support</strong> : Jours ouvrés 8h-18h</li>
                        </ul>
                    </div>
                    <p>
                        En version beta, des interruptions peuvent survenir pour les mises à jour 
                        et corrections. Les utilisateurs sont prévenus autant que possible.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>6. Propriété intellectuelle</h2>
                    <p>
                        Le portail, son code source, ses algorithmes de calcul et sa documentation 
                        sont la propriété exclusive de l'entreprise Guldagil. Toute reproduction, 
                        adaptation ou utilisation sans autorisation est interdite.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>7. Données et confidentialité</h2>
                    <p>
                        Les données de calcul ne sont pas conservées sur le serveur. 
                        Les tarifs et configurations sont confidentiels et ne doivent 
                        pas être communiqués à l'extérieur de l'entreprise.
                    </p>
                    <p>
                        Pour plus de détails, consultez notre 
                        <a href="/privacy.php">Politique de confidentialité</a>.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>8. Limitation de responsabilité</h2>
                    <div class="status-box status-warning">
                        <h4>⚠️ Version beta - Limitations</h4>
                        <p>
                            En phase de développement, le portail est fourni "en l'état". 
                            L'entreprise ne peut être tenue responsable :
                        </p>
                        <ul>
                            <li>Des erreurs de calcul dues aux données incomplètes</li>
                            <li>Des interruptions de service liées au développement</li>
                            <li>Des pertes de données en cas de dysfonctionnement</li>
                        </ul>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>9. Support et assistance</h2>
                    <p>
                        <strong>Équipe technique :</strong><br>
                        📧 <a href="mailto:support@guldagil.com">support@guldagil.com</a><br>
                        📞 Support technique interne<br>
                        🕒 Jours ouvrés : 8h00 - 18h00
                    </p>
                    <p>
                        <strong>Développeur principal :</strong><br>
                        Jean-Thomas RUNSER - Responsable du projet
                    </p>
                </section>

                <section class="legal-section">
                    <h2>10. Évolution des conditions</h2>
                    <p>
                        Ces CGU peuvent être modifiées à tout moment, notamment lors des 
                        mises à jour du portail. Les utilisateurs sont informés des 
                        modifications importantes via les canaux de communication internes.
                    </p>
                    <p>
                        La version en vigueur est identifiable par le numéro de build : 
                        <strong><?= BUILD_NUMBER ?></strong>
                    </p>
                </section>

                <section class="legal-section">
                    <h2>11. Contact et réclamations</h2>
                    <p>
                        Pour toute question relative à ces conditions d'utilisation :<br>
                        📧 <a href="mailto:legal@guldagil.com">legal@guldagil.com</a><br>
                        📧 <a href="mailto:jtrunser@guldagil.com">jtrunser@guldagil.com</a>
                    </p>
                </section>
            </div>

            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
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
