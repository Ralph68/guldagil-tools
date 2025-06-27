/**
 * Titre: Règles de validation centralisées
 * Chemin: /public/assets/js/modules/calculateur/models/validation.js
 * Version: 0.5 beta + build
 */

class ValidationModel {
    constructor() {
        this.rules = this.initializeRules();
        this.messages = CalculateurConfig.VALIDATION.MESSAGES;
    }

    initializeRules() {
        return {
            departement: {
                required: true,
                pattern: CalculateurConfig.VALIDATION.DEPT_PATTERN,
                custom: (value) => {
                    const num = parseInt(value);
                    return num >= 1 && num <= 95;
                }
            },
            poids: {
                required: true,
                type: 'number',
                min: CalculateurConfig.VALIDATION.MIN_POIDS,
                max: CalculateurConfig.VALIDATION.MAX_POIDS,
                custom: (value) => {
                    return !isNaN(parseFloat(value)) && parseFloat(value) > 0;
                }
            },
            type: {
                required: true,
                enum: ['colis', 'palette']
            },
            palettes: {
                type: 'number',
                min: 0,
                max: CalculateurConfig.VALIDATION.MAX_PALETTES,
                conditional: (data) => data.type === 'palette'
            },
            adr: {
                enum: ['oui', 'non']
            },
            service_livraison: {
                enum: Object.keys(CalculateurConfig.CARRIERS.SERVICES)
            },
            enlevement: {
                type: 'boolean'
            }
        };
    }

    validate(fieldName, value, allData = {}) {
        const rule = this.rules[fieldName];
        if (!rule) return { valid: true, errors: [] };

        const errors = [];

        // Required
        if (rule.required && this.isEmpty(value)) {
            errors.push(`${fieldName} requis`);
            return { valid: false, errors };
        }

        // Skip other validations if empty and not required
        if (this.isEmpty(value) && !rule.required) {
            return { valid: true, errors: [] };
        }

        // Type validation
        if (rule.type && !this.validateType(value, rule.type)) {
            errors.push(`Format ${rule.type} attendu`);
        }

        // Pattern validation
        if (rule.pattern && !rule.pattern.test(String(value))) {
            errors.push(this.getPatternMessage(fieldName));
        }

        // Enum validation
        if (rule.enum && !rule.enum.includes(value)) {
            errors.push(`Valeur non autorisée`);
        }

        // Min/Max validation
        if (rule.min !== undefined && parseFloat(value) < rule.min) {
            errors.push(`Minimum: ${rule.min}`);
        }
        if (rule.max !== undefined && parseFloat(value) > rule.max) {
            errors.push(`Maximum: ${rule.max}`);
        }

        // Custom validation
        if (rule.custom && !rule.custom(value, allData)) {
            errors.push(this.getCustomMessage(fieldName));
        }

        // Conditional validation
        if (rule.conditional && !rule.conditional(allData) && !this.isEmpty(value)) {
            errors.push('Champ non applicable');
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    validateAll(data) {
        const results = {};
        let isValid = true;

        Object.keys(this.rules).forEach(field => {
            const result = this.validate(field, data[field], data);
            results[field] = result;
            if (!result.valid) isValid = false;
        });

        // Business rules
        const businessErrors = this.validateBusinessRules(data);
        if (businessErrors.length > 0) {
            isValid = false;
            results._business = { valid: false, errors: businessErrors };
        }

        return {
            valid: isValid,
            fields: results,
            data
        };
    }

    validateBusinessRules(data) {
        const errors = [];

        // Colis > 60kg -> palette
        if (data.type === 'colis' && parseFloat(data.poids) > 60) {
            errors.push('Les colis sont limités à 60kg. Utilisez "Palette" pour ce poids.');
        }

        // ADR + services premium incompatibles
        if (data.adr === 'oui' && ['star18', 'star13', 'datefixe18', 'datefixe13'].includes(data.service_livraison)) {
            errors.push('Les services Star/Date fixe ne sont pas compatibles avec l\'ADR.');
        }

        // Palettes sans type palette
        if (data.palettes > 0 && data.type !== 'palette') {
            errors.push('Le nombre de palettes n\'est applicable qu\'au type "Palette".');
        }

        // Poids élevé + premium
        if (parseFloat(data.poids) > 1000 && ['star18', 'star13'].includes(data.service_livraison)) {
            errors.push('Les services Star sont limités à 1000kg maximum.');
        }

        return errors;
    }

    isEmpty(value) {
        return value === null || value === undefined || value === '';
    }

    validateType(value, type) {
        switch (type) {
            case 'number':
                return !isNaN(parseFloat(value));
            case 'boolean':
                return typeof value === 'boolean';
            case 'string':
                return typeof value === 'string';
            default:
                return true;
        }
    }

    getPatternMessage(fieldName) {
        const messages = {
            departement: this.messages.DEPT_INVALID,
            default: 'Format invalide'
        };
        return messages[fieldName] || messages.default;
    }

    getCustomMessage(fieldName) {
        const messages = {
            departement: 'Département français requis (01-95)',
            poids: 'Poids positif requis',
            default: 'Valeur invalide'
        };
        return messages[fieldName] || messages.default;
    }

    // Validation en temps réel
    validateField(fieldName, value, context = {}) {
        return this.validate(fieldName, value, context);
    }

    // Validation pour auto-complétion
    getSuggestions(fieldName, value) {
        switch (fieldName) {
            case 'departement':
                return this.getDepartementSuggestions(value);
            case 'service_livraison':
                return this.getServiceSuggestions(value);
            default:
                return [];
        }
    }

    getDepartementSuggestions(value) {
        const commonDepts = ['67', '75', '13', '69', '59', '44', '33', '31'];
        if (!value) return commonDepts;
        
        return commonDepts.filter(dept => 
            dept.startsWith(value) || dept.includes(value)
        );
    }

    getServiceSuggestions(value) {
        const services = Object.entries(CalculateurConfig.CARRIERS.SERVICES);
        if (!value) return services.map(([code, info]) => ({ code, label: info.label }));
        
        return services
            .filter(([code, info]) => 
                info.label.toLowerCase().includes(value.toLowerCase())
            )
            .map(([code, info]) => ({ code, label: info.label }));
    }

    // Validation contextuelle
    validateContext(data) {
        const context = {
            canUseADR: this.canUseADR(data),
            canUsePremium: this.canUsePremium(data),
            shouldUsePalette: this.shouldUsePalette(data),
            hasRestrictions: this.hasRestrictions(data)
        };

        return context;
    }

    canUseADR(data) {
        return !['star18', 'star13', 'datefixe18', 'datefixe13'].includes(data.service_livraison);
    }

    canUsePremium(data) {
        return data.adr !== 'oui' && parseFloat(data.poids) <= 1000;
    }

    shouldUsePalette(data) {
        return parseFloat(data.poids) > 60;
    }

    hasRestrictions(data) {
        const restrictedDepts = CalculateurConfig.BUSINESS?.DEPT_RESTRICTIONS?.DOM_TOM || [];
        return restrictedDepts.includes(data.departement);
    }

    // Validation par étapes
    validateStep(stepIndex, data) {
        switch (stepIndex) {
            case 0: // Destination & poids
                return this.validateStepFields(['departement', 'poids'], data);
            case 1: // Type
                return this.validateStepFields(['type'], data);
            case 2: // Options
                return this.validateStepFields(['adr', 'service_livraison'], data);
            default:
                return { valid: true, errors: [] };
        }
    }

    validateStepFields(fields, data) {
        const errors = [];
        let isValid = true;

        fields.forEach(field => {
            const result = this.validate(field, data[field], data);
            if (!result.valid) {
                errors.push(...result.errors);
                isValid = false;
            }
        });

        return { valid: isValid, errors };
    }

    // Messages d'aide
    getHelpText(fieldName) {
        const help = {
            departement: 'Saisissez le numéro du département français (01 à 95)',
            poids: `Poids en kg (${CalculateurConfig.VALIDATION.MIN_POIDS} à ${CalculateurConfig.VALIDATION.MAX_POIDS} kg)`,
            type: 'Colis: jusqu\'à 60kg, Palette: au-delà',
            palettes: 'Nombre de palettes EUR 80x120cm',
            adr: 'Marchandises dangereuses nécessitant déclaration ADR',
            service_livraison: 'Service de livraison selon vos besoins',
            enlevement: 'Service d\'enlèvement chez l\'expéditeur'
        };

        return help[fieldName] || '';
    }

    // Nettoyage et formatage
    sanitize(fieldName, value) {
        switch (fieldName) {
            case 'departement':
                return String(value).replace(/[^0-9]/g, '').slice(0, 2);
            case 'poids':
                return String(value).replace(',', '.');
            case 'palettes':
                return Math.max(0, parseInt(value) || 0);
            default:
                return value;
        }
    }
}

window.validationModel = new ValidationModel();
