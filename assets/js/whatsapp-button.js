document.addEventListener("DOMContentLoaded", function () {
    const config = window.wabConfig || {};
    const whatsappButton = document.querySelector(".whatsapp-button");
    const whatsappPopup = document.querySelector(".whatsapp-popup");
    const whatsappForm = document.getElementById("whatsapp-form");

    if (!config.phoneNumber) {
        console.error("Número de WhatsApp no configurado.");
        return;
    }

    if (!whatsappButton || !whatsappPopup || !whatsappForm) {
        return;
    }

    whatsappButton.addEventListener("click", function () {
        if (whatsappPopup.style.display === "none") {
            whatsappPopup.style.display = "block";
        } else {
            whatsappPopup.style.display = "none";
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
            .replace("{name}", name)
            .replace("{email}", email)
            .replace("{message}", message);

        const whatsappURL = `https://wa.me/${config.phoneNumber}?text=${encodeURIComponent(fullMessage)}`;

        // Código de seguimiento
        if (typeof window.wabTrack === "function") {
            window.wabTrack();
        }

        const urlActual = window.location.href;

        // Enviar el lead por AJAX antes de abrir WhatsApp
        fetch(config.leadEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
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
    });
});
