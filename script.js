
let data = [];

fetch('tarifs_guldagil.json')
    .then(response => response.json())
    .then(json => { data = json; });

document.getElementById('tarifForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const dep = document.getElementById('departement').value.trim();
    const poids = parseFloat(document.getElementById('poids').value);
    const adr = document.getElementById('adr').value === 'true';

    const match = data.find(row =>
        row['DEPARTEMENT'] === dep &&
        row['POIDS'] >= poids - 0.01 && row['POIDS'] <= poids + 0.01 &&
        String(row['ADR']).toLowerCase() === String(adr).toLowerCase()
    );

    const res = document.getElementById('resultat');
    if (match) {
        res.innerHTML = `
            <p><strong>Tarif HEPPNER :</strong> ${match['HEPPNER']} €</p>
            <p><strong>Tarif XPO :</strong> ${match['XPO']} €</p>
        `;
    } else {
        res.innerHTML = "<p style='color:red;'>Aucune correspondance trouvée pour ces critères.</p>";
    }
});
