<?php
/**
 * Titre: Politique de sécurité et bonnes pratiques
 * Chemin: /public/security.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../config/version.php';

// Meta données de la page
$page_title = "Politique de sécurité";
$page_description = "Mesures de sécurité et bonnes pratiques - Portail Guldagil";
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
                <h1>🔐 Politique de sécurité</h1>
                <p class="legal-meta">
                    Dernière mise à jour : <?= date('d/m/Y', BUILD_TIMESTAMP) ?><br>
                    Version du portail : <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
            </div>

            <div class="legal-content">
                <section class="legal-section">
                    <h2>1. Présentation générale</h2>
                    <p>
                        La sécurité du portail "<?= APP_NAME ?>" est une priorité absolue. 
                        Cette politique définit les mesures techniques et organisationnelles 
                        mises en place pour protéger les données et assurer la continuité de service.
                    </p>
                    <div class="status-box status-security">
                        <h4>🔒 Niveau de sécurité actuel</h4>
                        <p>
                            <strong>Version <?= APP_VERSION ?></strong> - Sécurité de base implémentée<br>
                            Authentification avancée prévue pour la version 1.0
                        </p>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>2. Architecture sécurisée</h2>
                    <h3>2.1 Infrastructure</h3>
                    <ul>
                        <li><strong>Serveur web</strong> : Configuration durcie, ports non standard</li>
                        <li><strong>Base de données</strong> : Accès restreint, connexions chiffrées</li>
                        <li><strong>Firewall</strong> : Filtrage IP, règles restrictives</li>
                        <li><strong>SSL/TLS</strong> : Chiffrement des communications (HTTPS)</li>
                    </ul>

                    <h3>2.2 Isolation des modules</h3>
                    <ul>
                        <li>Séparation logique des fonctionnalités</li>
                        <li>Droits d'accès granulaires par module</li>
                        <li>Logs séparés pour audit et traçabilité</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>3. Protection des données</h2>
                    <h3>3.1 Données sensibles</h3>
                    <div class="security-grid">
                        <div class="security-item">
                            <h4>💰 Tarifs transporteurs</h4>
                            <p>Chiffrement en base, accès restreint admin</p>
                        </div>
                        <div class="security-item">
                            <h4>📋 Données ADR</h4>
                            <p>Classification sécurisée, audit trail</p>
                        </div>
                        <div class="security-item">
                            <h4>🏭 Informations qualité</h4>
                            <p>Traçabilité complète, sauvegarde chiffrée</p>
                        </div>
                        <div class="security-item">
                            <h4>👤 Données utilisateurs</h4>
                            <p>Minimisation, pseudonymisation</p>
                        </div>
                    </div>

                    <h3>3.2 Gestion des sessions</h3>
                    <ul>
                        <li>Sessions temporaires uniquement</li>
                        <li>Pas de stockage local des données sensibles</li>
                        <li>Expiration automatique d'inactivité</li>
                        <li>Tokens sécurisés pour les API futures</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Contrôle d'accès</h2>
                    <div class="status-box status-development">
                        <h4>🚧 Authentification en développement</h4>
                        <p>
                            <strong>Version actuelle :</strong> Accès libre pour développement<br>
                            <strong>Version 1.0 :</strong> Authentification multi-facteurs prévue
                        </p>
                    </div>

                    <h3>4.1 Modèle d'autorisation prévu</h3>
                    <ul>
                        <li><strong>Administrateur</strong> : Accès complet, gestion système</li>
                        <li><strong>Gestionnaire</strong> : Modules métier, pas d'admin système</li>
                        <li><strong>Utilisateur</strong> : Calculateurs et consultation</li>
                        <li><strong>Invité</strong> : Accès lecture seule limité</li>
                    </ul>

                    <h3>4.2 Sécurisation progressive</h3>
                    <ol>
                        <li>Phase 1 : Authentification simple (login/password)</li>
                        <li>Phase 2 : Double authentification (2FA)</li>
                        <li>Phase 3 : SSO et intégration Active Directory</li>
                        <li>Phase 4 : Authentification biométrique (optionnel)</li>
                    </ol>
                </section>

                <section class="legal-section">
                    <h2>5. Surveillance et audit</h2>
                    <h3>5.1 Monitoring en temps réel</h3>
                    <ul>
                        <li><strong>Logs d'accès</strong> : Toutes les connexions tracées</li>
                        <li><strong>Monitoring performance</strong> : Alertes automatiques</li>
                        <li><strong>Détection d'intrusion</strong> : IDS/IPS actifs</li>
                        <li><strong>Analyse comportementale</strong> : Détection d'anomalies</li>
                    </ul>

                    <h3>5.2 Audit trail</h3>
                    <div class="audit-table">
                        <table>
                            <tr>
                                <th>Événement</th>
                                <th>Niveau</th>
                                <th>Conservation</th>
                            </tr>
                            <tr>
                                <td>Connexions utilisateurs</td>
                                <td>INFO</td>
                                <td>6 mois</td>
                            </tr>
                            <tr>
                                <td>Modifications admin</td>
                                <td>WARNING</td>
                                <td>2 ans</td>
                            </tr>
                            <tr>
                                <td>Tentatives d'intrusion</td>
                                <td>CRITICAL</td>
                                <td>5 ans</td>
                            </tr>
                            <tr>
                                <td>Erreurs système</td>
                                <td>ERROR</td>
                                <td>1 an</td>
                            </tr>
                        </table>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>6. Sauvegarde et continuité</h2>
                    <h3>6.1 Stratégie de sauvegarde</h3>
                    <ul>
                        <li><strong>Quotidienne</strong> : Base de données complète chiffrée</li>
                        <li><strong>Hebdomadaire</strong> : Système complet + code source</li>
                        <li><strong>Mensuelle</strong> : Archive longue durée (5 ans)</li>
                        <li><strong>Temps réel</strong> : Réplication des données critiques</li>
                    </ul>

                    <h3>6.2 Plan de continuité (PCA/PRA)</h3>
                    <ul>
                        <li><strong>RTO (Recovery Time Objective)</strong> : 4h maximum</li>
                        <li><strong>RPO (Recovery Point Objective)</strong> : 1h maximum</li>
                        <li><strong>Tests réguliers</strong> : Simulation mensuelle</li>
                        <li><strong>Site de secours</strong> : Hébergement redondant</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>7. Conformité RGPD 2025</h2>
                    <div class="status-box status-info">
                        <h4>📋 Mise à jour réglementaire 2025</h4>
                        <p>
                            Conformité aux nouvelles exigences RGPD 2025 incluant :<br>
                            • Certification sous-traitants CNIL<br>
                            • Nouvelles obligations IA et automatisation<br>
                            • Renforcement des droits utilisateurs
                        </p>
                    </div>

                    <h3>7.1 Bases légales et finalités</h3>
                    <ul>
                        <li><strong>Intérêt légitime</strong> : Gestion interne des processus logistiques</li>
                        <li><strong>Exécution contractuelle</strong> : Relations avec transporteurs</li>
                        <li><strong>Obligation légale</strong> : Respect réglementation ADR</li>
                        <li><strong>Consentement</strong> : Fonctionnalités optionnelles</li>
                    </ul>

                    <h3>7.2 Mesures techniques et organisationnelles</h3>
                    <div class="security-measures">
                        <div class="measure-category">
                            <h4>🔐 Mesures techniques</h4>
                            <ul>
                                <li>Chiffrement AES-256 en base et en transit</li>
                                <li>Authentification multi-facteurs (prévue v1.0)</li>
                                <li>Contrôle d'accès basé sur les rôles (RBAC)</li>
                                <li>Pseudonymisation des données sensibles</li>
                                <li>Monitoring et détection d'anomalies</li>
                            </ul>
                        </div>
                        <div class="measure-category">
                            <h4>👥 Mesures organisationnelles</h4>
                            <ul>
                                <li>Politique de confidentialité documentée</li>
                                <li>Formation RGPD du personnel</li>
                                <li>Procédures de violation de données</li>
                                <li>Audits périodiques de conformité</li>
                                <li>Contrats DPA avec sous-traitants</li>
                            </ul>
                        </div>
                    </div>

                    <h3>7.3 Droits des personnes concernées</h3>
                    <div class="rights-table">
                        <table>
                            <tr>
                                <th>Droit</th>
                                <th>Délai de réponse</th>
                                <th>Modalité</th>
                            </tr>
                            <tr>
                                <td>Accès aux données</td>
                                <td>1 mois</td>
                                <td>dpo@guldagil.com</td>
                            </tr>
                            <tr>
                                <td>Rectification</td>
                                <td>1 mois</td>
                                <td>Formulaire en ligne</td>
                            </tr>
                            <tr>
                                <td>Effacement</td>
                                <td>1 mois</td>
                                <td>Demande motivée</td>
                            </tr>
                            <tr>
                                <td>Portabilité</td>
                                <td>1 mois</td>
                                <td>Export sécurisé</td>
                            </tr>
                            <tr>
                                <td>Opposition</td>
                                <td>Immédiat</td>
                                <td>Opt-out automatique</td>
                            </tr>
                        </table>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>8. Gestion des incidents</h2>
                    <h3>8.1 Procédure de violation de données</h3>
                    <div class="incident-timeline">
                        <div class="timeline-step">
                            <h4>📊 Phase 1 - Détection (0-1h)</h4>
                            <ul>
                                <li>Identification de l'incident</li>
                                <li>Mesures de confinement immédiates</li>
                                <li>Alerte équipe sécurité</li>
                            </ul>
                        </div>
                        <div class="timeline-step">
                            <h4>🔍 Phase 2 - Évaluation (1-24h)</h4>
                            <ul>
                                <li>Analyse de l'impact</li>
                                <li>Classification du risque</li>
                                <li>Documentation détaillée</li>
                            </ul>
                        </div>
                        <div class="timeline-step">
                            <h4>📢 Phase 3 - Notification (24-72h)</h4>
                            <ul>
                                <li>Notification CNIL si requis</li>
                                <li>Information personnes concernées</li>
                                <li>Communication interne</li>
                            </ul>
                        </div>
                        <div class="timeline-step">
                            <h4>🔧 Phase 4 - Remédiation (72h+)</h4>
                            <ul>
                                <li>Correction des vulnérabilités</li>
                                <li>Amélioration des processus</li>
                                <li>Retour d'expérience (REX)</li>
                            </ul>
                        </div>
                    </div>

                    <h3>8.2 Contacts d'urgence</h3>
                    <div class="emergency-contacts">
                        <div class="contact-item">
                            <h4>🚨 Urgence sécurité</h4>
                            <p>
                                📞 Hotline 24/7 : +33 XXX XXX XXX<br>
                                📧 security@guldagil.com<br>
                                👤 RSSI : Jean-Thomas RUNSER
                            </p>
                        </div>
                        <div class="contact-item">
                            <h4>⚖️ Incident RGPD</h4>
                            <p>
                                📧 dpo@guldagil.com<br>
                                📞 DPO : +33 XXX XXX XXX<br>
                                🕒 Réponse : 2h max
                            </p>
                        </div>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>9. Formation et sensibilisation</h2>
                    <h3>9.1 Programme de formation</h3>
                    <ul>
                        <li><strong>Sensibilisation générale</strong> : Tous les utilisateurs (annuel)</li>
                        <li><strong>Formation RGPD</strong> : Personnel administratif (semestriel)</li>
                        <li><strong>Cybersécurité avancée</strong> : Équipe technique (trimestriel)</li>
                        <li><strong>Tests d'intrusion</strong> : Exercices pratiques (mensuel)</li>
                    </ul>

                    <h3>9.2 Ressources disponibles</h3>
                    <ul>
                        <li>Guide de bonnes pratiques interne</li>
                        <li>Formations CNIL en ligne</li>
                        <li>Veille réglementaire automatisée</li>
                        <li>Simulateurs de phishing</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>10. Évolution et amélioration continue</h2>
                    <h3>10.1 Roadmap sécurité 2025-2026</h3>
                    <div class="security-roadmap">
                        <div class="roadmap-quarter">
                            <h4>Q2 2025</h4>
                            <ul>
                                <li>Authentification 2FA obligatoire</li>
                                <li>Chiffrement bout-en-bout</li>
                                <li>Audit de sécurité externe</li>
                            </ul>
                        </div>
                        <div class="roadmap-quarter">
                            <h4>Q3 2025</h4>
                            <ul>
                                <li>Certification ISO 27001</li>
                                <li>Pen testing trimestriel</li>
                                <li>SIEM avancé</li>
                            </ul>
                        </div>
                        <div class="roadmap-quarter">
                            <h4>Q4 2025</h4>
                            <ul>
                                <li>Zero Trust Architecture</li>
                                <li>Certification CNIL sous-traitant</li>
                                <li>IA pour détection menaces</li>
                            </ul>
                        </div>
                        <div class="roadmap-quarter">
                            <h4>2026</h4>
                            <ul>
                                <li>Conformité NIS2</li>
                                <li>Blockchain pour traçabilité</li>
                                <li>Sécurité quantique</li>
                            </ul>
                        </div>
                    </div>

                    <h3>10.2 Métriques et KPI sécurité</h3>
                    <ul>
                        <li><strong>Disponibilité</strong> : 99.9% (SLA)</li>
                        <li><strong>Temps de détection d'incident</strong> : < 15 minutes</li>
                        <li><strong>Temps de résolution</strong> : < 4 heures</li>
                        <li><strong>Taux de conformité RGPD</strong> : 100%</li>
                        <li><strong>Formation personnel</strong> : 100% à jour</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>11. Réglementations applicables</h2>
                    <div class="regulations-grid">
                        <div class="regulation-item">
                            <h4>🇪🇺 RGPD (UE) 2016/679</h4>
                            <p>Protection des données personnelles - Application intégrale</p>
                        </div>
                        <div class="regulation-item">
                            <h4>🇫🇷 Loi Informatique et Libertés</h4>
                            <p>Transposition française du RGPD - Dernière modification 2024</p>
                        </div>
                        <div class="regulation-item">
                            <h4>🔐 Directive NIS2</h4>
                            <p>Cybersécurité des infrastructures - Entrée en vigueur 2024</p>
                        </div>
                        <div class="regulation-item">
                            <h4>📊 Loi République Numérique</h4>
                            <p>Transparence et ouverture des données publiques</p>
                        </div>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>12. Contact et signalement</h2>
                    <div class="contact-security">
                        <h3>Pour toute question de sécurité :</h3>
                        <p>
                            🔐 <strong>Responsable Sécurité :</strong> Jean-Thomas RUNSER<br>
                            📧 <a href="mailto:security@guldagil.com">security@guldagil.com</a><br>
                            📧 <a href="mailto:dpo@guldagil.com">dpo@guldagil.com</a> (Questions RGPD)<br>
                            📞 Support sécurité : 24h/7j<br>
                            🌐 Portail de signalement : <a href="/security/report">security.guldagil.com/report</a>
                        </p>
                        
                        <h3>Signalement de vulnérabilité :</h3>
                        <p>
                            Nous encourageons le signalement responsable de vulnérabilités.<br>
                            Les chercheurs en sécurité peuvent nous contacter via notre<br>
                            programme de <strong>bug bounty</strong> interne.
                        </p>
                    </div>
                </section>
            </div>

            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
                    <a href="/terms.php" class="btn btn-secondary">📋 Conditions d'utilisation</a>
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

    <style>
        /* Styles spécifiques sécurité */
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        .security-item {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-blue);
        }
        .security-measures {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 1.5rem 0;
        }
        .measure-category {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
        }
        .rights-table table, .audit-table table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .rights-table th, .rights-table td,
        .audit-table th, .audit-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        .rights-table th, .audit-table th {
            background: var(--gray-100);
            font-weight: 600;
        }
        .incident-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .timeline-step {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            border-top: 4px solid var(--color-warning);
        }
        .emergency-contacts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 1.5rem 0;
        }
        .contact-item {
            background: var(--color-danger-light);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--color-danger);
        }
        .security-roadmap {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .roadmap-quarter {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            border-top: 4px solid var(--primary-blue);
        }
        .regulations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        .regulation-item {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--color-success);
        }
        .contact-security {
            background: var(--primary-blue-light);
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid var(--primary-blue);
        }
        .status-security {
            background: rgba(34, 197, 94, 0.1);
            border-left: 4px solid var(--color-success);
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</body>
</html>
