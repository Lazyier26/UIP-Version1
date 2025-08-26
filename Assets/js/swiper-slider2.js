// UNIV CAROUSEL SWIPER
const swiper2 = new Swiper('.mySwiper2', {
      effect: 'coverflow',
      grabCursor: true,
      centeredSlides: true,
      slidesPerView: 3,  
      loop: true,
      coverflowEffect: {
        rotate: 10,  
        stretch: 10,
        depth: 160,
        modifier: 2,
        slideShadows: true,
      },
      navigation: {
        nextEl: '.mySwiper2 .swiper-button-next',
        prevEl: '.mySwiper2 .swiper-button-prev',
      },

      autoplay: {
    delay: 2000,
    disableOnInteraction: false
  },
    });