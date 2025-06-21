/**
 * Titre: Modèle données formulaire avec normalisation
 * Chemin: /public/assets/js/modules/calculateur/models/form-data.js
 * Version: 0.5 beta + build
 */

class FormDataModel {
    constructor() {
        this.defaultData = {
            departement: '',
            poids: '',
            type: '',
            adr: 'non',
            service_livraison: 'standard',
            enlevement: false,
            palettes: 0
        };
    }

    normalize(rawData) {
        const normalized = { ...this.defaultData };

        // Département - format 2 chiffres
        if (rawData.departement) {
            normalized.departement = String(rawData.departement)
                .replace(/[^0-9]/g, '')
                .padStart(2, '0')
                .slice(0, 2);
        }

        // Poids - float avec validation
        if (rawData.poids) {
            const poids = parseFloat(String(rawData.poids).replace(',', '.'));
            if (!isNaN(poids) && poids > 0) {
                normalized.poids = Math.min(poids, CalculateurConfig.VALIDATION.MAX_POIDS);
            }
        }

        // Type - validation valeurs autorisées
        if (['colis', 'palette'].includes(rawData.type)) {
            normalized.type = rawData.type;
        }

        // ADR - normalisation booléen/string
        if (typeof rawData.adr === 'boolean') {
            normalized.adr = rawData.adr ? 'oui' : 'non';
        } else if (['oui', 'non'].includes(rawData.adr)) {
            normalized.adr = rawData.adr;
        }

        // Service livraison - validation
        if (rawData.service_livraison && typeof rawData.service_livraison === 'string') {
            normalized.service_livraison = rawData.service_livraison;
        }

        // Enlèvement - booléen
        normalized.enlevement = Boolean(rawData.enlevement);

        // Palettes - entier positif
        if (rawData.palettes) {
            const palettes = parseInt(rawData.palettes);
            if (!isNaN(palettes) && palettes >= 0) {
                normalized.palettes = Math.min(palettes, CalculateurConfig.VALIDATION.MAX_PALETTES);
            }
        }

        return normalized;
    }

    validateRequiredFields(data) {
        const errors = {};

        if (!data.departement) {
            errors.departement = 'Département requis';
        }

        if (!data.poids) {
            errors.poids = 'Poids requis';
        }

        if (!data.type) {
            errors.type = 'Type d\'envoi requis';
        }

        return errors;
    }

    isComplete(data) {
        const required = this.validateRequiredFields(data);
        return Object.keys(required).length === 0;
    }

    diff(oldData, newData) {
        const changes = {};
        
        Object.keys(newData).forEach(key => {
            if (oldData[key] !== newData[key]) {
                changes[key] = {
                    old: oldData[key],
                    new: newData[key]
                };
            }
        });

        return changes;
    }

    export(data) {
        return JSON.parse(JSON.stringify(data));
    }

    import(data) {
        return this.normalize(data);
    }
}

window.formDataModel = new FormDataModel();
