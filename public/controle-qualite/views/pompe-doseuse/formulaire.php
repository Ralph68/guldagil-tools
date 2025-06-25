<!-- /public/controle-qualite/views/pompe-doseuse/formulaire.php -->
<div class="cq-form-container">
    <h1>⚗️ Contrôle Pompe Doseuse</h1>
    
    <form method="POST" class="cq-form">
        <!-- Identifiants -->
        <fieldset>
            <legend>📋 Identifiants</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="numero_arc">N° ARC *</label>
                    <input type="text" id="numero_arc" name="numero_arc" required>
                </div>
                <div class="form-group">
                    <label for="numero_dossier">N° Dossier</label>
                    <input type="text" id="numero_dossier" name="numero_dossier">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="agence">Agence *</label>
                    <select id="agence" name="agence" required>
                        <option value="">Sélectionnez...</option>
                        <?php foreach ($agences as $agence): ?>
                            <option value="<?= htmlspecialchars($agence) ?>"><?= htmlspecialchars($agence) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_expedition">Date expédition *</label>
                    <input type="date" id="date_expedition" name="date_expedition" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="nom_installation">Nom installation *</label>
                <input type="text" id="nom_installation" name="nom_installation" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="operateur_nom">Opérateur *</label>
                    <input type="text" id="operateur_nom" name="operateur_nom" required>
                </div>
                <div class="form-group">
                    <label for="operateur_email">Email opérateur *</label>
                    <input type="email" id="operateur_email" name="operateur_email" required>
                </div>
            </div>
        </fieldset>

        <!-- Équipement -->
        <fieldset>
            <legend>⚙️ Équipement</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="marque">Marque *</label>
                    <select id="marque" name="marque" required>
                        <option value="">Sélectionnez...</option>
                        <option value="TEKNA">TEKNA</option>
                        <option value="GRUNDFOS">GRUNDFOS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modele">Modèle *</label>
                    <input type="text" id="modele" name="modele" required placeholder="Ex: APG603NHH1003">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ref_gul">Ref Gul *</label>
                    <input type="text" id="ref_gul" name="ref_gul" required placeholder="Ex: DOS4-8V">
                </div>
                <div class="form-group">
                    <label for="numero_serie">N° série</label>
                    <input type="text" id="numero_serie" name="numero_serie">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="debit_maxi">Débit maxi (L/h)</label>
                    <input type="number" step="0.01" id="debit_maxi" name="debit_maxi">
                </div>
                <div class="form-group">
                    <label for="cylindree_maxi">Cylindrée maxi (ml/m³)</label>
                    <input type="number" step="0.001" id="cylindree_maxi" name="cylindree_maxi">
                </div>
            </div>
        </fieldset>

        <!-- Checklist Équipements -->
        <fieldset>
            <legend>✅ Équipements</legend>
            <div class="cq-checklist">
                <label><input type="checkbox" name="socle_plastique" value="1"> Socle Plastique</label>
                <label><input type="checkbox" name="connecteur_compteur_3vis" value="1"> Connecteur Compteur 3 vis vert (seko)</label>
                <label><input type="checkbox" name="connecteur_moule_4broches" value="1"> Connecteur moulé à 4 broches (DDE)</label>
                <label><input type="checkbox" name="raccords_pompes" value="1"> Raccords de pompes</label>
                <label><input type="checkbox" name="canne_injection_pvdf" value="1"> Canne d'injection PVDF (Blanche)</label>
                <label><input type="checkbox" name="crepine_aspiration_pvdf" value="1"> Crépine d'aspiration PVDF (Blanche)</label>
                <label><input type="checkbox" name="contact_niveau" value="1"> 1 Contact de niveau</label>
                <label><input type="checkbox" name="connecteur_2vis_niveau" value="1"> Connecteur 2 vis pour contact de niveau vert (seko)</label>
                <label><input type="checkbox" name="tuyau_souple_transparent" value="1"> Tuyau souple transparent ≥ 2 mètres</label>
                <label><input type="checkbox" name="tuyau_semi_rigide_opaque" value="1"> Tuyau semi rigide opaque ≥ 5 mètres</label>
                <label><input type="checkbox" name="vis_plastique_4" value="1"> 4 vis plastique</label>
            </div>
        </fieldset>

        <!-- Documentation -->
        <fieldset>
            <legend>📄 Documentation</legend>
            <div class="cq-checklist">
                <label><input type="checkbox" name="doc_technaevo_em136081" value="1"> Doc instructions Technaevo EM00136081</label>
                <label><input type="checkbox" name="doc_technaevo_em136060" value="1"> Doc instructions Technaevo EM00136060</label>
                <label><input type="checkbox" name="notice_grundfos_v2" value="1"> Notice d'installation DDE 6-10 grundfos V2</label>
                <label><input type="checkbox" name="doc_commercial_dos6" value="1"> Doc commerciale DOS 6 DDE</label>
                <label><input type="checkbox" name="doc_commercial_dos4_8v" value="1"> Doc commerciale DOS 4-8V</label>
            </div>
        </fieldset>

        <!-- Compteur -->
        <fieldset>
            <legend>📊 Compteur d'impulsion</legend>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="compteur_present" name="compteur_present" value="1" onchange="toggleCompteur()">
                    Compteur d'impulsion livré par Guldagil
                </label>
            </div>
            
            <div id="compteur_details" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="compteur_numero_serie">N° série compteur</label>
                        <input type="text" id="compteur_numero_serie" name="compteur_numero_serie">
                    </div>
                    <div class="form-group">
                        <label for="compteur_ref_gul">Ref Gul compteur</label>
                        <input type="text" id="compteur_ref_gul" name="compteur_ref_gul" placeholder="C__S DHM">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="compteur_type">Type</label>
                        <select id="compteur_type" name="compteur_type">
                            <option value="">Sélectionnez...</option>
                            <option value="DHM 1000">DHM 1000</option>
                            <option value="GMWFI (à Brides)">GMWFI (à Brides)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="compteur_diametre">Diamètre</label>
                        <input type="text" id="compteur_diametre" name="compteur_diametre">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="compteur_k_cteur">K Cteur</label>
                    <input type="text" id="compteur_k_cteur" name="compteur_k_cteur">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="doc_compteur_guldagil" value="1">
                        Doc "Guldagil" du Compteur
                    </label>
                </div>
            </div>
        </fieldset>

        <!-- Observations -->
        <fieldset>
            <legend>📝 Observations</legend>
            <div class="form-group">
                <label for="observations">Observations</label>
                <textarea id="observations" name="observations" rows="4" placeholder="Observations particulières..."></textarea>
            </div>
        </fieldset>

        <!-- Actions -->
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">
                💾 Valider et générer PDF
            </button>
        </div>
    </form>
</div>

<script>
function toggleCompteur() {
    const checkbox = document.getElementById('compteur_present');
    const details = document.getElementById('compteur_details');
    details.style.display = checkbox.checked ? 'block' : 'none';
}
</script>
