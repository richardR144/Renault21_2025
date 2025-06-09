import { Input, Tab, Ripple, initMDB } from "mdb-ui-kit";
initMDB({ Input, Tab, Ripple });



    // Gestion des onglets et boutons avec data-mdb-target
    const tabButtons = document.querySelectorAll('[data-mdb-target]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            const targetElement = document.querySelector(target);
            if (targetElement) {
                targetElement.classList.add('active');
            }
        });
    });

    // Bouton connexion/déconnexion
    const authBtn = document.getElementById('auth-btn');
    if (authBtn) {
        let isLoggedIn = false;
        function updateButton() {
            authBtn.textContent = isLoggedIn ? 'Se déconnecter' : 'Se connecter';
        }
        authBtn.addEventListener('click', function() {
            isLoggedIn = !isLoggedIn;
            updateButton();
        });
        updateButton();
    }

