var swiper1 = new Swiper(".swiper1", {
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
      swiper1.slideTo(this.clickedIndex);
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
document.querySelector(".swiper-title1").textContent =
  document.querySelector(".swiper-slide1-active").getAttribute("data-title");
