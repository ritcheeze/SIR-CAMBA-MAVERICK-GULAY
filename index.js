const loginForm = document.querySelector(".login-form");
const registerForm = document.querySelector(".register-form");
const getStartedBtn = document.querySelector("#getStartedBtn");
const signInSection = document.querySelector(".container");
const backgroundImage = document.querySelector(".bg-image");
const backgroundSlides = ["LSPU.jpg", "fish.jpg", "agriculture.jpg", "tech.webp"];
let currentBackground = 0;

function showRegister(){
  loginForm.style.display = "none";
  registerForm.style.display = "block";
}

function showLogin(){
  registerForm.style.display = "none";
  loginForm.style.display = "block";
}
const sections = document.querySelectorAll(".section");

function revealSections() {
    sections.forEach(section => {
        const sectionTop = section.getBoundingClientRect().top;

        if(sectionTop < window.innerHeight - 100){
            section.classList.add("show");
        }
    });
}

window.addEventListener("scroll", revealSections);
window.addEventListener("load", revealSections);

getStartedBtn.addEventListener("click", () => {
    showLogin();
    signInSection.scrollIntoView({ behavior: "smooth", block: "start" });
});

backgroundSlides.forEach(src => {
    const image = new Image();
    image.src = src;
});

setInterval(() => {
    currentBackground = (currentBackground + 1) % backgroundSlides.length;
    backgroundImage.src = backgroundSlides[currentBackground];
}, 5000);
