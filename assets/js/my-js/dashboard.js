document.addEventListener("DOMContentLoaded", () => {
    const downloadButton = document.getElementById("download-card");
    if (downloadButton) {
        downloadButton.addEventListener("click", () => window.print());
    }

    let user;

    try {
        user = JSON.parse(localStorage.getItem("user") || "null");
    } catch (error) {
        console.error("Could not read the saved member profile:", error);
        window.location.href = "login.html";
        return;
    }

    if (!user || typeof user !== "object") {
        window.location.href = "login.html";
        return;
    }

    const valueFrom = (...values) => {
        for (const value of values) {
            if (value !== undefined && value !== null && String(value).trim() !== "") {
                return value;
            }
        }
        return "";
    };

    const displayValue = (value, fallback = "Not provided") => {
        if (value && typeof value === "object") {
            return valueFrom(value.name, value.label, value.state, value.lga) || fallback;
        }
        return valueFrom(value) || fallback;
    };

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    const name = displayValue(user.name, "Member");
    const role = String(displayValue(user.role, "Member"));
    const status = String(displayValue(user.status, "Status unavailable"));
    const verification = valueFrom(user.verified, user.is_verified, user.isVerified);
    const isVerified = verification === true || verification === 1 ||
        (typeof verification === "string" && ["1", "true", "verified"].includes(verification.toLowerCase()));
    const photoUrl = valueFrom(user.photo_url, user.photo, user.avatar, user.profile_image, user.profileImage);
    const memberPhoto = document.getElementById("member-photo");
    const memberInitials = document.getElementById("member-initials");

    setText("member-name", name);
    setText("member-id", displayValue(
        valueFrom(user.membershipID, user.membership_id, user.membershipNo, user.membership_no, user.card_number),
        "Not assigned"
    ));
    setText("member-state", displayValue(valueFrom(user.state, user.state_name, user.stateName)));
    setText("member-lga", displayValue(valueFrom(user.lga, user.lga_name, user.lgaName)));
    setText("member-ward", displayValue(valueFrom(user.ward, user.ward_name, user.wardName)));
    setText("member-polling-unit", displayValue(valueFrom(
        user.polling_unit,
        user.pollingUnit,
        user.polling_unit_name,
        user.pollingUnitName
    )));
    setText("member-phone", displayValue(user.phone));
    setText("member-role", role.charAt(0).toUpperCase() + role.slice(1));
    setText("member-verification", isVerified ? "Verified" : "Verification pending");
    setText("member-status", status.charAt(0).toUpperCase() + status.slice(1));
    setText("verification-icon", isVerified ? "✓" : "!");

    if (memberInitials) {
        const initials = name.trim().split(/\s+/).slice(0, 2).map((part) => part.charAt(0)).join("");
        memberInitials.textContent = initials.toUpperCase() || "PB";
    }

    if (photoUrl && memberPhoto && memberInitials) {
        memberPhoto.alt = `Photo of ${name}`;
        memberPhoto.addEventListener("load", () => {
            memberPhoto.hidden = false;
            memberInitials.hidden = true;
        }, { once: true });

        memberPhoto.addEventListener("error", () => {
            memberPhoto.hidden = true;
            memberPhoto.removeAttribute("src");
        }, { once: true });

        memberPhoto.src = photoUrl;
    }

    const statusBadge = document.querySelector(".member-badge--status");
    if (statusBadge && status.toLowerCase() !== "active") {
        statusBadge.classList.add("is-inactive");
    }

    const verificationBadge = document.querySelector(".member-badge--verified");
    if (verificationBadge && !isVerified) {
        verificationBadge.classList.add("is-pending");
    }
});
