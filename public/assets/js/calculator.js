// calculator.js — UI only

// Réinitialise le formulaire et vide les résultats
document.getElementById('reset-btn').addEventListener('click', () => {
  const form = document.getElementById('calc-form');
  form.reset();
  const tbody = document.querySelector('.table-container tbody');
  if (tbody) tbody.innerHTML = '';
});

// Toggle d’affichage du tableau
document.getElementById('toggle-alternatives').addEventListener('click', () => {
  const tbl = document.querySelector('.table-container');
  if (tbl) tbl.classList.toggle('hidden');
});
