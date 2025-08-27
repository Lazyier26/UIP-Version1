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

// SEARCH FUNCTION
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const searchResults = document.getElementById("searchResults");

const allSlides = document.querySelectorAll(".mySwiper2 .swiper-slide");

// Prevent form submit refresh
searchForm.addEventListener("submit", (e) => {
  e.preventDefault();
});

// Listen for typing
searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase().trim();
  searchResults.innerHTML = ""; 

  if (query.length === 0) return; 

  allSlides.forEach(slide => {
    const title = slide.querySelector("h2").innerText;
    const description = slide.querySelector("p").innerText;
    const text = (title + " " + description).toLowerCase();

    if (text.includes(query)) {
      const li = document.createElement("li");
      li.innerHTML = `<strong>${title}</strong> - ${description}`;

      searchResults.appendChild(li);
    }
  });
});


