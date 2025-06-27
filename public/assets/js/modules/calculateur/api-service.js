// =====================================================================
// api-service.js - Service API
// =====================================================================
class ApiService {
    constructor(urls) {
        this.urls = urls;
        this.defaultHeaders = {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    async calculate(formData) {
        try {
            // Conversion FormData vers URLSearchParams
            const params = new URLSearchParams();
            Object.entries(formData).forEach(([key, value]) => {
                params.append(key, value);
            });
            params.append('ajax_calculate', '1');

            const response = await fetch(this.urls.calculate, {
                method: 'POST',
                headers: this.defaultHeaders,
                body: params
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.message || 'Erreur de calcul');
            }

            return data;

        } catch (error) {
            console.error('Erreur API:', error);
            throw new Error('Impossible de calculer les tarifs. Veuillez réessayer.');
        }
    }
}
