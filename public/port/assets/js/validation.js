// validation.js
const validationModule = {
    // Configuration simple
    config: {
        departement: {
            pattern: /^[0-9]{2}$/,
            message: 'Le département doit être un nombre à 2 chiffres (01-95)'
        },
        poids: {
            min: 1,
            max: 2000,
            message: 'Le poids doit être entre 1 et 2000 kg'
        }
    },

    // Vérification du département
    validateDepartement(value) {
        const isValid = this.config.departement.pattern.test(value);
        console.log(`Validation département: ${value} -> ${isValid}`);
        return isValid;
    },

    // Vérification du poids
    validatePoids(value) {
        const isValid = value >= this.config.poids.min && value <= this.config.poids.max;
        console.log(`Validation poids: ${value} -> ${isValid}`);
        return isValid;
    }
};

export { validationModule };
