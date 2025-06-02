<?php
// public/admin/modals/edit-rate-modal.php
// Modal d'édition des tarifs avec validation côté client
?>
<!-- Modal d'édition des tarifs -->
<div id="edit-rate-modal" class="modal" style="display: none;">
    <div class="modal-content edit-rate-content">
        <div class="modal-header">
            <h3>✏️ <span id="modal-title">Édition du tarif</span></h3>
            <button class="modal-close" onclick="closeEditModal()" aria-label="Fermer">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Indicateur de sauvegarde -->
            <div id="save-indicator" class="save-indicator" style="display: none;">
                <div class="save-spinner"></div>
                <span>Sauvegarde en cours...</span>
            </div>
            
            <form id="edit-rate-form" autocomplete="off">
                <!-- Section informations générales -->
                <div class="form-section">
                    <h4>📋 Informations générales</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-carrier">
                                Transporteur
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="edit-carrier" 
                                   class="form-control"
                                   readonly 
                                   placeholder="Transporteur">
                            <div class="field-help">
                                <small>Le transporteur ne peut pas être modifié</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-department-num">
                                Département
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="edit-department-num" 
                                   class="form-control"
                                   readonly 
                                   placeholder="XX">
                            <div class="field-help">
                                <small>Le numéro de département ne peut pas être modifié</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-department-name">
                                Nom du département
                            </label>
                            <input type="text" 
                                   id="edit-department-name" 
                                   class="form-control"
                                   placeholder="Ex: Bas-Rhin"
                                   maxlength="50">
                            <div class="field-help">
                                <small>Nom complet du département</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-delay">
                                Délai de livraison
                            </label>
                            <select id="edit-delay" class="form-control">
                                <option value="">Sélectionner un délai...</option>
                                <option value="24h">24h</option>
                                <option value="24h - 48h">24h - 48h</option>
                                <option value="48h">48h</option>
                                <option value="48h - 72h">48h - 72h</option>
                                <option value="72h">72h</option>
                                <option value="72h - 96h">72h - 96h</option>
                                <option value="96h">96h</option>
                            </select>
                            <div class="field-help">
                                <small>Délai habituel de livraison</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section tarifs par tranche de poids -->
                <div class="form-section">
                    <h4>💰 Tarifs par tranche de poids</h4>
                    <div class="section-help">
                        <p><strong>💡 Instructions :</strong></p>
                        <ul>
                            <li>Saisir les tarifs en euros (ex: 12.50)</li>
                            <li>Laisser vide si la tranche n'est pas applicable</li>
                            <li>Les tarifs sont calculés automatiquement pour les poids intermédiaires</li>
                        </ul>
                    </div>
                    
                    <!-- Tarifs détaillés (Heppner et K+N) -->
                    <div class="tariffs-grid" id="detailed-tariffs">
                        <div class="form-group">
                            <label for="edit-tarif-0-9">
                                0-9 kg
                                <span class="tariff-type">(Forfait)</span>
                            </label>
                            <input type="number" 
                                   id="edit-tarif-0-9" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="0-9">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-10-19">
                                10-19 kg
                                <span class="tariff-type">(Forfait)</span>
                            </label>
                            <input type="number" 
                                   id="edit-tarif-10-19" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="10-19">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-20-29">20-29 kg</label>
                            <input type="number" 
                                   id="edit-tarif-20-29" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="20-29">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-30-39">30-39 kg</label>
                            <input type="number" 
                                   id="edit-tarif-30-39" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="30-39">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-40-49">40-49 kg</label>
                            <input type="number" 
                                   id="edit-tarif-40-49" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="40-49">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-50-59">50-59 kg</label>
                            <input type="number" 
                                   id="edit-tarif-50-59" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="50-59">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-60-69">60-69 kg</label>
                            <input type="number" 
                                   id="edit-tarif-60-69" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="60-69">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-70-79">70-79 kg</label>
                            <input type="number" 
                                   id="edit-tarif-70-79" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="70-79">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-80-89">80-89 kg</label>
                            <input type="number" 
                                   id="edit-tarif-80-89" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="80-89">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-90-99">
                                90-99 kg
                                <span class="tariff-type">(Important)</span>
                            </label>
                            <input type="number" 
                                   id="edit-tarif-90-99" 
                                   class="form-control tariff-input important"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="90-99">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-100-299">
                                100-299 kg
                                <span class="tariff-type">(Base calcul)</span>
                            </label>
                            <input type="number" 
                                   id="edit-tarif-100-299" 
                                   class="form-control tariff-input base-rate"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="100-299">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-300-499">300-499 kg</label>
                            <input type="number" 
                                   id="edit-tarif-300-499" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="300-499">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-500-999">500-999 kg</label>
                            <input type="number" 
                                   id="edit-tarif-500-999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="500-999">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-tarif-1000-1999">1000-1999 kg</label>
                            <input type="number" 
                                   id="edit-tarif-1000-1999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="1000-1999">
                        </div>
                        
                        <!-- Tarif spécial XPO -->
                        <div class="form-group" id="edit-tarif-2000-group" style="display: none;">
                            <label for="edit-tarif-2000-2999">
                                2000-2999 kg
                                <span class="tariff-type">(XPO uniquement)</span>
                            </label>
                            <input type="number" 
                                   id="edit-tarif-2000-2999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00"
                                   data-weight="2000-2999">
                        </div>
                    </div>
                    
                    <!-- Tarifs simplifiés XPO -->
                    <div class="tariffs-grid" id="xpo-tariffs" style="display: none;">
                        <div class="form-group">
                            <label for="edit-xpo-0-99">
                                0-99 kg
                                <span class="tariff-type">(Forfait)</span>
                            </label>
                            <input type="number" 
                                   id="edit-xpo-0-99" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-xpo-100-499">100-499 kg</label>
                            <input type="number" 
                                   id="edit-xpo-100-499" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-xpo-500-999">500-999 kg</label>
                            <input type="number" 
                                   id="edit-xpo-500-999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-xpo-1000-1999">1000-1999 kg</label>
                            <input type="number" 
                                   id="edit-xpo-1000-1999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-xpo-2000-2999">2000-2999 kg</label>
                            <input type="number" 
                                   id="edit-xpo-2000-2999" 
                                   class="form-control tariff-input"
                                   step="0.01" 
                                   min="0" 
                                   max="9999.99"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>

                <!-- Section validation et aide -->
                <div class="form-section">
                    <h4>✅ Validation et aide</h4>
                    
                    <!-- Résumé de saisie -->
                    <div class="validation-summary" id="validation-summary">
                        <div class="summary-item">
                            <span class="summary-label">Tranches remplies :</span>
                            <span class="summary-value" id="filled-ranges">0/14</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Statut de complétude :</span>
                            <span class="summary-value" id="completion-status">
                                <span class="badge badge-warning">Incomplet</span>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Cohérence des tarifs :</span>
                            <span class="summary-value" id="rate-consistency">
                                <span class="badge badge-info">À vérifier</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Messages de validation -->
                    <div class="validation-messages" id="validation-messages">
                        <!-- Messages dynamiques -->
                    </div>
                    
                    <!-- Actions rapides -->
                    <div class="quick-actions">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllRates()">
                            🗑️ Vider tous les tarifs
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="copyFromTemplate()">
                            📋 Copier depuis un modèle
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="calculateSuggestions()">
                            💡 Suggestions automatiques
                        </button>
                    </div>
                </div>

                <!-- Champs cachés pour les métadonnées -->
                <input type="hidden" id="edit-rate-id">
                <input type="hidden" id="edit-carrier-code">
                <input type="hidden" id="edit-original-data">
            </form>
        </div>
        
        <div class="modal-footer">
            <div class="footer-info">
                <small class="text-muted">
                    💾 Sauvegarde automatique activée • 
                    <span id="last-save">Aucune modification</span>
                </small>
            </div>
            <div class="footer-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <span>❌</span>
                    Annuler
                </button>
                <button type="button" class="btn btn-warning" onclick="resetChanges()" id="reset-btn" style="display: none;">
                    <span>🔄</span>
                    Réinitialiser
                </button>
                <button type="button" class="btn btn-success" onclick="validateAndPreview()" id="preview-btn">
                    <span>👁️</span>
                    Aperçu
                </button>
                <button type="button" class="btn btn-primary" onclick="saveRateChanges()" id="save-btn">
                    <span>💾</span>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques à la modal -->
<style>
/* Styles pour la modal d'édition des tarifs */
.edit-rate-content {
    max-width: 1000px !important;
    width: 95% !important;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

.form-section h4 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-help {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.section-help p {
    margin: 0 0 0.5rem 0;
    color: #1565c0;
    font-weight: 600;
}

.section-help ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #1976d2;
}

.section-help li {
    margin-bottom: 0.25rem;
}

.tariff-input.important {
    border-color: var(--warning-color) !important;
    background: #fff8e1;
}

.tariff-input.base-rate {
    border-color: var(--success-color) !important;
    background: #e8f5e8;
    font-weight: 600;
}

.tariff-type {
    font-size: 0.7rem;
    color: var(--warning-color);
    font-weight: 500;
}

.required {
    color: var(--error-color);
    font-weight: bold;
}

.field-help small {
    color: #666;
    font-style: italic;
}

.save-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 10;
}

.save-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.validation-summary {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: 500;
    color: #555;
}

.validation-messages {
    min-height: 40px;
    padding: 1rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.validation-message {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.validation-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.validation-message.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.validation-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.footer-info {
    flex: 1;
    text-align: left;
}

.footer-actions {
    display: flex;
    gap: 0.5rem;
}

#last-save {
    font-weight: 500;
    color: var(--primary-color);
}

/* Animation pour les changements de tarifs */
.tariff-input.changed {
    background: #fff3cd !important;
    border-color: var(--warning-color) !important;
    animation: highlightChange 0.3s ease;
}

@keyframes highlightChange {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Responsive pour la modal */
@media (max-width: 768px) {
    .edit-rate-content {
        width: 98% !important;
        margin: 1% !important;
        max-height: 95vh !important;
    }
    
    .tariffs-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) !important;
    }
    
    .footer-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .footer-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .section-help {
        padding: 0.75rem;
    }
}

@media (max-width: 480px) {
    .tariffs-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)) !important;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .quick-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
