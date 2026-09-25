(function () {
    const config = Object.assign({
        gtmId: "",
        ga4Id: "",
        solocalId: "",
        debug: false
    }, window.RTB_TRACKING || {});

    window.dataLayer = window.dataLayer || [];

    const pushEvent = (eventName, payload) => {
        const eventPayload = Object.assign({ event: eventName }, payload || {});
        window.dataLayer.push(eventPayload);

        if (config.debug) {
            console.log("[RTB tracking]", eventPayload);
        }
    };

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

    const loadGtm = () => {
        if (!config.gtmId) return;

        window.dataLayer.push({ "gtm.start": Date.now(), event: "gtm.js" });

        const script = document.createElement("script");
        script.async = true;
        script.src = "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(config.gtmId);
        document.head.appendChild(script);
    };

    const loadGa4 = () => {
        if (!config.ga4Id) return;

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
            allow_google_signals: false
        });
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

        document.querySelectorAll('a[href="#contact"], a[href="chantiers-recents.html"], .btn').forEach((link) => {
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
        document.querySelectorAll('form[action*="contact-process.php"]').forEach((form) => {
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
    loadGa4();

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
