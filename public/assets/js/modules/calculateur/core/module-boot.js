/**
 * Titre: Module d'initialisation et correction des dépendances
 * Chemin: /public/assets/js/modules/calculateur/core/module-boot.js
 * Version: 0.5 beta + build
 * 
 * Ce fichier résout le problème d'initialisation des modules
 * en créant les instances globales manquantes et en gérant
 * l'ordre de chargement des dépendances.
 */

(function() {
    'use strict';

    // =========================================================================
    // GESTIONNAIRE DE BOOT DES MODULES
    // =========================================================================

    class ModuleBoot {
        constructor() {
            this.loadedModules = new Set();
            this.initQueue = [];
            this.isBooting = false;
        }

        /**
         * Vérifier si tous les modules requis sont chargés
         */
        checkDependencies() {
            const required = [
                'CalculateurConfig',
                'calculateurState', 
                'apiService',
                'formDataModel',
                'validationModel',
                'FormController',
                'CalculationController', 
                'UIController',
                'ProgressiveFormView',
                'ResultsDisplayView'
            ];

            const missing = [];
            const available = [];

            required.forEach(dep => {
                if (typeof window[dep] !== 'undefined') {
                    available.push(dep);
                    this.loadedModules.add(dep);
                } else {
                    missing.push(dep);
                }
            });

            console.log('📦 Modules disponibles:', available);
            if (missing.length > 0) {
                console.warn('⚠️ Modules manquants:', missing);
            }

            return { missing, available, allLoaded: missing.length === 0 };
        }

        /**
         * Créer les instances globales manquantes
         */
        createInstances() {
            console.log('🔧 Création des instances globales...');

            // StateManager - Vérifier s'il existe
            if (!window.calculateurState) {
                console.log('🆕 Création de calculateurState...');
                // Créer un StateManager minimal si nécessaire
                window.calculateurState = this.createMinimalStateManager();
            }

            // Form Data Model
            if (!window.formDataModel) {
                console.log('🆕 Création de formDataModel...');
                window.formDataModel = this.createMinimalFormDataModel();
            }

            // Validation Model
            if (!window.validationModel) {
                console.log('🆕 Création de validationModel...');
                window.validationModel = this.createMinimalValidationModel();
            }

            // Controllers - Créer instances à partir des classes
            if (window.FormController && !window.formController) {
                console.log('🆕 Création de formController...');
                window.formController = new window.FormController();
            }

            if (window.CalculationController && !window.calcController) {
                console.log('🆕 Création de calcController...');
                window.calcController = new window.CalculationController();
            }

            if (window.UIController && !window.uiController) {
                console.log('🆕 Création de uiController...');
                window.uiController = new window.UIController();
            }

            // Views - Créer instances si les classes existent
            if (window.ProgressiveFormView && !window.progressiveFormView) {
                console.log('🆕 Création de progressiveFormView...');
                window.progressiveFormView = new window.ProgressiveFormView();
            }

            if (window.ResultsDisplayView && !window.resultsDisplayView) {
                console.log('🆕 Création de resultsDisplayView...');
                window.resultsDisplayView = new window.ResultsDisplayView();
            }
        }

        /**
         * StateManager minimal pour éviter les erreurs
         */
        createMinimalStateManager() {
            return {
                data: {},
                observers: {},
                
                get(path) {
                    return this.getNestedValue(this.data, path);
                },
                
                set(path, value) {
                    this.setNestedValue(this.data, path, value);
                    this.notifyObservers(path, value);
                },
                
                updateFormData(field, value) {
                    if (!this.data.formData) this.data.formData = {};
                    this.data.formData[field] = value;
                    this.notifyObservers('formData', this.data.formData);
                },
                
                setCalculating(isCalculating) {
                    this.set('ui.isCalculating', isCalculating);
                },
                
                observe(path, callback) {
                    if (!this.observers[path]) this.observers[path] = [];
                    this.observers[path].push(callback);
                },
                
                notifyObservers(path, value) {
                    const callbacks = this.observers[path] || [];
                    callbacks.forEach(cb => {
                        try {
                            cb(value);
                        } catch (error) {
                            console.error('Error in observer:', error);
                        }
                    });
                },
                
                getNestedValue(obj, path) {
                    return path.split('.').reduce((current, key) => current && current[key], obj);
                },
                
                setNestedValue(obj, path, value) {
                    const keys = path.split('.');
                    const lastKey = keys.pop();
                    const target = keys.reduce((current, key) => {
                        if (!current[key]) current[key] = {};
                        return current[key];
                    }, obj);
                    target[lastKey] = value;
                },
                
                reset() {
                    this.data = {};
                    console.log('State reset');
                }
            };
        }

        /**
         * FormDataModel minimal
         */
        createMinimalFormDataModel() {
            return {
                isComplete(data) {
                    return data && data.departement && data.poids && data.type;
                },
                
                normalize(data) {
                    return {
                        departement: String(data.departement || ''),
                        poids: parseFloat(data.poids) || 0,
                        type: String(data.type || ''),
                        adr: data.adr === 'oui' ? 'oui' : 'non',
                        service_livraison: data.service_livraison || 'standard',
                        enlevement: Boolean(data.enlevement),
                        palettes: parseInt(data.palettes) || 0
                    };
                }
            };
        }

        /**
         * ValidationModel minimal
         */
        createMinimalValidationModel() {
            return {
                validateAll(data) {
                    const errors = {};
                    let valid = true;

                    if (!data.departement) {
                        errors.departement = 'Département requis';
                        valid = false;
                    }

                    if (!data.poids || data.poids <= 0) {
                        errors.poids = 'Poids requis';
                        valid = false;
                    }

                    if (!data.type) {
                        errors.type = 'Type requis';
                        valid = false;
                    }

                    return {
                        valid,
                        fields: errors
                    };
                }
            };
        }

        /**
         * CalculateurConfig minimal si manquant
         */
        createMinimalConfig() {
            if (window.CalculateurConfig) return;

            window.CalculateurConfig = {
                DEBUG: { ENABLED: true },
                META: { VERSION: '0.5.0-beta' },
                VALIDATION: {
                    DEPT_PATTERN: /^\d{2,3}$/,
                    MIN_POIDS: 1,
                    MAX_POIDS: 3000,
                    MESSAGES: {
                        DEPT_INVALID: 'Département invalide',
                        POIDS_TOO_LOW: 'Poids trop faible',
                        POIDS_TOO_HIGH: 'Poids trop élevé'
                    }
                },
                TIMING: {
                    DEBOUNCE_DELAY: 300,
                    AUTO_CALC_DELAY: 500
                },
                API: {
                    ENDPOINT: 'ajax-calculate.php',
                    TIMEOUT: 10000,
                    METHODS: { CALCULATE: 'POST' }
                },
                
                log(level, ...args) {
                    if (this.DEBUG.ENABLED) {
                        console[level]('[Calculateur]', ...args);
                    }
                }
            };
        }

        /**
         * API Service minimal
         */
        createMinimalApiService() {
            if (window.apiService) return;

            window.apiService = {
                init() {
                    console.log('API Service initialisé');
                },
                
                async calculate(formData) {
                    console.log('Calcul avec:', formData);
                    
                    try {
                        const response = await fetch('ajax-calculate.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(formData)
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        return await response.json();
                    } catch (error) {
                        console.error('Erreur API:', error);
                        throw error;
                    }
                },
                
                getStats() {
                    return { requests: 0, successes: 0, errors: 0 };
                }
            };
        }

        /**
         * Lancer le boot complet
         */
        async boot() {
            if (this.isBooting) return;
            this.isBooting = true;

            console.log('🚀 Démarrage du module boot...');

            // 1. Créer config minimal
            this.createMinimalConfig();

            // 2. Créer API service minimal
            this.createMinimalApiService();

            // 3. Attendre que les modules soient chargés
            await this.waitForModules();

            // 4. Créer les instances
            this.createInstances();

            // 5. Vérifier les dépendances finales
            const check = this.checkDependencies();
            
            if (check.allLoaded) {
                console.log('✅ Tous les modules sont prêts');
                this.startApplication();
            } else {
                console.warn('⚠️ Modules encore manquants:', check.missing);
                this.startFallback();
            }

            this.isBooting = false;
        }

        /**
         * Attendre que les modules soient chargés
         */
        async waitForModules(maxAttempts = 20) {
            let attempts = 0;
            
            while (attempts < maxAttempts) {
                const check = this.checkDependencies();
                
                if (check.available.length >= 3) { // Au moins les modules de base
                    break;
                }
                
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
        }

        /**
         * Démarrer l'application principale
         */
        startApplication() {
            console.log('🎯 Démarrage de l\'application...');
            
            // Initialiser CalculateurApp si disponible
            if (window.CalculateurApp) {
                const app = new window.CalculateurApp();
                app.init(window.CalculateurServerConfig || {})
                    .then(() => {
                        console.log('✅ Application initialisée avec succès');
                    })
                    .catch(error => {
                        console.error('❌ Erreur application:', error);
                        this.startFallback();
                    });
            } else {
                this.startFallback();
            }
        }

        /**
         * Mode de secours
         */
        startFallback() {
            console.log('🔄 Activation du mode de secours...');
            
            // Gestion basique du formulaire
            const form = document.getElementById('calculator-form');
            if (form) {
                this.setupBasicFormHandling(form);
            }
        }

        /**
         * Gestion basique du formulaire
         */
        setupBasicFormHandling(form) {
            // Gestion du type d'envoi
            const typeRadios = form.querySelectorAll('input[name="type"]');
            const palettesField = document.getElementById('field-palettes');
            
            typeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (palettesField) {
                        palettesField.style.display = radio.value === 'palette' ? 'block' : 'none';
                    }
                });
            });

            // Soumission du formulaire
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const response = await fetch('ajax-calculate.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    this.displayResults(result);
                } catch (error) {
                    console.error('Erreur calcul:', error);
                    this.displayError('Erreur de calcul');
                }
            });
        }

        /**
         * Affichage des résultats en mode de secours
         */
        displayResults(result) {
            const resultsContainer = document.querySelector('.results-content') || 
                                   document.querySelector('#results-content') ||
                                   document.querySelector('.results-panel');
            
            if (!resultsContainer) return;

            if (result.success && result.best) {
                resultsContainer.innerHTML = `
                    <div class="results-success">
                        <h3>🎯 Meilleur tarif</h3>
                        <div class="best-result">
                            <strong>${result.best.transporteur}</strong><br>
                            <span class="price">${result.best.prix_total.toFixed(2)}€</span>
                        </div>
                    </div>
                `;
            } else {
                this.displayError(result.message || 'Aucun résultat');
            }
        }

        /**
         * Affichage d'erreur
         */
        displayError(message) {
            const resultsContainer = document.querySelector('.results-content') || 
                                   document.querySelector('#results-content') ||
                                   document.querySelector('.results-panel');
            
            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="results-error">
                        <p>❌ ${message}</p>
                        <button onclick="location.reload()">Recharger</button>
                    </div>
                `;
            }
        }
    }

    // =========================================================================
    // INITIALISATION AUTOMATIQUE
    // =========================================================================

    // Créer l'instance globale
    window.moduleBoot = new ModuleBoot();

    // Fonction d'initialisation
    function initModuleBoot() {
        console.log('🔧 Initialisation du module boot...');
        window.moduleBoot.boot();
    }

    // Lancer selon l'état du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleBoot);
    } else {
        // DOM déjà prêt, lancer immédiatement mais avec un petit délai
        // pour laisser les autres scripts se charger
        setTimeout(initModuleBoot, 100);
    }

    // Exposer utilitaires globaux pour debug
    window.debugCalculateur = {
        boot: window.moduleBoot,
        restart: () => {
            console.log('🔄 Redémarrage des modules...');
            window.moduleBoot.boot();
        },
        check: () => window.moduleBoot.checkDependencies(),
        fallback: () => window.moduleBoot.startFallback()
    };

})();
