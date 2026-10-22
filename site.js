/*
 * RTB - comportements partagés par toutes les pages
 *  - menu mobile (ouverture, fermeture, touche Échap, libellé accessible)
 *  - bouton « Devis » : défilement doux + focus sur le formulaire de devis
 */
(function () {
    "use strict";

    var toggle = document.querySelector("[data-menu-toggle]");
    var navWrap = document.querySelector("[data-nav-wrap]");
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    /* ---------- Menu mobile ---------- */
    function setMenu(open) {
        if (!toggle || !navWrap) return;
        navWrap.classList.toggle("open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        toggle.setAttribute("aria-label", open ? "Fermer le menu" : "Ouvrir le menu");
        toggle.textContent = open ? "\u2715" : "\u2630";
    }

    if (toggle && navWrap) {
        toggle.addEventListener("click", function () {
            setMenu(!navWrap.classList.contains("open"));
        });

        navWrap.addEventListener("click", function (event) {
            if (event.target.closest("a")) setMenu(false);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || !navWrap.classList.contains("open")) return;
            var focusInMenu = navWrap.contains(document.activeElement);
            setMenu(false);
            if (focusInMenu) toggle.focus();
        });

        document.addEventListener("click", function (event) {
            if (!navWrap.classList.contains("open")) return;
            if (!event.target.closest(".site-header")) setMenu(false);
        });
    }

    /* ---------- Bouton « Devis » ---------- */
    function sameDocument(url) {
        var strip = function (path) { return path.replace(/index\.html$/, ""); };
        return url.origin === window.location.origin && strip(url.pathname) === strip(window.location.pathname);
    }

    function focusDevis() {
        var form = document.getElementById("devis");
        if (!form) return;

        // Sur écran tactile, on évite d'ouvrir le clavier : le focus va sur le titre du formulaire.
        var touch = window.matchMedia("(pointer: coarse)").matches;
        var target = touch
            ? form.querySelector("[data-devis-title]")
            : form.querySelector("[data-devis-first]");

        if (target) target.focus({ preventScroll: true });
    }

    function goToDevis() {
        var form = document.getElementById("devis");
        if (!form) return;

        form.scrollIntoView({ behavior: reduceMotion.matches ? "auto" : "smooth", block: "start" });
        focusDevis();
    }

    document.addEventListener("click", function (event) {
        var link = event.target.closest("a[href]");
        if (!link || event.defaultPrevented) return;
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (error) {
            return;
        }

        if (url.hash !== "#devis" || !sameDocument(url) || !document.getElementById("devis")) return;

        event.preventDefault();
        setMenu(false);
        goToDevis();

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, "", "#devis");
        }
    });

    // Arrivée depuis une autre page (index.html#devis) ou changement de hash au clavier.
    // Le navigateur réinitialise le focus quand il défile vers l'ancre : on attend la fin du chargement.
    if (window.location.hash === "#devis") {
        var focusAfterLoad = function () { window.setTimeout(focusDevis, 60); };
        if (document.readyState === "complete") focusAfterLoad();
        else window.addEventListener("load", focusAfterLoad);
    }
    window.addEventListener("hashchange", function () {
        if (window.location.hash === "#devis") focusDevis();
    });
})();
