// Atribución: se guarda en sessionStorage porque el usuario puede llegar con
// UTMs a una página y enviar el formulario desde otra.
const LEADIVO_ATTRIBUTION_KEY = "leadivo_attribution";
const LEADIVO_CAMPAIGN_PARAMS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid", "msclkid"];

function leadivoReadAttribution() {
    try {
        return JSON.parse(sessionStorage.getItem(LEADIVO_ATTRIBUTION_KEY)) || {};
    } catch (err) {
        return {};
    }
}

function leadivoExternalReferrer() {
    try {
        if (document.referrer && new URL(document.referrer).host !== window.location.host) {
            return document.referrer;
        }
    } catch (err) {
        // Referente mal formado: se ignora
    }
    return "";
}

function leadivoCaptureAttribution() {
    const stored = leadivoReadAttribution();
    const params = new URLSearchParams(window.location.search);
    const campaign = {};

    LEADIVO_CAMPAIGN_PARAMS.forEach(function (key) {
        const value = params.get(key);
        if (value) {
            campaign[key] = value;
        }
    });

    // Primera página de la sesión: página de entrada y referente externo
    let attribution = stored;
    if (!stored.landing_page) {
        attribution = {
            landing_page: window.location.href,
            referrer: leadivoExternalReferrer(),
        };
    }

    // Una nueva campaña reemplaza a la anterior (último clic)
    if (Object.keys(campaign).length) {
        LEADIVO_CAMPAIGN_PARAMS.forEach(function (key) {
            delete attribution[key];
        });
        Object.assign(attribution, campaign);
    }

    try {
        sessionStorage.setItem(LEADIVO_ATTRIBUTION_KEY, JSON.stringify(attribution));
    } catch (err) {
        // Almacenamiento no disponible (modo privado, bloqueado): se sigue sin atribución guardada
    }
    return attribution;
}

document.addEventListener("DOMContentLoaded", function () {
    const config = window.leadivoConfig || {};
    const attribution = leadivoCaptureAttribution();
    const whatsappButton = document.querySelector(".leadivo-button");
    const whatsappPopup = document.querySelector(".leadivo-popup");
    const whatsappForm = document.getElementById("leadivo-form");
    const closeButton = document.querySelector(".leadivo-close");

    if (!config.phoneNumber) {
        console.error("Número de WhatsApp no configurado.");
        return;
    }

    if (!whatsappButton || !whatsappPopup || !whatsappForm) {
        return;
    }

    // Hora en la burbuja de bienvenida, como en un mensaje recién recibido
    const bubbleTime = whatsappPopup.querySelector(".leadivo-bubble-time");

    function openPopup() {
        if (bubbleTime) {
            bubbleTime.textContent = new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        }
        whatsappPopup.hidden = false;
        whatsappButton.setAttribute("aria-expanded", "true");
        document.getElementById("leadivo-name").focus();
    }

    function closePopup() {
        whatsappPopup.hidden = true;
        whatsappButton.setAttribute("aria-expanded", "false");
        whatsappButton.focus();
    }

    // Medición del lead. Nunca se envían nombre ni email (GA4 prohíbe datos personales);
    // el mensaje solo se incluye si viene de un select, porque el texto libre puede contenerlos.
    function trackLead(message) {
        const eventData = {
            event: "whatsapp_lead",
            lead_source: "whatsapp_button",
            page_location: window.location.href,
        };
        if (config.fieldType === "select") {
            eventData.lead_topic = message;
        }

        try {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(eventData);
            document.dispatchEvent(new CustomEvent("leadivo:lead", { detail: eventData }));
        } catch (err) {
            console.error("Error al registrar el evento de WhatsApp:", err);
        }

        // Código de seguimiento personalizado (opcional, configurado en el admin)
        if (typeof window.leadivoTrack === "function") {
            window.leadivoTrack(eventData);
        }
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

        const name = document.getElementById("leadivo-name").value.trim();
        const emailField = document.getElementById("leadivo-email");
        const email = emailField ? emailField.value.trim() : "";
        const messageField = document.getElementById("leadivo-message");
        const message = messageField ? messageField.value.trim() : "";
        const phoneField = document.getElementById("leadivo-phone");
        const phone = phoneField ? phoneField.value.trim() : "";

        if (!name || (messageField && !message) || (emailField && !email)) {
            alert("Por favor, completa todos los campos.");
            return;
        }

        const consentField = document.getElementById("leadivo-consent");
        if (consentField && !consentField.checked) {
            alert("Debes aceptar el tratamiento de datos para continuar.");
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailField && !emailPattern.test(email)) {
            alert("Por favor, ingresa un correo electrónico válido.");
            return;
        }

        const fullMessage = config.messageTemplate
            .replaceAll("{name}", name)
            .replaceAll("{email}", email)
            .replaceAll("{message}", message)
            .replaceAll("{phone}", phone);

        const whatsappURL = `https://wa.me/${config.phoneNumber}?text=${encodeURIComponent(fullMessage)}`;

        trackLead(message);

        const payload = Object.assign({}, attribution, {
            action: "leadivo_submit_lead",
            nonce: config.nonce,
            website: document.getElementById("leadivo-website").value,
            name: name,
            email: email,
            phone: phone,
            message: message,
            consent: consentField && consentField.checked ? "1" : "",
            page_url: window.location.href,
            page_title: document.title,
        });

        // Enviar el lead por AJAX antes de abrir WhatsApp.
        // keepalive: la petición termina aunque el navegador cambie a la app de WhatsApp.
        fetch(config.ajaxUrl, {
            method: "POST",
            keepalive: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams(payload),
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
