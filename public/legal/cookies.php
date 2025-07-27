<?php
/**
 * Titre: Politique de cookies conforme à la réglementation française 2025
 * Chemin: /public/legal/cookies.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/config.php';

// Meta données de la page
$page_title = "Politique de cookies";
$page_description = "Gestion des cookies et technologies similaires sur le portail Guldagil - Conformité CNIL 2025";
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
    
    <link rel="canonical" href="/legal/cookies.php">
</head>
<body class="legal-page">
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>🍪 Politique de cookies</h1>
                <p class="legal-meta">
                    <strong>Conformément à la réglementation CNIL et directive ePrivacy 2025</strong><br>
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <!-- 1. QU'EST-CE QU'UN COOKIE -->
                <section class="legal-section">
                    <h2>1. 🔍 Qu'est-ce qu'un cookie ?</h2>
                    <h3>Définition</h3>
                    <p>
                        Un cookie est un petit fichier texte déposé sur votre ordinateur, tablette ou 
                        smartphone lorsque vous visitez un site web. Il permet au site de mémoriser 
                        vos actions et préférences pendant une durée déterminée.
                    </p>
                    
                    <h3>Types de cookies</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>🔧 Cookies techniques</h4>
                            <p>
                                Nécessaires au fonctionnement du site. Ils permettent la navigation, 
                                l'authentification et la sécurité.
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>📊 Cookies analytiques</h4>
                            <p>
                                Permettent de mesurer l'audience et analyser la performance du site 
                                de manière anonyme.
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>⚙️ Cookies de préférences</h4>
                            <p>
                                Mémorisent vos choix et personnalisations pour améliorer votre 
                                expérience utilisateur.
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🎯 Cookies publicitaires</h4>
                            <p style="color: var(--red-600, #dc2626); font-weight: 600;">
                                ❌ NON UTILISÉS sur le portail Guldagil
                            </p>
                        </div>
                    </div>
                </section>

                <!-- 2. COOKIES UTILISÉS SUR LE PORTAIL -->
                <section class="legal-section">
                    <h2>2. 🛠️ Cookies utilisés sur le portail Guldagil</h2>
                    
                    <div class="status-box status-success">
                        <h4>✅ Approche minimaliste</h4>
                        <p>
                            Le portail Guldagil utilise uniquement les cookies strictement nécessaires 
                            à son fonctionnement. Aucun cookie de tracking, publicitaire ou de profilage 
                            n'est déployé.
                        </p>
                    </div>

                    <h3>Cookies techniques obligatoires</h3>
                    <div class="cookie-table">
                        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                            <thead style="background: var(--gray-50, #f9fafb);">
                                <tr>
                                    <th style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db); text-align: left;">Nom du cookie</th>
                                    <th style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db); text-align: left;">Finalité</th>
                                    <th style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db); text-align: left;">Durée</th>
                                    <th style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db); text-align: left;">Éditeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);"><strong>PHPSESSID</strong></td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Authentification et gestion de session</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Session (supprimé à la fermeture)</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Guldagil</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);"><strong>auth_token</strong></td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Maintien de la connexion sécurisée</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">24 heures</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Guldagil</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);"><strong>user_preferences</strong></td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Sauvegarde des paramètres d'affichage</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">30 jours</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Guldagil</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);"><strong>csrf_token</strong></td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Protection contre les attaques CSRF</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Session</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Guldagil</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);"><strong>lang</strong></td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Langue préférée de l'interface</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">1 an</td>
                                    <td style="padding: 0.75rem; border: 1px solid var(--gray-300, #d1d5db);">Guldagil</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>Cookies absents</h3>
                    <div class="status-box status-info">
                        <h4>🚫 Technologies NON utilisées</h4>
                        <ul>
                            <li><strong>Google Analytics</strong> - Aucun tracking d'audience externe</li>
                            <li><strong>Facebook Pixel</strong> - Aucun pixel publicitaire</li>
                            <li><strong>Cookies de retargeting</strong> - Aucun suivi publicitaire</li>
                            <li><strong>Cookies tiers</strong> - Aucun partage avec des sociétés externes</li>
                            <li><strong>Fingerprinting</strong> - Aucune identification de l'appareil</li>
                        </ul>
                    </div>
                </section>

                <!-- 3. FINALITÉS ET BASE LÉGALE -->
                <section class="legal-section">
                    <h2>3. ⚖️ Finalités et base légale</h2>
                    
                    <h3>Cookies exemptés de consentement</h3>
                    <p>
                        Conformément à l'article 82 de la loi Informatique et Libertés et aux 
                        recommandations de la CNIL, certains cookies sont exemptés de consentement :
                    </p>
                    <ul>
                        <li><strong>Authentification :</strong> Identification de l'utilisateur connecté</li>
                        <li><strong>Sécurité :</strong> Protection contre les attaques informatiques</li>
                        <li><strong>Préférences :</strong> Mémorisation des choix utilisateur (langue, affichage)</li>
                        <li><strong>Panier :</strong> Maintien du contenu des formulaires en cours</li>
                        <li><strong>Équilibrage de charge :</strong> Répartition du trafic serveur</li>
                    </ul>

                    <h3>Base légale</h3>
                    <div class="company-info">
                        <p>
                            <strong>Article 82 - Loi Informatique et Libertés :</strong><br>
                            "Les cookies strictement nécessaires à la fourniture d'un service de 
                            communication en ligne expressément demandé par l'utilisateur sont 
                            exemptés du recueil du consentement."
                        </p>
                        <p>
                            <strong>Intérêt légitime :</strong> Assurer le bon fonctionnement et la 
                            sécurité du portail interne d'entreprise.
                        </p>
                    </div>
                </section>

                <!-- 4. DURÉE DE CONSERVATION -->
                <section class="legal-section">
                    <h2>4. ⏰ Durée de conservation</h2>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>🔒 Cookies de session</h4>
                            <p>
                                <strong>Durée :</strong> Suppression automatique à la fermeture du navigateur<br>
                                <strong>Usage :</strong> Authentification et navigation
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>⚙️ Cookies persistants</h4>
                            <p>
                                <strong>Durée maximale :</strong> 1 an<br>
                                <strong>Usage :</strong> Préférences utilisateur et langue
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🛡️ Cookies de sécurité</h4>
                            <p>
                                <strong>Durée :</strong> 24 heures maximum<br>
                                <strong>Usage :</strong> Tokens d'authentification et CSRF
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🧹 Nettoyage automatique</h4>
                            <p>
                                <strong>Fréquence :</strong> Quotidien<br>
                                <strong>Action :</strong> Suppression des cookies expirés
                            </p>
                        </div>
                    </div>

                    <h3>Règles de conservation</h3>
                    <ul>
                        <li>Maximum 13 mois pour les cookies de mesure d'audience (non utilisés actuellement)</li>
                        <li>Maximum 6 mois pour les cookies publicitaires (non utilisés)</li>
                        <li>Suppression automatique lors de la déconnexion pour les données sensibles</li>
                        <li>Renouvellement du consentement tous les 13 mois (si applicable)</li>
                    </ul>
                </section>

                <!-- 5. GESTION DES COOKIES -->
                <section class="legal-section">
                    <h2>5. 🎛️ Gestion de vos cookies</h2>
                    
                    <h3>Paramétrage de votre navigateur</h3>
                    <p>
                        Vous pouvez configurer votre navigateur pour accepter ou refuser les cookies :
                    </p>
                    
                    <div class="browser-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
                        <div class="browser-card" style="background: var(--gray-50, #f9fafb); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--gray-200, #e5e7eb);">
                            <h4>🌐 Chrome</h4>
                            <p style="font-size: 0.875rem;">
                                Paramètres > Confidentialité et sécurité > Cookies et autres données de sites
                            </p>
                        </div>
                        <div class="browser-card" style="background: var(--gray-50, #f9fafb); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--gray-200, #e5e7eb);">
                            <h4>🦊 Firefox</h4>
                            <p style="font-size: 0.875rem;">
                                Préférences > Vie privée et sécurité > Cookies et données de sites
                            </p>
                        </div>
                        <div class="browser-card" style="background: var(--gray-50, #f9fafb); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--gray-200, #e5e7eb);">
                            <h4>🧭 Safari</h4>
                            <p style="font-size: 0.875rem;">
                                Préférences > Confidentialité > Gérer les données de sites web
                            </p>
                        </div>
                        <div class="browser-card" style="background: var(--gray-50, #f9fafb); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--gray-200, #e5e7eb);">
                            <h4>⚡ Edge</h4>
                            <p style="font-size: 0.875rem;">
                                Paramètres > Cookies et autorisations de site > Gérer et supprimer les cookies
                            </p>
                        </div>
                    </div>

                    <div class="status-box status-warning">
                        <h4>⚠️ Impact du refus des cookies</h4>
                        <p>
                            Le refus des cookies techniques peut affecter le bon fonctionnement du portail :
                        </p>
                        <ul>
                            <li>Impossibilité de se connecter ou maintenir la session</li>
                            <li>Perte des préférences d'affichage</li>
                            <li>Fonctionnalités de sécurité désactivées</li>
                            <li>Nécessité de ressaisir les informations à chaque visite</li>
                        </ul>
                    </div>

                    <h3>Suppression des cookies existants</h3>
                    <p>
                        Pour supprimer les cookies déjà installés :
                    </p>
                    <ol>
                        <li>Accédez aux paramètres de votre navigateur</li>
                        <li>Recherchez la section "Confidentialité" ou "Cookies"</li>
                        <li>Sélectionnez "Effacer les données de navigation"</li>
                        <li>Choisissez la période et cochez "Cookies"</li>
                        <li>Validez la suppression</li>
                    </ol>
                </section>

                <!-- 6. COOKIES ET PORTAIL INTERNE -->
                <section class="legal-section">
                    <h2>6. 🏢 Spécificités du portail interne</h2>
                    
                    <h3>Usage professionnel</h3>
                    <div class="company-info">
                        <p>
                            Le portail Guldagil étant un outil interne d'entreprise destiné aux 
                            collaborateurs et partenaires professionnels, l'utilisation des cookies 
                            techniques s'inscrit dans le cadre de l'intérêt légitime de l'entreprise 
                            pour assurer :
                        </p>
                        <ul>
                            <li>La sécurité des données professionnelles</li>
                            <li>L'authentification des utilisateurs autorisés</li>
                            <li>Le bon fonctionnement des outils métier</li>
                            <li>La traçabilité des actions pour l'audit</li>
                        </ul>
                    </div>

                    <h3>Accès contrôlé</h3>
                    <p>
                        L'accès au portail étant restreint aux personnes habilitées, l'utilisation 
                        des cookies techniques est considérée comme acceptée dans le cadre de 
                        l'utilisation professionnelle des outils fournis par l'entreprise.
                    </p>

                    <h3>Données anonymisées</h3>
                    <p>
                        Aucune donnée personnelle n'est collectée à des fins commerciales ou de 
                        profilage. Les cookies techniques ne permettent pas d'identifier 
                        personnellement les utilisateurs en dehors du contexte professionnel.
                    </p>
                </section>

                <!-- 7. CONFORMITÉ RÉGLEMENTAIRE -->
                <section class="legal-section">
                    <h2>7. 📜 Conformité réglementaire</h2>
                    
                    <h3>Textes de référence</h3>
                    <div class="compliance-checklist">
                        <ul>
                            <li>✅ <strong>RGPD</strong> (Règlement 2016/679) - Protection des données</li>
                            <li>✅ <strong>Directive ePrivacy</strong> (2002/58/CE) - Communications électroniques</li>
                            <li>✅ <strong>Loi Informatique et Libertés</strong> (modifiée 2018-2025)</li>
                            <li>✅ <strong>Recommandations CNIL 2025</strong> - Cookies et traceurs</li>
                            <li>✅ <strong>Code de la consommation</strong> - Information des utilisateurs</li>
                        </ul>
                    </div>

                    <h3>Mise en conformité</h3>
                    <p>
                        Cette politique de cookies respecte les dernières évolutions réglementaires :
                    </p>
                    <ul>
                        <li><strong>Transparence :</strong> Information claire sur les cookies utilisés</li>
                        <li><strong>Minimisation :</strong> Utilisation limitée aux cookies strictement nécessaires</li>
                        <li><strong>Finalité :</strong> Objectifs précis et légitimes</li>
                        <li><strong>Durée :</strong> Conservation limitée dans le temps</li>
                        <li><strong>Contrôle :</strong> Moyens de gestion pour l'utilisateur</li>
                    </ul>

                    <h3>Audit et contrôles</h3>
                    <p>
                        La conformité de cette politique fait l'objet de contrôles réguliers :
                    </p>
                    <ul>
                        <li>Audit technique trimestriel des cookies déployés</li>
                        <li>Vérification de la durée de conservation</li>
                        <li>Test des fonctionnalités de gestion utilisateur</li>
                        <li>Mise à jour selon l'évolution réglementaire</li>
                    </ul>
                </section>

                <!-- 8. TECHNOLOGIES ALTERNATIVES -->
                <section class="legal-section">
                    <h2>8. 🔧 Technologies alternatives</h2>
                    
                    <h3>Stockage local</h3>
                    <p>
                        Le portail peut utiliser d'autres technologies de stockage local :
                    </p>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>💾 Local Storage</h4>
                            <p>
                                Stockage persistant des préférences utilisateur non sensibles 
                                (thème, langue, paramètres d'affichage).
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🔄 Session Storage</h4>
                            <p>
                                Stockage temporaire des données de formulaires en cours de saisie 
                                pour éviter les pertes de données.
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🗃️ IndexedDB</h4>
                            <p>
                                Stockage local de données volumineuses pour le fonctionnement 
                                hors ligne (cache des ressources).
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>🏷️ Web Beacons</h4>
                            <p style="color: var(--red-600, #dc2626); font-weight: 600;">
                                ❌ NON UTILISÉS - Aucun pixel de tracking
                            </p>
                        </div>
                    </div>

                    <h3>Gestion des technologies alternatives</h3>
                    <p>
                        Ces technologies suivent les mêmes principes que les cookies :
                    </p>
                    <ul>
                        <li>Utilisation limitée aux besoins fonctionnels</li>
                        <li>Pas de données personnelles sensibles</li>
                        <li>Suppression automatique selon les règles de conservation</li>
                        <li>Transparence sur leur utilisation</li>
                    </ul>
                </section>

                <!-- 9. CONTACTS ET RÉCLAMATIONS -->
                <section class="legal-section">
                    <h2>9. 📞 Contacts et réclamations</h2>
                    
                    <h3>Questions sur les cookies</h3>
                    <div class="contact-info">
                        <p>
                            Pour toute question relative à cette politique de cookies :
                        </p>
                        <ul>
                            <li><strong>Support technique :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Délégué à la Protection des Données :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Questions légales :</strong> <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a></li>
                            <li><strong>Téléphone :</strong> 03 89 44 13 17</li>
                        </ul>
                    </div>

                    <h3>Droit de réclamation</h3>
                    <p>
                        En cas de désaccord avec le traitement de vos données via les cookies, 
                        vous disposez du droit de saisir l'autorité de contrôle compétente :
                    </p>
                    <div class="company-info">
                        <p>
                            <strong>Commission Nationale de l'Informatique et des Libertés (CNIL)</strong><br>
                            3 Place de Fontenoy - TSA 80715<br>
                            75334 PARIS CEDEX 07<br>
                            <strong>Téléphone :</strong> 01 53 73 22 22<br>
                            <strong>Site web :</strong> <a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>
                        </p>
                    </div>
                </section>

                <!-- 10. ÉVOLUTIONS ET MISES À JOUR -->
                <section class="legal-section">
                    <h2>10. 🔄 Évolutions et mises à jour</h2>
                    
                    <h3>Modifications de la politique</h3>
                    <p>
                        Cette politique de cookies peut être modifiée pour s'adapter :
                    </p>
                    <ul>
                        <li>Aux évolutions réglementaires (CNIL, RGPD, ePrivacy)</li>
                        <li>Aux nouvelles fonctionnalités du portail</li>
                        <li>Aux recommandations des autorités de contrôle</li>
                        <li>Aux retours et demandes des utilisateurs</li>
                    </ul>

                    <h3>Information des utilisateurs</h3>
                    <p>
                        Les modifications importantes feront l'objet d'une information via :
                    </p>
                    <ul>
                        <li>Notification lors de la connexion au portail</li>
                        <li>Email d'information aux utilisateurs actifs</li>
                        <li>Mise à jour de la date en en-tête de ce document</li>
                        <li>Archivage des versions précédentes</li>
                    </ul>

                    <h3>Historique des versions</h3>
                    <div class="version-info">
                        <ul>
                            <li><strong>Version <?= APP_VERSION ?> :</strong> <?= date('d/m/Y', BUILD_TIMESTAMP) ?> - Version initiale</li>
                            <li><strong>Prochaine révision :</strong> <?= date('d/m/Y', strtotime('+6 months', BUILD_TIMESTAMP)) ?></li>
                            <li><strong>Responsable :</strong> <?= APP_AUTHOR ?></li>
                        </ul>
                    </div>
                </section>

                <!-- 11. RÉSUMÉ EXÉCUTIF -->
                <section class="legal-section">
                    <h2>11. 📋 Résumé exécutif</h2>
                    
                    <div class="status-box status-success">
                        <h4>✅ Points clés à retenir</h4>
                        <ul>
                            <li><strong>Utilisation minimale :</strong> Seuls les cookies techniques nécessaires</li>
                            <li><strong>Pas de tracking :</strong> Aucun cookie publicitaire ou de profilage</li>
                            <li><strong>Sécurité renforcée :</strong> Protection des données et authentification</li>
                            <li><strong>Durée limitée :</strong> Conservation selon les besoins fonctionnels</li>
                            <li><strong>Contrôle utilisateur :</strong> Possibilité de gestion via le navigateur</li>
                            <li><strong>Conformité RGPD :</strong> Respect des réglementations 2025</li>
                        </ul>
                    </div>

                    <div class="status-box status-info">
                        <h4>🔍 Audit de conformité</h4>
                        <p>
                            <strong>Dernière vérification :</strong> <?= date('d/m/Y') ?><br>
                            <strong>Statut :</strong> Conforme CNIL 2025<br>
                            <strong>Prochaine révision :</strong> <?= date('d/m/Y', strtotime('+3 months')) ?>
                        </p>
                    </div>
                </section>
            </div>

            <!-- Footer de la page légale -->
            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/legal/" class="btn btn-secondary">📚 Centre légal</a>
                    <a href="/legal/mentions.php" class="btn btn-secondary">⚖️ Mentions légales</a>
                    <a href="/legal/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
                    <a href="/legal/terms.php" class="btn btn-secondary">📋 CGU</a>
                </div>
                
                <div class="legal-disclaimer">
                    <p>
                        <small>
                            🍪 <strong>Politique conforme</strong> : CNIL 2025 - RGPD - Directive ePrivacy - 
                            Loi Informatique et Libertés - 
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