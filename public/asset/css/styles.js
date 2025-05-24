// Initialization for ES Users
import { Input, Tab, Ripple, initMDB } from "mdb-ui-kit";

initMDB({ Input, Tab, Ripple });



document.addEventListener('DOMContentLoaded', function() {
    const burger = document.getElementById('burger');
    const ulHeader = document.querySelector('.ulHeader');

    burger.addEventListener('click', function() {
        ulHeader.classList.toggle('active');
    });
});

