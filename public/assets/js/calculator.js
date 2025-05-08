// calculator.js

const poidsInput = document.getElementById('poids');
const depInput = document.getElementById('departement');
const adrInput = document.getElementById('adr');
const rdvInput = document.getElementById('rdv');
const premiumInput = document.getElementById('premium');
const dateInput = document.getElementById('date');
const resetBtn = document.getElementById('reset-btn');
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
  [poidsInput, depInput, adrInput, rdvInput, premiumInput, dateInput].forEach(el => {
    el.addEventListener('input', updateResult);
    el.addEventListener('change', updateResult);
    el.addEventListener('focus', () => el.value = '');
  });

  toggleBtn.addEventListener('click', () => {
    alternativesDiv.classList.toggle('hidden');
  });

  resetBtn.addEventListener('click', () => {
    document.getElementById('calc-form').reset();
    bestChoiceDiv.innerHTML = '';
    alternativesDiv.innerHTML = '';
    alternativesDiv.classList.add('hidden');
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
      let details = [];
      if (adr && t.options.adr) {
        total += t.options.adr;
        details.push(`+${t.options.adr}€ ADR`);
      }
      if (rdv && t.options.rdv) {
        total += t.options.rdv;
        details.push(`+${t.options.rdv}€ RDV`);
      }
      if (premium && t.options.premium) {
        total += t.options.premium;
        details.push(`+${t.options.premium}€ Premium`);
      }
      return { ...t, total, details };
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

  if (data.details && data.details.length) {
    const bubble = document.createElement('div');
    bubble.className = 'bubble-details';
    bubble.textContent = `Détail : ${data.details.join(', ')}`;
    div.appendChild(bubble);
  }

  return div;
}
