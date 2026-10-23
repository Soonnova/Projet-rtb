(function () {
    // Identifiant Google Analytics 4 (flux « www.rtb.fr - GA4 »).
    // Aucun cookie ni script Google n'est chargé tant que le visiteur n'a pas cliqué sur « Tout accepter ».
    // Pour surcharger la configuration, définir window.RTB_TRACKING avant ce script.
    const config = Object.assign({
        gtmId: "",
        ga4Id: "G-7J694X3GQN",
        solocalId: "",
        debug: false
    }, window.RTB_TRACKING || {});

    const CONSENT_KEY = "rtb_consent";
    const CONSENT_MAX_AGE_MS = 180 * 24 * 60 * 60 * 1000; // la CNIL recommande de redemander le choix au bout de 6 mois environ
    const GA_COOKIE_MAX_AGE_S = 33696000;                  // 13 mois maximum pour les cookies de mesure d'audience

    window.dataLayer = window.dataLayer || [];

    // Paramètres autorisés vers Google Analytics : jamais de texte libre saisi par le visiteur.
    const GA_PARAMS = ["page_type", "cta_label", "cta_target", "phone_number", "email_address"];
    let gaActive = false;

    const forwardToGa = (eventName, payload) => {
        if (!gaActive || typeof window.gtag !== "function" || eventName === "rtb_page_view") return;

        const params = {};
        GA_PARAMS.forEach((key) => {
            if (payload && payload[key]) params[key] = String(payload[key]).slice(0, 100);
        });

        window.gtag("event", eventName, params);

        // Événement recommandé par Google pour compter une demande de devis comme conversion.
        if (eventName === "rtb_lead_form_submit") window.gtag("event", "generate_lead", { page_type: params.page_type || "" });
    };

    const pushEvent = (eventName, payload) => {
        const eventPayload = Object.assign({ event: eventName }, payload || {});
        window.dataLayer.push(eventPayload);
        forwardToGa(eventName, payload);

        if (config.debug) {
            console.log("[RTB tracking]", eventPayload);
        }
    };

    // Utilisé aussi par quote-form.js pour signaler une demande envoyée.
    window.rtbTrack = pushEvent;

    const getPageType = () => {
        const path = window.location.pathname.toLowerCase();

        if (path.includes("chantiers-recents")) return "chantiers";
        if (path.includes("mentions-legales")) return "mentions-legales";
        if (path.includes("politique-confidentialite")) return "politique-confidentialite";
        return "accueil";
    };

    const storeUtmParams = () => {
        const params = new URLSearchParams(window.location.search);
        const utmKeys = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid"];
        const trackingSource = {};

        utmKeys.forEach((key) => {
            const value = params.get(key);
            if (value) trackingSource[key] = value;
        });

        if (Object.keys(trackingSource).length) {
            sessionStorage.setItem("rtb_tracking_source", JSON.stringify(trackingSource));
        }

        return trackingSource;
    };

    const readStoredUtmParams = () => {
        try {
            return JSON.parse(sessionStorage.getItem("rtb_tracking_source") || "{}");
        } catch (error) {
            return {};
        }
    };

    /* ---------- Consentement (bandeau cookies) ---------- */
    const readConsent = () => {
        try {
            const stored = JSON.parse(localStorage.getItem(CONSENT_KEY) || "null");
            if (!stored || (stored.value !== "granted" && stored.value !== "denied")) return null;
            if (Date.now() - stored.time > CONSENT_MAX_AGE_MS) return null;
            return stored.value;
        } catch (error) {
            return null;
        }
    };

    const writeConsent = (value) => {
        try {
            localStorage.setItem(CONSENT_KEY, JSON.stringify({ value: value, time: Date.now() }));
        } catch (error) {
            // Stockage indisponible : le choix vaut pour la page en cours seulement.
        }
    };

    const deleteGaCookies = () => {
        const host = window.location.hostname;
        const domains = ["", host, "." + host.replace(/^www\./, "")];

        document.cookie.split(";").forEach((entry) => {
            const name = entry.split("=")[0].trim();
            if (name !== "_ga" && name.indexOf("_ga_") !== 0 && name !== "_gid" && name.indexOf("_gat") !== 0) return;
            domains.forEach((domain) => {
                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/" + (domain ? "; domain=" + domain : "");
            });
        });
    };

    const loadGtm = () => {
        if (!config.gtmId) return;

        window.dataLayer.push({ "gtm.start": Date.now(), event: "gtm.js" });

        const script = document.createElement("script");
        script.async = true;
        script.src = "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(config.gtmId);
        document.head.appendChild(script);
    };

    const loadGa4 = () => {
        if (!config.ga4Id || gaActive) return;
        window["ga-disable-" + config.ga4Id] = false;

        const script = document.createElement("script");
        script.async = true;
        script.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(config.ga4Id);
        document.head.appendChild(script);

        window.gtag = window.gtag || function () {
            window.dataLayer.push(arguments);
        };

        window.gtag("js", new Date());
        window.gtag("config", config.ga4Id, {
            anonymize_ip: true,
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
            cookie_expires: GA_COOKIE_MAX_AGE_S
        });
        gaActive = true;
    };

    const stopGa4 = () => {
        if (config.ga4Id) window["ga-disable-" + config.ga4Id] = true;
        gaActive = false;
        deleteGaCookies();
    };

    const initConsent = () => {
        if (!config.ga4Id) return;

        const banner = document.createElement("div");
        banner.className = "cookie-banner";
        banner.id = "cookie-banner";
        banner.setAttribute("role", "region");
        banner.setAttribute("aria-label", "Cookies et mesure d'audience");
        banner.setAttribute("tabindex", "-1");
        banner.hidden = true;
        banner.innerHTML =
            '<div class="cookie-banner-inner">' +
            '<p class="cookie-banner-text"><strong>Votre vie privée.</strong> ' +
            "Ce site utilise Google Analytics pour compter les visites, les appels et les demandes de devis, afin de l'améliorer. " +
            "Cette mesure n'est activée que si vous l'acceptez, et vous pouvez changer d'avis à tout moment. " +
            '<a href="politique-confidentialite.html#cookies">En savoir plus</a></p>' +
            '<div class="cookie-actions">' +
            '<button type="button" class="cookie-btn" data-consent="denied">Tout refuser</button>' +
            '<button type="button" class="cookie-btn" data-consent="granted">Tout accepter</button>' +
            "</div></div>";

        const skip = document.querySelector(".skip-link");
        if (skip && skip.parentNode) skip.parentNode.insertBefore(banner, skip.nextSibling);
        else document.body.insertBefore(banner, document.body.firstChild);

        const openBanner = (moveFocus) => {
            banner.hidden = false;
            if (moveFocus) banner.focus();
        };

        const closeBanner = () => {
            banner.hidden = true;
            const main = document.getElementById("main");
            if (main) main.focus({ preventScroll: true });
        };

        banner.addEventListener("click", (event) => {
            const button = event.target.closest("[data-consent]");
            if (!button) return;

            const choice = button.getAttribute("data-consent");
            const previous = readConsent();
            writeConsent(choice);

            if (choice === "granted") loadGa4();
            else if (previous === "granted" || gaActive) stopGa4();

            closeBanner();
        });

        // Lien « Gérer les cookies » dans le pied de page, pour changer d'avis à tout moment.
        const footerLinks = document.querySelector(".footer-links") || document.querySelector(".footer-inner");
        if (footerLinks) {
            const manage = document.createElement("button");
            manage.type = "button";
            manage.className = "cookie-link";
            manage.textContent = "Gérer les cookies";
            manage.addEventListener("click", () => openBanner(true));
            footerLinks.appendChild(manage);
        }

        const consent = readConsent();
        if (consent === "granted") loadGa4();
        else if (consent === null) openBanner(false);
    };

    const bindLinkTracking = () => {
        document.querySelectorAll('a[href^="tel:"]').forEach((link) => {
            link.addEventListener("click", () => {
                pushEvent("rtb_phone_click", {
                    page_type: getPageType(),
                    phone_number: link.getAttribute("href").replace("tel:", "")
                });
            });
        });

        document.querySelectorAll('a[href^="mailto:"]').forEach((link) => {
            link.addEventListener("click", () => {
                pushEvent("rtb_email_click", {
                    page_type: getPageType(),
                    email_address: link.getAttribute("href").replace("mailto:", "")
                });
            });
        });

        document.querySelectorAll('a[href^="https://wa.me/"]').forEach((link) => {
            link.addEventListener("click", () => {
                pushEvent("rtb_whatsapp_click", { page_type: getPageType() });
            });
        });

        document.querySelectorAll('a[href="#contact"], a[href="#top"], a[href$="#devis"], a[href="chantiers-recents.html"], .btn, .mobile-bar-btn').forEach((link) => {
            link.addEventListener("click", () => {
                pushEvent("rtb_cta_click", {
                    page_type: getPageType(),
                    cta_label: (link.textContent || "").trim(),
                    cta_target: link.getAttribute("href") || ""
                });
            });
        });
    };

    const bindFormTracking = () => {
        document.querySelectorAll('form[action*="contact-process.php"]:not([data-quote-form])').forEach((form) => {
            form.addEventListener("submit", () => {
                const formData = new FormData(form);

                pushEvent("rtb_lead_form_submit", {
                    page_type: getPageType(),
                    projet: (formData.get("projet") || "").toString().slice(0, 100),
                    ville: (formData.get("ville") || "").toString().slice(0, 100)
                });
            });
        });
    };

    const utmParams = Object.assign({}, readStoredUtmParams(), storeUtmParams());

    loadGtm();
    initConsent();

    pushEvent("rtb_page_view", {
        page_type: getPageType(),
        page_title: document.title,
        page_path: window.location.pathname,
        traffic_source: utmParams.utm_source || "",
        traffic_medium: utmParams.utm_medium || "",
        traffic_campaign: utmParams.utm_campaign || "",
        solocal_id: config.solocalId || ""
    });

    bindLinkTracking();
    bindFormTracking();
})();
