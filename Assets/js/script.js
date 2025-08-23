/*HAMBURGER MENU*/
let menu = document.querySelector('#nav-toggle');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
  menu.classList.toggle('active');
  navbar.classList.toggle('open');
  document.body.classList.toggle('sidebar-open');
};

// TROPHY CAROUSEL
var swiper = new Swiper(".swiper", {
  effect: "coverflow",
  grabCursor: true,
  centeredSlides: true,
  initialSlide: 2,
  speed: 600,
  preventClicks: true,
  slidesPerView: "auto",
  coverflowEffect: {
    rotate: 0,
    stretch: 80,
    depth: 350,
    modifier: 1,
    slideShadows: true
  },

  autoplay: {
    delay: 3000,
    disableOnInteraction: false
  },
  
  on: {
    click(event) {
      swiper.slideTo(this.clickedIndex);
    },
    slideChange() {
      const activeSlide = this.slides[this.activeIndex];
      const titleText = activeSlide.getAttribute("data-title");
      const titleEl = document.querySelector(".swiper-title");
      titleEl.style.opacity = 0;
      setTimeout(() => {
        titleEl.textContent = titleText;
        titleEl.style.opacity = 1;
      }, 200);
    }
  }
});

document.querySelector(".swiper-title").textContent =
  document.querySelector(".swiper-slide-active").getAttribute("data-title");

