document.addEventListener('DOMContentLoaded', () => {
    const savePreferencesButton = document.getElementById('save-preferences-btn');
    const preferencesForm = document.getElementById('preferences-form');

    if (savePreferencesButton && preferencesForm) {
        savePreferencesButton.addEventListener('click', () => {
            event.preventDefault();
            const formData = new FormData(preferencesForm);
            const preferences = {};
            formData.forEach((value, key) => {
                preferences[key] = value;
            });

            fetch('/user/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ preferences })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Préférences sauvegardées avec succès.');
                    } else {
                        alert('Erreur lors de la sauvegarde des préférences.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur réseau.');
                });
        });
    }
});
