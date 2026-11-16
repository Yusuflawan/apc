document.addEventListener("DOMContentLoaded", () => {
    const eventsList = document.getElementById("past-events-list");
    if (!eventsList) return;

    const eventsApiUrl = "http://localhost/apc/backend/events";

    const showStatus = (message) => {
        const status = document.createElement("p");
        status.className = "col-xs-12 past-events__status";
        status.textContent = message;
        eventsList.replaceChildren(status);
    };

    const formatDate = (value) => {
        if (typeof value !== "string" || value.trim() === "") return null;

        const dateValue = value.trim();
        const parsedDate = new Date(/^\d{4}-\d{2}-\d{2}$/.test(dateValue)
            ? `${dateValue}T00:00:00Z`
            : dateValue);

        if (Number.isNaN(parsedDate.getTime())) return null;

        return {
            datetime: dateValue,
            label: new Intl.DateTimeFormat(undefined, {
                dateStyle: "long",
                timeZone: "UTC"
            }).format(parsedDate)
        };
    };

    const createEventCard = (event) => {
        const column = document.createElement("div");
        column.className = "col-xs-12 col-sm-6 col-md-4";

        const card = document.createElement("article");
        card.className = "past-event-card";

        if (typeof event.photo_url === "string" && event.photo_url.trim() !== "") {
            try {
                const photoUrl = new URL(event.photo_url.trim(), eventsApiUrl);
                if (photoUrl.protocol === "http:" || photoUrl.protocol === "https:") {
                    const image = document.createElement("img");
                    image.className = "past-event-card__image";
                    image.src = photoUrl.href;
                    image.alt = event.description
                        ? `Photo from ${event.description}`
                        : "Past event";
                    image.loading = "lazy";
                    card.appendChild(image);
                }
            } catch (error) {
                console.error("Could not load an event photo URL:", error);
            }
        }

        const body = document.createElement("div");
        body.className = "past-event-card__body";

        const date = formatDate(event.date);
        if (date) {
            const dateElement = document.createElement("time");
            dateElement.className = "past-event-card__date";
            dateElement.dateTime = date.datetime;
            dateElement.textContent = date.label;
            body.appendChild(dateElement);
        }

        const description = document.createElement("p");
        description.className = "past-event-card__description";
        description.textContent = typeof event.description === "string" && event.description.trim()
            ? event.description.trim()
            : "Event description unavailable.";
        body.appendChild(description);

        card.appendChild(body);
        column.appendChild(card);
        return column;
    };

    fetch(eventsApiUrl, { cache: "no-store" })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Events request failed with status ${response.status}.`);
            }
            return response.json();
        })
        .then((result) => {
            if (!result || result.status !== "success" || !Array.isArray(result.data)) {
                throw new Error("The events API returned an unexpected response.");
            }

            if (result.data.length === 0) {
                showStatus("There are no past events to display yet.");
                return;
            }

            const cards = result.data
                .filter((event) => event && typeof event === "object")
                .map(createEventCard);

            if (cards.length === 0) {
                showStatus("No event details are available to display.");
                return;
            }

            eventsList.replaceChildren(...cards);
        })
        .catch((error) => {
            console.error("Could not retrieve past events:", error);
            showStatus("Past events could not be loaded. Please try again later.");
        });
});
