// calculator.js

const poidsInput = document.getElementById('poids');
const depInput = document.getElementById('departement');
const adrInput = document.getElementById('adr');
const rdvInput = document.getElementById('rdv');
const premiumInput = document.getElementById('premium');
const bestChoiceDiv = document.getElementById('best-choice');
const alternativesDiv = document.getElementById('alternatives');
const toggleBtn = document.getElementById('toggle-alternatives');

let tarifs = [];

fetch('./data/tarifs.json')
  .then(res => res.json())
  .then(data => {
    tarifs = data;
    addListeners();
  });

function addListeners() {
  [poidsInput, depInput, adrInput, rdvInput, premiumInput].forEach(el => {
    el.addEventListener('input', updateResult);
    el.addEventListener('change', updateResult);
  });

  toggleBtn.addEventListener('click', () => {
    alternativesDiv.classList.toggle('hidden');
  });
}

function updateResult() {
  const poids = parseFloat(poidsInput.value);
  const dep = depInput.value.trim();
  if (!poids || !dep) return;

  const adr = adrInput.checked;
  const rdv = rdvInput.checked;
  const premium = premiumInput.checked;

  const candidates = tarifs
    .filter(t => t.departement === dep && poids <= t.poids_max)
    .map(t => {
      let total = t.prix;
      if (adr) total += t.options.adr || 0;
      if (rdv) total += t.options.rdv || 0;
      if (premium) total += t.options.premium || 0;
      return { ...t, total };
    })
    .sort((a, b) => a.total - b.total);

  displayResults(candidates);
}

function displayResults(candidates) {
  bestChoiceDiv.innerHTML = '';
  alternativesDiv.innerHTML = '';
  if (!candidates.length) {
    bestChoiceDiv.textContent = 'Aucun transporteur disponible';
    return;
  }

  const best = candidates[0];
  bestChoiceDiv.appendChild(createCard(best));

  candidates.slice(1).forEach(alt => {
    const diff = (alt.total - best.total).toFixed(2);
    const card = createCard(alt, diff);
    alternativesDiv.appendChild(card);
  });
}

function createCard(data, diff = null) {
  const div = document.createElement('div');
  div.className = 'result-card';

  const h3 = document.createElement('h3');
  h3.textContent = `${data.transporteur}`;
  div.appendChild(h3);

  const prix = document.createElement('p');
  prix.textContent = `Prix estimé : ${data.total.toFixed(2)} €`;
  div.appendChild(prix);

  const delai = document.createElement('p');
  delai.textContent = `Délai : ${data.delai}`;
  div.appendChild(delai);

  if (diff !== null) {
    const ecart = document.createElement('p');
    ecart.textContent = `+ ${diff} € par rapport au meilleur choix`;
    div.appendChild(ecart);
  }

  return div;
}
