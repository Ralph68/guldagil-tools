// =============================================================================
// FICHIER 7: /public/assets/js/modules/calculateur/views/results-view.js
// =============================================================================

/**
 * Vue des résultats
 */
class ResultsView {
    constructor() {
        this.elements = {};
        this.bindMethods();
    }
    
    bindMethods() {
        this.displayResults = this.displayResults.bind(this);
        this.displayError = this.displayError.bind(this);
        this.setLoadingState = this.setLoadingState.bind(this);
    }
    
    /**
     * Initialisation
     */
    init() {
        this.cacheElements();
        
        if (CalculateurConfig.DEBUG) {
            console.log('✅ ResultsView initialisée');
        }
    }
    
    /**
     * Cache des éléments
     */
    cacheElements() {
        this.elements = {
            section: document.querySelector('.results-section'),
            status: document.getElementById('results-status'),
            content: document.getElementById('results-content'),
            comparison: document.getElementById('comparison-content')
        };
    }
    
    /**
     * Affichage des résultats
     */
    displayResults(results) {
        if (!this.elements.content) return;
        
        if (results.affretement) {
            this.displayAffretement(results.message);
            return;
        }
        
        if (results.success && results.carriers) {
            this.renderSuccessResults(results);
        } else {
            this.displayError('Aucun tarif disponible');
        }
    }
    
    /**
     * Rendu des résultats de succès
     */
    renderSuccessResults(results) {
        // Mise à jour du statut
        if (this.elements.status) {
            this.elements.status.textContent = 'Tarifs disponibles';
            this.elements.status.className = 'results-status success';
        }
        
        // Contenu principal - meilleur tarif
        let html = '';
        
        if (results.best_rate) {
            html += `
                <div class="best-rate">
                    <div class="best-rate-header">
                        <h3>🏆 Meilleur tarif</h3>
                    </div>
                    <div class="best-rate-content">
                        <div class="carrier-name">${results.best_rate.carrier_name}</div>
                        <div class="price">${results.best_rate.formatted}</div>
                        <div class="delivery-time">${results.best_rate.delivery_info || ''}</div>
                    </div>
                </div>
            `;
        }
        
        // Comparaison des transporteurs
        if (results.carriers && Object.keys(results.carriers).length > 1) {
            html += '<div class="comparison-section">';
            html += '<h4>Comparaison des transporteurs</h4>';
            html += '<div class="carriers-list">';
            
            Object.entries(results.carriers).forEach(([carrier, data]) => {
                const isBest = results.best_rate?.carrier === carrier;
                
                html += `
                    <div class="carrier-item ${isBest ? 'best' : ''}">
                        <div class="carrier-info">
                            <span class="carrier-name">${data.name}</span>
                            ${isBest ? '<span class="best-badge">Meilleur</span>' : ''}
                        </div>
                        <div class="carrier-price">
                            ${data.price ? data.formatted : 'Non disponible'}
                        </div>
                    </div>
                `;
            });
            
            html += '</div></div>';
        }
        
        this.elements.content.innerHTML = html;
        
        // Animation d'apparition
        this.elements.content.style.opacity = '0';
        requestAnimationFrame(() => {
            this.elements.content.style.transition = 'opacity 0.3s ease';
            this.elements.content.style.opacity = '1';
        });
    }
    
    /**
     * Affichage d'affrètement
     */
    displayAffretement(message) {
        if (this.elements.status) {
            this.elements.status.textContent = 'Affrètement requis';
            this.elements.status.className = 'results-status warning';
        }
        
        this.elements.content.innerHTML = `
            <div class="affretement-message">
                <div class="affretement-icon">🚛</div>
                <h3>Affrètement nécessaire</h3>
                <p>${message}</p>
                <div class="contact-info">
                    <strong>📞 Service commercial : 03 89 63 42 42</strong>
                </div>
            </div>
        `;
    }
    
    /**
     * Affichage d'erreur
     */
    displayError(error) {
        if (this.elements.status) {
            this.elements.status.textContent = 'Erreur';
            this.elements.status.className = 'results-status error';
        }
        
        this.elements.content.innerHTML = `
            <div class="error-message">
                <div class="error-icon">❌</div>
                <h3>Erreur de calcul</h3>
                <p>${error}</p>
                <button type="button" onclick="window.formController?.reset()" class="retry-btn">
                    Réessayer
                </button>
            </div>
        `;
    }
    
    /**
     * État de chargement
     */
    setLoadingState(isLoading) {
        if (!this.elements.content) return;
        
        if (isLoading) {
            if (this.elements.status) {
                this.elements.status.textContent = 'Calcul en cours...';
                this.elements.status.className = 'results-status loading';
            }
            
            this.elements.content.innerHTML = `
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul des tarifs en cours...</p>
                </div>
            `;
        }
    }
    
    /**
     * État par défaut
     */
    showPlaceholder() {
        if (this.elements.status) {
            this.elements.status.textContent = 'En attente';
            this.elements.status.className = 'results-status placeholder';
        }
        
        this.elements.content.innerHTML = `
            <div class="placeholder-state">
                <div class="placeholder-icon">🚀</div>
                <h3>Prêt à calculer</h3>
                <p>Renseignez le formulaire pour voir les tarifs de nos transporteurs partenaires</p>
            </div>
        `;
    }
}

window.resultsView = new ResultsView();
