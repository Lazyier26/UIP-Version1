// EMAIL LINK FUNCTION
function openEmail(e) {
  e.preventDefault(); // stop default link
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