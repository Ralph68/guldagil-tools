<?php
// modals/help-modal.php - Modal d'aide et documentation
?>
<div id="help-modal" class="modal" style="display: none;">
    <div class="modal-content help-modal-content">
        <div class="modal-header">
            <h3>❓ Aide et documentation</h3>
            <button class="modal-close" onclick="closeHelpModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="help-navigation">
                <div class="help-tabs">
                    <button class="help-tab active" onclick="showHelpSection('quick-start')" data-section="quick-start">
                        🚀 Démarrage
                    </button>
                    <button class="help-tab" onclick="showHelpSection('features')" data-section="features">
                        ⚙️ Fonctionnalités
                    </button>
                    <button class="help-tab" onclick="showHelpSection('faq')" data-section="faq">
                        ❓ FAQ
                    </button>
                    <button class="help-tab" onclick="showHelpSection('support')" data-section="support">
                        📞 Support
                    </button>
                    <button class="help-tab" onclick="showHelpSection('shortcuts')" data-section="shortcuts">
                        ⌨️ Raccourcis
                    </button>
                </div>
            </div>

            <div class="help-content">
                <!-- Démarrage rapide -->
                <div id="help-quick-start" class="help-section active">
                    <div class="help-hero">
                        <div class="hero-icon">🎯</div>
                        <div class="hero-content">
                            <h2>Bienvenue dans l'administration Guldagil</h2>
                            <p>Guide rapide pour commencer à utiliser l'interface d'administration</p>
                        </div>
                    </div>

                    <div class="quick-start-steps">
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>📊 Tableau de bord</h4>
                                <p>Consultez les statistiques globales et l'activité récente du système. Les cartes colorées vous donnent un aperçu instantané de l'état de vos données.</p>
                                <div class="step-actions">
                                    <button class="btn btn-sm btn-primary" onclick="closeHelpModal(); showTab('dashboard');">
                                        Voir le tableau de bord
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>💰 Gestion des tarifs</h4>
                                <p>Ajoutez, modifiez ou supprimez les tarifs par transporteur et département. Utilisez les filtres pour trouver rapidement les tarifs à modifier.</p>
                                <div class="step-actions">
                                    <button class="btn btn-sm btn-primary" onclick="closeHelpModal(); showTab('rates');">
                                        Gérer les tarifs
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>📈 Analytics</h4>
                                <p>Analysez la couverture tarifaire, les performances par transporteur et obtenez des recommandations d'optimisation.</p>
                                <div class="step-actions">
                                    <button class="btn btn-sm btn-primary" onclick="closeHelpModal(); showTab('analytics');">
                                        Voir les analytics
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>📤 Import/Export</h4>
                                <p>Importez des données en masse via des fichiers CSV/Excel ou exportez vos configurations pour sauvegarde.</p>
                                <div class="step-actions">
                                    <button class="btn btn-sm btn-primary" onclick="closeHelpModal(); showTab('import');">
                                        Import/Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="quick-tips">
                        <h4>💡 Conseils rapides</h4>
                        <div class="tips-grid">
                            <div class="tip-item">
                                <span class="tip-icon">⚡</span>
                                <div class="tip-content">
                                    <strong>Navigation rapide</strong>
                                    <p>Utilisez Ctrl+1 à Ctrl+5 pour naviguer entre les onglets</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">💾</span>
                                <div class="tip-content">
                                    <strong>Sauvegarde automatique</strong>
                                    <p>Vos modifications sont sauvegardées automatiquement</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🔍</span>
                                <div class="tip-content">
                                    <strong>Recherche intelligente</strong>
                                    <p>Tapez dans les champs de recherche pour filtrer instantanément</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📱</span>
                                <div class="tip-content">
                                    <strong>Interface responsive</strong>
                                    <p>L'interface s'adapte à tous les écrans</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fonctionnalités -->
                <div id="help-features" class="help-section">
                    <h2>⚙️ Guide des fonctionnalités</h2>
                    
                    <div class="features-list">
                        <div class="feature-category">
                            <h3>💰 Gestion des tarifs</h3>
                            <div class="feature-items">
                                <div class="feature-item">
                                    <h4>✏️ Édition en ligne</h4>
                                    <p>Modifiez les tarifs directement dans le tableau avec validation en temps réel</p>
                                </div>
                                <div class="feature-item">
                                    <h4>🔍 Filtres avancés</h4>
                                    <p>Filtrez par transporteur, département ou utilisez la recherche textuelle</p>
                                </div>
                                <div class="feature-item">
                                    <h4>📊 Vue d'ensemble</h4>
                                    <p>Visualisez l'état de completion des tarifs par transporteur</p>
                                </div>
                                <div class="feature-item">
                                    <h4>🔄 Import en masse</h4>
                                    <p>Importez des centaines de tarifs via des fichiers CSV ou Excel</p>
                                </div>
                            </div>
                        </div>

                        <div class="feature-category">
                            <h3>📋 Configuration des taxes</h3>
                            <div class="feature-items">
                                <div class="feature-item">
                                    <h4>⚖️ Majorations complexes</h4>
                                    <p>Configurez les majorations ADR, IDF, saisonnières par transporteur</p>
                                </div>
                                <div class="feature-item">
                                    <h4>💸 Taxes fixes</h4>
                                    <p>Gérez les participations, contributions et taxes de sûreté</p>
                                </div>
                                <div class="feature-item">
                                    <h4>⛽ Surcharge carburant</h4>
                                    <p>Appliquez des pourcentages variables selon le transporteur</p>
                                </div>
                                <div class="feature-item">
                                    <h4>🧮 Simulateur d'impact</h4>
                                    <p>Testez l'impact des modifications avant application</p>
                                </div>
                            </div>
                        </div>

                        <div class="feature-category">
                            <h3>📈 Analytics et rapports</h3>
                            <div class="feature-items">
                                <div class="feature-item">
                                    <h4>🎯 Couverture tarifaire</h4>
                                    <p>Analysez le pourcentage de tarifs configurés par zone géographique</p>
                                </div>
                                <div class="feature-item">
                                    <h4>📊 Performance transporteurs</h4>
                                    <p>Comparez l'efficacité et la competitivité des transporteurs</p>
                                </div>
                                <div class="feature-item">
                                    <h4>🗺️ Carte de couverture</h4>
                                    <p>Visualisez les zones bien couvertes et celles à améliorer</p>
                                </div>
                                <div class="feature-item">
                                    <h4>📋 Rapports automatiques</h4>
                                    <p>Générez des rapports PDF avec graphiques et recommandations</p>
                                </div>
                            </div>
                        </div>

                        <div class="feature-category">
                            <h3>🔧 Outils avancés</h3>
                            <div class="feature-items">
                                <div class="feature-item">
                                    <h4>🔄 Synchronisation</h4>
                                    <p>Synchronisez avec les APIs des transporteurs (à venir)</p>
                                </div>
                                <div class="feature-item">
                                    <h4>🔔 Alertes intelligentes</h4>
                                    <p>Recevez des notifications pour les tarifs manquants ou obsolètes</p>
                                </div>
                                <div class="feature-item">
                                    <h4>📜 Historique complet</h4>
                                    <p>Tracez toutes les modifications avec possibilité de rollback</p>
                                </div>
                                <div class="feature-item">
                                    <h4>👥 Gestion des utilisateurs</h4>
                                    <p>Contrôlez les accès et permissions par rôle (admin, lecteur, etc.)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div id="help-faq" class="help-section">
                    <h2>❓ Questions fréquentes</h2>
                    
                    <div class="faq-list">
                        <div class="faq-category">
                            <h3>🔧 Configuration et utilisation</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Comment ajouter un nouveau tarif ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Pour ajouter un nouveau tarif :</p>
                                    <ol>
                                        <li>Allez dans l'onglet "Gestion des tarifs"</li>
                                        <li>Cliquez sur "Ajouter un tarif"</li>
                                        <li>Sélectionnez le transporteur et le département</li>
                                        <li>Remplissez les tranches de poids</li>
                                        <li>Cliquez sur "Enregistrer"</li>
                                    </ol>
                                    <p><strong>Astuce :</strong> Utilisez l'import CSV pour ajouter plusieurs tarifs d'un coup.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Comment modifier les taxes d'un transporteur ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Pour modifier les taxes :</p>
                                    <ol>
                                        <li>Allez dans l'onglet "Taxes & Majorations"</li>
                                        <li>Trouvez le transporteur concerné</li>
                                        <li>Cliquez sur "Modifier" dans sa carte</li>
                                        <li>Ajustez les valeurs dans le formulaire</li>
                                        <li>Utilisez le simulateur pour tester l'impact</li>
                                    </ol>
                                    <p><strong>Attention :</strong> Les modifications de taxes affectent tous les calculs.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Comment faire une sauvegarde des données ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Plusieurs options de sauvegarde :</p>
                                    <ul>
                                        <li><strong>Export rapide :</strong> Onglet Import/Export → "Sauvegarde complète"</li>
                                        <li><strong>Export sélectif :</strong> Exportez seulement les tarifs, options ou taxes</li>
                                        <li><strong>Automatique :</strong> Des sauvegardes automatiques sont créées quotidiennement</li>
                                    </ul>
                                    <p><strong>Recommandation :</strong> Exportez en JSON pour une sauvegarde complète.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Comment importer des tarifs depuis Excel ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Import depuis Excel :</p>
                                    <ol>
                                        <li>Téléchargez le modèle Excel depuis l'onglet Import/Export</li>
                                        <li>Remplissez le fichier avec vos tarifs</li>
                                        <li>Sélectionnez "Tarifs transporteurs" comme type d'import</li>
                                        <li>Glissez-déposez votre fichier ou cliquez pour le sélectionner</li>
                                        <li>Cliquez sur "Valider d'abord" pour vérifier</li>
                                        <li>Si tout est OK, cliquez sur "Importer le fichier"</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="faq-category">
                            <h3>🐛 Résolution de problèmes</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Les tarifs ne s'affichent pas correctement</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Solutions à essayer :</p>
                                    <ul>
                                        <li>Actualisez la page (F5 ou Ctrl+R)</li>
                                        <li>Vérifiez vos filtres de recherche</li>
                                        <li>Videz le cache de votre navigateur</li>
                                        <li>Vérifiez votre connexion internet</li>
                                    </ul>
                                    <p>Si le problème persiste, contactez le support technique.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Erreur lors de l'import de fichier</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Vérifications à faire :</p>
                                    <ul>
                                        <li><strong>Format :</strong> Le fichier doit être CSV, XLS ou XLSX</li>
                                        <li><strong>Taille :</strong> Maximum 10 MB</li>
                                        <li><strong>Colonnes :</strong> Respectez exactement les noms du modèle</li>
                                        <li><strong>Départements :</strong> Format 2 chiffres (01-95)</li>
                                        <li><strong>Transporteurs :</strong> heppner, xpo ou kn uniquement</li>
                                    </ul>
                                    <p>Utilisez la fonction "Valider d'abord" pour identifier les erreurs.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Je ne peux pas modifier certains tarifs</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Causes possibles :</p>
                                    <ul>
                                        <li><strong>Permissions :</strong> Vérifiez vos droits d'accès</li>
                                        <li><strong>Tarifs verrouillés :</strong> Certains tarifs peuvent être protégés</li>
                                        <li><strong>Session expirée :</strong> Reconnectez-vous</li>
                                    </ul>
                                    <p>Contactez l'administrateur si vous pensez avoir les bonnes permissions.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-category">
                            <h3>🔐 Sécurité et permissions</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Comment changer mon mot de passe ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Pour changer votre mot de passe :</p>
                                    <ol>
                                        <li>Cliquez sur votre nom d'utilisateur en haut à droite</li>
                                        <li>Sélectionnez "Paramètres du compte"</li>
                                        <li>Dans la section sécurité, cliquez sur "Changer le mot de passe"</li>
                                        <li>Entrez votre mot de passe actuel et le nouveau</li>
                                        <li>Confirmez avec "Mettre à jour"</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <span>Ma session expire souvent, que faire ?</span>
                                    <span class="faq-toggle">▼</span>
                                </div>
                                <div class="faq-answer">
                                    <p>Pour éviter les déconnexions :</p>
                                    <ul>
                                        <li>Les sessions durent 1 heure par défaut</li>
                                        <li>Votre session est prolongée à chaque action</li>
                                        <li>Cochez "Se souvenir de moi" à la connexion</li>
                                        <li>Évitez d'ouvrir plusieurs onglets admin simultanément</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div id="help-support" class="help-section">
                    <h2>📞 Support et contact</h2>
                    
                    <div class="support-grid">
                        <div class="support-card primary">
                            <div class="support-icon">📦</div>
                            <h3>Service logistique</h3>
                            <p>Questions sur les tarifs, transporteurs et configuration métier</p>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <span class="contact-label">Email :</span>
                                    <a href="mailto:achats@guldagil.com">achats@guldagil.com</a>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Téléphone :</span>
                                    <a href="tel:+33389634242">03 89 63 42 42</a>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Horaires :</span>
                                    <span>8h-17h, Lun-Ven</span>
                                </div>
                            </div>
                        </div>

                        <div class="support-card technical">
                            <div class="support-icon">🐛</div>
                            <h3>Support technique</h3>
                            <p>Bugs, améliorations interface et questions techniques</p>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <span class="contact-label">Email :</span>
                                    <a href="mailto:runser.jean.thomas@guldagil.com">runser.jean.thomas@guldagil.com</a>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Urgences :</span>
                                    <a href="tel:+33389634242">03 89 63 42 42</a>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Réponse :</span>
                                    <span>Sous 24h ouvrées</span>
                                </div>
                            </div>
                        </div>

                        <div class="support-card emergency">
                            <div class="support-icon">🚨</div>
                            <h3>Urgences</h3>
                            <p>Problèmes critiques affectant la production</p>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <span class="contact-label">Téléphone :</span>
                                    <a href="tel:+33389634242">03 89 63 42 42</a>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Disponible :</span>
                                    <span>24h/24, 7j/7</span>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Escalade :</span>
                                    <span>Direction générale</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="support-resources">
                        <h3>🔗 Ressources utiles</h3>
                        <div class="resources-grid">
                            <div class="resource-item">
                                <span class="resource-icon">📖</span>
                                <div class="resource-content">
                                    <h4>Documentation complète</h4>
                                    <p>Manuel utilisateur détaillé avec captures d'écran</p>
                                    <a href="/docs/manuel-utilisateur.pdf" target="_blank" class="btn btn-sm btn-secondary">
                                        📄 Télécharger PDF
                                    </a>
                                </div>
                            </div>

                            <div class="resource-item">
                                <span class="resource-icon">🎥</span>
                                <div class="resource-content">
                                    <h4>Tutoriels vidéo</h4>
                                    <p>Démonstrations pas à pas des fonctionnalités principales</p>
                                    <a href="#" onclick="showAlert('info', 'Tutoriels vidéo disponibles prochainement')" class="btn btn-sm btn-secondary">
                                        🎬 Voir les vidéos
                                    </a>
                                </div>
                            </div>

                            <div class="resource-item">
                                <span class="resource-icon">💬</span>
                                <div class="resource-content">
                                    <h4>Forum communautaire</h4>
                                    <p>Échangez avec d'autres utilisateurs et obtenez des astuces</p>
                                    <a href="#" onclick="showAlert('info', 'Forum communautaire en cours de création')" class="btn btn-sm btn-secondary">
                                        💭 Accéder au forum
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="feedback-section">
                        <h3>💡 Vos suggestions</h3>
                        <p>Votre avis nous intéresse ! Aidez-nous à améliorer l'interface :</p>
                        <div class="feedback-actions">
                            <button class="btn btn-primary" onclick="openFeedbackForm()">
                                📝 Proposer une amélioration
                            </button>
                            <button class="btn btn-warning" onclick="reportBug()">
                                🐛 Signaler un bug
                            </button>
                            <button class="btn btn-success" onclick="sendCompliment()">
                                👍 Envoyer un compliment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Raccourcis clavier -->
                <div id="help-shortcuts" class="help-section">
                    <h2>⌨️ Raccourcis clavier</h2>
                    
                    <div class="shortcuts-grid">
                        <div class="shortcut-category">
                            <h3>🧭 Navigation</h3>
                            <div class="shortcut-list">
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>1</kbd>
                                    </div>
                                    <div class="shortcut-description">Tableau de bord</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>2</kbd>
                                    </div>
                                    <div class="shortcut-description">Gestion des tarifs</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>3</kbd>
                                    </div>
                                    <div class="shortcut-description">Options supplémentaires</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>4</kbd>
                                    </div>
                                    <div class="shortcut-description">Taxes & majorations</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>5</kbd>
                                    </div>
                                    <div class="shortcut-description">Import/Export</div>
                                </div>
                            </div>
                        </div>

                        <div class="shortcut-category">
                            <h3>💾 Actions</h3>
                            <div class="shortcut-list">
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>S</kbd>
                                    </div>
                                    <div class="shortcut-description">Sauvegarder</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>E</kbd>
                                    </div>
                                    <div class="shortcut-description">Export rapide</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>F</kbd>
                                    </div>
                                    <div class="shortcut-description">Rechercher dans la page</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>R</kbd>
                                    </div>
                                    <div class="shortcut-description">Actualiser</div>
                                </div>
                            </div>
                        </div>

                        <div class="shortcut-category">
                            <h3>🖱️ Interface</h3>
                            <div class="shortcut-list">
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Échap</kbd>
                                    </div>
                                    <div class="shortcut-description">Fermer les modals</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>F1</kbd>
                                    </div>
                                    <div class="shortcut-description">Ouvrir l'aide</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Alt</kbd> + <kbd>←</kbd>
                                    </div>
                                    <div class="shortcut-description">Page précédente</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>F5</kbd>
                                    </div>
                                    <div class="shortcut-description">Actualiser la page</div>
                                </div>
                            </div>
                        </div>

                        <div class="shortcut-category">
                            <h3>📊 Tableaux</h3>
                            <div class="shortcut-list">
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>↑</kbd> <kbd>↓</kbd>
                                    </div>
                                    <div class="shortcut-description">Naviguer entre les lignes</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Entrée</kbd>
                                    </div>
                                    <div class="shortcut-description">Éditer la ligne sélectionnée</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Suppr</kbd>
                                    </div>
                                    <div class="shortcut-description">Supprimer la ligne sélectionnée</div>
                                </div>
                                <div class="shortcut-item">
                                    <div class="shortcut-keys">
                                        <kbd>Ctrl</kbd> + <kbd>A</kbd>
                                    </div>
                                    <div class="shortcut-description">Sélectionner tout</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="shortcuts-tip">
                        <div class="tip-icon">💡</div>
                        <div class="tip-content">
                            <h4>Astuce</h4>
                            <p>Vous pouvez personnaliser certains raccourcis dans les paramètres de votre compte. Les raccourcis fonctionnent même quand cette fenêtre d'aide est ouverte !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeHelpModal()">Fermer</button>
            <button class="btn btn-primary" onclick="printHelp()">
                <span>🖨️</span>
                Imprimer cette aide
            </button>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques à la modal d'aide */
.help-modal-content {
    max-width: 900px;
    width: 95%;
    max-height: 90vh;
}

.help-navigation {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.help-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 1rem;
}

.help-tab {
    padding: 0.75rem 1.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 500;
}

.help-tab:hover {
    background: #f3f4f6;
    border-color: var(--primary-color);
}

.help-tab.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.help-content {
    max-height: 60vh;
    overflow-y: auto;
    padding: 0 0.5rem;
}

.help-section {
    display: none;
}

.help-section.active {
    display: block;
}

.help-hero {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 8px;
}

.hero-icon {
    font-size: 3rem;
    color: var(--primary-color);
}

.hero-content h2 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
    font-size: 1.5rem;
}

.hero-content p {
    margin: 0;
    color: #6b7280;
    font-size: 1.1rem;
}

.quick-start-steps {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.step-card {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.step-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1.1rem;
}

.step-content p {
    margin: 0 0 1rem 0;
    color: #6b7280;
    line-height: 1.5;
}

.step-actions {
    margin-top: 1rem;
}

.quick-tips {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
}

.quick-tips h4 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.tip-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.tip-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.tip-content strong {
    display: block;
    color: #374151;
    margin-bottom: 0.25rem;
}

.tip-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
}

.features-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.feature-category h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.3rem;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.feature-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.feature-item {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border-left: 4px solid var(--primary-color);
}

.feature-item h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1rem;
}

.feature-item p {
    margin: 0;
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.faq-category h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.2rem;
}

.faq-item {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.faq-question {
    padding: 1rem;
    background: #f9fafb;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    transition: background 0.3s ease;
}

.faq-question:hover {
    background: #f3f4f6;
}

.faq-toggle {
    transition: transform 0.3s ease;
    color: var(--primary-color);
    font-weight: bold;
}

.faq-item.open .faq-toggle {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 1rem;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
}

.faq-item.open .faq-answer {
    max-height: 500px;
    padding: 1rem;
}

.faq-answer ol,
.faq-answer ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.faq-answer li {
    margin-bottom: 0.25rem;
}

.support-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.support-card {
    padding: 1.5rem;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    text-align: center;
}

.support-card.primary {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

.support-card.technical {
    border-color: var(--success-color);
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.support-card.emergency {
    border-color: var(--error-color);
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.support-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.support-card h3 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1.2rem;
}

.support-card p {
    margin: 0 0 1rem 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    text-align: left;
}

.contact-item {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    font-size: 0.9rem;
}

.contact-label {
    font-weight: 600;
    color: #374151;
    min-width: 80px;
}

.contact-item a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.contact-item a:hover {
    text-decoration: underline;
}

.support-resources {
    margin-bottom: 2rem;
}

.support-resources h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
}

.resources-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.resource-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.resource-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
    flex-shrink: 0;
}

.resource-content {
    flex: 1;
}

.resource-content h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1rem;
}

.resource-content p {
    margin: 0 0 0.75rem 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.feedback-section {
    background: #f0f9ff;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e0f2fe;
}

.feedback-section h3 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.feedback-section p {
    margin: 0 0 1rem 0;
    color: #6b7280;
}

.feedback-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.shortcut-category h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.1rem;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.shortcut-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
}

.shortcut-keys {
    display: flex;
    gap: 0.25rem;
    align-items: center;
}

.shortcut-keys kbd {
    background: #374151;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 24px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.shortcut-description {
    color: #6b7280;
    font-size: 0.9rem;
}

.shortcuts-tip {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #fff3cd;
    border-radius: 6px;
    border: 1px solid #ffc107;
}

.shortcuts-tip .tip-icon {
    font-size: 1.5rem;
    color: #856404;
    flex-shrink: 0;
}

.shortcuts-tip .tip-content h4 {
    margin: 0 0 0.5rem 0;
    color: #856404;
}

.shortcuts-tip .tip-content p {
    margin: 0;
    color: #856404;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .help-modal-content {
        width: 98%;
        max-height: 95vh;
    }
    
    .help-tabs {
        gap: 0.25rem;
    }
    
    .help-tab {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    .help-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .step-card {
        flex-direction: column;
        text-align: center;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .feature-items {
        grid-template-columns: 1fr;
    }
    
    .support-grid {
        grid-template-columns: 1fr;
    }
    
    .shortcuts-grid {
        grid-template-columns: 1fr;
    }
    
    .feedback-actions {
        flex-direction: column;
    }
    
    .resource-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Fonctions JavaScript pour la modal d'aide
function showHelpSection(sectionId) {
    // Masquer toutes les sections
    document.querySelectorAll('.help-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Désactiver tous les onglets
    document.querySelectorAll('.help-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Afficher la section sélectionnée
    const targetSection = document.getElementById('help-' + sectionId);
    const targetTab = document.querySelector(`[data-section="${sectionId}"]`);
    
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Scroll en haut du contenu
    document.querySelector('.help-content').scrollTop = 0;
}

function closeHelpModal() {
    const modal = document.getElementById('help-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function toggleFAQ(questionElement) {
    const faqItem = questionElement.parentElement;
    const isOpen = faqItem.classList.contains('open');
    
    // Fermer toutes les autres FAQ
    document.querySelectorAll('.faq-item.open').forEach(item => {
        if (item !== faqItem) {
            item.classList.remove('open');
        }
    });
    
    // Toggle l'item actuel
    if (isOpen) {
        faqItem.classList.remove('open');
    } else {
        faqItem.classList.add('open');
    }
}

function openFeedbackForm() {
    const subject = encodeURIComponent('Suggestion d\'amélioration - Interface Admin');
    const body = encodeURIComponent(`Bonjour,

J'aimerais proposer l'amélioration suivante pour l'interface d'administration :

[Décrivez votre suggestion ici]

Contexte :
- Navigateur : ${navigator.userAgent}
- URL actuelle : ${window.location.href}
- Date : ${new Date().toLocaleString()}

Merci !`);
    
    window.open(`mailto:runser.jean.thomas@guldagil.com?subject=${subject}&body=${body}`);
}

function reportBug() {
    const subject = encodeURIComponent('Rapport de bug - Interface Admin');
    const body = encodeURIComponent(`Bonjour,

J'ai rencontré le bug suivant :

Description du problème :
[Décrivez le problème ici]

Étapes pour reproduire :
1. [Étape 1]
2. [Étape 2]
3. [Étape 3]

Résultat attendu :
[Ce qui devrait se passer]

Résultat obtenu :
[Ce qui se passe réellement]

Informations techniques :
- Navigateur : ${navigator.userAgent}
- URL : ${window.location.href}
- Date/Heure : ${new Date().toLocaleString()}

Merci !`);
    
    window.open(`mailto:runser.jean.thomas@guldagil.com?subject=${subject}&body=${body}`);
}

function sendCompliment() {
    const subject = encodeURIComponent('Compliment - Interface Admin');
    const body = encodeURIComponent(`Bonjour,

Je tenais à vous féliciter pour le travail sur l'interface d'administration !

[Votre message ici]

Merci pour ce super outil !`);
    
    window.open(`mailto:runser.jean.thomas@guldagil.com?subject=${subject}&body=${body}`);
}

function printHelp() {
    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank');
    const helpContent = document.querySelector('.help-content').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Aide - Guldagil Administration</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .help-section { display: block !important; margin-bottom: 2rem; }
                .help-hero { background: #f0f9ff; padding: 1rem; border-radius: 6px; }
                .step-card { border: 1px solid #ddd; padding: 1rem; margin: 1rem 0; }
                .support-card { border: 1px solid #ddd; padding: 1rem; margin: 1rem 0; }
                kbd { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
                @media print {
                    .btn { display: none; }
                    .help-tabs { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>Guide d'utilisation - Administration Guldagil</h1>
            ${helpContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Ouvrir l'aide avec F1
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        const modal = document.getElementById('help-modal');
        if (modal) {
            modal.style.display = 'flex';
            showHelpSection('quick-start');
        }
    }
});

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('help-modal');
        if (modal && modal.style.display === 'flex') {
            closeHelpModal();
        }
    }
});

// Clic extérieur pour fermer
document.addEventListener('click', function(e) {
    if (e.target.id === 'help-modal') {
        closeHelpModal();
    }
});

// Fonction globale pour ouvrir l'aide
window.showHelp = function() {
    const modal = document.getElementById('help-modal');
    if (modal) {
        modal.style.display = 'flex';
        showHelpSection('quick-start');
    }
};

// Exposer les fonctions nécessaires
window.showHelpSection = showHelpSection;
window.closeHelpModal = closeHelpModal;
window.toggleFAQ = toggleFAQ;
window.openFeedbackForm = openFeedbackForm;
window.reportBug = reportBug;
window.sendCompliment = sendCompliment;
window.printHelp = printHelp;

console.log('✅ Modal d\'aide initialisée');
</script>
