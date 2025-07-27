<?php
/**
 * Titre: Mentions légales
 * Chemin: /public/legal/mentions.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/config.php';

// Meta données de la page
$page_title = "Mentions légales";
$page_description = "Mentions légales du portail Guldagil - Conformité légale et réglementaire";
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
    
    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/legal.css?v=<?= BUILD_NUMBER ?>">
    
    <link rel="canonical" href="/legal/mentions.php">
</head>
<body class="legal-page">
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>⚖️ Mentions légales</h1>
                <p class="legal-meta">
                    <strong>Conformité légale et réglementaire</strong><br>
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <!-- 1. IDENTIFICATION DE L'ÉDITEUR -->
                <section class="legal-section">
                    <h2>1. 🏢 Identification de l'éditeur</h2>
                    <div class="company-info">
                        <h3>Raison sociale</h3>
                        <p><strong>GULDAGIL</strong></p>
                        
                        <h3>Forme juridique</h3>
                        <p>Société par Actions Simplifiée (SAS)</p>
                        
                        <h3>Capital social</h3>
                        <p>100 000 €</p>
                        
                        <h3>Siège social</h3>
                        <p>
                            4 rue Robert Schuman – CS 30025<br>
                            68171 RIXHEIM Cedex<br>
                            France
                        </p>
                        
                        <h3>Identifiants légaux</h3>
                        <ul>
                            <li><strong>SIRET :</strong> 402 459 523 00013</li>
                            <li><strong>SIREN :</strong> 402 459 523</li>
                            <li><strong>RCS :</strong> MULHOUSE B 402 459 523</li>
                            <li><strong>Code APE :</strong> 3312Z</li>
                            <li><strong>TVA intracommunautaire :</strong> FR84 402459523</li>
                        </ul>
                        
                        <h3>Contact légal</h3>
                        <ul>
                            <li><strong>Téléphone :</strong> 03 89 44 13 17</li>
                            <li><strong>E-mail :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Site web :</strong> <a href="https://www.guldagil.com" target="_blank" rel="noopener">www.guldagil.com</a></li>
                        </ul>
                        
                        <h3>Secteur d'activité</h3>
                        <p>
                            <strong>Activité principale :</strong> Traitement des eaux éco-responsable<br>
                            <strong>Spécialités :</strong> Désembouage, détartrage, désoxydation, désinfection des réseaux<br>
                            <strong>Marchés :</strong> Industrie, collectivités, habitat collectif, tertiaire<br>
                            <strong>Effectif :</strong> 77 personnes (7 agences nationales)
                        </p>
                    </div>
                </section>

                <!-- 2. RESPONSABLE DE LA PUBLICATION -->
                <section class="legal-section">
                    <h2>2. 📝 Responsable de la publication</h2>
                    <p>
                        <strong>Président :</strong> CHARROIS Eric, Jean, Noël<br>
                        <strong>Directeurs généraux :</strong> CHARROIS Sylvain et CHARROIS Daniel, Jean-Louis<br>
                        <strong>Contact direction :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                    
                    <h3>Responsable éditorial du portail</h3>
                    <p>
                        <strong>Responsable technique :</strong> <?= APP_AUTHOR ?><br>
                        <strong>Contact :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                    
                    <h3>Commissaire aux comptes</h3>
                    <p>
                        <strong>Titulaire :</strong> COFIME AUDIT (SAS)<br>
                        <strong>Suppléant :</strong> COFIME (SA)
                    </p>
                </section>

                <!-- 3. HÉBERGEMENT -->
                <section class="legal-section">
                    <h2>3. 🖥️ Hébergement</h2>
                    <div class="hosting-info">
                        <h3>Prestataire d'hébergement</h3>
                        <p>
                            <strong>[Nom de l'hébergeur à préciser]</strong><br>
                            [Adresse de l'hébergeur]<br>
                            [Code postal] [Ville] [Pays]<br>
                            <strong>Contact :</strong> [Contact hébergeur]
                        </p>
                        
                        <h3>Localisation des serveurs</h3>
                        <p>
                            <strong>Localisation :</strong> France / Union Européenne<br>
                            <strong>Conformité :</strong> RGPD et réglementation française<br>
                            <strong>Sécurité :</strong> Certificats SSL/TLS
                        </p>
                        
                        <h3>Site web officiel</h3>
                        <p>
                            <strong>Réalisation site :</strong> OCI SAS<br>
                            <strong>URL :</strong> <a href="https://www.guldagil.com" target="_blank" rel="noopener">https://www.guldagil.com</a>
                        </p>
                    </div>
                </section>

                <!-- 4. PROPRIÉTÉ INTELLECTUELLE -->
                <section class="legal-section">
                    <h2>4. 🎨 Propriété intellectuelle</h2>
                    <h3>Droits d'auteur</h3>
                    <p>
                        L'ensemble des contenus présents sur le portail Guldagil (textes, images, 
                        bases de données, logos, mise en page, charte graphique, fonctionnalités) 
                        sont protégés par le droit français et international de la propriété intellectuelle.
                    </p>
                    
                    <h3>Droits de reproduction</h3>
                    <p>
                        Toute reproduction, représentation, modification, publication, adaptation de tout 
                        ou partie des éléments du portail, quel que soit le moyen ou le procédé utilisé, 
                        est interdite, sauf autorisation écrite préalable de Guldagil.
                    </p>
                    
                    <h3>Marques et logos</h3>
                    <p>
                        Les marques et logos reproduits sur le portail sont déposés par Guldagil ou 
                        par des tiers ayant autorisé leur utilisation. Toute reproduction ou utilisation 
                        non autorisée constitue une contrefaçon sanctionnée par les articles L.335-2 
                        et suivants du Code de la propriété intellectuelle.
                    </p>
                    
                    <h3>Technologies tierces</h3>
                    <p>
                        Le portail utilise des technologies et composants sous licences diverses 
                        (open source, propriétaires). Les crédits détaillés sont disponibles dans 
                        la documentation technique.
                    </p>
                </section>

                <!-- 5. FINALITÉ DU PORTAIL -->
                <section class="legal-section">
                    <h2>5. 🎯 Finalité du portail</h2>
                    <h3>Portail interne d'entreprise</h3>
                    <p>
                        Le portail Guldagil est un <strong>outil interne</strong> destiné exclusivement :
                    </p>
                    <ul>
                        <li>À la gestion des opérations commerciales et industrielles</li>
                        <li>Au calcul et à l'optimisation des frais de transport</li>
                        <li>À la gestion administrative et logistique</li>
                        <li>À l'analyse des données métier</li>
                        <li>À la collaboration entre les équipes Guldagil</li>
                    </ul>
                    
                    <h3>Accès restreint</h3>
                    <p>
                        L'accès au portail est strictement réservé aux :
                    </p>
                    <ul>
                        <li>Employés et collaborateurs de Guldagil</li>
                        <li>Partenaires commerciaux autorisés</li>
                        <li>Prestataires techniques habilités</li>
                    </ul>
                    
                    <h3>Données de l'industrie du traitement de l'eau</h3>
                    <p>
                        Le portail intègre des données spécialisées dans le secteur du traitement 
                        de l'eau, de l'assainissement et des solutions industrielles environnementales.
                    </p>
                </section>

                <!-- 6. RESPONSABILITÉS ET LIMITATIONS -->
                <section class="legal-section">
                    <h2>6. ⚠️ Responsabilités et limitations</h2>
                    <h3>Disponibilité du service</h3>
                    <p>
                        Guldagil s'efforce d'assurer la disponibilité du portail 24h/24 et 7j/7, 
                        mais ne peut garantir une disponibilité absolue. Des interruptions peuvent 
                        survenir pour maintenance, mise à jour ou cas de force majeure.
                    </p>
                    
                    <h3>Exactitude des informations</h3>
                    <p>
                        Guldagil met tout en œuvre pour fournir des informations exactes et à jour. 
                        Cependant, des erreurs ou omissions peuvent survenir. Les utilisateurs sont 
                        invités à signaler toute anomalie.
                    </p>
                    
                    <h3>Utilisation des données de calcul</h3>
                    <p>
                        Les résultats de calculs fournis par le portail sont donnés à titre indicatif. 
                        Guldagil ne saurait être tenu responsable des décisions prises sur la base 
                        de ces calculs. Une validation manuelle reste recommandée pour les opérations critiques.
                    </p>
                    
                    <h3>Limitation de responsabilité</h3>
                    <p>
                        La responsabilité de Guldagil ne peut être engagée en cas de :
                    </p>
                    <ul>
                        <li>Dommages directs ou indirects résultant de l'utilisation du portail</li>
                        <li>Perte de données due à un dysfonctionnement technique</li>
                        <li>Interruption d'activité liée à l'indisponibilité du service</li>
                        <li>Utilisation non conforme aux présentes mentions légales</li>
                    </ul>
                </section>

                <!-- 7. PROTECTION DES DONNÉES -->
                <section class="legal-section">
                    <h2>7. 🔒 Protection des données personnelles</h2>
                    <h3>Conformité RGPD</h3>
                    <p>
                        Le traitement des données personnelles effectué sur le portail Guldagil 
                        est conforme au Règlement Général sur la Protection des Données (RGPD) 
                        et à la loi "Informatique et Libertés" modifiée.
                    </p>
                    
                    <h3>Responsable du traitement</h3>
                    <p>
                        <strong>Responsable du traitement :</strong> GULDAGIL<br>
                        <strong>Délégué à la Protection des Données (DPO) :</strong><br>
                        📧 <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                    
                    <h3>Finalités du traitement</h3>
                    <p>Les données personnelles sont traitées uniquement pour :</p>
                    <ul>
                        <li>L'authentification et la gestion des accès</li>
                        <li>Le bon fonctionnement du portail</li>
                        <li>L'audit et la sécurité du système</li>
                        <li>L'amélioration des services proposés</li>
                    </ul>
                    
                    <h3>Droits des utilisateurs</h3>
                    <p>
                        Conformément au RGPD, vous disposez des droits d'accès, de rectification, 
                        d'effacement, de portabilité et d'opposition. Pour exercer ces droits, 
                        contactez : <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                    
                    <p>
                        <strong>👉 Consulter notre <a href="/legal/privacy.php">Politique de confidentialité</a> complète</strong>
                    </p>
                </section>

                <!-- 8. SÉCURITÉ INFORMATIQUE -->
                <section class="legal-section">
                    <h2>8. 🔐 Sécurité informatique</h2>
                    <h3>Mesures de sécurité</h3>
                    <p>
                        Guldagil met en œuvre des mesures techniques et organisationnelles 
                        appropriées pour assurer la sécurité du portail :
                    </p>
                    <ul>
                        <li>Chiffrement des données sensibles (HTTPS/TLS)</li>
                        <li>Authentification forte et gestion des sessions</li>
                        <li>Contrôles d'accès par rôles et permissions</li>
                        <li>Surveillance et logs d'audit</li>
                        <li>Sauvegardes régulières et plan de continuité</li>
                        <li>Mise à jour de sécurité régulières</li>
                    </ul>
                    
                    <h3>Signalement d'incident</h3>
                    <p>
                        Tout incident de sécurité doit être signalé immédiatement à :<br>
                        📧 <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                    
                    <p>
                        <strong>👉 Consulter notre <a href="/legal/security.php">Politique de sécurité</a> complète</strong>
                    </p>
                </section>

                <!-- 9. COOKIES ET TECHNOLOGIES SIMILAIRES -->
                <section class="legal-section">
                    <h2>9. 🍪 Cookies et technologies similaires</h2>
                    <h3>Utilisation des cookies</h3>
                    <p>
                        Le portail Guldagil utilise uniquement des cookies techniques nécessaires 
                        au bon fonctionnement du service :
                    </p>
                    <ul>
                        <li><strong>Cookies de session</strong> : Authentification et navigation</li>
                        <li><strong>Cookies de préférences</strong> : Sauvegarde des paramètres utilisateur</li>
                        <li><strong>Cookies de sécurité</strong> : Protection contre les attaques</li>
                    </ul>
                    
                    <h3>Absence de tracking</h3>
                    <p>
                        <strong>Aucun cookie de tracking, publicitaire ou de profilage n'est utilisé.</strong>
                        Le portail ne collecte aucune donnée à des fins commerciales ou marketing.
                    </p>
                    
                    <h3>Gestion des cookies</h3>
                    <p>
                        Vous pouvez configurer votre navigateur pour accepter ou refuser les cookies. 
                        Cependant, le refus des cookies techniques peut affecter le fonctionnement du portail.
                    </p>
                </section>

                <!-- 10. DROIT APPLICABLE ET JURIDICTIONS -->
                <section class="legal-section">
                    <h2>10. ⚖️ Droit applicable et juridictions</h2>
                    <h3>Droit français</h3>
                    <p>
                        Les présentes mentions légales sont régies par le droit français. 
                        Elles sont rédigées en langue française et conformes à la réglementation 
                        française et européenne en vigueur.
                    </p>
                    
                    <h3>Juridiction compétente</h3>
                    <p>
                        En cas de litige, et à défaut de résolution amiable, les tribunaux français 
                        seront seuls compétents. La juridiction compétente sera déterminée selon 
                        les règles de droit commun.
                    </p>
                    
                    <h3>Résolution amiable</h3>
                    <p>
                        Avant tout recours contentieux, les parties s'efforceront de résoudre 
                        leur différend par voie amiable. Contact pour médiation :<br>
                        📧 <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a>
                    </p>
                </section>

                <!-- 11. MISE À JOUR DES MENTIONS LÉGALES -->
                <section class="legal-section">
                    <h2>11. 🔄 Mise à jour des mentions légales</h2>
                    <h3>Modifications</h3>
                    <p>
                        Guldagil se réserve le droit de modifier les présentes mentions légales 
                        à tout moment, notamment pour se conformer à toute évolution réglementaire, 
                        jurisprudentielle, éditoriale ou technique.
                    </p>
                    
                    <h3>Information des utilisateurs</h3>
                    <p>
                        Les utilisateurs seront informés de toute modification substantielle 
                        par les moyens de communication internes habituels (email, portail, etc.).
                    </p>
                    
                    <h3>Version en vigueur</h3>
                    <p>
                        La version des mentions légales applicable est celle en ligne au moment 
                        de l'accès au portail. La date de dernière mise à jour figure en en-tête 
                        de ce document.
                    </p>
                </section>

                <!-- 12. INFORMATIONS COMPLÉMENTAIRES -->
                <section class="legal-section">
                    <h2>12. ℹ️ Informations complémentaires</h2>
                    <div class="status-box status-info">
                        <h4>📝 Version beta - Portail en développement</h4>
                        <p>
                            Le portail Guldagil est actuellement en version <?= APP_VERSION ?> (beta). 
                            Les fonctionnalités et mentions légales peuvent évoluer en fonction 
                            du développement du produit.
                        </p>
                    </div>
                    
                    <div class="version-info">
                        <h4>🔍 Traçabilité technique</h4>
                        <ul>
                            <li><strong>Version portail :</strong> <?= APP_VERSION ?></li>
                            <li><strong>Build :</strong> <?= BUILD_NUMBER ?></li>
                            <li><strong>Date de compilation :</strong> <?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?></li>
                            <li><strong>Responsable technique :</strong> <?= APP_AUTHOR ?></li>
                        </ul>
                    </div>
                    
                    <div class="contact-info">
                        <h4>📞 Contacts utiles</h4>
                        <ul>
                            <li><strong>Support technique :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Questions légales :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Protection des données :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Sécurité informatique :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                        </ul>
                    </div>
                </section>
            </div>

            <!-- Footer de la page légale -->
            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/legal/" class="btn btn-secondary">📚 Centre légal</a>
                    <a href="/legal/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
                    <a href="/legal/terms.php" class="btn btn-secondary">📋 CGU</a>
                    <a href="/legal/security.php" class="btn btn-secondary">🔐 Sécurité</a>
                </div>
                
                <div class="legal-disclaimer">
                    <p>
                        <small>
                            ⚖️ <strong>Mentions légales conformes</strong> : Loi n° 2004-575 du 21 juin 2004 - 
                            RGPD - Code de la propriété intellectuelle - 
                            Dernière vérification réglementaire : <?= date('m/Y') ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>
</body>
</html>