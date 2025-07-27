<?php
/**
 * Titre: Politique de sécurité informatique complète
 * Chemin: /public/legal/security.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../config/config.php';

// Meta données de la page
$page_title = "Politique de sécurité";
$page_description = "Mesures de sécurité et procédures de protection du portail Guldagil";
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
    <link rel="stylesheet" href="/assets/css/security.css?v=<?= BUILD_NUMBER ?>">
    
    <link rel="canonical" href="/legal/security.php">
</head>
<body class="legal-page">
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>🔐 Politique de sécurité informatique</h1>
                <p class="legal-meta">
                    <strong>Portail interne Guldagil - Mesures de protection</strong><br>
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <!-- 1. OBJECTIFS ET PÉRIMÈTRE -->
                <section class="legal-section">
                    <h2>1. 🎯 Objectifs et périmètre</h2>
                    
                    <h3>Objectifs de sécurité</h3>
                    <p>
                        La politique de sécurité du portail Guldagil vise à assurer la protection, 
                        l'intégrité et la disponibilité des données et services informatiques.
                    </p>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>🔒 Confidentialité</h4>
                            <p>Protection des données sensibles contre l'accès non autorisé</p>
                        </div>
                        <div class="info-card">
                            <h4>🛡️ Intégrité</h4>
                            <p>Garantie de l'exactitude et de la complétude des données</p>
                        </div>
                        <div class="info-card">
                            <h4>⚡ Disponibilité</h4>
                            <p>Accès aux services pour les utilisateurs autorisés</p>
                        </div>
                        <div class="info-card">
                            <h4>📋 Traçabilité</h4>
                            <p>Audit et surveillance des actions utilisateurs</p>
                        </div>
                    </div>

                    <h3>Périmètre d'application</h3>
                    <ul>
                        <li><strong>Portail web</strong> : Interface utilisateur et modules métier</li>
                        <li><strong>Base de données</strong> : Données de transport, tarifs, utilisateurs</li>
                        <li><strong>Infrastructure</strong> : Serveurs, réseau, stockage</li>
                        <li><strong>Utilisateurs</strong> : Employés Guldagil et partenaires autorisés</li>
                    </ul>
                </section>

                <!-- 2. ARCHITECTURE SÉCURISÉE -->
                <section class="legal-section">
                    <h2>2. 🏗️ Architecture sécurisée</h2>
                    
                    <h3>Infrastructure technique</h3>
                    <div class="security-measures">
                        <div class="measure-category">
                            <h4>🌐 Serveur web</h4>
                            <ul>
                                <li>Configuration Apache/Nginx durcie</li>
                                <li>Masquage des informations serveur</li>
                                <li>Limitation des méthodes HTTP</li>
                                <li>Protection contre les attaques DoS</li>
                            </ul>
                        </div>
                        <div class="measure-category">
                            <h4>🗄️ Base de données</h4>
                            <ul>
                                <li>Accès restreint par IP</li>
                                <li>Chiffrement des connexions (TLS)</li>
                                <li>Comptes avec privilèges minimaux</li>
                                <li>Requêtes préparées (anti-injection SQL)</li>
                            </ul>
                        </div>
                        <div class="measure-category">
                            <h4>🔥 Pare-feu (Firewall)</h4>
                            <ul>
                                <li>Filtrage par IP source</li>
                                <li>Restriction des ports ouverts</li>
                                <li>Règles de trafic granulaires</li>
                                <li>Détection d'intrusion (IDS)</li>
                            </ul>
                        </div>
                        <div class="measure-category">
                            <h4>🔐 Chiffrement</h4>
                            <ul>
                                <li>HTTPS obligatoire (SSL/TLS 1.3)</li>
                                <li>Certificats validés</li>
                                <li>HSTS (HTTP Strict Transport Security)</li>
                                <li>Chiffrement des données sensibles</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Isolation des modules</h3>
                    <p>
                        Chaque module du portail est isolé logiquement pour limiter l'impact 
                        d'une éventuelle compromission :
                    </p>
                    <ul>
                        <li>Séparation des droits d'accès par module</li>
                        <li>Logs séparés pour audit et traçabilité</li>
                        <li>Sessions compartimentées</li>
                        <li>Validation des données à chaque niveau</li>
                    </ul>
                </section>

                <!-- 3. PROTECTION DES DONNÉES -->
                <section class="legal-section">
                    <h2>3. 📊 Protection des données</h2>
                    
                    <h3>Classification des données</h3>
                    <div class="data-classification">
                        <div class="data-level data-critical">
                            <h4>🔴 Critique</h4>
                            <p><strong>Tarifs transporteurs, données financières</strong></p>
                            <ul>
                                <li>Chiffrement AES-256</li>
                                <li>Accès admin uniquement</li>
                                <li>Audit complet</li>
                                <li>Sauvegarde chiffrée</li>
                            </ul>
                        </div>
                        <div class="data-level data-confidential">
                            <h4>🟠 Confidentiel</h4>
                            <p><strong>Données clients, informations ADR</strong></p>
                            <ul>
                                <li>Accès par rôles</li>
                                <li>Logs d'accès</li>
                                <li>Anonymisation si possible</li>
                                <li>Durée de rétention limitée</li>
                            </ul>
                        </div>
                        <div class="data-level data-internal">
                            <h4>🟡 Interne</h4>
                            <p><strong>Données utilisateurs, préférences</strong></p>
                            <ul>
                                <li>Protection RGPD</li>
                                <li>Minimisation des données</li>
                                <li>Consentement utilisateur</li>
                                <li>Droit d'effacement</li>
                            </ul>
                        </div>
                        <div class="data-level data-public">
                            <h4>🟢 Public</h4>
                            <p><strong>Documentation, informations générales</strong></p>
                            <ul>
                                <li>Accès libre (interne)</li>
                                <li>Validation éditoriale</li>
                                <li>Versioning</li>
                                <li>Sauvegarde standard</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Gestion des sessions</h3>
                    <ul>
                        <li>Sessions temporaires uniquement (pas de stockage local persistant)</li>
                        <li>Expiration automatique après inactivité (30 minutes)</li>
                        <li>Régénération des ID de session</li>
                        <li>Cookies sécurisés (HttpOnly, Secure, SameSite)</li>
                        <li>Tokens CSRF pour les formulaires critiques</li>
                    </ul>
                </section>

                <!-- 4. AUTHENTIFICATION ET AUTORISATIONS -->
                <section class="legal-section">
                    <h2>4. 🔑 Authentification et autorisations</h2>
                    
                    <h3>Système d'authentification</h3>
                    <div class="status-box status-info">
                        <h4>📋 Version actuelle (0.5 beta)</h4>
                        <p>
                            <strong>Authentification de base implémentée</strong><br>
                            • Sessions PHP sécurisées<br>
                            • Mots de passe hashés (bcrypt)<br>
                            • Rate limiting des tentatives<br>
                            <strong>Version 1.0 :</strong> Authentification multi-facteurs
                        </p>
                    </div>

                    <h3>Politique des mots de passe - Conforme ANSSI 2025</h3>
                    <ul>
                        <li><strong>Longueur minimale :</strong> 12 caractères (recommandation ANSSI)</li>
                        <li><strong>Complexité :</strong> 3 familles sur 4 (maj, min, chiffres, symboles)</li>
                        <li><strong>Historique :</strong> Interdiction des 12 derniers mots de passe</li>
                        <li><strong>Expiration :</strong> Pas d'expiration forcée (nouvelle recommandation ANSSI 2025)</li>
                        <li><strong>Stockage :</strong> Hash Argon2id (standard 2025) avec salt unique</li>
                        <li><strong>Dictionnaire :</strong> Vérification contre mots de passe courants</li>
                        <li><strong>Authentification échouée :</strong> Délai progressif après 3 échecs</li>
                    </ul>

                    <h3>Gestion des rôles et permissions</h3>
                    <div class="roles-grid">
                        <div class="role-card role-admin">
                            <h4>👑 Administrateur</h4>
                            <ul>
                                <li>Accès complet au portail</li>
                                <li>Gestion des utilisateurs</li>
                                <li>Configuration système</li>
                                <li>Accès aux logs et audits</li>
                            </ul>
                        </div>
                        <div class="role-card role-manager">
                            <h4>👨‍💼 Responsable</h4>
                            <ul>
                                <li>Gestion équipe</li>
                                <li>Validation tarifs</li>
                                <li>Rapports avancés</li>
                                <li>Configuration modules</li>
                            </ul>
                        </div>
                        <div class="role-card role-user">
                            <h4>👤 Utilisateur</h4>
                            <ul>
                                <li>Calculs transport</li>
                                <li>Consultation tarifs</li>
                                <li>Gestion expéditions</li>
                                <li>Profil personnel</li>
                            </ul>
                        </div>
                        <div class="role-card role-readonly">
                            <h4>👁️ Lecture seule</h4>
                            <ul>
                                <li>Consultation uniquement</li>
                                <li>Pas de modification</li>
                                <li>Rapports limités</li>
                                <li>Accès temporaire</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Protection contre les attaques</h3>
                    <ul>
                        <li><strong>Attaques par force brute :</strong> Limitation à 5 tentatives / 15 minutes</li>
                        <li><strong>Injection SQL :</strong> Requêtes préparées obligatoires</li>
                        <li><strong>XSS :</strong> Échappement de toutes les sorties</li>
                        <li><strong>CSRF :</strong> Tokens de validation sur les formulaires</li>
                        <li><strong>Clickjacking :</strong> Header X-Frame-Options</li>
                    </ul>
                </section>

                <!-- 5. SURVEILLANCE ET AUDIT -->
                <section class="legal-section">
                    <h2>5. 📊 Surveillance et audit</h2>
                    
                    <h3>Logs de sécurité</h3>
                    <div class="logs-grid">
                        <div class="log-category">
                            <h4>🔐 Authentification</h4>
                            <ul>
                                <li>Tentatives de connexion</li>
                                <li>Connexions réussies/échouées</li>
                                <li>Changements de mots de passe</li>
                                <li>Verrouillages de comptes</li>
                            </ul>
                            <p><strong>Rétention :</strong> 12 mois</p>
                        </div>
                        <div class="log-category">
                            <h4>📋 Actions utilisateurs</h4>
                            <ul>
                                <li>Accès aux modules</li>
                                <li>Consultations de tarifs</li>
                                <li>Modifications de données</li>
                                <li>Téléchargements</li>
                            </ul>
                            <p><strong>Rétention :</strong> 6 mois</p>
                        </div>
                        <div class="log-category">
                            <h4>⚠️ Événements système</h4>
                            <ul>
                                <li>Erreurs applications</li>
                                <li>Tentatives d'intrusion</li>
                                <li>Performances anormales</li>
                                <li>Indisponibilités</li>
                            </ul>
                            <p><strong>Rétention :</strong> 24 mois</p>
                        </div>
                        <div class="log-category">
                            <h4>🔧 Administration</h4>
                            <ul>
                                <li>Modifications configuration</li>
                                <li>Gestion utilisateurs</li>
                                <li>Mises à jour système</li>
                                <li>Opérations de maintenance</li>
                            </ul>
                            <p><strong>Rétention :</strong> 5 ans</p>
                        </div>
                    </div>

                    <h3>Monitoring en temps réel</h3>
                    <ul>
                        <li><strong>Alertes automatiques :</strong> Tentatives d'intrusion, erreurs critiques</li>
                        <li><strong>Tableaux de bord :</strong> Métriques sécurité en temps réel</li>
                        <li><strong>Rapports périodiques :</strong> Synthèse hebdomadaire des incidents</li>
                        <li><strong>Analyse comportementale :</strong> Détection d'anomalies utilisateurs</li>
                    </ul>
                </section>

                <!-- 6. SAUVEGARDE ET CONTINUITÉ -->
                <section class="legal-section">
                    <h2>6. 💾 Sauvegarde et continuité</h2>
                    
                    <h3>Stratégie de sauvegarde</h3>
                    <div class="backup-grid">
                        <div class="backup-type">
                            <h4>📅 Quotidienne</h4>
                            <ul>
                                <li>Base de données complète</li>
                                <li>Chiffrement AES-256</li>
                                <li>Vérification d'intégrité</li>
                                <li>Stockage sécurisé</li>
                            </ul>
                        </div>
                        <div class="backup-type">
                            <h4>📊 Hebdomadaire</h4>
                            <ul>
                                <li>Système complet</li>
                                <li>Code source</li>
                                <li>Configuration</li>
                                <li>Tests de restauration</li>
                            </ul>
                        </div>
                        <div class="backup-type">
                            <h4>📦 Mensuelle</h4>
                            <ul>
                                <li>Archive longue durée</li>
                                <li>Documentation</li>
                                <li>Historique des versions</li>
                                <li>Stockage externe</li>
                            </ul>
                        </div>
                        <div class="backup-type">
                            <h4>⚡ Temps réel</h4>
                            <ul>
                                <li>Réplication des données critiques</li>
                                <li>Synchronisation continue</li>
                                <li>Basculement automatique</li>
                                <li>Monitoring permanent</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Plan de continuité d'activité (PCA)</h3>
                    <div class="company-info">
                        <h4>🎯 Objectifs de reprise</h4>
                        <ul>
                            <li><strong>RTO (Recovery Time Objective) :</strong> 4 heures maximum</li>
                            <li><strong>RPO (Recovery Point Objective) :</strong> 1 heure maximum</li>
                            <li><strong>Disponibilité cible :</strong> 99.5% (temps d'arrêt < 44h/an)</li>
                        </ul>
                        
                        <h4>🔧 Procédures de reprise</h4>
                        <ul>
                            <li>Tests de restauration mensuels</li>
                            <li>Simulation d'incidents trimestriels</li>
                            <li>Site de secours opérationnel</li>
                            <li>Équipe d'astreinte 24/7</li>
                        </ul>
                    </div>
                </section>

                <!-- 7. CONFORMITÉ RÉGLEMENTAIRE -->
                <section class="legal-section">
                    <h2>7. 📜 Conformité réglementaire</h2>
                    
                    <h3>Conformité réglementaire renforcée 2025</h3>
                    <div class="compliance-checklist">
                        <ul>
                            <li>✅ <strong>RGPD 2025</strong> - Nouvelles obligations IA et algorithmes décisionnels</li>
                            <li>✅ <strong>Minimisation des données</strong> - Collecte limitée au strict nécessaire</li>
                            <li>✅ <strong>Privacy by Design</strong> - Protection dès la conception</li>
                            <li>✅ <strong>Droits renforcés</strong> - Portabilité, effacement, opposition</li>
                            <li>✅ <strong>Notification 72h</strong> - Procédure automatisée pour violations</li>
                            <li>✅ <strong>Audit annuel</strong> - Évaluation d'impact obligatoire</li>
                            <li>✅ <strong>Certifications sous-traitants</strong> - Validation CNIL 2025</li>
                        </ul>
                    </div>

                    <h3>Réglementation NIS2 (Directive 2025)</h3>
                    <ul>
                        <li><strong>Cybersécurité renforcée :</strong> Mesures techniques obligatoires</li>
                        <li><strong>Signalement incidents :</strong> 24h aux autorités compétentes</li>
                        <li><strong>Gouvernance :</strong> Responsabilité des dirigeants engagée</li>
                        <li><strong>Gestion des risques :</strong> Évaluation continue</li>
                        <li><strong>Supply chain :</strong> Sécurisation de la chaîne logicielle</li>
                    </ul>

                    <h3>Réglementation secteur transport</h3>
                    <ul>
                        <li><strong>ADR :</strong> Sécurisation des données matières dangereuses</li>
                        <li><strong>Douanes :</strong> Protection des informations d'expédition</li>
                        <li><strong>Concurrence :</strong> Confidentialité des tarifs négociés</li>
                    </ul>

                    <h3>Standards de sécurité 2025</h3>
                    <ul>
                        <li><strong>ISO 27001:2022</strong> - Système de management de la sécurité</li>
                        <li><strong>ANSSI 2025</strong> - Guide cybersécurité pour entreprises</li>
                        <li><strong>CNIL 2025</strong> - Référentiel sécurité données personnelles</li>
                        <li><strong>Framework NIST 2.0</strong> - Gestion des risques cyber</li>
                        <li><strong>Directive NIS2</strong> - Cybersécurité européenne renforcée</li>
                    </ul>
                </section>

                <!-- 8. GESTION DES INCIDENTS -->
                <section class="legal-section">
                    <h2>8. 🚨 Gestion des incidents - Procédure NIS2</h2>
                    
                    <h3>Processus de signalement conforme</h3>
                    <div class="incident-flow">
                        <div class="flow-step">
                            <h4>1. 🔍 Détection</h4>
                            <p>Automatique (SIEM) ou manuelle (utilisateur/admin)</p>
                        </div>
                        <div class="flow-step">
                            <h4>2. 📞 Notification immédiate</h4>
                            <p>Contact : <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a><br>
                            Astreinte 24/7 pour incidents critiques</p>
                        </div>
                        <div class="flow-step">
                            <h4>3. ⚡ Évaluation rapide</h4>
                            <p>Classification selon grille NIS2<br>
                            Impact métier et technique</p>
                        </div>
                        <div class="flow-step">
                            <h4>4. 🔧 Résolution</h4>
                            <p>Plan de réponse adapté<br>
                            Communication aux parties prenantes</p>
                        </div>
                    </div>

                    <h3>Classification des incidents - Mise à jour 2025</h3>
                    <div class="incident-levels">
                        <div class="incident-level incident-critical">
                            <h4>🔴 Critique</h4>
                            <p><strong>Délai :</strong> 1 heure maximum</p>
                            <ul>
                                <li>Cyberattaque confirmée (ransomware, APT)</li>
                                <li>Fuite massive de données clients</li>
                                <li>Compromission infrastructure critique</li>
                                <li>Indisponibilité métier > 4h</li>
                            </ul>
                            <p><strong>Notification :</strong> ANSSI + CNIL (24h)</p>
                        </div>
                        <div class="incident-level incident-major">
                            <h4>🟠 Majeur</h4>
                            <p><strong>Délai :</strong> 4 heures maximum</p>
                            <ul>
                                <li>Tentative d'intrusion détectée</li>
                                <li>Dysfonctionnement module critique</li>
                                <li>Faille de sécurité identifiée</li>
                                <li>Perte partielle de données</li>
                            </ul>
                            <p><strong>Notification :</strong> Direction + DSI</p>
                        </div>
                        <div class="incident-level incident-minor">
                            <h4>🟡 Mineur</h4>
                            <p><strong>Délai :</strong> 24 heures</p>
                            <ul>
                                <li>Erreur applicative sans impact données</li>
                                <li>Performance dégradée temporaire</li>
                                <li>Bug interface utilisateur</li>
                                <li>Alerte de sécurité préventive</li>
                            </ul>
                            <p><strong>Notification :</strong> Équipe technique</p>
                        </div>
                    </div>

                    <h3>Notifications obligatoires renforcées</h3>
                    <ul>
                        <li><strong>CNIL :</strong> Violation données personnelles (72h)</li>
                        <li><strong>ANSSI :</strong> Incident cybersécurité critique (24h)</li>
                        <li><strong>Direction :</strong> Tout incident majeur/critique (immédiat)</li>
                        <li><strong>Utilisateurs :</strong> Impact sur leurs données (72h max)</li>
                        <li><strong>Autorités sectorielles :</strong> Selon réglementation transport</li>
                        <li><strong>Assureur cyber :</strong> Déclaration sinistre (48h)</li>
                    </ul>neur</h4>
                            <p><strong>Délai :</strong> 24 heures</p>
                            <ul>
                                <li>Bug interface utilisateur</li>
                                <li>Performance dégradée</li>
                                <li>Erreur de calcul ponctuelle</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Notifications obligatoires</h3>
                    <ul>
                        <li><strong>CNIL :</strong> Violation de données personnelles (72h)</li>
                        <li><strong>Direction :</strong> Tout incident de sécurité (immédiat)</li>
                        <li><strong>Utilisateurs :</strong> Impact sur leurs données (72h)</li>
                        <li><strong>Partenaires :</strong> Selon accord contractuel</li>
                    </ul>
                </section>

                <!-- 9. FORMATION ET SENSIBILISATION -->
                <section class="legal-section">
                    <h2>9. 🎓 Formation et sensibilisation</h2>
                    
                    <h3>Programme de formation</h3>
                    <div class="training-grid">
                        <div class="training-module">
                            <h4>👤 Nouveaux utilisateurs</h4>
                            <ul>
                                <li>Politique de sécurité</li>
                                <li>Bonnes pratiques mots de passe</li>
                                <li>Reconnaissance phishing</li>
                                <li>Utilisation sécurisée du portail</li>
                            </ul>
                        </div>
                        <div class="training-module">
                            <h4>👑 Administrateurs</h4>
                            <ul>
                                <li>Gestion des incidents</li>
                                <li>Configuration sécurisée</li>
                                <li>Audit et surveillance</li>
                                <li>Conformité réglementaire</li>
                            </ul>
                        </div>
                        <div class="training-module">
                            <h4>🔄 Formation continue</h4>
                            <ul>
                                <li>Mise à jour annuelle</li>
                                <li>Tests de phishing</li>
                                <li>Veille sécurité</li>
                                <li>Retours d'expérience incidents</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Sensibilisation quotidienne</h3>
                    <ul>
                        <li><strong>Rappels sécurité :</strong> Messages sur le portail</li>
                        <li><strong>Documentation :</strong> Guide utilisateur accessible</li>
                        <li><strong>Support :</strong> Assistance technique réactive</li>
                        <li><strong>Communication :</strong> Bulletins sécurité trimestriels</li>
                    </ul>
                </section>

                <!-- 10. ÉVOLUTION ET AMÉLIORATION -->
                <section class="legal-section">
                    <h2>10. 🔄 Évolution et amélioration</h2>
                    
                    <h3>Révision de la politique</h3>
                    <p>
                        Cette politique de sécurité fait l'objet d'une révision :
                    </p>
                    <ul>
                        <li><strong>Annuelle :</strong> Mise à jour complète</li>
                        <li><strong>Trimestrielle :</strong> Ajustements mineurs</li>
                        <li><strong>Exceptionnelle :</strong> Suite à incident majeur ou évolution réglementaire</li>
                    </ul>

                    <h3>Amélioration continue</h3>
                    <div class="improvement-cycle">
                        <div class="cycle-step">
                            <h4>📊 Mesure</h4>
                            <p>KPI sécurité et indicateurs de performance</p>
                        </div>
                        <div class="cycle-step">
                            <h4>📈 Analyse</h4>
                            <p>Évaluation des risques et vulnérabilités</p>
                        </div>
                        <div class="cycle-step">
                            <h4>🎯 Planification</h4>
                            <p>Plan d'actions et investissements sécurité</p>
                        </div>
                        <div class="cycle-step">
                            <h4>🚀 Mise en œuvre</h4>
                            <p>Déploiement des améliorations</p>
                        </div>
                    </div>

                    <h3>Roadmap sécurité</h3>
                    <div class="version-info">
                        <h4>🗓️ Évolutions prévues</h4>
                        <ul>
                            <li><strong>Version 1.0 :</strong> Authentification multi-facteurs (2FA)</li>
                            <li><strong>2025 Q3 :</strong> Détection comportementale avancée</li>
                            <li><strong>2025 Q4 :</strong> Chiffrement end-to-end des communications</li>
                            <li><strong>2026 :</strong> Intelligence artificielle pour la détection de menaces</li>
                        </ul>
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
                    <a href="/legal/cookies.php" class="btn btn-secondary">🍪 Cookies</a>
                </div>
                
                <div class="legal-disclaimer">
                    <p>
                        <small>
                            🔐 <strong>Politique de sécurité conforme</strong> : ISO 27001 - ANSSI - RGPD 2025 - 
                            Standards industrie - 
                            Dernière révision sécurité : <?= date('m/Y') ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- Horodatage et build fixe OBLIGATOIRE -->
    <div class="build-info">
        <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?> - <?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?></p>
    </div>

    <!-- Styles spécifiques pour la politique de sécurité -->
    <style>
    .security-measures {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin: 1.5rem 0;
    }

    .measure-category {
        background: var(--gray-50, #f9fafb);
        padding: 1.5rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--blue-500, #3b82f6);
    }

    .measure-category h4 {
        color: var(--blue-700, #1d4ed8);
        margin: 0 0 1rem 0;
        font-weight: 600;
    }

    .data-classification {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .data-level {
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 2px solid;
    }

    .data-critical {
        background: #fef2f2;
        border-color: #dc2626;
        color: #991b1b;
    }

    .data-confidential {
        background: #fff7ed;
        border-color: #ea580c;
        color: #9a3412;
    }

    .data-internal {
        background: #fefce8;
        border-color: #ca8a04;
        color: #854d0e;
    }

    .data-public {
        background: #f0fdf4;
        border-color: #16a34a;
        color: #166534;
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .role-card {
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--gray-300, #d1d5db);
    }

    .role-admin {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-color: #dc2626;
    }

    .role-manager {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-color: #2563eb;
    }

    .role-user {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-color: #16a34a;
    }

    .role-readonly {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        border-color: #6b7280;
    }

    .logs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin: 1.5rem 0;
    }

    .log-category {
        background: white;
        border: 1px solid var(--gray-200, #e5e7eb);
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .log-category h4 {
        color: var(--gray-900, #111827);
        margin: 0 0 1rem 0;
        font-weight: 600;
    }

    .backup-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .backup-type {
        background: var(--blue-50, #eff6ff);
        border: 1px solid var(--blue-200, #bfdbfe);
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
    }

    .backup-type h4 {
        color: var(--blue-700, #1d4ed8);
        margin: 0 0 1rem 0;
        font-weight: 600;
    }

    .incident-flow {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .flow-step {
        background: white;
        border: 2px solid var(--gray-200, #e5e7eb);
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
        position: relative;
    }

    .flow-step h4 {
        color: var(--gray-900, #111827);
        margin: 0 0 0.5rem 0;
        font-weight: 600;
    }

    .incident-levels {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .incident-level {
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 2px solid;
    }

    .incident-critical {
        background: #fef2f2;
        border-color: #dc2626;
        color: #991b1b;
    }

    .incident-major {
        background: #fff7ed;
        border-color: #ea580c;
        color: #9a3412;
    }

    .incident-minor {
        background: #fefce8;
        border-color: #ca8a04;
        color: #854d0e;
    }

    .training-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin: 1.5rem 0;
    }

    .training-module {
        background: var(--green-50, #f0fdf4);
        border: 1px solid var(--green-200, #bbf7d0);
        border-radius: 0.5rem;
        padding: 1.5rem;
    }

    .training-module h4 {
        color: var(--green-700, #15803d);
        margin: 0 0 1rem 0;
        font-weight: 600;
    }

    .improvement-cycle {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .cycle-step {
        background: var(--purple-50, #faf5ff);
        border: 1px solid var(--purple-200, #e9d5ff);
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
    }

    .cycle-step h4 {
        color: var(--purple-700, #7c3aed);
        margin: 0 0 0.5rem 0;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .security-measures,
        .data-classification,
        .roles-grid,
        .logs-grid,
        .backup-grid,
        .incident-flow,
        .incident-levels,
        .training-grid,
        .improvement-cycle {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>