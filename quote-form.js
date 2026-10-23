/*
 * RTB - formulaire de devis
 *  - validation en français, message d'erreur à côté de chaque champ + résumé
 *  - téléphone OU e-mail obligatoire (l'un des deux suffit)
 *  - envoi sans rechargement : la saisie est conservée en cas d'erreur
 *  - confirmation visible, focus déplacé dessus, délai de réponse annoncé
 * Sans JavaScript, le formulaire s'envoie normalement vers contact-process.php (inchangé).
 */
(function () {
    "use strict";

    var form = document.querySelector("[data-quote-form]");
    if (!form) return;

    var body = form.querySelector("[data-form-body]");
    var summary = form.querySelector("[data-form-summary]");
    var success = form.querySelector("[data-form-success]");
    var successDetail = form.querySelector("[data-form-success-detail]");
    var alertBox = form.querySelector("[data-form-alert]");
    var live = form.querySelector("[data-form-live]");
    var submit = form.querySelector("[data-form-submit]");
    var again = form.querySelector("[data-form-again]");
    var submitLabel = submit ? submit.textContent : "";
    var sending = false;

    var fields = {
        projet: form.elements.projet,
        nom: form.elements.nom,
        telephone: form.elements.telephone,
        email: form.elements.email
    };

    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    function digitsOf(value) { return value.replace(/\D/g, ""); }

    function validPhone(value) {
        if (!/^\+?[0-9 .\-()]+$/.test(value)) return false;
        var n = digitsOf(value).length;
        return n >= 9 && n <= 15;
    }

    function validate() {
        var errors = [];
        var projet = fields.projet.value.trim();
        var nom = fields.nom.value.trim();
        var tel = fields.telephone.value.trim();
        var mail = fields.email.value.trim();

        if (!projet) errors.push([fields.projet, "Indiquez le type de travaux, par exemple : carrelage, terrasse ou ouverture de mur."]);
        if (!nom) errors.push([fields.nom, "Indiquez votre nom et votre prénom."]);

        if (!tel && !mail) {
            errors.push([fields.telephone, "Indiquez un numéro de téléphone ou une adresse e-mail pour que nous puissions vous répondre."]);
        } else {
            if (tel && !validPhone(tel)) errors.push([fields.telephone, "Ce numéro de téléphone n'est pas valide. Exemple : 06 12 34 56 78."]);
            if (mail && !EMAIL.test(mail)) errors.push([fields.email, "Cette adresse e-mail n'est pas valide. Exemple : nom@exemple.fr."]);
        }
        return errors;
    }

    function errorBox(input) {
        return form.querySelector('[data-error-for="' + input.id + '"]');
    }

    function clearErrors() {
        form.querySelectorAll("[data-error-for]").forEach(function (box) {
            box.hidden = true;
            box.textContent = "";
        });
        form.querySelectorAll("[aria-invalid]").forEach(function (input) { input.removeAttribute("aria-invalid"); });
        summary.hidden = true;
        summary.textContent = "";
    }

    function clearFieldError(input) {
        var box = errorBox(input);
        if (box && !box.hidden) { box.hidden = true; box.textContent = ""; }
        input.removeAttribute("aria-invalid");
        // Téléphone et e-mail forment un groupe : corriger l'un efface l'erreur « au moins un des deux ».
        if (input === fields.telephone || input === fields.email) {
            [fields.telephone, fields.email].forEach(function (other) {
                if (other !== input && other.value.trim() === "" && errorBox(other) && /Indiquez un numéro/.test(errorBox(other).textContent)) clearFieldError(other);
            });
        }
    }

    function showErrors(errors) {
        clearErrors();
        errors.forEach(function (pair) {
            var input = pair[0];
            var box = errorBox(input);
            input.setAttribute("aria-invalid", "true");
            if (box) { box.textContent = pair[1]; box.hidden = false; }
        });

        var title = document.createElement("strong");
        title.textContent = errors.length > 1 ? "Le formulaire contient " + errors.length + " erreurs :" : "Le formulaire contient une erreur :";
        var list = document.createElement("ul");
        errors.forEach(function (pair) {
            var li = document.createElement("li");
            var a = document.createElement("a");
            a.href = "#" + pair[0].id;
            a.textContent = pair[1];
            a.addEventListener("click", function (event) {
                event.preventDefault();
                pair[0].focus();
            });
            li.appendChild(a);
            list.appendChild(li);
        });
        summary.appendChild(title);
        summary.appendChild(list);
        summary.hidden = false;
        summary.focus();
    }

    function setSending(state) {
        sending = state;
        if (!submit) return;
        submit.setAttribute("aria-disabled", state ? "true" : "false");
        submit.textContent = state ? "Envoi en cours..." : submitLabel;
        if (live) live.textContent = state ? "Envoi de votre demande en cours." : "";
    }

    function showServerError(message) {
        alertBox.textContent = message;
        alertBox.className = "form-alert is-visible is-error";
        alertBox.setAttribute("role", "alert");
        alertBox.focus();
    }

    function hideAlert() {
        alertBox.textContent = "";
        alertBox.className = "form-alert";
        alertBox.removeAttribute("role");
    }

    function showSuccess() {
        var tel = fields.telephone.value.trim();
        var mail = fields.email.value.trim();
        var slot = form.elements.creneau ? form.elements.creneau.value : "";
        var text = "Nous vous rappelons sous 48 h";
        if (tel) text += " au " + tel;
        if (slot && tel) text += ", " + slot.charAt(0).toLowerCase() + slot.slice(1) + " si possible";
        text += ".";
        if (!tel && mail) text = "Nous vous répondons sous 48 h par e-mail, à l'adresse " + mail + ".";
        successDetail.textContent = text;

        hideAlert();
        clearErrors();
        body.hidden = true;
        success.hidden = false;
        // Une seule annonce : la confirmation reçoit le focus et c'est elle que le lecteur d'écran lit.
        // La zone « envoi en cours » est déjà vidée par setSending(false) ; on ne la réutilise pas ici.
        if (live) live.textContent = "";
        success.focus({ preventScroll: true });
        success.scrollIntoView({ block: "center", behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth" });

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: "rtb_lead_form_submit", page_type: "accueil", projet: fields.projet.value.slice(0, 100), ville: "" });
    }

    function interpret(response) {
        // Redirection vers index.html?status=... (ancien comportement du serveur)
        if (response.redirected) {
            try { return Promise.resolve(new URL(response.url).searchParams.get("status") === "success"); } catch (e) { return Promise.resolve(false); }
        }
        return response.json().then(function (data) { return !!(data && data.ok); }, function () { return response.ok; });
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (sending) return;

        hideAlert();
        var errors = validate();
        if (errors.length) {
            showErrors(errors);
            return;
        }
        clearErrors();
        setSending(true);

        fetch(form.action, {
            method: "POST",
            headers: { "Accept": "application/json", "X-Requested-With": "fetch", "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
            body: new URLSearchParams(new FormData(form)).toString()
        }).then(interpret).then(function (ok) {
            setSending(false);
            if (ok) showSuccess();
            else showServerError("L'envoi n'a pas abouti. Vos informations sont conservées : vérifiez-les puis réessayez, ou appelez-nous au 06 81 32 79 05.");
        }).catch(function () {
            setSending(false);
            showServerError("Nous n'avons pas pu envoyer votre demande (connexion indisponible ?). Vos informations sont conservées : réessayez dans un instant, ou appelez-nous au 06 81 32 79 05.");
        });
    });

    // Une erreur disparaît dès que le champ est corrigé.
    Object.keys(fields).forEach(function (key) {
        fields[key].addEventListener("input", function () { clearFieldError(fields[key]); });
    });

    if (again) {
        again.addEventListener("click", function () {
            form.reset();
            clearErrors();
            success.hidden = true;
            body.hidden = false;
            if (live) live.textContent = "";
            var title = form.querySelector("[data-devis-title]");
            if (title) title.focus();
        });
    }
})();
