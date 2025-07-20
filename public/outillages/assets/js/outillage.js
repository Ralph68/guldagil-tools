// Module Outillage - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Module Outillage chargé');
    
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observer toutes les cartes
    document.querySelectorAll('.stat-card, .action-card').forEach(card => {
        observer.observe(card);
    });

    // Actualisation automatique des stats toutes les 5 minutes
    setInterval(refreshStats, 300000);
});

function refreshStats() {
    // Rafraîchir les statistiques via AJAX
    fetch('./ajax/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.stats);
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function updateStatsDisplay(stats) {
    document.querySelector('.stat-card:nth-child(1) h3').textContent = stats.total_outils;
    document.querySelector('.stat-card:nth-child(2) h3').textContent = stats.outils_attribues;
    document.querySelector('.stat-card:nth-child(3) h3').textContent = stats.demandes_attente;
    document.querySelector('.stat-card:nth-child(4) h3').textContent = stats.maintenance_due;
}