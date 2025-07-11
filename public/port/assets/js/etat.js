// etat.js
const stateModule = {
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        lastError: null
    },

    // Mise à jour de l'état
    updateState(newState) {
        console.log('Mise à jour de l\'état:', newState);
        this.state = { ...this.state, ...newState };
    }
};

export { stateModule };
