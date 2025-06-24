// =====================================================================
// results-controller.js - Contrôleur des résultats
// =====================================================================
class ResultsController {
    constructor() {
        this.container = document.querySelector('.results-content');
        this.states = {
            waiting: document.getElementById('results-waiting'),
            loading: document.getElementById('results-loading'),
            display: document.getElementById('results-display'),
            error: document.getElementById('results-error')
        };
    }

    showWaiting() {
        this.hideAllStates();
        if (this.states.waiting) {
            this.states.waiting.classList.add('active');
        }
    }

    showLoading() {
        this.hideAllStates();
        if (this.states.loading) {
            this.states.loading.classList.add('active');
        }
    }

    showResults(data) {
        this.hideAllStates();
        
        if (this.states.display) {
            this.states.display.innerHTML = this.renderResults(data);
            this.states.display.classList.add('active');
        }
    }

    showError(message) {
        this.hideAllStates();
        
        if (this.states.error) {
            const errorText = this.states.error.querySelector('.state-content p');
            if (errorText) {
                errorText.textContent = message;
            }
            this.states.error.classList.add('active');
        }
    }

    hideAllStates() {
        Object.values(this.states).forEach(state => {
            if (state) {
                state.classList.remove('active');
            }
        });
    }

    renderResults(data) {
        if (!data.carriers) {
            return '<p>Aucun résultat disponible</p>';
        }

        let html = '<div class="carriers-results">';

        // Trouver le meilleur prix
        let bestPrice = null;
        let bestCarrier = null;

        Object.entries(data.carriers).forEach(([carrier, info]) => {
            if (info.price && (bestPrice === null || info.price < bestPrice)) {
                bestPrice = info.price;
                bestCarrier = carrier;
            }
        });

        // Générer les cartes
        Object.entries(data.carriers).forEach(([carrier, info]) => {
            const isBest = carrier === bestCarrier && info.price;
            const isUnavailable = !info.price;

            html += `
                <div class="carrier-card ${isBest ? 'best-price' : ''} ${isUnavailable ? 'unavailable' : ''}">
                    <div class="carrier-info">
                        <h4>${info.name}</h4>
                        <p>${isUnavailable ? 'Service non disponible' : 'Tarif transporteur'}</p>
                    </div>
                    <div class="carrier-price ${isUnavailable ? 'unavailable' : ''}">
                        ${info.formatted}
                        ${isBest ? '<span class="best-price-badge">Meilleur prix</span>' : ''}
                    </div>
                </div>
            `;
        });

        html += '</div>';

        // Ajouter statistiques si disponibles
        if (data.stats) {
            html += this.renderStats(data.stats);
        }

        return html;
    }

    renderStats(stats) {
        return `
            <div class="calc-stats">
                <div class="stat-item">
                    <div class="stat-value">${stats.carriers_available || 0}</div>
                    <div class="stat-label">Transporteurs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.calculation_time || 0}ms</div>
                    <div class="stat-label">Temps calcul</div>
                </div>
            </div>
        `;
    }
}
