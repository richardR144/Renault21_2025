// Menu burger
document.addEventListener('DOMContentLoaded', ()=> {
    const burger = document.querySelector('#burger');
    const ulHeader = document.querySelector('.ulHeader');
    
    if (burger && ulHeader) {
        burger.addEventListener('click', ()=> {
            ulHeader.classList.toggle('active');
        });
    }
    });