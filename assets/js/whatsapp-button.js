document.addEventListener("DOMContentLoaded", function () {
    const config = window.wabConfig || {};
    const whatsappButton = document.querySelector(".whatsapp-button");
    const whatsappPopup = document.querySelector(".whatsapp-popup");
    const whatsappForm = document.getElementById("whatsapp-form");
    const closeButton = document.querySelector(".whatsapp-close");

    if (!config.phoneNumber) {
        console.error("Número de WhatsApp no configurado.");
        return;
    }

    if (!whatsappButton || !whatsappPopup || !whatsappForm) {
        return;
    }

    function openPopup() {
        whatsappPopup.hidden = false;
        whatsappButton.setAttribute("aria-expanded", "true");
        document.getElementById("whatsapp-name").focus();
    }

    function closePopup() {
        whatsappPopup.hidden = true;
        whatsappButton.setAttribute("aria-expanded", "false");
        whatsappButton.focus();
    }

    whatsappButton.addEventListener("click", function () {
        if (whatsappPopup.hidden) {
            openPopup();
        } else {
            closePopup();
        }
    });

    if (closeButton) {
        closeButton.addEventListener("click", closePopup);
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && !whatsappPopup.hidden) {
            closePopup();
        }
    });

    whatsappForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const name = document.getElementById("whatsapp-name").value.trim();
        const email = document.getElementById("whatsapp-email").value.trim();
        const message = document.getElementById("whatsapp-message").value.trim();

        if (!name || !email || !message) {
            alert("Por favor, completa todos los campos.");
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            alert("Por favor, ingresa un correo electrónico válido.");
            return;
        }

        const fullMessage = config.messageTemplate
            .replaceAll("{name}", name)
            .replaceAll("{email}", email)
            .replaceAll("{message}", message);

        const whatsappURL = `https://wa.me/${config.phoneNumber}?text=${encodeURIComponent(fullMessage)}`;

        // Código de seguimiento
        if (typeof window.wabTrack === "function") {
            window.wabTrack();
        }

        const urlActual = window.location.href;

        // Enviar el lead por AJAX antes de abrir WhatsApp
        fetch(config.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "wab_submit_lead",
                nonce: config.nonce,
                website: document.getElementById("whatsapp-website").value,
                name: name,
                email: email,
                message: message,
                page_url: urlActual,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                console.log("Lead enviado:", data);
            })
            .catch((error) => {
                console.error("Error al enviar lead:", error);
            });

        window.open(whatsappURL, "_blank");

        whatsappForm.reset();
        closePopup();
    });
});
