const baseUrl = "http://localhost/apc/backend";
window.baseUrl = baseUrl;

document.addEventListener("DOMContentLoaded", async () => {
  // Populate States Dropdown
  const stateSelect = document.getElementById("state");

  try {
    const response = await fetch(`${baseUrl}/states`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error("Failed to fetch states");
    }

    const result = await response.json();
    const states = result.data;

    // Clear existing options
    stateSelect.innerHTML = '<option value="">Select State</option>';

    // Populate the dropdown with the fetched states
    states.forEach((state) => {
      const option = document.createElement("option");
      option.value = state.id;
      option.textContent = state.state;

      // if (state.stateId === "32") {
      //     option.selected = true;
      // }

      stateSelect.appendChild(option);
    });

    stateSelect.dispatchEvent(new Event("change")); // Trigger change event to load LGAs for the default selected state

    // console.log(stateSelect.innerHTML);
  } catch (error) {
    console.error("Error fetching states:", error);
    stateSelect.innerHTML = '<option value="">Failed to load states</option>';
  }

  const lgaSelect = document.getElementById("lga");

  // Handle State Change to Populate LGAs
  stateSelect.addEventListener("change", async (e) => {
    const stateId = e.target.value; // Get the selected state ID
    // console.log("Selected State ID:", stateId); // Debugging

    // sId = stateId;
    // console.log( e.target.value); // Get the selected state ID
    lgaSelect.innerHTML = '<option value="">Loading...</option>'; // Show loading message

    if (!stateId) {
      lgaSelect.innerHTML = '<option value="">All LGA</option>'; // Reset LGA dropdown if no state is selected
      return;
    }

    try {
      // Fetch LGAs for the selected state
      const response = await fetch(`${baseUrl}/state/${stateId}/lgas`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error("Failed to fetch LGAs");
      }

      const result = await response.json();
      const lgas = result.data;
      lgaSelect.innerHTML = '<option value="">Select LGA</option>';

      // Populate the LGA dropdown with the fetched LGAs
      lgas.forEach((lga) => {
        const option = document.createElement("option");
        option.value = lga.id;
        option.textContent = lga.lga_name;
        lgaSelect.appendChild(option);
      });
    } catch (error) {
      console.error("Error loading LGAs:", error);
      lgaSelect.innerHTML = '<option value="">Failed to load LGAs</option>'; // Show error message in dropdown
    }
  });

//   lgaSelect.addEventListener("change", async (e) => {
    // const lgId = e.target.value; // Get the selected state ID

    // lgaId = lgId;
//   });

});



const registerForm = document.getElementById("register-form");
const formMessage = document.getElementById("form-message");
const steps = Array.from(registerForm.querySelectorAll(".form-step"));
const indicators = Array.from(document.querySelectorAll(".step-indicator"));
const backButton = document.getElementById("back-button");
const nextButton = document.getElementById("next-button");
const createButton = document.getElementById("create-button");
const photoInput = document.getElementById("photo");
const photoPreview = document.getElementById("photo-preview");
let currentStep = 0;
let photoPreviewUrl = "";

function showStep(stepIndex) {
    currentStep = stepIndex;
    steps.forEach((step, index) => {
        step.hidden = index !== currentStep;
        indicators[index].toggleAttribute("aria-current", index === currentStep);
        if (index === currentStep) indicators[index].setAttribute("aria-current", "step");
        indicators[index].classList.toggle("is-complete", index < currentStep);
    });
    backButton.hidden = currentStep === 0;
    nextButton.hidden = currentStep === steps.length - 1;
    createButton.hidden = currentStep !== steps.length - 1;
    formMessage.textContent = "";
    formMessage.dataset.state = "";
    steps[currentStep].querySelector("input, select").focus();
}

function validateCurrentStep() {
    const requiredFields = Array.from(steps[currentStep].querySelectorAll("input, select"));
    for (const field of requiredFields) {
        if (!field.reportValidity()) return false;
    }
    if (currentStep === 1) {
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm-password");
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("The passwords do not match.");
            confirmPassword.reportValidity();
            confirmPassword.setCustomValidity("");
            return false;
        }
    }
    return true;
}

nextButton.addEventListener("click", () => {
    if (validateCurrentStep()) showStep(currentStep + 1);
});

backButton.addEventListener("click", () => showStep(currentStep - 1));
showStep(0);

photoInput.addEventListener("change", () => {
    if (photoPreviewUrl) URL.revokeObjectURL(photoPreviewUrl);
    photoPreviewUrl = "";
    photoPreview.hidden = true;
    photoPreview.removeAttribute("src");

    const file = photoInput.files[0];
    if (file) {
        photoPreviewUrl = URL.createObjectURL(file);
        photoPreview.src = photoPreviewUrl;
        photoPreview.hidden = false;
    }
});

registerForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    formMessage.textContent = "";
    formMessage.dataset.state = "";

    if (!validateCurrentStep()) return;

    const password = document.getElementById("password").value;
    const formData = new FormData(registerForm);
    const photo = photoInput.files[0];
    createButton.disabled = true;
    createButton.textContent = "Creating account...";

    try {
        const uploadData = new FormData();
        uploadData.append("photo", photo);
        formMessage.textContent = "Uploading membership photo...";

        const uploadResponse = await fetch(`${baseUrl}/auth/upload-photo`, {
            method: "POST",
            body: uploadData
        });
        const uploadResult = await uploadResponse.json();
        if (!uploadResponse.ok || uploadResult.status !== "success" || !uploadResult.data.photo_url) {
            throw new Error(uploadResult.message || "Unable to upload the membership photo.");
        }

        formMessage.textContent = "Creating account...";
        const response = await fetch(`${baseUrl}/auth/register`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: formData.get("name").trim(),
                email: formData.get("email").trim(),
                phone: formData.get("phone").trim(),
                gender: formData.get("gender"),
                role: formData.get("role") || "member",
                state_id: formData.get("state"),
                lga_id: formData.get("lga"),
                account_name: formData.get("account-name").trim(),
                bank_name: formData.get("bank-name").trim(),
                account_number: formData.get("account-number").trim(),
                password,
                photo_url: uploadResult.data.photo_url
            })
        });
        const result = await response.json();
        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Unable to create the user.");
        }

        formMessage.textContent = result.message || "Member created successfully.";
        localStorage.setItem("user", JSON.stringify(result.data.user));
        localStorage.setItem("token", result.data.token);
        formMessage.dataset.state = "success";
        registerForm.reset();
        photoPreview.hidden = true;
        photoPreview.removeAttribute("src");
        if (photoPreviewUrl) URL.revokeObjectURL(photoPreviewUrl);
        photoPreviewUrl = "";
        setTimeout( () => {window.location.href = "dashboard.html"}, 3000);

    } catch (error) {
        formMessage.textContent = error.message || "Could not reach the server. Please try again.";
        formMessage.dataset.state = "error";
    } finally {
        createButton.disabled = false;
        createButton.textContent = "Create account";
    }
});