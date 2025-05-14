document.addEventListener('DOMContentLoaded', () => {
  const departement = document.getElementById('departement');
  const poids = document.getElementById('poids');
  const enlevement = document.getElementById('enlevement');
  const options = document.querySelectorAll('input[name="option_sup"]');

  // Auto-focus poids après 2 chiffres
  departement.addEventListener('input', () => {
    if (departement.value.length === 2) poids.focus();
  });

  // Réinitialiser département au focus
  departement.addEventListener('focus', () => {
    departement.select();
  });

  // Gestion des options si enlèvement est coché
  enlevement.addEventListener('change', () => {
    if (enlevement.checked) {
      options.forEach(opt => {
        opt.disabled = true;
        if (opt.value === 'standard') opt.checked = true;
      });
    } else {
      options.forEach(opt => {
        opt.disabled = false;
      });
    }
  });

  // Initialiser au chargement
  if (enlevement.checked) {
    options.forEach(opt => {
      opt.disabled = true;
      if (opt.value === 'standard') opt.checked = true;
    });
  }
});
