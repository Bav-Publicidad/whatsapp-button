// Atribución: se guarda en sessionStorage porque el usuario puede llegar con
// UTMs a una página y enviar el formulario desde otra.
const WAB_ATTRIBUTION_KEY = "wab_attribution";
const WAB_CAMPAIGN_PARAMS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid", "msclkid"];

function wabReadAttribution() {
    try {
        return JSON.parse(sessionStorage.getItem(WAB_ATTRIBUTION_KEY)) || {};
    } catch (err) {
        return {};
    }
}

function wabExternalReferrer() {
    try {
        if (document.referrer && new URL(document.referrer).host !== window.location.host) {
            return document.referrer;
        }
    } catch (err) {
        // Referente mal formado: se ignora
    }
    return "";
}

function wabCaptureAttribution() {
    const stored = wabReadAttribution();
    const params = new URLSearchParams(window.location.search);
    const campaign = {};

    WAB_CAMPAIGN_PARAMS.forEach(function (key) {
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
            referrer: wabExternalReferrer(),
        };
    }

    // Una nueva campaña reemplaza a la anterior (último clic)
    if (Object.keys(campaign).length) {
        WAB_CAMPAIGN_PARAMS.forEach(function (key) {
            delete attribution[key];
        });
        Object.assign(attribution, campaign);
    }

    try {
        sessionStorage.setItem(WAB_ATTRIBUTION_KEY, JSON.stringify(attribution));
    } catch (err) {
        // Almacenamiento no disponible (modo privado, bloqueado): se sigue sin atribución guardada
    }
    return attribution;
}

document.addEventListener("DOMContentLoaded", function () {
    const config = window.wabConfig || {};
    const attribution = wabCaptureAttribution();
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
            document.dispatchEvent(new CustomEvent("wab:lead", { detail: eventData }));
        } catch (err) {
            console.error("Error al registrar el evento de WhatsApp:", err);
        }

        // Código de seguimiento personalizado (opcional, configurado en el admin)
        if (typeof window.wabTrack === "function") {
            window.wabTrack(eventData);
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

        const name = document.getElementById("whatsapp-name").value.trim();
        const email = document.getElementById("whatsapp-email").value.trim();
        const message = document.getElementById("whatsapp-message").value.trim();
        const phoneField = document.getElementById("whatsapp-phone");
        const phone = phoneField ? phoneField.value.trim() : "";

        if (!name || !email || !message) {
            alert("Por favor, completa todos los campos.");
            return;
        }

        const consentField = document.getElementById("whatsapp-consent");
        if (consentField && !consentField.checked) {
            alert("Debes aceptar el tratamiento de datos para continuar.");
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
            .replaceAll("{message}", message)
            .replaceAll("{phone}", phone);

        const whatsappURL = `https://wa.me/${config.phoneNumber}?text=${encodeURIComponent(fullMessage)}`;

        trackLead(message);

        const payload = Object.assign({}, attribution, {
            action: "wab_submit_lead",
            nonce: config.nonce,
            website: document.getElementById("whatsapp-website").value,
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
