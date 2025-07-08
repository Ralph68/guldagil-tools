// Redirection vers portal.js
import('./portal.js').then(module => {
    // Réexporter toutes les fonctions
    Object.assign(window, module);
});
