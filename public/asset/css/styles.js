// Initialisation des users de MDB UI Kit
import { Input, Tab, Ripple, initMDB } from "mdb-ui-kit";

initMDB({ Input, Tab, Ripple });



document.addEventListener('DOMContentLoaded', function() {
    const burger = document.getElementById('burger');
    const ulHeader = document.querySelector('.ulHeader');

    burger.addEventListener('click', function() {
        ulHeader.classList.toggle('active');
    });
});

// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabElements = document.querySelectorAll('.nav-link');
    tabElements.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// Gestion des boutons radio
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('.form-check-input');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// Gestion des boutons de navigation
document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// Gestion des boutons de navigation pour les onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabNavButtons = document.querySelectorAll('.tab-nav-button');
    tabNavButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// Gestion des boutons de navigation pour les onglets avec des données
document.addEventListener('DOMContentLoaded', function() {
    const dataTabNavButtons = document.querySelectorAll('.data-tab-nav-button');
    dataTabNavButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// Gestion des boutons de navigation pour les onglets avec des données et des actions
document.addEventListener('DOMContentLoaded', function() {
    const actionTabNavButtons = document.querySelectorAll('.action-tab-nav-button');
    actionTabNavButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-mdb-target');
            const contentElements = document.querySelectorAll('.tab-content > div');
            contentElements.forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(target).classList.add('active');
        });
    });
});

// gestion du bouton se connecter se deconnecter
document.addEventListener('DOMContentLoaded', function() {
    // ...ton code burger...

    // Bouton connexion/déconnexion
    const authBtn = document.getElementById('auth-btn');
    if (authBtn) {
        let isLoggedIn = false; // À remplacer par ta logique réelle

        function updateButton() {
            authBtn.textContent = isLoggedIn ? 'Se déconnecter' : 'Se connecter';
        }

        authBtn.addEventListener('click', function() {
            isLoggedIn = !isLoggedIn;
            updateButton();
            // Ici, ajoute la vraie logique de connexion/déconnexion si besoin
        });

        updateButton();
    }
});

