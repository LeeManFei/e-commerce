document.addEventListener("DOMContentLoaded", () => {
  const formPopup = document.querySelector(".login-form");
  const signupLink = document.getElementById("signup-link");
  const loginLink = document.getElementById("login-link");
  const tempMsg = document.getElementById("temp-msg");

  // ---------------------------
  // TEMP MESSAGE LOGIC
  // ---------------------------
 
if (tempMsg && tempMsg.textContent.trim() !== "") {
  tempMsg.style.display = "block";
  tempMsg.style.opacity = 0;
  tempMsg.style.transform = "translateY(-20px)";

  let opacity = 0;
  let pos = -20;
  const interval = setInterval(() => {
    if (opacity >= 1) {
      clearInterval(interval);
    } else {
      opacity += 0.1;
      pos += 2;
      tempMsg.style.opacity = opacity;
      tempMsg.style.transform = `translateY(${pos}px)`;
    }
  }, 20);

  setTimeout(() => {
    tempMsg.style.transition = "all 0.5s ease";
    tempMsg.style.opacity = 0;
    tempMsg.style.transform = "translateY(-20px)";
    setTimeout(() => {
      tempMsg.style.display = "none";
    }, 500);
  }, 3000);
}

  // ---------------------------
  // SWITCH TO SIGNUP
  // ---------------------------
  if (signupLink) {
    signupLink.addEventListener("click", (e) => {
      e.preventDefault();
      formPopup.classList.add("show-signup");
    });
  }

  // ---------------------------
  // SWITCH BACK TO LOGIN
  // ---------------------------
  if (loginLink) {
    loginLink.addEventListener("click", (e) => {
      e.preventDefault();
      formPopup.classList.remove("show-signup");
    });
  }
});
