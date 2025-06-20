/**
 * Titre: Module affichage des résultats - Calculateur
 * Chemin: /public/assets/js/modules/calculateur/resultats-display.js
 * Version: 0.5 beta + build
 * 
 * Gestion de l'affichage des résultats et comparaisons
 * Dépendance: calculateur.js, utils.js
 */

// ========================================
// MODULE AFFICHAGE RÉSULTATS
// ========================================

window.Calculateur = window.Calculateur || {};

Calculateur.Resultats = {
    
    /**
     * Configuration d'affichage
     */
    config: {
        animationDuration: 500,
        fadeDelay: 100,
        maxComparison: 5
    },
    
    /**
     * Initialisation du module résultats
     */
    init() {
        this.setupResultsContainer();
        
        if (Calculateur.Config.DEBUG) {
            console.log('📊 Module Résultats initialisé');
        }
    },
    
    /**
     * Configuration conteneur résultats
     */
    setupResultsContainer() {
        const elements = Calculateur.Elements;
        
        // État initial
        if (elements.resultContent) {
            elements.resultContent.innerHTML = this.getInitialPlaceholder();
        }
        
        if (elements.resultStatus) {
            elements.resultStatus.textContent = 'En attente';
        }
    },
    
    /**
     * Placeholder initial
     */
    getInitialPlaceholder() {
        return `
            <div class="result-placeholder">
                <div class="placeholder-icon">🚀</div>
                <h4>Prêt à calculer</h4>
                <p>Renseignez le formulaire pour voir les tarifs</p>
            </div>
        `;
    },
    
    /**
     * Affichage principal des résultats
     */
    displayResults(results) {
        if (!results) {
            this.displayNoResults();
            return;
        }
        
        switch (results.type) {
            case 'affretement':
                this.displayAffretement(results);
                break;
            case 'success':
                this.displaySuccess(results);
                break;
            default:
                this.displayNoResults();
        }
    },
    
    /**
     * Affichage résultats de succès
     */
    displaySuccess(results) {
        const elements = Calculateur.Elements;
        const bestCarrier = results.formatted[results.bestCarrier];
        
        // Mise à jour statut
        if (elements.resultStatus) {
            elements.resultStatus.textContent = `Meilleur tarif: ${bestCarrier.formatted}`;
        }
        
        // Contenu principal
        if (elements.resultContent) {
            elements.resultContent.innerHTML = this.buildSuccessContent(results, bestCarrier);
        }
        
        // Affichage comparaison si disponible
        if (results.comparison && results.comparison.count > 1) {
            this.displayComparison(results.comparison);
        }
        
        // Actions rapides
        this.showQuickActions();
        
        // Alertes si nécessaire
        this.checkAndDisplayAlerts(results);
        
        // Animation
        this.animateResults();
    },
    
    /**
     * Construction du contenu de succès
     */
    buildSuccessContent(results, bestCarrier) {
        const formData = Calculateur.State.formData;
        
        return `
            <div class="result-success fade-in">
                <div class="result-header">
                    <div class="result-badge">🏆 Meilleur tarif</div>
                    <div class="result-carrier">${bestCarrier.name}</div>
                </div>
                
                <div class="result-price">
                    <span class="price-value">${bestCarrier.formatted}</span>
                </div>
                
                <div class="result-details">
                    <div class="detail-item">
                        <span class="detail-label">📍 Destination:</span>
                        <span class="detail-value">Département ${formData.departement}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">⚖️ Poids:</span>
                        <span class="detail-value">${formData.poids}kg</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📦 Type:</span>
                        <span class="detail-value">${this.formatType(formData.type)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">⚠️ ADR:</span>
                        <span class="detail-value">${formData.adr === 'oui' ? 'Oui' : 'Non'}</span>
                    </div>
                    ${formData.option_sup !== 'standard' ? `
                    <div class="detail-item">
                        <span class="detail-label">⚙️ Option:</span>
                        <span class="detail-value">${this.formatOption(formData.option_sup)}</span>
                    </div>
                    ` : ''}
                    ${parseInt(formData.palettes) > 0 ? `
                    <div class="detail-item">
                        <span class="detail-label">🛏️ Palettes:</span>
                        <span class="detail-value">${formData.palettes} EUR</span>
                    </div>
                    ` : ''}
                </div>
                
                ${this.buildMiniComparison(results.comparison)}
            </div>
        `;
    },
    
    /**
     * Mini-comparaison intégrée
     */
    buildMiniComparison(comparison) {
        if (!comparison || comparison.count <= 1) return '';
        
        const others = comparison.carriers.slice(1, 4); // 3 premiers autres
        
        if (others.length === 0) return '';
        
        let html = '<div class="result-comparison-mini"><h5>Autres transporteurs:</h5>';
        
        others.forEach(carrier => {
            const diff = carrier.price - comparison.carriers[0].price;
            html += `
                <div class="comparison-mini-item">
                    <span class="carrier-name">${carrier.name}</span>
                    <span class="carrier-price">
                        ${carrier.formatted}
                        <small class="price-diff">+${Calculateur.Utils.formatPrice(diff)}</small>
                    </span>
                </div>
            `;
        });
        
        html += `
            <div class="comparison-mini-footer">
                <button class="btn btn-link" onclick="Calculateur.Resultats.showFullComparison()">
                    Voir la comparaison complète →
                </button>
            </div>
        </div>`;
        
        return html;
    },
    
    /**
     * Affichage affrètement
     */
    displayAffretement(results) {
        const elements = Calculateur.Elements;
        
        if (elements.resultStatus) {
            elements.resultStatus.textContent = 'Affrètement requis';
        }
        
        if (elements.resultContent) {
            elements.resultContent.innerHTML = `
                <div class="result-affretement">
                    <div class="affretement-icon">🚛</div>
                    <h4>Affrètement nécessaire</h4>
                    <p>${results.message}</p>
                    <div class="affretement-actions">
                        <button class="btn btn-primary" onclick="Calculateur.Resultats.contactAffretement()">
                            📞 Demander un devis
                        </button>
                        <button class="btn btn-secondary" onclick="Calculateur.Core.resetCalculator()">
                            🔄 Modifier les critères
                        </button>
                    </div>
                </div>
            `;
        }
        
        this.animateResults();
    },
    
    /**
     * Affichage aucun résultat
     */
    displayNoResults() {
        const elements = Calculateur.Elements;
        
        if (elements.resultStatus) {
            elements.resultStatus.textContent = 'Aucun résultat';
        }
        
        if (elements.resultContent) {
            elements.resultContent.innerHTML = `
                <div class="result-no-results">
                    <div class="no-results-icon">❌</div>
                    <h4>Aucun tarif disponible</h4>
                    <p>Vérifiez vos critères de recherche</p>
                    <button class="btn btn-secondary" onclick="Calculateur.Core.resetCalculator()">
                        🔄 Recommencer
                    </button>
                </div>
            `;
        }
        
        this.animateResults();
    },
    
    /**
     * Affichage comparaison complète
     */
    displayComparison(comparison) {
        const elements = Calculateur.Elements;
        
        if (!elements.comparisonZone) return;
        
        let html = `
            <div class="comparison-header">
                <h4>🔍 Comparaison des transporteurs</h4>
                <p>${comparison.count} transporteur(s) disponible(s)</p>
            </div>
            
            <div class="comparison-table">
        `;
        
        comparison.carriers.forEach((carrier, index) => {
            const isFirst = index === 0;
            const diff = isFirst ? 0 : carrier.price - comparison.carriers[0].price;
            
            html += `
                <div class="comparison-row ${isFirst ? 'best-offer' : ''}">
                    <div class="comparison-rank">
                        ${isFirst ? '🏆' : (index + 1)}
                    </div>
                    <div class="comparison-carrier">
                        ${carrier.name}
                    </div>
                    <div class="comparison-price">
                        ${carrier.formatted}
                        ${!isFirst ? `<small class="price-diff">+${Calculateur.Utils.formatPrice(diff)}</small>` : ''}
                    </div>
                    <div class="comparison-actions">
                        <button class="btn btn-small ${isFirst ? 'btn-primary' : 'btn-secondary'}" 
                                onclick="Calculateur.Resultats.selectCarrier('${carrier.code}')">
                            ${isFirst ? 'Sélectionné' : 'Choisir'}
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        if (comparison.range) {
            html += `
                <div class="comparison-summary">
                    <div class="summary-item">
                        <span>💰 Écart de prix:</span>
                        <span>${Calculateur.Utils.formatPrice(comparison.range.difference)}</span>
                    </div>
                    <div class="summary-item">
                        <span>💾 Économie possible:</span>
                        <span>${Math.round((comparison.range.difference / comparison.range.max) * 100)}%</span>
                    </div>
                </div>
            `;
        }
        
        elements.comparisonZone.innerHTML = html;
        elements.comparisonZone.style.display = 'block';
        elements.comparisonZone.classList.add('fade-in');
    },
    
    /**
     * Affichage alertes
     */
    checkAndDisplayAlerts(results) {
        const elements = Calculateur.Elements;
        const formData = Calculateur.State.formData;
        const alerts = [];
        
        // Alerte poids élevé
        if (formData.poids > 1000) {
            alerts.push({
                type: 'warning',
                message: `Poids élevé (${formData.poids}kg) - Vérifiez les conditions de transport`
            });
        }
        
        // Alerte ADR
        if (formData.adr === 'oui') {
            alerts.push({
                type: 'info',
                message: 'Transport ADR - Délais supplémentaires possibles'
            });
        }
        
        // Alerte prix élevé
        if (results.best > 200) {
            alerts.push({
                type: 'warning',
                message: 'Coût élevé - Envisagez de grouper vos expéditions'
            });
        }
        
        // Affichage des alertes
        if (alerts.length > 0 && elements.alertsZone) {
            let html = '<div class="alerts-header"><h5>⚠️ Informations importantes</h5></div>';
            
            alerts.forEach(alert => {
                html += `
                    <div class="alert alert-${alert.type}">
                        <span class="alert-icon">${this.getAlertIcon(alert.type)}</span>
                        <span class="alert-message">${alert.message}</span>
                    </div>
                `;
            });
            
            elements.alertsZone.innerHTML = html;
            elements.alertsZone.style.display = 'block';
            elements.alertsZone.classList.add('fade-in');
        }
    },
    
    /**
     * Icônes d'alerte
     */
    getAlertIcon(type) {
        const icons = {
            info: 'ℹ️',
            warning: '⚠️',
            error: '❌',
            success: '✅'
        };
        return icons[type] || icons.info;
    },
    
    /**
     * Actions rapides
     */
    showQuickActions() {
        const elements = Calculateur.Elements;
        
        if (!elements.quickActions) return;
        
        elements.quickActions.innerHTML = `
            <div class="quick-actions-header">
                <h5>🚀 Actions rapides</h5>
            </div>
            <div class="quick-actions-buttons">
                <button class="btn btn-primary" onclick="Calculateur.Resultats.proceedToBooking()">
                    📋 Créer l'expédition
                </button>
                <button class="btn btn-secondary" onclick="Calculateur.Resultats.saveQuote()">
                    💾 Sauvegarder le devis
                </button>
                <button class="btn btn-secondary" onclick="Calculateur.Resultats.printResults()">
                    🖨️ Imprimer
                </button>
                <button class="btn btn-link" onclick="Calculateur.Core.resetCalculator()">
                    🔄 Nouveau calcul
                </button>
            </div>
        `;
        
        elements.quickActions.style.display = 'block';
        elements.quickActions.classList.add('fade-in');
    },
    
    /**
     * Animation des résultats
     */
    animateResults() {
        const elements = Calculateur.Elements;
        
        if (elements.resultMain) {
            elements.resultMain.classList.add('slide-up');
            setTimeout(() => {
                elements.resultMain.classList.remove('slide-up');
            }, this.config.animationDuration);
        }
    },
    
    /**
     * Formatage type d'envoi
     */
    formatType(type) {
        const types = {
            'colis': 'Colis',
            'palette': 'Palette'
        };
        return types[type] || type;
    },
    
    /**
     * Formatage option
     */
    formatOption(option) {
        const options = {
            'standard': 'Standard',
            'rdv': 'Prise de RDV',
            'datefixe': 'Date fixe',
            'premium13': 'Premium avant 13h',
            'premium18': 'Premium avant 18h'
        };
        return options[option] || option;
    },
    
    /**
     * Actions utilisateur
     */
    showFullComparison() {
        if (Calculateur.Elements.comparisonZone) {
            Calculateur.Elements.
