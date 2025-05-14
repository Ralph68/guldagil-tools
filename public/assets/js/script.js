// script.js — logique dynamique du formulaire principal

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('tarif-form');
  const dep = document.getElementById('departement');
  const poids = document.getElementById('poids');

  // Focus automatique sur poids après 2 chiffres de département
  dep.addEventListener('input', () => {
    if (dep.value.length === 2) {
      poids.focus();
    }
  });

  // Réinitialisation du champ département au clic
  dep.addEventListener('focus', () => {
    dep.value = '';
  });

  // Déclenche la soumission si tout est rempli
  form.addEventListener('change', () => {
    const ready = (
      dep.value.length === 2 &&
      poids.value && parseFloat(poids.value) > 0 &&
      form.type?.value &&
      form.adr?.value &&
      form.option?.value
    );

    if (ready) form.submit();
  });
});
