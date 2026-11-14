const baseUrl = "http://localhost/apc/backend";
window.baseUrl = baseUrl;

// handle message display
function displayMessage(message, color) {
  const msg = document.getElementById("form-message");
  if (msg) {
    msg.textContent = message;
    msg.style.color = color;
    setTimeout(() => {
      msg.textContent = "";
    }, 3000);
  }
}

const loginBtn = document.getElementById("login-btn");
const btnText = loginBtn.querySelector(".btn-text");
const spinner = loginBtn.querySelector(".spinner-border");

document.getElementById("login-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  loginBtn.disabled = true;
  btnText.textContent = "Logging in...";
  spinner.classList.remove("d-none");

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  if (!email || !password) {
    alert("Please fill in all fields.");
    loginBtn.disabled = false;
    btnText.textContent = "LOGIN";
    spinner.classList.add("d-none");
    return;
  }

  try {
    const response = await fetch(`${baseUrl}/auth/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
    });

    if (response.status != 200) {
      const errorResult = await response.json();
      throw new Error(errorResult.message || "Login failed. Please try again.");
    }

    const result = await response.json();
    if (result.status && result.status === "error") {
      throw new Error(result.message || "Login failed. Please try again.");
    }

    const data = result.data;

    localStorage.setItem("token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));
    displayMessage(data.message || "Login successful!", "green");

    // Redirect to dashboard based on role
    if (data.user.role == "admin") {
      window.location.href = "admin-dashboard.html";
    } else if (data.user.role == "member") {
      window.location.href = "dashboard.html";
    }
  } catch (error) {
    displayMessage(error.message || "An error occurred during login.", "red");
    console.error("Error during login:", error);
  } finally {
    loginBtn.disabled = false;
    btnText.textContent = "LOGIN";
    spinner.classList.add("d-none");
  }
});
