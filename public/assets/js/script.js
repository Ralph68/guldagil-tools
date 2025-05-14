document.addEventListener('DOMContentLoaded', () => {
  const departement = document.getElementById('departement');
  const poids = document.getElementById('poids');
  const enlevement = document.getElementById('enlevement');
  const options = document.querySelectorAll('input[name="option_sup"]');

  // Auto-focus poids après 2 chiffres
  departement.addEventListener('input', () => {
    if (departement.value.length === 2) poids.focus();
  });

  departement.addEventListener('focus', () => {
    departement.select();
  });

  function toggleOptions(disable) {
    options.forEach(opt => {
      const label = document.querySelector(`label[for="${opt.id}"]`);
      opt.disabled = disable;
      if (disable) {
        opt.checked = (opt.value === 'standard');
        label.classList.add('disabled-option');
      } else {
        label.classList.remove('disabled-option');
      }
    });
  }

  enlevement.addEventListener('change', () => {
    toggleOptions(enlevement.checked);
  });

  // Initialiser au chargement
  if (enlevement.checked) {
    toggleOptions(true);
  }
});
