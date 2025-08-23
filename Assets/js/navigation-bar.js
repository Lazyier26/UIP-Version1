/*HAMBURGER MENU*/
let menu = document.querySelector('#nav-toggle');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
  menu.classList.toggle('active');
  navbar.classList.toggle('open');
  document.body.classList.toggle('sidebar-open');
};