/*HAMBURGER MENU*/
let menu = document.querySelector('#nav-toggle');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
  menu.classList.toggle('active');
  navbar.classList.toggle('open');
  document.body.classList.toggle('sidebar-open');
};

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
      const walk = (x - startX) * 2; 
      slider.scrollLeft = scrollLeft - walk;
    });

// DONATION POPUP
function showQR() {
    const overlay = document.getElementById("overlay");
    overlay.style.display = "flex";
    setTimeout(() => overlay.classList.add("active"), 10);
  }

  function closePopup(event) {
    const overlay = document.getElementById("overlay");
    if (!event || event.target === overlay || event.target.tagName === "BUTTON") {
      overlay.classList.remove("active");
      setTimeout(() => (overlay.style.display = "none"), 300);
    }
  }


// EMAIL LINK FUNCTION
function openEmail(e) {
  e.preventDefault(); 
  // Try Gmail first
  const gmailUrl = "https://mail.google.com/mail/?view=cm&fs=1&to=info@uip.ph";
  const mailtoUrl = "mailto:info@uip.ph";

  // Open Gmail in a new tab
  const win = window.open(gmailUrl, "_blank");

  // If popup blocked or Gmail can't open, fallback to mailto
  setTimeout(() => {
    if (!win || win.closed || typeof win.closed === "undefined") {
      window.location.href = mailtoUrl;
    }
  }, 1000);
}





