const navLinks = document.querySelectorAll(".nav-link, .brand, [data-section]");
const sections = document.querySelectorAll(".page-section");
const loginForm = document.querySelector(".login-form");
const registerForm = document.querySelector(".register-form");
const searchInput = document.querySelector(".search-box input");
const searchButton = document.querySelector(".search-box button");
const contactForm = document.querySelector(".contact-form");
const formMessage = document.querySelector(".form-message");

function showSection(sectionId) {
  sections.forEach((section) => {
    section.classList.toggle("active", section.id === sectionId);
  });

  document.querySelectorAll(".nav-link").forEach((link) => {
    link.classList.toggle("active", link.dataset.section === sectionId);
  });

  history.replaceState(null, "", `#${sectionId}`);
}

function showRegister() {
  loginForm.style.display = "none";
  registerForm.style.display = "block";
}

function showLogin() {
  registerForm.style.display = "none";
  loginForm.style.display = "block";
}

function searchSections() {
  const query = searchInput.value.trim().toLowerCase();

  if (!query) {
    searchInput.focus();
    return;
  }

  const matchedSection = [...sections].find((section) => {
    return section.textContent.toLowerCase().includes(query);
  });

  if (matchedSection) {
    showSection(matchedSection.id);
  } else {
    searchInput.value = "";
    searchInput.placeholder = "No match found";
    searchInput.focus();
  }
}

navLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    const sectionId = link.dataset.section;

    if (!sectionId) {
      return;
    }

    event.preventDefault();
    showSection(sectionId);
  });
});

searchButton.addEventListener("click", searchSections);

searchInput.addEventListener("keydown", (event) => {
  if (event.key === "Enter") {
    searchSections();
  }
});

contactForm.addEventListener("submit", (event) => {
  event.preventDefault();
  formMessage.textContent = "Message ready to send. Thank you for contacting LSPU support.";
  contactForm.reset();
});

const startingSection = location.hash.replace("#", "");

if (startingSection && document.getElementById(startingSection)) {
  showSection(startingSection);
}
