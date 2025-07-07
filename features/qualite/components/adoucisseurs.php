<?php
/**
 * Titre: Composant Adoucisseurs - Module Contrôle Qualité
 * Chemin: /features/qualite/components/adoucisseurs.php
 * Version: 0.5 beta + build auto
 */

// Récupérer les types d'adoucisseurs
$adoucisseurTypes = $qualiteManager->getEquipmentTypesByCategory('adoucisseur');
$recentAdoucisseurs = $qualiteManager->getQualityControls(['equipment_type' => 'adoucisseur', 'limit' => 5]);

?>

<!-- Header section -->
<section class="page-header">
    <div class="page-title">
        <h2>💧 Contrôle Adoucisseurs</h2>
        <p>Contrôle et validation des adoucisseurs d'eau Clack et Fleck</p>
    </div>
    
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAdoucisseurTypeModal()">
            ➕ Nouveau contrôle adoucisseur
        </button>
        <button class="btn btn-secondary" onclick="exportAdoucisseurData()">
            📊 Export données
        </button>
    </div>
</section>

<!-- Types d'adoucisseurs disponibles -->
<section class="adoucisseur-types-section">
    <h3>🔧 Types d'adoucisseurs disponibles</h3>
    
    <div class="adoucisseur-types-grid">
        <?php foreach ($adoucisseurTypes as $type): ?>
        <div class="adoucisseur-type-card">
            <div class="type-header">
                <div class="type-icon">
                    <?php if (strpos($type['type_code'], 'CLACK') !== false): ?>
                        🔵
                    <?php else: ?>
                        🟢
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
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="type-actions">
                <button class="btn btn-primary" onclick="startAdoucisseurForm('<?= $type['type_code'] ?>')">
                    🚀 Démarrer contrôle
                </button>
                <button class="btn btn-secondary" onclick="viewAdoucisseurSpecs('<?= $type['id'] ?>')">
                    📋 Voir spécifications
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Formulaires selon le type -->
<section class="adoucisseur-forms-section">
    <h3>📝 Formulaires de contrôle</h3>
    
    <div class="forms-tabs">
        <button class="tab-btn active" onclick="showTab('clack')">Adoucisseurs Clack</button>
        <button class="tab-btn" onclick="showTab('fleck')">Adoucisseurs Fleck</button>
        <button class="tab-btn" onclick="showTab('universal')">Formulaire universel</button>
    </div>
    
    <!-- Onglet Clack -->
    <div id="clack-tab" class="tab-content active">
        <div class="form-preview">
            <h4>🔵 Formulaire Adoucisseur Clack (CI/CIM/CIP)</h4>
            <p>Formulaire spécialisé pour les adoucisseurs avec vannes Clack</p>
            
            <div class="form-sections-preview">
                <div class="section-preview">
                    <div class="section-icon">⚙️</div>
                    <div class="section-info">
                        <h5>Programmation Fleck SXT</h5>
                        <ul>
                            <li>Type de régénération (VR/Chrono)</li>
                            <li>Temps de détassage, aspiration, rinçage</li>
                            <li>Programmation SXT spécifique</li>
                            <li>Roue de programmation</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">💧</div>
                    <div class="section-info">
                        <h5>Analyse eau et réglages</h5>
                        <ul>
                            <li>TH eau brute et cible</li>
                            <li>Consommations et volumes</li>
                            <li>Estimation consommation sel</li>
                            <li>Planning régénérations</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/adoucisseur_fleck.php'">
                    🚀 Démarrer formulaire Fleck SXT
                </button>
                <button class="btn btn-secondary" onclick="downloadFleckTemplate()">
                    📄 Télécharger template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Onglet Universel -->
    <div id="universal-tab" class="tab-content">
        <div class="form-preview">
            <h4>🔧 Formulaire Universel Adoucisseur</h4>
            <p>Formulaire générique adaptatif selon le type d'adoucisseur sélectionné</p>
            
            <div class="universal-options">
                <div class="option-card">
                    <div class="option-icon">🎯</div>
                    <div class="option-content">
                        <h5>Formulaire adaptatif</h5>
                        <p>Le formulaire s'adapte automatiquement selon le type d'adoucisseur choisi</p>
                        <ul>
                            <li>Sections communes à tous les types</li>
                            <li>Sections spécifiques selon le modèle</li>
                            <li>Validations contextuelles</li>
                            <li>Calculs automatiques adaptés</li>
                        </ul>
                    </div>
                </div>
                
                <div class="option-card">
                    <div class="option-icon">⚡</div>
                    <div class="option-content">
                        <h5>Mode assistant</h5>
                        <p>Interface guidée pas-à-pas avec aide contextuelle</p>
                        <ul>
                            <li>Aide en temps réel</li>
                            <li>Suggestions automatiques</li>
                            <li>Détection d'erreurs</li>
                            <li>Sauvegarde automatique</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/adoucisseur_universal.php'">
                    🚀 Démarrer formulaire universel
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Contrôles récents d'adoucisseurs -->
<section class="recent-adoucisseurs-section">
    <div class="section-header">
        <h3>🕒 Contrôles adoucisseurs récents</h3>
        <a href="?action=controles&filter=adoucisseur" class="btn btn-secondary btn-small">Voir tous</a>
    </div>
    
    <?php if (!empty($recentAdoucisseurs)): ?>
    <div class="adoucisseurs-grid">
        <?php foreach ($recentAdoucisseurs as $control): ?>
        <div class="adoucisseur-control-card">
            <div class="control-header">
                <div class="control-type">
                    <?php if (strpos($control['equipment_type'], 'CLACK') !== false): ?>
                        <span class="type-badge type-clack">🔵 Clack</span>
                    <?php else: ?>
                        <span class="type-badge type-fleck">🟢 Fleck</span>
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
                <button class="btn btn-small btn-secondary" onclick="viewAdoucisseurControl(<?= $control['id'] ?>)">
                    👁️ Voir
                </button>
                <?php if (in_array($control['status'], ['draft', 'in_progress'])): ?>
                <button class="btn btn-small btn-primary" onclick="editAdoucisseurControl(<?= $control['id'] ?>)">
                    ✏️ Modifier
                </button>
                <?php endif; ?>
                <?php if ($control['status'] === 'validated'): ?>
                <button class="btn btn-small btn-success" onclick="sendAdoucisseurControl(<?= $control['id'] ?>)">
                    📧 Envoyer
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">💧</div>
        <h4>Aucun contrôle d'adoucisseur récent</h4>
        <p>Commencez par créer votre premier contrôle d'adoucisseur</p>
        <button class="btn btn-primary" onclick="showAdoucisseurTypeModal()">
            ➕ Nouveau contrôle
        </button>
    </div>
    <?php endif; ?>
</section>

<!-- Aide et documentation -->
<section class="help-section">
    <h3>📚 Aide et documentation</h3>
    
    <div class="help-grid">
        <div class="help-card">
            <div class="help-icon">📖</div>
            <div class="help-content">
                <h4>Guide de contrôle Clack</h4>
                <p>Procédures détaillées pour le contrôle des adoucisseurs Clack CI, CIM et CIP</p>
                <button class="btn btn-secondary" onclick="openHelp('clack')">
                    📖 Consulter
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">📗</div>
            <div class="help-content">
                <h4>Guide de contrôle Fleck</h4>
                <p>Procédures détaillées pour le contrôle des adoucisseurs Fleck SXT</p>
                <button class="btn btn-secondary" onclick="openHelp('fleck')">
                    📗 Consulter
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">🧮</div>
            <div class="help-content">
                <h4>Calculateur adoucisseur</h4>
                <p>Outils de calcul pour dimensionnement et paramétrage</p>
                <button class="btn btn-secondary" onclick="openCalculator()">
                    🧮 Ouvrir
                </button>
            </div>
        </div>
        
        <div class="help-card">
            <div class="help-icon">📋</div>
            <div class="help-content">
                <h4>Checklist de contrôle</h4>
                <p>Liste de vérification complète pour ne rien oublier</p>
                <button class="btn btn-secondary" onclick="downloadChecklist()">
                    📋 Télécharger
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Modal sélection type d'adoucisseur -->
<div id="adoucisseurTypeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>💧 Nouveau contrôle adoucisseur</h3>
            <button class="modal-close" onclick="closeModal('adoucisseurTypeModal')">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Sélectionnez le type d'adoucisseur à contrôler :</p>
            
            <div class="adoucisseur-type-selection">
                <?php foreach ($adoucisseurTypes as $type): ?>
                <div class="type-option" onclick="startAdoucisseurForm('<?= $type['type_code'] ?>')">
                    <div class="type-option-icon">
                        <?php if (strpos($type['type_code'], 'CLACK') !== false): ?>
                            🔵
                        <?php else: ?>
                            🟢
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
/* Styles spécifiques aux adoucisseurs */
.adoucisseur-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.adoucisseur-type-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #3b82f6;
}

.type-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.type-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
}

.type-code {
    background: #e5e7eb;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-family: monospace;
}

.forms-tabs {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 2rem;
}

.tab-btn {
    padding: 1rem 2rem;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
}

.tab-btn.active {
    border-bottom-color: #3b82f6;
    color: #3b82f6;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.form-sections-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.section-preview {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid #3b82f6;
}

.section-preview .section-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.universal-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin: 1.5rem 0;
}

.option-card {
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
}

.option-card .option-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.adoucisseurs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.adoucisseur-control-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.control-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.type-badge.type-clack {
    background: #dbeafe;
    color: #1d4ed8;
}

.type-badge.type-fleck {
    background: #dcfce7;
    color: #16a34a;
}

.control-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.5rem;
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

.adoucisseur-type-selection {
    display: grid;
    gap: 1rem;
}

.type-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.type-option:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.type-option-icon {
    font-size: 2rem;
}

.type-option-code {
    background: #e5e7eb;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-family: monospace;
}
</style>

<script>
function showTab(tabName) {
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

function showAdoucisseurTypeModal() {
    document.getElementById('adoucisseurTypeModal').style.display = 'flex';
}

function startAdoucisseurForm(typeCode) {
    // Rediriger vers le formulaire approprié selon le type
    if (typeCode.includes('CLACK')) {
        window.location.href = `forms/adoucisseur_clack.php?type=${typeCode}`;
    } else if (typeCode.includes('FLECK')) {
        window.location.href = `forms/adoucisseur_fleck.php?type=${typeCode}`;
    } else {
        window.location.href = `forms/adoucisseur_universal.php?type=${typeCode}`;
    }
}

function viewAdoucisseurSpecs(typeId) {
    window.location.href = `specs/adoucisseur.php?type=${typeId}`;
}

function viewAdoucisseurControl(controlId) {
    window.location.href = `?action=controles&view=${controlId}`;
}

function editAdoucisseurControl(controlId) {
    // TODO: Déterminer le type pour rediriger vers le bon formulaire
    window.location.href = `forms/edit.php?id=${controlId}`;
}

function sendAdoucisseurControl(controlId) {
    if (confirm('Envoyer ce contrôle validé à l\'agence ?')) {
        window.location.href = `actions/send.php?id=${controlId}`;
    }
}

function exportAdoucisseurData() {
    window.location.href = `export/adoucisseurs.php`;
}

function downloadClackTemplate() {
    window.location.href = `templates/clack_template.pdf`;
}

function downloadFleckTemplate() {
    window.location.href = `templates/fleck_template.pdf`;
}

function openHelp(type) {
    window.open(`help/${type}_guide.php`, '_blank');
}

function openCalculator() {
    window.open(`tools/adoucisseur_calculator.php`, '_blank');
}

function downloadChecklist() {
    window.location.href = `templates/adoucisseur_checklist.pdf`;
}
</script>="section-icon">📝</div>
                    <div class="section-info">
                        <h5>Identification</h5>
                        <ul>
                            <li>Agence, N° Dossier, N° ARC</li>
                            <li>Installation, Type de vanne</li>
                            <li>N° de série équipement</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">🔧</div>
                    <div class="section-info">
                        <h5>Caractéristiques techniques</h5>
                        <ul>
                            <li>Diamètre vanne, Type compteur</li>
                            <li>Débits BLFC/DLFC</li>
                            <li>Volume résine, Capacité échange</li>
                            <li>Dimensions bouteille et bac</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">⚙️</div>
                    <div class="section-info">
                        <h5>Programmation Clack</h5>
                        <ul>
                            <li>Phases de régénération (1-6)</li>
                            <li>Temps par phase</li>
                            <li>Type compteur (Bronze/Plastique)</li>
                            <li>Configuration CI/CIM/CIP</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">💧</div>
                    <div class="section-info">
                        <h5>Analyse eau et réglages</h5>
                        <ul>
                            <li>TH eau brute et cible</li>
                            <li>Consommations journalières</li>
                            <li>Calculs automatiques</li>
                            <li>Validation cohérence</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary btn-large" onclick="window.location.href='forms/adoucisseur_clack.php'">
                    🚀 Démarrer formulaire Clack
                </button>
                <button class="btn btn-secondary" onclick="downloadClackTemplate()">
                    📄 Télécharger template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Onglet Fleck -->
    <div id="fleck-tab" class="tab-content">
        <div class="form-preview">
            <h4>🟢 Formulaire Adoucisseur Fleck SXT</h4>
            <p>Formulaire spécialisé pour les adoucisseurs avec vannes Fleck SXT</p>
            
            <div class="form-sections-preview">
                <div class="section-preview">
                    <div class="section-icon">📝</div>
                    <div class="section-info">
                        <h5>Identification</h5>
                        <ul>
                            <li>Agence, N° Dossier, N° ARC</li>
                            <li>Installation, Modèle SXT</li>
                            <li>N° de série programmateur</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class="section-icon">🔧</div>
                    <div class="section-info">
                        <h5>Caractéristiques techniques</h5>
                        <ul>
                            <li>Type d'adoucisseur (ex: 26 SXT)</li>
                            <li>Volume résine, Capacité échange</li>
                            <li>Raccordement, Bac à sel</li>
                            <li>Vanne de remélange</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section-preview">
                    <div class
