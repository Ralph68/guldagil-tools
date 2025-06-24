// =====================================================================
// state-manager.js - Gestionnaire d'état
// =====================================================================
class StateManager {
    constructor() {
        this.state = {
            formData: {},
            results: null,
            loading: false,
            errors: []
        };
        this.listeners = new Map();
    }

    setState(newState) {
        const oldState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.notifyListeners(oldState, this.state);
    }

    getState() {
        return { ...this.state };
    }

    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);
    }

    notifyListeners(oldState, newState) {
        this.listeners.forEach((callbacks, key) => {
            if (oldState[key] !== newState[key]) {
                callbacks.forEach(callback => callback(newState[key], oldState[key]));
            }
        });
    }

    reset() {
        this.setState({
            formData: {},
            results: null,
            loading: false,
            errors: []
        });
    }
}
