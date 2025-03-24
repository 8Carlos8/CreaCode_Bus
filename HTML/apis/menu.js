// ./js/menu.js
const menuIcon = document.querySelector('.menu-icon');
const menu = document.querySelector('nav.menu');

menuIcon.addEventListener('click', () => {
    menu.classList.toggle('active');
});
