document.addEventListener('DOMContentLoaded', function() {
    const burger = document.getElementById('burger');
    const ulHeader = document.querySelector('.ulHeader');

    burger.addEventListener('click', function() {
        ulHeader.classList.toggle('active');
    });
});