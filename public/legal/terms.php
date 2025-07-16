<?php
/**
 * Titre: Conditions générales d'utilisation du portail Guldagil - MISE À JOUR COMPLÈTE
 * Chemin: /public/legal/terms.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../config/config.php';

// Meta données de la page
$page_title = "Conditions générales d'utilisation";
$page_description = "Règles d'usage du portail Guldagil - Traitement des eaux et solutions industrielles";
$page_type = "legal";
$current_module = "legal";

// Variables du header
$module_css = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="<?= $page_description ?>">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= APP_BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= APP_BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= APP_BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/legal.css?v=<?= APP_BUILD_NUMBER ?>">
</head>
<body class="legal-page">
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>📋 Conditions générales d'utilisation</h1>
                <p class="legal-meta">
                    <strong>Portail interne Guldagil</strong><br>
                    Dernière mise à jour : <?= date('d/m/Y', APP_BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= APP_BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <section class="legal-section">
                    <h2>1. Objet et champ d'application</h2>
                    <p>
                        Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès 
                        et l'utilisation du <strong>portail interne Guldagil</strong>, plateforme dédiée à la gestion 
                        des achats, de la logistique et des opérations industrielles.
                    </p>
                    <p>
                        Ce portail centralise les outils professionnels suivants :
                    </p>
                    <ul>
                        <li><strong>Calculateur de frais de port</strong> - Calcul et comparaison des tarifs de transport (Heppner, XPO, Kuehne+Nagel)</li>
                        <li><strong>Gestion ADR</strong> - Transport sécurisé de marchandises dangereuses selon réglementation ADR</li>
                        <li><strong>Contrôle Qualité</strong> - Système de gestion qualité et suivi des contrôles réglementaires</li>
                        <li><strong>Équipements EPI</strong> - Gestion des équipements de protection individuelle</li>
                        <li><strong>Outillages</strong> - Suivi et gestion du matériel industriel</li>
                        <li><strong>Administration</strong> - Outils de gestion et configuration du portail</li>
                    </ul>
                    <p>
                        <strong>Version actuelle :</strong> <?= APP_VERSION ?> (version de développement - fonctionnalités évolutives)
                    </p>
                </section>

                <section class="legal-section">
                    <h2>2. Présentation de Guldagil</h2>
                    <p>
                        <strong>Guldagil</strong> est une société française familiale créée en 1964, spécialisée dans 
                        le traitement des eaux et les solutions industrielles. Nous développons des systèmes de 
                        traitement, protection et désinfection des réseaux d'eau chaude et froide.
                    </p>
                    <p>
                        <strong>Domaines d'intervention :</strong>
                    </p>
                    <ul>
                        <li>Industrie (agro-alimentaire, pharmaceutique, cosmétique, transformation)</li>
                        <li>Collectivités (hôpitaux, écoles, gymnases, musées, hôtels)</li>
                        <li>Habitat collectif (syndics, copropriétés, OPHLM)</li>
                        <li>Tertiaire (magasins, immeubles de bureaux, laboratoires)</li>
                    </ul>
                    <p>
                        <strong>Certifications :</strong> CSTBat Service procédés de traitement des eaux (NF EN 14095)
                    </p>
                </section>

                <section class="legal-section">
                    <h2>3. Accès au service</h2>
                    <p>
                        Le portail est accessible aux collaborateurs de Guldagil et aux partenaires autorisés 
                        dans le cadre de leurs missions professionnelles. L'accès nécessite une authentification 
                        préalable avec identifiants personnels.
                    </p>
                    <p>
                        <strong>Conditions d'accès :</strong>
                    </p>
                    <ul>
                        <li>Compte utilisateur valide et activé</li>
                        <li>Respect des règles de sécurité informatique</li>
                        <li>Usage exclusivement professionnel</li>
                        <li>Connexion depuis un poste de travail autorisé</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Obligations des utilisateurs</h2>
                    <p>L'utilisateur s'engage à :</p>
                    <ul>
                        <li>Utiliser le portail dans le cadre de ses fonctions professionnelles uniquement</li>
                        <li>Maintenir la confidentialité de ses identifiants de connexion</li>
                        <li>Signaler immédiatement toute utilisation frauduleuse de son compte</li>
                        <li>Respecter la législation en vigueur, notamment sur les marchandises dangereuses (ADR)</li>
                        <li>Ne pas porter atteinte à la sécurité et au bon fonctionnement du système</li>
                        <li>Utiliser les données dans le respect du RGPD et de la confidentialité</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>5. Protection des données personnelles</h2>
                    <p>
                        Conformément au RGPD (Règlement Général sur la Protection des Données), 
                        les données personnelles collectées sont traitées dans le respect de la vie privée.
                    </p>
                    <p>
                        <strong>Données collectées :</strong>
                    </p>
                    <ul>
                        <li>Données d'identification (nom, prénom, email, rôle)</li>
                        <li>Données de connexion (logs, adresses IP, horaires)</li>
                        <li>Données d'utilisation (modules utilisés, actions effectuées)</li>
                        <li>Données de calculs (historique des tarifications, résultats ADR)</li>
                    </ul>
                    <p>
                        <strong>Finalités du traitement :</strong> Gestion des accès, sécurité du système, 
                        amélioration des services, traçabilité réglementaire.
                    </p>
                    <p>
                        <strong>Durée de conservation :</strong> 3 ans à compter de la dernière connexion 
                        ou de la fin de la relation contractuelle, conformément aux obligations légales.
                    </p>
                    <p>
                        <strong>Droits des utilisateurs :</strong> Accès, rectification, effacement, portabilité, 
                        opposition. Contact : <a href="mailto:jean-thomas.runser@guldagil.com">jean-thomas.runser@guldagil.com</a>
                    </p>
                </section>

                <section class="legal-section">
                    <h2>6. Sécurité et confidentialité</h2>
                    <p>
                        Guldagil met en œuvre les mesures techniques et organisationnelles appropriées 
                        pour assurer la sécurité et la confidentialité des données.
                    </p>
                    <ul>
                        <li>Chiffrement des données sensibles</li>
                        <li>Authentification forte et sessions sécurisées</li>
                        <li>Logs d'audit et traçabilité des actions</li>
                        <li>Sauvegardes régulières et plan de continuité</li>
                        <li>Contrôles d'accès par rôles</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>7. Responsabilités</h2>
                    <p>
                        <strong>Guldagil s'engage à :</strong>
                    </p>
                    <ul>
                        <li>Maintenir la disponibilité du service dans la mesure du possible</li>
                        <li>Assurer la sécurité des données confiées</li>
                        <li>Fournir un support technique approprié</li>
                        <li>Respecter les réglementations en vigueur</li>
                    </ul>
                    <p>
                        <strong>Limitations :</strong> Guldagil ne peut être tenu responsable des dommages 
                        résultant d'une utilisation non conforme du portail, d'une interruption de service 
                        due à des causes externes, ou d'erreurs de saisie de l'utilisateur.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>8. Propriété intellectuelle</h2>
                    <p>
                        Le portail Guldagil, ses fonctionnalités, bases de données et contenus sont 
                        protégés par les droits de propriété intellectuelle. Toute reproduction, 
                        modification ou distribution non autorisée est interdite.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>9. Évolutions et mises à jour</h2>
                    <p>
                        Guldagil se réserve le droit de faire évoluer le portail, ses fonctionnalités 
                        et les présentes CGU. Les utilisateurs seront informés des modifications 
                        significatives par notification sur le portail.
                    </p>
                    <p>
                        <strong>Version en développement :</strong> La version actuelle (<?= APP_VERSION ?>) 
                        est en phase de développement. Des évolutions et corrections peuvent intervenir 
                        sans préavis.
                    </p>
                </section>

                <section class="legal-section">
                    <h2>10. Contact et support</h2>
                    <p>
                        Pour toute question concernant ces conditions d'utilisation, le fonctionnement 
                        du portail ou vos données personnelles :
                    </p>
                    <div class="contact-info">
                        <p>
                            <strong>Développeur responsable :</strong><br>
                            Jean-Thomas RUNSER<br>
                            <a href="mailto:jean-thomas.runser@guldagil.com">jean-thomas.runser@guldagil.com</a>
                        </p>
                        <p>
                            <strong>Siège social :</strong><br>
                            Guldagil<br>
                            4 rue Robert Schuman<br>
                            CS 30025 – 68171 RIXHEIM Cedex<br>
                            France
                        </p>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>11. Droit applicable</h2>
                    <p>
                        Les présentes CGU sont soumises au droit français. En cas de litige, 
                        les tribunaux français seront seuls compétents.
                    </p>
                    <p>
                        <strong>Date d'entrée en vigueur :</strong> <?= date('d/m/Y', APP_BUILD_TIMESTAMP) ?>
                    </p>
                </section>
            </div>

            <div class="legal-footer">
                <p class="version-info">
                    <strong>Document généré automatiquement</strong><br>
                    Version portail : <?= APP_VERSION ?> - Build : <?= APP_BUILD_NUMBER ?><br>
                    Dernière compilation : <?= date('d/m/Y H:i', APP_BUILD_TIMESTAMP) ?>
                </p>
                <div class="legal-actions">
                    <a href="/legal/" class="btn-legal">📚 Tous les documents légaux</a>
                    <a href="/legal/privacy.php" class="btn-legal">🔒 Politique de confidentialité</a>
                    <a href="/" class="btn-legal">🏠 Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>
</body>
</html>
