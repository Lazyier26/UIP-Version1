/*SHOW MENU*/
let menu = document.querySelector('#nav-toggle');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
  menu.classList.toggle('active');  // for icon switch
  navbar.classList.toggle('open');  // for showing menu
};