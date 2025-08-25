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

// initialize title on load
document.querySelector(".swiper-title").textContent =
  document.querySelector(".swiper-slide-active").getAttribute("data-title");

// MARQUEE
const slider = document.getElementById("slider");
    const track = document.getElementById("sliderTrack");

    let isDown = false;
    let startX;
    let scrollLeft;

    slider.addEventListener("mousedown", (e) => {
      isDown = true;
      slider.classList.add("active");
      startX = e.pageX - slider.offsetLeft;
      scrollLeft = slider.scrollLeft;
    });

    slider.addEventListener("mouseleave", () => {
      isDown = false;
    });

    slider.addEventListener("mouseup", () => {
      isDown = false;
    });

    slider.addEventListener("mousemove", (e) => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - slider.offsetLeft;
      const walk = (x - startX) * 2; // scroll speed
      slider.scrollLeft = scrollLeft - walk;
    });

    // ✅ Touch support (for mobile)
    slider.addEventListener("touchstart", (e) => {
      isDown = true;
      startX = e.touches[0].pageX - slider.offsetLeft;
      scrollLeft = slider.scrollLeft;
    });

    slider.addEventListener("touchend", () => {
      isDown = false;
    });

    slider.addEventListener("touchmove", (e) => {
      if (!isDown) return;
      const x = e.touches[0].pageX - slider.offsetLeft;
      const walk = (x - startX) * 2;
      slider.scrollLeft = scrollLeft - walk;
    });