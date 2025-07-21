// debug-portail-calculateur.js
(function() {
    // Sécurité : le module doit être chargé
    if (!window.CalculateurModule) {
        console.error('[DEBUG] CalculateurModule non trouvé sur la page.');
        return;
    }

    const mod = window.CalculateurModule;
    console.group('🕵️‍♂️ DEBUG - CalculateurModule - Etat général');
    console.log('currentStep:', mod.state.currentStep);
    console.log('adrSelected:', mod.state.adrSelected);
    console.log('isCalculating:', mod.state.isCalculating);
    console.log('validationErrors:', mod.state.validationErrors);
    console.groupEnd();

    // Vérifie la présence et l’état des éléments DOM principaux
    const fields = ['departement', 'poids', 'type', 'palettes', 'paletteEur', 'adr', 'enlevement', 'optionSup'];
    fields.forEach(id => {
        const el = mod.dom[id];
        if (el) {
            console.log(`✅ DOM #${id} OK`, el);
        } else {
            console.warn(`❌ DOM #${id} ABSENT`);
        }
    });

    // Vérifie la structure des étapes
    console.group('🕵️‍♂️ DEBUG - Structure des étapes');
    if (mod.dom.stepBtns && mod.dom.stepBtns.length > 0) {
        mod.dom.stepBtns.forEach(btn => {
            const step = btn.dataset.step;
            console.log(`Step-btn [data-step="${step}"] :`, btn, 
                btn.classList.contains('active') ? 'active' : '', 
                btn.classList.contains('completed') ? 'completed' : ''
            );
        });
    } else {
        console.warn('❌ Aucun bouton .calc-step-btn trouvé');
    }

    if (mod.dom.stepContents && mod.dom.stepContents.length > 0) {
        mod.dom.stepContents.forEach(content => {
            const step = content.dataset.step;
            console.log(`Step-content [data-step="${step}"] : display=${content.style.display || 'default'},`, 
                content.classList.contains('active') ? 'active' : ''
            );
        });
    } else {
        console.warn('❌ Aucun contenu .calc-step-content trouvé');
    }
    console.groupEnd();

    // Test de progression manuelle
    window.debugActivateStep = function(n) {
        console.log(`[DEBUG] Activation manuelle étape ${n}`);
        mod.activateStep(n);
    };

    // Test validité des champs principaux
    try {
        const deptValid = mod.validateDepartement();
        const poidsValid = mod.validatePoids();
        console.log(`[DEBUG] Résultat validateDepartement():`, deptValid);
        console.log(`[DEBUG] Résultat validatePoids():`, poidsValid);
    } catch (e) {
        console.error('[DEBUG] Erreur lors de la validation des champs:', e);
    }

    // Analyse rapide du HTML pour les champs
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (!el) {
            console.warn(`[DEBUG] Champ manquant dans HTML: #${id}`);
        } else {
            // Affiche le type de champ et sa valeur actuelle
            console.log(`[DEBUG] Champ #${id} type=<${el.tagName.toLowerCase()}> value="${el.value}"`);
        }
    });

    // Rappel des actions utiles
    console.log('%c[DEBUG] Pour activer une étape manuellement, tapez debugActivateStep(n) en console (ex: debugActivateStep(2))', 'color: #2563eb; font-weight:bold');

    // Synthèse finale
    console.info('--- Fin du diagnostic DEBUG ---');
})();
