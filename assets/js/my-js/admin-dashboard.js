document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = "http://localhost/apc/backend";
    const token = localStorage.getItem("token");
    let user;

    try {
        user = JSON.parse(localStorage.getItem("user") || "null");
    } catch (error) {
        console.error("Could not read the saved admin profile:", error);
        window.location.replace("login.html");
        return;
    }

    if (!token || !user || user.role !== "admin") {
        window.location.replace(user && user.role === "member" ? "dashboard.html" : "login.html");
        return;
    }

    const memberCount = document.getElementById("member-count");
    const membersTableBody = document.getElementById("members-table-body");
    const membersMessage = document.getElementById("members-message");
    const eventMessage = document.getElementById("event-message");
    const refreshButton = document.getElementById("refresh-members");
    const eventForm = document.getElementById("create-event-form");
    const createEventButton = document.getElementById("create-event-button");
    const eventDateInput = document.getElementById("event-date");
    const eventPhotoInput = document.getElementById("event-photo");
    const eventPhotoPreview = document.getElementById("event-photo-preview");
    let eventPhotoPreviewUrl = "";

    document.getElementById("admin-user-name").textContent = user.name || user.email || "Admin";

    const localDate = new Date();
    localDate.setMinutes(localDate.getMinutes() - localDate.getTimezoneOffset());
    eventDateInput.max = localDate.toISOString().slice(0, 10);

    const logout = () => {
        localStorage.removeItem("token");
        localStorage.removeItem("refresh_token");
        localStorage.removeItem("user");
        window.location.replace("login.html");
    };

    document.getElementById("logout-button").addEventListener("click", logout);

    const clearEventPhotoPreview = () => {
        if (eventPhotoPreviewUrl) URL.revokeObjectURL(eventPhotoPreviewUrl);
        eventPhotoPreviewUrl = "";
        eventPhotoPreview.hidden = true;
        eventPhotoPreview.removeAttribute("src");
    };

    eventPhotoInput.addEventListener("change", () => {
        clearEventPhotoPreview();
        const photo = eventPhotoInput.files[0];
        if (!photo) return;

        eventPhotoPreviewUrl = URL.createObjectURL(photo);
        eventPhotoPreview.src = eventPhotoPreviewUrl;
        eventPhotoPreview.hidden = false;
    });

    const setMessage = (element, message, type) => {
        element.textContent = message;
        element.className = `admin-message${type ? ` admin-message--${type}` : ""}`;
    };

    const request = async (path, options = {}) => {
        const response = await fetch(`${baseUrl}${path}`, {
            ...options,
            headers: {
                Authorization: `Bearer ${token}`,
                ...(options.headers || {})
            }
        });
        const result = await response.json();

        if (!response.ok || !result || result.status !== "success") {
            const error = new Error(result && result.message ? result.message : "The request failed.");
            error.status = response.status;
            throw error;
        }

        return result;
    };

    const handleRequestError = (error, messageElement) => {
        console.error("Admin dashboard request failed:", error);
        if (error.status === 401 || error.status === 403) {
            logout();
            return;
        }
        setMessage(messageElement, error.message || "Unable to complete the request.", "error");
    };

    const addCell = (row, value) => {
        const cell = document.createElement("td");
        cell.textContent = value === null || value === undefined || value === ""
            ? "—"
            : String(value);
        row.appendChild(cell);
    };

    const loadMembers = async () => {
        refreshButton.disabled = true;
        setMessage(membersMessage, "Loading members...", "");

        try {
            const result = await request("/admin/members", { cache: "no-store" });
            if (
                !result.data ||
                !Array.isArray(result.data.members) ||
                !Number.isInteger(Number(result.data.count))
            ) {
                throw new Error("The member list response had an unexpected format.");
            }

            const members = result.data.members;
            memberCount.textContent = new Intl.NumberFormat().format(Number(result.data.count));
            membersTableBody.replaceChildren();

            if (members.length === 0) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 6;
                cell.textContent = "No members have registered yet.";
                row.appendChild(cell);
                membersTableBody.appendChild(row);
            } else {
                const rows = document.createDocumentFragment();
                members.forEach((member) => {
                    const row = document.createElement("tr");
                    addCell(row, member.name);
                    addCell(row, member.membership_id);
                    addCell(row, member.email);
                    addCell(row, member.phone);
                    const location = [member.state_name, member.lga_name].filter(Boolean).join(" / ");
                    addCell(row, location);
                    addCell(row, member.status);
                    rows.appendChild(row);
                });
                membersTableBody.appendChild(rows);
            }

            setMessage(membersMessage, `Showing ${members.length} member${members.length === 1 ? "" : "s"}.`, "success");
        } catch (error) {
            handleRequestError(error, membersMessage);
            if (error.status !== 401 && error.status !== 403) {
                memberCount.textContent = "—";
                membersTableBody.replaceChildren();
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 6;
                cell.textContent = "Member list unavailable.";
                row.appendChild(cell);
                membersTableBody.appendChild(row);
            }
        } finally {
            refreshButton.disabled = false;
        }
    };

    refreshButton.addEventListener("click", loadMembers);

    eventForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        createEventButton.disabled = true;
        setMessage(eventMessage, "Uploading event photo and creating event...", "");

        try {
            const result = await request("/admin/events", {
                method: "POST",
                body: new FormData(eventForm)
            });
            eventForm.reset();
            clearEventPhotoPreview();
            setMessage(eventMessage, result.message || "Event created successfully.", "success");
        } catch (error) {
            handleRequestError(error, eventMessage);
        } finally {
            createEventButton.disabled = false;
        }
    });

    loadMembers();
});
