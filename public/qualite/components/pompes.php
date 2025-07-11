<?php
/**
 * Titre: Composant Pompes Doseuses - Module Contrôle Qualité
 * Chemin: /features/qualite/components/pompes.php
 * Version: 0.5 beta + build auto
 */

// Récupérer les types de pompes doseuses
$pompeTypes = $qualiteManager->getEquipmentTypesByCategory('pompe_doseuse');
$recentPompes = $qualiteManager->getQualityControls(['equipment_type' => 'pompe_doseuse', 'limit' => 5]);

?>

<!-- Header section -->
<section class="page-header">
    <div class="page-title">
        <h2>⚙️ Contrôle Pompes Doseuses</h2>
        <p>Contrôle et validation des pompes doseuses TEKNA et GRUNDFOS</p>
    </div>
    
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showPompeTypeModal()">
            ➕ Nouveau contrôle pompe
        </button>
        <button class="btn btn-secondary" onclick="exportPompeData()">
            📊 Export données
        </button>
    </div>
</section>

<!-- Types de pompes disponibles -->
<section class="pompe-types-section">
    <h3>🔧 Types de pompes doseuses disponibles</h3>
    
    <div class="pompe-types-grid">
        <?php foreach ($pompeTypes as $type): ?>
        <div class="pompe-type-card">
            <div class="type-header">
                <div class="type-icon">
                    <?php if (strpos($type['type_code'], 'DOS4_8V') !== false): ?>
                        🟡
                    <?php elseif (strpos($type['type_code'], 'DOS6_DDE') !== false): ?>
                        🔵
                    <?php elseif (strpos($type['type_code'], 'DOS3_4') !== false): ?>
                        🟢
                    <?php else: ?>
                        ⚙️
                    <?php endif; ?>
                </div>
                <div class="type-info">
                    <h4><?= htmlspecialchars($type['type_name']) ?></h4>
                    <p><?= htmlspecialchars($type['description']) ?></p>
                    <span class="type-code"><?= htmlspecialchars($type['type_code']) ?></span>
                </div>
            </div>
            
            <?php 
            $models = $qualiteManager->getEquipmentModels($type['id']);
            ?>
            <div class="type-models">
                <h5>Modèles disponibles (<?= count($models) ?>)</h5>
                <div class="models-list">
                    <?php foreach ($models as $model): ?>
                    <div class="model-item">
                        <strong><?= htmlspecialchars($model['model_name']) ?></strong>
                        <small><?= htmlspecialchars($model['manufacturer']) ?></small>
                        <?php 
                        $specs = json_decode($model['technical_specs'], true);
                        if ($specs && isset($specs['debit_max_lh'])):
                        ?>
                        <div class="model-specs">
                            <span class="spec">⚡ <?= $specs['debit_max_lh'] ?> L/h</span>
                            <?php if (isset($specs['pression_max_bar'])): ?>
                            <span class="spec">🔧 <?= $specs['pression_max_bar'] ?> bar</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="type-actions">
                <button class="btn btn-primary" onclick="startPompeForm('<?= $type['type_code'] ?>')">
                    🚀 Démarrer contrôle
                </button>
                <button class="btn btn-secondary" onclick="viewPompeSpecs('<?= $type['id'] ?>')">
                    📋 Voir spécifications
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Formulaires selon le type -->
<section class="pompe-forms-section">
    <h3>📝 Formulaires de contrôle</h3>
    
    <div class="forms-tabs">
        <button class="tab-btn active" onclick="showPompeTab('dos4_8v')">DOS4-8V / DOS4-8V2</button>
        <button class="tab-btn" onclick="showPompeTab('dos6_dde')">DOS6 DDE</button>
        <button class="tab-btn" onclick="showPompeTab('dos3_4')">DOS3.4</button>
        <button class="tab-btn" onclick="showPompeTab('universal')">Formulaire universel</button>
    </div>
    
    <!-- Onglet DOS4-8V -->
    <div id="dos4_8v-tab" class="tab-content active">
        <div class="form-preview">
            <h4>🟡 Formulaire TEKNA DOS4-8V / DOS4-8V2</h4>
            <p>Formulaire spécialisé pour les pompes doseuses TEKNA DOS4-8V et DOS4-8V2</p>
            
            <div class="form-sections-preview">
                <div class="section-preview">
                    <div class="section-icon">📝</div>
                    <div class="section-info">
                        <h5>Identification</h5>
                        <ul>
                            <li>Agence, N° Dossier, N° ARC</li>
                            <li>Installation, Modèle exact</li>
                            <li>N° de série pompe</li>
                            <li>Référence Guldagil</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">🔧</div>
                    <div class="section-info">
                        <h5>Caractéristiques techniques</h5>
                        <ul>
                            <li>Débit maxi: 8 L/h à 2 bar</li>
                            <li>Cylindrée maxi: 0,83 ml/m³</li>
                            <li>Type connecteur (DOS4-8V vs DOS4-8V2)</li>
                            <li>Alimentation et câblage</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">📦</div>
                    <div class="section-info">
                        <h5>Accessoires et composants</h5>
                        <ul>
                            <li>Socle plastique, raccords pompe</li>
                            <li>Canne injection / Crépine aspiration PVDF</li>
                            <li>Contact de niveau, tuyaux</li>
                            <li>Documentation technique</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">💧</div>
                    <div class="section-info">
                        <h5>Contrôles et tests</h5>
                        <ul>
                            <li>Test débit et pression</li>
                            <li>Vérification connecteurs</li>
                            <li>Contrôle étanchéité</li>
                            <li>Test contact de niveau</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/pompe_dos4_8v.php'">
                    🚀 Démarrer formulaire DOS4-8V
                </button>
                <button class="btn btn-secondary" onclick="downloadDos48vTemplate()">
                    📄 Télécharger template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Onglet DOS6 DDE -->
    <div id="dos6_dde-tab" class="tab-content">
        <div class="form-preview">
            <h4>🔵 Formulaire GRUNDFOS DOS6 DDE</h4>
            <p>Formulaire spécialisé pour les pompes doseuses GRUNDFOS DOS6 DDE avec compteur intégré</p>
            
            <div class="form-sections-preview">
                <div class="section-preview">
                    <div class="section-icon">📝</div>
                    <div class="section-info">
                        <h5>Identification</h5>
                        <ul>
                            <li>Agence, N° Dossier, N° ARC</li>
                            <li>Installation, Modèle DOS6 DDE</li>
                            <li>N° de série pompe et compteur</li>
                            <li>Référence Guldagil</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">🔧</div>
                    <div class="section-info">
                        <h5>Caractéristiques techniques</h5>
                        <ul>
                            <li>Débit maxi: 6 L/h à 10 bar</li>
                            <li>Connecteur DDE 6-10</li>
                            <li>Compteur d'impulsion intégré</li>
                            <li>Communication bus de terrain</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">📊</div>
                    <div class="section-info">
                        <h5>Compteur d'impulsion</h5>
                        <ul>
                            <li>Type: DHM 1000 GMWFI</li>
                            <li>Diamètre et étendue</li>
                            <li>Facteur K compteur</li>
                            <li>Documentation compteur</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">⚡</div>
                    <div class="section-info">
                        <h5>Configuration et tests</h5>
                        <ul>
                            <li>Configuration DDE</li>
                            <li>Test compteur d'impulsion</li>
                            <li>Vérification communication</li>
                            <li>Calibrage débit</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/pompe_dos6_dde.php'">
                    🚀 Démarrer formulaire DOS6 DDE
                </button>
                <button class="btn btn-secondary" onclick="downloadDos6DdeTemplate()">
                    📄 Télécharger template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Onglet DOS3.4 -->
    <div id="dos3_4-tab" class="tab-content">
        <div class="form-preview">
            <h4>🟢 Formulaire TEKNA DOS3.4</h4>
            <p>Formulaire spécialisé pour les pompes doseuses TEKNA DOS3.4 compactes</p>
            
            <div class="form-sections-preview">
                <div class="section-preview">
                    <div class="section-icon">📝</div>
                    <div class="section-info">
                        <h5>Identification</h5>
                        <ul>
                            <li>Agence, N° Dossier, N° ARC</li>
                            <li>Installation, Modèle DOS3.4</li>
                            <li>N° de série pompe</li>
                            <li>Référence Guldagil</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">🔧</div>
                    <div class="section-info">
                        <h5>Caractéristiques techniques</h5>
                        <ul>
                            <li>Débit maxi: 3.4 L/h à 7 bar</li>
                            <li>Cylindrée maxi: 0,35 ml/m³</li>
                            <li>Connecteur standard 3 vis</li>
                            <li>Version compacte</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">📦</div>
                    <div class="section-info">
                        <h5>Configuration spécifique</h5>
                        <ul>
                            <li>Mode dosage manuel/auto</li>
                            <li>Réglage débit 40% par défaut</li>
                            <li>Pression travail 2 bar</li>
                            <li>Tests spécifiques compacte</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">✅</div>
                    <div class="section-info">
                        <h5>Contrôles qualité</h5>
                        <ul>
                            <li>Test précision dosage</li>
                            <li>Vérification étanchéité</li>
                            <li>Contrôle vibrations</li>
                            <li>Test longue durée</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/pompe_dos3_4.php'">
                    🚀 Démarrer formulaire DOS3.4
                </button>
                <button class="btn btn-secondary" onclick="downloadDos34Template()">
                    📄 Télécharger template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Onglet Universel -->
    <div id="universal-tab" class="tab-content">
        <div class="form-preview">
            <h4>🔧 Formulaire Universel Pompe Doseuse</h4>
            <p>Formulaire générique adaptatif selon le type de pompe doseuse sélectionnée</p>
            
            <div class="universal-options">
                <div class="option-card">
                    <div class="option-icon">🎯</div>
                    <div class="option-content">
                        <h5>Formulaire adaptatif</h5>
                        <p>Le formulaire s'adapte automatiquement selon le type de pompe choisi</p>
                        <ul>
                            <li>Sections communes à tous les types</li>
                            <li>Sections spécifiques selon le modèle</li>
                            <li>Validations contextuelles</li>
                            <li>Tests adaptés à chaque pompe</li>
                        </ul>
                    </div>
                </div>
                
                <div class="option-card">
                    <div class="option-icon">⚡</div>
                    <div class="option-content">
                        <h5>Mode assistant</h5>
                        <p>Interface guidée avec procédures de test intégrées</p>
                        <ul>
                            <li>Guide étape par étape</li>
                            <li>Procédures de test automatisées</li>
                            <li>Validation en temps réel</li>
                            <li>Aide contextuelle</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/pompe_universal.php'">
                    🚀 Démarrer formulaire universel
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Contrôles récents de pompes -->
<section class="recent-pompes-section">
    <div class="section-header">
        <h3>🕒 Contrôles pompes récents</h3>
        <a href="?action=controles&filter=pompe_doseuse" class="btn btn-secondary btn-small">Voir tous</a>
    </div>
    
    <?php if (!empty($recentPompes)): ?>
    <div class="pompes-grid">
        <?php foreach ($recentPompes as $control): ?>
        <div class="pompe-control-card">
            <div class="control-header">
                <div class="control-type">
                    <?php if (strpos($control['equipment_type'], 'DOS4_8V') !== false): ?>
                        <span class="type-badge type-dos4-8v">🟡 DOS4-8V</span>
                    <?php elseif (strpos($control['equipment_type'], 'DOS6_DDE') !== false): ?>
                        <span class="type-badge type-dos6-dde">🔵 DOS6 DDE</span>
                    <?php elseif (strpos($control['equipment_type'], 'DOS3_4') !== false): ?>
                        <span class="type-badge type-dos3-4">🟢 DOS3.4</span>
                    <?php else: ?>
                        <span class="type-badge type-other">⚙️ Pompe</span>
                    <?php endif; ?>
                </div>
                <div class="control-status">
                    <span class="status-badge status-<?= $control['status'] ?>">
                        <?php
                        $statusLabels = [
                            'draft' => 'Brouillon',
                            'in_progress' => 'En cours',
                            'completed' => 'Terminé',
                            'validated' => 'Validé',
                            'sent' => 'Envoyé'
                        ];
                        echo $statusLabels[$control['status']] ?? $control['status'];
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="control-info">
                <h4><?= htmlspecialchars($control['control_number']) ?></h4>
                <p class="installation"><?= htmlspecialchars($control['installation_name']) ?></p>
                <div class="control-meta">
                    <span class="agency">📍 <?= htmlspecialchars($control['agency_code']) ?></span>
                    <span class="date">📅 <?= date('d/m/Y', strtotime($control['created_at'])) ?></span>
                </div>
            </div>
            
            <div class="control-actions">
                <button class="btn btn-small btn-secondary" onclick="viewPompeControl(<?= $control['id'] ?>)">
                    👁️ Voir
                </button>
                <?php if (in_array($control['status'], ['draft', 'in_progress'])): ?>
                <button class="btn btn-small btn-primary" onclick="editPompeControl(<?= $control['id'] ?>)">
                    ✏️ Modifier
                </button>
                <?php endif; ?>
                <?php if ($control['status'] === 'validated'): ?>
                <button class="btn btn-small btn-success" onclick="sendPompeControl(<?= $control['id'] ?>)">
                    📧 Envoyer
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">⚙️</div>
        <h4>Aucun contrôle de pompe récent</h4>
        <p>Commencez par créer votre premier contrôle de pompe doseuse</p>
        <button class="btn btn-primary" onclick="showPompeTypeModal()">
            ➕ Nouveau contrôle
        </button>
    </div>
    <?php endif; ?>
</section>

<!-- Outils et calculateurs -->
<section class="tools-section">
    <h3>🧮 Outils et calculateurs</h3>
    
    <div class="tools-grid">
        <div class="tool-card">
            <div class="tool-icon">🧮</div>
            <div class="tool-content">
                <h4>Calculateur de débit</h4>
                <p>Calcul du débit optimal selon les paramètres d'installation</p>
                <div class="tool-features">
                    <span class="feature">• Calcul selon pression</span>
                    <span class="feature">• Dosage proportionnel</span>
                    <span class="feature">• Validation paramètres</span>
                </div>
                <button class="btn btn-primary" onclick="openFlowCalculator()">
                    🧮 Ouvrir calculateur
                </button>
            </div>
        </div>
        
        <div class="tool-card">
            <div class="tool-icon">📐</div>
            <div class="tool-content">
                <h4>Dimensionnement installation</h4>
                <p>Aide au choix de la pompe selon les besoins</p>
                <div class="tool-features">
                    <span class="feature">• Analyse des besoins</span>
                    <span class="feature">• Recommandations modèle</span>
                    <span class="feature">• Calcul accessoires</span>
                </div>
                <button class="btn btn-primary" onclick="openSizingTool()">
                    📐 Ouvrir outil
                </button>
            </div>
        </div>
        
        <div class="tool-card">
            <div class="tool-icon">🔧</div>
            <div class="tool-content">
                <h4>Assistant de réglage</h4>
                <p>Guide interactif pour le réglage des pompes</p>
                <div class="tool-features">
                    <span class="feature">• Procédures étape par étape</span>
                    <span class="feature">• Paramètres optimaux</span>
                    <span class="feature">• Contrôles de validation</span>
                </div>
                <button class="btn btn-primary" onclick="openSettingsAssistant()">
                    🔧 Ouvrir assistant
                </button>
            </div>
        </div>
        
        <div class="tool-card">
            <div class="tool-icon">🔍</div>
            <div class="tool-content">
                <h4>Diagnostic et dépannage</h4>
                <p>Outil d'aide au diagnostic des pannes</p>
                <div class="tool-features">
                    <span class="feature">• Diagnostic automatisé</span>
                    <span class="feature">• Solutions recommandées</span>
                    <span class="feature">• Base de connaissances</span>
                </div>
                <button class="btn btn-primary" onclick="openDiagnosticTool()">
                    🔍 Ouvrir diagnostic
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Aide et documentation -->
<section class="help-section">
    <h3>📚 Aide et documentation</h3>
    
    <div class="help-grid">
        <div class="help-card">
            <div class="help-icon">📖</div>
            <div class="help-content">
                <h4>Guide DOS4-8V / DOS4-8V2</h4>
                <p>Procédures complètes pour TEKNA DOS4-8V et DOS4-8V2</p>
                <button class="btn btn-secondary" onclick="openHelp('dos4_8v')">
                    📖 Consulter
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">📘</div>
            <div class="help-content">
                <h4>Guide DOS6 DDE</h4>
                <p>Procédures pour GRUNDFOS DOS6 DDE avec compteur</p>
                <button class="btn btn-secondary" onclick="openHelp('dos6_dde')">
                    📘 Consulter
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">📗</div>
            <div class="help-content">
                <h4>Guide DOS3.4</h4>
                <p>Procédures pour TEKNA DOS3.4 compacte</p>
                <button class="btn btn-secondary" onclick="openHelp('dos3_4')">
                    📗 Consulter
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">📋</div>
            <div class="help-content">
                <h4>Checklist contrôle pompes</h4>
                <p>Liste de vérification complète tous modèles</p>
                <button class="btn btn-secondary" onclick="downloadPompeChecklist()">
                    📋 Télécharger
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Modal sélection type de pompe -->
<div id="pompeTypeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚙️ Nouveau contrôle pompe doseuse</h3>
            <button class="modal-close" onclick="closeModal('pompeTypeModal')">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Sélectionnez le type de pompe doseuse à contrôler :</p>
            
            <div class="pompe-type-selection">
                <?php foreach ($pompeTypes as $type): ?>
                <div class="type-option" onclick="startPompeForm('<?= $type['type_code'] ?>')">
                    <div class="type-option-icon">
                        <?php if (strpos($type['type_code'], 'DOS4_8V') !== false): ?>
                            🟡
                        <?php elseif (strpos($type['type_code'], 'DOS6_DDE') !== false): ?>
                            🔵
                        <?php elseif (strpos($type['type_code'], 'DOS3_4') !== false): ?>
                            🟢
                        <?php else: ?>
                            ⚙️
                        <?php endif; ?>
                    </div>
                    <div class="type-option-content">
                        <h4><?= htmlspecialchars($type['type_name']) ?></h4>
                        <p><?= htmlspecialchars($type['description']) ?></p>
                        <span class="type-option-code"><?= htmlspecialchars($type['type_code']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques aux pompes doseuses */
.pompe-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.pompe-type-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #f59e0b;
}

.model-specs {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.spec {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
}

.type-badge.type-dos4-8v {
    background: #fef3c7;
    color: #d97706;
}

.type-badge.type-dos6-dde {
    background: #dbeafe;
    color: #1d4ed8;
}

.type-badge.type-dos3-4 {
    background: #dcfce7;
    color: #16a34a;
}

.type-badge.type-other {
    background: #f3f4f6;
    color: #6b7280;
}

.pompes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.pompe-control-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.tool-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-top: 4px solid #f59e0b;
}

.tool-card .tool-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #f59e0b;
}

.tool-features {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin: 1rem 0;
}

.feature {
    font-size: 0.9rem;
    color: #6b7280;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.help-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.help-card .help-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.pompe-type-selection {
    display: grid;
    gap: 1rem;
}
</style>

<script>
function showPompeTab(tabName) {
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Afficher l'onglet sélectionné
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function showPompeTypeModal() {
    document.getElementById('pompeTypeModal').style.display = 'flex';
}

function startPompeForm(typeCode) {
    // Rediriger vers le formulaire approprié selon le type
    if (typeCode.includes('DOS4_8V')) {
        window.location.href = `forms/pompe_dos4_8v.php?type=${typeCode}`;
    } else if (typeCode.includes('DOS6_DDE')) {
        window.location.href = `forms/pompe_dos6_dde.php?type=${typeCode}`;
    } else if (typeCode.includes('DOS3_4')) {
        window.location.href = `forms/pompe_dos3_4.php?type=${typeCode}`;
    } else {
        window.location.href = `forms/pompe_universal.php?type=${typeCode}`;
    }
}

function viewPompeSpecs(typeId) {
    window.location.href = `specs/pompe.php?type=${typeId}`;
}

function viewPompeControl(controlId) {
    window.location.href = `?action=controles&view=${controlId}`;
}

function editPompeControl(controlId) {
    window.location.href = `forms/edit.php?id=${controlId}`;
}

function sendPompeControl(controlId) {
    if (confirm('Envoyer ce contrôle validé à l\'agence ?')) {
        window.location.href = `actions/send.php?id=${controlId}`;
    }
}

function exportPompeData() {
    window.location.href = `export/pompes.php`;
}

function downloadDos48vTemplate() {
    window.location.href = `templates/dos4_8v_template.pdf`;
}

function downloadDos6DdeTemplate() {
    window.location.href = `templates/dos6_dde_template.pdf`;
}

function downloadDos34Template() {
    window.location.href = `templates/dos3_4_template.pdf`;
}

function openFlowCalculator() {
    window.open(`tools/flow_calculator.php`, '_blank');
}

function openSizingTool() {
    window.open(`tools/sizing_tool.php`, '_blank');
}

function openSettingsAssistant() {
    window.open(`tools/settings_assistant.php`, '_blank');
}

function openDiagnosticTool() {
    window.open(`tools/diagnostic_tool.php`, '_blank');
}

function openHelp(type) {
    window.open(`help/${type}_guide.php`, '_blank');
}

function downloadPompeChecklist() {
    window.location.href = `templates/pompe_checklist.pdf`;
}
</script>
